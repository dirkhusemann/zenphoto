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
	$bt = @debug_backtrace();
	if (is_array($bt)) {
		$b = array_shift($bt);
		$base = currentRelativeURL($b['file']);
	} else {
		$base = '';
	}
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . $base);
}
?>
