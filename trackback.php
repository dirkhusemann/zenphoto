<?php 
/**
 * Trackback receiver for Zenphoto
 */
require_once(dirname(__FILE__).'/zp-core/folder-definitions.php');
require_once(ZENFOLDER ."/functions.php");
require_once(ZENFOLDER .'/'.PLUGIN_FOLDER . "/comment_trackback.php");
$_zp_trackback->printTrackbackReceiver();
?>