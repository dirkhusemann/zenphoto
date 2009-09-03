<?php
$plugin_description = gettext("Attaches 'reflectins' to images and thumbnails. This is really an example plugin demonstrating the <em>image HTML</em> filters. The <code>reflection.js</code> does not work on all themes due to CSS issues.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.7';
$option_interface = new image_reflection();

zp_register_filter('standard_image_html', 'reflection_std_images');
zp_register_filter('custom_image_html', 'reflection_custom_images');
zp_register_filter('standard_album_thumb_html', 'reflection_std_album_thumbs');
zp_register_filter('standard_image_thumb_html', 'reflection_std_image_thumbs');
zp_register_filter('custom_album_thumb_html', 'reflection_custom_album_thumbs');

addPluginScript('<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/reflection/reflection.js"></script>');

/**
 * The option handler class
 *
 */
class image_reflection {
	
	/**
	 * Class instantiation function
	 *
	 * @return reflection
	 */
	function image_reflection() {
		setOptionDefault('reflection_std_images',1);
		setOptionDefault('reflection_custom_images',1);
		setOptionDefault('reflection_std_image_thumbs',1);
		setOptionDefault('reflection_std_album_thumbs',1);
		setOptionDefault('reflection_custom_image_thumbs',1);
		setOptionDefault('reflection_custom_album_thumbs',1);
		setOptionDefault('reflection_height','30');
		setOptionDefault('reflection_opacity','0.6');
	}

		/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Reflection height') => array('key' => 'reflection_height', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('The height of the <em>reflection</em>. A percentage of the original image&#151;a number between 0 and 100.')),
									gettext('Reflection opacity') => array('key' => 'reflection_opacity', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('The opacity of the <em>reflection</em>. Must be a decimal fraction between 0 and 1.')),
									gettext('Standard images') => array('key' => 'reflection_std_images', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>reflection</em> to images shown via the <code>printDefaultSizedImage()</code> function.')),
									gettext('Custom images') =>array('key' => 'reflection_custom_images', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>reflection</em> to images shown via the custom image functions.')),
									gettext('Standard image thumbnails') =>array('key' => 'reflection_std_image_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>reflection</em> to images shown via the <code>printImageThumb()</code> function.')),
									gettext('Standard album thumbnails') =>array('key' => 'reflection_std_album_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>reflection</em> to images shown via the <code>printAlbumThumbImage()</code> function.')),
									gettext('Custom image thumbnails') =>array('key' => 'reflection_custom_image_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>reflection</em> to images shown via the custom image functions when <em>thumbstandin</em> is set.')),
									gettext('Custom album thumbnails') =>array('key' => 'reflection_custom_album_thumbs', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('Apply <em>reflection</em> to images shown via the <code>printCustomAlbumThumbImage()</code> function.'))
								);
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

function reflection_insert_class($html) {
	$reflect = sprintf('reflect rheight%1$u ropacity%2$u',getOption('reflection_height'),getOption('reflection_opacity')*100);
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

function reflection_std_images($html) {
	if (getOption('reflection_std_images')) {
		$html = reflection_insert_class($html);
	}
	return $html;
}
function reflection_custom_images($html, $thumbstandin) {
	if (getOption('reflection_custom_images') && !$thumbstandin || getOption('reflection_custom_image_thumbs') && $thumbstandin) {
		$html = reflection_insert_class($html);
	}
	return $html;
}
function reflection_std_album_thumbs($html) {
	if (getOption('reflection_std_album_thumbs')) {
		$html = reflection_insert_class($html);
	}
	return $html;
}
function reflection_std_image_thumbs($html) {
	if (getOption('reflection_std_image_thumbs')) {
		$html = reflection_insert_class($html);
	}
	return $html;
}
function reflection_custom_album_thumbs($html) {
	if (getOption('reflection_custom_album_thumbs')) {
		$html = reflection_insert_class($html);
	}
	return $html;
}

?>