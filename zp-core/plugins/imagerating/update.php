<?php
require('../../template-functions.php');
$imgId = $_GET['imgId']; 
$rating = $_GET['rating'];
if ($rating > 5) { $rating = 5; }
  
if(!checkIP($imgId)){ 
  // fetch used_ips and add new ip
  $ip = $_SERVER['REMOTE_ADDR'];  
  $numbers = query_full_array("SELECT total_votes, total_value, used_ips FROM ". prefix('images') ." WHERE id='$imgId' ")or die(" Error: ".mysql_error());
  $checkIP = unserialize($numbers['used_ips']);
  ((is_array($checkIP)) ? array_push($checkIP,$ip) : $checkIP = array($ip));
  $insertip = serialize($checkIP);
  $update = query_single_row("UPDATE ". prefix('images') ." SET total_votes = total_votes + 1, total_value = total_value + ".$rating.", used_ips='".$insertip."' WHERE id = '".$imgId."'"); 
}
?>
