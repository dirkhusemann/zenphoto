<?php

// force UTF-8 Ø

require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
if (!file_exists(dirname(__FILE__) . '/' . DATA_FOLDER . "/zp-config.php")) {
	$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($dir, -1) == '/') $dir = substr($dir, 0, -1);
	$location = "http://". $_SERVER['HTTP_HOST']. $dir . "/" . ZENFOLDER . "/setup.php";
	header("Location: $location" );
}
define('OFFSET_PATH', 0);

require_once(ZENFOLDER . "/template-functions.php");
if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

/**
 * Invoke the controller to handle requests
 */
require_once(dirname(__FILE__). "/".ZENFOLDER.'/controller.php');

header ('Content-Type: text/html; charset=' . getOption('charset'));
$obj = '';

// Display an arbitrary theme-included PHP page
if (isset($_GET['p'])) {
	handleSearchParms('page', $_zp_current_album, $_zp_current_image);
	$theme = setupTheme();
	$page = str_replace(array('/','\\','.'), '', sanitize($_GET['p']));
	if (strpos($page, '*')===0) {
		$page = substr($page,1); // handle old zenfolder page urls
		$_GET['z'] = true;
	}
	if (isset($_GET['z'])) { // system page
		$_zp_gallery_page = basename($obj = ZENFOLDER."/".$page.".php");
	} else {
		$obj = THEMEFOLDER."/$theme/$page.php";
		$_zp_gallery_page = basename($obj);
		if (!zp_loggedin()) setOption('Page-Hitcounter-'.$page, getOption('Page-Hitcounter-'.$page)+1);
	}

// Display an Image page.
} else if (in_context(ZP_IMAGE)) {
	handleSearchParms('image', $_zp_current_album, $_zp_current_image);
	$theme = setupTheme();
	$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/image.php");
	if (!isMyALbum($_zp_current_album->name, ALL_RIGHTS)) { //update hit counter
		$hc = $_zp_current_image->get('hitcounter')+1;
		$_zp_current_image->set('hitcounter', $hc);
		$_zp_current_image->save();
	}
	
// Display an Album page.
} else if (in_context(ZP_ALBUM)) {
	if ($_zp_current_album->isDynamic()) {
		$search = $_zp_current_album->getSearchEngine();
		zp_setcookie("zenphoto_image_search_params", $search->getSearchParams(), 0);
		set_context(ZP_INDEX | ZP_ALBUM);
	} else {
		handleSearchParms('album', $_zp_current_album);
	}
	$theme = setupTheme();
	$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/album.php");
	// update hit counter
	if (!isMyALbum($_zp_current_album->name, ALL_RIGHTS) && getCurrentPage() == 1) {
		$hc = $_zp_current_album->get('hitcounter')+1;
		$_zp_current_album->set('hitcounter', $hc);
		$_zp_current_album->save();
	}

	// Display the Index page.
} else if (in_context(ZP_INDEX)) {
	handleSearchParms('index');
	$theme = setupTheme();
	if (!zp_loggedin()) setOption('Page-Hitcounter-index', getOption('Page-Hitcounter-index')+1);
	$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/index.php");
}

if (!isset($theme)) {
	$theme = setupTheme();
}
if (DEBUG_PLUGINS) debugLog('Loading the "theme" plugins.');
foreach (getEnabledPlugins() as $extension=>$loadtype) {
	if ($loadtype <= 1) {
		if (DEBUG_PLUGINS) debugLog('    '.$extension.' ('.$loadtype.')');
		require_once(getPlugin($extension.'.php'));
	}
	$_zp_loaded_plugins[] = $extension;
}

$custom = SERVERPATH.'/'.THEMEFOLDER.'/'.internalToFilesystem($theme).'/functions.php';
if (file_exists($custom)) {
	require_once($custom);
} else {
	$custom = false;
}

// Load plugins, then load the requested $obj (page, image, album, or index; defined above).
if (file_exists(SERVERPATH . "/" . internalToFilesystem($obj)) && $zp_request) {
	if (checkforPassword(true)) { // password protected object
		$passwordpage = SERVERPATH.'/'.THEMEFOLDER.'/'.$theme.'/password.php';
		if (file_exists($passwordpage)) {
			header("HTTP/1.0 200 OK");
			header("Status: 200 OK");
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
			include($passwordpage);
			exposeZenPhotoInformations( $obj, $_zp_loaded_plugins, $theme, $_zp_filters );
			exit();
		}
	}
	// Zenpage automatic hitcounter update support
	if(function_exists("is_NewsArticle") AND !$_zp_loggedin) {
		if(is_NewsArticle()) {
			$hc = $_zp_current_zenpage_news->get('hitcounter')+1;
			$_zp_current_zenpage_news->set('hitcounter', $hc);
			$_zp_current_zenpage_news->save();
		}
		if(is_NewsCategory()) {
			$catname = sanitize($_GET['category'],3);
			query("UPDATE ".prefix('zenpage_news_categories')." SET `hitcounter` = `hitcounter`+1 WHERE `cat_link` = '".zp_escape_string($catname)."'",true);
		}
		if(is_Pages()) {
			$hc = $_zp_current_zenpage_page->get('hitcounter')+1;
			$_zp_current_zenpage_page->set('hitcounter', $hc);
			$_zp_current_zenpage_page->save();
		}
	}

	// re-initialize video dimensions if needed
	if (isImageVideo() & !is_null($_zp_flash_player)) $_zp_current_image->updateDimensions();

	// Display the page itself
	if(!is_null($_zp_HTML_cache)) { $_zp_HTML_cache->startHTMLCache(); }
	// Include the appropriate page for the requested object, and a 200 OK header.
	header("HTTP/1.0 200 OK");
	header("Status: 200 OK");
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
	include(internalToFilesystem($obj));

} else {
	// If the requested object does not exist, issue a 404 and redirect to the theme's
	// 404.php page, or a 404.php in the zp-core folder.
	
	list($album, $image) = rewrite_get_album_image('album','image');
	$_zp_gallery_page = '404.php';
	$errpage = THEMEFOLDER.'/'.internalToFilesystem($theme).'/404.php';
	if (DEBUG_404) {
		debugLog("404 error: album=$album; image=$image; theme=$theme");
		debugLogArray('$_SERVER', $_SERVER);
		debugLogArray('$_REQUEST', $_REQUEST);
		debugLog('');
	}
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	if (file_exists(SERVERPATH . "/" . $errpage)) {
		if ($custom) require_once($custom);
		include($errpage);
	} else {
		include(ZENFOLDER. '/404.php');
	}

}

exposeZenPhotoInformations( $obj, $_zp_loaded_plugins, $theme, $_zp_filters );

if(!is_null($_zp_HTML_cache)) { $_zp_HTML_cache->endHTMLCache(); }

?>
