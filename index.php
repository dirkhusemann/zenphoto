<?php 
require_once("zen/template-functions.php");
$themepath = 'themes';

$theme = zp_conf('theme');

$_zp_themeroot = WEBPATH . "/$themepath/$theme";

if (in_context(ZP_IMAGE)) {
  include("$themepath/$theme/image.php");
} else if (in_context(ZP_ALBUM)) {
  include("$themepath/$theme/album.php");
} else if (in_context(ZP_INDEX)) {
  include("$themepath/$theme/index.php");
}


?>
