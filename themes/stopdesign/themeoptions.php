<?php

class ThemeOptions {

	function ThemeOptions() {
		/* put any setup code needed here */
		setOptionDefault('Allow_search', true);
		setOptionDefault('Mini_slide_selector', 'Recent images');
		setOptionDefault('Gallery_image_crop_width', '210');
		setOptionDefault('Gallery_image_crop_height', '60');
	}
	
	function getOptionsSupported() {
		return array(	gettext('Allow search') => array('key' => 'Allow_search', 'type' => OPTION_TYPE_CHECKBOX,
													'desc' => gettext('Check to enable search form.')),
									gettext('Mini slide selector') => array('key' => 'Mini_slide_selector', 'type' => OPTION_TYPE_SELECTOR,
													'selections' => array(gettext('Recent images') => 'Recent images', gettext('Random images') => 'Random images'),
													'desc' => gettext('Select what you want for the six special slides.')),
									gettext('Gallery image crop width') => array('key' => 'Gallery_image_crop_width', 'type' => OPTION_TYPE_TEXTBOX, 'desc' => gettext('The width of image to crop in the gallery pages')),
									gettext('Gallery image crop height') => array('key' => 'Gallery_image_crop_height', 'type' => OPTION_TYPE_TEXTBOX, 'desc' => gettext('The height of image to crop in the gallery pages'))
									);
	}
	function handleOption($option, $currentValue) {
	}

}
?>
