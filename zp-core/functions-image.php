<?php
/**
 * image processing functions
 * @package core
 *
 */

// force UTF-8 Ø

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
		echo('<strong>'.sprintf(gettext('Zenphoto Image Processing Error: %s'), $errormessage).'</strong>'
		. '<br /><br />'.sprintf(gettext('Request URI: [ <code>%s</code> ]'), sanitize($_SERVER['REQUEST_URI'], 3))
		. '<br />PHP_SELF: [ <code>' . sanitize($_SERVER['PHP_SELF'], 3) . '</code> ]'
		. (empty($newfilename) ? '' : '<br />'.sprintf(gettext('Cache: [<code>%s</code>]'), substr(CACHEFOLDER, 0, -1) . sanitize($newfilename, 3)).' ')
		. (empty($image) || empty($album) ? '' : ' <br />'.sprintf(gettext('Image: [<code>%s</code>]'),sanitize($album.'/'.$image, 3)).' <br />'));
	} else {
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/images/' . $errorimg);
		exit();
	}
}

/**
 * Prints debug information from the arguments to i.php.
 *
 * @param string $album alubm name
 * @param string $image image name
 * @param array $args size/crop arguments
 */
function imageDebug($album, $image, $args) {
	list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;
	echo "Album: [ " . $album . " ], Image: [ " . $image . " ]<br/><br/>";
	echo "<strong>".gettext("Debug")." <code>i.php</code> | ".gettext("Arguments:")."</strong><br />\n\n"
	.  "<ul><li>".gettext("size =")."    <strong>" . sanitize($size, 3)     . "</strong></li>\n"
	.  "<li>".gettext("width =")."   <strong>" . sanitize($width, 3)    . "</strong></li>\n"
	.  "<li>".gettext("height =")."  <strong>" . sanitize($height, 3)   . "</strong></li>\n"
	.  "<li>".gettext("cw =")."      <strong>" . sanitize($cw, 3)       . "</strong></li>\n"
	.  "<li>".gettext("ch =")."      <strong>" . sanitize($ch, 3)       . "</strong></li>\n"
	.  "<li>".gettext("cx =")."      <strong>" . sanitize($cx, 3)       . "</strong></li>\n"
	.  "<li>".gettext("cy =")."      <strong>" . sanitize($cy, 3)       . "</strong></li>\n"
	.  "<li>".gettext("quality =")." <strong>" . sanitize($quality, 3)  . "</strong></li>\n"
	.  "<li>".gettext("thumb =")."   <strong>" . sanitize($thumb, 3)    . "</strong></li>\n"
	.  "<li>".gettext("crop =")."    <strong>" . sanitize($crop, 3)     . "</strong></li></ul>\n";
}

/**
 * Calculates proprotional width and height
 * Used internally by cacheImage
 * 
 * Returns array containing the new width and height
 *
 * @param int $size
 * @param int $width
 * @param int $height
 * @param int $w
 * @param int $h
 * @param int $thumb
 * @param int $image_use_side
 * @param int $dim
 * @return array
 */
function propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $dim) {	
	$hprop = round(($h / $w) * $dim);
	$wprop = round(($w / $h) * $dim);
	if ($size) {
		if ((($thumb || ($image_use_side == 'longest')) && $h > $w) || ($image_use_side == 'height') || ($image_use_side == 'shortest' && $h < $w)) {
			$newh = $dim;  // height is the size and width is proportional
			$neww = $wprop;
		} else {
			$neww = $dim;  // width is the size and height is proportional
			$newh = $hprop; 
		}
	} else { // length and/or width is set, size is NULL (Thumbs work the same as image in this case)
		if ($height) { 
			$newh = $height;  // height is supplied, use it
		} else {
			$newh = $hprop;		// height not supplied, use the proprotional
		}
		if ($width) {
			$neww = $width;   // width is supplied, use it
		} else {
			$neww = $wprop;   // width is not supplied, use the proportional
		}
	}
	if (DEBUG_IMAGE) debugLog("propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $wprop, $hprop)::\$neww=$neww; \$newh=$newh");	
	return array($neww, $newh);
}

/**
 * Creates the cache folder version of the image, including watermarking
 *
 * @param string $newfilename the name of the file when it is in the cache
 * @param string $imgfile the image name
 * @param array $args the cropping arguments
 * @param bool $allow_watermark set to true if image may be watermarked
 * @param bool $force_cache set to true to force the image into the cache folders
 * @param string $theme the current theme
 */
function cacheImage($newfilename, $imgfile, $args, $allow_watermark=false, $force_cache=false, $theme) {
	@list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbstandin, $thumbWM, $adminrequest) = $args;
	// Set the config variables for convenience.
	$image_use_side = getOption('image_use_side');
	$upscale = getOption('image_allow_upscale');
	$allowscale = true;
	$sharpenthumbs = getOption('thumb_sharpen');
	$sharpenimages = getOption('image_sharpen');
	$newfile = SERVERCACHE . $newfilename;
	if (DEBUG_IMAGE) debugLog("cacheImage(\$imgfile=".basename($imgfile).", \$newfilename=$newfilename, \$allow_watermark=$allow_watermark, \$force_cache=$force_cache, \$theme=$theme) \$size=$size, \$width=$width, \$height=$height, \$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy, \$quality=$quality, \$thumb=$thumb, \$crop=$crop \$image_use_side=$image_use_side; \$upscale=$upscale;");
	// Check for the source image.
	if (!file_exists($imgfile) || !is_readable($imgfile)) {
		imageError(gettext('Image not found or is unreadable.'), 'err-imagenotfound.gif');
	}
	$rotate = false;
	if (imageCanRotate() && getOption('auto_rotate'))  {
		$rotate = getImageRotation($imgfile);
	}

	if ($im = imageGet($imgfile)) {
		if ($rotate) {
			$im = rotateImage($im, $rotate);
		}
		$w = imageWidth($im);
		$h = imageHeight($im);
		// Give the sizing dimension to $dim
		$ratio_in = '';
		$ratio_out = '';
		$crop = ($crop || $cw != 0 || $ch != 0);
		if (!empty($size)) {
			$dim = $size;
			$width = $height = false;
			if ($crop) {		
				$dim = $size;
				if (!$ch) $ch = $size;
				if (!$cw) $cw = $size;
			}
		} else if (!empty($width) && !empty($height)) {
			$ratio_in = $h / $w;
			$ratio_out = $height / $width;
			if ($ratio_in > $ratio_out) { // image is taller than desired, $height is the determining factor
				$thumb = true;
				$dim = $width;
				if (!$ch) $ch = $height;
			} else { // image is wider than desired, $width is the determining factor
				$dim = $height;
				if (!$cw) $cw = $width;
			}
		} else if (!empty($width)) {
			$dim = $width;
			$size = $height = false;
		} else if (!empty($height)) {
			$dim = $height;
			$size = $width = false;
		} else {
			// There's a problem up there somewhere...
			imageError(gettext("Unknown error! Please report to the developers at <a href=\"http://www.zenphoto.org/\">www.zenphoto.org</a>"), 'err-imagegeneral.gif');
		}
	
		$sizes = propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $dim);
		list($neww, $newh) = $sizes;
		
		if (DEBUG_IMAGE) debugLog("cacheImage:".basename($imgfile).": \$size=$size, \$width=$width, \$height=$height, \$w=$w; \$h=$h; \$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy, \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$newh=$newh, \$neww=$neww, \$dim=$dim, \$ratio_in=$ratio_in, \$ratio_out=$ratio_out \$upscale=$upscale \$rotate=$rotate \$force_cache=$force_cache");
		
		if (!$upscale && $newh >= $h && $neww >= $w) { // image is the same size or smaller than the request
			if (!getOption('fullimage_watermark') && !($crop || $thumb || $rotate || $force_cache)) { // no processing needed
				if (DEBUG_IMAGE) debugLog("Serve ".basename($imgfile)." from original image.");
				if (getOption('album_folder_class') != 'external') { // local album system, return the image directly
					$image = substr(strrchr($imgfile, '/'), 1);
					$album = substr($imgfile, strlen(getAlbumFolder()));
					$album = substr($album, 0, strlen($album) - strlen($image) - 1);
					if (DEBUG_IMAGE) debugLog("Local: ".getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
					header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
					exit();
				} else {  // the web server does not have access to the image, have to supply it
					$suffix = strtolower(substr(strrchr($imgfile, "."), 1));
					if (DEBUG_IMAGE) debugLog("External: ".$imgfile.' suffix='.$suffix.' size='.filesize($imgfile));
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
			$allowscale = false;
			if ($crop) {
				if ($width > $neww) {
					$width = $neww;
				}
				if ($height > $newh) {
					$height = $newh;
				}
			}
			if (DEBUG_IMAGE) debugLog("cacheImage:no upscale ".basename($imgfile).":  \$newh=$newh, \$neww=$neww, \$crop=$crop, \$thumb=$thumb, \$rotate=$rotate, \$force_cache=$force_cache, watermark=".getOption('fullimage_watermark'));
		}
		// Crop the image if requested.
		if ($crop) {
				if ($cw > $ch) {
					$ir = $ch/$cw;
				} else {
					$ir = $cw/$ch;
				}
				if ($size) {
					$ts = $size;
					$neww = $size;
					$newh = $ir*$size;
				} else {
					$neww = $width;
					$newh = $height;
					if ($neww > $newh) {
						$ts = $neww;
						if ($newh === false) {
							$newh = $ir*$neww;
						}
					} else {
						$ts = $newh;
						if ($neww === false) {
							$neww = $ir*$newh;
						}
					}
				}
			
			$cr = min($w, $h)/$ts;
			if (!$cx) {
				if (!$cw) {
					$cw = $w;
				} else {
					$cw = round($cw*$cr);
				}
				$cx = round(($w - $cw) / 2);
			} else { // custom crop
				if (!$cw || $cw > $w) $cw = $w;
			}
			if (!$cy) {
				if (!$ch) {
					$ch = $h;
				} else {
					$ch = round($ch*$cr);
				}
				$cy = round(($h - $ch) / 2);
			} else { // custom crop
				if (!$ch || $ch > $h) $ch = $h;
			}
			if ($cw + $cx > $w) $cx = $w - $cw;
			if ($cx < 0) {
				$cw = $cw + $cx;
				$cx = 0;
			}
			if ($ch + $cy > $h) $cy = $h - $ch;
			if ($cy < 0) {
				$ch = $ch + $cy;
				$cy = 0;
			}
			if (DEBUG_IMAGE) debugLog("cacheImage:crop ".basename($imgfile).":\$size=$size, \$width=$width, \$height=$height, \$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy, \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$rotate=$rotate");
			$newim = createImage($neww, $newh);
			resampleImage($newim, $im, 0, 0, $cx, $cy, $neww, $newh, $cw, $ch);
		} else {
			if ($allowscale) {
				$sizes = propSizes($size, $width, $height, $w, $h, $thumb, $image_use_side, $dim);
				list($neww, $newh) = $sizes;
				
			}
			if (DEBUG_IMAGE) debugLog("cacheImage:no crop ".basename($imgfile).":\$size=$size, \$width=$width, \$height=$height, \$dim=$dim, \$neww=$neww; \$newh=$newh; \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$rotate=$rotate; \$allowscale=$allowscale;");
			$newim = createImage($neww, $newh);
			resampleImage($newim, $im, 0, 0, 0, 0, $neww, $newh, $w, $h);
		}		
		
		
		if (($thumb && $sharpenthumbs) || (!$thumb && $sharpenimages)) {
			imageUnsharpMask($newim, getOption('sharpen_amount'), getOption('sharpen_radius'), getOption('sharpen_threshold'));
		}
		$watermark_image = false;
		if ($thumbWM) {
			if ($thumb || !$allow_watermark) {
				$watermark_image = SERVERPATH . '/' . ZENFOLDER . '/watermarks/' . internalToFIlesystem($thumbWM).'.png';
				if (!file_exists($watermark_image)) $watermark_image = SERVERPATH . '/' . ZENFOLDER . '/images/imageDefault.png';
			}
		} else {
			if ($allow_watermark) {
				$watermark_image = getOption('fullimage_watermark');
				if ($watermark_image) {
					$watermark_image = SERVERPATH . '/' . ZENFOLDER . '/watermarks/' . internalToFIlesystem($watermark_image).'.png';
					if (!file_exists($watermark_image)) $watermark_image = SERVERPATH . '/' . ZENFOLDER . '/images/imageDefault.png';
				}
			}
		}
		if ($watermark_image) {
			$offset_h = getOption('watermark_h_offset') / 100;
			$offset_w = getOption('watermark_w_offset') / 100;
			$watermark = imageGet($watermark_image);
			$watermark_width = imageWidth($watermark);
			$watermark_height = imageHeight($watermark);
			$imw = imageWidth($newim);
			$imh = imageHeight($newim);
			$percent = getOption('watermark_scale')/100;
			$r = sqrt(($imw * $imh * $percent) / ($watermark_width * $watermark_height));
			if (!getOption('watermark_allow_upscale')) {
				$r = min(1, $r);
			}
			$nw = round($watermark_width * $r);
			$nh = round($watermark_height * $r);
			if (($nw != $watermark_width) || ($nh != $watermark_height)) {
				$watermark = imageResizeAlpha($watermark, $nw, $nh);
			}
			// Position Overlay in Bottom Right
			$dest_x = max(0, floor(($imw - $nw) * $offset_w));
			$dest_y = max(0, floor(($imh - $nh) * $offset_h));
			if (DEBUG_IMAGE) debugLog("Watermark:".basename($imgfile).": \$offset_h=$offset_h, \$offset_w=$offset_w, \$watermark_height=$watermark_height, \$watermark_width=$watermark_width, \$imw=$imw, \$imh=$imh, \$percent=$percent, \$r=$r, \$nw=$nw, \$nh=$nh, \$dest_x=$dest_x, \$dest_y=$dest_y");
			copyCanvas($newim, $watermark, $dest_x, $dest_y, 0, 0, $nw, $nh);
			imageKill($watermark);
		}

		// Create the cached file (with lots of compatibility)...
		mkdir_recursive(dirname($newfile));
		if (imageOutput($newim, 'jpg', $newfile, $quality)) {
			if (DEBUG_IMAGE) debugLog('Finished:'.basename($imgfile));
		} else {
			if (DEBUG_IMAGE) debugLog('cacheImage: failed to create '.$newfile);
		}
		@chmod($newfile, 0666 & CHMOD_VALUE);
		imageKill($newim);
		imageKill($im);
	}
}

 /* Determines the rotation of the image looking EXIF information.  
  *   
  * @param string $imgfile the image name  
  * @return false when the image should not be rotated, or the degrees the  
  *         image should be rotated otherwise.  
  *  
  * PHP GD do not support flips so when a flip is needed we make a  
  * rotation that get close to that flip. But I don't think any camera will  
  * fill a flipped value in the tag.  
  */  
function getImageRotation($imgfile) {
	$imgfile = substr($imgfile, strlen(getAlbumFolder()));
  $result = query_single_row('SELECT EXIFOrientation FROM '.prefix('images').' AS i JOIN '.prefix('albums').' as a ON i.albumid = a.id WHERE "'.$imgfile.'" = CONCAT(a.folder,"/",i.filename)');
	if (is_array($result) && array_key_exists('EXIFOrientation', $result)) {
		$splits = preg_split('/!([(0-9)])/', $result['EXIFOrientation']);
		$rotation = $splits[0];
		switch ($rotation) {
			case 1 : return false; break;
			case 2 : return false; break; // mirrored
			case 3 : return 180;   break; // upsidedown (not 180 but close)
			case 4 : return 180;   break; // upsidedown mirrored
			case 5 : return 270;   break; // 90 CW mirrored (not 270 but close)
			case 6 : return 270;   break; // 90 CCW
			case 7 : return 90;    break; // 90 CCW mirrored (not 90 but close)
			case 8 : return 90;    break; // 90 CW
			default: return false;
		}
	}
	return false;
}

?>
