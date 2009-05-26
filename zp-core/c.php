<?php
/**
 * creates the captcha images
 * @package core
 */

// force UTF-8 Ø

require_once(dirname(__FILE__).'/functions.php');
require_once(dirname(__FILE__).'/lib-encryption.php');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
header ("Content-type: image/png");
$cypher = preg_replace('/[^0-9a-f]/', '', $_GET['i']);

$key = getOption('zenphoto_captcha_key');
$string = rc4($key, pack("H*", $cypher));

$image = zp_createImage(65, 20);
$background = zp_imageGet(SERVERPATH.'/'.ZENFOLDER.'/images/captcha_background.png');
zp_copyCanvas($image, $background, 0, 0, rand(0,10), rand(0,10), 65, 20);

$lettre = zp_colorAllocate($image,5,5,5);
$len = strlen($string);
$sz = floor(55/$len);
$start = rand(2,55-$sz*$len);
for ($i=0; $i<$len; $i++) {
	$l = $start+$i*($sz)+rand(2,4);
	zp_writeString($image,5,$l,rand(0,3),substr($string, $i, 1),$lettre);
}

$rectangle = zp_colorAllocate($image,48,57,85);
zp_drawRectangle($image,0,0,64,19,$rectangle);

zp_imageOutput($image, 'png', NULL);

?>

