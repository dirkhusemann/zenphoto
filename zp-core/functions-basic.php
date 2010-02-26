<?php
/**
 * basic functions used by zenphoto i.php
 * Keep this file to the minimum to allow the largest available memory for processing images!
 * Headers not sent yet!
 * @package functions
 *
 */

// force UTF-8 Ø
require_once(dirname(__FILE__).'/folder-definitions.php');
include(dirname(__FILE__).'/version.php'); // Include the version info.
if(!function_exists("gettext")) {
	require_once(dirname(__FILE__).'/lib-gettext/gettext.inc');
}


define('FILESYSTEM_CHARSET', 'ISO-8859-1');
define('DEBUG_LOGIN', false); // set to true to log admin saves and login attempts
define('DEBUG_ERROR', !defined('RELEASE')); // set to true to supplies the calling sequence with zp_error messages
define('DEBUG_IMAGE', false); // set to true to log image processing debug information.
define('DEBUG_404', false); // set to true to log 404 error processing debug information.
define('DEBUG_EXIF', false); // set to true to log start/finish of exif processing. Useful to find problematic images.
define('DEBUG_PLUGINS', false); // set to true to log plugin load sequence.

if (DEBUG_LOGIN || DEBUG_IMAGE || DEBUG_404 || DEBUG_EXIF) {
	debugLog("Zenphoto v".ZENPHOTO_VERSION.'['.ZENPHOTO_RELEASE.']');
}

if (function_exists('date_default_timezone_set')) { // insure a correct timezone
	error_reporting(0);
	$_zp_server_timezone = date_default_timezone_get();
	date_default_timezone_set($_zp_server_timezone);
	@ini_set('date.timezone', $_zp_server_timezone);
}

// Set error reporting.
if (defined("RELEASE")) {
	error_reporting(E_ALL ^E_NOTICE);
} else {
	if (version_compare(PHP_VERSION,'5.0.0') == 1) {
		error_reporting(E_ALL | E_STRICT);
	} else {
		error_reporting(E_ALL);
	}
}
$_zp_error = false;

require_once(dirname(__FILE__).'/lib-utf8.php');

if (!file_exists(dirname(dirname(__FILE__)).'/'.DATA_FOLDER . "/zp-config.php")) {
	die (sprintf(gettext("<strong>Zenphoto error:</strong> zp-config.php not found. Perhaps you need to run <a href=\"%s/setup.php\">setup</a> (or migrate your old config.php)"),ZENFOLDER));
}
// Including zp-config.php more than once is OK, and avoids $conf missing.
require(dirname(dirname(__FILE__)).'/'.DATA_FOLDER.'/zp-config.php');

if (!defined('CHMOD_VALUE')) { define('CHMOD_VALUE', 0777); }
if (!defined('OFFSET_PATH')) { define('OFFSET_PATH', 0); }
if (!defined('COOKIE_PESISTENCE')) { define('COOKIE_PESISTENCE', 5184000); }

// Set the memory limit higher just in case -- suppress errors if user doesn't have control.
// 100663296 bytes = 96M
if (ini_get('memory_limit') && parse_size(ini_get('memory_limit')) < 100663296) {
	@ini_set('memory_limit','96M');
}

// If the server protocol is not set, set it to the default (obscure zp-config.php change).
if (!isset($_zp_conf_vars['server_protocol'])) $_zp_conf_vars['server_protocol'] = 'http';

$_zp_imagick_present = false;
require_once(dirname(__FILE__).'/functions-db.php');

// load graphics libraries in priority order
// once a library has concented to load, all others will 
// abdicate.
$_zp_graphics_optionhandlers = array();
require_once(dirname(__FILE__).'/lib-Imagick.php');
require_once(dirname(__FILE__).'/lib-GD.php');

if (function_exists('zp_graphicsLibInfo')) {
	$_zp_supported_images = zp_graphicsLibInfo();
	unset($_zp_supported_images['Library']);
	foreach ($_zp_supported_images as $key=>$type) {
		unset($_zp_supported_images[$key]);
		if ($type) $_zp_supported_images[strtolower($key)] = true;
	}
	$_zp_supported_images = array_keys($_zp_supported_images);
} else {
	$_zp_supported_images = array();
}

require_once(dirname(__FILE__).'/lib-encryption.php');

switch (OFFSET_PATH) {
	case 0:	// starts from the root index.php
		$const_webpath = dirname($_SERVER['SCRIPT_NAME']);
		break;
	case 1:	// starts from the zp-core folder
	case 2:	// things which do not need admin tabs (setup, image processor scripts, etc.)
		$const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
		break;
	case 3: // starts from the plugins folder
		$const_webpath = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
		break;
	case 4: // starts from within a folder within the plugins folder
		$const_webpath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
		break;
	case 5: // $const_webpath provided by the "loading function"
		break;
}
$const_webpath = str_replace("\\", '/', $const_webpath);
if ($const_webpath == '/') $const_webpath = '';
if (!defined('WEBPATH')) { define('WEBPATH', $const_webpath); }
if (!defined('SERVERPATH')) define('SERVERPATH', str_replace("\\", '/', dirname(dirname(__FILE__))));
$protocol = getOption('server_protocol');
switch ($protocol) {
	case 'https':
	define('PROTOCOL', $protocol);
	break;
	default:
	if(secureServer()) {
		define('PROTOCOL', 'https');
	} else {
		define('PROTOCOL', 'http');
	}
	break;
}
define('FULLWEBPATH', PROTOCOL."://" . $_SERVER['HTTP_HOST'] . WEBPATH);
define('SAFE_MODE_ALBUM_SEP', '__');
define('SERVERCACHE', SERVERPATH . '/'.CACHEFOLDER);

// Set the version number.
$_zp_conf_vars['version'] = ZENPHOTO_VERSION;

// the options array
$_zp_options = NULL;


/**
 * Decodes HTML Special Characters.  Function for backwards compatability with PHP 4.1.
 *
 * @param string $text
 * @param string $quote_style
 * @return string
 */
if (!function_exists("htmlspecialchars_decode")) {
	function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
		$translation_table = get_html_translation_table(HTML_SPECIALCHARS, $quote_style);
		$translation_table["'"] = '&#039;';
		return (strtr($string, array_flip($translation_table)));
	}
}

/**
 * encodes a pre-sanitized string to be used in an HTML text-only field (value, alt, title, etc.)
 *
 * @param string $this_string
 * @return string
 */
function html_encode($this_string, $striptags=true) {
	if ($striptags) {$this_string = strip_tags($this_string);}
	$this_string = htmlspecialchars_decode($this_string, ENT_QUOTES);
	return htmlspecialchars($this_string, ENT_QUOTES, getOption('charset'));
}

/**
 * encodes a pre-sanitized string to be used in a Javascript alert box
 *
 * @param string $this_string
 * @return string
 */
function js_encode($this_string) {
	global $_zp_UTF8;
	$this_string = preg_replace("/\r?\n/", "\\n", $this_string);
	$this_string = $_zp_UTF8->encode_javascript($this_string);
	return $this_string;
}

/**
 * Get a option stored in the database.
 * This function reads the options only once, in order to improve performance.
 * @param string $key the name of the option.
 * @param bool $db set to true to force retrieval from the database.
 */
function getOption($key, $db=false) {
	global $_zp_conf_vars, $_zp_options, $_zp_optionDB_hasownerid;
	if (is_null($_zp_options)) {
		$_zp_options = array();

		$sql = "SELECT `name`, `value` FROM ".prefix('options').' WHERE `ownerid`=0';
		$optionlist = query_full_array($sql, true);
		if ($optionlist == false) { // might be old, un-migrated option table during setup--retry without the `ownerid`.
			$sql = "SELECT `name`, `value` FROM ".prefix('options');
			$optionlist = query_full_array($sql, true);
		}
		if ($optionlist !== false) {
			foreach($optionlist as $option) {
				$_zp_options[$option['name']] = $option['value'];
			}
		}
	} else {
		if ($db) {
			$sql = "SELECT `value` FROM ".prefix('options')." WHERE `name`='".zp_escape_string($key)."' AND `ownerid`=0";
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
		$result = query_single_row("SELECT `value` FROM ".prefix('options')." WHERE `name`='".$key."' AND `ownerid`=0", true);
		if (is_array($result) && array_key_exists('value', $result)) { // option already exists.
			if (is_null($value)) {
				$sql = "UPDATE " . prefix('options') . " SET `value`=NULL WHERE `name`='" . zp_escape_string($key) ."' AND `ownerid`=0";
			} else {
				$sql = "UPDATE " . prefix('options') . " SET `value`='" . zp_escape_string($value) . "' WHERE `name`='" . zp_escape_string($key) ."' AND `ownerid`=0";
			}
			$result = query($sql, true);
		} else {
			if (is_null($value)) {
				$sql = "INSERT INTO " . prefix('options') . " (name, value, ownerid) VALUES ('" . zp_escape_string($key) . "',NULL, 0)";
			} else {
				$sql = "INSERT INTO " . prefix('options') . " (name, value, ownerid) VALUES ('" . zp_escape_string($key) . "','" . zp_escape_string($value) . "', 0)";
			}
			$result = query($sql, true);
		}
	} else {
		$result = true;
	}
	if ($result) {
		$_zp_options[$key] = $value;
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
		if (is_null($default)) {
			$sql = "INSERT INTO " . prefix('options') . " (`name`, `value`, `ownerid`) VALUES ('" . zp_escape_string($key) . "', NULL, 0);";
		} else {
			$sql = "INSERT INTO " . prefix('options') . " (`name`, `value`, `ownerid`) VALUES ('" . zp_escape_string($key) . "', '".
			zp_escape_string($default) . "', 0);";
		}
		query($sql, true);
		$_zp_options[$key] = $default;
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

/**
 * Returns true if the file has the dynamic album suffix
 *
 * @param string $path
 * @return bool
 */
function hasDynamicAlbumSuffix($path) {
	return strtolower(substr(strrchr($path, "."), 1)) == 'alb';
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
		$uri = urldecode(sanitize($_SERVER['REQUEST_URI'], 0));
		$path = substr($uri, strlen(WEBPATH)+1);
		$scripturi = sanitize($_SERVER['PHP_SELF'],0);
		$script = substr($scripturi,strpos($scripturi, WEBPATH.'/')+strlen(WEBPATH)+1);
		// Only extract the path when the request doesn't include the running php file (query request).
		if (strlen($path) > 0 && strpos($uri, $script) === false && isset($_GET[$albumvar])) {
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
			$albumpos = strpos($path, '/album/');
			if ($imagepos !== false) {
				$ralbum = substr($path, 0, $imagepos);
				$rimage = substr($path, $slashpos+1);
			} else if ($albumpos !== false) {
				$ralbum = substr($path, 0, $albumpos);
				$rimage = substr($path, $slashpos+1);
			} else if ($pagepos !== false) {
				$ralbum = substr($path, 0, $pagepos);
				$rimage = null;
			} else if ($slashpos !== false) {
				$ralbum = substr($path, 0, $slashpos);
				$rimage = substr($path, $slashpos+1);
				if ((is_dir(getAlbumFolder() . internalToFilesystem($ralbum . '/' . $rimage)) || hasDynamicAlbumSuffix($rimage))) {
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
	$ralbum = isset($_GET[$albumvar]) ? sanitize_path($_GET[$albumvar]) : null;
	$rimage = isset($_GET[$imagevar]) ? sanitize_path($_GET[$imagevar]) : null;
	return array($ralbum, $rimage);
}

/**
 * Returns the path of an image for uses in caching it
 * NOTE: character set if for the filesystem
 *
 * @param string $album album folder
 * @param string $image image file name
 * @param array $args cropping arguments
 * @return string
 */
function getImageCacheFilename($album8, $image8, $args) {
	global $_zp_supported_images;
	// this function works in FILESYSTEM_CHARSET, so convert the file names
	$album = internalToFilesystem($album8);
	$suffix = getSuffix($image8);
	if (!in_array($suffix, $_zp_supported_images)) {
		$suffix = 'jpg';
	}
	$image = stripSuffix(internalToFilesystem($image8));
	// Set default variable values.
	$postfix = getImageCachePostfix($args);
	if (empty($album)) {
		$albumsep = '';
	} else {
		if (ini_get('safe_mode')) {
			$albumsep = SAFE_MODE_ALBUM_SEP;
			$album = str_replace(array('/',"\\"), $albumsep, $album);
		} else {
			$albumsep = '/';
		}
	}
	$result = '/' . $album . $albumsep . $image . $postfix . '.'.$suffix;
	return $result;
}

/**
 * Returns the crop/sizing string to postfix to a cache image
 *
 * @param array $args cropping arguments
 * @return string
 */
function getImageCachePostfix($args) {
	list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbStandin, $passedWM, $adminrequest, $gray) = $args;
	$postfix_string = ($size ? "_$size" : "") . ($width ? "_w$width" : "")
	. ($height ? "_h$height" : "") . ($cw ? "_cw$cw" : "") . ($ch ? "_ch$ch" : "")
	. (is_numeric($cx) ? "_cx$cx" : "") . (is_numeric($cy) ? "_cy$cy" : "")
	. ($thumb || $thumbStandin ? '_thumb' : '')
	. ($adminrequest ? '_admin' : '')
	. ($gray ? '_gray' : '');
	return $postfix_string;
}


/**
 * Validates and edits image size/cropping parameters
 *
 * @param array $args cropping arguments
 * @return array
 */
function getImageParameters($args, $album=NULL) {
	$thumb_crop = getOption('thumb_crop');
	$thumb_size = getOption('thumb_size');
	$thumb_crop_width = getOption('thumb_crop_width');
	$thumb_crop_height = getOption('thumb_crop_height');
	$thumb_quality = getOption('thumb_quality');
	$image_default_size = getOption('image_size');
	$quality = getOption('image_quality');
	// Set up the parameters
	$thumb = $crop = false;
	@list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbstandin, $WM, $adminrequest, $gray) = $args;
	$thumb = $thumbstandin;
	if ($size == 'thumb') {
		$thumb = true;
		if ($thumb_crop) {
			$cw = $thumb_crop_width;
			$ch = $thumb_crop_height;
		}
		$size = round($thumb_size);
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
	list($width, $height, $cw, $ch, $quality) =	array_map('sanitize_numeric', array($width, $height, $cw, $ch, $quality));
	if (!is_null($cx)) {
		$cx = sanitize_numeric($cx);
	}
	if (!is_null($cy)) {
		$cy = sanitize_numeric($cy);
	}
	if (empty($cw) && empty($ch)) {
		$crop = false;
	} else {
		$crop = true;
	}
	if (is_null($gray)) {
		if ($thumb) {
			$gray = getOption('thumb_gray');
		} else {
			$gray = getOption('image_gray');
		}
	}
	if (empty($quality)) {
		if ($thumb) {
			$quality = round($thumb_quality);
		} else {
			$quality = getOption('image_quality');
		}
	}
	if (empty($WM)) {
		if (!$thumb) {
			if (!empty($album)) {
				$WM = getAlbumInherited($album, 'watermark', $id);
			}
			if (empty($WM)) {
				$WM = getOption('fullimage_watermark');
			}
		}
	}

	// Return an array of parameters used in image conversion.
	$args =  array($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbstandin, $WM, $adminrequest, $gray);
	return $args;
}

/**
 * forms the i.php parameter list for an image.
 *
 * @param array $args
 * @param string $album the album name
 * @param string $image the image name
 * @return string
 */
function getImageProcessorURI($args, $album, $image) {
	list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop, $thumbstandin, $passedWM, $adminrequest, $gray) = $args;
	$uri = WEBPATH.'/'.ZENFOLDER.'/i.php?a='.urlencode($album).'&i='.urlencode($image);
	if (!empty($size)) $uri .= '&s='.$size;
	if (!empty($width)) $uri .= '&w='.$width;
	if (!empty($height)) $uri .= '&h='.$height;
	if (!is_null($cw)) $uri .= '&cw='.$cw;
	if (!is_null($ch)) $uri .= '&ch='.$ch;
	if (!is_null($cx)) $uri .= '&cx='.$cx;
	if (!is_null($cy)) $uri .= '&cy='.$cy;
	if (!empty($quality)) $uri .= '&q='.$quality;
	if ($thumb || $thumbstandin) $uri .= '&t=1';
	if (!empty($passedWM)) $uri .= '&wmk='.$passedWM;
	if (!empty($adminrequest)) $uri .= '&admin';
	if (!empty($gray)) $uri .= '&gray='.$gray;
	return $uri;
}

/**
 * Takes user input meant to be used within a path to a file or folder and
 * removes anything that could be insecure or malicious, or result in duplicate
 * representations for the same physical file.
 *
 * This function is used primarily for album names.
 * NOTE: The initial and trailing slashes are removed!!!
 *
 * Returns the sanitized path
 *
 * @param string $filename is the path text to filter.
 * @return string
 */
function sanitize_path($filename) {
	if (get_magic_quotes_gpc()) $filename = stripslashes($filename);
	$filename = str_replace(chr(0), " ", $filename);
	$filename = strip_tags($filename);
	$filename = preg_replace(array('/^\/+/','/\/+$/','/\/\/+/','/\.\.+/'), '', $filename);
	return $filename;
}

/**
 * Checks if the input is numeric, rounds if so, otherwise returns false.
 *
 * @param mixed $num the number to be sanitized
 * @return int
 */
function sanitize_numeric($num) {
	if (is_numeric($num)) {
		return round($num);
	} else {
		return false;
	}
}

/** Make strings generally clean.  Takes an input string and cleans out
 * null-bytes, slashes (if magic_quotes_gpc is on), and optionally use KSES
 * library to prevent XSS attacks and other malicious user input.
 * @param string $input_string is a string that needs cleaning.
 * @param string $sanitize_level is a number between 0 and 3 that describes the
 * type of sanitizing to perform on $input_string.
 *   0 - Basic sanitation. Only strips null bytes. Not recommended for submitted form data.
 *   1 - User specified. (User defined code is allowed. Used for descriptions and comments.)
 *   2 - Text style/formatting. (Text style codes allowed. Used for titles.)
 *   3 - Full sanitation. (Default. No code allowed. Used for text only fields)
 * @return string the sanitized string.
 */
function sanitize($input_string, $sanitize_level=3) {
	if (is_array($input_string)) {
		$output_string = array();
		foreach ($input_string as $output_key => $output_value) {
			$output_string[$output_key] = sanitize_string($output_value, $sanitize_level);
		}
	} else {
		$output_string = sanitize_string($input_string, $sanitize_level);
	}
	return $output_string;
}

/** returns a sanitized string for the sanitize function
 * @param string $input_string
 * @param string $sanitize_level
 * @return string the sanitized string.
 */
function sanitize_string($input_string, $sanitize_level) {
	// Strip slashes if get_magic_quotes_gpc is enabled.
	if (get_magic_quotes_gpc()) $input_string = stripslashes($input_string);
	// Basic sanitation.
	if ($sanitize_level === 0) {
		return str_replace(chr(0), " ", $input_string);
		}
	// User specified sanititation.
	require_once(dirname(__FILE__).'/lib-htmlawed.php');

	if ($sanitize_level === 1) {
		$user_tags = "(".getOption('allowed_tags').")";
		$allowed_tags = parseAllowedTags($user_tags);
		if ($allowed_tags === false) { $allowed_tags = array(); } // someone has screwed with the 'allowed_tags' option row in the database, but better safe than sorry
		$input_string = kses($input_string, $allowed_tags);

	// Text formatting sanititation.
	} else if ($sanitize_level === 2) {
		$style_tags = "(".getOption('style_tags').")";
		$allowed_tags = parseAllowedTags($style_tags);
		if ($allowed_tags === false) { $allowed_tags = array(); } // someone has screwed with the 'style_tags' option row in the database, but better safe than sorry
		$input_string = kses($input_string, $allowed_tags);

	// Full sanitation.  Strips all code.
	} else if ($sanitize_level === 3) {
		$allowed_tags = array();
		$input_string = kses($input_string, $allowed_tags);
	}
	return $input_string;
}

/**
 * Formats an error message
 * If DEBUG_ERROR is set, supplies the calling sequence
 *
 * @param string $message
 * @param bool $fatal set true to fail the script
 */
function zp_error($message, $fatal=true) {
	global $_zp_error;
	if (!$_zp_error) {
		
//TODO: gettext calls for the messages		
		
		?>
		<div style="padding: 15px; border: 1px solid #F99; background-color: #FFF0F0; margin: 20px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt;">
			<h2 style="margin: 0px 0px 5px; color: #C30;">Zenphoto encountered an error</h2>
			<div style=" color:#000;">
				<?php echo $message; ?>
			</div>
		<?php
		if (DEBUG_ERROR) {
			// Get a backtrace.
			$bt = debug_backtrace();
			array_shift($bt); // Get rid of zp_error in the backtrace.
			$prefix = '  ';
			?>
			<p>
				<strong>Backtrace:</strong>
				<br />
				<pre>
					<?php
					echo "\n";
					foreach($bt as $b) {
						echo $prefix . ' -> '
						. (isset($b['class']) ? $b['class'] : '')
						. (isset($b['type']) ? $b['type'] : '')
						. $b['function']
						. ' (' . basename($b['file'])
						. ' [' . $b['line'] . "])\n";
						$prefix .= '  ';
					}
					?>
				</pre>
			</p>
			<?php
		}
		?>
		</div>
		<?php
		if ($fatal) {
			$_zp_error = true;
			exit();
		}
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
 * @param bool $webpath true if you want the WEBPATH to be returned, false if you want to generate a partly path. A trailing "/" is always added.
 * @return string
 */
function rewrite_path($rewrite, $plain,$webpath=true) {
	$path = null;
	if (getOption('mod_rewrite')) {
		$path = $rewrite;
	} else {
		$path = $plain;
	}
	if (substr($path, 0, 1) == "/"  && $webpath) $path = substr($path, 1);
	if($webpath) {
		return WEBPATH . "/" . $path;
	} else {
		return $path;
	}
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
 * Returns the fully qualified path to the album folders
 *
 * @param string $root the base from whence the path dereives
 * @return sting
 */
$_zp_album_folder = null;
function getAlbumFolder($root=SERVERPATH) {
	$root = str_replace('\\', '/', $root);
	global $_zp_album_folder, $_zp_conf_vars;
	if (is_null($_zp_album_folder)) {
		if (!isset($_zp_conf_vars['external_album_folder']) || empty($_zp_conf_vars['external_album_folder'])) {
			if (!isset($_zp_conf_vars['album_folder']) || empty($_zp_conf_vars['album_folder'])) {
				$_zp_album_folder = $_zp_conf_vars['album_folder'] = '/'.ALBUMFOLDER.'/';
			} else {
				$_zp_album_folder = str_replace('\\', '/', $_zp_conf_vars['album_folder']);
			}
		} else {
			$_zp_conf_vars['album_folder_class'] = 'external';
			$_zp_album_folder =  str_replace('\\', '/', $_zp_conf_vars['external_album_folder']);
		}
		if (substr($_zp_album_folder, -1) != '/') $_zp_album_folder .= '/';
	}
	if (!isset($_zp_conf_vars['album_folder_class'])) {
		$_zp_conf_vars['album_folder_class'] = 'std';
	}
	switch ($_zp_conf_vars['album_folder_class']) {
		case 'std':
			return $root . $_zp_album_folder;
		case 'in_webpath':
			if (WEBPATH) { 			// strip off the WEBPATH
				$root = str_replace('\\', '/', dirname($root));
				if ($root == '/') {
					$root = '';
				}
			}
			return $root . $_zp_album_folder;
		case 'external':
			return $_zp_album_folder;
	}
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
function debugLog($message, $reset=false, $date=true) {
	if ($reset) { $mode = 'w'; } else { $mode = 'a'; }
	$f = fopen(dirname(dirname(__FILE__)) . '/' . DATA_FOLDER . '/debug_log.txt', $mode);
	if ($date) $date = '{'.gmdate('D, d M Y H:i:s').' GMT} '."\n";
	fwrite($f, $date.$message . "\n");
	fclose($f);
}

/**
 * "print_r" equivalent for the debug log
 *
 * @param string $name the name (or message) to display for the array
 * @param array $source
 */
function debugLogArray($name, $source, $indent=0, $trail='',$date=true) {
	if (is_array($source)) {
		$msg = str_repeat(' ', $indent)."$name => ( ";
		if (count($source) > 0) {
			foreach ($source as $key => $val) {
				if (strlen($msg) > 72) {
					debugLog($msg,false,$date);
					$date = false;
					$msg = str_repeat(' ', $indent);
				}
				if (is_array($val)) {
					if (!empty($msg)) debugLog($msg);
					debugLogArray($key, $val, $indent+5, ',',false);
					$msg = '';
				} else {
					if (is_null($val)) {
						$msg .= $key.' => NULL, ';
					} else {
						$msg .= $key . " => " . $val. ', ';
					}
				}
			}

			$msg = substr($msg, 0, strrpos($msg, ',')) . " )".$trail;
		} else {
			$msg .= ")";
		}
		debugLog($msg,false,$date);
	} else {
		debugLog($name.' parameter is not an array.');
	}
}

/**
 * Logs the calling stack
 *
 * @param string $message Message to prefix the backtrace
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
			debugLog($msg.$caller.' ('.$line.')',false,false);
			$prefix .= '  ';
		} else {
			debugLog($caller.' called',false,false);
		}
		$date = false;
		if (isset($b['file']) && isset($b['line'])) {
			$line = basename($b['file'])	. ' [' . $b['line'] . "]";
		} else {
			$line = 'unknown';
		}
	}
	if (!empty($line)) {
		debugLog($prefix.' from '.$line,false,false);
	}
}

/**
 * Records a Var to the debug log
 *
 * @param string $message message to insert in log
 * @param mixed $var the variable to record
 */
function debugLogVar($message, $var) {
	ob_start();
	var_dump($var);
	$str = ob_get_contents();
	ob_end_clean();
	debugLog($message);
	debugLog($str,false,false);
}


/**
 * Makes directory recursively, returns TRUE if exists or was created sucessfuly.
 * Note: PHP5 includes a recursive parameter to mkdir, but PHP4 does not, so this
 *   is required to target PHP4.
 * @param string $pathname The directory path to be created.
 * @return boolean TRUE if exists or made or FALSE on failure.
 */

function mkdir_recursive($pathname, $mode=0777) {
	if (!is_dir(dirname($pathname))) mkdir_recursive(dirname($pathname), $mode);
	return is_dir($pathname) || @mkdir($pathname, $mode & CHMOD_VALUE);
}

/**
 * Parses a byte size from a size value (eg: 100M) for comparison.
 */
function parse_size($size) {
	$suffixes = array(
		'' => 1,
		'k' => 1024,
		'm' => 1048576, // 1024 * 1024
		'g' => 1073741824, // 1024 * 1024 * 1024
	);
	if (preg_match('/([0-9]+)\s*(k|m|g)?(b?(ytes?)?)/i', $size, $match)) {
		return $match[1] * $suffixes[strtolower($match[2])];
	}
}

/**
 * Converts a file system filename to UTF-8 for zenphoto internal storage
 *
 * @param string $filename the file name to convert
 * @return string
 */
function filesystemToInternal($filename) {
	global $_zp_UTF8;
	return $_zp_UTF8->convert($filename, FILESYSTEM_CHARSET, getOption('charset'));
}

/**
 * Converts a Zenphoto Internal filename string to one compatible with the file system
 *
 * @param string $filename the file name to convert
 * @return string
 */
function internalToFilesystem($filename) {
	global $_zp_UTF8;
	return $_zp_UTF8->convert($filename, getOption('charset'), FILESYSTEM_CHARSET);
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
 * Returns true if the file is a valid 'other' type
 *
 * @param string $filename the name of the target
 * @return bool
 */
function is_valid_other($filename) {
	global $_zp_extra_filetypes;
	$ext = strtolower(substr(strrchr($filename, "."), 1));
	return isset($_zp_extra_filetypes[$ext]);
}

/**
 * Returns true if we are running on a Windows server
 *
 * @return bool
 */
function isWin() {
	return (strtoupper (substr(PHP_OS, 0,3)) == 'WIN' ) ;
}

/**
 * Returns an img src URI encoded based on the OS of the server
 *
 * @param string $uri uri in FILESYSTEM_CHARSET encoding
 * @return string
 */
function imgSrcURI($uri) {
	if (getOption('UTF8_image_URI')) return filesystemToInternal($uri);
	return $uri;
}

/**
 * returns the mod_rewrite suffix
 *
 * @return string
 */
function im_suffix() {
	return getOption('mod_rewrite_image_suffix');
}

/**
 * Returns the suffix of a file name
 *
 * @param string $filename
 * @return string
 */
function getSuffix($filename) {
	return strtolower(substr(strrchr($filename, "."), 1));
}

/**
 * returns a file name sans the suffix
 *
 * @param unknown_type $filename
 * @return unknown
 */
function stripSuffix($filename) {
	return str_replace(strrchr($filename, "."),'',$filename);
}
/**
 * Returns the Require string for the appropriate script based on the PHP version
 *
 * @param string $v The version dermarkation
 * @param string $script the script name
 * @return string
 */
function PHPScript($v, $script) {
	return dirname(__FILE__).'/'.(version_compare(PHP_VERSION, $v) == 1?'PHP5':'PHP4').'_functions/'.$script;
}

/**
 * returns the non-empty value of $field from the album or one of its parents
 *
 * @param string $folder the album name
 * @param string $field the desired field name
 * @param int $id will be set to the album `id` of the album which has the non-empty field
 * @return string
 */
function getAlbumInherited($folder, $field, &$id) {
	$folders = explode('/',filesystemToInternal($folder));
	$album = array_shift($folders);
	$like = ' LIKE "'.$album.'"';
	while (count($folders) > 0) {
		$album .= '/'.array_shift($folders);
		$like .= ' OR `folder` LIKE "'.$album.'"';
	}
	$sql = 'SELECT `id`, `'.$field.'` FROM '.prefix('albums').' WHERE `folder`'.$like;
	$result = query_full_array($sql);
	if (!is_array($result)) return '';
	while (count($result) > 0) {
		$try = array_pop($result);
		if (!empty($try[$field])) {
			$id = $try['id'];
			return $try[$field];
		}
	}
	return '';
}

/**
 * primitive theme setup for image handling scripts
 *
 * we need to conserve memory so loading the classes is out of the question.
 *
 * @param string $album
 * @return string
 */
function themeSetup($album) {
	// we need to conserve memory in i.php so loading the classes is out of the question.
	$id = NULL;
	$theme = getAlbumInherited(filesystemToInternal($album), 'album_theme', $id);
	if (empty($theme)) {
		return getOption('current_theme');
	} else {
		loadLocalOptions($id, $theme);
		return $theme;
	}
}

/**
 * Loads option table with album/theme options
 *
 * @param int $albumid
 * @param string $theme
 */
function loadLocalOptions($albumid, $theme) {
	$theme = zp_escape_string($theme);
	if ($albumid) {
		//raw album options
		$sql = "SELECT `name`, `value` FROM ".prefix('options').' WHERE `theme` IS NULL AND `ownerid`='.$albumid;
		$optionlist = query_full_array($sql, true);
		if ($optionlist !== false) {
			foreach($optionlist as $option) {
				setOption($option['name'], $option['value'], false);
			}
		}
	}
	//raw theme options
	$sql = "SELECT `name`, `value` FROM ".prefix('options').' WHERE `theme`="'.$theme.'" AND `ownerid`=0';
	$optionlist = query_full_array($sql, true);
	if ($optionlist !== false) {
		foreach($optionlist as $option) {
			setOption($option['name'], $option['value'], false);
		}
	}
	if ($albumid) {
		//album-theme options
		$sql = "SELECT `name`, `value` FROM ".prefix('options').' WHERE `theme`="'.$theme.'" AND `ownerid`='.$albumid;
		$optionlist = query_full_array($sql, true);
		if ($optionlist !== false) {
			foreach($optionlist as $option) {
				setOption($option['name'], $option['value'], false);
			}
		}
	}
}

/**
 * Checks to see if the loggedin Admin has rights to the album
 *
 * @param string $albumfolder the album to be checked
 * @param int $action what the user wishes to do
 */
function isMyAlbum($albumfolder, $action) {
	global $_zp_loggedin, $_zp_admin_album_list;
	if ($_zp_loggedin & (ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS | VIEW_ALL_RIGHTS)) {
		if ($_zp_loggedin & (ADMIN_RIGHTS | $action)) return true;
	}
	if (zp_apply_filter('check_album_credentials', false)) return true;
	if (empty($albumfolder) || $albumfolder == '/') {
		return false;
	}
	if ($_zp_loggedin & $action) {
		if (is_null($_zp_admin_album_list)) {
			getManagedAlbumList();
		}
		if (count($_zp_admin_album_list) == 0) {
			return false;
		}
		$desired_folders = explode('/', $albumfolder);
		foreach ($_zp_admin_album_list as $key => $adminalbum) { // see if it is one of the managed folders or a subfolder there of
			$admin_folders = explode('/', $adminalbum);
			$found = true;
			foreach ($admin_folders as $level=>$folder) {
				if ($level >= count($desired_folders) || $folder != $desired_folders[$level]) {
					$found = false;
					break;
				}
			}
			if ($found) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Returns the path to a watermark
 *
 * @param string $wm watermark name
 * @return string
 */
function getWatermarkPath($wm) {
	$path = SERVERPATH . '/' . ZENFOLDER . '/watermarks/' . internalToFilesystem($wm).'.png';
	if (!file_exists($path)) {
		$path = SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/watermarks/' . internalToFilesystem($wm).'.png';
	}
	return $path;
}

/**
 * mysql_real_escape_string standin that insures the DB connection is passed.
 *
 * @param string $string
 * @return string
 */
function zp_escape_string($string) {
	global $mysql_connection;
	return mysql_real_escape_string($string,$mysql_connection);
}

/**
 * Checks to see if access was through a secure protocol
 *
 * @return bool
 */
function secureServer() {
	if(isset($_SERVER['HTTPS']) && strpos(strtolower($_SERVER['HTTPS']),'on')===0) {
		return true;
	}
	return false;
}

?>
