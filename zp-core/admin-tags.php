<?php
/* This template is used to reload metadata from images. Running it will process the entire gallery,
 supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. */
define('OFFSET_PATH', true);
require_once("template-functions.php");
require_once("admin-functions.php");

if (!zp_loggedin()) {
	printLoginForm("/" . ZENFOLDER . "/admin-tags.php");
	exit();
} else {
	$gallery = new Gallery();
	printAdminHeader();
	echo "\n</head>";
	echo "\n<body>";
	printLogoAndLinks();
	echo "\n" . '<div id="main">';
	printTabs();
	echo "\n" . '<div id="content">';
	
	if (count($_POST) > 0) {
		$kill = array();
		foreach ($_POST as $key => $value) {
			$key = postIndexDecode($key);
			$kill[] = $key;
		}
		if (isset($_GET['delete'])) {
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
				$tags = array_diff($tags, $kill);
				$row['tags'] = implode(",", $tags);
				$sql = 'UPDATE '.prefix('images')."SET `tags`='".$row['tags']."' WHERE `id`='".$row['id']."'";
				query($sql);
			}

			foreach ($albumlist as $row) {
				$tags = explode(",", strtolower($row['tags']));
				$tags = array_diff($tags, $kill);
				$row['tags'] = implode(",", $tags);
				$sql = 'UPDATE '.prefix('albums')."SET `tags`='".$row['tags']."' WHERE `id`='".$row['id']."'";
				query($sql);
			}
				
		}
	}
	
	echo "<h1>".gettext("Tag Management")."</h1>";
	
 	echo '<form name="tag_delete" ' . 'action="?page=tags&delete=true" ' .	'method="post">';
 	echo gettext("Tags found in the gallery:");
	tagSelector(NULL, '');
	echo "\n<p><input type=\"submit\" value=\"".gettext("delete checked tags")."\" /></p>";
	echo '</form>';
	echo "\n" . '</div>';
	echo "\n" . '</div>';

	printAdminFooter();
}
echo "\n</body>";
echo "\n</html>";
?>



