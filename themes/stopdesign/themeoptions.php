<?php

class ThemeOptions {

	var $iSupport = array('Allow_comments' => array('type' => 1, 'desc' => 'Set to enable comment section.'),
				'Allow_search' => array('type' => 1, 'desc' => 'Set to enable search form.'),
				'Gallery_description' => array('type' => 0, 'desc' => 'Place a brief descripton  your gallery here.'),
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
		$selector = array('Recent images', 'Random images');
		if ($option = 'Mini_slide_selector') {
			echo '<select id="themeselect" name="' . $option . '"' . ">\n";
			for ($i=0; $i<count($selector); $i++) {
				echo '<option value="' . $selector[$i] . '"';
				if ($currentValue == $selector[$i]) { 
					echo ' selected="selected"'; 
				}
				echo '>' . $selector[$i] . "</option>\n";
			}
			echo "</select>\n";
		}
	}

}
?>
