<?php


// functions.php - HEADERS NOT SENT YET!

if (!file_exists(dirname(__FILE__) . "/zp-config.php")) {
  die ("<strong>Zenphoto error:</strong> zp-config.php not found. Perhaps you need to run <a href=\"zen/setup.php\">setup</a> (or migrate your old config.php)");
}

require_once(dirname(__FILE__) . "/zp-config.php");

// Set the version number.
$_zp_conf_vars['version'] = '1.0.4';

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
    $pos = strpos($string, " ", $length);
    return substr($string, 0, $pos) . "...";
  } else {
    return $string;
  }
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

/** Takes a user input string (usually from the query string) and cleans out
 HTML, null-bytes, and slashes (if magic_quotes_gpc is on) to prevent
 XSS attacks and other malicious user input, and make strings generally clean.
 */
function sanitize($user_input) {
  if (get_magic_quotes_gpc()) $user_input = stripslashes($user_input);
  $user_input = str_replace(chr(0), " ", $user_input);
  return $user_input;
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
       $count++;
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
    // Init
    $size = 0;
 
    // Trailing slash
    if (substr($directory, -1, 1) !== DIRECTORY_SEPARATOR) {
        $directory .= DIRECTORY_SEPARATOR;
    }

    $stack = array($directory);

    for ($i = 0, $j = count($stack); $i < $j; ++$i) {
        // Add to total size
        if (is_file($stack[$i])) {
            $size += filesize($stack[$i]);
            
        } else if (is_dir($stack[$i])) {
            // Read directory
            $dir = dir($stack[$i]);
            while (false !== ($entry = $dir->read())) {
                // No pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                // Add to stack
                $add = $stack[$i] . $entry;
                if (is_dir($stack[$i] . $entry)) {
                    $add .= DIRECTORY_SEPARATOR;
                }
                $stack[] = $add;
 
            }
            // Clean up
            $dir->close();
        }
        // Recount stack
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
