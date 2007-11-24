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
if (in_context(ZP_IMAGE)) {
  include("$themepath/$theme/image.php");
} else if (in_context(ZP_ALBUM)) {
  if(isset($_GET['zipfile']) && is_dir(realpath('albums/' . $_GET['album']))){ 
  	createAlbumZip($_GET['album']); 
  } else { 
    setOption('search_params', NULL);
    include("$themepath/$theme/album.php"); 
  } 
} else if (in_context(ZP_INDEX)) {
  if (isset($_GET['p'])) {
    $page = str_replace(array('/','\\','.'), '', $_GET['p']);
    include("$themepath/$theme/$page.php");
  } else {
    include("$themepath/$theme/index.php");
  }
}


?>