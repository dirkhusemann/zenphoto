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
 * Javascript and place it in the effenberger_effects folder (in the global plugins folder.) 
 * For instance, to add the Reflex effect, download reflex.zip, unzip it, and place reflex.js
 * in the folder.
 * 
 * You may also add a text file of the same name as the effect to contain the default 
 * modifier parameter(s) for the effect. If no such file is present there will be no default
 * modifiers applied.
 * 
 * I have included some typical default text files. Feel free to change them.
 * 
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_description = sprintf(gettext('Attaches "Effenberger effects" to images and thumbnails. This plugin is intended as an example for the use of the <em>image_html</em> filters.<br /><strong>Due to licensing considerations no effects are included with this plugin.</strong> See <a href="http://www.netzgesta.de/cvi/">CVI Astonishing Image Effects</a> by Christian Effenberger to select and download effects. Unzip the file you download and copy the <code><em>effect</em></code>.js file to the <code>/%s/effenberger_effects</code> folder.<br />All testing was done with the <code>default</code> theme. No warranty is made that any particular effect will work with any particular theme.'),USER_PLUGIN_FOLDER);
$plugin_URL = "http://www.netzgesta.de/cvi/";
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 
$option_interface = new image_effenberger();

zp_register_filter('standard_image_html', 'effenberger_std_images');
zp_register_filter('custom_image_html', 'effenberger_custom_images');
zp_register_filter('standard_album_thumb_html', 'effenberger_std_album_thumbs');
zp_register_filter('standard_image_thumb_html', 'effenberger_std_image_thumbs');
zp_register_filter('custom_album_thumb_html', 'effenberger_custom_album_thumbs');

if (defined('OFFSET_PATH') && OFFSET_PATH == 0) {
	$selected_effects = array_unique(array(	getOption('effenberger_std_images'), getOption('effenberger_custom_images'),
	getOption('effenberger_std_album_thumbs'), getOption('effenberger_std_image_thumbs'),
	getOption('effenberger_custom_album_thumbs')));

	if (count($selected_effects) > 0) {
		foreach (array_unique($selected_effects) as $effect) {
			if (!empty($effect)) {
				addPluginScript('<script type="text/javascript" src="'.getPlugin('effenberger_effects/'.$effect.'.js',false,WEBPATH).'"></script>');
			}
		}
	}
}

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
	}

		/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$effectlist = getPluginFiles('*.js','effenberger_effects');
		$extra = $list = array();
		foreach($effectlist as $file=>$path) {
			$list[$file] = $file;
			setOptionDefault('effenberger_effect',$file);
			if (file_exists($txt = getPlugin('effenberger_effects/'.internalToFilesystem($file).'.txt'))) {
				$default = file_get_contents($txt);
				setOptionDefault('effenberger_std_images',$file);
				setOptionDefault('effenberger_custom_images',$file);
				setOptionDefault('effenberger_std_image_thumbs',$file);
				setOptionDefault('effenberger_std_album_thumbs',$file);
				setOptionDefault('effenberger_custom_image_thumbs',$file);
				setOptionDefault('effenberger_custom_album_thumbs',$file);
				setOptionDefault('effenberger_modifier_'.$file, $default);
			}							
			$extra[sprintf(gettext('Modifiers for <em>%s</em>'),$file)] = 
									array('key' => 'effenberger_modifier_'.$file, 'type' => OPTION_TYPE_TEXTBOX,
												'desc' => sprintf(gettext('Additional parameters for the <em>%s</em> effect.'),$file)
												);
		}
		if (count($list) == 0) {
			return array(gettext('No effects') => array('key' => 'effenberger_effect', 'type' => OPTION_TYPE_CUSTOM,
										'desc' => gettext('Due to licensing considerations no effects are included with this plugin. See <a href="http://www.netzgesta.de/cvi/">CVI Astonishing Image Effects</a> by Christian Effenberger to select and download effects. Unzip the file you download and copy the <code><em>effect</em></code>.js file to a folder named <code>effenberger_effects</code> in the global plugins folder.')));
		}
		$std = array(	gettext('Images (standard)') => array('key' => 'effenberger_std_images', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list, 'null_selection' => gettext('none'),
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printDefaultSizedImage()</code> function.')),
									gettext('Images (custom)') =>array('key' => 'effenberger_custom_images', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list, 'null_selection' => gettext('none'),
										'desc' => gettext('Apply <em>effect</em> to images shown via the custom image functions.')),
									gettext('Image thumbnails (standard)') =>array('key' => 'effenberger_std_image_thumbs', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list, 'null_selection' => gettext('none'),
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printImageThumb()</code> function.')),
									gettext('Album thumbnails (standard)') =>array('key' => 'effenberger_std_album_thumbs', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list, 'null_selection' => gettext('none'),
										'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printAlbumThumbImage()</code> function.')),
									gettext('Image thumbnails (custom)') =>array('key' => 'effenberger_custom_image_thumbs', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list, 'null_selection' => gettext('none'),
										'desc' => gettext('Apply <em>effect</em> to images shown via the custom image functions when <em>thumbstandin</em> is set.')),
									gettext('Album thumbnails  (custom)') =>array('key' => 'effenberger_custom_album_thumbs', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $list, 'null_selection' => gettext('none'),
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
		echo gettext('No <em>effects</em> scripts found.');
	}

}

function effenberger_insert_class($html, $effect) {
	$reflect = trim($effect.' '.getOption('effenberger_modifier_'.$effect));
	$i = strpos($html, 'class=');
	if ($i === false) {
		$i = strpos($html, '/>');
		$html = substr($html, 0, $i).'class="'.$reflect.'" '.substr($html,$i);
	} else {
		$quote = substr($html, $i+6,1);
		$i = strpos($html, $quote, $i+7);
		$html = substr($html, 0, $i).' '.$reflect.substr($html,$i);
	}
	return $html;
}

function effenberger_std_images($html) {
	if ($effect = getOption('effenberger_std_images')) {
		$html = effenberger_insert_class($html,	$effect);
	}
	return $html;
}
function effenberger_custom_images($html, $thumbstandin) {
	if (($effect = getOption('effenberger_custom_images')) && !$thumbstandin || getOption('effenberger_custom_image_thumbs') && $thumbstandin) {
		$html = effenberger_insert_class($html,	$effect);
	}
	return $html;
}
function effenberger_std_album_thumbs($html) {
	if ($effect = getOption('effenberger_std_album_thumbs')) {
		$html = effenberger_insert_class($html,	$effect);
	}
	return $html;
}
function effenberger_std_image_thumbs($html) {
	if ($effect = getOption('effenberger_std_image_thumbs')) {
		$html = effenberger_insert_class($html,	$effect);
	}
	return $html;
}
function effenberger_custom_album_thumbs($html) {
	if ($effect = getOption('effenberger_custom_album_thumbs')) {
		$html = effenberger_insert_class($html,	$effect);
	}
	return $html;
}

?>