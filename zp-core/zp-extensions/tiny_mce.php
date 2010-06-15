<?php
/** Tiny MCE text editor
 * 
 * You can place your own additional custom configuration files within /zp-core/zp-extensions/tiny_mce/config (e.g. <filename>.js.php)
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Tiny MCE text editor for backend textareas");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.3.1'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---tiny_mce.php.html";
$option_interface = new tinymceOptions();

zp_register_filter('texteditor_config','tinymceConfigJS');

/**
 * Plugin option handling class
 *
 */
class tinymceOptions {

	function tinymceOptions() {
		setOptionDefault('tinymce_zenphoto', 'zenphoto-default.js.php');
		setOptionDefault('tinymce_zenpage', 'zenpage-default-full.js.php');
	}

	function getOptionsSupported() {
		$configarray = getTinyMCEConfigFiles();
		$options = array(gettext('Text editor configuration') => array('key' => 'tinymce_zenphoto', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $configarray,
										'desc' => 'Applies to <em>admin</em> editable text other than for Zenpage pages and news articles.'),
										gettext('Zenpage editor configuration') => array('key' => 'tinymce_zenpage', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $configarray,
										'desc' => 'Applies to editing on the Zenpage <em>pages</em> and <em>news</em> tabs.')
									);
		return $options;
	}
	
	function handleOption($option, $currentValue) {
	} 
}


function tinymceConfigJS($editorconfig,$mode) {
	if (empty($editorconfig)) {	// only if we get here first!
		$locale = getLocaleForTinyMCEandAFM();
		switch($mode) {
			case 'zenphoto':
				$editorconfig = getOption('tinymce_zenphoto');
				break;
			case 'zenpage';
			$editorconfig = getOption('tinymce_zenpage');
			break;
		}
		if (!empty($editorconfig)) {
			$editorconfig = SERVERPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/tiny_mce/config/'.$editorconfig;
			require_once($editorconfig);
		}
	}
	return $editorconfig;
}

function getTinyMCEConfigFiles() {
		$array = array();
		$files = glob(dirname(__FILE__).'/tiny_mce/config/*.js.php');
		$default = array(gettext('TinyMCE disabled') => '');
		$array = array_merge($array,$default);
		foreach($files as $file) {
			$filename = strrchr($file,'/');
			$filename = substr($filename, 1);
			$filearray = array($filename => $filename);
			//print_r($filearray);
			$array = array_merge($array,$filearray);
		}
		return $array;
	}
	
	//$array = getTinyMCEConfigFiles();
	//echo "<pre>"; print_r($array); echo "</pre>";
?>