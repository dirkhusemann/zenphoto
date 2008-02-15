<?php
define("MIRROR_HORIZONTAL", 1);
define("MIRROR_VERTICAL", 2);
define("MIRROR_BOTH", 3);

function Mirror($src, $dest, $type)
{
	$imgsrc = imagecreatefromjpeg($src);
	$width = imagesx($imgsrc);
	$height = imagesy($imgsrc);
	$imgdest = imagecreatetruecolor($width, $height);
 
	for ($x=0 ; $x<$width ; $x++)
		{
			for ($y=0 ; $y<$height ; $y++)
		{
			if ($type == MIRROR_HORIZONTAL) imagecopy($imgdest, $imgsrc, $width-$x-1, $y, $x, $y, 1, 1);
			if ($type == MIRROR_VERTICAL) imagecopy($imgdest, $imgsrc, $x, $height-$y-1, $x, $y, 1, 1);
			if ($type == MIRROR_BOTH) imagecopy($imgdest, $imgsrc, $width-$x-1, $height-$y-1, $x, $y, 1, 1);
		}
		}
 
	imagejpeg($imgdest, $dest);
 
	imagedestroy($imgsrc);
	imagedestroy($imgdest);
}

Mirror(SRC_IMAGE, DEST_IMAGE, MIRROR_HORIZONTAL);

print "<img src='SRC_IMAGE'>";
print "<br><br>";
print "<img src='DEST_IMAGE'>";
?>