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
	$search = new SearchEngine();
	if (isset($_POST['savealbum'])) {
		$albumname = $_POST['album'];
		$album = $_POST['albumselect'];
		$words = $_POST['words'];
		$thumb = $_POST['thumb'];
		setOption('search_fields', 32767, false); // parse the search fields post
		$fields = $search->fields;
		$redirect = $album.'/'.$albumname.".alb";

		if (!empty($albumname)) {
			$f = fopen(getAlbumFolder().$redirect, 'w');
			if ($f !== false) {
				fwrite($f,"WORDS=$words\nTHUMB=$thumb\nFIELDS=$fields\n");
				fclose($f);
				// redirct to edit of this album
				header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit&album=" . urlencode($redirect));
				exit();
			}
		}
	}
	$_GET['page'] = 'upload'; // pretend to be the edit page.
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
	$search->setSearchParams($params);
	$fields = $search->fields;
	$words = urldecode(trim($search->words));
	$images = $search->getImages(0);
	$imagelist = array();
	foreach ($images as $image) {
		$folder = $image['folder'];
		$filename = $image['filename'];
		$imagelist[] = '/'.$folder.'/'.$filename;
	}
	$albumname = sanitize(trim($words));
	$albumname = str_replace('!', ' NOT ', $albumname);
	$albumname = str_replace('&', ' AND ', $albumname);
	$albumname = str_replace('|', ' OR ', $albumname);
	$albumname = seoFriendlyURL($albumname);
	while ($old != $albumname) {
		$old = $albumname;
		$albumname = str_replace('--', '-', $albumname);
	}
	?>
<form action="?savealbum" method="post"><input type="hidden"
	name="savealbum" value="yes" />
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
		<td><input type="text" size="60" name="words"
			value="<?php echo $words ?>" /></td>
	</tr>
	<tr>
		<td>Search fields:</td>
		<td>
		<table class="checkboxes">
			<tr>
				<td><input type="checkbox" name="sf_title" value=1
				<?php if ($fields & SEARCH_TITLE) echo ' checked'; ?>> Title</td>
				<td><input type="checkbox" name="sf_desc" value=1
				<?php if ($fields & SEARCH_DESC) echo ' checked'; ?>> Description</td>
				<td><input type="checkbox" name="sf_tags" value=1
				<?php if ($fields & SEARCH_TAGS) echo ' checked'; ?>> Tags</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="sf_filename" value=1
				<?php if ($fields & SEARCH_FILENAME) echo ' checked'; ?>>
				File/Folder name</td>
				<td><input type="checkbox" name="sf_location" value=1
				<?php if ($fields & SEARCH_LOCATION) echo ' checked'; ?>> Location</td>
				<td><input type="checkbox" name="sf_city" value=1
				<?php if ($fields & SEARCH_CITY) echo ' checked'; ?>> City</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="sf_state" value=1
				<?php if ($fields & SEARCH_STATE) echo ' checked'; ?>> State</td>
				<td><input type="checkbox" name="sf_country" value=1
				<?php if ($fields & SEARCH_COUNTRY) echo ' checked'; ?>> Country</td>
			</tr>
		</table>
		</td>
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

