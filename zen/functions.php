<?php
require_once("kses.php");

// functions.php - HEADERS NOT SENT YET!

if (!file_exists(dirname(__FILE__) . "/zp-config.php")) {
  die ("<strong>Zenphoto error:</strong> zp-config.php not found. Perhaps you need to run <a href=\"zen/setup.php\">setup</a> (or migrate your old config.php)");
}

require_once(dirname(__FILE__) . "/zp-config.php");

// Set the version number.
$_zp_conf_vars['version'] = '1.0.8';

if (defined('OFFSET_PATH')) {
  $const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
} else {
  $const_webpath = dirname($_SERVER['SCRIPT_NAME']);
}
if ($const_webpath == '\\' || $const_webpath == '/') $const_webpath = '';
define('WEBPATH', $const_webpath);
define('SERVERPATH', dirname(dirname(__FILE__)));
define('SERVERCACHE', SERVERPATH . "/cache");
define('PROTOCOL', zp_conf('server_protocol'));
define('FULLWEBPATH', PROTOCOL."://" . $_SERVER['HTTP_HOST'] . WEBPATH);
define('SAFE_MODE_ALBUM_SEP', '__');



// For easy access to config vars.
function zp_conf($var) {
  global $_zp_conf_vars;
  if (array_key_exists($var, $_zp_conf_vars)) {
    return $_zp_conf_vars[$var];
  } else {
    return null;
  }
}

// Set up assertions for debugging.
assert_options(ASSERT_ACTIVE, 1);
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

function get_image($imgfile) {
	$ext = strtolower(substr(strrchr($imgfile, "."), 1));
	if ($ext == "jpg" || $ext == "jpeg") {
		return imagecreatefromjpeg($imgfile);
	} else if ($ext == "gif") {
		return imagecreatefromgif($imgfile);
	} else if ($ext == "png") {
		return imagecreatefrompng($imgfile);
	} else {
		return false;
	}
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
  if (zp_conf('mod_rewrite')) {
    $path = urldecode(substr($_SERVER['REQUEST_URI'], strlen(WEBPATH)+1));
    // Only extract the path when the request doesn't include the running php file (query request).
    if (strlen($path) > 0 && strpos($_SERVER['REQUEST_URI'], $_SERVER['PHP_SELF']) === false && isset($_GET[$albumvar])) {
      $im_suffix = zp_conf('mod_rewrite_image_suffix');
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
        if (is_dir(SERVERPATH . '/albums/' . $ralbum . '/' . $rimage)) {
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
  return array($_GET[$albumvar], $_GET[$imagevar]);
}


/** getAlbumArray - returns an array of folder names corresponding to the
      given album string.
    @param $albumstring is the path to the album as a string. Ex: album/subalbum/my-album
    @param $includepaths is a boolean whether or not to include the full path to the album
      in each item of the array. Ex: when $includepaths==false, the above array would be
      ['album', 'subalbum', 'my-album'], and with $includepaths==true, 
      ['album', 'album/subalbum', 'album/subalbum/my-album']
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
  $thumb_crop = zp_conf('thumb_crop');
  $thumb_size = zp_conf('thumb_size');
  $thumb_crop_width = zp_conf('thumb_crop_width');
  $thumb_crop_height = zp_conf('thumb_crop_height');
  $thumb_quality = zp_conf('thumb_quality');
  $image_default_size = zp_conf('image_size');
  $quality = zp_conf('image_quality');
  // Set up the parameters
  $thumb = $crop = false;
  list($size, $width, $height, $cw, $ch, $cx, $cy, $quality) = $args;
  
  if ($size == 'thumb') {
    $thumb = true;
    if ($thumb_crop) {
      $cw = min($thumb_crop_width, $thumb_size);
      $ch = min($thumb_crop_height, $thumb_size);
    }
    $size = round($thumb_size);
    $quality = round($thumb_quality);
    
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
    array_map('round_if_numeric', array($width, $height, $cw, $ch, $cx, $cy, $quality));
  if (empty($cw) && empty($ch)) $crop = false; else $crop = true;
  if (empty($quality)) $quality = zp_conf('image_quality');
  
  // Return an array of parameters used in image conversion.
  return array($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop);
}


// Checks if the input is numeric, rounds if so, otherwise returns false.
function round_if_numeric($num) {
  if (is_numeric($num)) {
    return round($num);
  } else {
    return false;
  }
}




/** Takes a user input string (usually from the query string) and cleans out
 HTML, null-bytes, and slashes (if magic_quotes_gpc is on) to prevent
 XSS attacks and other malicious user input, and make strings generally clean.
 @param $input_string is a string that needs cleaning.
 @param $deepclean is whether to replace HTML tags, javascript, etc.
 */
function sanitize($input_string, $deepclean=false) {
  if (get_magic_quotes_gpc()) $input_string = stripslashes($input_string);
  $input_string = str_replace(chr(0), " ", $input_string);
  if ($deepclean) $input_string = kses($input_string, array());
  return $input_string;
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
  if (zp_conf('mod_rewrite')) {
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
   $timezoneadjust = zp_conf('time_offset');

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


// Unzip function; ignores ZIP directory structure.
// Requires zziplib

function unzip($file, $dir) {
   $zip = zip_open($file);
   if ($zip) {
     while ($zip_entry = zip_read($zip)) {
       // Skip non-images in the zip file.
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
        $info = split($line, "::");
        $item = explode("::", $line);
        $themeinfo[trim($item[0])] = kses(trim($item[1]), zp_conf('allowed_tags'));
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
  $admin_email = zp_conf('admin_email');
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
  	      exit;
  	    }
  	  }
  	}
  
  	foreach($_GET as $k => $v){
  	  foreach($badStrings as $v2){
  	    if (strpos($v, $v2) !== false){
  	      header("HTTP/1.0 403 Forbidden");
  	      die("Forbidden");
  	      exit;
  	    }
  	  }
  	}
  
  	if( $headers == '' ) {
		$headers = "From: " . zp_conf('gallery_title') . "<zenphoto@" . $_SERVER['SERVER_NAME'] . ">";
	}

	// Convert to UTF-8
    if (zp_conf('charset') != 'UTF-8') {
        $subject = utf8::convert($subject, zp_conf('charset'));   
        $message = utf8::convert($message, zp_conf('charset'));   
  	}

  	// Send the mail
	UTF8::send_mail("Admin <" . zp_conf('admin_email') . ">", $subject, $message, $headers);
  }
}

?>
