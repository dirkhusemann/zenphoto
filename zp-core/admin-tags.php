<?php
/* This template is used to reload metadata from images. Running it will process the entire gallery,
 supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. */
define('OFFSET_PATH', true);
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

if (count($_POST) > 0) {
	if (isset($_GET['delete'])) {
		$kill = array();
		foreach ($_POST as $key => $value) {
			$key = postIndexDecode($key);
			$kill[] = $key;
		}
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
	if (isset($_GET['rename'])) {
		$list = array();
		$kill = array();
		foreach ($_POST as $key => $value) {
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

echo "<h1>".gettext("Tag Management")."</h1>";

echo "\n<table>";
echo "\n<th>";
echo gettext("Delete tags from the gallery");
echo "\n</th>";
echo "\n<th></th>";
echo "\n<th>";
echo gettext("Rename tags");
echo "\n</th>";
echo "\n<th></th>";
echo "\n<tr>";

echo "\n<td>";
echo "\n".'<form name="tag_delete" ' . 'action="?page=tags&delete=true" ' .	'method="post">';
tagSelector(NULL, '');
echo "\n<p><input type=\"submit\" value=\"".gettext("delete checked tags")."\" /></p>";
echo "\n</form>";
echo "\n</td>";
echo "\n<td>&nbsp;&nbsp;</td>";
echo "\n<td>";
echo "\n".'<form name="tag_rename" ' . 'action="?page=tags&rename=true" ' .	'method="post">';
echo "\n<ul class=\"tagrenamelist\">";
$list = array_unique(getAllTags());
sort($list);
foreach($list as $item) {
	$listitem = postIndexEncode($item);
	echo "\n".'<li><label for="'.$listitem.'"><input id="'.$listitem.'" name="'.$listitem.'" type="text"';
	echo " /> ".$item."</label></li>";
}
echo "\n</ul>";
echo "\n<p><input type=\"submit\" value=\"".gettext("rename tags")."\" /></p>";
echo "\n</form>";
echo "\n<td>";
echo "\n<td valign='top'><p>".
gettext('To delete tags from the gallery, place a checkmark in the box for each tag you wish to delete then press the <em>').
gettext("delete checked tags").'</em> '.gettext("button").'".'.
			"</p><p>".
gettext('To change the value of a tag enter a new value for the tag in the text box in front of the tag. Then press the <em>').
gettext("rename tags").'</em> '.gettext("button").'".'.
			"</p></td>";
echo "\n</tr>";
echo "\n</table>";

echo "\n" . '</div>';
echo "\n" . '</div>';

printAdminFooter();
echo "\n</body>";
echo "\n</html>";
?>



