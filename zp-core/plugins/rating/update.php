<?php
require('../../template-functions.php');
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
	
if(!checkIP($id,$option)) { 
	// fetch used_ips and add new ip
	$ip = sanitize($_SERVER['REMOTE_ADDR']);  
	$numbers = query_full_array("SELECT total_votes, total_value, used_ips FROM ".$dbtable." WHERE id='$id' ");
	$checkIP = unserialize($numbers['used_ips']);
	((is_array($checkIP)) ? array_push($checkIP,$ip) : $checkIP = array($ip));
	$insertip = serialize($checkIP);
	query("UPDATE ".$dbtable." SET total_votes = total_votes + 1, total_value = total_value + ".$rating.", used_ips='".$insertip."' WHERE id = '".$id."'"); 
}
?>
