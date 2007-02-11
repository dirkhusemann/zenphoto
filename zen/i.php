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

// Set the memory limit higher just in case -- supress errors if user doesn't have control.
@ini_set('memory_limit','64M');

// Set the config variables for convenience.
$image_use_longest_side = zp_conf('image_use_longest_side');
$upscale = zp_conf('image_allow_upscale');

// Don't let anything get above this, to save the server from burning up...
define('MAX_SIZE', 3000);
$debug = isset($_GET['debug']) ? true : false;

// Check for minimum parameters.
if (!isset($_GET['a']) || !isset($_GET['i'])) {
  if ($debug) {
    die('<b>Zenphoto error:</b> You must specify at least both an album and an image.');
  } else {
    header('Location: ' . FULLWEBPATH . '/zen/images/err-imagenotfound.gif');
    return;
  }
}

// Fix special characters in the album and image names if mod_rewrite is on:
// URL looks like: "/album1/subalbum/image/picture.jpg"
list($ralbum, $rimage) = rewrite_get_album_image('a', 'i');

$album = str_replace('..','', sanitize($ralbum));
$image = str_replace(array('..','/',"\\"),'', sanitize($rimage));

// Disallow abusive size requests.
if ((isset($_GET['s']) && $_GET['s'] < MAX_SIZE) 
  || (isset($_GET['w']) && $_GET['w'] < MAX_SIZE)
  || (isset($_GET['h']) && $_GET['h'] < MAX_SIZE)) {

  // Extract the image parameters from the input variables
  // This validates the input as well.
  $args = getImageParameters(
    array(
      $_GET['s'], $_GET['w'], $_GET['h'], $_GET['cw'], $_GET['ch'], $_GET['cx'], $_GET['cy'], $_GET['q'])
    );
  list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;

} else {
  // No image parameters specified; return the original image.
  header("Location: " . FULLWEBPATH . "/albums/" . pathurlencode($album) . "/" . rawurlencode($image));
  return;
}

// Make the directories for the albums in the cache, recursively.
// Skip this for safe_mode, where we can't write to directories we create!
if (!ini_get("safe_mode")) {
  $albumdirs = getAlbumArray($album, true);
  foreach($albumdirs as $dir) {
    $dir = SERVERCACHE . '/' . $dir;
    if (!is_dir($dir)) {
      mkdir($dir, 0777);
    } else if (!is_writable($dir)) {
      chmod($dir, 0777);
    }
  }
}

$newfilename = getImageCacheFilename($album, $image, $args);

$newfile = SERVERCACHE . $newfilename;
$imgfile = SERVERPATH  . "/albums/$album/$image";

// Check for the source image.
if (!file_exists($imgfile)) {
  if ($debug) {
    die('<b>Zenphoto error:</b> Image not found! <br />Cache: [' 
      . sanitize($newfilename, true) . '], Image: ['.sanitize($album.'/'.$image, true)']<br />');
  } else {
    header('Location: ' . FULLWEBPATH . '/zen/images/err-imagenotfound.gif');
    return;
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
      if ($debug) {
        die('<b>Zenphoto error:</b> Image processing error. Please report to the developers.<br />Cache: [' 
          . sanitize($newfilename, true) . '], Image: ['.sanitize($album.'/'.$image, true)']<br />');
      } else {
        header('Location: ' . FULLWEBPATH . '/zen/images/err-imagegeneral.gif');
        return;
      }
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
      header("Location: " . FULLWEBPATH . "/albums/" . pathurlencode($album) . "/" . rawurlencode($image));
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

    // Create the cached file (with lots of compatibility)...
		touch($newfile);

		imagejpeg($newim, $newfile, $quality);
    chmod($newfile, 0777);
		imagedestroy($newim);
		imagedestroy($im);
	}
}

// ... and redirect the browser to it.
// Header('Cache-Control: must-revalidate'); // ?? Not sure if this helps.
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($newfile)).' GMT');
header('Content-Type: image/jpeg');
header('Location: ' . FULLWEBPATH . '/cache' . pathurlencode($newfilename), true, 301);
exit();
?>
