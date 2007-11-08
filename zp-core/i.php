<?php

/*******************************************************************************
 * i.php: Zenphoto image processor
 * All *uncached* image requests go through this file
 * (As of 1.0.8 images are requested directly from the cache if they exist)
 *******************************************************************************
 * URI Parameters:
 *   s  - size (logical): Based on config, makes an image of "size s."
 *   h  - height (explicit): Image is always h pixels high, w is calculated.
 *   w  - width (explicit): Image is always w pixels wide, h is calculated.
 *   cw - crop width: crops the image to cw pixels wide.
 *   ch - crop height: crops the image to ch pixels high.
 *   cx - crop x position: the x (horizontal) position of the crop area.
 *   cy - crop y position: the y (vertical) position of the crop area.
 *   q  - JPEG quality (1-100): sets the quality of the resulting image.
 *
 * - cx and cy are measured from the top-left corner of the _scaled_ image.
 * - One of s, h, or w _must_ be specified; the others are optional.
 * - If more than one of s, h, or w are specified, s takes priority, then w+h:
 * - If both w and h are given, the image is resized to shortest side, then 
 *     cropped on the remaining dimension. Image output will always be WxH.
 * - If none of s, h, or w are specified, the original image is returned.
 *******************************************************************************
 */

define('OFFSET_PATH', true);
require_once('functions.php');
require_once('functions-image.php');

// Set the memory limit higher just in case -- supress errors if user doesn't have control.
@ini_set('memory_limit','64M');

$debug = isset($_GET['debug']);

// Set the config variables for convenience.
$image_use_longest_side = getOption('image_use_longest_side');
$upscale = getOption('image_allow_upscale');
$sharpenthumbs = getOption('thumb_sharpen');

// Don't let anything get above this, to save the server from burning up...
define('MAX_SIZE', 3000);

// Check for minimum parameters.
if (!isset($_GET['a']) || !isset($_GET['i'])) {
  imageError("Too few arguments! Image not found.", 'err-imagenotfound.gif');
}

// Fix special characters in the album and image names if mod_rewrite is on:
// URL looks like: "/album1/subalbum/image/picture.jpg"
list($ralbum, $rimage) = rewrite_get_album_image('a', 'i');

$album = str_replace('..','', sanitize($ralbum));
$image = str_replace(array('/',"\\"),'', sanitize($rimage));

// Disallow abusive size requests.
if ( (isset($_GET['s']) && abs($_GET['s']) < MAX_SIZE) 
  || (isset($_GET['w']) && abs($_GET['w']) < MAX_SIZE)
  || (isset($_GET['h']) && abs($_GET['h']) < MAX_SIZE)) {

  // Extract the image parameters from the input variables
  // This validates the input as well.
  $args = getImageParameters(
    array(
      $_GET['s'], $_GET['w'], $_GET['h'], $_GET['cw'], $_GET['ch'], $_GET['cx'], $_GET['cy'], $_GET['q'])
    );
  list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;
  
  if ($debug) echo "Album: [ " . $album . " ], Image: [ " . $image . " ]<br/><br/>";
  if ($debug) imageDebug($args);

} else {
  // No image parameters specified or are out of bounds; return the original image.
  header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
  return;
}

// Construct the filename to save the cached image.
$newfilename = getImageCacheFilename($album, $image, $args);
$newfile = SERVERCACHE . $newfilename;
if ($album=='') {
  $imgfile = getAlbumFolder() . $image;
} else {
  $imgfile = getAlbumFolder() . "$album/$image";
}

/** Check for possible problems ***********
 ******************************************/
// Make sure the cache directory is writable, attempt to fix. Issue a warning if not fixable.
if (!is_dir(SERVERCACHE)) {
  @mkdir(SERVERCACHE, 0777);
  @chmod(SERVERCACHE, 0777);
  if (!is_dir(SERVERCACHE))
    imageError("The cache directory does not exist. Please create it and set the permissions to 0777.", 'err-cachewrite.gif');
}
if (!is_writable(SERVERCACHE)) {
  @chmod(SERVERCACHE, 0777);
  if (!is_writable(SERVERCACHE))
    imageError("The cache directory is not writable! Attempts to chmod didn't work.", 'err-cachewrite.gif');
}
// Check for GD
if (!function_exists('imagecreatetruecolor'))
  imageError('The GD Library is not installed or not available.', 'err-nogd.gif');
// Check for the source image.
if (!file_exists($imgfile) || !is_readable($imgfile))
  imageError('Image not found or is unreadable.', 'err-imagenotfound.gif');



// Make the directories for the albums in the cache, recursively.
// Skip this for safe_mode, where we can't write to directories we create!
if (!ini_get("safe_mode")) {
  $albumdirs = getAlbumArray($album, true);
  foreach($albumdirs as $dir) {
    $dir = SERVERCACHE . '/' . $dir;
    if (!is_dir($dir)) {
      mkdir($dir, 0777);
      chmod($dir, 0777);
    } else if (!is_writable($dir)) {
      chmod($dir, 0777);
    }
  }
}


$process = true;
// If the file exists, check its modification time and update as needed.
if (file_exists($newfile)) {
  if (filemtime($newfile) < filemtime($imgfile)) {
    $process = true;
  } else {
    $process = false;
  }
}

// If the file hasn't been cached yet, create it.
if ($process) {
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
    
    // If the requested image is the same size or larger than the original, redirect to it.
    if (!$upscale && $newh >= $h && $neww >= $w && !$crop) {
      header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
      return;
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
/*
    // Video Watermarks on video thumb
	 $perform_video_watermark = getOption('perform_video_watermark');
     $video_watermark_image = getOption('video_watermark_image');
     if ($_GET['vwm'] == true && $thumb == true) {  
       $watermark = imagecreatefrompng($video_watermark_image);
       imagealphablending($watermark, false);
       imagesavealpha($watermark, true);
       $watermark_width = imagesx($watermark);
       $watermark_height = imagesy($watermark);
       // Position Overlay in Bottom Right
       $dest_x = imagesx($newim) - $watermark_width;
       $dest_y = imagesy($newim) - $watermark_height;
       imagecopymerge($newim, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height,100);
       imagedestroy($watermark);
     }
*/
	
	// Image Watermarking
	$perform_watermark = false;
	if ($_GET['vwm']) {
	  if ($thumb) {
	   $perform_watermark = true;
       $watermark_image = getOption('video_watermark_image');
	  }
	} else {
	  if (!$thumb) {
	    $perform_watermark = getOption('perform_watermark');
	    $watermark_image = getOption('watermark_image');
	  }
	}

	if ($perform_watermark) {
	  $watermark = imagecreatefrompng($watermark_image);
	  imagealphablending($watermark, false);
	  imagesavealpha($watermark, true);
	  $watermark_width = imagesx($watermark);
	  $watermark_height = imagesy($watermark);
	  // Position Overlay in Bottom Right
	  $dest_x = max(0, imagesx($newim) - $watermark_width);
	  $dest_y = max(0, imagesy($newim) - $watermark_height);

	  imagecopy($newim, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
	  imagedestroy($watermark);
	}
	
	// Create the cached file (with lots of compatibility)...
        @touch($newfile);
        imagejpeg($newim, $newfile, $quality);
        @chmod($newfile, 0666);
        imagedestroy($newim);
        imagedestroy($im);
	}
}
if (!$debug) {
  // ... and redirect the browser to it.
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($newfile)).' GMT');
  header('Content-Type: image/jpeg');
  header('Location: ' . FULLWEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($newfilename), true, 301);
  exit();
  
} else {
  
  echo "\n<p>Image: <img src=\"" . FULLWEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($newfilename) ."\" /></p>";
  
}

////////////////////////////////////////////////////////////////////////////////

?>
