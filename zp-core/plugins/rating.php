<?php
/**
 * rating -- Supports an image and album rating system
 * @author Malte Müller (acrylian) and Stephen Billard (sbillard)
 * @version 1.0.1
 * @package plugins
 */

$plugin_description = gettext("Adds several theme functions to enable images and/or album rating by users.");
$plugin_author = "Malte Müller (acrylian) and Stephen Billard (sbillard)";
$plugin_version = '1.0.1';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---rating.php.html";

// register the scripts needed
addPluginScript('<script type="text/javascript" src="'.FULLWEBPATH."/".ZENFOLDER .'/plugins/rating/rating.js"></script>');
addPluginScript('<link rel="stylesheet" href="'.FULLWEBPATH."/".ZENFOLDER.'/plugins/rating/rating.css" type="text/css" />');

require_once('rating/functions-rating.php');


/**
 * Returns the rating of the designated image
 *
 * @param string $option 'totalvalue' or 'totalvotes'
 * @param int $id Record id for the image
 * @return int
 */
function getImageRating($option, $id) {
	return getRating($option,"image",$id);
}

/**
 * Returns the average rating of the image
 *
 * @param int $id the id of the image
 * @return real
 */
function getImageRatingCurrent($id) {
	$votes = getImageRating("totalvotes",$id);
	$value = getImageRating("totalvalue",$id);
	if($votes != 0)
	{ $rating =  round($value/$votes, 1);
	}
	return $rating;
}

/**
 * Prints the image rating information for the current image
 *
 */
function printImageRating() {
	printRating("image");
}

/**
 * Prints the rating accordingly to option, it's a combined function for image and album rating
 *
 * @param string $option "image" for image rating, "album" for album rating.
 * @see printImageRating() and printAlbumRating()
 *
 */
function printRating($option) {
	switch($option) {
		case "image":
			$id = getImageID();
			$value = getImageRating("totalvalue", $id);
			$votes = getImageRating("totalvotes", $id);
			break;
		case "album":
			$id = getAlbumID();
			$value = getAlbumRating("totalvalue", $id);
			$votes = getAlbumRating("totalvotes", $id);
			break;
	}
	if($votes != 0) {
		$ratingpx = round(($value/$votes)*25);
	}
	$zenpath = WEBPATH."/".ZENFOLDER."/plugins";
	echo "<div id=\"rating\">\n";
	echo "<ul class=\"star-rating\">\n";
	echo "<li class=\"current-rating\" id=\"current-rating\" style=\"width:".$ratingpx."px\"></li>\n";
	$msg1 = gettext("Rating");
	$msg2 = gettext("Total votes");
	if(!checkForIP(sanitize($_SERVER['REMOTE_ADDR']),$id,$option)){
		echo "<li><a href=\"javascript:rate(1,$id,'".rawurlencode($zenpath)."','$option')\" title=\"".gettext("1 star out of 5")."\" class=\"one-star\">2</a></li>\n";
		echo "<li><a href=\"javascript:rate(2,$id,'".rawurlencode($zenpath)."','$option')\" title=\"".gettext("2 stars out of 5")."\" class=\"two-stars\">2</a></li>\n";
		echo "<li><a href=\"javascript:rate(3,$id,'".rawurlencode($zenpath)."','$option')\" title=\"".gettext("3 stars out of 5")."\" class=\"three-stars\">2</a></li>\n";
		echo "<li><a href=\"javascript:rate(4,$id,'".rawurlencode($zenpath)."','$option')\" title=\"".gettext("4 stars out of 5")."\" class=\"four-stars\">2</a></li>\n";
		echo "<li><a href=\"javascript:rate(5,$id,'".rawurlencode($zenpath)."','$option')\" title=\"".gettext("5 stars out of 5")."\" class=\"five-stars\">2</a></li>\n";
	}
	echo "</ul>\n";
	echo "<div id =\"vote\">\n";
	switch($option) {
		case "image":
			echo $msg1.' '.getImageRatingCurrent($id).' ('.$msg2.': '.$votes.")";
			break;
		case "album":
			echo $msg1.' '.getAlbumRatingCurrent($id).' ('.$msg2.': '.$votes.")";
			break;
	}
	echo "</div>\n";
	echo "</div>\n";
}

/**
 * Get the rating for an image or album,
 *
 * @param string $option 'totalvalue' or 'totalvotes'
 * @param string $option2 'image' or 'album'
 * @param int $id id of the image or album
 * @see getImageRating() and getAlbumRating()
 * @return unknown
 */
function getRating($option,$option2,$id) {
	switch ($option) {
		case "totalvalue":
			$rating = "total_value"; break;
		case "totalvotes":
			$rating = "total_votes"; break;
	}
	switch ($option2) {
		case "image":
			if(!$id) {
				$id = getImageID();
			}
			$dbtable = prefix('images');
			break;
		case "album":
			if(!$id) {
				$id = getAlbumID();
			}
			$dbtable = prefix('albums');
			break;
	}
	$result = query_single_row("SELECT ".$rating." FROM $dbtable WHERE id = $id");
	return $result[$rating];
}

/**
 * Prints the image rating information for the current image
 *
 */
function printAlbumRating() {
	printRating("album");
}

/**
 * Returns the average rating of the album
 *
 * @param int $id Record id for the album
 * @return real
 */
function getAlbumRatingCurrent($id) {
	$votes = getAlbumRating("totalvotes",$id);
	$value = getAlbumRating("totalvalue",$id);
	if($votes != 0)
	{ $rating =  round($value/$votes, 1);
	}
	return $rating;
}

/**
 * Returns the rating of the designated album
 *
 * @param string $option 'totalvalue' or 'totalvotes'
 * @param int $id Record id for the album
 * @return int
 */
function getAlbumRating($option, $id) {
	$rating =  getRating($option,"album",$id);
	return $rating;
}

?>