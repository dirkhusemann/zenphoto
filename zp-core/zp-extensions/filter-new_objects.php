<?php
/**
 * Adds data to newly created images and albums.
 * This plugin, while functional, is intended primarily as an example of the 
 * use of the new_album and new_image filters.
 * 
 * @package plugins
 */
$plugin_is_filter = -5;
$plugin_description = gettext('Adds admin user who uploaded image to the description of the image (and to the album description if the album did not already exist.)').' '.
											gettext('For this to work with ZIP files you must have ZZIPlib configured in your PHP.').
											(function_exists('zip_open') ? '':' '.gettext('<strong>You do not have ZZIPlib configured.</strong>'));
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---filter-new_objects.php.html";

zp_register_filter('new_album', 'updateAlbum');
zp_register_filter('new_image', 'updateImage');

/**
 * Adds user who caused the album to be created to the description of the album
 *
 * @param object $album
 * @return object
 */
function updateAlbum($album) {
	global $_zp_current_admin;
	if (zp_loggedin()) {
		$bt = debug_backtrace();
		foreach($bt as $b) {
			if (isset($b['file']) && basename($b['file']) == 'admin-upload.php') {
				$album->setDesc(gettext('Created by: ').$_zp_current_admin['name']);
			}
		}
	}
	$album->save();
	return $album;
}

/**
 * Adds user who uploaded the image to the description of the image
 *
 * @param object $image
 * @return object
 */
function updateImage($image) {
	global $_zp_current_admin;
	if (zp_loggedin()) {
		$bt = debug_backtrace();
		foreach($bt as $b) {
			if (isset($b['file']) && basename($b['file']) == 'admin-upload.php') {
				$newdesc = $image->getDesc();
				if (empty($newdesc)) {
					$newdesc = gettext('Uploaded by: ').$_zp_current_admin['name'];
				} else {
					$newdesc .= ' ('.gettext('Uploaded by: ').$_zp_current_admin['name'].')';
				}
				$image->setDesc($newdesc);
			}
		}
	}
	$image->save();
	return $image;
}
?>