<?php
/**
 * These functions have been removed from mainstream Zenphoto as as they have been
 * supplanted. 
 * 
 * They are not maintained and they are not guarentted to function correctly with the
 * current version of Zenphoto.
 * 
 * @package plugins
 */
$plugin_description = gettext("Deprecated Zenphoto functions. These functions have been removed from mainstream Zenphoto as as they have been supplanted. They are not maintained and they are not guaranteed to function correctly with the current version of Zenphoto.");
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---deprecated-functions.php.html";

/**
 * THIS FUNCTION IS DEPRECATED! Use getHitcounter()!
 * Gets the hitcount of a page, news article or news category
 * 
 * @param string $mode Pass "news", "page" or "category" to get the hitcounter of the current page, article or category if one is set
 * @param mixed $obj If you want to get the hitcount of a specific page or article you additonally can to pass its object.
 * 									 If you want to get the hitcount of a specific category you need to pass its cat_link. 
 * 									 In any case $mode must be set!
 * @return int
 */
function getZenpageHitcounter($mode="",$obj=NULL) {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_gallery_page, $_zp_current_category;
	trigger_error(gettext('getZenpageHitcounter is deprecated. Use getHitcounter().'), E_USER_NOTICE);
	switch($mode) {
		case "news":
			if((is_NewsArticle() OR is_News()) AND !is_object($obj)) {
				$obj = $_zp_current_zenpage_news;
				$hc = $obj->get('hitcounter');
			} else if(is_object($obj)) {
				$hc = $obj->get('hitcounter');
			}
			return $hc;
			break;
		case "page":
			if(is_Pages() AND !is_object($obj)) {
				$obj = $_zp_current_zenpage_page;
				$hc = $obj->get('hitcounter');
			} else if(is_object($obj)) {
				$hc = $obj->get('hitcounter');
			}
			return $hc;
			break;
		case "category":
			if(!is_object($obj) || is_NewsCategory() AND !empty($obj)) {
				$catname = $_zp_current_category;
				$hc = query_single_row("SELECT hitcounter FROM ".prefix('zenpage_news_categories')." WHERE cat_link = '".$catname."'");
				return $hc["hitcounter"];
			} 
			break;
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

/**
 * Returns the EXIF infromation from the current image
 *
 * @return array
 */
function getImageEXIFData() {
	trigger_error(gettext('getImageEXIFData is deprecated. Use getImageMetaData().'), E_USER_NOTICE);
	global $_zp_current_image;
	if (is_null($_zp_current_image)) return false;
	return $_zp_current_image->getMetaData();
}

/**
 * Returns the Location of the album.
 *
 * @return string
 */
function getAlbumPlace() {
	trigger_error(gettext('getAlbumPlace is deprecated. Use getAlbumLocation().'), E_USER_NOTICE);
	global $_zp_current_album;
	return $_zp_current_album->getLocation();
}

/**
 * Prints the location of the album and make it editable
 *
 * @param bool $editable when true, enables AJAX editing in place
 * @param string $editclass CSS class applied to element if editable
 * @param mixed $messageIfEmpty Either bool or string. If false, echoes nothing when description is empty. If true, echoes default placeholder message if empty. If string, echoes string.
 * @author Ozh
 */
function printAlbumPlace($editable=false, $editclass='', $messageIfEmpty = true) {
	trigger_error(gettext('printAlbumPlace is deprecated. Use printAlbumLocation().'), E_USER_NOTICE);
	if ( $messageIfEmpty === true ) {
		$messageIfEmpty = gettext('(No place...)');
	}
	printEditable('album', 'location', $editable, $editclass, $messageIfEmpty, !getOption('tinyMCEPresent'));
}


/***************************
 * ZENPAGE PLUGIN FUNCTIONS
 ***************************/

/**
 * THIS FUNCTION IS DEPRECATED! Use getHitcounter()!
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
	trigger_error(gettext('zenpageHitcounter is deprecated. Use getHitcounter().'), E_USER_NOTICE);
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
 * Same as zenphoto's rewrite_path() except it's without WEBPATH, needed for some partial urls
 * 
 * @param $rewrite The path with mod_rewrite
 * @param $plain The path without
 * 
 * @return string
 */
function rewrite_path_zenpage($rewrite='',$plain='') {
	trigger_error(gettext('function is deprecated.'), E_USER_NOTICE);
	if (getOption('mod_rewrite')) {
		return $rewrite;
	} else {
		return $plain;
	}
}

/**
 * CombiNews feature: Returns a list of tags of an image.
 *
 * @return array
 */
function getNewsImageTags() {
	trigger_error(gettext('function is deprecated.'), E_USER_NOTICE);
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		return $_zp_current_zenpage_news->getTags();
	} else {
		return false;
	}
}

/**
 * CombiNews feature: Prints a list of tags of an image. These tags are not editable.
 *
 * @param string $option links by default, if anything else the
 *               tags will not link to all other photos with the same tag
 * @param string $preText text to go before the printed tags
 * @param string $class css class to apply to the UL list
 * @param string $separator what charactor shall separate the tags
 * @param bool $editable true to allow admin to edit the tags
 * @return string
 */
function printNewsImageTags($option='links',$preText=NULL,$class='taglist',$separator=', ',$editable=TRUE) {
	trigger_error(gettext('function is deprecated.'), E_USER_NOTICE);
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		$singletag = getNewsImageTags();
		$tagstring = implode(', ', $singletag);
		if (empty($tagstring)) { $preText = ""; }
		if (count($singletag) > 0) {
			echo "<ul class=\"".$class."\">\n";
			if (!empty($preText)) {
				echo "<li class=\"tags_title\">".$preText."</li>";
			}
			$ct = count($singletag);
			foreach ($singletag as $atag) {
				if ($x++ == $ct) { $separator = ""; }
				if ($option == "links") {
					$links1 = "<a href=\"".htmlspecialchars(getSearchURL($atag, '', 'tags', 0, 0))."\" title=\"".$atag."\" rel=\"nofollow\">";
					$links2 = "</a>";
				}
				echo "\t<li>".$links1.htmlspecialchars($atag, ENT_QUOTES).$links2.$separator."</li>\n";
			}

			echo "</ul>";

			echo "<br clear=\"all\" />\n";
		}
	}
}

function getNumSubalbums() {
	trigger_error(gettext('function is deprecated.'), E_USER_NOTICE);
	return getNumAlbums();
}

function getAllSubalbums($param=NULL) {
	trigger_error(gettext('function is deprecated.'), E_USER_NOTICE);
	return getAllAlbums($param);
}


?>