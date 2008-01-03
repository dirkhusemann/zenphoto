<?php 
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
require_once(ZENFOLDER . "/template-functions.php");
$themepath = 'themes';
$theme = $_zp_gallery->getCurrentTheme();
$_zp_themeroot = WEBPATH . "/$themepath/$theme";
if (!(false === ($requirePath = getPlugin('themeoptions.php', true)))) {
  require_once($requirePath);
  $optionHandler = new ThemeOptions(); /* prime the theme options */
}

header ('Content-Type: text/html; charset=' . getOption('charset'));
$obj = '';


if (isset($_GET['p'])) {
  $page = str_replace(array('/','\\','.'), '', $_GET['p']);
  if (substr($page, 0, 1) == "*") {
    include ($obj = ZENFOLDER."/".substr($page, 1) . ".php");
  } else {
    include($obj = "$themepath/$theme/$page.php");
  }
} else if (in_context(ZP_IMAGE)) {
  include($obj = "$themepath/$theme/image.php");
} else if (in_context(ZP_ALBUM)) {
  if(isset($_GET['zipfile']) && is_dir(realpath(getAlbumFolder() . $_GET['album']))){ 
  	createAlbumZip($_GET['album']); 
  } else { 
    $cookiepath = WEBPATH;
    if (WEBPATH == '') { $cookiepath = '/'; }
    setcookie("zenphoto_search_params", "", time()-368000, $cookiepath);
    include($obj = "$themepath/$theme/album.php"); 
  } 
} else if (in_context(ZP_INDEX)) {
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
  echo "\n<!-- zenphoto version " . getOption('version') . " Theme: " . $theme . " (" . $a[count($a)-1] . ") -->";
}


?>