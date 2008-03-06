<?php

class ThemeOptions {

	var $iSupport = array('Allow_comments' => array('type' => 1, 'desc' => 'Set to enable comment section.'),
				'Allow_search' => array('type' => 1, 'desc' => 'Set to enable search form.'),
				'Gallery_description' => array('type' => 2, 'desc' => 'Place a brief descripton  your gallery here.'),
				'Mini_slide_selector' => array('type' => 2, 'desc' => 'Select what you want for the six special slides.')
				);

	function ThemeOptions() {
		/* put any setup code needed here */
		setOptionDefault('Allow_comments', true);
		setOptionDefault('Allow_search', true);
		setOptionDefault('Gallery_description', 'You can insert your Gallery description using on the Admin Options tab.');
		setOptionDefault('Mini_slide_selector', 'Recent images');
	}
	
	function getOptionsSupported() {
		return $this->iSupport;
	}
	function handleOption($option, $currentValue) {
		$selector = array(gettext('Recent images') => 'Recent images', gettext('Random images') => 'Random images');
		switch ($option) {
			case 'Mini_slide_selector':
				echo '<select id="themeselect" name="' . $option . '"' . ">\n";
				generateListFromArray(array($currentValue), $selector);
				echo "</select>\n";
				break;
			case 'Gallery_description':
				echo '<textarea name="Gallery_description" cols="60"'.
					'rows="4" style="width: 360px">'.$currentValue.'</textarea>';
				break;
		}
	}

}
?>
