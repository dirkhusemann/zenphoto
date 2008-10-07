<?php
/** 
 * This template is used to generate cache images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. 
 * Passing clear=on will purge the designated cache before generating cache images
 * @package core
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/template-functions.php');

function loadAlbum($album) {
	global $_zp_current_album;
	$subalbums = $album->getSubAlbums();
	$count = 0;
	foreach ($subalbums as $folder) {
		$subalbum = new Album($album, $folder);
		$count = $count + loadAlbum($subalbum);
	}
	$_zp_current_album = $album;
	if (getNumImages() > 0) {
		echo "<br />" . $album->name . "{";
		while (next_image(true)) {
			echo '<img src="' . getImageThumb() . '" height="8" width="8" /> | <img src="' . getDefaultSizedImage() . '" height="20" width="20" />' . "\n";
			$count++;
		}
		echo "}<br/>\n";
	}
	return $count;
}
if (!($_zp_loggedin & ADMIN_RIGHTS)) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}
printAdminHeader();
echo "\n</head>";
echo "\n<body>";
	if (isset($_REQUEST['album'])) {
		$alb = $_REQUEST['album'];
		$folder = urldecode(strip($alb));
		$object = $folder;
	} else {
		$object = '<em>'.gettext('Gallery').'</em>';
	}

	printLogoAndLinks();
	echo "\n" . '<div id="main">';
	printTabs(empty($alb) ? 'edit' : 'home');
	echo "\n" . '<div id="content">';

	if (isset($_GET['clear']) || isset($_POST['clear'])) {
		$clear = sprintf(gettext('Clearing and refreshing cache for %s'), $object);
	} else {
		$clear = sprintf(gettext('Refreshing cache for %s'), $object); 
	}
	global $_zp_gallery;
	$count = 0;
	
	$gallery = new Gallery();
	
	if (isset($alb)) {
		echo "\n<h2>".$clear."</h2>";
		if (isset($_GET['clear']) || isset($_POST['clear'])) {
			$gallery->clearCache(SERVERCACHE . '/' . $folder); // clean out what was there
		}
		$album = new Album($album, $folder);
		$count = loadAlbum($album);
	} else {
		echo "\n<h2>".$clear."</h2>";
		if (!empty($clear)) {
			$gallery->clearCache(); // clean out what was there.
		}
		$albums = $_zp_gallery->getAlbums();
		foreach ($albums as $folder) {
			$album = new Album($album, $folder);
			$count = $count + loadAlbum($album);
		}
	}
	echo "\n" . "<br/>".sprintf(gettext("Finished: Total of %u images."), $count);
	
	if (isset($_REQUEST['return'])) $ret = $_REQUEST['return'];
	if (!empty($ret)) {
		$r = "?page=edit";
		if ($ret != '*') $r .= "&album=$ret";
	} else {
		$r = '';
	}
	echo "<p><a href=\"admin.php$r\">&laquo; ".gettext("Back")."</a></p>";
	echo "\n" . '</div>';
	echo "\n" . '</div>';

	printAdminFooter();

echo "\n</body>";
echo "\n</head>";
?>
