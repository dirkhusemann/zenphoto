<?php
/**
 * rating plugin - utility functions
 * @author Stephen Billard (sbillard)
 * @version 2.0.0
 * @package plugins
 */
$_rating_current_IPlist = array();
/**
 * Checks if an IP address has already voted
 *
 * @param string $ip IP address to be checked
 * @param int $id ID of the object in question
 * @param string $dbtable prefixed database table that contains the object
 * @return bool
 */
function checkForIp($ip, $id, $dbtable) {
	global $_rating_current_IPlist;
	$_rating_current_IPlist = array();
	$IPlist = query_single_row("SELECT * FROM $dbtable WHERE id= $id");
	if (is_array($IPlist)) {
		if (!empty($IPlist['used_ips'])) {
			$_rating_current_IPlist = unserialize($IPlist['used_ips']);
			if (!empty($_rating_current_IPlist)) {
				foreach ($_rating_current_IPlist as $element=>$value) {
					break;
				}
				if (!is_numeric($element)) {
					if (array_key_exists($ip, $_rating_current_IPlist)) {
						return $_rating_current_IPlist[$ip];
					}
				} else {
					if (in_array($ip, $_rating_current_IPlist)) {
						return $IPlist['rating']; // use the average when old data.
					}
				}
			}
		}
	}
	return false;
}
/**
 * returns the $object for the current loaded page
 *
 * @param object $object
 * @return object
 */
function getCurrentPageObject() {
	global $_zp_gallery_page, $_zp_current_album, $_zp_current_image, $_zp_current_zenpage_news, $_zp_current_zenpage_page;
	switch ($_zp_gallery_page) {
		case 'album.php':
			return $_zp_current_album;
		case 'image.php':
			return $_zp_current_image;
		case 'news.php':
			return $_zp_current_zenpage_news;
		case 'pages.php':
			return $_zp_current_zenpage_page;
		default:
			die(sprintf(gettext('%s is not a valid getRating() context'), $_zp_gallery_page));
	}
}
?>