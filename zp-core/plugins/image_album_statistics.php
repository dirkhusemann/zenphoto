<?php
/**
 * image_album_statistics -- support functions for "statistics" about images and albums.
 * 
 * Supports such statistics as "most popular", "latest", "top rated", etc.
 */

$plugin_description = gettext("Functions that provide various statistics about images and albums in the gallery.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.3';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---image_album_statistics.php.html";

/**
 * Retuns a list of album statistic accordingly to $option
 *
 * @param int $number the number of albums to get
 * @param string $option "popular" for the most popular albums,
 *     "latest" for the latest uploaded, "mostrated" for the most voted,
 *     "toprated" for the best voted
 * 		 "latestupdated" for the latest updated
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
		case "latestupdated":
			// get all albums
			$allalbums = query_full_array("SELECT id, title, folder, thumb FROM " . prefix('albums'). $albumWhere);
			$latestimages = array();

			// get latest images of each album
			foreach($allalbums as $album) {
				$image = query_single_row("SELECT id, albumid FROM " . prefix('images'). " WHERE albumid = ".$album['id'] . " AND `show` = 1 ORDER BY id DESC");
				array_push($latestimages, $image);
			}
			// sort latest image by mtime
			arsort($latestimages);
			//print_r($latestimages);
			$updatedalbums = array();
			$count = 0;
			foreach($latestimages as $latestimage) {
				$count++;
				foreach($allalbums as $album) {
					if($album['id'] === $latestimage['albumid']) {
						array_push($updatedalbums,$album);
					}
				}
				if($count === $number) {
					break;
				}
			}
			break;
	}
	if($option === "latestupdated") {
		return $updatedalbums;
	} else {
		$albums = query("SELECT id, title, folder, thumb FROM " . prefix('albums') . $albumWhere . " ORDER BY ".$sortorder." DESC LIMIT $number");
		return $albums;
	}
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
 * 									"latestupdated" for the latest updated
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printAlbumStatistic($number, $option, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	$albums = getAlbumStatistic($number, $option);
	echo "\n<div id=\"".$option."_album\">\n";
	$albumpath = rewrite_path("/", "index.php?album=");
	echo "<ul>";
	if ($option === "latestupdated") { // needs a "normal" array
		foreach($albums as $album) {
			printAlbumStatisticItem($album, $option,$showtitle, $showdate, $showdesc, $desclength);
		}
	} else {
		while ($album = mysql_fetch_array($albums)) { // needs a mysql array
			printAlbumStatisticItem($album, $option,$showtitle, $showdate, $showdesc, $desclength);
		}
		echo "</ul></div>\n";
	}
}

/**
 * A helper function that only prints a item of the loop within printAlbumStatistic()
 * Not for standalone use.
 *
 * @param array $album the array that getAlbumsStatistic() submitted
 */
function printAlbumStatisticItem($album, $option, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	global $_zp_gallery;
	$tempalbum = new Album($_zp_gallery, $album['folder']);
		echo "<li><a href=\"".$albumpath.pathurlencode($tempalbum->name)."\" title=\"" . $tempalbum->getTitle() . "\">\n";
		echo "<img src=\"".$tempalbum->getAlbumThumb()."\"></a>\n<br />";
		if($showtitle) {
			echo "<h3><a href=\"".$albumpath.pathurlencode($tempalbum->name)."\" title=\"" . $tempalbum->getTitle() . "\">\n";
			echo $tempalbum->getTitle()."</a></h3>\n";
		}
		if($showdate) {
			if($option === "latestupdated") {
				$filechangedate = filectime(getAlbumFolder().$tempalbum->name);
				$latestimage = query_single_row("SELECT mtime FROM " . prefix('images'). " WHERE albumid = ".$tempalbum->getAlbumID() . " AND `show` = 1 ORDER BY id DESC");
				$lastuploaded = query("SELECT COUNT(*) FROM ".prefix('images')." WHERE albumid = ".$tempalbum->getAlbumID() . " AND mtime = ". $latestimage['mtime']);
				$row = mysql_fetch_row($lastuploaded);
				$count = $row[0];
				echo "<p>".gettext("Last update: ").zpFormattedDate(getOption('date_format'),$filechangedate)."</p>";
				if($count <= 1) {
					$image = gettext("image");
				} else {
					$image = gettext("images");
				}
				echo "<span>".$count.gettext(" new ").$image."</span>";
			} else {
				echo "<p>". zpFormattedDate(getOption('date_format'),strtotime($tempalbum->getDateTime()))."</p>";
			}
		}
		if($showdesc) {
			echo "<p>".my_truncate_string($tempalbum->getDesc(), $desclength)."</p>";
		}
		echo "</li>";
}

/**
 * Prints the most popular albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printPopularAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printAlbumStatistic($number,"popular",$showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the latest albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printLatestAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printAlbumStatistic($number,"latest",$showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the most rated albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printMostRatedAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printAlbumStatistic($number,"mostrated",$showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printTopRatedAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printAlbumStatistic($number,"toprated",$showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printLatestUpdatedAlbums($number=5,$showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printAlbumStatistic($number,"latestupdated",$showtitle, $showdate, $showdesc, $desclength);
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
	$images = query_full_array("SELECT images.albumid, images.filename AS filename, images.mtime as mtime, images.title AS title, " .
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
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image desc should be shown
 * @param integer $desclength the length of the desc to be shown
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
			echo $image->getTitle()."</a></h3>\n";
		}
		if($showdate) {
			echo "<p>". zpFormattedDate(getOption('date_format'),strtotime($image->getDateTime()))."</p>";
		}
		if($showdesc) {
			echo "<p>".my_truncate_string($image->getDesc(), $desclength)."</p>";
		}
	echo "</li>\n";
	}
	echo "</ul></div>\n";
}

/**
 * Prints the most popular images
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printPopularImages($number=5, $album='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printImageStatistic($number, "popular",$album, $showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the n top rated images
 *
 * @param int $number The number if images desired
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printTopRatedImages($number=5, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printImageStatistic($number, "toprated", $showtitle, $showdate, $showdesc, $desclength);
}


/**
 * Prints the n most rated images
 *
 * @param int $number The number if images desired
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image desc should be shown
 * @param integer $desclength the length of the desc to be shown 
 */
function printMostRatedImages($number=5, $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printImageStatistic($number, "mostrated", $showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the latest images by ID (=upload order)
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printLatestImages($number=5, $album='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printImageStatistic($number, "latest", $album, $showtitle, $showdate, $showdesc, $desclength);
}

/**
 * Prints the latest images by date order
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image desc should be shown
 * @param integer $desclength the length of the desc to be shown
 */
function printLatestImagesByDate($number=5, $album='', $showtitle=false, $showdate=false, $showdesc=false, $desclength=40) {
	printImageStatistic($number, "latest-date", $album, $showtitle, $showdate, $showdesc, $desclength);
}

?>