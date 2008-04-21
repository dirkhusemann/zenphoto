<?php
require_once('functions.php');
require_once('lib-encryption.php');
header ("Content-type: image/png");
$cypher = $_GET['i'];

$key = 'zenphoto_captcha_string';
$string = rc4($key, urldecode($cypher));

$image = imagecreate(65, 20);
$background = imagecreatefrompng(SERVERPATH.'/'.ZENFOLDER.'/images/captcha_background.png');
imagecopy($image, $background, 0, 0, rand(0,10), rand(0,10), 65, 20);

$lettre = imagecolorallocate($image,5,5,5);
$start = rand(0,(4-strlen($string))*7);
for ($i=0; $i<strlen($string); $i++) {
	$l = $start+$i*10+6+rand(0,3);
	imagestring($image,rand(5,10),$l,rand(0,3),substr($string, $i, 1),$lettre);
}

$rectangle = imagecolorallocate($image,48,57,85);
ImageRectangle ($image,0,0,64,19,$rectangle);

imagepng($image, NULL, 0);

?>

