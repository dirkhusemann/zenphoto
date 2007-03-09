<?php 
require_once("zen/template-functions.php");
$themepath = 'themes';

$theme = $_zp_gallery->getCurrentTheme();

$_zp_themeroot = WEBPATH . "/$themepath/$theme";

header ('Content-Type: text/html; charset=' . zp_conf('charset'));

if (in_context(ZP_IMAGE)) {
  include("$themepath/$theme/image.php");
} else if (in_context(ZP_ALBUM)) {
  include("$themepath/$theme/album.php");
} else if (in_context(ZP_INDEX)) {
  if (isset($_GET['p'])) {
    $page = str_replace(array('/','\\','.'), '', $_GET['p']);
    include("$themepath/$theme/$page.php");
  } else {
    include("$themepath/$theme/index.php");
  }
}

echo "<!--  \n\n"; print_r($_zp_object_cache); echo "\n\n  -->";


?>
