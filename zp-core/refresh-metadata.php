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
	$gallery = new Gallery();
	if (isset($_GET['refresh'])) {
		if ($_GET['refresh'] != 'done') {
			if ($gallery->garbageCollect(true, true)) {
				$param = '?refresh=continue';
			} else {
				$param = '?refresh=done';
			}
			$r = "&return=".$_GET['return'];
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/refresh-metadata.php" . $param . $r);
		}
	}
	printAdminHeader();
	echo "\n</head>";
	echo "\n<body>";
	printLogoAndLinks();
	echo "\n" . '<div id="main">';
	printTabs();
	echo "\n" . '<div id="content">';
	echo "<h1>zenphoto Metadata refresh</h1>";

	if (isset($_GET['refresh']) && db_connect()) {
		echo "<h3>Finished refreshing metadata.</h3>";
		if (isset($_GET['return'])) $ret = $_GET['return'];
		if (isset($_POST['return'])) $ret = $_POST['return'];
		if (!empty($ret)) {
			$r = "?page=edit";
			if ($ret != '*') {
				$r .= "&album=$ret";
			}
		}
		echo "<p><a href=\"admin.php$r\">&laquo; Back</a></p>";
	} else if (db_connect()) {
		echo "<h3>database connected</h3>";
		$folder = '';
		$id = '';
		$r = "";
		if (isset($_GET['album'])) $alb = $_GET['album'];
		if (isset($_POST['album'])) $alb = $_POST['album'];
		if (isset($alb)) {
			$folder = querydecode(strip($alb));
			if (!empty($folder)) {
				$sql = "SELECT `id` FROM ". prefix('albums') . " WHERE `folder`=\"".mysql_real_escape_string($folder)."\";";
				$row = query_single_row($sql);
				$id = $row['id'];
			} else {
				$folder = '';
			}
		}
		if (!empty($id)) {
			$id = "WHERE `albumid`=$id";
			$r = " for <em>$folder</em>";
		} else {		
			$sql = "UPDATE " . prefix('albums') . " SET `mtime`=0 WHERE `dynamic`='1';";
			query($sql);
		}
		if (!empty($folder) && empty($id)) {
			echo "<p><em>$folder</em> not found</p>";
		} else {
			$sql = "UPDATE " . prefix('images') . " SET `mtime`=0 $id;";
			query($sql);
			if (isset($_GET['return'])) $ret = $_GET['return'];
			if (isset($_POST['return'])) $ret = $_POST['return'];
			echo "<p>We're all set to refresh the metadata$r</p>";
			echo "<p><a href=\"?refresh=start&return=$ret\" title=\"Refresh image metadata.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
		}
	} else {
		echo "<h3>database not connected</h3>";
		echo "<p>Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.";
	}

	echo "\n" . '</div>';
	echo "\n" . '</div>';

	printAdminFooter();
}
echo "\n</body>";
echo "\n</html>";
?>

