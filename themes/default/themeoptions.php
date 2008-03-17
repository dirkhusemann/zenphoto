<?php

/* Plug-in for theme option handling 
 * The Options page of admin.php tests for the presence of this file in a theme folder
 * If it is present admin.php links to it with a require_once call.
 * If it is not present, no theme options are displayed.
 * 
 * Interface functions:
 *     getOptionsSupported()
 *        returns an array of the option names the theme supports
 *        the array is indexed by the option name. The value for each option
 *          a value of 0 says for admin to use standard processing for the option
 *          a value of 1 will cause admin to call handleThemeOption to generate the HTML for the option
 *             
 *     handleThemeOption($option, $currentValue)
 *       $option is the name of the option being processed
 *       $currentValue is the "before" value of the option
 *
 *       this function is called by admin from within the table row/column where the option field is placed
 *       It must write the HTML that does the option handling UI
 *
 *       the version below provides a dropdown list of all the CSS files in the theme folder. It is used by themes
 *       which support selectable CSS files for different color schemes.
 */

require_once(SERVERPATH . "/" . ZENFOLDER . "/admin-functions.php");

class ThemeOptions {
	
	function ThemeOptions() {
		setOptionDefault('Allow_comments', true);
		setOptionDefault('Allow_ratings', true);
		setOptionDefault('Allow_search', true);
		setOptionDefault('Theme_colors', 'light'); 
	}
	
	function getOptionsSupported() {
		return array(	gettext('Allow_comments') => array('key' => 'Allow_comments', 'type' => 1, 'desc' => gettext('Set to enable comment section.')),
									gettext('Allow_ratings') => array('key' => 'Allow_ratings', 'type' => 1, 'desc' => gettext('Set to enable album and image ratings.')),
									gettext('Allow_search') => array('key' => 'Allow_search', 'type' => 1, 'desc' => gettext('Set to enable search form.')),
									gettext('Theme_colors') => array('key' => 'Theme_colors', 'type' => 2, 'desc' => gettext('Set the colors of the theme'))
								);
	}

	function handleOption($option, $currentValue, $alb="") {
		if ($option == 'Theme_colors') {
			$gallery = new Gallery();
			$theme = $gallery->getCurrentTheme();
			$themeroot = SERVERPATH . "/themes/$theme/styles";
			echo '<select id="themeselect" name="' . $alb . $option . '"' . ">\n";
			generateListFromFiles($currentValue, $themeroot , '.css');
			echo "</select>\n";
		}
	}
}
?>
