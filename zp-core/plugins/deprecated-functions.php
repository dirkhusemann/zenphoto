<?php
/*
 * These functions have been removed from mainstream Zenphoto as as they have been
 * supplanted. 
 * 
 * They are not maintained and they are not guarentted to function correctly with the
 * current version of Zenphoto.
 * 
 * @package plugins
 */
$plugin_description = gettext("Deprecated Zenphoto functions. These functions have been removed from mainstream Zenphoto as as they have been supplanted. They are not maintained and they are not guarentted to function correctly with the current version of Zenphoto.");
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---deprecated-functions.php.html";


/**
 * THIS FUNCTION IS DEPRECATED! Use getZenpageHitcounter()!
 * 
 * Increments (optionally) and returns the hitcounter for a news category (page 1), a single news article or a page
 * Does not increment the hitcounter if the viewer is logged in as the gallery admin.
 * Also does currently not work if the static cache is enabled
 *
 * @param string $option "pages" for a page, "news" for a news article, "category" for a news category (page 1 only)
 * @param bool $viewonly set to true if you don't want to increment the counter.
 * @param int $id Optional record id of the object if not the current image or album
 * @return string
 */
function zenpageHitcounter($option='pages', $viewonly=false, $id=NULL) {
	global $_zp_current_zenpage_page, $_zp_current_zenpage_news, $_zp_loggedin;
	trigger_error(gettext('hitcounter is deprecated. Use getZenpageHitcounter().'), E_USER_NOTICE);
	switch($option) {
		case "pages":
			if (is_null($id)) {
				$id = getPageID();
			}
			$dbtable = prefix('zenpage_pages');
			$doUpdate = true;
			break;
		case "category":
			if (is_null($id)) {
				$id = getCurrentNewsCategoryID();
			}
			$dbtable = prefix('zenpage_news_categories');
			$doUpdate = getCurrentNewsPage() == 1; // only count initial page for a hit on an album
			break;
		case "news":
			if (is_null($id)) {
				$id = getNewsID();
			}
			$dbtable = prefix('zenpage_news');
			$doUpdate = true;
			break;
	}
	if(($option == "pages" AND is_Pages()) OR ($option == "news" AND is_NewsArticle()) OR ($option == "category" AND is_NewsCategory())) {
		if (($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) || $viewonly) { $doUpdate = false; }
		$hitcounter = "hitcounter";
		$whereID = " WHERE `id` = $id";
		$sql = "SELECT `".$hitcounter."` FROM $dbtable $whereID";
		if ($doUpdate) { $sql .= " FOR UPDATE"; }
		$result = query_single_row($sql);
		$resultupdate = $result['hitcounter'];
		if ($doUpdate) {
			$resultupdate++;
			query("UPDATE $dbtable SET `".$hitcounter."`= $resultupdate $whereID");
		}
		return $resultupdate;
	}
}

/**
 * Prints the image rating information for the current image
 * Deprecated:
 * Included for forward compatibility--use printRating() directly
 *
 */
function printImageRating($object=NULL) {
	global $_zp_current_image;
	if (is_null($object)) $object = $_zp_current_image;
	printRating(3, $object);
}

/**
 * Prints the album rating information for the current image
 * Deprecated:
 * Included for forward compatibility--use printRating() directly
 *
 */
function printAlbumRating($object=NULL) {
	global $_zp_current_album;
	if (is_null($object)) $object = $_zp_current_album;
	printRating(3, $object);
}

/**
 * Prints image data. 
 * 
 * Deprecated, use printImageMetadata
 *
 */
function printImageEXIFData() {
	trigger_error(gettext('printImageEXIFData is deprecated. Use printImageMetadata().'), E_USER_NOTICE);
	if (isImageVideo()) {
	} else {
		printImageMetadata(); 
	} 
}


/**
 * This function is considered deprecated. 
 * Please use the new replacement get/printCustomSizedImageMaxSpace(). 
 * 
 * Prints out a sized image up to $maxheight tall (as width the value set in the admin option is taken)
 *
 * @param int $maxheight how bif the picture should be
 */
function printCustomSizedImageMaxHeight($maxheight) {
	trigger_error(gettext('printCustomSizedImageMaxHeight is deprecated. Use printCustomSizedImageMaxSpace().'), E_USER_NOTICE);
	if (getFullWidth() === getFullHeight() OR getDefaultHeight() > $maxheight) {
		printCustomSizedImage(getImageTitle(), null, null, $maxheight, null, null, null, null, null, null);
	} else {
		printDefaultSizedImage(getImageTitle());
	}
}

/**
 * Retrieves the date of the current comment.
 * 
 * Deprecated--use getCommentDateTime()
 * 
 * Returns a formatted date
 *
 * @param string $format how to format the result
 * @return string
 */
function getCommentDate($format = NULL) {
	trigger_error(gettext('getCommentDate is deprecated. Use getCommentDateTime().'), E_USER_NOTICE);
	if (is_null($format)) {
		$format = getOption('date_format');
		$time_tags = array('%H', '%I', '%R', '%T', '%r');
		foreach ($time_tags as $tag) { // strip off any time formatting
			$t = strpos($format, $tag);
			if ($t !== false) {
				$format = trim(substr($format, 0, $t));
			}
		}
	}
	global $_zp_current_comment;
	return myts_date($format, $_zp_current_comment['date']);
}

/**
 * Retrieves the time of the current comment.
 * 
 * Deprecated--use getCommentDateTime()
 * 
 * Returns a formatted time

 * @param string $format how to format the result
 * @return string
 */
function getCommentTime($format = '%I:%M %p') {
	trigger_error(gettext('getCommentTime is deprecated. Use getCommentDateTime().'), E_USER_NOTICE);
	global $_zp_current_comment;
	return myts_date($format, $_zp_current_comment['date']);
}

/**
 * Returns the hitcounter for the page viewed (image.php and album.php only).
 * Deprecated, use getHitcounter()
 *
 * @param string $option "image" for image hit counter (default), "album" for album hit counter
 * @param bool $viewonly set to true if you don't want to increment the counter.
 * @param int $id Optional record id of the object if not the current image or album
 * @return string
 * @since 1.1.3
 */
function hitcounter($option='image', $viewonly=false, $id=NULL) {
	trigger_error(gettext('hitcounter is deprecated. Use getHitcounter().'), E_USER_NOTICE);
	switch($option) {
		case "image":
			if (is_null($id)) {
				$id = getImageID();
			}
			$dbtable = prefix('images');
			break;
		case "album":
			if (is_null($id)) {
				$id = getAlbumID();
			}
			$dbtable = prefix('albums');
			break;
	}
	$sql = "SELECT `hitcounter` FROM $dbtable WHERE `id` = $id";
	$result = query_single_row($sql);
	$resultupdate = $result['hitcounter'];
	return $resultupdate;
}

/**
 * Shortens a string to $length
 * 
 * Deprecated: use truncate_string
 *
 * @param string $string the string to be shortened
 * @param int $length the desired length for the string
 * @return string
 */
function my_truncate_string($string, $length) {
	trigger_error(gettext('my_truncate_string is deprecated. Use truncate_string().'), E_USER_NOTICE);
	if (strlen($string) > $length) {
		$short = substr($string, 0, $length);
		return $short. '...';
	} else {
		return $string;
	}
}

?>