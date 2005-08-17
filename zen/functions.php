<?php


// functions.php - HEADERS NOT SENT YET!


require_once("config.php");

// For easy access to config vars.
function zp_conf($var) {
  global $_zp_conf_vars;
  return $_zp_conf_vars[$var];
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


// Simple mySQL timestamp formatting function.
function myts_date($format,$mytimestamp)
{
   // If your server is in a different time zone than you, set this.
   $timezoneadjust = 0;

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

// Determines if the input is an e-mail address. Stolen from WordPress, then fixed.
function is_email($input_email) {
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


// Takes a comment makes the body of an email.
function commentReply($str, $name, $albumtitle, $imagetitle) {
  $str = wordwrap($str, 75, '\n');
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
        $themeinfo[trim($item[0])] = strip_tags(trim($item[1]), zp_conf('allowed_tags'));
      }
    }
    return $themeinfo;
  } else {
    return false;
  }
}

?>
