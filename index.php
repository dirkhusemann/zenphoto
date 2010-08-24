<?php

// force UTF-8 Ø

require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
if (!file_exists(dirname(__FILE__) . '/' . DATA_FOLDER . "/zp-config.php")) {
	if (file_exists(dirname(__FILE__).'/'.ZENFOLDER.'/setup.php')) {
		$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
		if (substr($dir, -1) == '/') $dir = substr($dir, 0, -1);
		$location = "http://". $_SERVER['HTTP_HOST']. $dir . "/" . ZENFOLDER . "/setup.php";
		header("Location: $location" );
	} else {
		die('setup scripts missing');
	}
}

define('OFFSET_PATH', 0);
require_once(ZENFOLDER . "/template-functions.php");
if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	if (file_exists(dirname(__FILE__).'/'.ZENFOLDER.'/setup.php')) {
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
		exit();
	} else {
		die('setup scripts missing');
	}
}

/**
 * Invoke the controller to handle requests
 */
require_once(dirname(__FILE__). "/".ZENFOLDER.'/controller.php');
header ('Content-Type: text/html; charset=' . getOption('charset'));
$_zp_obj = '';

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
		$_zp_gallery_page = basename($_zp_obj = ZENFOLDER."/".$page.".php");
	} else {
		$_zp_obj = THEMEFOLDER."/$theme/$page.php";
		$_zp_gallery_page = basename($_zp_obj);
	}

// Display an Image page.
} else if (in_context(ZP_IMAGE)) {
	handleSearchParms('image', $_zp_current_album, $_zp_current_image);
	$theme = setupTheme();
	$_zp_gallery_page = basename($_zp_obj = THEMEFOLDER."/$theme/image.php");

// Display an Album page.
} else if (in_context(ZP_ALBUM)) {
	if ($_zp_current_album->isDynamic()) {
		$search = $_zp_current_album->getSearchEngine();
		zp_setcookie("zenphoto_search_params", $search->getSearchParams(), 0);
	} else {
		handleSearchParms('album', $_zp_current_album);
	}
	$theme = setupTheme();
	$_zp_gallery_page = basename($_zp_obj = THEMEFOLDER."/$theme/album.php");

	// Display the Index page.
} else if (in_context(ZP_INDEX)) {
	handleSearchParms('index');
	$theme = setupTheme();
	$_zp_gallery_page = basename($_zp_obj = THEMEFOLDER."/$theme/index.php");
}

if (!isset($theme)) {
	$theme = setupTheme();
}
if (DEBUG_PLUGINS) debugLog('Loading the "theme" plugins.');
$_zp_loaded_plugins = array();
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


if ($zp_request) {
	$_zp_obj = zp_apply_filter('load_theme_script',$_zp_obj);
}
if ($zp_request && file_exists(SERVERPATH . "/" . internalToFilesystem($_zp_obj))) {
	$hint = $show = false;
	if (checkforPassword($hint, $show)) { // password protected object
		$passwordpage = SERVERPATH.'/'.THEMEFOLDER.'/'.$theme.'/password.php';
		if (!file_exists($passwordpage)) {
			$passwordpage = SERVERPATH.'/'.ZENFOLDER.'/password.php';
		}
		header("HTTP/1.0 200 OK");
		header("Status: 200 OK");
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
		include($passwordpage);
		exposeZenPhotoInformations($_zp_obj, $_zp_loaded_plugins, $theme, $_zp_filters);
		exit();
	}

	// re-initialize video dimensions if needed
	if (isImageVideo() & isset($_zp_flash_player)) $_zp_current_image->updateDimensions();

	// Display the page itself
	if(!is_null($_zp_HTML_cache)) { $_zp_HTML_cache->startHTMLCache(); }
	// Include the appropriate page for the requested object, and a 200 OK header.
	header("HTTP/1.0 200 OK");
	header("Status: 200 OK");
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
	include(internalToFilesystem($_zp_obj));

} else {
	// If the requested object does not exist, issue a 404 and redirect to the theme's
	// 404.php page, or a 404.php in the zp-core folder.

	list($album, $image) = rewrite_get_album_image('album','image');
	debug404($album, $image, $theme);
	$_zp_gallery_page = '404.php';
	$errpage = THEMEFOLDER.'/'.internalToFilesystem($theme).'/404.php';
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	if (file_exists(SERVERPATH . "/" . $errpage)) {
		if ($custom) require_once($custom);
		include($errpage);
	} else {
		include(ZENFOLDER. '/404.php');
	}
	$_zp_HTML_cache = NULL;
}

exposeZenPhotoInformations($_zp_obj, $_zp_loaded_plugins, $theme, $_zp_filters);

if(!is_null($_zp_HTML_cache)) { $_zp_HTML_cache->endHTMLCache(); }

?>
