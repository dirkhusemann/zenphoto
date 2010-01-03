<?php
/**
 * Provides an example filter to quickly edit the visible and comment allowed image properties
 * 
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Provides mass edit of some image properties.");
$plugin_author = "Stephen Billard (sbillard)";
 $plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."--image_massedit.php.html";

zp_register_filter('admin_tabs', 'image_massedit_admin_tabs');

function image_massedit_admin_tabs($tabs, $current) {
	if (isset($_GET['album'])) {
		$albumname = sanitize($_GET['album'],3);
		if (isMyAlbum($albumname, ALBUM_RIGHTS)) {
			$album = new Album(new Gallery(), $albumname);
			if (count($album->getImages())>0 && !$album->isDynamic()) {
				if (!is_array($tabs['edit']['subtabs'])) $tabs['edit']['subtabs'] = array();
				$tabs['edit']['subtabs'] = array_merge($tabs['edit']['subtabs'], array(gettext('Mass edit') => PLUGIN_FOLDER.'/image-massedit/image-massedit_tab.php?page=edit&amp;tab=mass_edit&amp;album='.urlencode($albumname)));
			}
		}
	}
	return $tabs;
}
?>
