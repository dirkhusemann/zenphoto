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

	function ThemeOptions() {
		setOptionDefault('Theme_logo', '');
		setOptionDefault('Allow_comments', true);
		setOptionDefault('Allow_search', true);
		setOptionDefault('Slideshow', true);
		setOptionDefault('Graphic_logo', 'logo');
		setOptionDefault('Watermark_head_image', true);
		setOptionDefault('Theme_personality', 'Image page');
		setOptionDefault('Theme_colors', 'effervescence');
	}

	function getOptionsSupported() {
		return array(	gettext('Theme logo') => array('key' => 'Theme_logo', 'type' => 0, 'desc' => gettext('The text for the them logo')),
									gettext('Allow comments') => array('key' => 'Allow_comments', 'type' => 1, 'desc' => gettext('Set to enable comment section.')),
									gettext('Watermark head_image') => array('key' => 'Watermark_head_image', 'type' => 1, 'desc' => gettext('Set to place a watermark on the heading image. (Image watermarking must be set.)')),
									gettext('Allow search') => array('key' => 'Allow_search', 'type' => 1, 'desc' => gettext('Set to enable search form.')),
									gettext('Slideshow') => array('key' => 'Slideshow', 'type' => 1, 'desc' => gettext('Set to enable slideshow for the <em>Smooth</em> personality.')),
									gettext('Graphic logo') => array('key' => 'Graphic_logo', 'type' => 2, 'desc' => gettext('Select a logo (PNG files in the images folder) or leave empty for text logo.')),
									gettext('Theme personality') => array('key' => 'Theme_personality', 'type' => 2, 'desc' => gettext('Select the theme personality')),
									gettext('Theme colors') => array('key' => 'Theme_colors', 'type' => 2, 'desc' => gettext('Set the colors of the theme'))
								);
	}

	function handleOption($option, $currentValue, $alb="") {
		switch ($option) {
			case 'Theme_colors':
				$gallery = new Gallery();
				$theme = $gallery->getCurrentTheme();
				$themeroot = SERVERPATH . "/themes/$theme/styles";
				echo '<select id="themeselect" name="' . $alb . $option . '"' . ">\n";
				generateListFromFiles($currentValue, $themeroot , '.css');
				echo "</select>\n";
				break;

			case 'Theme_personality':
				echo '<select id="ef_personality" name="' . $alb . $option . '"' . ">\n";
				generateListFromArray(array($currentValue), array(gettext('Image page') => 'Image page', gettext('Simpleviewer') => 'Simpleviewer', gettext('Slimbox') => 'Slimbox', gettext('Smoothgallery') => 'Smoothgallery'));
				echo "</select>\n";
				break;
				
			case 'Graphic_logo':
				$gallery = new Gallery();
				$theme = $gallery->getCurrentTheme();
				$themeroot = SERVERPATH . "/themes/$theme/images";
				echo '<select id="themeselect" name="' . $alb . $option . '"' . ">\n";
				echo '<option></option>';
				generateListFromFiles($currentValue, $themeroot , '.png');
				echo "</select>\n";
				break;
		}
	}

}
?>
