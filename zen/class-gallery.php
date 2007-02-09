<?php
/*******************************************************************************
 *******************************************************************************
 * Gallery Class ***************************************************************
 ******************************************************************************/

class Gallery {

  var $albumdir = NULL;
  var $albums = NULL;
  var $theme;
  var $themes;
  
  function Gallery() {
    
    // Set our album directory
    $this->albumdir = SERVERPATH . "/albums/";
    
    if (!is_dir($this->albumdir) || !is_readable($this->albumdir)) {
      die("Error: The 'albums' directory cannot be found or is not readable.");
    }
  }
  
  // The main albums directory
  function getAlbumDir() { return $this->albumdir; }
  
  
  /**
   * Get Albums will create our $albums array with a fully populated set of Album
   * objects in the correct order.
   *
   * @param $page An option parameter that can be used to return a slice of the array. 
   * 
   * @return  An array of Albums.
   */
  function getAlbums($page=0) {
    
    // Have the albums been loaded yet?
    if (is_null($this->albums)) {
      
      $albumnames = $this->loadAlbumNames();
      $albums = $this->sortAlbumArray($albumnames);
      
      // Store the values
      $this->albums = $albums;
    }
    
    if ($page == 0) { 
      return $this->albums;
    } else {
      $albums_per_page = zp_conf('albums_per_page');
      return array_slice($this->albums, $albums_per_page*($page-1), $albums_per_page);
    }
  }
  
  
  /**
   * Sort the album array based on either a) the manual sort order as specified by the user,
   * or b) the reverse order of how they are returned from disk. This will thus give us the
   * album list in with the the ordered albums first, followed by the rest with the newest first.
   *
   * @return A sorted array of album names.
   * 
   * @author Todd Papaioannou (lucky@luckyspin.org)
   * @since  1.0.0
   */
  function sortAlbumArray($albums) {
    
    $albums_r = array();
    
    $result = query("SELECT folder, sort_order FROM " . prefix("albums") 
      . " ORDER BY sort_order");
      
    $i = 0;
    $albums_r = array_flip($albums);
    $albums_touched = array();
    while ($row = mysql_fetch_assoc($result)) {
      $folder = $row['folder'];
      if (array_key_exists($folder, $albums_r)) {
        $albums_r[$folder] = $i;
        $albums_touched[] = $folder;
      }
      $i++;
    }
        
    $albums_untouched = array_diff($albums, $albums_touched);
    foreach($albums_untouched as $alb) {
      $albums_r[$alb] = $i;
      $i++;
    }
    $albums = array_flip($albums_r);
    ksort($albums);
    
    $albums_ordered = array();
    foreach($albums as $album) {
      $albums_ordered[] = $album;
    }
    
    return $albums_ordered;
  }
  
  /**
   * Load all of the albums names that are found in the Albums directory on disk.
   *
   * @return An array of album names.
   */
  function loadAlbumNames() {
    $albumdir = $this->getAlbumDir();
    if (!is_dir($albumdir) || !is_readable($albumdir)) {
      die("Error: The 'albums' directory cannot be found or is not readable.");
    }

    $dir = opendir($albumdir);
    $albums = array();

    while ($dirname = readdir($dir)) {
      if (is_dir($albumdir.$dirname) && substr($dirname, 0, 1) != '.') {
        $albums[] = $dirname;
      }
    }
    closedir($dir);
    
    return $albums;
  }

  
  /**
   * Returns the $index'th album in the array. Index is an integer.
   * Takes care of bounds checking, no need to check input.
   */
  function getAlbum($index) {
    $this->getAlbums();
    if ($index >= 0 && $index < $this->getNumAlbums()) {
      return new Album($this, $this->albums[$index]);
    } else {
      return false;
    }
  }


  /**
   * Returns the total number of TOPLEVEL albums in the gallery (does not include sub-albums)
   * @param $db whether or not to use the database (includes ALL detected albums) or the directories
   */
  function getNumAlbums($db=false) {
    $count = -1;
    if (!$db) {
      $this->getAlbums();
      $count = count($this->albums);
    } else {
      $sql = "SELECT count(*) FROM " . prefix('albums');
      $result = query($sql);
      $count = mysql_result($result, 0);
    } 
    return $count;
  }
  
  
  /**
   * Populates the theme array and returns it. The theme array contains information about
   * all the currently available themes.
   */
  function getThemes() {
    if (empty($this->themes)) {
      $themedir = SERVERPATH . "/themes";
      $themes = array();
      if ($dp = @opendir($themedir)) {
        while (false !== ($dir = readdir($dp))) {
          if (substr($dir, 0, 1) != "." && is_dir("$themedir/$dir")) {
            if (file_exists($themedir . "/$dir/theme.txt")) {
              $themes[$dir] = parseThemeDef($themedir . "/$dir/theme.txt");
            }
          }
        }
      }
      $this->themes = $themes;
    }
    return $this->themes;
  }


  /**
   * Returns the foldername of the current theme. Uses the theme.txt file first,
   * and if that doesn't exist or the theme in it doesn't exist, then the default theme is used.
   */
  function getCurrentTheme() {
    if (empty($this->theme)) {
      $themefile = SERVERPATH . "/cache/theme.txt";
      $theme = "";
      if (is_readable($themefile) && $fp = @fopen($themefile, "r")) {
        $theme = fgets($fp);
        $themes = $this->getThemes();
        if (!isset($themes[$theme])) {
          $theme = "";
        }
        fclose($fp);
      }
      if (empty($theme)) {
        $theme = "default";
      }
      $this->theme = $theme;
    }
    return $this->theme;
  }


  /**
   * Sets the current theme by writing it to the theme.txt file in the cache folder.
   * TODO: use the database for this instead, with an options or prefs table.
   */
  function setCurrentTheme($theme) {
    $themefile = SERVERPATH . "/cache/theme.txt";
    $themes = $this->getThemes();
    if (isset($themes[$theme]) && $fp = @fopen($themefile, "w")) {
      fwrite($fp, $theme);
      return true;
    } else {
      return false;
    }
  }
  

  /**
   * getNumImages() - efficiently get the number of images from a database SELECT count(*)
   * Ideally one should call garbageCollect() before to make sure the database is current.
   */
  function getNumImages() {
    $result = query_single_row("SELECT count(*) FROM ".prefix('images'));
    return array_shift($result);
  }

  
  function getNumComments() {
    $result = query_single_row("SELECT count(*) FROM ".prefix('comments')." WHERE inmoderation = 0");
    return array_shift($result);
  }
  

  function getAllComments() {
    $result = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website,"
        . " (c.date + 0) AS date, c.comment, c.email FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, "
        .prefix('albums')." AS a ". " WHERE c.imageid = i.id AND i.albumid = a.id AND c.inmoderation = 0 ORDER BY c.id DESC ");
    return $result;
  }
  
    
  /* For every album in the gallery, look for its file. Delete from the database
   * if the file does not exist.
   * $cascade - garbage collect every image and album in the gallery as well.
   * $full    - garbage collect every image and album in the *database* - completely cleans the database.
   */
  function garbageCollect($cascade=true, $full=false) {
    $result = query("SELECT * FROM " . prefix('albums') . " WHERE `parentid` IS NULL");
    $dead = array();
    
    // Load the albums from disk
    $files = $this->loadAlbumNames();
    
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['folder'], $files)) {
        $dead[] = $row['id'];
      }
    }

    if (count($dead) > 0) {
      $first = true;
      $sql = "DELETE FROM " . prefix('albums') . " WHERE ";
      foreach ($dead as $album) {
        if (!$first) $sql .= " OR";
        $sql .= "`id` = $album";
        $first = false;
      }
      $n = query($sql);
      
      if (!$full && $n > 0 && $cascade) {
        $first = true;
        $sql = "DELETE FROM ".prefix('images')." WHERE ";
        foreach ($dead as $album) {
          if (!$first) $sql .= " OR";
          $sql .= "`albumid` = $album";
          $first = false;
        }
      }
    }
    
    if ($full) {
      $first = true;
      // Delete all images that don't belong to an album.
      $result = query("SELECT `id` FROM " . prefix('albums'));
      if ($this->getNumAlbums() > 0) {
        $sql = "DELETE FROM ".prefix('images')." WHERE ";
        while($album = mysql_fetch_assoc($result)) {
          if (!$first) $sql .= " AND";
          $sql .= " `albumid` != ".$album['id'];
          $first = false;
        }
        query($sql);
        
        // Then go into existing albums recursively to clean them... very invasive.
        foreach ($this->albums as $folder) {
          $album = new Album($this, $folder);
          $album->garbageCollect(true);
          $album->preLoad();
        }
      }
    }
    
  }
  
  
  /**
   * Returns the size in bytes of the cache folder. WARNING: VERY SLOW.
   */
  function sizeOfCache() {
    $cachefolder = SERVERPATH . "/cache";
    if (is_dir($cachefolder)) {
      return dirsize($cachefolder);
    } else {
      return 0;
    }
  }


  /**
   * Returns the size in bytes of the albums folder. WARNING: VERY SLOW.
   */
  function sizeOfImages() {
    $imagefolder = SERVERPATH . "/albums";
    if (is_dir($imagefolder)) {
      return dirsize($imagefolder);
    } else {
      return 0;
    }
  }
  

  /** 
   * Cleans out the cache folder for older installations (deletes root-level JPGs)
   * TODO: clean out the entire folder on request.
   */
  function clearCache($folders=false) {
    $cachefolder = SERVERPATH . "/cache";
    if (is_dir($cachefolder)) {
      foreach (glob($cachefolder . "/*.jpg") as $filename) {
        if (file_exists($filename)) {
          @unlink($filename);
        }
      }
    }
    return null;
  }
  

}
?>
