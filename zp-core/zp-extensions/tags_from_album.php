<?php
/** 
 * Provides a function to print a tag cloud of all image tags from an album optionally including the subalbums or the album tags including sub album tags.
 * Requires MySQL 5 or newer.
 *   
 * @author Malte Müller (acrylian) based on maxslug's FlickrishPrintAlbumTagCloud() from http://www.zenphoto.org/support/topic.php?id=6879#post-40363
 * @package plugins 
 */

$plugin_description = gettext("Prints a tag cloud of all image tags from an album optionally including the subalbums or the album tags including sub album tags. Requires MySQL 5 or newer."); 
$plugin_author = "Malte Müller (acrylian) based on maxslug's FlickrishPrintAlbumTagCloud()";
$plugin_version = '1.3'; 
$plugin_URL = "";

/**
 * Prints a tag cloud list of the tags in one album and optionally its subalbums.
 * 
 * @param string $albumname folder name of the album to get the tags from ($subalbums = true this is the base albums)
 * @param bool $subalbums TRUE if the tags of subalbum should be. FALSE is default
 * @param string $mode "images" for image tags, "albums" for album tags
 * @return array
 */
function getAllTagsFromAlbum($albumname="",$subalbums=false,$mode='images') {
	$passwordcheck = '';
	$albumname = sanitize($albumname);
	if (zp_loggedin()) {
		$albumWhere = "WHERE `dynamic`=0";
	} else {
		$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
		foreach($albumscheck as $albumcheck) {
			if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
				$albumpasswordcheck= " AND id != ".$albumcheck['id'];
				$passwordcheck = $passwordcheck.$albumpasswordcheck;
			}
		}
		$albumWhere = "WHERE `dynamic`=0 AND `show`=1".$passwordcheck;
	}
	if($subalbums) {
		$albumWhere .= " AND `folder` LIKE '".$albumname."/%' ";
	} else {
		$albumWhere .= " AND `folder` = '".$albumname."' ";
	}
	$albumids = query_full_array("SELECT id FROM " . prefix('albums'). $albumWhere);
	$imageWhere = '';
	switch($mode) {
		case "images":
			foreach($albumids as $albumid) {
				$imageWhere .= ' AND i.albumid='. $albumid['id'];
			}
			$sql = "SELECT t.tagid, tags.name, COUNT(*) AS count FROM ".
           prefix('obj_to_tag').
           " AS t LEFT JOIN (".
           prefix('images'). "AS i, ".
           prefix('tags').   "AS tags)".
           " ON (t.objectid = i.id AND tags.id= t.tagid)".
           " WHERE t.type = 'images' AND i.show = 1 ".
           $imageWhere.
           " GROUP BY t.tagid";
			break;
		case "albums":
			foreach($albumids as $albumid) {
				$imageWhere .= ' AND a.id='. $albumid['id'];
			}
			$sql = "SELECT t.tagid, tags.name, COUNT(*) AS count FROM ".
           prefix('obj_to_tag').
           " AS t LEFT JOIN (".
           prefix('albums'). "AS a, ".
           prefix('tags').   "AS tags)".
           " ON (t.objectid = a.id AND tags.id= t.tagid)".
           " WHERE t.type = 'albums'".
           $imageWhere.
           " GROUP BY t.tagid";
			break;
		}
    $tags = query_full_array($sql);
    if (!is_array($tags)) {
        return;
    }
    return $tags;
}


/** 
 * Prints a tag cloud list of the tags in one album and optionally its subalbums.
 * Known limitation: If $mode is set to "all" there is no tag count and therefore no tag cloud but a simple list
 * 
 * @param string $albumname folder name of the album to get the tags from ($subalbums = true this is the base albums)
 * @param bool $subalbums TRUE if the tags of subalbum should be. FALSE is default
 * @param string $mode "images" for image tags, "albums" for album tags, "all" for both mixed
 * @param string $separator how to separate the entries
 * @param string $class css classs to style the list 
 * @param integer $showcounter if the tag count should be shown (no counter if $mode = "all")
 * @param integere $size_min smallest font size the cloud should display
 * @param integer $size_max largest font size the cloud should display
 * @param integer $count_min the minimum count for a tag to appear in the output 
 * @param integer $count_max the floor count for setting the cloud font size to $size_max
 */
function printAllTagsFromAlbum($albumname="",$subalbums=false,$mode='images',$separator='',$class="",$showcounter=true,$size_min=0.5,$size_max=5,$count_min=1,$count_max=50) {
	if($mode == 'all') {
		$showcounter = false;
		$tags1 = getAllTagsFromAlbum($albumname,$subalbums,'albums');
		$tags2 = getAllTagsFromAlbum($albumname,$subalbums,'images');
		$tags = array_merge($tags1,$tags2);
		$tags = getAllTagsFromAlbum_multi_unique($tags);
	} else {
		$tags = getAllTagsFromAlbum($albumname,$subalbums,$mode);
	}
	$size_min = sanitize_numeric($size_min); 
	$size_max = sanitize_numeric($size_max);
	$count_min = sanitize_numeric($count_min);
	$count_max = sanitize_numeric($count_max);
	$separator = sanitize($separator);
	
	$counter = '';
	echo "<ul ".$class.">\n";
	foreach ($tags as $row) {
		$count = $row['count'];
		$tid   = $row['tagid'];
		$tname = $row['name'];
		$size = min(max(round(($size_max*($count-$count_min))/($count_max-$count_min),
		2), $size_min)
		,$size_max);
		$size = str_replace(',','.', $size);
		if($showcounter) {
			$counter = ' ('.$count.')';
		}
		if($mode == 'all') { // disable the dynamic font size for 'all' mode
			$style = '';
		} else {
			$style = "style=\"font-size:".$size."em;\"";
		}
		echo "<li><a class=\"tagLink\" href=\"".htmlspecialchars(getSearchURL($tname, '', 'tags',''))."\" $style rel=\"nofollow\">".$tname.$counter."</a>".$separator."</li>\n";
	}
	echo "</ul>\n";
}


/** 
 * Removes duplicate entries in multi dimensional array. 
 * From kenrbnsn at rbnsn dot com http://uk.php.net/manual/en/function.array-unique.php#57202
 * @param array $array
 * @return array
 */
function getAllTagsFromAlbum_multi_unique($array) {
	foreach ($array as $k=>$na)
	$new[$k] = serialize($na);
	$uniq = array_unique($new);
	foreach($uniq as $k=>$ser)
	$new1[$k] = unserialize($ser);
	return ($new1);
}
?>