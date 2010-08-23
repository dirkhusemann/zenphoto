<?php
/** 
 * Provides a function to print a tag cloud of all image tags from an album optionally including the subalbums or the album tags including sub album tags.
 * Note: The optional counter prints the total number of the tag used, not just for the select album (as clicking on it will return all anyway.)
 *   
 * @author Malte Müller (acrylian)
 * @package plugins 
 */

$plugin_description = gettext("Prints a tag cloud of all image tags from an album optionally including the subalbums or the album tags including sub album tags."); 
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.3.1'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---tags_from_album.php.html";

/**
 * Prints a tag cloud list of the tags in one album and optionally its subalbums. Returns FALSE if no value.
 * 
 * @param string $albumname folder name of the album to get the tags from ($subalbums = true this is the base albums)- This value is mandatory.
 * @param bool $subalbums TRUE if the tags of subalbum should be. FALSE is default
 * @param string $mode "images" for image tags, "albums" for album tags."images" is default.
 * @return array
 */
function getAllTagsFromAlbum($albumname,$subalbums=false,$mode='images') {
	global $_zp_gallery;
	$passwordcheck = '';
	$imageWhere = '';
	$tagWhere = "";
	$albumname = sanitize($albumname);
	if(empty($albumname)) {
		return FALSE;
	}
	$albumobj = new Album($_zp_gallery,$albumname);
	if(!$albumobj->exists) {
		return FALSE;
	}
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
	switch($mode) {
		case "images":
			if(count($albumids) == 0) {
				return FALSE;
			} else {
				$imageWhere = " WHERE ";
				$count = "";
				foreach($albumids as $albumid) {
					$count++;
					$imageWhere .= 'albumid='. $albumid['id'];
					if($count != count($albumids)) $imageWhere .= " OR ";
				}
			}
			//echo "imageWhere: ".$imageWhere."<br />";
			$imageids = query_full_array("SELECT id, albumid FROM " . prefix('images').$imageWhere);
			// if the album has no direct images and $subalbums is set to false
			if(count($imageids) == 0) {
				return FALSE;
			} else {
				$count = "";
				$tagWhere = " WHERE ";
				foreach($imageids as $imageid) {
					$count++;
					$tagWhere .= '(o.objectid ='. $imageid['id']." AND o.tagid = t.id AND o.type = 'images')";
					if($count != count($imageids)) $tagWhere .= " OR ";
				}
			}
			if(empty($tagWhere)) {
				return FALSE;
			} else {
				$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM ". prefix('obj_to_tag'). " WHERE tagid = t.id AND type = 'images') AS count FROM  ". prefix('obj_to_tag'). " AS o,". prefix('tags'). " AS t".$tagWhere." ORDER BY t.name");
			}
			break;
		case "albums":
			$count = "";
			if(count($albumids) == 0) {
				return FALSE;
			} else {
				$tagWhere = " WHERE ";
				foreach($albumids as $albumid) {
					$count++;
					$tagWhere .= '(o.objectid ='. $albumid['id']." AND o.tagid = t.id AND o.type = 'albums')";
					if($count != count($albumids)) $tagWhere .= " OR ";
				}
			}
			if(empty($tagWhere)) {
				return FALSE;
			} else {
				$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM ". prefix('obj_to_tag'). " WHERE tagid = t.id AND o.type = 'albums') AS count FROM ". prefix('obj_to_tag'). " AS o,". prefix('tags'). " AS t".$tagWhere." ORDER BY t.name");
			}
			break;
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
function printAllTagsFromAlbum($albumname="",$subalbums=false,$mode='images',$separator='',$class='',$showcounter=true,$tagcloud=true,$size_min=1,$size_max=5,$count_min=1,$count_max=50) {
	if($mode == 'all') {
		if(getAllTagsFromAlbum($albumname,$subalbums,'albums') OR getAllTagsFromAlbum($albumname,$subalbums,'images')) {
			$showcounter = false;
			$tags1 = getAllTagsFromAlbum($albumname,$subalbums,'albums');
			$tags2 = getAllTagsFromAlbum($albumname,$subalbums,'images');
			$tags = array_merge($tags1,$tags2);
			$tags = getAllTagsFromAlbum_multi_unique($tags);
		} else {
			return FALSE;
		}
	} else {
		if(getAllTagsFromAlbum($albumname,$subalbums,$mode)) {
			$tags = getAllTagsFromAlbum($albumname,$subalbums,$mode);
		} else {
			return FALSE;
		}
	}
	$size_min = sanitize_numeric($size_min); 
	$size_max = sanitize_numeric($size_max);
	$count_min = sanitize_numeric($count_min);
	$count_max = sanitize_numeric($count_max);
	$separator = sanitize($separator);
	if(!empty($class)) $class = 'class="'.sanitize($class).'"';
	$counter = '';
	echo "<ul ".$class.">\n";
	$loopcount = '';
	$tagcount = count($tags);
	$tagcount;
	foreach ($tags as $row) {
		$loopcount++;
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
		if($loopcount == $tagcount) $separator = '';
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