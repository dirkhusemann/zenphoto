<?php
/**
 * Dummy version of lib-Imagick as a place holder
 * @package core
 */

// force UTF-8 Ø

$_zp_graphics_optionhandlers[] = new lib_Imagik_Options(); // register option handler
/**
 * Option class for lib-GD
 *
 */
class lib_Imagik_Options {

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array();
	}
	function canLoadMsg() {
		if (class_exists('Imagick')) {
			return gettext('<strong><em>Imagick</em></strong> is not yet supported.');
		} else {
			return '';
		}
	}
}

?>