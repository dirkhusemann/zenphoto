<?php
/**
 * An example plugin demonstrating the use of the various "image_html" filters.
 * 
 * It makes use of some effects javascript created by Christian Effenberger which may
 * be downloaded from his site: //www.netzgesta.de/cvi/
 * 
 * However, these effects have not been thoroughly tested and may now work with some 
 * theme CSS. Use at our own discretion. 
 * 
 * No effects are distributed with this plugin to conform with CVI licensing constraints.
 * To add an effect to the plugin, download the zip file from the site. Extract the effect
 * Javascript and place it in the effenberger_effects folder (in the plugins folder.) 
 * For instance, to add the Reflex effect, download reflex.zip, unzip it, and place reflex.js
 * in the folder.
 * 
 * You may also add a text file of the same name as the effect to contain the default 
 * modifier parameter(s) for the effect. If no such file is present there will be no default
 * modifiers applied.
 * 
 * I have included some typical default text files. Feel free to change them.
 */
$plugin_description = gettext('Attaches "Effenberger effects" to images and thumbnails. This plugin is intended as an example for the use of the <em>image_html</em> filters.<br />Due to licensing considerations no effects are included with this plugin. See <a href="http://www.netzgesta.de/cvi/">CVI Astonishing Image Effects</a> by Christian Effenberger to select and download effects. Unzip the file you download and copy the <code><em>effect</em></code>.js file to the <code>effenberger_effects</code> folder in the plugins folders.<br />All testing was done with the <code>default</code> theme. No warranty is made that any particular effect will work with any particular theme.');
$plugin_URL = "http://www.netzgesta.de/cvi/";
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.7';
$option_interface = new image_effenberger();

zp_register_filter('standard_image_html', 'effenberger_std_images');
zp_register_filter('custom_image_html', 'effenberger_custom_images');
zp_register_filter('standard_album_thumb_html', 'effenberger_std_album_thumbs');
zp_register_filter('standard_image_thumb_html', 'effenberger_std_image_thumbs');
zp_register_filter('custom_album_thumb_html', 'effenberger_custom_album_thumbs');

addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/effenberger_effects/'.getOption('effenberger_effect').'.js"></script>');

/**
 * The option handler class
 *
 */
class image_effenberger {
	
	/**
	 * Class instantiation function
	 *
	 * @return effenberger
	 */
	function image_effenberger() {
		setOptionDefault('effenberger_std_images',1);
		setOptionDefault('effenberger_custom_images',1);
		setOptionDefault('effenberger_std_image_thumbs',1);
		setOptionDefault('effenberger_std_album_thumbs',1);
		setOptionDefault('effenberger_custom_image_thumbs',1);
		setOptionDefault('effenberger_custom_album_thumbs',1);
	}

		/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$curdir = getcwd();
		chdir($dir = SERVERPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/effenberger_effects/');
		$filelist = safe_glob('*.js');
		$extra = $list = array();
		foreach($filelist as $file) {
			$file = filesystemToInternal(str_replace('.js', '', $file));
			$list[$file] = $file;
			setOptionDefault('effenberger_effect',$file);
			if (file_exists($file.'.txt')) {
				$default = file_get_contents($file.'.txt');
				setOptionDefault('effenberger_modifier_'.$file, $default);
			}							
			$extra[sprintf(gettext('Modifiers for <em>%s</em>'),$file)] = 
									array('key' => 'effenberger_modifier_'.$file, 'type' => OPTION_TYPE_TEXTBOX,
												'desc' => sprintf(gettext('Additional parameters for the <em>%s</em> effect.'),$file)
												);
		}
		$std = array(	gettext('Effect') => array('key' => 'effenberger_effect', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list,
										'desc' => gettext('Select the effect to be used.')),
									gettext('Standard images') => array('key' => 'effenberger_std_images', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printDefaultSizedImage()</code> function.')),
									gettext('Custom images') =>array('key' => 'effenberger_custom_images', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>effect</em> to images shown via the custom image functions.')),
									gettext('Standard image thumbnails') =>array('key' => 'effenberger_std_image_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printImageThumb()</code> function.')),
									gettext('Standard album thumbnails') =>array('key' => 'effenberger_std_album_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printAlbumThumbImage()</code> function.')),
									gettext('Custom image thumbnails') =>array('key' => 'effenberger_custom_image_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>effect</em> to images shown via the custom image functions when <em>thumbstandin</em> is set.')),
									gettext('Custom album thumbnails') =>array('key' => 'effenberger_custom_album_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printCustomAlbumThumbImage()</code> function.'))
								);
								
		return array_merge($std, $extra);
	}
	
	/**
	 * handles any custom options
	 *
	 * @param string $option
	 * @param mixed $currentValue
	 */
	function handleOption($option, $currentValue) {
	}

}

function effenberger_insert_class($html) {
	$effect = getOption('effenberger_effect');
	$reflect = trim($effect.' '.getOption('effenberger_modifier_'.$effect));
	$i = strpos($html, 'class=');
	if ($i === false) {
		$i = strpos($html, '/>');
		$html = substr($html, 0, $i).'class="'.$reflect.'" />';
	} else {
		$quote = substr($html, $i+6,1);
		$i = strpos($html, $quote, $i+7);
		$html = substr($html, 0, $i).' '.$reflect.substr($html,$i);
	}
	return $html;
}

function effenberger_std_images($html) {
	if (getOption('effenberger_std_images')) {
		$html = effenberger_insert_class($html);
	}
	return $html;
}
function effenberger_custom_images($html, $thumbstandin) {
	if (getOption('effenberger_custom_images') && !$thumbstandin || getOption('effenberger_custom_image_thumbs') && $thumbstandin) {
		$html = effenberger_insert_class($html);
	}
	return $html;
}
function effenberger_std_album_thumbs($html) {
	if (getOption('effenberger_std_album_thumbs')) {
		$html = effenberger_insert_class($html);
	}
	return $html;
}
function effenberger_std_image_thumbs($html) {
	if (getOption('effenberger_std_image_thumbs')) {
		$html = effenberger_insert_class($html);
	}
	return $html;
}
function effenberger_custom_album_thumbs($html) {
	if (getOption('effenberger_custom_album_thumbs')) {
		$html = effenberger_insert_class($html);
	}
	return $html;
}

?>