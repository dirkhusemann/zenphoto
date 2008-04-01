<?php
/**
 * Flips an image from left to right
 *
 * @param string $imgPath the fully qualified image path
 */
function flip_image($imgPath) {
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
define("MIRROR_HORIZONTAL", 1);
define("MIRROR_VERTICAL", 2);
define("MIRROR_BOTH", 3);

/**
 * Mirrors an image
 *
 * @param string $src the fully qualified source image path
 * @param string $dest the fully qualified destination path
 * @param int $type see defines above
 */
function Mirror($src, $dest, $type) {
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
/**
 * Returns a rotated image
 *
 * @param image $src_img the source image
 * @param int $angle the rotation angle
 * @return image
 */
function imageRotate($src_img, $angle) {
	$src_x = imagesx($src_img);
	$src_y = imagesy($src_img);
	if ($angle == 90 || $angle == -90) {
		$dest_x = $src_y;
		$dest_y = $src_x;
	} else {
		$dest_x = $src_x;
		$dest_y = $src_y;
	}

	$rotate=imagecreatetruecolor($dest_x,$dest_y);
	imagealphablending($rotate, false);

	switch ($angle) {
		case 90:
			for ($y = 0; $y < ($src_y); $y++) {
				for ($x = 0; $x < ($src_x); $x++) {
					$color = imagecolorat($src_img, $x, $y);
					imagesetpixel($rotate, $dest_x - $y - 1, $x, $color);
				}
			}
			break;
		case -90:
			for ($y = 0; $y < ($src_y); $y++) {
				for ($x = 0; $x < ($src_x); $x++) {
					$color = imagecolorat($src_img, $x, $y);
					imagesetpixel($rotate, $y, $dest_y - $x - 1, $color);
				}
			}
			break;
		case 180:
			for ($y = 0; $y < ($src_y); $y++) {
				for ($x = 0; $x < ($src_x); $x++) {
					$color = imagecolorat($src_img, $x, $y);
					imagesetpixel($rotate, $dest_x - $x - 1, $dest_y - $y - 1, $color);
				}
			}
			break;
		default: $rotate = $src_img;
	};
	return $rotate;
}

?>