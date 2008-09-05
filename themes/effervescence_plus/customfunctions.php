<?php

/* SQL Counting Functions */
function get_subalbum_count() {
	$sql = "SELECT COUNT(id) FROM ". prefix("albums") ." WHERE parentid IS NOT NULL";
	if (!zp_loggedin()) {$sql .= " AND `show` = 1"; }  /* exclude the unpublished albums */
	$result = query($sql);
	$count = mysql_result($result, 0);
	return $count;
}

function show_sub_count_index() {
	echo getNumSubalbums();
}

function printHeadingImage($randomImage) {
	global $_zp_themeroot;
	$id = getAlbumId();
	echo '<div id="randomhead">';
	if (is_null($randomImage) || checkforPassword(true)) {
		echo '<img src="'.$_zp_themeroot.'/images/zen-logo.jpg" alt="'.gettext('There were no images from which to select the random heading.').'" />';
	} else {
		$randomAlbum = $randomImage->getAlbum();
		$randomAlt1 = $randomAlbum->getTitle();
		if ($randomAlbum->getAlbumId() <> $id) {
			$randomAlbum = $randomAlbum->getParent();
			while (!is_null($randomAlbum) && ($randomAlbum->getAlbumId() <> $id)) {
				$randomAlt1 = $randomAlbum->getTitle().":\n".$randomAlt1;
				$randomAlbum = $randomAlbum->getParent();
			}
		}
		$randomImageURL = htmlspecialchars(getURL($randomImage));
		echo "<a href='".$randomImageURL."' title='".gettext('Random picture...')."'><img src='".
					htmlspecialchars($randomImage->getCustomImage(NULL, 620, 180, 620, 180, NULL, NULL, !getOption('Watermark_head_image'))).
					"' width=620 height=180 alt=".'"'.
					htmlspecialchars($randomAlt1, ENT_QUOTES).
					":\n".htmlspecialchars($randomImage->getTitle(), ENT_QUOTES).
					'" /></a>';
	}
	echo '</div>';
}


/* Custom caption functions */
function getCustomAlbumDesc() {
	if(!in_context(ZP_ALBUM)) return false;
	global $_zp_current_album;
	$desc = $_zp_current_album->getDesc();
	if (strlen($desc) == 0) {
		$desc = $_zp_current_album->getTitle();
	} else {
		$desc = $_zp_current_album->getTitle()."\n".$desc;
	}
	return $desc;
}
function getImage_AlbumCount() {
	$c = getNumSubalbums();
	if ($c > 0) {
		$result = "\n ".sprintf(gettext("%u albums(s)"),$c);
	} else {
		$result = '';
	}
	$c = getNumImages();
	if ($c > 0) {
		$result .=  "\n ".sprintf(gettext("%u images(s)"),$c);
	}
	return $result;
}
function parseCSSDef($file) {
	$file = str_replace(WEBPATH, '', $file);
	$file = SERVERPATH . $file;
	if (is_readable($file) && $fp = @fopen($file, "r")) {
		while($line = fgets($fp)) {
			if (!(false === strpos($line, "#main2 {"))) {
				$line = fgets($fp);
				$line = trim($line);
				$item = explode(":", $line);
				$rslt = trim(substr($item[1], 0, -1));
				return $rslt;
			}
		}
	}
	return "#0b9577"; /* the default value */
}

function printNofM($what, $first, $last, $total) {
	if (!is_null($first)) {
		echo "<p align=\"center\">";
		if ($first == $last) {
			if ($what == 'Album') {
				printf(gettext('Album %1$u of %2$u'), $first, $total);
			} else {
				printf(gettext('Photo %1$u of %2$u'), $first, $total);
			}
		} else {
			if ($what == 'Album') {
				printf(gettext('Albums %1$u-%2$u of %3$u'), $first, $last, $total);
			} else {
				printf(gettext('Photos %1$u-%2$u of %3$u'), $first, $last, $total);
			}
		}
		echo "</p>";
	}
}

function printThemeInfo() {
	global $themeColor, $themeResult, $_noFlash;
	if ($themeColor == 'effervescence') {
		$themeColor = '';
	}
	$personality = getOption('Theme_personality');
	if ($personality == 'Image page') {
		$personality = '';
	} else if (($personality == 'Simpleviewer') && (!getOption('mod_rewrite') || $_noFlash)) {
		$personality = "<strike>$personality</strike>";
	}
	if (empty($themeColor) && empty($personality)) {
		echo '<p><small>Effervescence</small></p>';
	} else if (empty($themeColor)) {
		if ($themeResult) {
			echo '<p><small>'.sprintf(gettext('Effervescence %s'),$personality).'</small></p>';
		} else {
			echo '<p><small>'.sprintf(gettext('Effervescence %s (not found)'),$personality).'</small></p>';
		}
	} else if (empty($personality)) {
		echo '<p><small>'.sprintf(gettext('Effervescence %s'),$themeColor).'</small></p>';
	} else {
		if ($themeResult) {
			echo '<p><small>'.sprintf(gettext('Effervescence %1$s %2$s'),$themeColor, $personality).'</small></p>';
		} else {
			echo '<p><small>'.sprintf(gettext('Effervescence %1$s %2$s (not found)'),$themeColor, $personality).'</small></p>';
		}
	}
}

function printLinkWithQuery($url, $query, $text) {
	if (substr($url, -1, 1) == '/') {$url = substr($url, 0, (strlen($url)-1));}
	$url = $url . (getOption("mod_rewrite") ? "?" : "&amp;");
	echo "<a href=\"$url$query\">$text</a>";
}

function printLogo() {
	global $_zp_themeroot;
	if ($img = getOption('Graphic_logo')) {
		echo '<img src="'.$_zp_themeroot.'/images/'.$img.'.png" alt="Logo"/>';
	} else {
		$name = getOption('Theme_logo');
		if (empty($name)) {
			$name = sanitize($_SERVER['HTTP_HOST']);
		}
		echo "<h1><a>$name</a></h1>";
	}
}

function annotateAlbum() {
	global $_zp_current_album;
	$tagit = '';
	$pwd = $_zp_current_album->getPassword();
	if (zp_loggedin() && !empty($pwd)) {
		$tagit = "\n".gettext('The album is password protected.');
	}
	if (!$_zp_current_album->getShow()) {
		$tagit .= "\n".gettext('The album is not published.');
	}
	return  sprintf(gettext('View the Album: %s'),getBareAlbumTitle()).getImage_AlbumCount().$tagit;
}

function annotateImage() {
	global $_zp_current_image;
	if (!$_zp_current_image->getShow()) {
		$tagit = "\n".gettext('The image is marked not visible.');
	} else {
		$tagit = '';
	}
	return  sprintf(gettext('View the image: %s'),GetBareImageTitle()).$tagit;
}

?>