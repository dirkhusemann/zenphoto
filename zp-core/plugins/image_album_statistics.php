<?php
$plugin_description = gettext("Functions that provide various statistics about images and albums in the gallery.");
$plugin_author = "Malte Müller";
$plugin_version = '1.0.0';
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
 * Prints album statistic according to $option
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular albums,
 *                  "latest" for the latest uploaded,
 *                  "latest" for the latest uploaded,
 *                  "mostrated" for the most voted,
 *                  "toprated" for the best voted
 */
function printAlbumStatistic($number, $option) {

	$albums = getAlbumStatistic($number, $option);
	echo "\n<div id=\"$option_albums\">\n";
	if (getOption('mod_rewrite'))
	{ $albumlinkpath = WEBPATH."/";
	} else {
		$albumlinkpath = "index.php?album=";
	}

	$gallery = new Gallery();
	while ($album = mysql_fetch_array($albums)) {
		$tempalbum = new Album($gallery, $album['folder']);

		echo "<a href=\"".pathurlencode($tempalbum->name)."\" title=\"" . $tempalbum->getTitle() . "\">\n";
		echo "<img src=\"".$tempalbum->getAlbumThumb()."\"></a>\n";
	}
	echo "</div>\n";
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
 * Prints image statistic according to $option
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
function printImageStatistic($number, $option, $album='') {
	$images = getImageStatistic($number, $option, $album);
	echo "\n<div id=\"$option\">\n";
	foreach ($images as $image) {
		echo '<a href="' . $image->getImageLink() . '" title="' . htmlspecialchars($image->getTitle(), ENT_QUOTES) . "\">\n";
		echo '<img src="' . $image->getThumb() . "\"  alt=\"" . htmlspecialchars($image->getTitle(),ENT_QUOTES) . "\" /></a>\n";
	}
	echo "</div>\n";
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