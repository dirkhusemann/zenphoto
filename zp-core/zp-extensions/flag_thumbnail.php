<?php
/**
 * 
 * Apply a lock image over thumbnails of password protected albums
 * 
 * To use simply enable the plugin.
 * 
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_description = sprintf(gettext('Apply <img src="%1$s/images/lock.png" alt=""/> over thumbnails of <em>password protected</em> albums and <img src="%1$s/images/action.png" alt=""/> over thumbnails of <em>not published</em> albums and <em>not visible</em> images.'),WEBPATH.'/'.ZENFOLDER);
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---flag_thumbnail.php.html";

zp_register_filter('standard_image_thumb_html', 'flag_thumbnail_std_image_thumbs');
zp_register_filter('standard_album_thumb_html', 'flag_thumbnail_std_album_thumbs', 1);
zp_register_filter('custom_album_thumb_html', 'flag_thumbnail_custom_album_thumbs', 1);
zp_register_filter('custom_image_html', 'flag_thumbnail_custom_images', 1);

function flag_thumbnail_insert_class($html) {
	$i = strpos($html, 'class=');
	if ($i !== false) {
		$img = '';
		if (strpos($html, 'password_protected', $i+7) !== false) {
			$img = WEBPATH.'/'.ZENFOLDER.'/images/lock.png';
		} else if (strpos($html, 'not_visible', $i+7) !== false) {
			$img = WEBPATH.'/'.ZENFOLDER.'/images/action.png';
		}
		if ($img) {
			$html = '<span style="position:relative; display:block;">'."\n".
							$html."\n".
							'<img src="'.$img.'" alt="" style="position: absolute;top: 4px;left: 4px;"/>'."\n".'</span>'."\n";
		}
	}
	return $html;
}

function flag_thumbnail_custom_images($html, $thumbstandin) {
	if ($thumbstandin) {
		$html = flag_thumbnail_insert_class($html);
	}
	return $html;
}
function flag_thumbnail_std_image_thumbs($html) {
	$html = flag_thumbnail_insert_class($html);
	return $html;
}
function flag_thumbnail_std_album_thumbs($html) {
	$html = flag_thumbnail_insert_class($html);
	return $html;
}
function flag_thumbnail_custom_album_thumbs($html) {
	$html = flag_thumbnail_insert_class($html);
	return $html;
}

?>