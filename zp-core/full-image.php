<?php
/**
 * handles the watermarking and protecting of the full image link
 * @package core
 */

// force UTF-8 Ø
if (!defined('OFFSET_PATH')) define('OFFSET_PATH', 2); // don't need any admin tabs
require_once(dirname(__FILE__) . "/functions.php");
require_once(dirname(__FILE__) . "/functions-image.php");

// Check for minimum parameters.
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	header("HTTP/1.0 404 Not Found");
	imageError(gettext("Too few arguments! Image not found."), 'err-imagenotfound.gif');
}
list($ralbum, $rimage) = rewrite_get_album_image('a', 'i');
$ralbum = internalToFilesystem($ralbum);
$rimage = internalToFilesystem($rimage);
$album = str_replace('..','', sanitize_path($ralbum));
$image = str_replace(array('/',"\\"),'', sanitize_path($rimage));
$album8 = filesystemToInternal($album);
$image8 = filesystemToInternal($image);
$theme = themeSetup($album); // loads the theme based image options.

/* Prevent hotlinking to the full image from other servers. */
$server = $_SERVER['SERVER_NAME'];
if (isset($_SERVER['HTTP_REFERER'])) $test = strpos($_SERVER['HTTP_REFERER'], $server); else $test = true;
if ( $test == FALSE && getOption('hotlink_protection')) { /* It seems they are directly requesting the full image. */
	$i = 'index.php?album='.$album8 . '&image=' . $image8;
	header("Location: {$i}");
	exit();
}

// have to check for passwords
if (!(zp_loggedin(VIEW_ALL_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS))) {
	$hash = getOption('gallery_password');
	if (!empty($hash) && zp_getCookie('zp_gallery_auth') != $hash) {
		require_once(dirname(__FILE__) . "/template-functions.php");
		pageError(403, gettext("Forbidden"));
		exit();
	} else { // maybe there was a login screen posted
		zp_handle_password('zp_image_auth', getOption('protected_image_password'), getOption('protected_image_user'));
	}
}

if (!isMyAlbum($album8, ALL_RIGHTS)) {
	$hash = getOption('protected_image_password'); 
	$authType = 'zp_image_auth';
	if (zp_getCookie($authType) != $hash) {
		require_once(dirname(__FILE__) . "/template-functions.php");
		$hint = get_language_string(getOption('protected_image_hint'));
		$show = getOption('protected_image_user');
		$parms = '';
		if (isset($_GET['wmk'])) {
			$parms = '&wmk='.$_GET['wmk'];
		}
		if (isset($_GET['q'])) {
			$parms .= '&q='.sanitize_numeric($_GET['q']);
		}
		if (isset($_GET['dsp'])) {
			$parms .= '&dsp='.sanitize_numeric($_GET['dsp']);
		}
		$action = WEBPATH.'/'.ZENFOLDER.'/full-image.php?userlog=1&a='.urlencode($album8).'&i='.urlencode($image8).$parms;
		printPasswordForm($hint, true, getOption('login_user_field') || $show, $action);
		exit();
	}
}



$image_path = getAlbumFolder().$album.'/'.$image;
$suffix = getSuffix($image_path);
$cache_file = $album . "/" . substr($image, 0, -strlen($suffix)-1) . '_FULL.' . $suffix;
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
	$args = array('FULL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
	$cache_file = getImageCacheFilename($album, $image, $args);
	$cache_path = SERVERCACHE.$cache_file;
	mkdir_recursive(dirname($cache_path), CHMOD_VALUE);
} else {
	$cache_path = NULL;
}

$rotate = false;
if (zp_imageCanRotate() && getOption('auto_rotate'))  {
	$rotate = getImageRotation($image_path);
}
$id = NULL;
$watermark_use_image = '';
if (isset($_GET['wmk'])) {
	$watermark_use_image = $_GET['wmk'];
} else {
	$watermark_use_image = getAlbumInherited($album, 'watermark', $id);
	if (empty($watermark_use_image)) $watermark_use_image = getOption('fullimage_watermark');
}
if (isset($_GET['q'])) {
	$quality = sanitize_numeric($_GET['q']);
} else {
	$quality = getOption('full_image_quality');
}
if (isset($_GET['dsp'])) {
	$disposal = sanitize($_GET['dsp']);
} else {
	$disposal = getOption('protect_full_image');
}

if (!$watermark_use_image && !$rotate) { // no processing needed
	if (getOption('album_folder_class') != 'external' && getOption('protect_full_image') != 'Download') { // local album system, return the image directly
		header('Content-Type: image/'.$suffix);
		header('Location: '.getAlbumFolder(FULLWEBPATH).pathurlencode(imgSrcURI($_zp_current_album->name.'/'.$_zp_current_image->filename)), true, 301);
		exit();
	} else {  // the web server does not have access to the image, have to supply it
		$fp = fopen($image_path, 'rb');
		// send the right headers
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
		header("Content-Type: image/$suffix");
		if ($disposal == 'Download') {
			header('Content-Disposition: attachment; filename="' . $image . '"');  // enable this to make the image a download
		}
		header("Content-Length: " . filesize($image_path));
		// dump the picture and stop the script
		fpassthru($fp);
		fclose($fp);
		exit();
	}
}
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
header("Content-Type: image/$suffix");
if ($disposal == 'Download') {
	header('Content-Disposition: attachment; filename="' . $image . '"');  // enable this to make the image a download
}

if (is_null($cache_path) || !file_exists($cache_path)) { //process the image
	$newim = zp_imageGet($image_path);
	if ($rotate) {
		$newim = zp_rotateImage($newim, $rotate);
	}
	if ($watermark_use_image) {
		$watermark_image = getWatermarkPath($watermark_use_image);
		if (!file_exists($watermark_image)) $watermark_image = SERVERPATH . '/' . ZENFOLDER . '/images/imageDefault.png';
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
	if (!zp_imageOutput($newim, $suffix, $cache_path, $quality) && DEBUG_IMAGE) {
		debugLog('full-image failed to create:'.$image);
	}
}

if (!is_null($cache_path)) {
	if ($disposal == 'Download') {
		$fp = fopen($cache_path, 'rb');
		// send the right headers
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
		header("Content-Type: image/$suffix");
		header("Content-Length: " . filesize($image_path));
		// dump the picture and stop the script
		fpassthru($fp);
		fclose($fp);
	} else {
		header('Location: ' . FULLWEBPATH.'/'.CACHEFOLDER.pathurlencode(imgSrcURI($cache_file)), true, 301);
	}
	exit();
}

?>

