<?php


// classes.php - HEADERS STILL NOT SENT!


// Load the database functions.
require_once("auth_zp.php");

/**********************************************************************/
// Image Class //

class Image {

  var $filename;  // true filename of the image.
  var $webpath;   // The full URL path to the original image.
  var $localpath; // The full SERVER path to the original image.
  var $name;      // $filename with the extension stripped off.
  var $imageid;   // From the database; simplifies queries.
  var $album;     // An album object for the album containing this image.
  var $meta;      // Image metadata array.
  var $comments;  // Image comment array.
  var $index;     // The index of the current image in the album array.

  // Constructor
  function Image($album, $filename) {
    // $album is an Album object; it should already be created.
    $this->album = $album;
		$this->webpath = WEBPATH . "/albums/".$album->name."/".$filename;
		$this->localpath = SERVERPATH . "/albums/".$album->name."/".$filename;
		// Check if the file exists.
		if(!file_exists($this->localpath)) {
			die("Image <strong>{$this->localpath}</strong> does not exist.");
		}
		$this->filename = $filename;
    $this->name = $filename; // Strip the extension?
    $this->comments = null;
    // Query the database for an Image entry with the given filename/albumname
    $entry = query_single_row("SELECT * FROM ".prefix("images").
      " WHERE `filename`='".mysql_escape_string($filename).
      "' AND `albumid`='".mysql_escape_string($this->album->albumid)."' LIMIT 1;");
    if (!$entry) {
      $this->meta['title'] = $filename;
      $this->meta['desc']  = null;
      $this->meta['commentson'] = 1;
      $this->meta['show'] = 1;
      query("INSERT INTO ".prefix("images")." (albumid, filename, title) " .
            "VALUES ('".mysql_escape_string($this->album->albumid).
            "', '".mysql_escape_string($filename).
            "', '".mysql_escape_string($this->meta['title'])."');");
      $this->imageid = mysql_insert_id();
    } else {
      $this->meta['title'] = $entry['title'];
      $this->meta['desc']  = $entry['desc'];
      $this->meta['commentson'] = $entry['commentson'];
      $this->meta['show'] = $entry['show'];
      $this->imageid = $entry['id'];
    }
  }


  function getTitle() {
    return $this->meta['title'];
  }
  function setTitle($title) {
    $this->meta['title'] = $title;
    // Update the database entry with the new title.
    query("UPDATE ".prefix("images")." SET `title`='" . mysql_escape_string($title) .
          "' WHERE `id`=".$this->imageid);
  }
	
	function getAlbum() {
		return $this->album;
	}
	function getAlbumName() {
		return $this->album->name;
	}


  function getDesc() {
    return $this->meta['desc'];
  }
  function setDesc($desc) {
    $this->meta['desc'] = $desc;
    query("UPDATE ".prefix("images")." SET `desc`='" . mysql_escape_string($desc) .
          "' WHERE `id`=".$this->imageid);
  }


  function getShow() { return $this->meta['show']; }
  function setShow($show) {
    if ($show) $show = 1;
    else       $show = 0;
    $this->meta['show'] = $show;
    query("UPDATE ".prefix("images")." SET `show`='" . $show .
          "' WHERE `id`=".$this->imageid);
  }
  
  function getCommentsAllowed() { return $this->meta['commentson']; }
  function setCommentsAllowed($commentson) {
    if ($commentson) $commentson = 1;
    else             $commentson = 0;
    $this->meta['commentson'] = $commentson;
    query("UPDATE ".prefix("images")." SET `commentson`='" . $commentson .
          "' WHERE `id`=".$this->imageid);
  }


  function getComments() {
    $comments = query_full_array("SELECT *, (date + 0) AS date FROM " . prefix("comments") . 
       " WHERE imageid='" . $this->imageid . "'");
    $this->comments = $comments;
    return $this->comments;
  }
  
  function addComment($name, $email, $website, $comment) {
    $this->getComments();
    $name = trim($name);
    $email = trim($email);
    $website = trim($website);
    // Let the comment have trailing line breaks and space? Nah...
    // Also (in)validate HTML here, and in $name.
    $comment = trim($comment);
    if (empty($email) || !is_email($email) || empty($name) || empty($comment)) {
      return false;
    }
    
    if (!empty($website) && substr($website, 0, 7) != "http://") {
      $website = "http://" . $website;
    }
    
    $newcomment = array();
    $newcomment['name'] = $name;
    $newcomment['email'] = $email;
    $newcomment['website'] = $website;
    $newcomment['comment'] = $comment;
    $newcomment['date'] = time();
    $this->comments[] = $newcomment;
    // Update the database entry with the new comment
    query("INSERT INTO " . prefix("comments") . " (imageid, name, email, website, comment, date) VALUES " .
          " ('" . $this->imageid .
          "', '" . mysql_escape_string($name) . 
          "', '" . mysql_escape_string($email) . 
          "', '" . mysql_escape_string($website) . 
          "', '" . mysql_escape_string($comment) . 
          "', NOW())");
    return true;
  }

  function getCommentCount() { 
    if (!isset($this->meta['commentcount'])) {
      if ($this->comments == null) {
        $count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE imageid = " . $this->imageid);
        $this->meta['commentcount'] = array_shift($count);
      } else {
        $this->meta['commentcount'] = count($this->comments);
      }
    }
    return $this->meta['commentcount'];
  }

  // Returns a path to the original image in the original folder.
  function getFullImage() {
    return $this->webpath;
  }

  function getSizedImage($size) {
    if (zp_conf('mod_rewrite')) {
      return WEBPATH."/".urlencode($this->album->name)."/image/".$size."/".urlencode($this->filename);
    } else {
      return WEBPATH."/zen/i.php?a=" . urlencode($this->album->name) . "&i=" . urlencode($this->filename) . "&s=" . $size;
    }
  }

  function getThumb() {
    if (zp_conf('mod_rewrite')) {
      return WEBPATH."/" . urlencode($this->album->name) . "/image/thumb/" . urlencode($this->filename);
    } else {
      return WEBPATH."/zen/i.php?a=" . urlencode($this->album->name) . "&i=" . urlencode($this->filename) . "&s=thumb";
    }
  }
	
	// Returns the filename of the next/prev image.
	function getNextImage() {
    if ($this->index == null)
      $this->index = array_search($this->filename, $this->album->getImages(0));
		return $this->album->getImage($this->index+1);
	}
	
	function getPrevImage() {
		if ($this->index == null)
      $this->index = array_search($this->filename, $this->album->getImages(0));
		return $this->album->getImage($this->index-1);
	}
  
  function getAlbumPage() {
    $images_per_page = zp_conf('images_per_page');
    if ($this->index == null)
      $this->index = array_search($this->filename, $this->album->getImages(0));
    return floor(($this->index / $images_per_page)+1);
  }

  // Tag methods?

}


/**********************************************************************/
// Album Class //

class Album {

  var $name;      // Folder name of the album.
  var $albumid;   // From the database; simplifies queries.
  var $meta = array();   // Album metadata array.
	var $images = NULL;    // Full images array storage.
	var $gallery;
  var $index;

  // Constructor
  function Album($gallery, $folder) {
    // First, instantiate the album object (->$this->$album). If the album doesn't exist yet, it'll be created.
    $this->name = $folder;
		$this->gallery = $gallery;
    if(!file_exists(SERVERPATH . "/albums/".$folder)) {
			die("Album <strong>{$this->localpath}</strong> does not exist.");
		}
    // Query the database for an Image entry with the given filename/albumname
    $entry = query_single_row("SELECT * FROM ".prefix("albums").
      " WHERE `folder`='".mysql_escape_string($folder)."' LIMIT 1;");
    if (!$entry) {
      $this->meta['title'] = $folder;
      $this->meta['desc']  = NULL;
      $this->meta['date']  = NULL;
      $this->meta['place'] = NULL;
      $this->meta['show']  = 1;
			$this->meta['thumb'] = NULL;
      query("INSERT INTO ".prefix("albums")." (folder, title) " .
            "VALUES ('".mysql_escape_string($folder).
            "', '".mysql_escape_string($folder)."');");
      $this->albumid = mysql_insert_id();
    } else {
      $this->meta['title'] = $entry['title'];
      $this->meta['desc']  = $entry['desc'];
      $this->meta['date']  = $entry['date'];
      $this->meta['place'] = $entry['place'];
      $this->meta['show']  = $entry['show'];
			$this->meta['thumb'] = $entry['thumb'];
      $this->albumid = $entry['id'];
    }
  }
	
  function getTitle() {
    return $this->meta['title'];
  }
  function setTitle($title) {
    $this->meta['title'] = $title;
    // Update the database entry with the new title.
    query("UPDATE ".prefix("albums")." SET `title`='" . mysql_escape_string($title) .
      "' WHERE `id`=".$this->albumid);
  }
	
	function getDesc() {
    return $this->meta['desc'];
  }
  function setDesc($desc) {
    $this->meta['desc'] = $desc;
    // Update the database entry with the new description.
    query("UPDATE ".prefix("albums")." SET `desc`='" . mysql_escape_string($desc) .
      "' WHERE `id`=".$this->albumid);
  }

  function getShow() { return $this->meta['show']; }
  function setShow($show) {
    if ($show) $show = 1;
    else       $show = 0;
    $this->meta['show'] = $show;
    query("UPDATE ".prefix("albums")." SET `show`='" . $show .
          "' WHERE `id`=".$this->albumid);
  }
	
	function getImages($page=0) {
		if (is_null($this->images)) {
			$albumdir = SERVERPATH . "/albums/{$this->name}/";
			if (!is_dir($albumdir) || !is_readable($albumdir)) {
				die("The {$this->name} album cannot be found.\n");
			}
			$dp = opendir($albumdir);
			$images = array();
			while ($file = readdir($dp)) {
				if (is_file($albumdir.$file) && is_valid_image($file)) {
					$images[] = $file;
				}
			}
			// Sorting here? Alphabetical by default.
			// Store the result so we don't have to traverse the dir again.
			$this->images = $images;
		}
		// Return the cut of images based on $page. Page 0 means show all.
		if ($page == 0) { 
			return $this->images;
		} else {
			$images_per_page = zp_conf('images_per_page');
			return array_slice($this->images, $images_per_page*($page-1), $images_per_page);
		}
	}
	
	function getNumImages() {
		if (is_null($this->images)) $this->getImages();
		return count($this->images);
	}
	
	function getImage($index) {
		if (is_null($this->images)) $this->getImages();
		if ($index >= 0 && $index < $this->getNumImages())
			return $this->images[$index];
		else
			return false;
	}
	
	function getAlbumThumb() {
		$albumdir = SERVERPATH . "/albums/{$this->name}/";
		$thumb = $this->meta['thumb'];
		// TODO: Make this use the database entry if it's not null...
		if ($thumb == NULL || !file_exists($albumdir.$thumb)) {
			$dp = opendir($albumdir);
			while ($thumb = readdir($dp)) {
				if (is_file($albumdir.$thumb) && is_valid_image($thumb)) break;
			}
		}
		$image = new Image($this, $thumb);
		return $image->getThumb();
	}
	
	function getNextAlbum() {
    if ($this->index == null)
      $this->index = array_search($this->name, $this->gallery->getAlbums(0));
		return $this->gallery->getAlbum($this->index+1);
	}
	
	function getPrevAlbum() {
    if ($this->index == null)
      $this->index = array_search($this->name, $this->gallery->getAlbums(0));
		return $this->gallery->getAlbum($this->index-1);
	}
  
  function getGalleryPage() {
    $albums_per_page = zp_conf('albums_per_page');
    if ($this->index == null)
      $this->index = array_search($this->name, $this->gallery->getAlbums(0));
    return floor(($this->index / $albums_per_page)+1);
  }
  
  /* For every image in the album, look for its file. Delete from the database
   * if the file does not exist.
   */
  function garbageCollect() {
    if (is_null($this->images)) $this->getImages();
    $result = query("SELECT * FROM ".prefix('images')." WHERE `albumid` = ".$this->albumid);
    $dead = array();
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['filename'], $this->images)) {
        $dead[] = $row['id'];
      }
    }
    if (count($dead) > 0) {
      $sql = "DELETE FROM ".prefix('images')." WHERE `id` = " . array_pop($dead);
      foreach ($dead as $img) {
        $sql .= " OR `id` = $img";
      }
      query($sql);
    }
  }
  
}


/**********************************************************************/
// Group Class //
// To be implemented later.

class Group {

}

/**********************************************************************/
// Gallery Class //

class Gallery {

	var $albums = NULL;
	
	function Gallery() {
		$albumdir = SERVERPATH . "/albums/";
		if (!is_dir($albumdir) || !is_readable($albumdir)) {
			die("Error: The 'albums' directory cannot be found or is not readable.");
		}
		$dp = opendir($albumdir);
		$albums = array();
		while ($file = readdir($dp)) {
			if (is_dir($albumdir.$file) && $file != "." && $file != "..") {
				$albums[] = $file;
			}
		}
    // Reverse the order to make recently added albums first.
    $albums = array_reverse($albums);
		// Sorting here? Alphabetical by default.
		$this->albums = $albums;
	}
	
	function getAlbums($page=0) {
		if ($page == 0) { 
			return $this->albums;
		} else {
			$albums_per_page = zp_conf('albums_per_page');
			return array_slice($this->albums, $albums_per_page*($page-1), $albums_per_page);
		}
	}
	
	// Takes care of bounds checking, no need to check input.
	function getAlbum($index) {
		if ($index >= 0 && $index < $this->getNumAlbums())
			return $albums[$index];
		else
			return false;
	}
	
	function getNumAlbums() {
		return count($this->albums);
	}
  
    
  /* For every album in the gallery, look for its file. Delete from the database
   * if the file does not exist.
   * $cascade - garbage collect every image and album in the gallery as well.
   * $full    - garbage collect every image and album in the *database* - completely cleans the database.
   */
  function garbageCollect($cascade=true, $full=false) {
    $result = query("SELECT * FROM ".prefix('albums'));
    $dead = array();
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['folder'], $this->albums)) {
        $dead[] = $row['id'];
      }
    }

    if (count($dead) > 0) {
      $first = true;
      $sql = "DELETE FROM ".prefix('albums')." WHERE ";
      foreach ($dead as $folder) {
        if (!$first) $sql .= " OR";
        $sql .= "`id` = $folder";
        $first = false;
      }
      $n = query($sql);
      
      if (!$full && $n > 0 && $cascade) {
        $first = true;
        $sql = "DELETE FROM ".prefix('images')." WHERE ";
        foreach ($dead as $folder) {
          if (!$first) $sql .= " OR";
          $sql .= "`albumid` = $folder";
          $first = false;
        }
      }
    }
    
    if ($full) {
      $first = true;
      $result = query("SELECT `id` FROM ".prefix('albums'));
      if (count($this->albums) > 0) {
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
          $album->garbageCollect();
        }
      }
    }
    
  }
  

}


?>
