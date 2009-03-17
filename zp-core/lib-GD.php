<?php
/**
 * library for image handling using the GD library of functions
 * @package core
 */

// force UTF-8 Ø

/**
 * Zenphoto image manipulation functions using the PHP GD library
 *
 */
	
/**
 * Takes an image filename and returns a GD Image using the correct function
 * for the image's format (imagecreatefrom*). Supports JPEG, GIF, and PNG.
 * @param string $imagefile the full path and filename of the image to load.
 * @return image the loaded GD image object.
 *
 */
function imageGet($imgfile) {
	$ext = strtolower(substr(strrchr($imgfile, "."), 1));
	switch ($ext) {
		case 'png':
			return imagecreatefrompng($imgfile);
		case 'wbmp':
			return imagecreatefromwbmp($imgfile);
		case 'jpeg':
		case 'jpg':
			return imagecreatefromjpeg($imgfile);
		case 'gif':
			return imagecreatefromgif($imgfile);
	}
	return false;
}

/**
 * outputs an image resource as a given type
 *
 * @param resource $im
 * @param string $type
 * @param string $filename
 * @param int $quality
 */
function imageOutput($im, $type, $filename=NULL, $qual=0) {
	switch ($type) {
		case 'png':
			return imagepng($im, $filename, $qual);
		case 'wbmp':
			return imagewbmp($im, $filename);
		case 'jpeg':
		case 'jpg':
			return imagejpeg($im, $filename, $qual);
		case 'gif':
			return imagegif($im, $filename);
	}
	return false;
}

/**
 * Creates a true color image
 *
 * @param int $w the width of the image
 * @param int $h the height of the image
 * @return image
 */
function createImage($w, $h) {
	return imagecreatetruecolor($w, $h);
}

/**
 * copies an image canvas
 *
 * @param image $imgCanvas source canvas
 * @param image $img destination canvas
 * @param int $dest_x destination x
 * @param int $dest_y destination y
 * @param int $src_x source x
 * @param int $src_y source y
 * @param int $w width
 * @param int $h height
 */
function copyCanvas($imgCanvas, $img, $dest_x, $dest_y, $src_x, $src_y, $w, $h) {
	return imageCopy($imgCanvas, $img, $dest_x, $dest_y, $src_x, $src_y, $w, $h);
}

/**
 * resamples an image to a new copy
 *
 * @param resource $dst_image
 * @param resource $src_image
 * @param int $dst_x
 * @param int $dst_y
 * @param int $src_x
 * @param int $src_y
 * @param int $dst_w
 * @param int $dst_h
 * @param int $src_w
 * @param int $src_h
 * @return bool
 */
function  resampleImage($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
	return imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
}

/**
 * Sharpens an image using an Unsharp Mask filter.
 *
 * Original description from the author:
 *
 * WARNING ! Due to a known bug in PHP 4.3.2 this script is not working well in this
 * version. The sharpened images get too dark. The bug is fixed in version 4.3.3.
 *
 * From version 2 (July 17 2006) the script uses the imageconvolution function in
 * PHP version >= 5.1, which improves the performance considerably.
 *
 * Unsharp masking is a traditional darkroom technique that has proven very
 * suitable for digital imaging. The principle of unsharp masking is to create a
 * blurred copy of the image and compare it to the underlying original. The
 * difference in colour values between the two images is greatest for the pixels
 * near sharp edges. When this difference is subtracted from the original image,
 * the edges will be accentuated.
 *
 * The Amount parameter simply says how much of the effect you want. 100 is
 * 'normal'. Radius is the radius of the blurring circle of the mask. 'Threshold'
 * is the least difference in colour values that is allowed between the original
 * and the mask. In practice this means that low-contrast areas of the picture are
 * left unrendered whereas edges are treated normally. This is good for pictures of
 * e.g. skin or blue skies.
 *
 * Any suggenstions for improvement of the algorithm, expecially regarding the
 * speed and the roundoff errors in the Gaussian blur process, are welcome.
 *
 * Permission to license this code under the GPL was granted by the author on 2/12/2007.
 *
 * @param image $img the GD format image to sharpen. This is not a URL string, but
 *   should be the result of a GD image function.
 * @param int $amount the strength of the sharpening effect. Nominal values are between 0 and 100.
 * @param int $radius the pixel radius of the sharpening mask. A smaller radius sharpens smaller
 *   details, and a larger radius sharpens larger details.
 * @param int $threshold the color difference threshold required for sharpening. A low threshold
 *   sharpens all edges including faint ones, while a higher threshold only sharpens more distinct edges.
 * @return image the input image with the specified sharpening applied.
 */
function imageUnsharpMask($img, $amount, $radius, $threshold) {
	/*
	 Unsharp Mask for PHP - version 2.0
	 Unsharp mask algorithm by Torstein Hønsi 2003-06.
	 Please leave this notice.
	 */

	// $img is an image that is already created within php using
	// imgcreatetruecolor. No url! $img must be a truecolor image.

	// Attempt to calibrate the parameters to Photoshop:
	if ($amount > 500)    $amount = 500;
	$amount = $amount * 0.016;
	if ($radius > 50)    $radius = 50;
	$radius = $radius * 2;
	if ($threshold > 255)    $threshold = 255;

	$radius = abs(round($radius));     // Only integers make sense.
	if ($radius == 0) return $img;
	$w = imagesx($img); $h = imagesy($img);
	$imgCanvas = imagecreatetruecolor($w, $h);
	$imgCanvas2 = imagecreatetruecolor($w, $h);
	$imgBlur = imagecreatetruecolor($w, $h);
	$imgBlur2 = imagecreatetruecolor($w, $h);
	imagecopy ($imgCanvas, $img, 0, 0, 0, 0, $w, $h);
	imagecopy ($imgCanvas2, $img, 0, 0, 0, 0, $w, $h);


	// Gaussian blur matrix:
	//    1    2    1
	//    2    4    2
	//    1    2    1
	//////////////////////////////////////////////////

	imagecopy($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h); // background

	for ($i = 0; $i < $radius; $i++)    {
		if (function_exists('imageconvolution')) { // PHP >= 5.1
			$matrix = array(
			array( 1, 2, 1 ),
			array( 2, 4, 2 ),
			array( 1, 2, 1 )
			);
			imageconvolution($imgCanvas, $matrix, 16, 0);
		} else {

			// Move copies of the image around one pixel at the time and merge them with weight
			// according to the matrix. The same matrix is simply repeated for higher radii.

			imagecopy      ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
			imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
			imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
			imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
			imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
			imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
			imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
			imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
			imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
			imagecopy      ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

			// During the loop above the blurred copy darkens, possibly due to a roundoff
			// error. Therefore the sharp picture has to go through the same loop to
			// produce a similar image for comparison. This is not a good thing, as processing
			// time increases heavily.
			imagecopy      ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 20 );
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 16.666667);
			imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
			imagecopy      ($imgCanvas2, $imgBlur2, 0, 0, 0, 0, $w, $h);
		}
	}

	// Calculate the difference between the blurred pixels and the original
	// and set the pixels
	for ($x = 0; $x < $w; $x++)    { // each row
		for ($y = 0; $y < $h; $y++)    { // each pixel

			$rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
			$rOrig = (($rgbOrig >> 16) & 0xFF);
			$gOrig = (($rgbOrig >> 8) & 0xFF);
			$bOrig = ($rgbOrig & 0xFF);

			$rgbBlur = ImageColorAt($imgCanvas, $x, $y);

			$rBlur = (($rgbBlur >> 16) & 0xFF);
			$gBlur = (($rgbBlur >> 8) & 0xFF);
			$bBlur = ($rgbBlur & 0xFF);

			// When the masked pixels differ less from the original
			// than the threshold specifies, they are set to their original value.
			$rNew = (abs($rOrig - $rBlur) >= $threshold)
			? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
			: $rOrig;
			$gNew = (abs($gOrig - $gBlur) >= $threshold)
			? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
			: $gOrig;
			$bNew = (abs($bOrig - $bBlur) >= $threshold)
			? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
			: $bOrig;

			if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
				$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
				ImageSetPixel($img, $x, $y, $pixCol);
			}
		}
	}
	return $img;
}

/**
 * Resize a PNG file with transparency to given dimensions
 * and still retain the alpha channel information
 * Author:  Alex Le - http://www.alexle.net
 *
 *
 * @param image $src
 * @param int $w
 * @param int $h
 * @return image
 */
function imageResizeAlpha(&$src, $w, $h) {
	/* create a new image with the new width and height */
	$temp = imagecreatetruecolor($w, $h);

	/* making the new image transparent */
	$background = imagecolorallocate($temp, 0, 0, 0);
	imagecolortransparent($temp, $background); // make the new temp image all transparent
	imagealphablending($temp, false); // turn off the alpha blending to keep the alpha channel

	/* Resize the PNG file */
	/* use imagecopyresized to gain some performance but loose some quality */
	imagecopyresampled($temp, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
	/* use imagecopyresampled if you concern more about the quality */
	//imagecopyresampled($temp, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
	return $temp;
}

/**
 * Returns true if GD library is configued with image rotation suppord
 *
 * @return bool
 */
function imageCanRotate() {
	return function_exists('imagerotate');
}

/**
 * Rotates an image resource according to its EXIF info and auto_rotate option
 * NB: requires the imagarotate function to be configured
 *
 * @param resource $im
 * @param int $rotate
 * @return resource
 */
function rotateImage($im, $rotate) {
	$newim_rot = imagerotate($im, $rotate, 0);
	imagedestroy($im);
	return $newim_rot;
}

/**
 * Returns image info such as the image height and width
 *
 * @param string $filename
 * @param array $imageinfo
 * @return array
 */
function imageGetInfo($filename, &$imageinfo=NULL) {
	return getimagesize($filename, $imageinfo);
}

/**
 * Returns the width of an image resource
 *
 * @param resource $im
 * @return int
 */
function imageWidth($im) {
	return imagesx($im);
}

/**
 * Returns the height of an image resource
 *
 * @param resource $im
 * @return int
 */
function imageHeight($im) {
	return imagesy($im);
}

/**
 * Does a copy merge of two image resources
 *
 * @param resource $dst_im
 * @param resource $src_im
 * @param int $dst_x
 * @param int $dst_y
 * @param int $src_x
 * @param int $src_y
 * @param int $src_w
 * @param int $src_h
 * @param int $pct
 * @return resource
 */
function imageMerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
	return imagecopymerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
}

/**
 * destroys an image resource
 *
 * @param resource $im
 * @return bool
 */
function imageKill($im)  {
	return imagedestroy($im);
}

/**
 * Returns an RGB color identifier
 *
 * @param resource $image
 * @param int $red
 * @param int $green
 * @param int $blue
 * @return int
 */
function colorAllocate($image, $red, $green, $blue) {
	return imagecolorallocate($image, $red, $green, $blue);
}

/**
 * Rencers a string into the image
 *
 * @param resource $image
 * @param int $font
 * @param int $x
 * @param int $y
 * @param string $string
 * @param int $color
 * @return bool
 */
function writeString($image, $font, $x, $y, $string, $color) {
	return imagestring($image, $font, $x, $y, $string, $color);
}

/**
 * Creates a rectangle
 *
 * @param resource $image
 * @param int $x1
 * @param int $y1
 * @param int $x2
 * @param int $y2
 * @param int $color
 * @return bool
 */
function createRectangle($image, $x1, $y1, $x2, $y2 , $color) {
	return imagerectangle($image, $x1, $y1, $x2, $y2 , $color);
}

function graphicsLibInfo() {
	$lib = array ();
	if (extension_loaded('gd')) {
		$lib['Library'] = 'PHP GD library';
		$imgtypes = imagetypes();
		$lib['GIF'] = $imgtypes & IMG_GIF;
 		$lib['JPG'] = $imgtypes & IMG_JPG;
		$lib['PNG'] = $imgtypes & IMG_PNG;
		$lib['WBMP'] = $imgtypes & IMG_WBMP;
	} else {
		$lib ['Library'] = '';
	}
	return $lib;
}
?>