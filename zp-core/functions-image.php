<?php

// functions-image.php - HEADERS NOT SENT YET!

// Don't let anything get above this, to save the server from burning up...
define('MAX_SIZE', 3000);

/**
 * If in debug mode, prints the given error message and continues; otherwise redirects
 * to the given error message image and exits; designed for a production gallery.
 * @param $errormessage string the error message to print if $_GET['debug'] is set.
 * @param $errorimg string the filename of the error image to display for production. Defaults
 *   to 'err-imagegeneral.gif'. Images should be located in /zen/images .
 */
function imageError($errormessage, $errorimg='err-imagegeneral.gif') {
	global $newfilename, $album, $image;
	$debug = isset($_GET['debug']);
	if ($debug) {
		echo('<strong>Zenphoto Image Processing Error:</strong> ' . $errormessage
		. '<br /><br />Request URI: [ <code>' . sanitize($_SERVER['REQUEST_URI'], true) . '</code> ]'
		. '<br />PHP_SELF:    [ <code>' . sanitize($_SERVER['PHP_SELF'], true) . '</code> ]'
		. (empty($newfilename) ? '' : '<br />Cache: [<code> ' . substr(CACHEFOLDER, 0, -1) . sanitize($newfilename, true) . '</code>]')
		. (empty($image) || empty($album) ? '' : ' <br />Image: [<code> '.sanitize($album.'/'.$image, true).' </code>]<br />'));
	} else {
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/images/' . $errorimg);
		exit();
	}
}

/**
 * Prints debug information from the arguments to i.php.
 * @param args an array of the arguments to i.php in the standard order.
 * @return nothing.
 */
function imageDebug($args) {
	list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;
	echo "<strong>DEBUG <code>i.php</code> | Arguments:</strong><br />\n\n"
	.  "<ul><li>size =    <strong>" . sanitize($size, true)     . "</strong></li>\n"
	.  "<li>width =   <strong>" . sanitize($width, true)    . "</strong></li>\n"
	.  "<li>height =  <strong>" . sanitize($height, true)   . "</strong></li>\n"
	.  "<li>cw =      <strong>" . sanitize($cw, true)       . "</strong></li>\n"
	.  "<li>ch =      <strong>" . sanitize($ch, true)       . "</strong></li>\n"
	.  "<li>cx =      <strong>" . sanitize($cx, true)       . "</strong></li>\n"
	.  "<li>cy =      <strong>" . sanitize($cy, true)       . "</strong></li>\n"
	.  "<li>quality = <strong>" . sanitize($quality, true)  . "</strong></li>\n"
	.  "<li>thumb =   <strong>" . sanitize($thumb, true)    . "</strong></li>\n"
	.  "<li>crop =    <strong>" . sanitize($crop, true)     . "</strong></li></ul>\n";

}

/**
 * Takes an image filename and returns a GD Image using the correct function
 * for the image's format (imagecreatefrom*). Supports JPEG, GIF, and PNG.
 * @param string $imagefile the full path and filename of the image to load.
 * @return image the loaded GD image object.
 *
 */
function get_image($imgfile) {
	$ext = strtolower(substr(strrchr($imgfile, "."), 1));
	if ($ext == "jpg" || $ext == "jpeg") {
		return imagecreatefromjpeg($imgfile);
	} else if ($ext == "gif") {
		return imagecreatefromgif($imgfile);
	} else if ($ext == "png") {
		return imagecreatefrompng($imgfile);
	} else {
		return false;
	}
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
function unsharp_mask($img, $amount, $radius, $threshold) {
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
 * Creates the cache folder version of the image, including watermarking
 *
 * @param string $newfilename the name of the file when it is in the cache
 * @param string $imgfile the image name
 * @param array $args the cropping arguments
 * @param bool $allow_watermark set to true if image may be watermarked
 * @param bool $force_cache set to true to force the image into the cache folders
 */
function cacheGalleryImage($newfilename, $imgfile, $args, $allow_watermark=false, $force_cache=false) {
	@list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;
	// Set the config variables for convenience.
	$image_use_longest_side = getOption('image_use_longest_side');
	$upscale = getOption('image_allow_upscale');
	$sharpenthumbs = getOption('thumb_sharpen');

	$newfile = SERVERCACHE . $newfilename;
	// Check for GD
	if (!function_exists('imagecreatetruecolor'))
	imageError('The GD Library is not installed or not available.', 'err-nogd.gif');
	// Check for the source image.
	if (!file_exists($imgfile) || !is_readable($imgfile)) {
		imageError('Image not found or is unreadable.', 'err-imagenotfound.gif');
	}

	if ($im = get_image($imgfile)) {
		$w = imagesx($im);
		$h = imagesy($im);

		// Give the sizing dimension to $dim
		if (!empty($size)) {
			$dim = $size;
			$width = $height = false;
		} else if (!empty($width) && !empty($height)) {
			$ratio_in = $h / $w;
			$ratio_out = $height / $width;
			$crop = true;
			if ($ratio_in > $ratio_out) {
				$thumb = true;
				$dim = $width;
				$ch = $height;
			} else {
				$dim = $height;
				$cw = $width;
				$height = true;
			}

		} else if (!empty($width)) {
			$dim = $width;
			$size = $height = false;
		} else if (!empty($height)) {
			$dim = $height;
			$size = $width = false;
		} else {
			// There's a problem up there somewhere...
			imageError("Unknown error! Please report to the developers at <a href=\"http://www.zenphoto.org/\">www.zenphoto.org</a>", 'err-imagegeneral.gif');
		}

		// Calculate proportional height and width.
		$hprop = round(($h / $w) * $dim);
		$wprop = round(($w / $h) * $dim);

		if ((!$thumb && $size && $image_use_longest_side && $h > $w) || ($thumb && $h <= $w) || $height) {
			$newh = $dim;
			$neww = $wprop;
		} else {
			$newh = $hprop;
			$neww = $dim;
		}
		if (!$upscale && $newh >= $h && $neww >= $w && !$crop) { // image is the same size or smaller than the request
			if (!getOption('perform_watermark') && !$force_cache) { // no processing needed
				if (is_null(getOption('external_album_folder'))) { // local album system, return the image directly
					$image = substr(strrchr($imgfile, '/'), 1);
					$album = substr($imgfile, strlen(getAlbumFolder()));
					$album = substr($album, 0, strlen($album) - strlen($image) - 1);
					header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
					exit();
				} else {  // the web server does not have access to the image, have to supply it
					$suffix = strtolower(substr(strrchr($filename, "."), 1));
					$fp = fopen($imgfile, 'rb');
					// send the right headers
					header("Content-Type: image/$suffix");
					header("Content-Length: " . filesize($imgfile));
					// dump the picture and stop the script
					fpassthru($fp);
					fclose($fp);
					exit();
				}
			}
			$neww = $w;
			$newh = $h;
		}
		$newim = imagecreatetruecolor($neww, $newh);
		imagecopyresampled($newim, $im, 0, 0, 0, 0, $neww, $newh, $w, $h);

		// Crop the image if requested.
		if ($crop) {
			if ($cw === false || $cw > $neww) $cw = $neww;
			if ($ch === false || $ch > $newh) $ch = $newh;
			if ($cx === false) $cx = round(($neww - $cw) / 2);
			if ($cy === false) $cy = round(($newh - $ch) / 2);
			if ($cw + $cx > $neww) $cx = $neww - $cw;
			if ($ch + $cy > $newh) $cy = $newh - $ch;
			$newim_crop = imagecreatetruecolor($cw, $ch);
			imagecopy($newim_crop, $newim, 0, 0, $cx, $cy, $cw, $ch);
			imagedestroy($newim);
			$newim = $newim_crop;
		}

		if ($thumb && $sharpenthumbs) {
			unsharp_mask($newim, 40, 0.5, 3);
		}
		$perform_watermark = false;
		if ($_GET['vwm']) {
			if ($thumb) {
				$perform_watermark = true;
				$watermark_image = getOption('video_watermark_image');
			}
		} else {
			if ($allow_watermark) {
				$perform_watermark = getOption('perform_watermark');
				$watermark_image = SERVERPATH . '/' . ZENFOLDER . '/' . getOption('watermark_image');
			}
		}
		if ($perform_watermark) {
			$offset_h = getOption('watermark_h_offset') / 100;
			$offset_w = getOption('watermark_w_offset') / 100;
			$watermark = imagecreatefrompng($watermark_image);
			imagealphablending($watermark, false);
			imagesavealpha($watermark, true);
			$watermark_width = imagesx($watermark);
			$watermark_height = imagesy($watermark);
			// Position Overlay in Bottom Right
			$dest_x = max(0, floor((imagesx($newim) - $watermark_width) * $offset_w));
			$dest_y = max(0, floor((imagesy($newim) - $watermark_height) * $offset_h));
			imagecopy($newim, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
			imagedestroy($watermark);
		}

		// Create the cached file (with lots of compatibility)...
		@touch($newfile);
		imagejpeg($newim, $newfile, $quality);
		@chmod($newfile, 0666 & CHMOD_VALUE);
		imagedestroy($newim);
		imagedestroy($im);
	}
}


?>
