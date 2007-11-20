<?php
/* This template is used to generate cache images. Running it will process the entire gallery,
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
  printLogoAndLinks();

  echo "\n" . '<div id="main">';
  printTabs();
  echo "\n" . '<div id="content">';
  echo "<h1>zenphoto Metadata refresh</h1>";

  if (isset($_GET['refresh']) && db_connect()) {
    echo "<h3>Finished refreshing metadata.</h3>";
	$r = $_GET['return'];
	if (!empty($r)) {
	  $r = "?page=edit&album=$r";
	}
	echo "<p><a href=\"admin.php$r\">&laquo; Back</a></p>";
  } else if (db_connect()) {
    echo "<h3>database connected</h3>";
	$folder = '';
	$id = '';
	$r = "";
	if (isset($_GET['album'])) {
	  $folder = $_GET['album'];
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
	}
	if (!empty($folder) && empty($id)) {
	  echo "<p><em>$folder</em> not found</p>";
	} else {
      $sql = "UPDATE " . prefix('images') . " SET `mtime`=0 $id;";
	  query($sql);
      echo "<p>We're all set to refresh image metadata$r</p>";
      echo "<p><a href=\"?refresh=start&return=$folder\" title=\"Refresh image metadata.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
    }
  } else {
    echo "<h3>database not connected</h3>";
    echo "<p>Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.";
  }
    
  echo "\n" . '</div>';
  echo "\n" . '</div>';

  printAdminFooter();
}
?>
</body>
</html>