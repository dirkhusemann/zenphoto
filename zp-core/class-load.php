<?php
/*******************************************************************************
* Load the base classes (Image, Album, Gallery, etc.)                          *
*******************************************************************************/

require_once(dirname(__FILE__).'/functions-basic.php');
require_once(dirname(__FILE__).'/classes.php');
require_once(dirname(__FILE__).'/class-image.php');
require_once(dirname(__FILE__).'/class-album.php');
require_once(dirname(__FILE__).'/class-gallery.php');
require_once(dirname(__FILE__).'/class-search.php');
require_once(dirname(__FILE__).'/class-transientimage.php');
require_once(dirname(__FILE__).'/class-comment.php');

if (getOption('zp_plugin_zenpage')) {
	require_once(dirname(__FILE__).PLUGIN_FOLDER.'zenpage/zenpage-class-news.php');
	require_once(dirname(__FILE__).PLUGIN_FOLDER.'zenpage/zenpage-class-page.php');
}
			
// load the class & filter plugins
$class_optionInterface = array();
foreach (getEnabledPlugins() as $extension => $class) {
	if ($class > 1) {
		$option_interface = NULL;
		require_once(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . $extension);
		if (!is_null($option_interface)) {
			$class_optionInterface[$extension] = $option_interface;
		}
	}
}

?>