<?php
/**
 * These are the functions that setup needs before the database can be accessed (so it can't include
 * functions.php because that will cause a database connect error.)
 * @package setup
 */

// force UTF-8 Ø


include('version.php'); // Include the version info.
require_once(dirname(__FILE__).'/folder-definitions.php');

$const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
$const_webpath = str_replace("\\", '/', $const_webpath);
if ($const_webpath == '/') $const_webpath = '';
if (!defined('WEBPATH')) { define('WEBPATH', $const_webpath); }
if (!defined('SERVERPATH')) { define('SERVERPATH', dirname(dirname(__FILE__))); }
define('FILESYSTEM_CHARSET', 'ISO-8859-1');
define('ADMIN_RIGHTS',1);

function zp_getCookie($name) {
	if (isset($_SESSION[$name])) { return $_SESSION[$name]; }
	if (isset($_COOKIE[$name])) { return $_COOKIE[$name]; }
	return false;
}

function zp_setCookie($name, $value, $time=0, $path='/') {
	setcookie($name, $value, $time, $path);
	if ($time < 0) {
		unset($_SESSION[$name]);
		unset($_COOKIE[$name]);
	} else {
		$_SESSION[$name] = $value;
		$_COOKIE[$name] = $value;
	}
}
$_options = array();
function getOption($key) {
	global $_options;
	if (isset($_options[$key])) return $_options[$key];
	return NULL;
}

function setOption($key, $value, $persistent=true) {
	global $_options;
	$_options[$key] = $value;
}

function sanitize($input_string, $sanitize_level=3) {
	if (is_array($input_string)) {
		foreach ($input_string as $output_key => $output_value) {
			$output_string[$output_key] = sanitize_string($output_value, $sanitize_level);
		}
		unset($output_key, $output_value);
	} else {
		$output_string = sanitize_string($input_string, $sanitize_level);
	}
	return $output_string;
}

function sanitize_string($input_string, $sanitize_level) {
	require_once(dirname(__FILE__).'/lib-htmlawed.php');
	if (get_magic_quotes_gpc()) $input_string = stripslashes($input_string);
	if ($sanitize_level === 0) {
		$input_string = str_replace(chr(0), " ", $input_string);
	} else if ($sanitize_level === 1) {
		$allowed_tags = "(".getOption('allowed_tags').")";
		$allowed = parseAllowedTags($allowed_tags);
		if ($allowed === false) { $allowed = array(); }
		$input_string = kses($input_string, $allowed);
	} else if ($sanitize_level === 2) {
		$allowed = array();
		$input_string = kses($input_string, $allowed);
	// Full sanitation.  Strips all code.
	} else if ($sanitize_level === 3) {
		$allowed_tags = array();
		$input_string = kses($input_string, $allowed_tags);
	}
	return $input_string;
}

function printAdminFooter() {
	echo "<div id=\"footer\">";
	echo "\n  <a href=\"http://www.zenphoto.org\" title=\"".gettext('A simpler web photo album')."\">zen<strong>photo</strong></a>";
	echo " | <a href=\"http://www.zenphoto.org/support/\" title=\"".gettext('Forum').'">'.gettext('Forum')."</a> | <a href=\"http://www.zenphoto.org/trac/\" title=\"Trac\">Trac</a> | <a href=\"changelog.html\" title=\"".gettext('View Changelog')."\">Changelog</a>\n</div>";
}

function debugLog($message, $reset=false) {
	if ($reset) { $mode = 'w'; } else { $mode = 'a'; }
	$f = fopen(SERVERPATH . '/' . ZENFOLDER . '/debug_log.txt', $mode);
	fwrite($f, $message . "\n");
	fclose($f);
}

function debugLogArray($name, $source, $indent=0, $trail='') {
	if (is_array($source)) {
		$msg = str_repeat(' ', $indent)."$name => ( ";
		if (count($source) > 0) {
			foreach ($source as $key => $val) {
				if (strlen($msg) > 72) {
					debugLog($msg);
					$msg = str_repeat(' ', $indent);
				}
				if (is_array($val)) {
					if (!empty($msg)) debugLog($msg);
					debugLogArray($key, $val, $indent+5, ',');
					$msg = '';
				} else {
					$msg .= $key . " => " . $val. ', ';
				}
			}

			$msg = substr($msg, 0, strrpos($msg, ',')) . " )".$trail;
		} else {
			$msg .= ")";
		}
		debugLog($msg);
	} else {
		debugLog($name.' parameter is not an array.');
	}
}

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
 * Creates the body of a select list
 *
 * @param array $currentValue list of items to be flagged as checked
 * @param array $list the elements of the select list
 * @param bool $descending set true for a reverse order sort
 */
function generateListFromArray($currentValue, $list, $descending, $localize) {
	if ($localize) {
		$list = array_flip($list);
		if ($descending) {
			arsort($list);
		} else {
			natcasesort($list);
		}
		$list = array_flip($list);
	} else {
		if ($descending) {
			rsort($list);
		} else {
			natcasesort($list);
		}
	}
	foreach($list as $key=>$item) {
		echo '<option value="' . $item . '"';		
		if (in_array($item, $currentValue)) {
			echo ' selected="selected"';
		}
		if ($localize) $display = $key; else $display = $item;
		echo '>' . $display . "</option>"."\n";
	}
}

function zp_loggedin() {
	return ADMIN_RIGHTS;
}

function mkdir_recursive($pathname, $mode=0777) {
	if (!is_dir(dirname($pathname))) mkdir_recursive(dirname($pathname), $mode);
	return is_dir($pathname) || @mkdir($pathname, $mode & CHMOD_VALUE);
}


?>