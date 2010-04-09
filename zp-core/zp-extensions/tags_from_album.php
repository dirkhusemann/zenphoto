<?php
/** 
 * Provides a function to print a tag cloud of all image tags from an album optionally including the subalbums or the album tags including sub album tags.
 * Note: The optional counter prints the total number of the tag used, not just for the select album (as clicking on it will return all anyway.)
 *   
 * @author Malte Müller (acrylian)
 * @package plugins 
 */

$plugin_description = gettext("Prints a tag cloud of all image tags from an album optionally including the subalbums or the album tags including sub album tags. Note the optional counter prints the total number of the tag used, not just for the selected album (as clicking on it will return all anyway)."); 
$plugin_author = "Malte Müller (acrylian)";
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
		$albumWhere .= " AND `folder` LIKE '".zp_escape_string($albumname)."%'";
	} else {
		$albumWhere .= " AND `folder` = '".zp_escape_string($albumname)."' ";
	}
	//echo "albumWhere: ".$albumWhere."<br />";
	$albumids = query_full_array("SELECT id, folder FROM " . prefix('albums'). $albumWhere);
	//echo "albumids: <pre>"; print_r($albumids); echo "</pre><br />";
	$imageWhere = '';
	switch($mode) {
		case "images":
			if(count($albumids) != 0) $imageWhere = " WHERE ";
			$count = "";
			foreach($albumids as $albumid) {
				$count++;
				$imageWhere .= 'albumid='. $albumid['id'];
				if($count != count($albumids)) $imageWhere .= " OR ";
			}
			//echo "imageWhere: ".$imageWhere."<br />";
			$imageids = query_full_array("SELECT id, albumid FROM " . prefix('images').$imageWhere);
			// if the album has no direct images and $subalbums is set to false
			if(count($imageids) == 0) return false; 
			//echo "imageids: <pre>"; print_r($imageids); echo "</pre><br />";
			$count = "";
			$tagWhere = "";
			if(count($imageids) != 0) $tagWhere = " WHERE ";
			foreach($imageids as $imageid) {
				$count++;
				$tagWhere .= '(o.objectid ='. $imageid['id']." AND o.tagid = t.id AND o.type = 'images')";
				if($count != count($imageids)) $tagWhere .= " OR ";
			}
			$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM ". prefix('obj_to_tag'). " WHERE tagid = t.id AND type = 'images') as count FROM  ". prefix('obj_to_tag'). "as o,". prefix('tags'). "as t".$tagWhere." ORDER by t.name");
			break;
		case "albums":
			$count = "";
			$tagWhere = "";
			if(count($albumids) != 0) $tagWhere = " WHERE ";
			foreach($albumids as $albumid) {
				$count++;
				$tagWhere .= '(o.objectid ='. $albumid['id']." AND o.tagid = t.id AND o.type = 'albums')";
				if($count != count($albumids)) $tagWhere .= " OR ";
			}
			$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM ". prefix('obj_to_tag'). " WHERE tagid = t.id AND o.type = 'albums')  as count ". prefix('obj_to_tag'). "as o,". prefix('tags'). "as t".$tagWhere." ORDER by t.name");
			break;
	}
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
 * @param bool $tagcloud if set to false a simple list without font size changes will be printed, set to true (default) prints a list as a tag cloud
 * @param integere $size_min smallest font size the cloud should display
 * @param integer $size_max largest font size the cloud should display
 * @param integer $count_min the minimum count for a tag to appear in the output 
 * @param integer $count_max the floor count for setting the cloud font size to $size_max
 */
function printAllTagsFromAlbum($albumname="",$subalbums=false,$mode='images',$separator='',$class="",$showcounter=true,$tagcloud=true,$size_min=1,$size_max=5,$count_min=1,$count_max=50) {
	if($mode == 'all') {
		if(getAllTagsFromAlbum($albumname,$subalbums,'albums') OR getAllTagsFromAlbum($albumname,$subalbums,'images')) {
			$showcounter = false;
			$tags1 = getAllTagsFromAlbum($albumname,$subalbums,'albums');
			$tags2 = getAllTagsFromAlbum($albumname,$subalbums,'images');
			$tags = array_merge($tags1,$tags2);
			$tags = getAllTagsFromAlbum_multi_unique($tags);
		} else {
			return false;
		}
	} else {
		if(getAllTagsFromAlbum($albumname,$subalbums,$mode)) {
			$tags = getAllTagsFromAlbum($albumname,$subalbums,$mode);
		} else {
			return false;
		}
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
		$tid   = $row['id'];
		$tname = $row['name'];
		$style = "";
		if($tagcloud OR $mode == 'all') {
			$size = min(max(round(($size_max*($count-$count_min))/($count_max-$count_min),
			2), $size_min)
			,$size_max);
			$size = str_replace(',','.', $size);
			$style = " style=\"font-size:".$size."em;\"";
		}
		if($showcounter) {
			$counter = ' ('.$count.')';
		}
		echo "<li><a class=\"tagLink\" href=\"".htmlspecialchars(getSearchURL($tname, '', 'tags',''))."\"".$style." rel=\"nofollow\">".$tname.$counter."</a>".$separator."</li>\n";
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