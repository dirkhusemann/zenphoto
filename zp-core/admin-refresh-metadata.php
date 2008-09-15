<?php
/**
 * This template is used to reload metadata from images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named.
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once("template-functions.php");
require_once("admin-functions.php");

if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}

$gallery = new Gallery();
$imageid = '';
if (isset($_GET['refresh'])) {
	if (isset($_GET['id'])) {
		$imageid = sanitize_numeric($_GET['id']);
	}
	$imageid = $gallery->garbageCollect(true, true, $imageid);
}

if (isset($_GET['prune'])) {
	$type = 'prune&';
	$title = gettext('Refresh Database');
	$finished = gettext('Finished refreshing the database');
	$incomplete = gettext('Database refresh is incomplete');
	$allset = gettext("We're all set to refresh the database");
	$continue = gettext('Continue refreshing the database.');
} else {
	$type = '';
	$title = gettext('Refresh Metadata');
	$finished = gettext('Finished refershing the metadata');
	$incomplete = gettext('Metadata refresh is incomplete');
	$allset = gettext("We're all set to refresh the metadata");
	$continue = gettext('Continue refreshing the metadata.');
}

printAdminHeader();
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs(isset($_REQUEST['album']) ? 'edit' : 'home');
echo "\n" . '<div id="content">';
echo "<h1>".$title."</h1>";
$ret = '';
if (isset($_GET['refresh']) && db_connect()) {
	if (isset($_REQUEST['return'])) $ret = $_REQUEST['return'];
	if (!empty($ret)) {
		$r = "?page=edit";
		if ($ret != '*') {
			$r .= "&album=$ret";
		}
	} else {
		$r = '';
	}
	if (empty($imageid)) {
		echo "<h3>".$finished."</h3>";
		echo "<p><a href=\"admin.php$r\">&laquo; Back</a></p>";
	} else {
		echo '<h3>'.$incomplete.'</h3>';
		echo "<p><a href=\"?".$type."refresh=continue&id=".$imageid."&return=$ret\" title=\"".$continue."\" style=\"font-size: 15pt; font-weight: bold;\">".gettext("Continue!")."</a></p>";
	}
} else if (db_connect()) {
	echo "<h3>".gettext("database connected")."</h3>";
	$folder = '';
	$id = '';
	$r = "";
	if ($type !== 'prune&') {
		if (isset($_GET['album'])) $alb = $_GET['album'];
		if (isset($_POST['album'])) $alb = $_POST['album'];
		if (isset($alb)) {
			$folder = urldecode(strip($alb));
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
			$r = " $folder";
		} else {

			$sql = "UPDATE " . prefix('albums') . " SET `mtime`=0 WHERE `dynamic`='1';";

			query($sql);
		}
	}
	if (!empty($folder) && empty($id)) {
		echo "<p> ".sprintf(gettext("<em>%s</em> not found"),$folder)."</p>";
	} else {
		if ($type !== 'prune&') {
			$sql = "UPDATE " . prefix('images') . " SET `mtime`=0 $id;";
			query($sql);
		}

		if (isset($_GET['return'])) $ret = $_GET['return'];
		if (isset($_POST['return'])) $ret = $_POST['return'];
		if (empty($r)) {
			echo "<p>".$allset."</p>";
		} else {
			echo "<p>".sprintf(gettext("We're all set to refresh the metadata for <em>%s</em>"),$r)."</p>";
		}
		echo "<p><a href=\"?".$type."refresh=start&return=$ret\" title=\"".gettext("Refresh image metadata.")."\" style=\"font-size: 15pt; font-weight: bold;\">".gettext("Go!")."</a></p>";
	}
} else {
	echo "<h3>".gettext("database not connected")."</h3>";
	echo "<p>".gettext("Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.");
}

echo "\n" . '</div>';
echo "\n" . '</div>';

printAdminFooter();

echo "\n</body>";
echo "\n</html>";
?>



