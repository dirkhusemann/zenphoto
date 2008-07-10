<?php
/**
 * basic functions used by zenphoto core
 * @package functions
 *
 */
define('DEBUG_LOGIN', false); // set to true to log admin saves and login attempts
define('ALBUM_OPTIONS_TABLE', true);  // TODO: 1.2 change this to true. See also the 1.2 todo list on the tasks tab
define('SAFE_GLOB', false);
include('version.php'); // Include the version info.
if (!defined('CHMOD_VALUE')) { define('CHMOD_VALUE', 0777); }
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
if (!defined('OFFSET_PATH')) { define('OFFSET_PATH', 0); }
if(!function_exists("gettext")) {
	// load the drop-in replacement library
	require_once('lib-gettext/gettext.inc');
}
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding('UTF-8');
}

// Set the memory limit higher just in case -- supress errors if user doesn't have control.
if (ini_get('memory_limit') < '128M') {
	@ini_set('memory_limit','128M');
}

// functions.php - HEADERS NOT SENT YET!

if (!file_exists(dirname(__FILE__) . "/zp-config.php")) {
	die ("<strong>".gettext("Zenphoto error:</strong> zp-config.php not found. Perhaps you need to run")." <a href=\"" . ZENFOLDER . "/setup.php\">setup</a> ".gettext("(or migrate your old config.php)"));
}

// Including zp-config.php more than once is OK, and avoids $conf missing.
require("zp-config.php");

// If the server protocol is not set, set it to the default (obscure zp-config.php change).
if (!isset($_zp_conf_vars['server_protocol'])) $_zp_conf_vars['server_protocol'] = 'http';

require_once('lib-kses.php');
require_once('exif/exif.php');
require_once('functions-db.php');

// allow reading of old Option tables--should be needed only during upgrade
$hasownerid = false;
$result = query_full_array("SHOW COLUMNS FROM ".prefix('options').' LIKE "%ownerid%"', true);
if (is_array($result)) {
	foreach ($result as $row) {
		if ($row['Field'] == 'ownerid') {
			$hasownerid = true;
			break;
		}
	}
}

switch (OFFSET_PATH) {
	case 0:	// starts from the root index.php
		$const_webpath = dirname($_SERVER['SCRIPT_NAME']);
		break;
	case 1:  // starts from the zp-core folder
	case 2:
		$const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
		break;
	case 3: // starts from the plugins folder
		$const_webpath = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
		break;
	case 4: // starts from within a folder within the plugins folder
		$const_webpath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
		break;
}
$const_webpath = str_replace("\\", '/', $const_webpath);
if ($const_webpath == '/') $const_webpath = '';
if (!defined('WEBPATH')) { define('WEBPATH', $const_webpath); }
define('SERVERPATH', str_replace("\\", '/', dirname(dirname(__FILE__))));
define('PROTOCOL', getOption('server_protocol'));
define('FULLWEBPATH', PROTOCOL."://" . $_SERVER['HTTP_HOST'] . WEBPATH);
define('SAFE_MODE_ALBUM_SEP', '__');
if (!defined('DEBUG')) { define('DEBUG', false); }
define('CACHEFOLDER', '/cache/');
define('SERVERCACHE', SERVERPATH . substr(CACHEFOLDER, 0, -1));

// Set the version number.
$_zp_conf_vars['version'] = ZENPHOTO_VERSION;

// the options array
$_zp_options = NULL;

/* album folder
 *  Name of the folder where albums are located.
 *  may be overridden by zp-config:
 *    Set conf['album_folder'] to the folder path that is located within the zenphoto folders.
 *      or
 *    Set conf['external_album_folder'] to an external folder path.
 *  An external folder path overrides one located within the zenphotos folders.
 */
define('ALBUMFOLDER', '/albums/');
if (!defined('PLUGIN_FOLDER')) { define('PLUGIN_FOLDER', '/plugins/'); }

/*******************************************************************************
 * native gettext respectivly php-gettext replacement							             *
 *******************************************************************************/

require_once('functions-i18n.php');

getUserLocale();

setupCurrentLocale();

$session_started = getOption('album_session');
if ($session_started) session_start();

// Set error reporting to the default if it's not.
error_reporting(E_ALL ^ E_NOTICE);
$_zp_error = false;

/**
 * wraps htmlspecialchars and makes it work for xml
 *
 * @param string $text
 * @return string
 */
function xmlspecialchars($text) {
	return str_replace("&#039;", '&apos;', htmlspecialchars($text, ENT_QUOTES));
} 

/**
 * Get a option stored in the database.
 * This function reads the options only once, in order to improve performance.
 * @param string $key the name of the option.
 * @param bool $db set to true to force retrieval from the database.
 */
function getOption($key, $db=false) {
	global $_zp_conf_vars, $_zp_options;
	if (is_null($_zp_options)) {
		$_zp_options = array();

		$sql = "SELECT `name`, `value` FROM ".prefix('options');
		if ($hasownerid) $sql .= ' WHERE `ownerid`=0';
		$optionlist = query_full_array($sql, true);
		if ($optionlist !== false) {
			foreach($optionlist as $option) {
				$_zp_options[$option['name']] = $option['value'];
			}
		}
	} else {
		if ($db) {
			$sql = "SELECT `value` FROM ".prefix('options')." WHERE `name`='".$key."'";
			if ($hasownerid) $sql .= " AND `ownerid`=0";
			$optionlist = query_single_row($sql);
			return $optionlist['value'];
		}
	}
	if (array_key_exists($key, $_zp_options)) {
		return $_zp_options[$key];
	} else {
		if (array_key_exists($key, $_zp_conf_vars)) {
			return $_zp_conf_vars[$key];
		} else {
			return NULL;
		}
	}
}

/**
 * Stores an option value.
 *
 * @param string $key name of the option.
 * @param mixed $value new value of the option.
 * @param bool $persistent set to false if the option is stored in memory only
 * otherwise it is preserved in the database
 */
function setOption($key, $value, $persistent=true) {
	global $_zp_conf_vars, $_zp_options;
	if ($persistent) {
		$result = query_single_row("SELECT `value` FROM ".prefix('options')." WHERE `name`='".$key."' AND `ownerid`=0");
		if (is_array($result) && array_key_exists('value', $result)) { // option already exists.
			$sql = "UPDATE " . prefix('options') . " SET `value`='" . escape($value) . "' WHERE `name`='" . escape($key) ."' AND `ownerid`=0";
			$result = query($sql, true);
		} else {
				$sql = "INSERT INTO " . prefix('options') . " (name, value, ownerid) VALUES ('" . escape($key) . "','" . escape($value) . "', 0)";
				$result = query($sql, true);
		}
	} else {
		$result = true;
	}
	if ($result) {
		$_zp_options[$key] = strip($value);
		return true;
	} else {
		return false;
	}
}

/**
 * Converts a boolean value to 1 or 0 and sets the option with it
 *
 * @param string $key the option
 * @param bool $value the value to be set
 */
function setBoolOption($key, $value) {
	if ($value) {
		setOption($key, '1');
	} else {
		setOption($key, '0');
	}
}

/**
 * Sets the default value of an option.
 *
 * If the option has never been set it is set to the value passed
 *
 * @param string $key the option name
 * @param mixed $default the value to be used as the default
 */
function setOptionDefault($key, $default) {
	global $_zp_conf_vars, $_zp_options;
	if (NULL == $_zp_options) { getOption('nil'); } // pre-load from the database
	if (!array_key_exists($key, $_zp_options)) {
		$sql = "INSERT INTO " . prefix('options') . " (`name`, `value`, `ownerid`) VALUES ('" . escape($key) . "', '".
						escape($default) . "', 0);";
		query($sql, true);
		$_zp_options[$key] = $value;
	}
}

/**
 * Retuns the option array
 *
 * @return array
 */
function getOptionList() {
	global $_zp_options;
	if (NULL == $_zp_options) { getOption('nil'); } // pre-load from the database
	return $_zp_options;
}

function getOptionTableName($albumname) {
	$pfxlen = strlen(prefix(''));
	if (strlen($albumname) > 54-$pfxlen) { // table names are limited to 62 characters
		return substr(substr($albumname, 0, max(0,min(24-$pfxlen, 20))).'_'.md5($albumname),0,54-$pfxlen).'_options';  
	}
	return $albumname.'_options';
}

/**
 * parses the 'allowed_tags' option for use by kses
 *
 *@param string &$source by name, contains the string with the tag options
 *@return array the allowed_tags array.
 *@since 1.1.3
 **/
function parseAllowedTags(&$source) {
	$source = trim($source);
	if (substr($source, 0, 1) != "(") { return false; }
	$source = substr($source, 1); //strip off the open paren
	$a = array();
	while ((strlen($source) > 1) && (substr($source, 0, 1) != ")")) {
		$i = strpos($source, '=>');
		if ($i === false) { return false; }
		$tag = trim(substr($source, 0, $i));
		$source = trim(substr($source, $i+2));
		if (substr($source, 0, 1) != "(") { return false; }
		$x = parseAllowedTags($source, $level);
		if ($x === false) { return false; }
		$a[$tag] = $x;
	}
	if (substr($source, 0, 1) != ')') { return false; }
	$source = trim(substr($source, 1)); //strip the close paren
	return $a;
}

// Set up default EXIF variables:
// Note: The database setup/upgrade uses this list, so if fields are added or deleted, upgrade.php should be
//   run or the new data won't be stored (but existing fields will still work; nothing breaks).
$_zp_exifvars = array(
// Database Field       => array('IFDX',   'ExifKey',           'ZP Display Text',        Display?)
		'EXIFOrientation'       => array('IFD0',   'Orientation',       gettext('Orientation'),            false),
		'EXIFMake'              => array('IFD0',   'Make',              gettext('Camera Maker'),           true),
		'EXIFModel'             => array('IFD0',   'Model',             gettext('Camera Model'),           true),
		'EXIFExposureTime'      => array('SubIFD', 'ExposureTime',      gettext('Shutter Speed'),          true),
		'EXIFFNumber'           => array('SubIFD', 'FNumber',           gettext('Aperture'),               true),
		'EXIFFocalLength'       => array('SubIFD', 'FocalLength',       gettext('Focal Length'),           true),
		'EXIFFocalLength35mm'   => array('SubIFD', 'FocalLength35mmEquiv', gettext('35mm Equivalent Focal Length'), false),
		'EXIFISOSpeedRatings'   => array('SubIFD', 'ISOSpeedRatings',   gettext('ISO Sensitivity'),        true),
		'EXIFDateTimeOriginal'  => array('SubIFD', 'DateTimeOriginal',  gettext('Time Taken'),             true),
		'EXIFExposureBiasValue' => array('SubIFD', 'ExposureBiasValue', gettext('Exposure Compensation'),  true),
		'EXIFMeteringMode'      => array('SubIFD', 'MeteringMode',      gettext('Metering Mode'),          true),
		'EXIFFlash'             => array('SubIFD', 'Flash',             gettext('Flash Fired'),            true),
		'EXIFImageWidth'        => array('SubIFD', 'ExifImageWidth',    gettext('Original Width'),         false),
		'EXIFImageHeight'       => array('SubIFD', 'ExifImageHeight',   gettext('Original Height'),        false),
		'EXIFContrast'          => array('SubIFD', 'Contrast',          gettext('Contrast Setting'),       false),
		'EXIFSharpness'         => array('SubIFD', 'Sharpness',         gettext('Sharpness Setting'),      false),
		'EXIFSaturation'        => array('SubIFD', 'Saturation',        gettext('Saturation Setting'),     false),
		'EXIFGPSLatitude'       => array('GPS',    'Latitude',          gettext('Latitude'),               false),
		'EXIFGPSLatitudeRef'    => array('GPS',    'Latitude Reference',gettext('Latitude Reference'),     false),
		'EXIFGPSLongitude'      => array('GPS',    'Longitude',         gettext('Longitude'),              false),
		'EXIFGPSLongitudeRef'   => array('GPS',    'Longitude Reference',gettext('Longitude Reference'),   false),
		'EXIFGPSAltitude'       => array('GPS',    'Altitude',          gettext('Altitude'),               false),
		'EXIFGPSAltitudeRef'    => array('GPS',    'Altitude Reference',gettext('Altitude Reference'),     false)
);


// Set up assertions for debugging.
assert_options(ASSERT_ACTIVE, 0);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
/**
 * Emits an assertion error
 *
 * @param string $file the script file
 * @param string $line the line of the assertion
 * @param string $code the error message
 */
function assert_handler($file, $line, $code) {
	dmesg(gettext("ERROR: Assertion failed in")." [$file:$line]: $code");
}
// Set up assertion callback
assert_options(ASSERT_CALLBACK, 'assert_handler');

// Image utility functions
/**
 * Returns true if the file is an image
 *
 * @param string $filename the name of the target
 * @return bool
 */
function is_valid_image($filename) {
	$ext = strtolower(substr(strrchr($filename, "."), 1));
	return in_array($ext, array('jpg','jpeg','gif','png'));
}

$_zp_supported_videos = array('flv','3gp','mov','mp3','mp4');
//ZenVideo: Video utility functions
/**
 * Returns true fi the file is a video file
 *
 * @param string $filename the name of the target
 * @return bool
 */
function is_valid_video($filename) {
	global $_zp_supported_videos;
	$ext = strtolower(substr(strrchr($filename, "."), 1));
	return in_array($ext, $_zp_supported_videos);
}


/**
 * Check if the image is a video thumb
 *
 * @param string $album folder path for the album
 * @param string $filename name of the target
 * @return bool
 *
 * Note: this function is inefficient and slows down the image file loop a lot.
 * Don't use it in a loop!
 */
function is_videoThumb($album, $filename){
	global $_zp_supported_videos;
	$ext = strtolower(substr($fext = strrchr($filename, "."), 1));
	if (in_array($ext, $_zp_supported_videos)) {
		return str_replace($fext, '', $filename);
	}
	return false;
}

/**
 * Search for a thumbnail for the image
 *
 * @param string $album folder path of the album
 * @param string $video name of the target
 * @return string
 */
function checkVideoThumb($album, $video){
	$video = is_videoThumb($album, $video);
	if($video) {
		$extTab = array(".jpg",".jpeg",".gif",".png");
		foreach($extTab as $ext) {
			if(file_exists($album."/".$video.$ext)) {
				return $video.$ext;
			}
		}
	}
	return NULL;
}

/**
 * Search for a high quality version of the video
 *
 * @param string $album folder path of the album
 * @param string $video name of the target
 * @return string
 */
function checkVideoOriginal($album, $video){
	$video = is_videoThumb($album, $video);
	if ($video) {
		$extTab = array(".ogg",".OGG",".avi",".AVI",".wmv",".WMV");
		foreach($extTab as $ext) {
			if(file_exists($album."/".$video.$ext)) {
				return $video.$ext;
			}
		}
	}
	return NULL;
}

/**
 * Returns a truncated string
 *
 * @param string $string souirce string
 * @param int $length how long it should be
 * @return string
 */
function truncate_string($string, $length) {
	if (strlen($string) > $length) {
		$pos = strpos($string, ' ', $length);
		if ($pos === FALSE) return substr($string, 0, $length) . '...';
		return substr($string, 0, $pos) . '...';
	}
	return $string;
}

/**
 * Returns the oldest ancestor of an alubm;
 *
 * @param string $album an album object
 * @return object
 */
function getUrAlbum($album) {
	while (true) {
		$parent = $album->getParent();
		if (is_null($parent)) { return $album; }
		$album = $parent;
	}
}

/**
 * Returns the DB sort key for an album sort type
 *
 * @param string $sorttype The sort type option
 * @return string
 */
function albumSortKey($sorttype) {
	switch ($sorttype) {
		case "Title":
			return 'title';
		case "Manual":
			return 'sort_order';
		case "Date":
			return 'date';
		case "ID":
			return 'id';
	}
	return 'filename';
}
/**
 * Returns the DB key associated with the subalbum sort type
 *
 * @param string $sorttype subalbum sort type
 * @return string
 */
function subalbumSortKey($sorttype) {
	switch ($sorttype) {
		case "Title":
			return 'title';
		case "Manual":
			return 'sort_order';
		case "Filename":
			return 'folder';
		case "Date":
			return 'date';
		case "ID":
			return 'id';
	}
	return 'sort_order';
}

/**
 * rewrite_get_album_image - Fix special characters in the album and image names if mod_rewrite is on:
 * This is redundant and hacky; we need to either make the rewriting completely internal,
 * or fix the bugs in mod_rewrite. The former is probably a good idea.
 *
 *  Old explanation:
 *    rewrite_get_album_image() parses the album and image from the requested URL
 *    if mod_rewrite is on, and replaces the query variables with corrected ones.
 *    This is because of bugs in mod_rewrite that disallow certain characters.
 *
 * @param string $albumvar "$_GET" parameter for the album
 * @param string $imagevar "$_GET" parameter for the image
 */
function rewrite_get_album_image($albumvar, $imagevar) {
	if (getOption('mod_rewrite')) {
		$path = urldecode(substr($_SERVER['REQUEST_URI'], strlen(WEBPATH)+1));
		// Only extract the path when the request doesn't include the running php file (query request).
		if (strlen($path) > 0 && strpos($_SERVER['REQUEST_URI'], $_SERVER['PHP_SELF']) === false && isset($_GET[$albumvar])) {
			$im_suffix = getOption('mod_rewrite_image_suffix');
			$suf_len = strlen($im_suffix);
			$qspos = strpos($path, '?');
			if ($qspos !== false) $path = substr($path, 0, $qspos);
			// Strip off the image suffix (could interfere with the rest, needs to go anyway).
			if ($suf_len > 0 && substr($path, -($suf_len)) == $im_suffix) {
				$path = substr($path, 0, -($suf_len));
			}

			if (substr($path, -1, 1) == '/') $path = substr($path, 0, strlen($path)-1);
			$pagepos  = strpos($path, '/page/');
			$slashpos = strrpos($path, '/');
			$imagepos = strpos($path, '/image/');
			if ($imagepos !== false) {
				$ralbum = substr($path, 0, $imagepos);
				$rimage = substr($path, $slashpos+1);
			} else if ($pagepos !== false) {
				$ralbum = substr($path, 0, $pagepos);
				$rimage = null;
			} else if ($slashpos !== false) {
				$ralbum = substr($path, 0, $slashpos);
				$rimage = substr($path, $slashpos+1);
				if ((is_dir(getAlbumFolder() . $ralbum . '/' . $rimage)) || hasDyanmicAlbumSuffix($rimage)) {
					$ralbum = $ralbum . '/' . $rimage;
					$rimage = null;
				}
			} else {
				$ralbum = $path;
				$rimage = null;
			}
			return array($ralbum, $rimage);
		}
	}
	// No mod_rewrite, or no album, etc. Just send back the query args.
	$ralbum = isset($_GET[$albumvar]) ? $_GET[$albumvar] : null;
	$rimage = isset($_GET[$imagevar]) ? $_GET[$imagevar] : null;
	return array($ralbum, $rimage);
}

/** getAlbumArray - returns an array of folder names corresponding to the
 *     given album string.
 * @param string $albumstring is the path to the album as a string. Ex: album/subalbum/my-album
 * @param string $includepaths is a boolean whether or not to include the full path to the album
 *    in each item of the array. Ex: when $includepaths==false, the above array would be
 *    ['album', 'subalbum', 'my-album'], and with $includepaths==true,
 *    ['album', 'album/subalbum', 'album/subalbum/my-album']
 *  @return array
 */
function getAlbumArray($albumstring, $includepaths=false) {
	if ($includepaths) {
		$array = array($albumstring);
		while($slashpos = strrpos($albumstring, '/')) {
			$albumstring = substr($albumstring, 0, $slashpos);
			array_unshift($array, $albumstring);
		}
		return $array;
	} else {
		return explode('/', $albumstring);
	}
}

/**
 * Returns the name of an image for uses in caching it
 *
 * @param string $album album folder
 * @param string $image image file name
 * @param array $args cropping arguments
 * @return string
 */
function getImageCacheFilename($album, $image, $args) {
	// Set default variable values.
	$postfix = getImageCachePostfix($args);
	if (ini_get('safe_mode')) {
		$albumsep = SAFE_MODE_ALBUM_SEP;
		$album = str_replace(array('/',"\\"), $albumsep, $album);
	} else {
		$albumsep = '/';
	}
	return '/' . $album . $albumsep . $image . $postfix . '.jpg';
}

/**
 * Returns the crop/sizing string to postfix to a cache image
 *
 * @param array $args cropping arguments
 * @return string
 */
function getImageCachePostfix($args) {
	list($size, $width, $height, $cw, $ch, $cx, $cy) = $args;
	$postfix_string = ($size ? "_$size" : "") . ($width ? "_w$width" : "")
	. ($height ? "_h$height" : "") . ($cw ? "_cw$cw" : "") . ($ch ? "_ch$ch" : "")
	. (is_numeric($cx) ? "_cx$cx" : "") . (is_numeric($cy) ? "_cy$cy" : "");
	return $postfix_string;
}


/**
 * Validates and edits image size/cropping parameters
 *
 * @param array $args cropping arguments
 * @return array
 */
function getImageParameters($args) {
	$thumb_crop = getOption('thumb_crop');
	$thumb_size = getOption('thumb_size');
	$thumb_crop_width = getOption('thumb_crop_width');
	$thumb_crop_height = getOption('thumb_crop_height');
	$thumb_quality = getOption('thumb_quality');
	$image_default_size = getOption('image_size');
	$quality = getOption('image_quality');
	// Set up the parameters
	$thumb = $crop = false;
	@list($size, $width, $height, $cw, $ch, $cx, $cy, $quality) = $args;

	if ($size == 'thumb') {
		$thumb = true;
		if ($thumb_crop) {
			$cw = $thumb_crop_width;
			$ch = $thumb_crop_height;
		}
		$size = round($thumb_size);
		$quality = round($thumb_quality);

	} else if ((is_numeric($size) && is_numeric($cw) && is_numeric($ch))
	|| (is_numeric($width) && is_numeric($height))) {
		if (is_numeric($width) && is_numeric($height)) {
			$size = max($width, $height);
			$cw = $width;
			$ch = $height;
			$height = $width = false;
		}
		$thumb = true;
		$cw = min($size, $cw);
		$ch = min($size, $ch);

	} else {
		if ($size == 'default') {
			$size = $image_default_size;
		} else if (empty($size) || !is_numeric($size)) {
			$size = false; // 0 isn't a valid size anyway, so this is OK.
		} else {
			$size = round($size);
		}
	}

	// Round each numeric variable, or set it to false if not a number.
	list($width, $height, $cw, $ch, $cx, $cy, $quality) =
	array_map('sanitize_numeric', array($width, $height, $cw, $ch, $cx, $cy, $quality));
	if (empty($cw) && empty($ch)) $crop = false; else $crop = true;
	if (empty($quality)) $quality = getOption('image_quality');

	// Return an array of parameters used in image conversion.
	return array($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop);
}


/**
 * Checks if the input is numeric, rounds if so, otherwise returns false.
 *
 * @param mixed $num the number to be sanitized
 * @return int
 */
function sanitize_numeric($num) {
	if (is_numeric($num)) {
		return abs(round($num));
	} else {
		return false;
	}
}


/** Takes a user input string (usually from the query string) and cleans out
 * HTML, null-bytes, and slashes (if magic_quotes_gpc is on) to prevent
 * XSS attacks and other malicious user input, and make strings generally clean.
 *
 * @param string $input_string is a string that needs cleaning.
 * @param string $deepclean is whether to replace HTML tags, javascript, etc.
 * @return string the sanitized string.
 */
function sanitize($input_string, $deepclean=false) {
	if (get_magic_quotes_gpc()) $input_string = stripslashes($input_string);
	$input_string = str_replace(chr(0), " ", $input_string);
	if ($deepclean) $input_string = kses($input_string, array());
	return $input_string;
}

/** Takes user input meant to be used within a path to a file or folder and
 * removes anything that could be insecure or malicious, or result in duplicate
 * representations for the same physical file.
 *
 * Returns the sanitized path
 *
 * @param string $filename is the path text to filter.
 * @return string
 */
function sanitize_path($filename) {
	$filename = str_replace(chr(0), " ", $filename);
	$filename = strip_tags($filename);
	$filename = preg_replace(array('/^\/+/','/\/+$/','/\/\/+/','/\.\.+/'), '', $filename);
	return $filename;
}

/**
 * Formats an error message
 * If DEBUG is set, supplies the calling sequence
 *
 * @param string $message
 */
function zp_error($message) {
	global $_zp_error;
	if (!$_zp_error) {
		echo '<div style="padding: 15px; border: 1px solid #F99; background-color: #FFF0F0; margin: 20px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt;">'
		. ' <h2 style="margin: 0px 0px 5px; color: #C30;">Zenphoto Error</h2><div style=" color:#000;">' . "\n\n" . $message . '</div>';
		if (DEBUG) {
			// Get a backtrace.
			$bt = debug_backtrace();
			array_shift($bt); // Get rid of zp_error in the backtrace.
			$prefix = '  ';
			echo "\n\n<p><strong>Backtrace:</strong> <br />\n<pre>\n";
			foreach($bt as $b) {
				echo $prefix . ' in '
				. (isset($b['class']) ? $b['class'] : '')
				. (isset($b['type']) ? $b['type'] : '')
				. $b['function']
				. ' (' . basename($b['file'])
				. ' [' . $b['line'] . "])\n";
				$prefix .= '  ';
			}
			echo "</p>\n";
		}
		echo "</div>\n";
		$_zp_error = true;
		exit();
	}
}

/**
 * Returns either the rewrite path or the plain, non-mod_rewrite path
 * based on the mod_rewrite option in zp-config.php.
 * The given paths can start /with or without a slash, it doesn't matter.
 *
 * IDEA: this function could be used to specially escape items in
 * the rewrite chain, like the # character (a bug in mod_rewrite).
 *
 * This is here because it's used in both template-functions.php and in the classes.
 * @param string $rewrite is the path to return if rewrite is enabled. (eg: "/myalbum")
 * @param string $plain is the path if rewrite is disabled (eg: "/?album=myalbum")
 * @return string
 */
function rewrite_path($rewrite, $plain) {
	$path = null;
	if (getOption('mod_rewrite')) {
		$path = $rewrite;
	} else {
		$path = $plain;
	}
	if (substr($path, 0, 1) == "/") $path = substr($path, 1);
	return WEBPATH . "/" . $path;
}

/**
 * Returns a formated date for output
 *
 * @param string $format the "strftime" format string
 * @param date $dt the date to be output
 * @return string
 */
function zpFormattedDate($format, $dt) {
	$fdate = strftime($format, $dt);
	$chrset = 'ISO-8859-1';
	if (function_exists('mb_internal_encoding')) {
		if (($charset = mb_internal_encoding()) == 'UTF-8') {
			return $fdate;
		}
	}
	return utf8::convert($fdate, $charset);
}

/**
 * Simple mySQL timestamp formatting function.
 *
 * @param string $format formatting template
 * @param int $mytimestamp timestamp
 * @return string
 */
function myts_date($format,$mytimestamp)
{
	// If your server is in a different time zone than you, set this.
	$timezoneadjust = getOption('time_offset');

	$month  = substr($mytimestamp,4,2);
	$day    = substr($mytimestamp,6,2);
	$year   = substr($mytimestamp,0,4);

	$hour   = substr($mytimestamp,8,2);
	$min    = substr($mytimestamp,10,2);
	$sec    = substr($mytimestamp,12,2);

	$epoch  = mktime($hour+$timezoneadjust,$min,$sec,$month,$day,$year);
	$date   = zpFormattedDate($format, $epoch);
	return $date;
}

// Text formatting and checking functions

/**
 * Determines if the input is an e-mail address. Adapted from WordPress.
 * Name changed to avoid conflicts in WP integrations.
 *
 * @param string $input_email email address?
 * @return bool
 */
function is_valid_email_zp($input_email) {
	$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
	if(strstr($input_email, '@') && strstr($input_email, '.')) {
		if (preg_match($chars, $input_email)) {
			return true;
		}
	}
	return false;
}

/**
 * Checks for a zip file
 *
 * @param string $filename name of the file
 * @return bool
 */
function is_zip($filename) {
	$ext = strtolower(strrchr($filename, "."));
	return ($ext == ".zip");
}


/**
 * rawurlencode function that is path-safe (does not encode /)
 *
 * @param string $path URL
 * @return string
 */
function pathurlencode($path) {
	return implode("/", array_map("rawurlencode", explode("/", $path)));
}

/**
 * Get the size of a directory.
 * From: http://aidan.dotgeek.org/lib/
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.0
 * @param       string $directory   Path to directory
 */
function dirsize($directory)
{
	$size = 0;
	if (substr($directory, -1, 1) !== DIRECTORY_SEPARATOR) {
		$directory .= DIRECTORY_SEPARATOR;
	}
	$stack = array($directory);
	for ($i = 0, $j = count($stack); $i < $j; ++$i) {
		if (is_file($stack[$i])) {
			$size += filesize($stack[$i]);
		} else if (is_dir($stack[$i])) {
			$dir = dir($stack[$i]);
			while (false !== ($entry = $dir->read())) {
				if ($entry == '.' || $entry == '..') continue;
				$add = $stack[$i] . $entry;
				if (is_dir($stack[$i] . $entry)) $add .= DIRECTORY_SEPARATOR;
				$stack[] = $add;
			}
			$dir->close();
		}
		$j = count($stack);
	}
	return $size;
}

/**
 * Return human readable sizes
 * From: http://aidan.dotgeek.org/lib/
 *
 * @param       int    $size        Size
 * @param       int    $unit        The maximum unit
 * @param       int    $retstring   The return string format
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.1.0
 */
function size_readable($size, $unit = null, $retstring = null)
{
	// Units
	$sizes = array('B', 'KB', 'MB', 'GB', 'TB');
	$ii = count($sizes) - 1;

	// Max unit
	$unit = array_search((string) $unit, $sizes);
	if ($unit === null || $unit === false) {
		$unit = $ii;
	}

	// Return string
	if ($retstring === null) {
		$retstring = '%01.2f %s';
	}

	// Loop
	$i = 0;
	while ($unit != $i && $size >= 1024 && $i < $ii) {
		$size /= 1024;
		$i++;
	}

	return sprintf($retstring, $size, $sizes[$i]);
}


/**
 * Takes a comment and makes the body of an email.
 *
 * @param string $str comment
 * @param string $name author
 * @param string $albumtitle album
 * @param string $imagetitle image
 * @return string
 */
function commentReply($str, $name, $albumtitle, $imagetitle) {
	$str = wordwrap(strip_tags($str), 75, '\n');
	$lines = explode('\n', $str);
	$str = implode('%0D%0A', $lines);
	$str = "$name commented on $imagetitle in the album $albumtitle: %0D%0A%0D%0A" . $str;
	return $str;
}


/**
 * Parses and sanitizes Theme definition text
 *
 * @param file $file theme file
 * @return string
 */
function parseThemeDef($file) {
	$themeinfo = array();
	if (is_readable($file) && $fp = @fopen($file, "r")) {
		while($line = fgets($fp)) {
			if (substr(trim($line), 0, 1) != "#") {
				$item = explode("::", $line);
				$allowed_tags = "(".getOption('allowed_tags').")";
				$allowed = parseAllowedTags($allowed_tags);
				$themeinfo[trim($item[0])] = kses(trim($item[1]), $allowed);
			}
		}
		return $themeinfo;
	} else {
		return false;
	}
}

/**
 * Send an mail to the admin user(s). We also attempt to intercept any form injection
 * attacks by slime ball spammers.
 *
 * @param string $subject  The subject of the email.
 * @param string $message  The message contents of the email.
 * @param string $headers  Optional headers for the email.
 * @param array $admin_emails a list of email addresses
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function zp_mail($subject, $message, $headers = '', $admin_emails=null) {
	if (is_null($admin_emails)) { $admin_emails = getAdminEmail(); }
	if (count($admin_emails) > 0) {
		// Make sure no one is trying to use our forms to send Spam
		// Stolen from Hosting Place:
		//   http://support.hostingplace.co.uk/knowledgebase.php?action=displayarticle&cat=0000000039&id=0000000040
		$badStrings = array("Content-Type:", "MIME-Version:",	"Content-Transfer-Encoding:",	"bcc:",	"cc:");
		foreach($_POST as $k => $v) {
			foreach($badStrings as $v2) {
				if (strpos($v, $v2) !== false) {
					header("HTTP/1.0 403 Forbidden");
					die("Forbidden");
					exit();
				}
			}
		}

		foreach($_GET as $k => $v){
			foreach($badStrings as $v2){
				if (strpos($v, $v2) !== false){
					header("HTTP/1.0 403 Forbidden");
					die("Forbidden");
					exit();
				}
			}
		}

		if( $headers == '' ) {
			$headers = "From: " . getOption('gallery_title') . "<zenphoto@" . $_SERVER['SERVER_NAME'] . ">";
		}

		// Convert to UTF-8
		if (getOption('charset') != 'UTF-8') {
			$subject = utf8::convert($subject, getOption('charset'));
			$message = utf8::convert($message, getOption('charset'));
		}

		// Send the mail
		foreach ($admin_emails as $email) {
			UTF8::send_mail($email, $subject, $message, $headers);
		}
	}
}

/**
 * Sort the album array based on either according to the sort key.
 * Default is to sort on the `sort_order` field.
 *
 * Returns an array with the albums in the desired sort order
 *
 * @param  array $albums array of album names
 * @param  string $sortkey the sorting scheme
 * @return array
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function sortAlbumArray($albums, $sortkey='sort_order') {
	global $_zp_loggedin;

	$hidden = array();
	$result = query("SELECT folder, sort_order, `show`, `dynamic`, `search_params` FROM " .
	prefix("albums") . " ORDER BY " . $sortkey);

	$i = 0;
	$albums_r = array_flip($albums);
	$albums_touched = array();
	while ($row = mysql_fetch_assoc($result)) {
		$folder = $row['folder'];
		if (array_key_exists($folder, $albums_r)) {
			$albums_r[$folder] = $i;
			$albums_touched[] = $folder;
			if (!$_zp_loggedin && !$row['show']) { $hidden[] = $folder; }
		}
		$i++;
	}

	$albums_untouched = array_diff($albums, $albums_touched);
	foreach($albums_untouched as $alb) {
		$albums_r[$alb] = -$i;  /* place them in the front of the list */
		$i++;
	}

	foreach($hidden as $alb) {
		unset($albums_r[$alb]);
	}

	$albums = array_flip($albums_r);
	ksort($albums);

	$albums_ordered = array();
	foreach($albums as $album) {
		$albums_ordered[] = $album;
	}

	return $albums_ordered;
}

/**
 * Emits a page error. Used for attempts to bypass password protection
 *
 */
function pageError() {
	header("HTTP/1.0 403 Forbidden");
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\"><head>	<title>403 - Forbidden</TITLE>	<META NAME=\"ROBOTS\" CONTENT=\"NOINDEX, FOLLOW\"></head>";
	echo "<BODY bgcolor=\"#ffffff\" text=\"#000000\" link=\"#0000ff\" vlink=\"#0000ff\" alink=\"#0000ff\">";
	echo "<FONT face=\"Helvitica,Arial,Sans-serif\" size=\"2\">";
	echo "<b>The page access is forbidden by the server (403)</b><br/><br/>";
	echo "</body></html>";
}

/**
 * Checks to see access is allowed to an album
 * Returns true if access is allowed.
 * There is no password dialog--you must have already had authorization via a cookie.
 *
 * @param string $albumname the album
 * @param string &$hint becomes populated with the password hint.
 * @return bool
 */
function checkAlbumPassword($albumname, &$hint) {
	global $_zp_pre_authorization;
	if (zp_loggedin()) { return true; }
	if (isset($_zp_pre_authorization[$albumname])) {
		return true;
	}
	$album = new album($_zp_gallery, $albumname);
	$hash = $album->getPassword();
	if (empty($hash)) {
		$album = $album->getParent();
		while (!is_null($album)) {
			$hash = $album->getPassword();
			$authType = "zp_album_auth_" . cookiecode($album->name);
			$saved_auth = zp_getCookie($authType);

			if (!empty($hash)) {
				if ($saved_auth != $hash) {
					$hint = $album->getPasswordHint();
					return false;
				}
			}
			$album = $album->getParent();
		}
		// revert all tlhe way to the gallery
		$hash = getOption('gallery_password');
		$authType = 'zp_gallery_auth';
		$saved_auth = zp_getCookie($authType);
		if (!empty($hash)) {
			if ($saved_auth != $hash) {
				$hint = getOption('gallery_hint');
				return false;
			}
		}
	} else {
		$authType = "zp_album_auth_" . cookiecode($album->name);
		$saved_auth = zp_getCookie($authType);
		if ($saved_auth != $hash) {
			$hint = $album->getPasswordHint();
			return false;
		}
	}
	$_zp_pre_authorization[$albumname] = true;
	return true;
}

/**
 * Adds a subalbum to the zipfile being created
 *
 * @param string $base the directory of the base album
 * @param string $offset the from $base to the subalbum
 * @param string $subalbum the subalbum file name
 */
function zipAddSubalbum($base, $offset, $subalbum) {
	global $_zp_zip_list;
	$leadin = str_replace(getAlbumFolder(), '', $base);
	if (checkAlbumPassword($leadin.$offset.$subalbum, $hint)) {
		$new_offset = $offset.$subalbum.'/';
		$rp = $base.$new_offset;
		$cwd = getcwd();
		chdir($rp);
		if ($dh = opendir($rp)) {
			$_zp_zip_list[] = "./".$new_offset.'*.*';
			while (($file = readdir($dh)) !== false) {
				if($file != '.' && $file != '..'){
					if (is_dir($rp.$file)) {
						zipAddSubalbum($base, $new_offset, $file, $zip);
					}
				}
			}
			closedir($dh);
		}
		chdir($cwd);
	}
}

/**
 * Creates a zip file of the album
 *
 * @param string $album album folder
 */
function createAlbumZip($album){
	global $_zp_zip_list;
	if (!checkAlbumPassword($album, $hint)) {
		pageError();
		exit();
	}
	$rp = realpath(getAlbumFolder() . $album) . '/';
	$p = $album . '/';
	include_once('archive.php');
	$dest = realpath(getAlbumFolder()) . '/' . urlencode($album) . ".zip";
	$persist = getOption('persistent_archive');
	if (!$persist  || !file_exists($dest)) {
		if (file_exists($dest)) unlink($dest);
		$z = new zip_file($dest);
		$z->set_options(array('basedir' => $rp, 'inmemory' => 0, 'recurse' => 0, 'storepaths' => 1));
		if ($dh = opendir($rp)) {
			$_zp_zip_list[] = '*.*';

			while (($file = readdir($dh)) !== false) {
				if($file != '.' && $file != '..'){
					if (is_dir($rp.$file)) {
						$base_a = explode("/", $album);
						unset($base_a[count($base_a)-1]);
						$base = implode('/', $base_a);
						zipAddSubalbum($rp, $base, $file, $z);
					}
				}
			}
			closedir($dh);
		}
		$z->add_files($_zp_zip_list);
		$z->create_archive();
	}
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . urlencode($album) . '.zip"');
	echo file_get_contents($dest);
	if (!$persist) { unlink($dest); }
}

/**
 * Returns the fully qualified path to the album folders
 *
 * @param string $root the base from whence the path dereives
 * @return sting
 */
$_zp_xternal_album_folder = null;
$_zp_album_folder = null;
function getAlbumFolder($root=SERVERPATH) {
	global $_zp_xternal_album_folder, $_zp_album_folder;
	if (!is_null($_zp_album_folder)) return $root . $_zp_album_folder;
	if ($_zp_xternal_album_folder != null) return $_zp_xternal_album_folder;

	if (!is_null($_zp_xternal_album_folder = getOption('external_album_folder'))) {
		if (substr($_zp_xternal_album_folder, -1) != '/') $_zp_xternal_album_folder .= '/';
		return $_zp_xternal_album_folder;
	} else {
		if (is_null($_zp_album_folder = getOption('album_folder'))) {
			$_zp_album_folder = ALBUMFOLDER;
		}
		return $root . $_zp_album_folder;
	}
}

/**
 * Returns the fully qualified "require" file name of the plugin file.
 *
 * @param  string $plugin is the name of the plugin file, typically something.php
 * @param  bool $inTheme tells where to find the plugin.
 *   true means look in the current theme
 *   false means look in the zp-core/plugins folder.
 *
 * @return string
 */
function getPlugin($plugin, $inTheme) {
	global $_zp_themeroot;
	$_zp_themeroot = WEBPATH . "/themes/$inTheme";
	if ($inTheme) {
		$pluginFile = $_zp_themeroot . '/' . $plugin;
		$pluginFile = SERVERPATH . '/' . str_replace(WEBPATH, '', $pluginFile);
	} else {
		$pluginFile = SERVERPATH . '/' . ZENFOLDER . '/plugins/' . $plugin;
	}
	if (file_exists($pluginFile)) {
		return $pluginFile;
	} else {
		return false;
	}
}

/**
 * For internal use--fetches a single tag from IPTC data
 *
 * @param string $tag the metadata tag sought
 * @return string
 */
function getIPTCTag($tag) {
	global $iptc;
	$iptcTag = $iptc[$tag];
	$r = "";
	$ct = count($iptcTag);
	for ($i=0; $i<$ct; $i++) {
		$w = $iptcTag[$i];
		if (!empty($r)) { $r .= ", "; }
		$r .= $w;
	}
	return $r;
}

/**
 * For internal use--fetches the IPTC array for a single tag.
 *
 * @param string $tag the metadata tag sought
 * @return array
 */
function getIPTCTagArray($tag) {
	global $iptc;
	if (array_key_exists($tag, $iptc)) {
		return $iptc[$tag];
	}
	return NULL;
}

/**
 * Parces IPTC data and returns those tags zenphoto is interested in
 * folds multiple tags into single zp data items based on precidence.
 *
 * @param string $imageName the name of the image
 * @return array
 */
function getImageMetadata($imageName) {
	global $iptc;

	$result = array();
	getimagesize($imageName, $imageInfo);
	if (is_array($imageInfo)) {
		/* EXIF date */
		$exifraw = read_exif_data_raw($imageName, false);
		$subIFD = $exifraw['SubIFD'];
		$date = $subIFD['DateTime'];
		if (empty($date)) {
			$date = $subIFD['DateTimeOriginal'];
		}
		if (empty($date)) {
			$date = $subIFD['DateTimeDigitized'];
		}
		if (!empty($date)) {
			$result['date'] = $date;
		}

		/* check IPTC data */
		$iptc = iptcparse($imageInfo["APP13"]);
		if ($iptc) {
			/* iptc date */
			$date = getIPTCTag('2#055');
			if (!empty($date)) {
				$result['date'] = substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
			}
			/* iptc title */
			$title = getIPTCTag('2#005');   /* Option Name */
			if (empty($title)) {
				$title = getIPTCTag('2#105'); /* Headline */
			}
			if (!empty($title)) {
				$result['title'] = utf8::convert($title, 'ISO-8859-1');
			}

			/* iptc description */
			$caption= getIPTCTag('2#120');
			if (!empty($caption)) {
				$result['desc'] = utf8::convert($caption, 'ISO-8859-1');
			}

			/* iptc location, state, country */
			$location = getIPTCTag('2#092');
			if (!empty($location)) {
				$result['location'] = utf8::convert($location, 'ISO-8859-1');
			}
			$city = getIPTCTag('2#090');
			if (!empty($city)) {
				$result['city'] = utf8::convert($city, 'ISO-8859-1');
			}
			$state = getIPTCTag('2#095');
			if (!empty($state)) {
				$result['state'] = utf8::convert($state, 'ISO-8859-1');
			}
			$country = getIPTCTag('2#101');
			if (!empty($country)) {
				$result['country'] = utf8::convert($country, 'ISO-8859-1');
			}
			/* iptc credit */
			$credit= getIPTCTag('2#080'); /* by-line */
			if (empty($credit)) {
				$credit = getIPTCTag('2#110'); /* credit */
			}
			if (empty($credit)) {
				$credit = getIPTCTag('2#115'); /* source */
			}
			if (!empty($credit)) {
				$result['credit'] = utf8::convert($credit, 'ISO-8859-1');
			}

			/* iptc copyright */
			$copyright= getIPTCTag('2#116');
			if (!empty($copyright)) {
				$result['copyright'] = utf8::convert($copyright, 'ISO-8859-1');
			}

			/* iptc keywords (tags) */
			$keywords = getIPTCTagArray('2#025');
			if (is_array($keywords)) {
				$taglist = array();
				foreach($keywords as $keyword) {
					$taglist[] = utf8::convert($keyword, 'ISO-8859-1');
				}
				$result['tags'] = $taglist;
			}
		}
	}

	return $result;
}

/**
 * Unzips an image archive
 *
 * @param file $file the archive
 * @param string $dir where the images go
 */
function unzip($file, $dir) { //check if zziplib is installed
	if(function_exists('zip_open()')) {
		$zip = zip_open($file);
		if ($zip) {
			while ($zip_entry = zip_read($zip)) { // Skip non-images in the zip file.
				if (!is_valid_image(zip_entry_name($zip_entry))) continue;
				if (zip_entry_open($zip, $zip_entry, "r")) {
					$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					$path_file = str_replace("/",DIRECTORY_SEPARATOR, $dir . '/' . zip_entry_name($zip_entry));
					$fp = fopen($path_file, "w");
					fwrite($fp, $buf);
					fclose($fp);
					zip_entry_close($zip_entry);
				}
			}
			zip_close($zip);
		}
	} else { // Use Zlib http://www.phpconcept.net/pclzip/index.en.php
		require_once('lib-pclzip.php');
		$zip = new PclZip($file);
		if ($zip->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH) == 0) {
			die("Error : ".$zip->errorInfo(true));
		}
	}
}
/**
 * Checks to see if a URL is valid
 *
 * @param string $url the URL being checked
 * @return bool
 */
function isValidURL($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

/**
 * Generic comment adding routine. Called by album objects or image objects
 * to add comments.
 *
 * Returns a code for the success of the comment add:
 *    0: Bad entry
 *    1: Marked for moderation
 *    2: Successfully posted
 *
 * @param string $name Comment author name
 * @param string $email Comment author email
 * @param string $website Comment author website
 * @param string $comment body of the comment
 * @param string $code Captcha code entered
 * @param string $code_ok Captcha md5 expected
 * @param string $type 'albums' if it is an album or 'images' if it is an image comment
 * @param object $receiver the object (image or album) to which to post the comment
 * @param string $ip the IP address of the comment poster
 * @param bool $private set to true if the comment is for the admin only
 * @param bool $anon set to true if the poster wishes to remain anonymous 
 * @return int
 */
function postComment($name, $email, $website, $comment, $code, $code_ok, $receiver, $ip, $private, $anon) {
	if (strtolower(get_class($receiver)) == 'image') {
		$type = 'images';
	} else {
		$type = 'albums';
	}
	$receiver->getComments();
	$name = trim($name);
	$email = trim($email);
	$website = trim($website);
	$code = md5(trim($code));
	$code_ok = trim($code_ok);

	// Let the comment have trailing line breaks and space? Nah...
	// Also (in)validate HTML here, and in $name.
	$comment = trim($comment);
	if (getOption('comment_email_required') && (empty($email) || !is_valid_email_zp($email))) { return -2; }
	if (getOption('comment_name_required') && empty($name)) { return -3; }
	if (getOption('comment_web_required') && (empty($website) || !isValidURL($website))) { return -4; }
	if (getOption('Use_Captcha')) {
		if ($code != $code_ok) { return -5; }
	}
	if (empty($comment)) {
		return -6;
	}

	if (!empty($website) && substr($website, 0, 7) != "http://") {
		$website = "http://" . $website;
	}

	$goodMessage = 2;
	$gallery = new gallery();
	if (!(false === ($requirePath = getPlugin('spamfilters/'.getOption('spam_filter').".php", false)))) {
		require_once($requirePath);
		$spamfilter = new SpamFilter();
		$goodMessage = $spamfilter->filterMessage($name, $email, $website, $comment, $type=='images'?$receiver->getFullImage():NULL, $ip);
	}

	if ($goodMessage) {
		if ($goodMessage == 1) {
			$moderate = 1;
		} else {
			$moderate = 0;
		}
		if ($private) $private = 1; else $private = 0;
		if ($anon) $anon = 1; else $anon = 0;
		
		// Update the database entry with the new comment
		query("INSERT INTO " . prefix("comments") . " (`ownerid`, `name`, `email`, `website`, `comment`, `inmoderation`, `date`, `type`, `ip`, `private`, `anon`) VALUES " .
						" ('" . $receiver->id .
						"', '" . escape($name) . 
						"', '" . escape($email) . 
						"', '" . escape($website) . 
						"', '" . escape($comment) . 
						"', '" . $moderate . 
						"', NOW()" .
						", '$type'" .
						", '$ip'" .
						", '$private'" .
						", '$anon')");

		if ($moderate) {
			$action = "placed in moderation";
		} else {
			//  add to comments array and notify the admin user

			$newcomment = array();
			$newcomment['name'] = $name;
			$newcomment['email'] = $email;
			$newcomment['website'] = $website;
			$newcomment['comment'] = $comment;
			$newcomment['date'] = time();
			$receiver->comments[] = $newcomment;
			$action = "posted";
		}

		if ($type == 'images') {
			$on = $receiver->getAlbumName() . " about " . $receiver->getTitle();
			$url = "album=" . urlencode($receiver->album->name) . "&image=" . urlencode($receiver->filename);
			$album = $receiver->getAlbum();
			$ur_album = getUrAlbum($album);
		} else {
			$on = $receiver->name;
			$url = "album=" . urlencode($receiver->name);
			$ur_album = getUrAlbum($receiver);
		}
		if (getOption('email_new_comments')) {
			$message = gettext("A comment has been $action in your album")." $on\n" .
 										"\n" .
 										"Author: " . $name . "\n" .
 										"Email: " . $email . "\n" .
 										"Website: " . $website . "\n" .
 										"Comment:\n" . $comment . "\n" .
 										"\n" .
 										"You can view all comments about this image here:\n" .
 										"http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/index.php?$url\n" .
 										"\n" .
 										"You can edit the comment here:\n" .
 										"http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/" . ZENFOLDER . "/admin-comments.php\n";
			$emails = array();
			$admin_users = getAdministrators();
			foreach ($admin_users as $admin) {  // mail anyone else with full rights
				if (($admin['rights'] & ADMIN_RIGHTS) && ($admin['rights'] & COMMENT_RIGHTS) && !empty($admin['email'])) {
					$emails[] = $admin['email'];
					unset($admin_users[$admin['id']]);
				}
			}
			$id = $ur_album->getAlbumID();
			$sql = "SELECT `adminid` FROM ".prefix('admintoalbum')." WHERE `albumid`=$id";
			$result = query_full_array($sql);
			foreach ($result as $anadmin) {
				$admin = $admin_users[$anadmin['adminid']];
				if (!empty($admin['email'])) {
					$emails[] = $admin['email'];
				}
			}
			zp_mail("[" . getOption('gallery_title') . "] Comment posted on $on", $message, "", $emails);
		}

	}
	return $goodMessage;
}

/**
 * Write output to the debug log
 * Use this for debugging when echo statements would come before headers are sent
 * or would create havoc in the HTML.
 * Creates (or adds to) a file named debug_log.txt which is located in the zenphoto core folder
 *
 * @param string $message the debug information
 * @param bool $reset set to true to reset the log to zero before writing the message
 */
function debugLog($message, $reset=false) {
	if ($reset) { $mode = 'w'; } else { $mode = 'a'; }
	$f = fopen(SERVERPATH . '/' . ZENFOLDER . '/debug_log.txt', $mode);
	fwrite($f, $message . "\n");
	fclose($f);
}

/**
 * "print_r" equivalent for the debug log
 *
 * @param array $source
 */
function debugLogArray($name, $source) {
	$msg = "Array $name( ";
	if (is_array($source)) {
		if (count($source) > 0) {
			foreach ($source as $key => $val) {
				if (strlen($msg) > 72) {
					debugLog($msg);
					$msg = '';
				}
				$msg .= $key . " => " . $val . ", ";
			}
			$msg = substr($msg, 0, strrpos($msg, ',')) . " )";
		} else {
			$msg .= ")";
		}
		debugLog($msg);
	} else {
		debugLog($msg . ")");
	}
}

/**
 * Logs the calling stack
 *
 * @ param string $message Message to prefix the backtrace
 */
function debugLogBacktrace($message) {
	debugLog("Backtrace: $message");
	// Get a backtrace.
	$bt = debug_backtrace();
	array_shift($bt); // Get rid of debug_backtrace in the backtrace.
	$prefix = '';
	$line = '';
	$caller = '';
	foreach($bt as $b) {
		$caller = (isset($b['class']) ? $b['class'] : '')	. (isset($b['type']) ? $b['type'] : '')	. $b['function'];
		if (!empty($line)) { // skip first output to match up functions with line where they are used.

			$msg = $prefix . ' from ';
			debugLog($msg.$caller.' ('.$line.')');
			$prefix .= '  ';
		} else {
			debugLog($caller.' called');
		}
		$line = basename($b['file'])	. ' [' . $b['line'] . "]";
	}
	if (!empty($line)) {
		debugLog($prefix.' from '.$line);
	}
}

/**
 * Provide an alternative to glob if the ISP has disabled it
 * To enable the alternative, change the SAFE_GLOB define at the front to functions.php
 *
 * @param string $pattern the 'pattern' for matching files
 * @param bit $flags glob 'flags'
 */
function safe_glob($pattern, $flags=0) {
	if (!SAFE_GLOB) { 
		$glob = glob($pattern, $flags); 
		if (is_array($glob)) {
			return $glob;
		}
		return Array();
	}
	$split=explode('/',$pattern);
	$match=array_pop($split);
	$path=implode('/',$split);
	if (empty($path)) { $path = '.'; };

	if (($dir=opendir($path))!==false) {
		$glob=array();
		while(($file=readdir($dir))!==false) {
			if (fnmatch($match,$file)) {
				if ((is_dir("$path/$file"))||(!($flags&GLOB_ONLYDIR))) {
					if ($flags&GLOB_MARK) $file.='/';
					$glob[]=$file;
				}
			}
		}
		closedir($dir);
		if (!($flags&GLOB_NOSORT)) sort($glob);
		return $glob;
	} else {
		return false;
	}
}
if (!function_exists('fnmatch')) {
	/**
	 * pattern match function in case it is not included in PHP
	 *
	 * @param string $pattern pattern
	 * @param string $string haystack
	 * @return bool
	 */
	function fnmatch($pattern, $string) {
		return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|'), array('*' => '.*', '?' => '.?')) . '$/i', $string);
	}
}

/**
 * Returns the value of a cookie from either the cookies or from $_SESSION[]
 *
 * @param string $name the name of the cookie
 */
function zp_getCookie($name) {
	if (isset($_SESSION[$name])) { return $_SESSION[$name]; }
	if (isset($_COOKIE[$name])) { return $_COOKIE[$name]; }
	return false;
}
/**
 * Sets a cookie both in the browser cookies and in $_SESSION[]
 *
 * @param string $name
 * @param string $value
 * @param timestamp $time
 * @param string $path
 */
function zp_setCookie($name, $value, $time=0, $path='/') {
	if (!getOption('album_session')) {
		setcookie($name, $value, $time, $path);
	}
	if ($time < 0) {
		unset($_SESSION[$name]);
		unset($_COOKIE[$name]);
	} else {
		$_SESSION[$name] = $value;
		$_COOKIE[$name] = $value;
	}	
}

//admin user handling

// TODO: 1.2 change this define to 2
define('NO_RIGHTS', 2);
if (NO_RIGHTS == 2) {
	define('MAIN_RIGHTS', 4);
	define('UPLOAD_RIGHTS', 16);
	define('COMMENT_RIGHTS', 64);
	define('EDIT_RIGHTS', 256);
	define('THEMES_RIGHTS', 1024);
	define('OPTIONS_RIGHTS', 8192);
	define('ADMIN_RIGHTS', 65536);
} else {
	define('MAIN_RIGHTS', 1);
	define('UPLOAD_RIGHTS', 2);
	define('COMMENT_RIGHTS', 4);
	define('EDIT_RIGHTS', 8);
	define('THEMES_RIGHTS', 16);
	define('OPTIONS_RIGHTS', 32);
	define('ADMIN_RIGHTS', 16384);
}
define('ALL_RIGHTS', 07777777777);

$_zp_current_admin = null;
$_zp_admin_users = null;

/**
 * Saves an admin user's settings
 *
 * @param string $user The username of the admin
 * @param string $pass The password associated with the user name (md5)
 * @param string $name The display name of the admin
 * @param string $email The email address of the admin
 * @param bit $rights The administrating rites for the admin
 * @param array $albums an array of albums that the admin can access. (If empty, access is to all albums)
 */
function saveAdmin($user, $pass, $name, $email, $rights, $albums) {

	if (DEBUG_LOGIN) { debugLog("saveAdmin($user, $pass, $name, $email, $rights, $albums)"); }
		
	$sql = "SELECT `name`, `id` FROM " . prefix('administrators') . " WHERE `user` = '$user'";
	$result = query_single_row($sql);
	if ($result) {
		$id = $result['id'];
		if (!is_null($pass)) {
			$password = "' ,`password`='" . escape($pass);
		}
		if (!is_null($rights)) {
			$rightsset = "', `rights`='" . escape($rights);
		}
		$sql = "UPDATE " . prefix('administrators') . "SET `name`='" . escape($name) . $password .
 					"', `email`='" . escape($email) . $rightsset . "' WHERE `id`='" . $id ."'";
		$result = query($sql);
		
		if (DEBUG_LOGIN) { debugLog("updating[$id]:$result");	}	
		
	} else {
		if (is_null($pass)) $pass = md5($user);
		$sql = "INSERT INTO " . prefix('administrators') . " (user, password, name, email, rights) VALUES ('" .
		escape($user) . "','" . escape($pass) . "','" . escape($name) . "','" . escape($email) . "','" . $rights . "')";
		$result = query($sql);
		$sql = "SELECT `name`, `id` FROM " . prefix('administrators') . " WHERE `user` = '$user'";
		$result = query_single_row($sql);
		$id = $result['id'];
		
		if (DEBUG_LOGIN) { debugLog("inserting[$id]:$result"); }	
		
	}
	$sql = "DELETE FROM ".prefix('admintoalbum')." WHERE `adminid`=$id";
	$result = query($sql);
	$gallery = new Gallery();
	if (is_array($albums)) {
		foreach ($albums as $albumname) {
			$album = new Album($gallery, $albumname);
			$albumid = $album->getAlbumID();
			$sql = "INSERT INTO ".prefix('admintoalbum')." (adminid, albumid) VALUES ($id, $albumid)";
			$result = query($sql);
		}
	}
}

/**
 * Returns an array of admin users, indexed by the userid
 *
 * The array contains the md5 password, user's name, email, and admin priviledges
 *
 * @return array
 */
function getAdministrators() {
	global $_zp_admin_users;
	if (is_null($_zp_admin_users)) {
		$_zp_admin_users = array();
		$sql = "SELECT `user`, `password`, `name`, `email`, `rights`, `id` FROM ".prefix('administrators')."ORDER BY `rights` DESC, `id`";
		$admins = query_full_array($sql, true);
		if ($admins !== false) {
			foreach($admins as $user) {
				if (NO_RIGHTS == 2) {
					if (($rights = $user['rights']) & 1) { // old compressed rights
						$newrights = MAIN_RIGHTS;
						if ($rights & 2) $newrights = $newrights | UPLOAD_RIGHTS;
						if ($rights & 4) $newrights = $newrights | COMMENT_RIGHTS;
						if ($rights & 8) $newrights = $newrights | EDIT_RIGHTS;
						if ($rights & 16) $newrights = $newrights | THEMES_RIGHTS;
						if ($rights & 32) $newrights = $newrights | OPTIONS_RIGHTS;
						if ($rights & 16384) $newrights = $newrights | ADMIN_RIGHTS;
						$user['rights'] = $newrights;
					}
				} else {
					if (!(($rights = $user['rights']) & 1)) { // new expanded rights
						$newrights = MAIN_RIGHTS;
						if ($rights & 16) $newrights = $newrights | UPLOAD_RIGHTS;
						if ($rights & 64) $newrights = $newrights | COMMENT_RIGHTS;
						if ($rights & 256) $newrights = $newrights | EDIT_RIGHTS;
						if ($rights & 1024) $newrights = $newrights | THEMES_RIGHTS;
						if ($rights & 8192) $newrights = $newrights | OPTIONS_RIGHTS;
						if ($rights & 65536) $newrights = $newrights | ADMIN_RIGHTS;
						$user['rights'] = $newrights;
					}
				}
				$_zp_admin_users[$user['id']] = array('user' => $user['user'], 'pass' => $user['password'],
 												'name' => $user['name'], 'email' => $user['email'], 'rights' => $user['rights'],
 												'id' => $user['id']);
			}
		}
	}
	return $_zp_admin_users;
}

/**
 * Retuns the administration rights of a saved authorization code
 *
 * @param string $authCode the md5 code to check
 *
 * @return bit
 */
function checkAuthorization($authCode) {
	
	if (DEBUG_LOGIN) { debugLog("checkAuthorization($authCode)");	}
	
	global $_zp_current_admin;
	$admins = getAdministrators();
	$reset_date = getOption('admin_reset_date');
	if ((count($admins) == 0) || empty($reset_date)) {
		if ((count($admins) != 0)) setOption('admin_reset_date', 1);  // in case there is no save done in admin
		$_zp_current_admin = null;
		
		if (DEBUG_LOGIN) { debugLog("no admin or reset request"); }		
		
		return ADMIN_RIGHTS; //no admins or reset request
	}
	if (empty($authCode)) return 0; //  so we don't "match" with an empty password
	$i = 0;
	foreach($admins as $user) {
		
	if (DEBUG_LOGIN) { debugLogArray("checking",$user);	}	
		
		if ($user['pass'] == $authCode) {
			$_zp_current_admin = $user;
			$result = $user['rights']; 
			if ($i == 0) { // the first admin is the master.
				$result = $result | ADMIN_RIGHTS; 
			} 
			
			if (DEBUG_LOGIN) { debugLog("match");	}		
			
			return $result;
		}
		$i++;
	}
	$_zp_current_admin = null;
	return 0; // no rights
}

/**
 * Checks a logon user/password against the list of admins
 *
 * Returns true if there is a match
 *
 * @param string $user
 * @param string $pass
 * @return bool
 */
function checkLogon($user, $pass) {
	$admins = getAdministrators();
	foreach ($admins as $admin) {
		if ($admin['user'] == $user) {
			$md5 = md5($user.$pass);
			if ($admin['pass'] == $md5) {
				return checkAuthorization($md5);
			}
		}
	}
	return false;
}

/**
 * Returns the email addresses of the Admin with ADMIN_USERS rights
 *
 * @param bit $rights what kind of admins to retrieve
 * @return array
 */
function getAdminEmail($rights=ADMIN_RIGHTS) {
	$emails = array();
	$admins = getAdministrators();
	$user = array_shift($admins);
	if (!empty($user['email'])) {
		$emails[] = $user['email'];
	}
	foreach ($admins as $user) {
		if (($user['rights'] & $rights)  && !empty($user['email'])) {
			$emails[] = $user['email'];
		}
	}
	return $emails;
}

/**
 * Populates and returns the $_zp_admin_album_list array
 *
 * @return array
 */
function getManagedAlbumList() {
	global $_zp_admin_album_list, $_zp_current_admin;
	$_zp_admin_album_list = array();
	$sql = "SELECT ".prefix('albums').".`folder` FROM ".prefix('albums').", ".
	prefix('admintoalbum')." WHERE ".prefix('admintoalbum').".adminid=".
	$_zp_current_admin['id']." AND ".prefix('albums').".id=".prefix('admintoalbum').".albumid";
	$albums = query_full_array($sql);
	foreach($albums as $album) {
		$_zp_admin_album_list[] =$album['folder'];
	}
	return $_zp_admin_album_list;
}

/**
 * Checks to see if the loggedin Admin has rights to the album
 *
 * @param string $albumfolder the album to be checked
 */
function isMyAlbum($albumfolder, $action) {
	global $_zp_loggedin, $_zp_admin_album_list, $_zp_current_admin;
	if ($_zp_loggedin & ADMIN_RIGHTS) { return true; }
	if (empty($albumfolder)) { return false; }
	if ($_zp_loggedin & $action) {
		if (is_null($_zp_admin_album_list)) {
			getManagedAlbumList();
		}
		if (count($_zp_admin_album_list) == 0) { return false; }
		foreach ($_zp_admin_album_list as $key => $adminalbum) { // see if it is one of the managed folders or a subfolder there of
			if (substr($albumfolder, 0, strlen($adminalbum)) == $adminalbum) { return true; }
		}
		return false;
	} else {
		return false;
	}
}
/**
 * Returns  an array of album ids whose parent is the folder
 * @param string $albumfolder folder name if you want a album different >>from the current album
 * @return array
 */
function getAllSubAlbumIDs($albumfolder='') {
	global $_zp_current_album;
	if (empty($albumfolder)) {
		if (isset($_zp_current_album)) {
			$albumfolder = $_zp_current_album->getFolder();
		} else {
			return null;
		}
	}
	$query = "SELECT `id`,`folder`, `show` FROM " . prefix('albums') . " WHERE `folder` LIKE '" . mysql_real_escape_string($albumfolder) . "%'";
	$subIDs = query_full_array($query);
	return $subIDs;
}

/**
 * recovers search parameters from stored cookie, clears the cookie
 *
 * @param string $album Name of the album
 * @param string $image Name of the image
 */
function handleSearchParms($album='', $image='') {
	global $_zp_current_search, $_zp_current_context;
	$cookiepath = WEBPATH;
	if (WEBPATH == '') { $cookiepath = '/'; }
	if (empty($album)) { // clear the cookie
		zp_setcookie("zenphoto_image_search_params", "", time()-368000, $cookiepath);
		return;
	}
	$params = zp_getCookie('zenphoto_image_search_params');
	if (!empty($params)) {
		$_zp_current_search = new SearchEngine();
		$_zp_current_search->setSearchParams($params);
		// check to see if we are still "in the search context"
		if (!empty($image)) {
			if ($_zp_current_search->getImageIndex($album, $image) === false) {
				$_zp_current_search = null;
				return;
			}
		} else {
			if ($_zp_current_search->getAlbumIndex($album) === false) {
				$_zp_current_search = null;
				return;
			}
		}
		set_context($_zp_current_context | ZP_SEARCH_LINKED);
	}
}

/**
 * Returns the theme folder
 * If there is an album theme, loads the theme options.
 * 
 * @return string
 */
function setupTheme() {
	global $_zp_gallery_albums_per_page, $_zp_gallery, $_zp_current_album,
					$_zp_current_search, $_zp_options, $_zp_themeroot, $themepath;
	$albumtheme = '';
	if (in_context(ZP_SEARCH_LINKED)) {
		$name = $_zp_current_search->dynalbumname;
		if (!empty($name)) {
			$album = new Album($_zp_gallery, $name);
		} else {
			$album = NULL;
		}
	} else {
		$album = $_zp_current_album;
	}
	$theme = $_zp_gallery->getCurrentTheme();
	if (!is_null($album)) {
		$parent = getUrAlbum($album);
		$albumtheme = $parent->getAlbumTheme();
	}
	if (!(false === ($requirePath = getPlugin('themeoptions.php', $theme)))) {
		require_once($requirePath);
		$optionHandler = new ThemeOptions(); /* prime the theme options */
	}
	$_zp_gallery_albums_per_page = max(1, getOption('albums_per_page'));
	if (!empty($albumtheme)) {
		$theme = $albumtheme;
		if (ALBUM_OPTIONS_TABLE) {
			$tbl = prefix('options').' WHERE `ownerid`='.$parent->id;
		} else {
			$tbl = prefix(getOptionTableName($parent->name));
		}
		//load the album theme options
		$sql = "SELECT `name`, `value` FROM ".$tbl;
		$optionlist = query_full_array($sql, true);
		if ($optionlist !== false) {
			foreach($optionlist as $option) {
				$_zp_options[$option['name']] = $option['value'];
			}
		}
	}
	$_zp_themeroot = WEBPATH . "/$themepath/$theme";
	return $theme;
}

/**
 * Returns true if the file has the dynamic album suffix
 *
 * @param string $path
 * @return bool
 */
function hasDyanmicAlbumSuffix($path) {
	return strtolower(substr(strrchr($path, "."), 1)) == 'alb';
}

/**
 * Count Binary Ones
 *
 * Returns the number of bits set in $bits
 *
 * @param bit $bits the bit mask to count
 * @param int $limit the upper limit on the numer of bits;
 * @return int
 */
function cbone($bits, $limit) {
	$c = 0;
	for ($i=0; $i<$limit; $i++) {
		$x = pow(2, $i);
		if ($bits & $x) $c++;
	}
	return $c;
}

/**
 * generates a simple captcha for comments
 *
 * Thanks to gregb34 who posted the original code
 *
 * Returns the captcha code string and image URL (via the $image parameter).
 *
 * @return string;
 */
function generateCaptcha(&$image) {
	require_once('lib-encryption.php');

	$lettre='abcdefghijkmnpqrstuvwxyz';
	$chiffre='23456789';

	$string = '';
	for ($i=0; $i<=4; $i++) {
		if (($i > 0) && rand(0, 4) > 2) {
			$string .= $chiffre[rand(0,7)];
		} else {
			$string .= $lettre[rand(0,23)];
		}
	}
	$admins = getAdministrators();
	$admin = array_shift($admins);
	$key = $admin['pass'];
	$cypher = urlencode(rc4($key, $string));

	$code=md5($string);
	$image = WEBPATH . '/' . ZENFOLDER . "/c.php?i=$cypher";

	return $code;
}

/**
 * Allows plugins to add to the scripts output by zenJavascript()
 *
 * @param string $script the text to be added.
 */
function addPluginScript($script) {
	global $_zp_plugin_scripts;
	$_zp_plugin_scripts[] = $script;
}

$_zp_use_tag_table = 0;
/**
 * Returns true if we have converted to the database table for tags
 *
 * @return bool
 */
function useTagTable() {
	global $_zp_use_tag_table;
	if ($_zp_use_tag_table > 0) {
		return true;
	} else if ($_zp_use_tag_table < 0) {
		return false;
	}
	$result = query_full_array("SHOW COLUMNS FROM ".prefix('images').' LIKE "%tags%"');	
	foreach ($result as $row) {
		if ($row['Field'] == 'tags') {
			$_zp_use_tag_table = -1;
			return false;
		}
	}
	$_zp_use_tag_table = 1;
	return true;
}

/**
 * Trims the tag values and eliminates duplicates.
 * Tags are case insensitive so only the first of 'Tag' and 'tag' will be preserved
 *
 * Returns the filtered tag array.
 * 
 * @param array $tags
 * @return array
 */
function filterTags($tags) {
	$lc_tags = array();
	$filtered_tags = array();
	foreach ($tags as $key=>$tag) {
		$tag = trim($tag);
		if (!empty($tag)) {
			$lc_tag = strtolower($tag);
			if (!in_array($lc_tag, $lc_tags)) {
				$lc_tags[] = $lc_tag;
				$filtered_tags[] = $tag;
			}
		}
	}
	return $filtered_tags;
}

$_zp_all_tags = null;
/**
 * Grabs the entire galleries tags
 * Returns an array with all the tags found (there may be duplicates)
 * 
 * Should be used internally only, works only on "old" tag format.
 *
 * @return array
 * @since 1.1
 */
function getAllTagsStrings() {
	global $_zp_all_tags;
	if (!is_null($_zp_all_tags)) { return $_zp_all_tags; }
	$result = query_full_array("SELECT `tags` FROM ". prefix('images'));
	foreach($result as $row){
		$alltags = $alltags.$row['tags'].",";  // add comma after the last entry so that we can explode to array later
	}
	$result = query_full_array("SELECT `tags` FROM ". prefix('albums'));
	foreach($result as $row){
		$alltags = $alltags.$row['tags'].",";  // add comma after the last entry so that we can explode to array later
	}
	$alltags = explode(",",$alltags);
	$_zp_all_tags = array();
	foreach ($alltags as $tag) {
		$clean = trim($tag);
		if (!empty($clean)) {
			$_zp_all_tags[] = $clean;
		}
	}
	return $_zp_all_tags;
}

$_zp_unique_tags = NULL;
/**
 * Returns an array of unique tag names
 *
 * @return unknown
 */
function getAllTagsUnique() {
	global $_zp_unique_tags;
	if (!is_null($_zp_unique_tags)) return $_zp_unique_tags;  // cache them.
	if (useTagTable()) {
		$sql = "SELECT `name` FROM ".prefix('tags');
		$result = query_full_array($sql);
		if (is_array($result)) {
			$_zp_unique_tags = array();
			foreach ($result as $row) {
				$_zp_unique_tags[] = $row['name'];
			}
			return $_zp_unique_tags;
		} else {
			return array();
		}
	} else {
		$taglist = getAllTagsStrings();
		$seen = array();
		foreach ($taglist as $key=>$tag) {
			$tagLC = utf8::strtolower($tag);
			if (in_array($tagLC, $seen)) {
				unset($taglist[$key]);
			} else {
				$seen[] = $tagLC;
			}
		}
		$_zp_unique_tags = array_merge($taglist);
		return $_zp_unique_tags;
	}
}

$_zp_count_tags = NULL;
/**
 * Returns an array indexed by 'tag' with the element value the count of the tag
 *
 * @return array
 */
function getAllTagsCount() {
	global $_zp_count_tags;
	if (!is_null($_zp_count_tags)) return $_zp_count_tags;
	if (useTagTable()) {
		$_zp_count_tags = array();
		$sql = "SELECT `name`, `id` from ".prefix('tags');
		$tagresult = query_full_array($sql);
		if (is_array($tagresult)) {
			foreach ($tagresult as $row) {
				$sql = "SELECT COUNT(*) AS row_count FROM ".prefix('obj_to_tag')." WHERE `tagid`='".$row['id']."'";
				$countresult = query_single_row($sql);
				$_zp_count_tags[$row['name']]	= $countresult['row_count'];
			}
		}
		return $_zp_count_tags;
	} else {
		$alltags = getAllTagsStrings();
		$list = array();
		$tagsLC = array();
		foreach ($alltags as $tag) {
			$tagLC = utf8::strtolower($tag);
			$list[$tagLC] = $tag;
			$tagsLC[] = $tagLC;
		}
		$tagcounts = array_count_values($tagsLC);
		$_zp_count_tags = array();
		foreach ($tagcounts as $key=>$count) {
			$_zp_count_tags[$list[$key]] = $count;
		}
		return $_zp_count_tags;
	}
}

/**
 * Stores tags for an album/image
 *
 * @param array $tags the tag values
 * @param int $id the record id of the album/image
 * @param string $tbl 'albums' or 'images'
 */
function storeTags($tags, $id, $tbl) {
	$tags = filterTags($tags);
	$tagsLC = array();
	foreach ($tags as $tag) {
		$tagsLC[$tag] = utf8::strtolower($tag);
	}
	$sql = "SELECT `id`, `tagid` from ".prefix('obj_to_tag')." WHERE `objectid`='".$id."' AND `type`='".$tbl."'";
	$result = query_full_array($sql);
	$existing = array();
	if (is_array($result)) {
		foreach ($result as $row) {
			$dbtag = query_single_row("SELECT `name` FROM ".prefix('tags')." WHERE `id`='".$row['tagid']."'");
			$existingLC = utf8::strtolower($dbtag['name']);
			if (in_array($existingLC, $tagsLC)) { // tag already set no action needed
				$existing[] = $existingLC;
			} else { // tag no longer set, remove it
				query("DELETE FROM ".prefix('obj_to_tag')." WHERE `id`='".$row['id']."'");
			}
		}
	}
	$tags = array_flip(array_diff($tagsLC, $existing)); // new tags for the object
	foreach ($tags as $tag) {
		$dbtag = query_single_row("SELECT `id` FROM ".prefix('tags')." WHERE `name`='".escape($tag)."'");
		if (!is_array($dbtag)) { // tag does not exist
			query("INSERT INTO " . prefix('tags') . " (name) VALUES ('" . escape($tag) . "')");
			$dbtag = query_single_row("SELECT `id` FROM ".prefix('tags')." WHERE `name`='".escape($tag)."'");
		}
		query("INSERT INTO ".prefix('obj_to_tag'). "(`objectid`, `tagid`, `type`) VALUES (".$id.",".$dbtag['id'].",'".$tbl."')");
	}
}

/**
 * Retrieves the tags for an album/image
 * Returns them in an array
 *
 * @param int $id the record id of the album/image
 * @param string $tbl 'albums' or 'images'
 * @return unknown
 */
function readTags($id, $tbl) {
	$tags = array();
	$result = query_full_array("SELECT `tagid` FROM ".prefix('obj_to_tag')." WHERE `type`='".$tbl."' AND `objectid`='".$id."'");
	if (is_array($result)) {
		foreach ($result as $row) {
			$dbtag = query_single_row("SELECT `name` FROM".prefix('tags')." WHERE `id`='".$row['tagid']."'");
			$tags[] = $dbtag['name'];
		}
	}
	return $tags;
}

/**
 * Creates the body of a select list
 *
 * @param array $currentValue list of items to be flagged as checked
 * @param array $list the elements of the select list
 */
function generateListFromArray($currentValue, $list) {
	$localize = !is_numeric(array_shift(array_keys($list)));
	if ($localize) {
		$list = array_flip($list);
		natcasesort($list);
		$list = array_flip($list);
	} else {
		natcasesort($list);
	}
	foreach($list as $key=>$item) {
		echo '<option value="' . $item . '"';
		$inx = array_search($item, $currentValue);
		if ($inx !== false) {
			echo ' selected="selected"';
		}
		if ($localize) $display = $key; else $display = $item;
		echo '>' . $display . "</option>"."\n";
	}
}

function generateListFromFiles($currentValue, $root, $suffix) {
	$curdir = getcwd();
	chdir($root);
	$filelist = safe_glob('*'.$suffix);
	$list = array();
	foreach($filelist as $file) {
		$list[] = str_replace($suffix, '', $file);
	}
	generateListFromArray(array($currentValue), $list);
	chdir($curdir);
}

/**
 * General link printing function
 * @param string $url The link URL
 * @param string $text The text to go with the link
 * @param string $title Text for the title tag
 * @param string $class optional class
 * @param string $id optional id
 */
function printLink($url, $text, $title=NULL, $class=NULL, $id=NULL) {
	echo "<a href=\"" . htmlspecialchars($url) . "\"" .
	(($title) ? " title=\"" . htmlspecialchars($title, ENT_QUOTES) . "\"" : "") .
	(($class) ? " class=\"$class\"" : "") .
	(($id) ? " id=\"$id\"" : "") . ">" .
	$text . "</a>";
}
?>
