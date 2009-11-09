<?php
/**
 * Dummy version of lib-Imagick as a place holder
 * @package core
 */

// force UTF-8 Ø

if (class_exists('Imagick')) {
	$_lib_Imagick_msg = gettext('<strong><em>Imagick</em></strong> is not yet supported.');
} else {
	$_lib_Imagick_msg = '';
}
?>