<?php
/* This template is used to reload metadata from images. Running it will process the entire gallery,
 supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. */
define('OFFSET_PATH', true);
require_once("template-functions.php");
require_once("admin-functions.php");

if (!zp_loggedin()) {
	printLoginForm("/" . ZENFOLDER . "/refresh-metadata.php");
	exit();
} else {
	if (isset($_POST['savealbum'])) {
		$albumname = $_POST['album'];
		$album = $_POST['albumselect'];
		$folder = getAlbumFolder().$album;
		$tags = $_POST['tags'];
		$thumb = $_POST['thumb'];

		if (!empty($albumname)) {
			$f = fopen($folder.$albumname.'.alb', 'w');
			if ($f !== false) {
				fwrite($f,"TAGS:$tags\nTHUMB:$thumb\n");
				fclose($f);
				// redirct to edit of this album
				header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit&album=" . urlencode($albumname . '.alb'));
				exit();
			}
		}
	}

	printAdminHeader();
	echo "\n</head>";
	echo "\n<body>";
	printLogoAndLinks();
	echo "\n" . '<div id="main">';
	printTabs();
	echo "\n" . '<div id="content">';
	echo "<h1>zenphoto Create Dynamic Album</h1>\n";
	if (isset($_POST['savealbum'])) { // we fell through, some kind of error
		echo "<div class=\"errorbox space\">";
		echo "<h2>Failed to save the album file</h2>";
		echo "</div>\n";
	}
	$gallery = new Gallery();
	$albumlist = array();
	genAlbumUploadList($albumlist);
	$params = trim(zp_getCookie('zenphoto_image_search_params'));
	$search = new SearchEngine();
	$search->setSearchParams($params);
	$search->fields = SEARCH_TAGS;
	$tags = trim($search->words);
	$images = $search->getImages(0);
	$imagelist = array();
	foreach ($images as $image) {
		$folder = $image['folder'];
		$filename = $image['filename'];
		$imagelist[] = '/'.$folder.'/'.$filename;
	}

	$trialname = strtolower(sanitize(trim($tags)));
	$trialname = str_replace('!', '_', $tags);
	$albumname = '';
	for ($i=0; $i<strlen($tags); $i++) {
		$c2 = $c;
		$c = substr($trialname, $i, 1);
		if (($c == '_') || (($c >= 'a') && ($c <= 'z')) || (($c >= '0') && ($c <= '9'))) {
			$albumname .= $c;
		} else {
			$c = '-';
			if (!empty($albumname) && ($c2 != '-')) {
				$albumname .= '-';
			}
		}
	}
	?>
<form action="?savealbum" method="post"><input
	type="hidden" name="savealbum" value="yes" />
<table>
	<tr>
		<td>Album name:</td>
		<td><input type="text" size="40" name="album"
			value="<?php echo $albumname ?>" /></td>
	</tr>
	<tr>
		<td>Create in:</td>
		<td><select id="albumselectmenu" name="albumselect">
		<?php
		if (isMyAlbum('/', UPLOAD_RIGHTS)) {
			?>
			<option value="" selected="1" style="font-weight: bold;">/</option>
			<?php
		}
		$bglevels = array('#fff','#f8f8f8','#efefef','#e8e8e8','#dfdfdf','#d8d8d8','#cfcfcf','#c8c8c8');
		foreach ($albumlist as $fullfolder => $albumtitle) {
			$singlefolder = $fullfolder;
			$saprefix = "";
			$salevel = 0;
			// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
			while (strstr($singlefolder, '/') !== false) {
				$singlefolder = substr(strstr($singlefolder, '/'), 1);
				$saprefix = "&nbsp; &nbsp;&raquo;&nbsp;" . $saprefix;
				$salevel++;
			}
			echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: '.$bglevels[$salevel].'; border-bottom: 1px dotted #ccc;"' : '')
					. "$selected>" . $saprefix . $singlefolder . " (" . $albumtitle . ')' . "</option>\n";
		}
?>
		</select></td>
	</tr>

	<tr>
		<td>Thumbnail:</td>
		<td><select id="thumb" name="thumb">
		<?php
		foreach ($imagelist as $image) {
			echo "\n<option value=\"".$image."\" >".$image."</option>";
		}
		?>
		</select></td>
	</tr>
	<tr>
		<td>Search criteria:</td>
		<td><input type="text" size="60" name="tags"
			value="<?php echo $tags ?>" /></td>
	</tr>
</table>
<input type="submit" value="Create the album" class="button" /></form>

		<?php

		echo "\n" . '</div>';
		echo "\n" . '</div>';

		printAdminFooter();
}
echo "\n</body>";
echo "\n</html>";
?>

