<?php
/**
 * These are the functions that setup needs before the database can be accessed (so it can't include 
 * functions.php because that will cause a database connect error.)
 */

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

function sanitize($input_string, $deepclean=false) {
	if (get_magic_quotes_gpc()) $input_string = stripslashes($input_string);
	$input_string = str_replace(chr(0), " ", $input_string);
	if ($deepclean) $input_string = kses($input_string, array());
	return $input_string;
}

function printAdminFooter() {
	echo "<div id=\"footer\">";
	echo "\n  <a href=\"http://www.zenphoto.org\" title=\"A simpler web photo album\">zen<strong>photo</strong></a>";
	echo " | <a href=\"http://www.zenphoto.org/support/\" title=\"Forum\">Forum</a> | <a href=\"http://www.zenphoto.org/trac/\" title=\"Trac\">Trac</a> | <a href=\"changelog.html\" title=\"View Changelog\">Changelog</a>\n</div>";
}
?>