<?php
/**
 * This template is used to reload metadata from images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named.
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');
require_once(dirname(__FILE__).'/template-functions.php');

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
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
	$type = 'prune&amp;';
	$title = gettext('Refresh Database');
	$finished = gettext('Finished refreshing the database');
	$incomplete = gettext('Database refresh is incomplete');
	$allset = gettext("We're all set to refresh the database");
	$continue = gettext('Continue refreshing the database.');
} else {
	$type = '';
	$title = gettext('Refresh Metadata');
	$finished = gettext('Finished refreshing the metadata');
	$incomplete = gettext('Metadata refresh is incomplete');
	$allset = gettext("We're all set to refresh the metadata");
	$continue = gettext('Continue refreshing the metadata.');
}

printAdminHeader();

if (isset($_REQUEST['album'])) {
	$tab = 'edit';
} else {
	$tab = 'home';
}
if (isset($_REQUEST['return'])) {
	$ret = sanitize_path($_REQUEST['return']);
	if (substr($ret, 0, 1) == '*') {
		if (empty($ret) || $ret == '*.' || $ret == '*/') {
			$r = '?page=edit';
		} else {
			$r = '?page=edit&amp;album='.urlencode(substr($ret, 1)).'&amp;tab=subalbuminfo';
		}
	} else {
		$r = '?page=edit&amp;album='.urlencode($ret);
	}
	$backurl = 'admin-edit.php'.$r;
} else {
	$ret = $r = '';
	$backurl = 'admin.php';
}
if (isset($_GET['refresh']) && db_connect()) {
	if (empty($imageid)) {
		?>
		<meta http-equiv="refresh" content="1; url=<?php echo $backurl; ?>" />
		<?php
	} else {
		if (!empty($ret)) $ret = '&amp;return='.$ret;
		$redirecturl = '?'.$type.'refresh=continue&amp;id='.$imageid.$ret; 
		?>
		<meta http-equiv="refresh" content="1; url=<?php echo $redirecturl; ?>" />
		<?php
	}
} else if (db_connect()) {
	$folder = $albumwhere = $imagewhere = $id = $r = '';
	if ($type !== 'prune&amp;') {
		if (isset($_REQUEST['album'])) {
			if (isset($_POST['album'])) {
				$alb = urldecode($_POST['album']);
			} else {
				$alb = $_GET['album'];
			}
			$folder = sanitize_path($alb);
			if (!empty($folder)) {
				$sql = "SELECT `id` FROM ". prefix('albums') . " WHERE `folder`=\"".zp_escape_string($folder)."\";";
				$row = query_single_row($sql);
				$id = $row['id'];
			}
		}
		if (!empty($id)) {
			$imagewhere = "WHERE `albumid`=$id";
			$r = " $folder";
			$albumwhere = "WHERE `parentid`=$id";
		}
	}
	if (isset($_REQUEST['return'])) $ret = sanitize_path($_REQUEST['return']);
	if (empty($folder)) {
		$album = '';
	} else {
		$album = '&amp;album='.$folder;
	}
	if (!empty($ret)) $ret = '&amp;return='.$ret;
	$starturl = '?'.$type.'refresh=start'.$album.$ret;
	if (!empty($id)) {
		
	}
	?>
	<meta http-equiv="refresh" content="1; url=<?php  echo$starturl; ?>" />
	<?php
}
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs($tab);
echo "\n" . '<div id="content">';
echo "<h1>".$title."</h1>";
if (isset($_GET['refresh']) && db_connect()) {
	if (empty($imageid)) {
		?>
		<h3><?php echo $finished; ?></h3>
		<p><?php echo gettext('you should return automatically. If not press: '); ?></p>
		<p><a href="<?php echo $backurl; ?>">&laquo; <?php echo gettext('Back'); ?></a></p>
		<?php
	} else {
		?>
		<h3><?php echo $incomplete; ?></h3>
		<p><?php echo gettext('This process should continue automatically. If not press: '); ?></p>
		<p><a href="<?php echo $redirecturl; ?>" title="<?php echo $continue; ?>" style="font-size: 15pt; font-weight: bold;"><?php echo gettext("Continue!"); ?></a></p>
		<?php 
	}
		
} else if (db_connect()) {
	echo "<h3>".gettext("database connected")."</h3>";
	if ($type !== 'prune&amp;') {
		$sql = "UPDATE " . prefix('albums') . " SET `mtime`=0 $albumwhere";
		query($sql);
		$sql = "UPDATE " . prefix('images') . " SET `mtime`=0 $imagewhere;";
		query($sql);
	}
	if (!empty($folder) && empty($id)) {
		echo "<p> ".sprintf(gettext("<em>%s</em> not found"),$folder)."</p>";
	} else {
		if (empty($r)) {
			echo "<p>".$allset."</p>";
		} else {
			echo "<p>".sprintf(gettext("We're all set to refresh the metadata for <em>%s</em>"),$r)."</p>";
		}
		echo '<p>'.gettext('This process should start automatically. If not press: ').'</p>';
		echo "<p><a href=\"$starturl\" title=\"".gettext("Refresh image metadata.")."\" style=\"font-size: 15pt; font-weight: bold;\">".gettext("Go!")."</a></p>";
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



