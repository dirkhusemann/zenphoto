<?php 
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
if (!file_exists(dirname(__FILE__) . '/' . ZENFOLDER . "/zp-config.php")) {
	$location = "http://". $_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']) . "/" . ZENFOLDER . "/setup.php";
	header("Location: $location" );
}

require_once(ZENFOLDER . "/template-functions.php");

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
}

//load extensions
$curdir = getcwd();
chdir(SERVERPATH . "/" . ZENFOLDER . EXTENSION_FOLDER);
$filelist = safe_glob('*'.'php');
chdir($curdir);
foreach ($filelist as $extension) {
	$opt = 'zp_extension_'.substr($extension, 0, strlen($extension)-4);
	if (getOption($opt)) {
		require_once(SERVERPATH . "/" . ZENFOLDER . EXTENSION_FOLDER . $extension);
	}
}

$themepath = 'themes';

header ('Content-Type: text/html; charset=' . getOption('charset'));
$obj = '';
if (isset($_GET['p'])) {
	// arbitrary PHP page, either in the theme on in the zenphoto core
	$theme = setupTheme();
	$page = str_replace(array('/','\\','.'), '', $_GET['p']);
	if (substr($page, 0, 1) == "*") {
		include ($obj = ZENFOLDER."/".substr($page, 1) . ".php");
	} else {
		$obj = "$themepath/$theme/$page.php";
		if (file_exists(SERVERPATH . "/" . $obj)) {
			include($obj);
		}
	}
} else if (in_context(ZP_IMAGE)) {
	// image page
	handleSearchParms($_zp_current_album->name, $_zp_current_image->filename);
	$theme = setupTheme();
	include($obj = "$themepath/$theme/image.php");
} else if (in_context(ZP_ALBUM)) {
	// album page
	if(isset($_GET['zipfile']) && is_dir(realpath(getAlbumFolder() . $_GET['album']))){ 
		createAlbumZip($_GET['album']); 
	} else { 
		if ($_zp_current_album->isDynamic()) {
			$search = $_zp_current_album->getSearchEngine();		
			$cookiepath = WEBPATH;
			if (WEBPATH == '') { $cookiepath = '/'; }
			zp_setcookie("zenphoto_image_search_params", $search->getSearchParams(), 0, $cookiepath);
			set_context(ZP_INDEX | ZP_ALBUM);
			$theme = setupTheme();
			include($obj = "$themepath/$theme/album.php");
		} else {
			handleSearchParms($_zp_current_album->name);
			$theme = setupTheme();
			include($obj = "$themepath/$theme/album.php"); 
		}
	} 
} else if (in_context(ZP_INDEX)) {
	// index page
	handleSearchParms();
	$theme = setupTheme();
	include($obj = "$themepath/$theme/index.php");
}
if (!file_exists(SERVERPATH . "/" . $obj)) {
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
	echo "\n<html>\n<head>\n</head>\n<body>\n<strong>Zenphoto error:</strong> missing theme page.";
	echo "\n<!-- The requested page was not found: $obj -->";
	echo "\n</body>\n</html>";
}
$a = explode("/", $obj);
if ($a[count($a)-1] != 'full-image.php') {
	echo "\n<!-- zenphoto version " . ZENPHOTO_VERSION . " [" . ZENPHOTO_RELEASE . "] Theme: " . $theme . " (" . $a[count($a)-1] . ") -->";
}
?>