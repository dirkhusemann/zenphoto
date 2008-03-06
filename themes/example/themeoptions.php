<?php
class ThemeOptions {

	function ThemeOptions() {
		/* put any setup code needed here */
		setOptionDefault('Allow_comments', true);
	setOptionDefault('Allow_search', true);
	}
	
	function getOptionsSupported() {
		return array(	gettext('Allow comments') => array('key' => 'Allow_comments', 'type' => 1, 'desc' => gettext('Set to enable comment section.')),
									gettext('Allow search') => array('key' => 'Allow_search', 'type' => 1, 'desc' => gettext('Set to enable search form.'))
								);
			}
	function handleOption($option, $currentValue) {}
}
?>
