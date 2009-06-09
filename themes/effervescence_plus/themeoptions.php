<?php

/* Plug-in for theme option handling
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 *
 */

require_once(SERVERPATH . "/" . ZENFOLDER . "/admin-functions.php");

class ThemeOptions {

	function ThemeOptions() {
		setOptionDefault('Theme_logo', '');
		setOptionDefault('Allow_search', true);
		setOptionDefault('enable_album_zipfile', false);
		setOptionDefault('Slideshow', true);
		setOptionDefault('Graphic_logo', 'logo');
		setOptionDefault('Watermark_head_image', true);
		setOptionDefault('Theme_personality', 'Image page');
		setOptionDefault('Theme_colors', 'effervescence');
	}

	function getOptionsSupported() {
		return array(	gettext('Theme logo') => array('key' => 'Theme_logo', 'type' => OPTION_TYPE_TEXTBOX, 'multilingual' => 1, 'desc' => gettext('The text for the theme logo')),
									gettext('Watermark head image') => array('key' => 'Watermark_head_image', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to place a watermark on the heading image. (Image watermarking must be set.)')),
									gettext('Zipfile download') => array('key' => 'enable_album_zipfile', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable album zipfile download link.')),
									gettext('Allow search') => array('key' => 'Allow_search', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable search form.')),
									gettext('Slideshow') => array('key' => 'Slideshow', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable slideshow for the <em>Smoothgallery</em> personality.')),
									gettext('Graphic logo') => array('key' => 'Graphic_logo', 'type' => OPTION_TYPE_CUSTOM, 'desc' => gettext('Select a logo (PNG files in the images folder) or leave empty for text logo.')),
									gettext('Theme personality') => array('key' => 'Theme_personality', 'type' => OPTION_TYPE_SELECTOR, 'selections' => array(gettext('Image page') => 'Image page', gettext('Simpleviewer') => 'Simpleviewer', gettext('Slimbox') => 'Slimbox', gettext('Smoothgallery') => 'Smoothgallery'),
													'desc' => gettext('Select the theme personality')),
									gettext('Theme colors') => array('key' => 'Theme_colors', 'type' => OPTION_TYPE_CUSTOM, 'desc' => gettext('Select the colors of the theme'))
								);
	}

	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'Theme_colors':
				$theme = basename(dirname(__FILE__));
				$themeroot = SERVERPATH . "/themes/$theme/styles";
				echo '<select id="themeselect" name="' . $option . '"' . ">\n";
				generateListFromFiles($currentValue, $themeroot , '.css');
				echo "</select>\n";
				break;

			case 'Graphic_logo':
				$gallery = new Gallery();
				$theme = $gallery->getCurrentTheme();
				$themeroot = SERVERPATH . "/themes/$theme/images";
				echo '<select id="themeselect" name="' . $option . '"' . ">\n";
				echo '<option value=""></option>';
				generateListFromFiles($currentValue, $themeroot , '.png');
				echo "</select>\n";
				break;
		}
	}
}
?>
