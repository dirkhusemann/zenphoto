<?php


// i.php - image generation.


require_once("functions.php");

// Set the config variables for convenience.
$thumb_crop = zp_conf('thumb_crop');
$thumb_size = zp_conf('thumb_size');
$thumb_crop_width = zp_conf('thumb_crop_width');
$thumb_crop_height = zp_conf('thumb_crop_height');
$image_use_longest_side = zp_conf('image_use_longest_side');
$image_quality = zp_conf('image_quality');

// Generate an image from the given file ($_GET['f']) at the given size ($_GET['s'])
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	return false;
	// TODO: Return a default image (possibly with an error message) instead of just dying.
}
$album = $_GET['a'];
$image = $_GET['i'];

if(isset($_GET['s']) && $_GET['s'] < 3000) { // Disallow abusive size requests.
	if ($_GET['s'] == "thumb") {
		$thumb = true;
	} else {
		$thumb = false;
	}
	$size = $_GET['s'];
} else {
	return false;
}
if(isset($_GET['h'])) $height = $_GET['h'];  else $height = false;
if(isset($_GET['q'])) $quality = $_GET['q']; else $quality = $image_quality;

// Check the cache for the processed image; if it doesn't exist, create it.
$newfile = SERVERCACHE."/{$album}_{$image}_{$size}.jpg";
$imgfile = SERVERPATH."/albums/$album/$image";
$cached = true;


if (!file_exists($newfile)) {
	if ($im = get_image($imgfile)) {
		$w = imagesx($im);
		$h = imagesy($im);
		if ($thumb) {
			if ($w < $h) {
				$neww = $thumb_size;
				$newh = round(($h / $w) * $thumb_size);
			} else {
				$neww = round(($w / $h) * $thumb_size);
				$newh = $thumb_size;
			}
			$thumb = imagecreatetruecolor($neww, $newh);
			imagecopyresampled($thumb, $im, 0, 0, 0, 0, $neww, $newh, $w, $h);
			if ($thumb_crop) {
				if ($thumb_crop_width > $thumb_size) $thumb_crop_width = $thumb_size;
				if ($thumb_crop_height > $thumb_size) $thumb_crop_height = $thumb_size;
				$newim = imagecreatetruecolor($thumb_crop_width, $thumb_crop_height);
				$x = round(($neww - $thumb_crop_width) / 2);
				$y = round(($newh - $thumb_crop_height) / 2);
				imagecopy($newim, $thumb, 0, 0, $x, $y, $thumb_crop_width, $thumb_crop_height);
			} else {
				$newim = $thumb;
			}

		} else {
		  if ($image_use_longest_side && $h > $w) {
        $newh = $size;
        $neww = round(($w / $h) * $size);
      } else {
  			$neww = $size;
  			$newh = round(($h / $w) * $size);
      }
      
      // If the requested image is the same size as the original, redirect to it.
      if ($newh == $h && $neww == $w) {
        header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/albums/$album/$image");
        return;
      }
      
			$newim = imagecreatetruecolor($neww, $newh);
      // imageinterlace($newim, 1);
			imagecopyresampled($newim, $im, 0, 0, 0, 0, $neww, $newh, $w, $h);
		}
		
		imagejpeg($newim, $newfile, $image_quality);
		imagedestroy($newim);
		imagedestroy($im);
	}
	// $cached = false; // Just cache it anyway.
}

if ($cached) {
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", mktime (0,0,0,1,1,2000)) . " GMT"); // Date in the past
	header("Expires: Mon, 26 Jul 2040 05:00:00 GMT"); // Never expire the image (well, until 2040)
	header("Cache-Control: max-age=10000000, s-maxage=1000000, proxy-revalidate, must-revalidate");
}
header('Content-Type: image/jpeg');
if ($fp = fopen($newfile, 'rb')) {
	fpassthru($fp);
}

?>
