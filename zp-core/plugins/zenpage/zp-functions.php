<?php 
/**
 * Wrapper file to include some necessary zenphoto admin stuff to zenpage
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */ 
define("OFFSET_PATH",4); 
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/admin-globals.php');
require_once("zenpage-admin-functions.php");
if(!($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
}
?>
