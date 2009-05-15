<?php
/**
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
 * - cx and cy are measured from the top-left corner of the image.
 * - One of s, h, or w _must_ be specified; the others are optional.
 * - If more than one of s, h, or w are specified, s takes priority, then w+h:
 * - If none of s, h, or w are specified, the original image is returned.
 *******************************************************************************
 * @package core
 */

// force UTF-8 Ø


define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/functions-basic.php');
require_once(dirname(__FILE__).'/functions-image.php');

$debug = isset($_GET['debug']);

// Check for minimum parameters.
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	header("HTTP/1.0 404 Not Found");
	imageError(gettext("Too few arguments! Image not found."), 'err-imagenotfound.gif');
}

// Fix special characters in the album and image names if mod_rewrite is on:
// URL looks like: "/album1/subalbum/image/picture.jpg"

list($ralbum, $rimage) = rewrite_get_album_image('a', 'i');
$ralbum = internalToFilesystem($ralbum);
$rimage = internalToFilesystem($rimage);
$album = str_replace('..','', sanitize_path($ralbum));
$image = str_replace(array('/',"\\"),'', sanitize_path($rimage));
$theme = themeSetup($album); // loads the theme based image options.
$adminrequest = isset($_GET['admin']);

// Disallow abusive size requests.
if ( (isset($_GET['s']) && abs($_GET['s']) < MAX_SIZE)
|| (isset($_GET['w']) && abs($_GET['w']) < MAX_SIZE)
|| (isset($_GET['h']) && abs($_GET['h']) < MAX_SIZE)) {

	// Extract the image parameters from the input variables
	// This validates the input as well.
	$args = array(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
	if (isset($_GET['s'])) { //0
		$args[0] = $_GET['s'];
	}
	if (isset($_GET['w'])) {  //1
		$args[1] = $_GET['w'];
	}
	if (isset($_GET['h'])) { //2
		$args[2] = $_GET['h'];
	}
	if (isset($_GET['cw'])) { //3
		$args[3] = $_GET['cw'];
	}
	if (isset($_GET['ch'])) { //4
		$args[4] = $_GET['ch'];
	}
	if (isset($_GET['cx'])) { //5
		$args[5] = $_GET['cx'];
	}
	if (isset($_GET['cy'])) { //6
		$args[6] = $_GET['cy'];
	}
	if (isset($_GET['q'])) { //7
		$args[7] = $_GET['q'];
	}
	//8 thumb
	//9 crop
	if (isset($_GET['t'])) { //10
		$args[10] = $_GET['t'];
	}
	if (isset($_GET['wmt']) && !$adminrequest) { //11
		$args[11] = $_GET['wmt'];
	}
	$args [12] = $adminrequest; //12
	if (isset($_GET['gray'])) {
		$args[12] = $_GET['gray'];
	}
	$args = getImageParameters($args);
	list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbstandin, $thumbWM, $adminrequest, $gray) = $args;
	if (DEBUG_IMAGE) debugLog("i.php($ralbum, $rimage): \$size=$size, \$width=$width, \$height=$height, \$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy, \$quality=$quality, \$thumb=$thumb, \$crop=$crop, \$thumbstandin=$thumbstandin, \$thumbWM=$thumbWM, \$adminrequest=$adminrequest, \$gray=$gray");
	
	$allowWatermark = !$thumb && !$adminrequest;
} else {
	// No image parameters specified or are out of bounds; return the original image.
	//TODO: this will fail when the album folder is external to zp. Maybe should force the sizes within bounds.
	header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode(filesystemToInternal($album)) . "/" . rawurlencode(filesystemToInternal($image)));
	return;
}

// Construct the filename to save the cached image.
$newfilename = getImageCacheFilename(filesystemToInternal($album), filesystemToInternal($image), $args);
$newfile = SERVERCACHE . $newfilename;
if (trim($album)=='') {
	$imgfile = getAlbumFolder() . $image;
} else {
	$imgfile = getAlbumFolder() . $album.'/'.$image;
}

if ($debug) imageDebug($album, $image, $args, $imgfile);
	

/** Check for possible problems ***********
 ******************************************/
// Make sure the cache directory is writable, attempt to fix. Issue a warning if not fixable.
if (!is_dir(SERVERCACHE)) {
	@mkdir(SERVERCACHE, CHMOD_VALUE);
	@chmod(SERVERCACHE, CHMOD_VALUE);
	if (!is_dir(SERVERCACHE))
		imageError(gettext("The cache directory does not exist. Please create it and set the permissions to 0777."), 'err-cachewrite.gif');
}
if (!is_writable(SERVERCACHE)) {
	@chmod(SERVERCACHE, CHMOD_VALUE);
	if (!is_writable(SERVERCACHE))
		imageError(gettext("The cache directory is not writable! Attempts to chmod didn't work."), 'err-cachewrite.gif');
}
if (!file_exists($imgfile)) {
	$imgfile = $rimage; // undo the sanitize
	// then check to see if it is a transient image
	$i = strpos($imgfile, '_{');
	if ($i !== false) {
		$j = strpos($imgfile, '}_');
		$source = substr($imgfile, $i+2, $j-$i-2);
		$imgfile = substr($imgfile, $j+1);
		$i = strpos($imgfile, '_{');
		if ($i !== false) {
			$j = strpos($imgfile, '}_');
			$source2 = '/'.substr($imgfile, $i+2, $j-$i-2);
			$imgfile = substr($imgfile, $j+2);
		} else {
			$source2 = '';
		}

		if ($source != ZENFOLDER) {
			$source = THEMEFOLDER.'/'.$source;
		}
		$args[3] = $args[4] = 0;
		$args[5] = 1;    // full crops for these default images
		$args[9] = NULL; 
		$imgfile = SERVERPATH .'/'. $source.$source2 . "/" . $imgfile;
		
	} 
	if (!file_exists($imgfile)) {	
		header("HTTP/1.0 404 Not Found");
		imageError(gettext("Image not found; file does not exist."), 'err-imagenotfound.gif');
	}
}

// Make the directories for the albums in the cache, recursively.
// Skip this for safe_mode, where we can't write to directories we create!
if (!ini_get("safe_mode")) {
	$albumdirs = getAlbumArray($album, true);
	foreach($albumdirs as $dir) {
		$dir = internalToFilesystem($dir);
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
if (file_exists($newfile) & !$adminrequest) {
	if (filemtime($newfile) >= filemtime($imgfile)) {
		$process = false;
	}
}

// If the file hasn't been cached yet, create it.
if ($process) {
	// setup standard image options from the album theme if it exists
	cacheImage($newfilename, $imgfile, $args, $allowWatermark, false, $theme);
	$fmt = filemtime($newfile);
}
if (!$debug) {
	// ... and redirect the browser to it.
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fmt).' GMT');
	header('Content-Type: image/jpeg');
	header('Location: ' . FULLWEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode(imgSrcURI($newfilename)), true, 301);
	exit();

} else {
	echo "\n<p>Image: <img src=\"" . FULLWEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode(imgSrcURI($newfilename)) ."\" /></p>";

}

////////////////////////////////////////////////////////////////////////////////

function themeSetup($album) {
	// a real hack--but we need to conserve memory in i.php so loading the classes is out of the question.
	$theme = getOption('current_theme');
	$folders = explode('/', str_replace('\\', '/', $album));
	if (isset($_GET['album'])) { //it is an album thumb
		if (count($folders) <= 1) { // and the album is in the gallery
			return $theme;
		}
	}
	$uralbum = filesystemToInternal($folders[0]);
	$sql = 'SELECT `id`, `album_theme` FROM '.prefix('albums').' WHERE `folder`="'.$uralbum.'"';
	$result = query_single_row($sql);
	if (!empty($result['album_theme'])) {
		$theme = $result['album_theme'];
		//load the album theme options
		$sql = "SELECT `name`, `value` FROM ".prefix('options').' WHERE `ownerid`='.$result['id'];
		$optionlist = query_full_array($sql, true);
		if ($optionlist !== false) {
			foreach($optionlist as $option) {
				setOption($option['name'], $option['value'], false);
			}
		}
	}
	return $theme;
}

?>
