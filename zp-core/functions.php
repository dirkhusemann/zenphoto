<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }

// Set the memory limit higher just in case -- supress errors if user doesn't have control.
@ini_set('memory_limit','128M');

// functions.php - HEADERS NOT SENT YET!

if (!file_exists(dirname(__FILE__) . "/zp-config.php")) {
  die ("<strong>Zenphoto error:</strong> zp-config.php not found. Perhaps you need to run <a href=\"" . ZENFOLDER . "/setup.php\">setup</a> (or migrate your old config.php)");
}

// Including zp-config.php more than once is OK, and avoids $conf missing.
require("zp-config.php");

// If the server protocol is not set, set it to the default (obscure zp-config.php change).
if (!isset($_zp_conf_vars['server_protocol'])) $_zp_conf_vars['server_protocol'] = 'http';

require_once('kses.php');
require_once('exif/exif.php');
require_once('plugins/phooglelite.php');
require_once('functions-db.php');

if (defined('OFFSET_PATH')) {
  $const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
} else {
  $const_webpath = dirname($_SERVER['SCRIPT_NAME']);
}
if ($const_webpath == '\\' || $const_webpath == '/') $const_webpath = '';
if (!defined('WEBPATH')) { define('WEBPATH', $const_webpath); }
define('SERVERPATH', dirname(dirname(__FILE__)));
define('PROTOCOL', getOption('server_protocol'));
define('FULLWEBPATH', PROTOCOL."://" . $_SERVER['HTTP_HOST'] . WEBPATH);
define('SAFE_MODE_ALBUM_SEP', '__');
define('DEBUG', false);
define('CACHEFOLDER', '/cache/');
define('SERVERCACHE', SERVERPATH . substr(CACHEFOLDER, 0, -1));

// Set the version number.
$_zp_conf_vars['version'] = '1.1.3';

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

// Set error reporting to the default if it's not.
error_reporting(E_ALL ^ E_NOTICE);
$_zp_error = false;

 
/**
  * Get a option stored in the database.
  * This function reads the options only once, in order to improve performance.
  * @param string $key the name of the option.
  */
function getOption($key) {
  global $_zp_conf_vars, $_zp_options, $setup;
  if (NULL == $_zp_options) {
    $_zp_options = array();
    if (!isset($setup)) {
      $sql = "SELECT `name`, `value` FROM ".prefix('options');
      $optionlist = query_full_array($sql);
      foreach($optionlist as $option) {
        $_zp_options[$option['name']] = $option['value'];
        $_zp_conf_vars[$option['name']] = $option['value'];  /* so that zp_conf will get the DB result */
      }
    }
  }  
  if (array_key_exists($key, $_zp_options)) {
    return $_zp_options[$key];
  } else {
    return $_zp_conf_vars[$key];
  }
}

/**
  * Create new option in database.
  *
  * @param string $key name of the option.
  * @param mixed $value new value of the option.
  */
function setOption($key, $value, $persistent=true) {
  global $_zp_conf_vars, $_zp_options;
  if ($value == getOption($key)) {
    return true;  // not changed 
  }
  if ($persistent) {   
    if (array_key_exists($key, $_zp_options)) {
      // option already exists.    
      $sql = "UPDATE " . prefix('options') . " SET `value`='" . escape($value) . "' WHERE `name`='" . escape($key) ."'";
    } else {
      $sql = "INSERT INTO " . prefix('options') . " (name, value) VALUES ('" . escape($key) . "','" . escape($value) . "')";
    }
    $result = query($sql);
  } else {
    $result = true; 
  }
  if ($result) {
    $_zp_options[$key] = strip($value);
    $_zp_conf_vars[$key] = strip($value);  /* so that zp_conf will get the DB result */
    return true;
  } else {
    return false;
  }
}
  
function setBoolOption($key, $value) {
  if ($value) {
    setOption($key, '1');
  } else {
    setOption($key, '0');
  }
}

function setOptionDefault($key, $default) {
  global $_zp_conf_vars, $_zp_options;
  if (NULL == $_zp_options) { getOption('nil'); } // pre-load from the database
  if (!array_key_exists($key, $_zp_options)) {
    $sql = "INSERT INTO " . prefix('options') . " (`name`, `value`) VALUES ('" . escape($key) . "', '". 
                            escape($default) . "');";
    query($sql);
    $_zp_options[$key] = $value;
    $_zp_conf_vars[$key] = $value; /* so that zp_conf will get the DB result */
  }
}
  
function getOptionList() { 
  global $_zp_options;
  if (NULL == $_zp_options) { getOption('nil'); } // pre-load from the database
  return $_zp_options; 
}

/**
* parses the 'allowed_tags' option for use by 
*@param string $source by name, contains the string with the tag options
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
    'EXIFOrientation'       => array('IFD0',   'Orientation',       'Orientation',            false),
    'EXIFMake'              => array('IFD0',   'Make',              'Camera Maker',           true),
    'EXIFModel'             => array('IFD0',   'Model',             'Camera Model',           true),
    'EXIFExposureTime'      => array('SubIFD', 'ExposureTime',      'Shutter Speed',          true),
    'EXIFFNumber'           => array('SubIFD', 'FNumber',           'Aperture',               true),
    'EXIFFocalLength'       => array('SubIFD', 'FocalLength',       'Focal Length',           true),
    'EXIFFocalLength35mm'   => array('SubIFD', 'FocalLength35mmEquiv', '35mm Equivalent Focal Length', false),
    'EXIFISOSpeedRatings'   => array('SubIFD', 'ISOSpeedRatings',   'ISO Sensitivity',        true),
    'EXIFDateTimeOriginal'  => array('SubIFD', 'DateTimeOriginal',  'Time Taken',             true),
    'EXIFExposureBiasValue' => array('SubIFD', 'ExposureBiasValue', 'Exposure Compensation',  true),
    'EXIFMeteringMode'      => array('SubIFD', 'MeteringMode',      'Metering Mode',          true),
    'EXIFFlash'             => array('SubIFD', 'Flash',             'Flash Fired',            true),
    'EXIFImageWidth'        => array('SubIFD', 'ExifImageWidth',    'Original Width',         false),
    'EXIFImageHeight'       => array('SubIFD', 'ExifImageHeight',   'Original Height',        false),
    'EXIFContrast'          => array('SubIFD', 'Contrast',          'Contrast Setting',       false),
    'EXIFSharpness'         => array('SubIFD', 'Sharpness',         'Sharpness Setting',      false),
    'EXIFSaturation'        => array('SubIFD', 'Saturation',        'Saturation Setting',     false),
    'EXIFGPSLatitude'       => array('GPS',    'Latitude',          'Latitude',               true),
    'EXIFGPSLatitudeRef'    => array('GPS',    'Latitude Reference','Latitude Reference',     true),
    'EXIFGPSLongitude'      => array('GPS',    'Longitude',         'Longitude',              true),
    'EXIFGPSLongitudeRef'   => array('GPS',    'Longitude Reference','Longitude Reference',   true),
    'EXIFGPSAltitude'       => array('GPS',    'Altitude',          'Altitude',               true),
    'EXIFGPSAltitudeRef'    => array('GPS',    'Altitude Reference','Altitude Reference',     true)
  );


// Set up assertions for debugging.
assert_options(ASSERT_ACTIVE, 0);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
function assert_handler($file, $line, $code) {
	dmesg("ERROR: Assertion failed in [$file:$line]: $code");
}
// Set up assertion callback
assert_options(ASSERT_CALLBACK, 'assert_handler');

// Image utility functions
function is_valid_image($filename) {
	$ext = strtolower(substr(strrchr($filename, "."), 1));
	return in_array($ext, array('jpg','jpeg','gif','png'));
}

//ZenVideo: Video utility functions
function is_valid_video($filename) {
	$ext = strtolower(substr(strrchr($filename, "."), 1));
	return in_array($ext, array('flv','3gp','mov'));
}

//ZenVideo: Check if the image is a video thumb
function is_videoThumb($album,$filename){
	
	$extTab = array(".flv",".3gp",".mov");
   foreach($extTab as $ext) {
     $video = $album.substr($filename,0,strrpos($filename,".")).$ext;
     if(file_exists($video) && !is_valid_video($filename)){
       return true;
     }
   }
 return false;
}

//ZenVideo: Search a thumbnail for the image
function checkVideoThumb($album,$video){
	$extTab = array(".flv",".3gp",".mov",".FLV",".3GP",".MOV");
    foreach($extTab as $ext) {
      $video = str_replace($ext,"",$video);
    }
	$extTab = array(".jpg",".jpeg",".gif",".png");

	foreach($extTab as $ext) {
  		if(file_exists($album."/".$video.$ext)) {
          	return $video.$ext;
      	}
	}
	return NULL;
}

function truncate_string($string, $length) {
  if (strlen($string) > $length) {
    $pos = strpos($string, ' ', $length);
    if ($pos === FALSE) return substr($string, 0, $length) . '...';
    return substr($string, 0, $pos) . '...';
  }
  return $string;
}


/** rewrite_get_album_image - Fix special characters in the album and image names if mod_rewrite is on:
    This is redundant and hacky; we need to either make the rewriting completely internal,
    or fix the bugs in mod_rewrite. The former is probably a good idea.
    
    Old explanation:
      rewrite_get_album_image() parses the album and image from the requested URL
      if mod_rewrite is on, and replaces the query variables with corrected ones.
      This is because of bugs in mod_rewrite that disallow certain characters.
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
        if (is_dir(getAlbumFolder() . $ralbum . '/' . $rimage)) {
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
      given album string.
    @param $albumstring is the path to the album as a string. Ex: album/subalbum/my-album
    @param $includepaths is a boolean whether or not to include the full path to the album
      in each item of the array. Ex: when $includepaths==false, the above array would be
      ['album', 'subalbum', 'my-album'], and with $includepaths==true, 
      ['album', 'album/subalbum', 'album/subalbum/my-album']
    @return array an array of album folder name strings for the given album.
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




/** getImageCacheFilename
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

/** getImageCachePostfix
 */
function getImageCachePostfix($args) {
  list($size, $width, $height, $cw, $ch, $cx, $cy) = $args;
  $postfix_string = ($size ? "_$size" : "") . ($width ? "_w$width" : "") 
    . ($height ? "_h$height" : "") . ($cw ? "_cw$cw" : "") . ($ch ? "_ch$ch" : "") 
    . (is_numeric($cx) ? "_cx$cx" : "") . (is_numeric($cy) ? "_cy$cy" : "");
  return $postfix_string;
}

/** getImageParameters
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
      $cw = min($thumb_crop_width, $thumb_size);
      $ch = min($thumb_crop_height, $thumb_size);
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


// Checks if the input is numeric, rounds if so, otherwise returns false.
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
 * @param $input_string is a string that needs cleaning.
 * @param $deepclean is whether to replace HTML tags, javascript, etc.
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
 * @param $filename is the path-bound text to filter.
 * @return string the sanitized filename ready for use in file functions.
 */
function sanitize_path($filename) {
  $filename = str_replace(chr(0), " ", $filename);
  $filename = strip_tags($filename);
  $filename = preg_replace(array('/^\/+/','/\/+$/','/\/\/+/','/\.\.+/'), '', $filename);
  return $filename;
}

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
 * @param $rewrite is the path to return if rewrite is enabled. (eg: "/myalbum")
 * @param $plain is the path if rewrite is disabled (eg: "/?album=myalbum")
 * The given paths can start /with or without a slash, it doesn't matter.
 *
 * IDEA: this function could be used to specially escape items in
 * the rewrite chain, like the # character (a bug in mod_rewrite).
 *
 * This is here because it's used in both template-functions.php and in the classes.
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


// Simple mySQL timestamp formatting function.
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
   $date   = date ($format, $epoch);
   return $date;
}

// Text formatting and checking functions

// Determines if the input is an e-mail address. Adapted from WordPress.
// Name changed to avoid conflicts in WP integrations.
function is_valid_email_zp($input_email) {
  $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
  if(strstr($input_email, '@') && strstr($input_email, '.')) {
    if (preg_match($chars, $input_email)) {
      return true;
    }
  }
  return false;
}

function is_image($filename) {
  $ext = strtolower(strrchr($filename, "."));
  return ($ext == ".jpg" || $ext == ".jpeg" || $ext == ".png" || $ext == ".gif");
}

function is_zip($filename) {
  $ext = strtolower(strrchr($filename, "."));
  return ($ext == ".zip");
}


// rawurlencode function that is path-safe (does not encode /)
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


// Takes a comment and makes the body of an email.
function commentReply($str, $name, $albumtitle, $imagetitle) {
  $str = wordwrap(strip_tags($str), 75, '\n');
  $lines = explode('\n', $str);
  $str = implode('%0D%0A', $lines);
  $str = "$name commented on $imagetitle in the album $albumtitle: %0D%0A%0D%0A" . $str;
  return $str;
}


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
 * Send an mail to the admin user. We also attempt to intercept any form injection
 * attacks by slime ball spammers.
 *
 * @param $subject  The subject of the email.
 * @param $message  The message contents of the email.
 * @param $headers  Optional headers for the email.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function zp_mail($subject, $message, $headers = '') {
  $admin_email = getOption('admin_email');
  if (!empty($admin_email)) {
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
    UTF8::send_mail("Admin <" . getOption('admin_email') . ">", $subject, $message, $headers);
  }
}

/**
   * Sort the album array based on either a) the manual sort order as specified by the user,
   * or b) the reverse order of how they are returned from disk. This will thus give us the
   * album list in with the the ordered albums first, followed by the rest with the newest first.
   *
   * @return A sorted array of album names.
   * 
   * @author Todd Papaioannou (lucky@luckyspin.org)
   * @since  1.0.0
   */
  function sortAlbumArray($albums, $sortkey='sort_order') {
  	global $_zp_loggedin;
    
    $albums_r = array();
	$hidden = array();
    $result = query("SELECT folder, sort_order, `show` FROM " . prefix("albums") 
      . " ORDER BY " . $sortkey);
      
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

function createAlbumZip($album){
  $rp = realpath(getAlbumFolder() . $album) . '/';
  $p = $album . '/';
  if(is_dir($rp)){
    include_once('plugins/zipfile.php');
    $z = new zipfile();
    $z->add_dir($p);
    if ($dh = opendir($rp)) {
      while (($file = readdir($dh)) !== false) {
        if($file != '.' && $file != '..'){
          $z->add_file(file_get_contents($rp . $file), $p . $file);
        }
      }
      closedir($dh);
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $album . '.zip"'); 
    echo $z->file();
  }
}

function getAlbumFolder($root=SERVERPATH) {
  if (is_null($album_folder = getOption('album_folder'))) {
    $album_folder = ALBUMFOLDER;
  }
  if (!is_null($external_folder = getOption('external_album_folder'))) {
    return $external_folder;
  } else {
    return $root . $album_folder;
  }
}

/*
 *Plug-in handling
  
 *getPlugin($plugin, $inTheme)
  
 *$plugin is the name of the plugin file, typically something.php
 *$inTheme tells where to find the plugin.
 *   true means look in the current theme
 *   false means look in the zp-core/plugins folder.
 *
 *  Returns the fully qualified "require" file name.
 */
function getPlugin($plugin, $inTheme) {
$gallery = new Gallery();
$theme = $gallery->getCurrentTheme();
$_zp_themeroot = WEBPATH . "/themes/$theme";
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
      $result['title'] = $title; 
    }

    /* iptc description */
    $caption= getIPTCTag('2#120');
    if (!empty($caption)) { 
      $result['desc'] = $caption;
    }
	  
	  /* iptc location, state, country */
	  $location = getIPTCTag('2#092');
	  if (!empty($location)) {
	    $result['location'] = $location;
	  }
	  $city = getIPTCTag('2#090');
	  if (!empty($city)) {
	    $result['city'] = $city;
	  }
	  $state = getIPTCTag('2#095');
	  if (!empty($state)) {
	    $result['state'] = $state;
	  }
	  $country = getIPTCTag('2#101');
	  if (!empty($country)) {
	    $result['country'] = $country;
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
      $result['credit'] = $credit;
    }
 	
 	/* iptc copyright */
 	$copyright= getIPTCTag('2#116');
    if (!empty($copyright)) { 
      $result['copyright'] = $copyright;
    }
	
	  /* iptc keywords (tags) */
      $keywords= getIPTCTag('2#025');
      if (!empty($keywords)) { 
        $result['tags'] = $keywords;
      }
    }
  }
  
  return $result;
}

function unzip($file, $dir) { //check if zziplib is installed
  if(function_exists('zip_open()')) {
    $zip = zip_open($file);
    if ($zip) {
      while ($zip_entry = zip_read($zip)) { // Skip non-images in the zip file.
        if (!is_image(zip_entry_name($zip_entry))) continue;
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
    require_once('pclzip.lib.php');
    $zip = new PclZip($file);
    if ($zip->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH) == 0) {
      die("Error : ".$zip->errorInfo(true));
    }
  }
}
/**
 * Checks to see if a URL is valid
 *
 * @param string $url
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
 * @param object $receiver
 * @return int
 */
function postComment($name, $email, $website, $comment, $code, $code_ok, $receiver) {
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
  $file = SERVERCACHE . "/code_" . $code_ok . ".png";
  if (getOption('Use_Captcha')) {
    if (!file_exists($file)) { return -5; }
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
    $goodMessage = $spamfilter->filterMessage($name, $email, $website, $comment, $type=='images'?$receiver->getFullImage():NULL);
  }

  if ($goodMessage) {
    if ($goodMessage == 1) {
      $moderate = 1;
    } else {
      $moderate = 0;
    }

    // Update the database entry with the new comment
    query("INSERT INTO " . prefix("comments") . " (`imageid`, `name`, `email`, `website`, `comment`, `inmoderation`, `date`, `type`) VALUES " .
            " ('" . $receiver->id .
            "', '" . escape($name) . 
            "', '" . escape($email) . 
            "', '" . escape($website) . 
            "', '" . escape($comment) . 
            "', '" . $moderate . 
            "', NOW()" .
            ", '$type')");

    if (!$moderate) {
      //  add to comments array and notify the admin user
       
      $newcomment = array();
      $newcomment['name'] = $name;
      $newcomment['email'] = $email;
      $newcomment['website'] = $website;
      $newcomment['comment'] = $comment;
      $newcomment['date'] = time();
      $receiver->comments[] = $newcomment;

      if ($type == 'images') {
        $on = $receiver->getAlbumName() . " about " . $receiver->getTitle();
        $url = "album=" . urlencode($receiver->album->name) . "&image=" . urlencode($receiver->filename);
      } else {
        $on = $receiver->name;
        $url = "album=" . urlencode($receiver->name);
      }
      if (getOption('email_new_comments')) {
        $message = "A comment has been posted in your album $on\n" .
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
                     "http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/" . ZENFOLDER . "/admin.php?page=comments\n";
        zp_mail("[" . getOption('gallery_title') . "] Comment posted on $on", $message);
      }
    }
  }
  return $goodMessage;
}

?>
