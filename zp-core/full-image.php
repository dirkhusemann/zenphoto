<?php
/**
 * handles the watermarking and protecting of the full image link
 * @package core
 */

// force UTF-8 Ø

/* Prevent hotlinking to the full image from other servers. */
$server = $_SERVER['SERVER_NAME'];
if (isset($_SERVER['HTTP_REFERER'])) $test = strpos($_SERVER['HTTP_REFERER'], $server); else $test = true;
if ( $test == FALSE && getOption('hotlink_protection')) { /* It seems they are directly requesting the full image. */
	$image = 'index.php?album='.$_zp_current_album->name . '&image=' . $_zp_current_image->filename;
	header("Location: {$image}");
	exit();
}

if (checkforPassword(true)) {
	pageError(403, gettext("Forbidden"));
	exit();
}
if (!isMyAlbum($_zp_current_album->name, ALL_RIGHTS) && ($hash = getOption('protected_image_password'))) {
	$authType = 'zp_image_auth';
	if (zp_getCookie($authType) != $hash) {
		$hint = get_language_string(getOption('protected_image_hint'));
		$show = getOption('protected_image_user');
		printPasswordForm($hint, true, getOption('login_user_field') || $show);
		exit();
	}
}
require_once(dirname(__FILE__).'/functions-image.php');
$image_path = $_zp_current_image->localpath;
$suffix = getSuffix($image_path);
$cache_file = $_zp_current_album->name . "/" . substr($_zp_current_image->filename, 0, -strlen($suffix)-1) . '_FULL.' . $suffix;
switch ($suffix) {
	case 'bmp':
		$suffix = 'wbmp';
		break;
	case 'jpg':
		$suffix = 'jpeg';
		break;
	case 'png':
	case 'gif':
	case 'jpeg':
		break;
	default:
		pageError(405, gettext("Method Not Allowed"));
		exit();
}

if (getOption('cache_full_image')) {
	$cache_path = SERVERCACHE . '/' . internalToFilesystem($cache_file);
} else {
	$cache_path = NULL;
}

if (!getOption('fullimage_watermark')) { // no processing needed
	if (getOption('album_folder_class') != 'external' && !getOption('protect_full_image') == 'Download') { // local album system, return the image directly
		header('Content-Type: image/'.$suffix);
		header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($_zp_current_album->name) . "/" . rawurlencode($_zp_current_image->filename));
		exit();
	} else {  // the web server does not have access to the image, have to supply it
		$fp = fopen($image_path, 'rb');
		// send the right headers
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
		header("Content-Type: image/$suffix");
		if (getOption('protect_full_image') == 'Download') {
			header('Content-Disposition: attachment; filename="' . $_zp_current_image->filename . '"');  // enable this to make the image a download
		}
		header("Content-Length: " . filesize($image_path));
		// dump the picture and stop the script
		fpassthru($fp);
		fclose($fp);
		exit();
	}
}
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
header("content-type: image/$suffix");
$newim = zp_imageGet($image_path);

if (getOption('protect_full_image') == 'Download') {
	header('Content-Disposition: attachment; filename="' . $_zp_current_image->filename . '"');  // enable this to make the image a download
}
if (getOption('fullimage_watermark')) {
	$watermark_image = getOption('fullimage_watermark');
	if ($watermark_image) {
		$watermark_image = SERVERPATH . '/' . ZENFOLDER . '/watermarks/' . internalToFilesystem($watermark_image).'.png';
		if (!file_exists($watermark_image)) $watermark_image = SERVERPATH . '/' . ZENFOLDER . '/images/imageDefault.png';
	}
	$offset_h = getOption('watermark_h_offset') / 100;
	$offset_w = getOption('watermark_w_offset') / 100;
	$watermark = zp_imageGet($watermark_image);
	$watermark_width = zp_imageWidth($watermark);
	$watermark_height = zp_imageHeight($watermark);
	$imw = zp_imageWidth($newim);
	$imh = zp_imageHeight($newim);
	$percent = getOption('watermark_scale')/100;
	$r = sqrt(($imw * $imh * $percent) / ($watermark_width * $watermark_height));
	if (!getOption('watermark_allow_upscale')) {
		$r = min(1, $r);
	}
	$nw = round($watermark_width * $r);
	$nh = round($watermark_height * $r);
	if (($nw != $watermark_width) || ($nh != $watermark_height)) {
		$watermark = zp_imageResizeAlpha($watermark, $nw, $nh);
	}
	// Position Overlay in Bottom Right
	$dest_x = max(0, floor(($imw - $nw) * $offset_w));
	$dest_y = max(0, floor(($imh - $nh) * $offset_h));
	zp_copyCanvas($newim, $watermark, $dest_x, $dest_y, 0, 0, $nw, $nh);
	zp_imageKill($watermark);
}
$quality = getOption('full_image_quality');
zp_imageOutput($newim, $suffix, $cache_path, $quality);

if (!is_null($cache_path)) {
	@touch($cache_path);
	@chmod($cache_path, 0666 & CHMOD_VALUE);
	header('Content-Type: image/'.$suffix);
	header('Location: ' . FULLWEBPATH . CACHEFOLDER . $cache_file, true, 301);
	exit();
}

?>

