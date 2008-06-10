<?php
/* This template is used to reload metadata from images. Running it will process the entire gallery,
 supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. */
define('OFFSET_PATH', 1);
require_once("template-functions.php");
require_once("admin-functions.php");

if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}
$gallery = new Gallery();
$_get['page'] = 'tags';
printAdminHeader();
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';

if (isset($_GET['convert'])) {
	// convert the tags the table
	$gallery = new Gallery();
	$alltags = getAllTagsUnique();
	foreach ($alltags as $tag) {
		$sql = "INSERT INTO " . prefix('tags') . " (name) VALUES ('" . escape($tag) . "')";
		query($sql);
	}
	$sql = "SELECT `id`, `tags` FROM ".prefix('albums');
	$result = query_full_array($sql);
	if (is_array($result)) {
		foreach ($result as $row) {
			if (!empty($row['tags'])) {
				$tags = explode(",", strtolower($row['tags']));
				storeTags($tags, $row['id'], 'albums');
			}
		}
	}
	$sql = "SELECT `id`, `tags` FROM ".prefix('images');
	$result = query_full_array($sql);
	if (is_array($result)) {
		foreach ($result as $row) {
			if (!empty($row['tags'])) {
				$tags = explode(",", strtolower($row['tags']));
				storeTags($tags, $row['id'], 'images');
			}
		}
	}
	query("ALTER TABLE ".prefix('albums')." DROP COLUMN `tags`");
	query("ALTER TABLE ".prefix('images')." DROP COLUMN `tags`");
	$_zp_use_tag_table = 1;  // we have converted
}

if (count($_POST) > 0) {
	if (isset($_GET['newtags'])) {
		foreach ($_POST as $value) {
			if (!empty($value)) {
				$value = strtolower(trim($value));
				$result = query_single_row("SELECT `id` FROM ".prefix('tags')." WHERE `name`='".$value."'");
				if (!is_array($result)) { // it really is a new tag
					query("INSERT INTO ".prefix('tags')." (`name`) VALUES ('" . escape($value) . "')");
				}
			}
		}
	}
	if (isset($_GET['delete'])) {
		foreach ($_POST as $key => $value) {
			$key = postIndexDecode($key);
			$kill[] = $key;
		}
		if (useTagTable()) {
			$sql = "SELECT `id` FROM ".prefix('tags')." WHERE ";
			foreach ($kill as $tag) {
				$sql .= "`name`='".$tag."' OR ";
			}
			$sql = substr($sql, 0, strlen($sql)-4);
			$dbtags = query_full_array($sql);
			if (is_array($dbtags)) {
				$sqltags = "DELETE FROM ".prefix('tags')." WHERE ";
				$sqlobjects = "DELETE FROM ".prefix('obj_to_tag')." WHERE ";
				foreach ($dbtags as $tag) {
					$sqltags .= "`id`='".$tag['id']."' OR ";
					$sqlobjects .= "`tagid`='".$tag['id']."' OR ";
				}
				$sqltags = substr($sqltags, 0, strlen($sqltags)-4);
				query($sqltags);
				$sqlobjects = substr($sqlobjects, 0, strlen($sqlobjects)-4);
				query($sqlobjects);
			}
		} else {
			$kill = array();
			$x = $kill;
			$first = array_shift($x);
			$match = "'%".$first."%'";
			foreach ($kill as $tag) {
				$match .= " OR `tags` LIKE '%".$tag."%'";
			}
			$sql = 'SELECT `id`, `tags` FROM '.prefix('images').' WHERE `tags` LIKE '.$match.';';
			$imagelist = query_full_array($sql);
			$sql = 'SELECT `id`, `tags` FROM '.prefix('albums').' WHERE `tags` LIKE '.$match.';';
			$albumlist = query_full_array($sql);

			foreach ($imagelist as $row) {
				$tags = explode(",", strtolower($row['tags']));
				foreach ($tags as $key=>$tag) {
					$tags[$key] = trim($tag);
				}
				$tags = array_diff($tags, $kill);
				$row['tags'] = implode(",", $tags);
				$sql = 'UPDATE '.prefix('images')."SET `tags`='".$row['tags']."' WHERE `id`='".$row['id']."'";
				query($sql);
			}

			foreach ($albumlist as $row) {
				$tags = explode(",", strtolower($row['tags']));
				foreach ($tags as $key=>$tag) {
					$tags[$key] = trim($tag);
				}
				$tags = array_diff($tags, $kill);
				$row['tags'] = implode(",", $tags);
				$sql = 'UPDATE '.prefix('albums')."SET `tags`='".$row['tags']."' WHERE `id`='".$row['id']."'";
				query($sql);
			}
		}
	}
	if (isset($_GET['rename'])) {
		if (useTagTable()) {
			foreach($_POST as $key=>$value) {
				if (!empty($value)) {
					$key = postIndexDecode($key);
					$newName = strtolower(sanitize($value));
					$newtag = query_single_row("SELECT `id` FROM ".prefix('tags')." WHERE `name`='".$newName."'");
					$oldtag = query_single_row("SELECT `id` FROM ".prefix('tags')." WHERE `name`='".escape($key)."'");
					if (is_array($newtag)) {
						query("DELETE FROM ".prefix('tags')." WHERE `id`=".$oldtag['id']);
						query("UPDATE ".prefix('obj_to_tag')." SET `tagid`=".$newtag['id']." WHERE `tagid`=".$oldtag['id']);
					} else {
						query("UPDATE ".prefix('tags')." SET `name`='".escape($newName)." WHERE `id`=".$oldtag['id']);
					}
				}
			}
		} else {
			$list = array();
			$kill = array();
			foreach($_POST as $key => $value) {
				if (!empty($value)) {
					$list[$key] = strtolower(sanitize($value));
					$key = postIndexDecode($key);
					$kill[] = $key;
				}
			}
			$first = array_shift($kill);
			$match = "'%".$first."%'";
			foreach ($kill as $tag) {
				$match .= " OR `tags` LIKE '%".$tag."%'";
			}
			$sql = 'SELECT `id`, `tags` FROM '.prefix('images').' WHERE `tags` LIKE '.$match.';';
			$imagelist = query_full_array($sql);
			$sql = 'SELECT `id`, `tags` FROM '.prefix('albums').' WHERE `tags` LIKE '.$match.';';
			$albumlist = query_full_array($sql);

			foreach ($imagelist as $row) {
				$tags = explode(",", strtolower($row['tags']));
				foreach ($tags as $key=>$tag) {
					$tag = trim($tag);
					$listkey = PostIndexEncode($tag);
					if (array_key_exists($listkey, $list)) {
						$tags[$key] = $list[$listkey];
					}
				}
				$row['tags'] = implode(",", $tags);
				$sql = 'UPDATE '.prefix('images')."SET `tags`='".$row['tags']."' WHERE `id`='".$row['id']."'";
				query($sql);
			}

			foreach ($albumlist as $row) {
				$tags = explode(",", strtolower($row['tags']));
				foreach ($imagelist as $row) {
					$tags = explode(",", strtolower($row['tags']));
					foreach ($tags as $key=>$tag) {
						$tag = trim($tag);
						$listkey = PostIndexEncode($tag);
						if (array_key_exists($listkey, $list)) {
							$tags[$key] = $list[$listkey];
						}
					}
					$row['tags'] = implode(",", $tags);
					$sql = 'UPDATE '.prefix('albums')."SET `tags`='".$row['tags']."' WHERE `id`='".$row['id']."'";
					query($sql);
				}

			}
		}
	}
}

echo "<h1>".gettext("Tag Management")."</h1>";

echo "\n<table>";
echo "\n<tr>";
echo "\n<th>".gettext("Delete tags from the gallery")."</th>";
echo "\n<th></th>";
echo "\n<th>".gettext("Rename tags")."</th>";
echo "\n<th></th>";
echo "\n<th>";
$newTags = useTagTable();
if ($newTags) {
	echo gettext("New tags");
}
echo "</th>";
echo "\n</tr>";
echo "\n<tr>";

echo "\n<td>";
echo "\n".'<form name="tag_delete" action="?page=tags&delete=true" method="post">';
tagSelector(NULL, '');
echo "\n<p><input type=\"submit\" value=\"".gettext("delete checked tags")."\" /></p>";
echo "\n</form>";
echo "\n</td>";
echo "\n<td>&nbsp;&nbsp;</td>";
echo "\n<td>";
echo "\n".'<form name="tag_rename" action="?page=tags&rename=true" method="post">';
echo "\n<ul class=\"tagrenamelist\">";
$list = getAllTagsUnique();
sort($list);
foreach($list as $item) {
	$listitem = postIndexEncode($item);
	echo "\n".'<li><label for="'.$listitem.'"><input id="'.$listitem.'" name="'.$listitem.'" type="text"';
	echo " /> ".$item."</label></li>";
}
echo "\n</ul>";
echo "\n<p><input type=\"submit\" value=\"".gettext("rename tags")."\" /></p>";
echo "\n</form>";
echo "\n</td>";
echo "\n<td>&nbsp;&nbsp;</td>";
echo "\n<td>";
if ($newTags) {
	echo '<form name="new_tags" action="?page=tags&newtags=true"method="post">';
	echo "\n<ul class=\"tagnewlist\">";
	for ($i=0; $i<40; $i++) {
		echo "\n".'<li><label for="new_tag_'.$i.'"><input id="new_tag_'.$i.'" name="new_tag_'.$i.'" type="text"';
		echo " /></label></li>";
	}
	echo "\n</ul>";
	echo "\n<p><input type=\"submit\" value=\"".gettext("save new tags")."\" /></p>";
	echo "\n</form>";
}
echo "\n</td>";
echo "\n</tr>";
echo "\n<tr>";
echo "\n<td colspan=5 valign='top'><p>".
gettext('To delete tags from the gallery, place a checkmark in the box for each tag you wish to delete then press the <em>delete checked tags</em>button').'.'.
			"</p><p>".
gettext('To change the value of a tag enter a new value for the tag in the text box in front of the tag. Then press the <em>rename tags</em>button').'</p>';
if ($newTags) {
	echo "\n<p>".gettext("Add tags to the list by entering their names in the input fields of the <em>New tags</em> list. Then press the <em>save new tags</em>button").'</p>';
} else {
	echo "\n<p>".gettext("Your tags are currently stored as strings in each album and image object recored. You can convert to using database tables to manage tags by clicking on the button below. This will result in a performance improvement on large galleries but it is not a reversable process.").'</p>';
	echo "\n<p><form name='tag_convert' action='?page=tags&convert=true' method='post'>";
	echo "\n<button type=\"submit\" class=\"tooltip\" id='convert_tags' title=\"".gettext("Converts the <em>tags</em> from strings in a record field to a database tag table structure. Warning, this cannot be reversed.")."\"> ".gettext("Convert tags to table")."</button>";
	echo "\n</form></p>";
	echo "\n<p><strong>".gettext("STOP! Remember, this is a development build. Unless you are one of the Developers helping to test this feature I strongly recommend not touching the button.")."</strong></p>";
}
echo "\n</td>";
echo "\n</tr>";
echo "\n</table>";

echo "\n" . '</div>';
echo "\n" . '</div>';

printAdminFooter();
echo "\n</body>";
echo "\n</html>";
?>



