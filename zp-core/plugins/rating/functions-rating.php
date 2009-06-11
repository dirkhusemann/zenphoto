<?php
/**
 * rating plugin - utility functions
 * @author Stephen Billard (sbillard)
 * @version 2.0.0
 * @package plugins
 */

$_rating_current_IPlist = array();
/**
 * Returns the last vote rating from an IP or false if 
 * no vote on record
 *
 * @param unknown_type $ip
 * @param unknown_type $usedips
 * @return unknown
 */
function getRatingByIP($ip, $usedips) {
	global $_rating_current_IPlist;
	$rating = 0;
	if (empty($_rating_current_IPlist)) {
		if (!empty($usedips)) {
			$_rating_current_IPlist = unserialize($usedips);
			if (!empty($_rating_current_IPlist)) {
				foreach ($_rating_current_IPlist as $element=>$value) {
					if (!is_numeric($element)) {
						if (array_key_exists($ip, $_rating_current_IPlist)) {
							return $_rating_current_IPlist[$ip];
						}
					} else {
						$rating = $rating + $value;
					}
				}
			}
			if (in_array($ip, $_rating_current_IPlist)) {
				return $rating / count($_rating_current_IPlist); // no individual data, assume the average
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