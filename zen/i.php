<?php

/*******************************************************************************
 * i.php: Zenphoto image processor. All image requests go through this file.   *
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
 * - If more than one of s, h, or w are specified, s takes priority, then w.
 * - If none of s, h, or w are specified, the original image is returned.
 *******************************************************************************
 */

define('OFFSET_PATH', true);
// i.php - image generation.
require_once("functions.php");

// Set the config variables for convenience.
$thumb_crop = zp_conf('thumb_crop');
$thumb_size = zp_conf('thumb_size');
$thumb_crop_width = zp_conf('thumb_crop_width');
$thumb_crop_height = zp_conf('thumb_crop_height');
$image_use_longest_side = zp_conf('image_use_longest_side');
$image_default_size = zp_conf('image_size');
$quality = zp_conf('image_quality');
$thumb_quality = zp_conf('thumb_quality');
$upscale = zp_conf('image_allow_upscale');

// Don't let anything get above this, to save the server from burning up...
define('MAX_SIZE', 3000);

// Generate an image from the given file ($_GET['f']) at the given size ($_GET['s'])
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	die("<b>Zenphoto error:</b> Please specify both an album and an image.");
	// TODO: Return a default image (possibly with an error message) instead of just dying.
}

// Fix special characters in the album and image names if mod_rewrite is on:
if (zp_conf('mod_rewrite')) {
  $zppath = substr($_SERVER['REQUEST_URI'], strlen(WEBPATH)+1);
  $qspos = strpos($zppath, '?');
  if ($qspos !== false) $zppath = substr($zppath, 0, $qspos); 
  $zpitems = explode("/", $zppath);
  if (isset($zpitems[1]) && $zpitems[1] == 'image') {
    $req_album = $zpitems[0];
    // This next line assumes the image filename is always last. Take note.
    $req_image = $zpitems[count($zpitems)-1];
    if (!empty($req_album)) $_GET['a'] = urldecode($req_album);
    if (!empty($req_image)) $_GET['i'] = urldecode($req_image);
  }
}


$album = sanitize($_GET['a']);
$image = sanitize($_GET['i']);

// Disallow abusive size requests.
if ((isset($_GET['s']) && $_GET['s'] < MAX_SIZE) 
  || (isset($_GET['w']) && $_GET['w'] < MAX_SIZE)
  || (isset($_GET['h']) && $_GET['h'] < MAX_SIZE)) {

  // Set default variable values.
  $thumb = $size = $width = $height = $crop = $cw = $ch = $cx = $cy = false;
  
  // If s=thumb, Set up for the default thumbnail settings.
  $size = $_GET['s'];
	if ($size == "thumb") {
		$thumb = true;
    if ($thumb_crop) {
      if ($thumb_crop_width > $thumb_size)  $thumb_crop_width  = $thumb_size;
      if ($thumb_crop_height > $thumb_size) $thumb_crop_height = $thumb_size;
      $cw = $thumb_crop_width;
      $ch = $thumb_crop_height;
      $crop = true;
    } else {
      $crop = $cw = $ch = false;
    }
    $size = round($thumb_size);
    $quality = round($thumb_quality);

  // Otherwise, populate the parameters from the URI
	} else {
    if ($size == "default") {
      $size = $image_default_size;
    } else if (empty($size) || !is_numeric($size)) {
      $size = false; // 0 isn't a valid size anyway, so this is OK.
    } else {
      $size = round($size);
    }
    
    if (isset($_GET['w']))  { $width   = round($_GET['w']); }
    if (isset($_GET['h']))  { $height  = round($_GET['h']); }
    if (isset($_GET['cw'])) { $cw      = round($_GET['cw']); $crop = true; }
    if (isset($_GET['ch'])) { $ch      = round($_GET['ch']); $crop = true; }
    if (isset($_GET['cx'])) { $cx      = round($_GET['cx']); }
    if (isset($_GET['cy'])) { $cy      = round($_GET['cy']); }
    if (isset($_GET['q']))  { $quality = round($_GET['q']); }
	}
  
  $postfix_string = ($size ? "_$size" : "") . ($width ? "_w$width" : "") 
    . ($height ? "_h$height" : "") . ($cw ? "_cw$cw" : "") . ($ch ? "_ch$ch" : "") 
    . (is_numeric($cx) ? "_cx$cx" : "") . (is_numeric($cy) ? "_cy$cy" : "");
    
} else {
  // No image parameters specified; return the original image.
  header("Location: " . PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . WEBPATH 
    . "/albums/" . rawurlencode($album) . "/" . rawurlencode($image));
  return;
}


$newfilename = "{$album}_{$image}{$postfix_string}.jpg";
$newfile = SERVERCACHE . "/" . $newfilename;
$imgfile = SERVERPATH  . "/albums/$album/$image";

// Check for the source image.
if (!file_exists($imgfile)) {
  die("<b>Zenphoto error:</b> Image not found.");
}

// If the file hasn't been cached yet, create it.
if (!file_exists($newfile)) {
	if ($im = get_image($imgfile)) {
		$w = imagesx($im);
		$h = imagesy($im);
    
    // Give the sizing dimension to $dim
    if (!empty($size)) {
      $dim = $size;
      $width = $height = false;
    } else if (!empty($width)) {
      $dim = $width;
      $size = $height = false;
    } else if (!empty($height)) {
      $dim = $height;
      $size = $width = false;
    } else {
      // There's a problem up there somewhere...
      die("<b>Zenphoto error:</b> Image processing error. Please report to the developers.");
    }
    
    // Calculate proportional height and width.
    $hprop = round(($h / $w) * $dim);
    $wprop = round(($w / $h) * $dim);
    
		if ($thumb) {
      // Thumbs always use the shortest side to catch the whole image.
      // $dim should always be $size here.
			if ($h > $w) {
				$neww = $dim;
				$newh = $hprop;
			} else {
				$neww = $wprop;
				$newh = $dim;
			}
    } else {
      if (($size && $image_use_longest_side && $h > $w) || $height) {
        $newh = $dim;
        $neww = $wprop;
      } else {
  			$neww = $dim;
  			$newh = $hprop;
      }
      
      // If the requested image is the same size or smaller than the original, redirect to it.
      if (!$upscale && $newh >= $h && $neww >= $w && !$crop) {
        header("Location: " . PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . WEBPATH
          . "/albums/" . rawurlencode($album) . "/" . rawurlencode($image));
        return;
      }
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
    chmod($newfile,0644);
		imagedestroy($newim);
		imagedestroy($im);
	}
}

// ... and redirect the browser to it.

header("Location: " . PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/cache/" . rawurlencode($newfilename));

?>
