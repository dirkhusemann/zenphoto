<?php

// force UTF-8 Ø

if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }

if (!file_exists(dirname(__FILE__) . '/' . ZENFOLDER . "/zp-config.php")) {
	$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($dir, -1) == '/') $dir = substr($dir, 0, -1);
	$location = "http://". $_SERVER['HTTP_HOST']. $dir . "/" . ZENFOLDER . "/setup.php";
	header("Location: $location" );
}
define('OFFSET_PATH', 0);

require_once(ZENFOLDER . "/template-functions.php");

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
}

$_zp_plugin_scripts = array();
$_zp_flash_player = NULL;
$_zp_HTML_cache = NULL;

header ('Content-Type: text/html; charset=' . getOption('charset'));
$obj = '';

// Display an arbitrary theme-included PHP page
// If the 'p' parameter starts with * (star) then include the file from the zp-core folder.
if (isset($_GET['p'])) {
	$theme = setupTheme();
	$page = str_replace(array('/','\\','.'), '', sanitize($_GET['p']));
	if (substr($page, 0, 1) == "*") {
		$_zp_gallery_page = basename($obj = ZENFOLDER."/".substr($page, 1) . ".php");
	} else {
		$obj = THEMEFOLDER."/$theme/$page.php";
		$_zp_gallery_page = basename($obj);
		if (file_exists(SERVERPATH . "/" . $obj)) {
		}
	}

// Display an Image page.
} else if (in_context(ZP_IMAGE)) {
	handleSearchParms($_zp_current_album->name, $_zp_current_image->filename);
	$theme = setupTheme();
	$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/image.php");

// Display an Album page.
} else if (in_context(ZP_ALBUM)) {
	if(isset($_GET['zipfile']) && is_dir(realpath(getAlbumFolder() . $_GET['album']))){
		createAlbumZip(sanitize($_GET['album']));
	} else {
		if ($_zp_current_album->isDynamic()) {
			$search = $_zp_current_album->getSearchEngine();
			$cookiepath = WEBPATH;
			if (WEBPATH == '') { $cookiepath = '/'; }
			zp_setcookie("zenphoto_image_search_params", $search->getSearchParams(), 0, $cookiepath);
			set_context(ZP_INDEX | ZP_ALBUM);
			$theme = setupTheme();
			$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/album.php");
		} else {
			handleSearchParms($_zp_current_album->name);
			$theme = setupTheme();
			$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/album.php");
		}
	}

// Display the Index page.
} else if (in_context(ZP_INDEX)) {
	handleSearchParms();
	$theme = setupTheme();
	$_zp_gallery_page = basename($obj = THEMEFOLDER."/$theme/index.php");
}

// Load plugins, then load the requested $obj (page, image, album, or index; defined above).
if (file_exists(SERVERPATH . "/" . $obj) && $zp_request) {
	foreach (getEnabledPlugins() as $extension) {
		require_once(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . $extension);
	}
if(!is_null($_zp_HTML_cache)) { $_zp_HTML_cache->startHTMLCache(); }
	// Include the appropriate page for the requested object, and a 200 OK header.
	header("HTTP/1.0 200 OK");
	include($obj);

// If the requested object does not exist, issue a 404 and redirect to the theme's
// 404.php page, or a 404.php in the zp-core folder.
} else {
	list($album, $image) = rewrite_get_album_image('album','image');
	$_zp_gallery_page = '404.php';
	$errpage = THEMEFOLDER."/$theme/404.php";
	header("HTTP/1.0 404 Not Found");
	if (file_exists(SERVERPATH . "/" . $errpage)) {
		include($errpage);
	} else {
		include(ZENFOLDER. '/404.php');
	}
}

$a = basename($obj);
if ($a != 'full-image.php') {
	if (defined('RELEASE')) {
		$official = 'Official Build';
	} else {
		$official = 'SVN';
	}
	echo "\n<!-- zenphoto version " . ZENPHOTO_VERSION . " [" . ZENPHOTO_RELEASE . "] ($official)";
	if (isset($zenpage_version)) echo ' zenpage version '.$zenpage_version.' ['.ZENPAGE_RELEASE.'] ';
	echo " Theme: " . $theme . " (" . $a . ") { memory: ".INI_GET('memory_limit')." } -->";
}
if(!is_null($_zp_HTML_cache)) { $_zp_HTML_cache->endHTMLCache(); }

?>