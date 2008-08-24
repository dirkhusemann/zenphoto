<?php
/** 
 * This template is used to generate cache images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. 
 * Passing clear=on will purge the designated cache before generating cache images
 * @package core
 */
define('OFFSET_PATH', 1);
require_once("admin-functions.php");
require_once("template-functions.php");

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
	printLogoAndLinks();
	echo "\n" . '<div id="main">';
	printTabs(isset($_REQUEST['album']) ? 'edit' : 'home');
	echo "\n" . '<div id="content">';

	if (isset($alb)) {
		$object = $folder;
	} else {
		$object = '<em>'.gettext('Gallery').'</em>';
	}
	if (isset($_GET['clear']) || isset($_POST['clear'])) {
		$clear = sprintf(gettext('Clearing and refreshing cache for %s'), $object);
	} else {
		$clear = sprintf(gettext('Refreshing cache for %s'), $object); 
	}
	global $_zp_gallery;
	$count = 0;
	
	$gallery = new Gallery();
	
	if (isset($_GET['album'])) $alb = $_GET['album'];
	if (isset($_POST['album'])) $alb = $_POST['album'];
	if (isset($alb)) {
		$folder = urldecode(strip($alb));
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
	
	if (isset($_GET['return'])) $ret = $_GET['return'];
	if (isset($_POST['return'])) $ret = $_POST['return'];
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
