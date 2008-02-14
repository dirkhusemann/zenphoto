<?php 
function flip_image($imgPath)
{
	// flip from left to right (mirror image)
	$src = imagecreatefromjpeg($imgPath);
	$width = imagesx($src);
	$height = imagesy($src);
	$dst = imagecreate($width, $height);

	for($x = 0; $x < $width; $x++) {
		imagecopy($dst, $src, ($width-1) - $x, 0, $x, 0, 1, $height);
	}

	imagedestroy($src);
	unlink($imgPath);   // need to unlink before writing due to perms issue
	imagejpeg($dst, $imgPath);
	imagedestroy($dst);
	return;
}
?>