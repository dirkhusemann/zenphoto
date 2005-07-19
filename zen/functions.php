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


function printErrorPage($title, $text) {
  $html = 
"
<html><head><title>zenphoto Error: $title</title>
<style type=\"text/css\">
  body { font-family: Arial, Helvetica, sans-serif; margin: 0px; font-size: 8pt; }
  #main { margin: 15%; padding: 10px 20px; background-color: #f6f6f6; border: 1px solid #ddd; }
  hr { height: 0px; border: 0px; border-top: 1px solid #ccc; }
</style>
</head>
<body>
  <div id=\"main\">
  <h1 style=\"color: #C40;\">$title</h1>
  <p><strong>$text</strong></p>
  </div>
</body>
</html>

";
  echo $html;
}

?>
