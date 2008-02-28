<?php
require_once('functions.php');
require_once('lib-encryption.php');
header ("Content-type: image/png");
$cypher = $_GET['i'];

$key = 'zenphoto_captcha_string';
$string = rc4($key, urldecode($cypher));

$image = imagecreate(65,20);

$fond = imagecolorallocate($image, 255, 255, 255);
ImageFill ($image,65,20, $fond);

$ligne = imagecolorallocate($image,150,150,150);

$i = 7;
while($i<=15) {
	ImageLine($image, 0,$i, 65,$i, $ligne);
	$i = $i+7;
}

$i = 10;
while($i<=65) {
	ImageLine($image,$i,0,$i,20, $ligne);
	$i = $i+10;
}

$lettre = imagecolorallocate($image,0,0,0);
imagestring($image,10,5+rand(0,6),0,substr($string, 0, 1),$lettre);
imagestring($image,10,20+rand(0,6),0,substr($string, 1, 1),$lettre);
imagestring($image,10,35+rand(0,6),0,substr($string, 2, 1),$lettre);

$rectangle = imagecolorallocate($image,48,57,85);
ImageRectangle ($image,0,0,64,19,$rectangle);

imagepng($image, NULL, 0);

?>

