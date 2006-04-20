<?php

define('OFFSET_PATH', true);
// i.php - image generation.
require_once("functions.php");

// Set the config variables for convenience.
$thumb_crop = zp_conf('thumb_crop');
$thumb_size = zp_conf('thumb_size');
$thumb_crop_width = zp_conf('thumb_crop_width');
$thumb_crop_height = zp_conf('thumb_crop_height');
$image_use_longest_side = zp_conf('image_use_longest_side');
$quality = zp_conf('image_quality');
$thumb_quality = zp_conf('thumb_quality');

// Generate an image from the given file ($_GET['f']) at the given size ($_GET['s'])
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	die("<b>Zenphoto error:</b> Please specify both an album and an image.");
	// TODO: Return a default image (possibly with an error message) instead of just dying.
}
$album = get_magic_quotes_gpc() ? stripslashes($_GET['a']) : $_GET['a'];
$image = get_magic_quotes_gpc() ? stripslashes($_GET['i']) : $_GET['i'];

// Disallow abusive size requests.
if (isset($_GET['s']) && $_GET['s'] < 3000) {
  // Setting up default variable values.
  $crop = $cw = $ch = $cx = $cy = false;
  
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
    
  // Otherwise, get crops/height from the command line if they exist.
	} else {
    $size = round($size);
    if (isset($_GET['cw'])) { $cw      = round($_GET['cw']); $crop = true; }
    if (isset($_GET['ch'])) { $ch      = round($_GET['ch']); $crop = true; }
    if (isset($_GET['cx'])) { $cx      = round($_GET['cx']); }
    if (isset($_GET['cy'])) { $cy      = round($_GET['cy']); }
    if (isset($_GET['q']))  { $quality = round($_GET['q']); }
	}
  
  $postfix_string = $size . ($height ? "_h$height" : "") . ($cw ? "_cw$cw" : "") . ($ch ? "_ch$ch" : "") 
    . (is_numeric($cx) ? "_cx$cx" : "") . (is_numeric($cy) ? "_cy$cy" : "");
} else {
	die("<b>Zenphoto error:</b> Too few arguments given to the image processor.");
}


$newfilename = "/{$album}_{$image}_{$postfix_string}.jpg";
$newfile = SERVERCACHE . $newfilename;
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
    
    // Calculate proportional height and width.
    $hprop = round(($h / $w) * $size);
    $wprop = round(($w / $h) * $size);
    
		if ($thumb) {
			if ($w < $h) {
				$neww = $size;
				$newh = $hprop;
			} else {
				$neww = $wprop;
				$newh = $size;
			}
    } else {
      if ($image_use_longest_side && $w < $h) {
        $newh = $size;
        $neww = $wprop;
      } else {
  			$neww = $size;
  			$newh = $hprop;
      }
      
      // If the requested image is the same size or smaller than the original, redirect to it.
      if ($newh >= $h && $neww >= $w) {
        header("Location: " . PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/albums/$album/$image");
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
header("Location: " . PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/cache$newfilename");

?>
