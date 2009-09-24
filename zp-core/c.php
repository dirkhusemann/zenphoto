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
$len = strlen($string);

$fontname = getOption('zenphoto_captcha_font');
if (empty($fontname)) {
	$font = 5;
} else {
	$font = zp_imageloadfont($fontname);
}
$pallet = array(array('R'=>16, 'G'=>110, 'B'=>3),
								array('R'=>132, 'G'=>4, 'B'=>16),
								array('R'=>103, 'G'=>3, 'B'=>143),
								array('R'=>143, 'G'=>32, 'B'=>3),
								array('R'=>143, 'G'=>38, 'B'=>48),
								array('R'=>0, 'G'=>155, 'B'=>18));
$fw = zp_imagefontwidth($font); 
$w = $fw*$len+2; 
$h = $fh = zp_imagefontheight($font); 
$kerning = min(4,floor($fw/2)-1);
$leading = $fh-4;
$ink = $lead = $kern = array();
for ($i=0; $i<$len; $i++) {
	$lead[$i] = rand(2,$leading);
	$h = max($h, $fh+$lead[$i]+2);
	$kern[$i] = rand(2,$kerning);
	$w = $w+$kern[$i];
	$ink[$i] =$pallet[rand(0,5)];
}
$image = zp_createImage($w, $h);
$background = zp_imageGet(SERVERPATH.'/'.ZENFOLDER.'/images/captcha_background.png');
zp_copyCanvas($image, $background, 0, 0, rand(0,9), rand(0,9), $w, $h);

$l = rand(2,$kerning);
for ($i=0; $i<$len; $i++) {
	$lettre = zp_colorAllocate($image,$ink[$i]['R'],$ink[$i]['G'],$ink[$i]['B']);
	zp_writeString($image,$font,$l,$lead[$i],substr($string, $i, 1),$lettre);
	$l = $l+$fw+$kern[$i];
}

$rectangle = zp_colorAllocate($image,48,57,85);
zp_drawRectangle($image,0,0,$w-1,$h-1,$rectangle);

zp_imageOutput($image, 'png', NULL);

?>

