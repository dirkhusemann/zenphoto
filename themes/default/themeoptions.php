<?php

/* Plug-in for theme option handling 
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 * 
*/

class ThemeOptions {
	
	function ThemeOptions() {
		setThemeOptionDefault('Allow_search', true);
		setThemeOptionDefault('Theme_colors', 'light'); 
	}
	
	function getOptionsSupported() {
		return array(	gettext('Allow search') => array('key' => 'Allow_search', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable search form.')),
									gettext('Theme colors') => array('key' => 'Theme_colors', 'type' => OPTION_TYPE_CUSTOM, 'desc' => gettext('Select the colors of the theme'))
								);
	}

	function handleOption($option, $currentValue) {
		if ($option == 'Theme_colors') {
			$theme = basename(dirname(__FILE__));
			$themeroot = SERVERPATH . "/themes/$theme/styles";
			echo '<select id="Default_themeselect_colors" name="' . $option . '"' . ">\n";
			generateListFromFiles($currentValue, $themeroot , '.css');
			echo "</select>\n";
		}
	}
}
?>
