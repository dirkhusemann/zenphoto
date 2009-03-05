<?php 
/**
 * Wrapper file to include some necessary zenphoto admin stuff to zenpage
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */ 
define("OFFSET_PATH",4); 
include('../../admin-functions.php');
if (getOption('zenpage_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . "zenpage/setup.php?admin");
	exit();
}
include ("zenpage-admin-functions.php");
if(!($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
  header("Location: ../../admin.php"); exit;
}
?>
