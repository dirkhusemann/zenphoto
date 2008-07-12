<?php

/* SQL Counting Functions */
function show_subalbum_count() {
	$sql = "SELECT COUNT(id) FROM ". prefix("albums") ." WHERE parentid IS NOT NULL";
	if (!zp_loggedin()) {$sql .= " AND `show` = 1"; }  /* exclude the unpublished albums */
	$result = query($sql);
	$count = mysql_result($result, 0);
	echo $count;
}

function show_sub_count_index() {
	echo getNumSubalbums();
}

function printHeadingImage($randomImage) {
	$id = getAlbumId();
	if (is_null($randomImage) || checkforPassword(true)) {
		$randomImage= new Image(new Album(new Gallery(), ''), 'zen-logo.jpg' );
	}
	if (!is_null($randomImage)) {
		echo '<div id="randomhead">';
		$randomAlbum = $randomImage->getAlbum();
		$randomAlt1 = $randomAlbum->getTitle();
		if ($randomAlbum->getAlbumId() <> $id) {
			$randomAlbum = $randomAlbum->getParent();
			while (!is_null($randomAlbum) && ($randomAlbum->getAlbumId() <> $id)) {
				$randomAlt1 = $randomAlbum->getTitle().":\n".$randomAlt1;
				$randomAlbum = $randomAlbum->getParent();
			}
		}
		$randomImageURL = getURL($randomImage);
		echo "<a href='".$randomImageURL."' title='".gettext('Random picture...')."'><img src='".
					htmlspecialchars($randomImage->getCustomImage(NULL, 620, 180, 620, 180, NULL, NULL, !getOption('Watermark_head_image'))).
					"' width=620 height=180 alt=".'"'.
					htmlspecialchars($randomAlt1, ENT_QUOTES).
					":\n".htmlspecialchars($randomImage->getTitle(), ENT_QUOTES).
					'" /></a>';
		echo '</div>';
	}
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
function printImage_AlbumCount() {
	$c = getNumSubalbums();
	if ($c > 0) {
		echo "\n".$c.' '.gettext("albums(s)");
	}
	$c = getNumImages();
	if ($c > 0) {
		echo "\n".$c.' '.gettext("images(s)");
	}
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

function printNofM($whats, $whatp, $first, $last, $total) {
	if (!is_null($first)) {
		echo "<p align=\"center\">";
		if ($first == $last) {
			echo "$whats $first ";
		} else {
			echo "$whatp $first-$last ";
		}
		echo gettext('of') . " $total</p>";
	}
}

function printThemeInfo() {
	global $themeColor, $themeResult, $_noFlash;
	if ($themeColor == 'effervescence') {
		$themeColor = '';
	} else {
		$themeColor = ": '$themeColor'";
	}
	if (!$themeResult) { $themeColor .= ' ' . gettext("(not found)"); }
	$personality = getOption('Theme_personality');
	if ($personality != 'Image page') {
		if (($personality == 'Simpleviewer') && (!getOption('mod_rewrite') || $_noFlash)) {
			$personality = "<strike>$personality</strike>";
		}
		$personality = "+$personality$themeColor";
	} else {
		$personality = $themeColor;
	}
	echo "<p><small>Effervescence$personality</small></p>";
}

function printLinkWithQuery($url, $query, $text) {
	if (substr($url, -1, 1) == '/') {$url = substr($url, 0, (strlen($url)-1));}
	$url = $url . (getOption("mod_rewrite") ? "?" : "&amp;");
	echo "<a href=\"$url$query\">$text</a>";
}

function printLogo() {
	global $_zp_themeroot;
	if ($img = getOption('Graphic_logo')) {
		echo '<img src="'.$_zp_themeroot.'/images/'.$img.'.png" />';
	} else {
		$name = getOption('Theme_logo');
		if (empty($name)) {
			$name = sanitize($_SERVER['HTTP_HOST']);
		}
		echo "<h1><a>$name</a></h1>";
	}
}

?>