<?php
/**
 * Provides an example of the use of the custom data filters
 * 
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Example filter for custom data.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-custom_data.php.html";

register_filter('save_image_custom_data', 'save_image', 2);
register_filter('edit_image_custom_data', 'edit_image', 3);
register_filter('save_album_custom_data', 'save_album', 2);
register_filter('edit_album_custom_data', 'edit_album', 3);
register_filter('save_comment_custom_data', 'save_comment');
register_filter('edit_comment_custom_data', 'edit_comment', 2);

/**
 * Returns a processed custom data item
 * called when an image is saved on the backend
 *
 * @param string $discard always empty
 * @param int $i prefix for the image being saved
 * @return string
 */
function save_image($discard, $i) {
	return sanitize($_POST[$i.'-custom_data'], 1);
}

/**
 * Returns table row(s) for the edit of an image custom data field
 *
 * @param string $discard always empty
 * @param int $currentimage prefix for the image being edited
 * @param object $image the image object
 * @return string
 */
function edit_image($discard, $image, $currentimage) {
	return 
		'<tr>
			<td valign="top">'.gettext("Special data:").'</td>
			<td><textarea name="'.$currentimage.'-custom_data" cols="'.TEXTAREA_COLUMNS.'"	rows="6">'.htmlentities($image->get('custom_data'),ENT_COMPAT,getOption("charset")).'</textarea></td>
		</tr>';
}

/**
 * Returns a processed album custom data item
 * called when an album is saved on the backend
 *
 * @param string $discard always empty
 * @param int $prefix the prefix for the album being saved
 * @return string
 */
function save_album($discard, $prefix) {
	return sanitize($_POST[$prefix.'x_album_custom_data'], 1);
}

/**
 * Returns table row(s) for the edit of an album custom data field
 *
 * @param string $discard always empty
 * @param int $prefix prefix of the album being edited
 * @param object $album the album object
 * @return string
 */
function edit_album($discard, $album, $prefix) {
	return
		'<tr>
			<td align="left" valign="top">'.gettext("Special data:").'</td>
			<td><textarea name="'.$prefix.'x_album_custom_data" cols="'.TEXTAREA_COLUMNS.'"	rows="6">'.htmlentities($album->get('custom_data'),ENT_COMPAT,getOption("charset")).'</textarea></td>
		</tr>';
}

/**
 * Returns a processed comment custom data item
 * Called when a comment edit is saved
 *
 * @param string $discard always empty
 * @return string
 */
function save_comment($discard) {
	return sanitize($_POST['comment_custom_data'], 1);
}

/**
 * Returns table row(s) for edit of a comment's custom data
 *
 * @param string $discard always empty
 * @return string
 */
function edit_comment($discard, $raw) {
	return
		'<tr>
			<td align="left" valign="top">'.gettext("Extra information:").'</td>
			<td><textarea name="comment_custom_data" cols="60"	rows="6">'.htmlentities($raw,ENT_COMPAT,getOption("charset")).'</textarea></td>
		</tr>';
}

?>