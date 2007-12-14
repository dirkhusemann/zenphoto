<?php 
if (checkforPassword(true)) { 
header("HTTP/1.0 404 Not Found");
?>
<HTML>
<HEAD>
	<TITLE>404 - Page Not Found</TITLE>
	<META NAME="ROBOTS" CONTENT="NOINDEX, FOLLOW">
</HEAD>
<BODY bgcolor="#ffffff" text="#000000" link="#0000ff" vlink="#0000ff" alink="#0000ff">
<FONT face="Helvitica,Arial,Sans-serif" size="2">
<B>The page could not be found on the server (404)</B><BR>
<BR>
<?php
exit();
}
header('content-type: image/jpeg'); 
header('Content-Disposition: attachment; filename="' . $_zp_current_image->name . '"');  


$image_path = $_zp_gallery->getAlbumDir() . $_zp_current_album->name . "/" . $_zp_current_image->name;

$newim = imagecreatefromjpeg($image_path);

if (getOption('perform_watermark')) {
  $watermark_path = SERVERPATH . "/" . ZENFOLDER . "/" . getOption('watermark_image');
  $watermark = imagecreatefrompng($watermark_path);
  imagealphablending($watermark, false);
  imagesavealpha($watermark, true);
  $watermark_width = imagesx($watermark);
  $watermark_height = imagesy($watermark);
  // Position Overlay in Bottom Right
  $dest_x = max(0, imagesx($newim) - $watermark_width-20);
  $dest_y = max(0, imagesy($newim) - $watermark_height-20);
  imagecopy($newim, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
  imagedestroy($watermark);
}

imagejpeg($newim);
?>

