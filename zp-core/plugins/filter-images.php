<?php
/**
 * filters image lists to eliminate undesired ones
 * 
 * @package plugins
 */
$plugin_description = gettext("Filter out images that we do not want displayed.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-images.php.html";

register_filter('image_filter', 'filterImages');

/**
 * Removes unwanted images from the list returned from the filesystem
 *
 * @param array $image_array the list of images found
 * @return array
 */
function filterImages($image_array) {
	$new_list = array();
	foreach ($image_array as $image) {
		if ($image != 'undesired_image.xxx') {
			$new_list[] = $image;
		}
	}
	return $new_list;
}

?>