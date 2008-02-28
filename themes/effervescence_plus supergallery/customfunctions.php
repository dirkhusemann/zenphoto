<?php
require_once(SERVERPATH . "/" . ZENFOLDER . '/plugins/supergallery-functions.php');

function printGalleryHeadingImage() {
	$img = getRandomGalleryImage();
	if (!isset($img['folder'])) {
		$img['folder'] = SERVERPATH . "/" . ZENFOLDER . '/images/zen-logo.jpg';
		$img['title'] = 'No image available';
		$img['desc'] = '';
		$img['gallery'] = '';
	}
	$cachefilename = substr(getImageCacheFilename('', '', getImageParameters(array('thumb'))), 1);
	cacheGalleryImage($cachefilename, $img['folder'], getImageParameters(array(NULL, 620, 180, 620, 180, NULL, NULL, 
						!getOption('Watermark_head_image'))), true, true);
	$randomImageURL = WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
	echo '<div id="randomhead">';
	echo "<a href='".getSubgalleryURL()."' title='Random Picture...'><img src='".
 			$randomImageURL."' width=620 height=180  alt=".'"'.
 			htmlspecialchars($img['gallery'], ENT_QUOTES).
 			":\n".htmlspecialchars($img['title'], ENT_QUOTES).
 			'"/></a>';
	echo '</div>';
}

/* Custom caption functions */
function getCustomGalleryDesc() {
	global $_current_subgallery;
	$desc = getSubgalleryDesc();
	if (strlen($desc) == 0) {
		$desc = getSubgalleryTitle();
	} else {
		$desc = getSubgalleryTitle()."\n".$desc;
	}
	return $desc;
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
		echo "<p align=\"center\">$what";
	if ($first == $last) { 
		echo " $first";
	} else {
		echo "s $first-$last"; 
	}
	echo " of $total</p>";
	}
}

function printThemeInfo() {
	global $themeColor, $themeResult, $_noFlash;
	if ($themeColor == 'effervescence') {
		$themeColor = '';
	} else {
		$themeColor = ": '$themeColor'";
	}
	if (!$themeResult) { $themeColor .= " (not found)"; }
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

function printLogo() {
	$name = getOption('Theme_logo');
	if (empty($name)) {
		$name = sanitize($_SERVER['HTTP_HOST']);
	}
	echo "<h1><a>$name</a></h1>";
}

?> 