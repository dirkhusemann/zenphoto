<?php

/* Plug-in for theme option handling
 * The Options page of admin.php tests for the presence of this file in a theme folder
 * If it is present admin.php links to it with a require_once call.
 * If it is not present, no theme options are displayed.
 *
 * Interface functions:
 *     getOptionsSupported()
 *        returns an array of the option names the theme supports
 *        the array is indexed by the option name. The value for each option is an array:
 *          'type' => 0 says for admin to use a standard textbox for the option
 *          'type' => 1 says for admin to use a standard checkbox for the option
 *          'type' => 2 will cause admin to call handleOption to generate the HTML for the option
 *          'desc' => text to be displayed for the option description.
 *
 *     handleOption($option, $currentValue)
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

	var $iSupport = array(	'Theme_logo' => array('type' => 0, 'desc' => 'The text for the them logo'),
							'Allow_search' => array('type' => 1, 'desc' => 'Set to enable search form.'),
							'Slideshow' => array('type' => 1, 'desc' => 'Set to enable slideshow for the <em>Smooth</em> personality.'),
							'Watermark_head_image' => array('type' => 1, 'desc' => 'Set to place a watermark on the heading image. (Image watermarking must be set.)'),
							'Theme_personality' => array('type' => 2, 'desc' => 'Select the theme personality'),
							'Theme_colors' => array('type' => 2, 'desc' => 'Set the colors of the theme')
	);

	function ThemeOptions() {
		setOptionDefault('Theme_logo', '');
		setOptionDefault('Allow_search', true);
		setOptionDefault('Slideshow', true);
		setOptionDefault('Watermark_head_image', true);
		setOptionDefault('Theme_personality', 'Image page');
		setOptionDefault('Theme_colors', 'effervescence');
	}

	function getOptionsSupported() {
		return $this->iSupport;
	}

	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'Theme_colors':
				$gallery = new Gallery();
				$theme = $gallery->getCurrentTheme();
				$themeroot = SERVERPATH . "/themes/$theme/styles";
				echo '<select id="themeselect" name="' . $option . '"' . ">\n";
				generateListFromFiles($currentValue, $themeroot , '.css');
				echo "</select>\n";
				break;

			case 'Theme_personality':
				echo '<select id="ef_personality" name="' . $option . '"' . ">\n";
				generateListFromArray(array($currentValue), array('Image page', 'Simpleviewer', 'Slimbox', 'Smoothgallery'));
				echo "</select>\n";
				break;
		}
	}

}
?>
