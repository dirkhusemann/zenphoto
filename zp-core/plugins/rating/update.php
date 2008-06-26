<?php
require('../../template-functions.php');
require_once('functions-rating.php');

debugLog("rating:update.php");

$id = sanitize_numeric($_GET['id']); 
$rating = sanitize_numeric($_GET['rating']);
$option = $_GET['option'];

	switch($option) {
		case "image":
			$dbtable =  prefix('images');
			break;
		case "album":
			$dbtable =  prefix('albums');
			break;
	}
	
if ($rating > 5) { 
	$rating = 5;
}
	
$ip = sanitize($_SERVER['REMOTE_ADDR']);  
if(!checkForIP($ip,$id,$option)) { 
	$_rating_current_IPlist[] = $ip;
	$insertip = serialize($_rating_current_IPlist);
	
debugLogArray("IPs ($insertip) ",$_rating_current_IPlist);	
	
	query("UPDATE ".$dbtable." SET total_votes = total_votes + 1, total_value = total_value + ".$rating.", used_ips='".$insertip."' WHERE id = '".$id."'"); 
}
?>
