<?php
/**
 * translates accented characters to unaccented ones
 * @package plugins
 */
$plugin_description = gettext("Filter out albums that we do not want shown.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-albums.php.html";

register_filter('album_filter', 'filterAlbums');

/**
 * Removes unwanted albums from the list found on Disk
 *
 * @param array $album_array list of albums found
 * @return array
 */
function filterAlbums($album_array) {
	$new_list = array();
	foreach ($album_array as $album) {
		if ($album != 'empty') {
			$new_list[] = $album;
		}
	}
	return $new_list;
}
?>