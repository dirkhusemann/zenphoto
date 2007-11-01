<?php
/* This template is used to generate cache images. Running it will process the entire gallery,
	supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named. */
define('OFFSET_PATH', true);
require_once("template-functions.php");
require_once("admin-functions.php");

function loadAlbum($album) {
  global $_zp_current_album;
  $subalbums = $album->getSubAlbums();
  foreach ($subalbums as $folder) {
    $subalbum = new Album($album, $folder);
    $count = $count + loadAlbum($subalbum);
  }
  $_zp_current_album = $album;
  if (getNumImages() > 0) {
    echo "<br/>" . $album->name . "{";
    while (next_image(true)) {
      echo '<img src="' . getImageThumb() . '" height="8" width="8" /> | <img src="' . getDefaultSizedImage() . '" height="20" width="20" />' . "\n";
      $count++;
    }
    echo "}<br/>\n";
  }
  return $count;
}

if (!zp_loggedin()) {
  printLoginForm();
  exit(); 
} else {
  printAdminHeader();
  printLogoAndLinks();

  echo "\n" . '<div id="main">';
  printTabs();
  echo "\n" . '<div id="content">';


  global $_zp_gallery;
  $count = 0;
  
  if (isset($_GET['album'])) {
    $folder = strip($_GET['album']);
    echo "\n<h2>Refreshing cache for $folder</h2>";
    $album = new Album($album, $folder);
    $count = loadAlbum($album);
  } else {
    echo "\n<h2>Refreshing cache for Gallery</h2>";
    $albums = $_zp_gallery->getAlbums();
      foreach ($albums as $folder) {
      $album = new Album($album, $folder);
      $count = $count + loadAlbum($album);
    }
  }
  echo "\n" . "<br/>Finished: Total of $count images.";
  echo "\n" . '</div>';
  echo "\n" . '</div>';

  printAdminFooter();
}
?>
</body>
</html>