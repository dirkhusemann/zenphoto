<?php
/* Prevent hotlinking to the full image from other servers. */
$server = $_SERVER['SERVER_NAME'];
$test = strpos($_SERVER['HTTP_REFERER'], $server);
if ( $test == FALSE ) { /* It seems they are directly requesting the full image. */
	$image = 'index.php?album='.$_zp_current_album->name . '&image=' . $_zp_current_image->name;
	header("Location: {$image}");
	exit();
}

if (checkforPassword(true)) {
	pageError();
	exit();
}
$image_path = $_zp_gallery->getAlbumDir() . $_zp_current_album->name . "/" . $_zp_current_image->name;
$suffix = strtolower(substr(strrchr($image_path, "."), 1));
switch ($suffix) {
	case 'bmp':
		$suffix = 'wbmp'; 
		break;
	case 'jpg':
		$suffix = 'jpeg'; 
		break;
}
if (!getOption('perform_watermark')) { // no processing needed
	if (is_null(getOption('external_album_folder')) && !getOption('full_image_download')) { // local album system, return the image directly
		header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($_zp_current_album->name) . "/" . rawurlencode($_zp_current_image->name));
		exit();
	} else {  // the web server does not have access to the image, have to supply it
		$fp = fopen($image_path, 'rb');
		// send the right headers
		header("Content-Type: image/$suffix");
		if (getOption('full_image_download')) {
			header('Content-Disposition: attachment; filename="' . $_zp_current_image->name . '"');  // enable this to make the image a download
		}
		header("Content-Length: " . filesize($image_path));
		// dump the picture and stop the script
		fpassthru($fp);
		fclose($fp);
		exit();
	}
}
header("content-type: image/$suffix");
switch ($suffix) {
	case 'png':
		$newim = imagecreatefrompng($image_path);
		break;
	case 'wbmp':
		$newim = imagecreatefromwbmp($image_path);
		break;
	case 'jpeg':
		$newim = imagecreatefromjpeg($image_path);
		break;
	case 'gif':
		$newim = imagecreatefromgif($image_path);
		break;
}
if (getOption('full_image_download')) {
	header('Content-Disposition: attachment; filename="' . $_zp_current_image->name . '"');  // enable this to make the image a download
}
if (getOption('perform_watermark')) {
	$watermark_path = SERVERPATH . "/" . ZENFOLDER . "/" . getOption('watermark_image');
	$offset_h = getOption('watermark_h_offset') / 100;
	$offset_w = getOption('watermark_w_offset') / 100;
	$watermark = imagecreatefrompng($watermark_path);
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
$quality = getOption('full_image_quality');
switch ($suffix) {
	case 'jpeg':
		imagejpeg($newim, NULL, $quality);
		break;
	case 'png':
		if ($quality = 100) {
			$quality = 0;
		} else {
			$quality = round((99 - $quality)/10);
		}
		imagepng($newim, NULL, $quality);
		break;
	case 'bmp':
		imagewbmp($newim);
		break;
	case 'gif':
		imagegif($newim);
		break;
}

?>

