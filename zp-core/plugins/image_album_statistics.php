<?php
/**
 * image_album_statistics -- support functions for "statistics" about images and albums.
 * 
 * Supports such statistics as "most popular", "latest", "top rated", etc.
 */

$plugin_description = gettext("Functions that provide various statistics about images and albums in the gallery.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.1';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---image_album_statistics.php.html";

/**
 * Retuns a list of album statistic accordingly to $option
 *
 * @param int $number the number of albums to get
 * @param string $option "popular" for the most popular albums,
 *     "latest" for the latest uploaded, "mostrated" for the most voted,
 *     "toprated" for the best voted
 * @return string
 */
function getAlbumStatistic($number=5, $option) {
	if (zp_loggedin()) {
		$albumWhere = "";
	} else {
		$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
		foreach($albumscheck as $albumcheck) {
			if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
				$albumpasswordcheck= " AND id != ".$albumcheck['id'];
				$passwordcheck = $passwordcheck.$albumpasswordcheck;
			}
		}
		$albumWhere = "WHERE `show`=1".$passwordcheck;
	}
	switch($option) {
		case "popular":
			$sortorder = "hitcounter";
			break;
		case "latest":
			$sortorder = "id";
			break;
		case "mostrated":
			$sortorder = "total_votes"; break;
		case "toprated":
			$sortorder = "(total_value/total_votes)"; break;
	}
	$albums = query("SELECT id, title, folder, thumb FROM " . prefix('albums') . $albumWhere . " ORDER BY ".$sortorder." DESC LIMIT $number");
	return $albums;
}

/**
 * Prints album statistic according to $option as an unordered HTML list
 * A css class is attached by default named '$option_album'
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular albums,
 *                  "latest" for the latest uploaded,
 *                  "latest" for the latest uploaded,
 *                  "mostrated" for the most voted,
 *                  "toprated" for the best voted
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printAlbumStatistic($number, $option, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	$albums = getAlbumStatistic($number, $option);
	echo "\n<div id=\"".$option."_album\">\n";
	if (getOption('mod_rewrite'))
	{ $albumlinkpath = WEBPATH."/";
	} else {
		$albumlinkpath = "index.php?album=";
	}
	echo "<ul>";
	$gallery = new Gallery();
	while ($album = mysql_fetch_array($albums)) {
		$tempalbum = new Album($gallery, $album['folder']);
		echo "<li><a href=\"".pathurlencode($tempalbum->name)."\" title=\"" . $tempalbum->getTitle() . "\">\n";
		echo "<img src=\"".$tempalbum->getAlbumThumb()."\"></a>\n<br />";
		if($showtitle) {
			echo "<h3><a href=\"".pathurlencode($tempalbum->name)."\" title=\"" . $tempalbum->getTitle() . "\">\n";
			echo $tempalbum->getTitle()."</a>\n";
		}
		echo "</h3>";
		if($showdate) {
			echo "<p>". zpFormattedDate(getOption('date_format'),strtotime($tempalbum->getDateTime()))."</p>";
		}
		if($showdesc) {
			echo "<p>".my_truncate_string($tempalbum->getDesc(), $desclength)."</p>";
		}
		echo "</li>";
	}
	echo "</ul></div>\n";
}

/**
 * Prints the most popular albums
 *
 * @param string $number the number of albums to get
 */
function printPopularAlbums($number=5) {
	printAlbumStatistic($number,"popular");
}

/**
 * Prints the latest albums
 *
 * @param string $number the number of albums to get
 */
function printLatestAlbums($number=5) {
	printAlbumStatistic($number,"latest");
}

/**
 * Prints the most rated albums
 *
 * @param string $number the number of albums to get
 */
function printMostRatedAlbums($number=5) {
	printAlbumStatistic($number,"mostrated");
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 */
function printTopRatedAlbums($number=5) {
	printAlbumStatistic($number,"toprated");
}

/**
 * Returns a list of image statistic according to $option
 *
 * @param string $number the number of images to get
 * @param string $option "popular" for the most popular images,
 *                       "latest" for the latest uploaded,
 *                       "latest" for the latest uploaded,
 *                       "latest-date" for the latest uploaded, but fetched by date,
 *                       "mostrated" for the most voted,
 *                       "toprated" for the best voted
 * @param string $album title of an specific album
 * @return string
 */
function getImageStatistic($number, $option, $album='') {
	global $_zp_gallery;
	if (zp_loggedin()) {
		$albumWhere = "";
		$imageWhere = "";
	} else {
		$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
		foreach($albumscheck as $albumcheck) {
			if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
				$albumpasswordcheck= " AND albums.id != ".$albumcheck['id'];
				$passwordcheck = $passwordcheck.$albumpasswordcheck;
			}
		}
		$albumWhere = " AND albums.show=1".$passwordcheck;
		$imageWhere = " AND images.show=1";
	}
	if(!empty($album)) {
		$specificalbum = " albums.title = '".$album."' AND ";
	} else {
		$specificalbum = "";
	}
	switch ($option) {
		case "popular":
			$sortorder = "images.hitcounter"; break;
		case "latest-date":
			$sortorder = "images.date"; break;
		case "latest":
			$sortorder = "images.id"; break;
		case "mostrated":
			$sortorder = "images.total_votes"; break;
		case "toprated":
			$sortorder = "(images.total_value/images.total_votes)"; break;
	}
	$imageArray = array();
	$images = query_full_array("SELECT images.albumid, images.filename AS filename, images.title AS title, " .
 														"albums.folder AS folder, images.show, albums.show, albums.password FROM " .
	prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
															" WHERE ".$specificalbum."images.albumid = albums.id " . $imageWhere . $albumWhere .
															" AND albums.folder != ''".
															" ORDER BY ".$sortorder." DESC LIMIT $number");
	foreach ($images as $imagerow) {

		$filename = $imagerow['filename'];
		$albumfolder = $imagerow['folder'];

		$desc = $imagerow['title'];
		// Album is set as a reference, so we can't re-assign to the same variable!
		$image = new Image(new Album($_zp_gallery, $albumfolder), $filename);
		$imageArray [] = $image;
	}
	return $imageArray;
}

/**
 * Prints image statistic according to $option as an unordered HTML list
 * A css class is attached by default named accordingly'$option'
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular images,
 *                       "latest" for the latest uploaded,
 *                       "latest" for the latest uploaded,
 *                       "mostrated" for the most voted,
 *                       "toprated" for the best voted
 * @param string $album title of an specific album
 * @return string
 */
function printImageStatistic($number, $option, $album='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	$images = getImageStatistic($number, $option, $album);
	echo "\n<div id=\"$option\">\n";
	echo "<ul>";
	foreach ($images as $image) {
		echo "<li><a href=\"" . $image->getImageLink() . "\" title=\"" . htmlspecialchars($image->getTitle(), ENT_QUOTES) . "\">\n";
		echo "<img src=\"" . $image->getThumb() . "\"  alt=\"" . htmlspecialchars($image->getTitle(),ENT_QUOTES) . "\" /></a>\n";
		if($showtitle) {
			echo "<h3><a href=\"".pathurlencode($image->name)."\" title=\"" . $image->getTitle() . "\">\n";
			echo $image->getTitle()."</a>\n";
		}
		echo "</h3>";
		if($showdate) {
			echo "<p>". zpFormattedDate(getOption('date_format'),strtotime($image->getDateTime()))."</p>";
		}
		if($showdesc) {
			echo "<p>".my_truncate_string($image->getDesc(), $desclength)."</p>";
		}
	}
	echo "</li>";
	echo "</ul></div>\n";
}

/**
 * Prints the most popular images
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 */
function printPopularImages($number=5, $album='') {
	printImageStatistic($number, "popular",$album);
}

/**
 * Prints the latest images by ID (=upload order)
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 */
function printLatestImages($number=5, $album='') {
	printImageStatistic($number, "latest", $album);
}

/**
 * Prints the latest images by date order
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 */
function printLatestImagesByDate($number=5, $album='') {
	printImageStatistic($number, "latest-date", $album);
}

?>