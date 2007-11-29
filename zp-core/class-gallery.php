<?php
/*******************************************************************************
 *******************************************************************************
 * Gallery Class ***************************************************************
 ******************************************************************************/

class Gallery {

  var $albumdir = NULL;
  var $albums = NULL;
  var $options = NULL;
  var $theme;
  var $themes;
  
  function Gallery() {
    
    // Set our album directory
    $this->albumdir = getAlbumFolder();
    
    if (!is_dir($this->albumdir) || !is_readable($this->albumdir)) {
      $msg = "Error: The 'albums' directory (" . $this->albumdir . ") ";
      if (!is_dir($this->albumdir)) { 
        $msg .= "cannot be found.";
      } else {
        $msg .= "is not readable.";
      }
      die($msg);
    }
    getOption('nil'); // force loading of the $options
  }
  
  // The main albums directory
  function getAlbumDir() { return $this->albumdir; }
  
  function getGallerySortKey($sorttype=null) {
    if (is_null($sorttype)) { $sorttype = getOption('gallery_sorttype'); }
    switch ($sorttype) {
      case "Title":
        return 'title';
      case "Filename":
        return 'folder';
      case "Date":
        return 'date';
    }
    return 'sort_order';
  }

  
  /**
   * Get Albums will create our $albums array with a fully populated set of Album
   * objects in the correct order.
   *
   * @param $page An option parameter that can be used to return a slice of the array. 
   * 
   * @return  An array of Albums.
   */
  function getAlbums($page=0, $sorttype=null) {
    
    // Have the albums been loaded yet?
    if (is_null($this->albums)) {
      
      $albumnames = $this->loadAlbumNames();
	  $key = $this->getGallerySortKey($sorttype);
	  if (getOption('gallery_sortdirection')) { $key .= ' DESC'; }
      $albums = sortAlbumArray($albumnames, $key);
      
      // Store the values
      $this->albums = $albums;
    }
    
    if ($page == 0) { 
      return $this->albums;
    } else {
      $albums_per_page = getOption('albums_per_page');
      return array_slice($this->albums, $albums_per_page*($page-1), $albums_per_page);
    }
  }
  
    /**
   * Load all of the albums names that are found in the Albums directory on disk.
   *
   * @return An array of album names.
   */
  function loadAlbumNames() {
    $albumdir = $this->getAlbumDir();
    if (!is_dir($albumdir) || !is_readable($albumdir)) {
      $msg = "Error: The 'albums' directory (" . $this->albumdir . ") ";
      if (!is_dir($albumdir)) { 
        $msg .= "cannot be found.";
      } else {
        $msg .= "is not readable.";
      }
      die($msg);
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
        ksort($themes);
      }
      $this->themes = $themes;
    }
    return $this->themes;
  }


  /**
   * Returns the foldername of the current theme. 
   * Uses the 'current_theme' option first, then the theme.txt file,
   * and if they don't exist or the theme in it doesn't exist, then the default theme is used.
   */
  function getCurrentTheme() {
    if (empty($this->theme)) {
      if (is_null($theme=getOption('current_theme'))) {
        $themefile = SERVERCACHE . "/theme.txt";
        $theme = "";
        if (is_readable($themefile) && $fp = @fopen($themefile, "r")) {
          $theme = fgets($fp);
          $themes = $this->getThemes();
          if (!isset($themes[$theme])) {
            $theme = "";
          }
          fclose($fp);
        }
      } else {
        $themes = $this->getThemes();
        if (!isset($themes[$theme])) {
          $theme = "";
        }       
      }
      if (empty($theme)) { $theme = "default"; }
      setOptionDefault('current_theme', $theme);
      $this->theme = $theme;
    }
    return $this->theme;
  }


  /**
   * Sets the current theme by writing it to the theme.txt file in the cache folder.
   * TODO: use the database for this instead, with an options or prefs table.
   */
  function setCurrentTheme($theme) {
    setOption('current_theme', $theme);
  }
  

  /**
   * getNumImages() - efficiently get the number of images from a database SELECT count(*)
   * Ideally one should call garbageCollect() before to make sure the database is current.
   */
  function getNumImages() {
    $result = query_single_row("SELECT count(*) FROM ".prefix('images'));
    return array_shift($result);
  }

  
  function getNumComments($moderated=false) {
    $sql = "SELECT count(*) FROM ".prefix('comments');
    if (!$moderated) { 
      $sql .= " WHERE inmoderation = 0";
    }
    $result = query_single_row($sql);
    return array_shift($result);
  }
  

  function getAllComments($moderated=false) {
    $sql = "SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website,"
        . " (c.date + 0) AS date, c.comment, c.email FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, "
        .prefix('albums')." AS a ". " WHERE c.imageid = i.id AND i.albumid = a.id";
    if (!$moderated) { 
      $sql .= " AND inmoderation = 0";
    }
    $sql .= " ORDER BY c.id DESC ";
    $result = query_full_array($sql);
    return $result;
  }
  
    
  /* For every album in the gallery, look for its file. Delete from the database
   * if the file does not exist.
   * $cascade - garbage collect every image and album in the gallery as well.
   * $full    - garbage collect every image and album in the *database* - completely cleans the database.
   */
  function garbageCollect($cascade=true, $full=false) {
    // Check for the existence of top-level albums (subalbums handled recursively).
    $result = query("SELECT * FROM " . prefix('albums') . " WHERE `parentid` IS NULL");
    $dead = array();
    $live = array();
    // Load the albums from disk
    $files = $this->loadAlbumNames();
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['folder'], $files) || in_array($row['folder'], $live)) {
        $dead[] = $row['id'];
      } else {
        $live[] = $row['folder'];
      }
    }

    if (count($dead) > 0) { /* delete the dead albums from the DB */
      $first = array_pop($dead);
      $sql1 = "DELETE FROM " . prefix('albums') . " WHERE `id` = '$first'";
      $sql2 = "DELETE FROM " . prefix('images') . " WHERE `albumid` = '$first'";
      foreach ($dead as $albumid) {
        $sql1 .= " OR `id` = '$albumid'";
        $sql2 .= " OR `albumid` = '$albumid'";
      }
      $n = query($sql1);
      if (!$full && $n > 0 && $cascade) {
        query($sql2);
      }
    }
    
    if ($full) {
    
      /* Delete all image entries that don't belong to an album at all. */
      
      $albumids = query_full_array("SELECT `id` FROM " . prefix('albums'));                  /* all the album IDs */
      $idsofalbums = array();
      foreach($albumids as $row) { $idsofalbums[] = $row['id']; } 
      $imageAlbums = query_full_array("SELECT DISTINCT `albumid` FROM " . prefix('images')); /* albumids of all the images */
      $albumidsofimages = array();
      foreach($imageAlbums as $row) { $albumidsofimages[] = $row['albumid']; } 
      $orphans = array_diff($albumidsofimages, $idsofalbums);                                /* albumids of images with no album */
      
      if (count($orphans) > 0 ) { /* delete dead images from the DB */
        $firstrow = array_pop($orphans);
        $sql = "DELETE FROM ".prefix('images')." WHERE `albumid`='" . $firstrow . "'";
        foreach($orphans as $id) $sql .= " OR `albumid`='" . $id . "'";
        query($sql);
        
        // Then go into existing albums recursively to clean them... very invasive.
        foreach ($this->getAlbums(0) as $folder) {
          $album = new Album($this, $folder);
		  if(is_null($album->getDateTime())) {  // see if we can get one from an image
		    $image = $album->getImage(0);
			if(!($image === false)) {
			  $album->setDateTime($image->getDateTime());
			}
		  }
          $album->garbageCollect(true);
          $album->preLoad();
        }
      }
      
      /* Look for image records where the file no longer exists. While at it, check for images with IPTC data to update the DB */
      
      $deadman = strtotime('+ 10 sec');  // protect against too much processing.
      
      $images = query_full_array('SELECT `id`, `albumid`, `filename`, `desc`, `title`, `date`, `tags`, `mtime` FROM ' . prefix('images') . ';');
      foreach($images as $image) {

        $sql = 'SELECT `folder` FROM ' . prefix('albums') . ' WHERE `id`="' . $image['albumid'] . '";';
        $row = query_single_row($sql);      
        $imageName = getAlbumFolder() . $row['folder'] . '/' . $image['filename'];
        if (file_exists($imageName)) {
          
          if ($image['mtime'] != filemtime($imageName)) { // file has changed since we last saw it
            /* check metadata */
            $metadata = getImageMetadata($imageName);
            $set = '';
           
            /* title */
            $defaultTitle = substr($image['filename'], 0, strrpos($image['filename'], '.'));
            if (empty($defaultTitle )) {
              $defaultTitle = $image['filename'];
            }          
            if ($defaultTitle == $image['title']) { /* default title */
              if (isset($metadata['title'])) {
                $set = ',`title`="' . mysql_real_escape_string($metadata['title']) . '"'; 
              }
            }
          
            /* description */
            if (is_null($row['desc'])) {
              if (isset($metadata['desc'])) {
                $set .= ', `desc`="' . mysql_real_escape_string($metadata['desc']) . '"'; 
              }
            } 
			
			/* tags */
            if (is_null($row['tags'])) {
              if (isset($metadata['tags'])) {
                $set .= ', `tags`="' . mysql_real_escape_string($metadata['tags']) . '"'; 
              }
            }
			
			/* location, city, state, and country */
            if (isset($metadata['location'])) {
               $set .= ', `location`="' . mysql_real_escape_string($metadata['location']) . '"'; 
            }    
            if (isset($metadata['city'])) {
               $set .= ', `city`="' . mysql_real_escape_string($metadata['city']) . '"'; 
            }    
            if (isset($metadata['state'])) {
               $set .= ', `state`="' . mysql_real_escape_string($metadata['state']) . '"'; 
            }    
            if (isset($metadata['country'])) {
               $set .= ', `state`="' . mysql_real_escape_string($metadata['country']) . '"'; 
            }    
 			/* credit & copyright */
 	        if (isset($metadata['credit'])) {
               $set .= ', `credit`="' . escape($metadata['credit']) . '"'; 
            }    
 	        if (isset($metadata['copyright'])) {
              $set .= ', `copyright`="' . escape($metadata['copyright']) . '"'; 
			}    
          
            /* date (for sorting) */
            $newDate = strftime('%Y-%m-%d %T', filectime($imageName));
            if (isset($metadata['date'])) {
              $newDate = $metadata['date'];
            }          
            $set .= ', `date`="'. $newDate . '"';      

            /* update DB is necessary */
            $sql = "UPDATE " . prefix('images') . " SET `EXIFValid`=0,`mtime`=" . filemtime($imageName) . $set . " WHERE `id`='" . $image['id'] ."'";         
            query($sql);
          
            if (time() > $deadman) { return true; }    // avoide excessive processing
          } 
        } else {
          $sql = 'DELETE FROM ' . prefix('images') . ' WHERE `id`="' . $image['id'] . '";';
          $result = query($sql);   
          $sql = 'DELETE FROM ' . prefix('comments') . ' WHERE `imageid` ="' . $image['id'] . '";'; 
          $result = query($sql);
        }
      }    
      
      /* clean the comments table */
      
      $imageids = query_full_array('SELECT `id` FROM ' . prefix('images'));                          /* all the image IDs */
      $idsofimages = array();
      foreach($imageids as $row) { $idsofimages[] = $row['id']; }
      $commentImages = query_full_array("SELECT DISTINCT `imageid` FROM " . prefix('comments'));     /* imageids of all the comments */
      $imageidsofcomments = array();
      foreach($commentImages as $row) { $imageidsofcomments [] = $row['imageid']; } 
      $orphans = array_diff($imageidsofcomments , $idsofimages );                                    /* imageids of comments with no image */      
      
      if (count($orphans) > 0 ) { /* delete dead comments from the DB */
        $firstrow = array_pop($orphans);
        $sql = "DELETE FROM " . prefix('comments') . "WHERE `imageid`='" . $firstrow . "'";
        foreach($orphans as $id) $sql .= " OR `imageid`='" . $id . "'";
        query($sql);
      }
    }
  return false;  
  }
  
  
  /**
   * Returns the size in bytes of the cache folder. WARNING: VERY SLOW.
   */
  function sizeOfCache() {
    $cachefolder = SERVERCACHE;
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
    $imagefolder = substr(getAlbumFolder(), 0, -1);
    if (is_dir($imagefolder)) {
      return dirsize($imagefolder);
    } else {
      return 0;
    }
  }
  

/** 
   * Cleans out the cache folder 
   */
  function clearCache($cachefolder=NULL) {
    if (is_null($cachefolder)) {
      $cachefolder = SERVERCACHE;
	}
    if (is_dir($cachefolder)) {
	  $handle = opendir($cachefolder);
      while (false !== ($filename = readdir($handle))) {
	    $fullname = $cachefolder . '/' . $filename;
	    if (is_dir($fullname)) {
		  if (($filename != '.') && ($filename != '..')) {
		    $this->clearCache($fullname);
			rmdir($fullname);
		  }
		} else {
          if (file_exists($fullname)) {
            unlink($fullname); 
		  }
	    }

      }
	closedir($handle);
	}
  }
    
}
?>
