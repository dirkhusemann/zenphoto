<?php 
/**
 * Wrapper file to include some necessary zenphoto admin stuff to zenpage
 *
 * @author Malte Müller (acrylian)
 * @package zenpage
 */ 
define("OFFSET_PATH",4); 
include('../../admin-functions.php');
require_once(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . 'zenpage/zenpage-version.php'); // includes the $plugin_version statement
if (getOption('zenpage_release') != ZENPAGE_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . "zenpage/setup.php?admin");
	exit();
}
include ("zenpage-admin-functions.php");
if(!($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
  header("Location: ../../admin.php"); exit;
}
?>
