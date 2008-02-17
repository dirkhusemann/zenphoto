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
 *   t  - Set for custom images if used as thumbs (no watermarking.)
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
@ini_set('memory_limit','128M');

$debug = isset($_GET['debug']);

// Check for minimum parameters.
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	imageError("Too few arguments! Image not found.", 'err-imagenotfound.gif');
}
$allowWatermark = true;
if (isset($_GET['t'])) {
	$allowWatermark = !$_GET['t'];
} else {
	if (isset($_GET[s])) {
		$allowWatermark = $_GET['s'] != 'thumb';
	}
}

// Fix special characters in the album and image names if mod_rewrite is on:
// URL looks like: "/album1/subalbum/image/picture.jpg"
list($ralbum, $rimage, $search_link) = rewrite_get_album_image('a', 'i');
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

	if ($debug) debugLog("Album: [ " . $album . " ], Image: [ " . $image . " ]<br/><br/>");
	if ($debug) DebugLogArray("args", $args);

} else {
	// No image parameters specified or are out of bounds; return the original image.
	//TODO: this will fail when the album folder is external to zp. Maybe should force the sizes within bounds.
	header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
	return;
}

// Construct the filename to save the cached image.
$newfilename = getImageCacheFilename($album, $image, $args);
$newfile = SERVERCACHE . $newfilename;
if (trim($album)=='') {
	$imgfile = getAlbumFolder() . $image;
} else {
	$imgfile = getAlbumFolder() . "$album/$image";
}

/** Check for possible problems ***********
 ******************************************/
// Make sure the cache directory is writable, attempt to fix. Issue a warning if not fixable.
if (!is_dir(SERVERCACHE)) {
	@mkdir(SERVERCACHE, CHMOD_VALUE);
	@chmod(SERVERCACHE, CHMOD_VALUE);
	if (!is_dir(SERVERCACHE))
	imageError("The cache directory does not exist. Please create it and set the permissions to 0777.", 'err-cachewrite.gif');
}
if (!is_writable(SERVERCACHE)) {
	@chmod(SERVERCACHE, CHMOD_VALUE);
	if (!is_writable(SERVERCACHE))
	imageError("The cache directory is not writable! Attempts to chmod didn't work.", 'err-cachewrite.gif');
}

// Make the directories for the albums in the cache, recursively.
// Skip this for safe_mode, where we can't write to directories we create!
if (!ini_get("safe_mode")) {
	$albumdirs = getAlbumArray($album, true);
	foreach($albumdirs as $dir) {
		$dir = SERVERCACHE . '/' . $dir;
		if (!is_dir($dir)) {
			@mkdir($dir, CHMOD_VALUE);
			chmod($dir, CHMOD_VALUE);
		} else if (!is_writable($dir)) {
			chmod($dir, CHMOD_VALUE);
		}
	}
}

$process = true;
// If the file exists, check its modification time and update as needed.
$fmt = filemtime($imgfile);
if (file_exists($newfile)) {
	if ($fmt = filemtime($newfile) < filemtime($imgfile)) {
		$process = true;
	} else {
		$process = false;
	}
}
// If the file hasn't been cached yet, create it.
if ($process) {
	cacheGalleryImage($newfilename, $imgfile, $args, $allowWatermark);
}
if (!$debug) {
	// ... and redirect the browser to it.
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fmt).' GMT');
	header('Content-Type: image/jpeg');
	header('Location: ' . FULLWEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($newfilename), true, 301);
	exit();

} else {

	echo "\n<p>Image: <img src=\"" . FULLWEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($newfilename) ."\" /></p>";

}

////////////////////////////////////////////////////////////////////////////////

?>
