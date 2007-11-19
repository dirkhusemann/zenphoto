<?php
/* *****************************************************************************
 *******************************************************************************
 * Album Class *****************************************************************
 ******************************************************************************/

class Album extends PersistentObject {

  var $name;             // Folder name of the album (full path from the albums folder) 
  var $exists = true;    // Does the folder exist?
  var $images = null;    // Full images array storage.
  var $subalbums = null; // Full album array storage.
  var $parent = null;    // The parent album name
  var $parentalbum = null; // The parent album's album object (lazy)
  var $gallery;
  var $index;
  var $themeoverride;

  // Constructor
  function Album(&$gallery, $folder, $cache=true) {
    $folder = sanitize_path($folder);
    
    $this->name = $folder;
    $this->gallery = &$gallery;
    if ($folder == '') {
	  $this->localpath = getAlbumFolder();
	} else {
	  $this->localpath = getAlbumFolder() . $folder . "/"; 
	}

    // Second defense against upward folder traversal:
    if(!file_exists($this->localpath) || strpos($this->localpath, '..') !== false) {
      $this->exists = false;
      return false;
    }
    parent::PersistentObject('albums', array('folder' => $this->name), 'folder', $cache);
    
  }
  
  
  function setDefaults() {
    // Set default data for a new Album (title and parent_id)
    $parentalbum = $this->getParent();
    $title = trim(str_replace(array('-','_','+','~'), ' ', $this->name));
    if (!is_null($parentalbum)) {
      $this->set('parentid', $parentalbum->getAlbumId());
      $title = substr($title, strrpos($title, '/')+1);
	  $this->set('subalbum_sort_type', $parentalbum->getSubalbumSortType());
      $this->set('album_sortdirection',$parentalbum->getSortDirection('album'));
      $this->set('sort_type', $parentalbum->getSortType());
      $this->set('image_sortdirection', $parentalbum->getSortDirection('image'));   
    } else {
	  $this->set('subalbum_sort_type', getOption('gallery_sorttype'));
	  $this->set('album_sortdirection',getOption('gallery_sortdirection'));
	  $this->set('sort_type', getOption('image_sorttype'));
	  $this->set('image_sortdirection',getOption('image_sortdirection'));
	}
    $this->set('title', $title);
    
    return true;
  }
  
  // Folder on the filesystem
  function getFolder() { return $this->name; }
  
  // The id of this album in the db
  function getAlbumID() { return $this->id; }
  
  // The parent Album of this Album. NULL if this is a top-level album.
  function getParent() {
    if (is_null($this->parentalbum)) {
      $slashpos = strrpos($this->name, "/");
      if ($slashpos) {
        $parent = substr($this->name, 0, $slashpos);
        $parentalbum = new Album($this->gallery, $parent);
        if ($parentalbum->exists) {
          return $parentalbum;
        }
      }
    } else if ($this->parentalbum->exists) {
      return $this->parentalbum;
    }
    return NULL;   
  }
  
  // Title
  function getTitle() { return $this->get('title'); }
  function setTitle($title) { $this->set('title', $title); }
  
  // Description
  function getDesc() { return $this->get('desc'); }
  function setDesc($desc) { $this->set('desc', $desc); }
  
  // Tags
  function getTags() { return $this->get('tags'); }
  function setTags($tags) { $this->set('tags', $tags); }
  
  // Date/Time
  function getDateTime() { return $this->get('date'); }
  function setDateTime($datetime) { 
    if ($datetime == "") {
	  $this->set('date', '0000-00-00 00:00:00');
	} else {
      $time = @strtotime($datetime);
      if ($time == -1 || $time == false) return;
      $this->set('date', date('Y-m-d H:i:s', $time)); 
	}
  }
  
  // Place
  function getPlace() { return $this->get('place'); }
  function setPlace($place) { $this->set('place', $place); }
  
  // Sort type
  function getSortDirection($what) {
    if ($what == 'image') {
	  return $this->get('image_sortdirection');
    } else {
	  return $this->get('album_sortdirection');
    }
  }
  function setSortDirection($what, $val) {
    if ($val) { $b = 1; } else { $b = 0; }
    if ($what == 'image') {
	  $this->set('image_sortdirection', $b);
    } else {
	  $this->set('album_sortdirection', $b);
    }
  }
  
  function getSortType() { return $this->get('sort_type'); }
  function setSortType($sorttype) { $this->set('sort_type', $sorttype); }
  
  function getSubalbumSortType() { return $this->get('subalbum_sort_type'); }
  function setSubalbumSortType($sorttype) { $this->set('subalbum_sort_type', $sorttype); }
  
  function getSortOrder() { return $this->get('sort_order'); }
  function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }
  
  function getSortKey($sorttype=null) {
    if (is_null($sorttype)) { $sorttype = $this->getSortType(); }
    switch ($sorttype) {
      case "Title":
        return 'title';
      case "Manual":
        return 'sort_order';
      case "Date":
        return 'date';
    }
    return 'filename';
  }
  function getSubalbumSortKey($sorttype=null) {
    if (is_null($sorttype)) { $sorttype = $this->getSubalbumSortType(); }
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

  // Show this album?
  function getShow() { return $this->get('show'); }
  function setShow($show) { $this->set('show', $show ? 1 : 0); }
  
  /**
   * Returns all the subdirectories in this album as Album objects (sub-albums).
   * @param page  Which page of subalbums to display.
   * @return an array of Album objects.
   */
   
  /* modified by s.billard to add paging for subalbums thumbs, sorting of subalbums */
   
  function getSubAlbums($page=0, $sorttype=null) {
    if (is_null($this->subalbums)) {
      $dirs = $this->loadFileNames(true);
      $subalbums = array();
      
      foreach ($dirs as $dir) {
        $dir = $this->name . '/' . $dir;
        $subalbums[] = $dir;
      }
	  $key = $this->getSubalbumSortKey($sorttype);
	  if ($this->getSortDirection('album')) { $key .= ' DESC'; }
      $sortedSubalbums = sortAlbumArray($subalbums, $key); 
      $this->subalbums = $sortedSubalbums;
    }
    if ($page == 0) { 
      return $this->subalbums;
    } else { 
      $albums_per_page = getOption('albums_per_page');
      return array_slice($this->subalbums, $albums_per_page*($page-1), $albums_per_page);
    }
  }
  
  /**
   * Get Images will return all of a slice of the images for this album. They will
   * also be sorted according to the sort type of this album, or by filename if none
   * has been set.
   *
   * @param  page  Which page of images should be returned.
   * @return An array of Image objects.
   */
  function getImages($page=0, $firstPageCount=0, $sorttype=null) {

//echo "\n<br>getImages";        
//echo "\n<br>Page $page";

   if (is_null($this->images)) {
      // Load, sort, and store the images in this Album.
      $images = $this->loadFileNames();
      $images = $this->sortImageArray($images, $sorttype);
      $this->images = $images;
    }
    // Return the cut of images based on $page. Page 0 means show all.
    if ($page == 0) { 
      return $this->images;
    } else {
    // Only return $firstPageCount images if we are on the first page and $firstPageCount > 0
      if (($page==1) && ($firstPageCount>0)) {
        $pageStart = 0;
        $images_per_page = $firstPageCount;

//echo "\n<br>firstPage: $firstPageCount";                
        
      } else {
        if ($firstPageCount>0) { 
          $fetchPage = $page - 2; 
        } else {
          $fetchPage = $page - 1;
        } 
        $images_per_page = getOption('images_per_page');
        $pageStart = $firstPageCount + $images_per_page * $fetchPage;

//echo "\n<br>firstPageCount: $firstPageCount, images_per_page: $images_per_page";                
//echo "\n<br>pageStart: $pageStart<br/>\n";                

      }  
      $slice = array_slice($this->images, $pageStart , $images_per_page);
      
//echo "\n<br/>this->images<br/>\n";
//print_r($this->images);
//echo "\n<br/>Slice<br/>\n";
//print_r($slice);

      return $slice;
    }
  }
  
  
  /**
   * sortImageArray will sort an array of Images based on the given key. The
   * key must be one of (filename, title, sort_order) at the moment.
   *
   * @param images The array of filenames to be sorted.
   * @return A new array of filenames sorted according to the set key.
   */
  function sortImageArray($images, $sorttype=null) {
    global $_zp_loggedin;
	
	$hidden = array();
    $key = $this->getSortKey($sorttype);
	if ($this->getSortDirection('image')) { $key .= ' DESC'; }
    $result = query("SELECT filename, title, sort_order, `show` FROM " . prefix("images")
      . " WHERE albumid=" . $this->id . " ORDER BY " . $key);

    $i = 0;
    $images_to_keys = array_flip($images);
    $images_in_db = array();
    while ($row = mysql_fetch_assoc($result)) {
      $filename = $row['filename'];
      // If the image is on the filesystem, but not yet processed, give it the next key:
      // TODO: We should mark this album for garbage collection if filenames are discovered.
      if (array_key_exists($filename, $images_to_keys) && !in_array($filename, $images_in_db)) {
        $images_to_keys[$filename] = $i;
        $images_in_db[] = $filename;
		if (!$_zp_loggedin && !$row['show']) { $hidden[] = $filename; }
        $i++;
      }
    }

    // Place the images not yet in the database before those with sort columns.
	// This is consistent with the sort oder of a NULL sort_order key in manual sorts
    $images_not_in_db = array_diff($images, $images_in_db);
    foreach($images_not_in_db as $filename) {
      $images_to_keys[$filename] = -$i;
      $i++;
    }
	
	foreach($hidden as $filename) {
	  unset($images_to_keys[$filename]);
	}
	
    $images = array_flip($images_to_keys);
    ksort($images);
    return $images;
  }
  

  function getNumImages() {
    if (is_null($this->images)) { 
      $this->getImages(0);
    }
    return count($this->images);
  }
  
  function getImage($index) {
    if ($index >= 0 && $index < $this->getNumImages()) {
      if (!is_null($this->images)) {
        // Get the image from the array if we already have it, but...
        return new Image($this, $this->images[$index]);
      } else {
        // if possible, run a single query instead of getting all images.
        $key = $this->getSortKey();
	    if ($this->getSortDirection('image')) { $key .= ' DESC'; }
        $result = query("SELECT filename FROM " . prefix("images") 
            . " WHERE albumid=" . $this->id . " ORDER BY $key LIMIT $index,1");
        $filename = mysql_result($result, 0);
        return new Image($this, $filename);        
      }
    }
    return false;
  }

  /**
   * Gets the album's set thumbnail image from the database if one exists,
   * otherwise, finds the first image in the album or sub-album and returns it
   * as an Image object.
   * TODO: This should fail more gracefully when there are errors reading folders etc.
   */
  function getAlbumThumbImage() {
    $albumdir = getAlbumFolder() . $this->name ."/";
    $thumb = $this->get('thumb');
	$i = strpos($thumb, '/');
	if ($root = ($i === 0)) { 
		$thumb = substr($thumb, 1); /* strip off the slash */ 
		$albumdir = getAlbumFolder(); 
	}
    if ($thumb != NULL && file_exists($albumdir.$thumb)) {
      if ($i===false) { 
	  	return new Image($this, $thumb); 
	} else { 
		$pieces = explode('/', $thumb); 
		$i = count($pieces); 
		$thumb = $pieces[$i-1]; 
		unset($pieces[$i-1]); 
		$albumdir = implode('/', $pieces); 
	  if (!$root) { $albumdir = $this->name . "/" . $albumdir; } else { $albumdir = $albumdir . "/";} 
	  	return new Image(new Album($this->gallery, $albumdir), $thumb); 
	} 
    } else {
      $dp = opendir($albumdir);
      while ($thumb = readdir($dp)) {
        if (is_file($albumdir.$thumb) && is_valid_image($thumb)) {
          return new Image($this, $thumb);
        }
      }
      // Otherwise, look in sub-albums.
      $subalbums = $this->getSubAlbums();
      foreach ($subalbums as $subdir) {
        $subalbum = new Album($this->gallery, $subdir);
        $thumb = $subalbum->getAlbumThumbImage();
        if ($thumb != NULL && $thumb->exists) {
          return $thumb;
        }
      }
	  //jordi-kun - no images, no subalbums, check for videos
	$dp = opendir($albumdir);
	while ($thumb = readdir($dp)) {
		if (is_file($albumdir.$thumb) && is_valid_video($thumb)) {
			return new Image($this, $thumb);
		}
	}
    }
    $noImage = new Album($this->gallery, '');
    return new image($noImage, 'zen-logo.jpg');
  }
  
  /**
   * Gets the thumbnail URL for the album thumbnail image as returned by $this->getAlbumThumbImage();
   */
  function getAlbumThumb() {
    $image = $this->getAlbumThumbImage();
    return $image->getThumb();
  }
  
  function setAlbumThumb($filename) { $this->set('thumb', $filename); }
  
  function getNextAlbum() {
    if (is_null($parent = $this->getParent())) {
     $albums = $this->gallery->getAlbums(0);
    } else { 
     $albums = $parent->getSubAlbums(0);
    }
    $inx = array_search($this->name, $albums)+1;
    if ($inx >= 0 && $inx < count($albums)) {
      return new Album($parent, $albums[$inx]);
    } 
    return null;
  }
  
  function getPrevAlbum() {
    if (is_null($parent = $this->getParent())) {
     $albums = $this->gallery->getAlbums(0);
    } else { 
     $albums = $parent->getSubAlbums(0);
    }
    $inx = array_search($this->name, $albums)-1;
    if ($inx >= 0 && $inx < count($albums)) {
      return new Album($paraent, $albums[$inx]);
    } 
    return null;
  }
  
  function getGalleryPage() {
    $albums_per_page = getOption('albums_per_page');
    if ($this->index == null)
      $this->index = array_search($this->name, $this->gallery->getAlbums(0));
    return floor(($this->index / $albums_per_page)+1);
  }
  
  
  /**
   * Delete the entire album PERMANENTLY. Be careful! This is unrecoverable.
   */
  function deleteAlbum() {
    foreach ($this->getSubAlbums() as $folder) {  
	  $subalbum = new Album($album, $folder); 
	  $subalbum->deleteAlbum(); 
	}
    foreach($this->getImages() as $filename) {
      // False here means don't clean up (cascade already took care of it)
      $image = new Image($this, $filename);
      $image->deleteImage(false);
    }
    query("DELETE FROM " . prefix('albums') . " WHERE `id` = '" . $this->id . "'");
    return rmdir($this->localpath); 
  }
  
  // Are comments allowed?
  function getCommentsAllowed() { return $this->get('commentson'); }
  function setCommentsAllowed($commentson) { $this->set('commentson', $commentson ? 1 : 0); }
  
  /**
   * For every image in the album, look for its file. Delete from the database
   * if the file does not exist. Same for each sub-directory/album.
   */
  function garbageCollect($deep=false) {
    if (is_null($this->images)) $this->getImages();
    $result = query("SELECT * FROM ".prefix('images')." WHERE `albumid` = '" . $this->id . "'");
    $dead = array();
    $live = array();

    $files = $this->loadFileNames();

    // Does the filename from the db row match any in the files on disk?
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['filename'], $files)) {
        // In the database but not on disk. Kill it.
        $dead[] = $row['id'];
      } else if (in_array($row['filename'], $live)) {
        // Duplicate in the database. Kill it.
        $dead[] = $row['id'];
        // Do something else here? Compare titles/descriptions/metadata/update dates to see which is the latest?
      } else {
        $live[] = $row['filename'];
      }
    }
    
    if (count($dead) > 0) {
      $sql = "DELETE FROM ".prefix('images')." WHERE `id` = '" . array_pop($dead) . "'";
      foreach ($dead as $img) $sql .= " OR `id` = '$img'";
      query($sql);
    }
    
    // Get all sub-albums and make sure they exist.
    $result = query("SELECT * FROM ".prefix('albums')." WHERE `folder` LIKE '" . mysql_real_escape_string($this->name) . "/%'");
    $dead = array();
    $live = array();
    // Does the dirname from the db row exist on disk?
    while($row = mysql_fetch_assoc($result)) {
      if (!is_dir(getAlbumFolder() . $row['folder']) || in_array($row['folder'], $live)
          || substr($row['folder'], -1) == '/' || substr($row['folder'], 0, 1) == '/') {
        $dead[] = $row['id'];
      } else {
        $live[] = $row['folder'];
      }
    }
    if (count($dead) > 0) {
      $sql = "DELETE FROM ".prefix('albums')." WHERE `id` = '" . array_pop($dead) . "'";
      foreach ($dead as $albumid) $sql .= " OR `id` = '$albumid'";
      query($sql);
    }
    
    if ($deep) {
      foreach($this->getSubAlbums(0) as $dir) {
        $subalbum = new Album($this->gallery, $dir);
        // Could have been deleted if it didn't exist above...
        if ($subalbum->exists)
          $subalbum->garbageCollect($deep);
      }
    }
  }


  /**
   * Simply creates objects of all the images and sub-albums in this album to
   * load accurate values into the database.
   */
  function preLoad() {
    $images = $this->getImages(0);
    foreach($images as $filename) {
      $image = new Image($this, $filename);
    }
    $subalbums = $this->getSubAlbums(0);
    foreach($subalbums as $dir) {
      $album = new Album($this->gallery, $dir);
      $album->preLoad();
    }
  }

  
  /**
   * Load all of the filenames that are found in this Albums directory on disk.
   * @return An array of file names.
   * @param  $dirs Whether or not to return directories ONLY with the file array. Default is false.
   */
  function loadFileNames($dirs=false) {
    $albumdir = getAlbumFolder() . $this->name . "/";
    if (!is_dir($albumdir) || !is_readable($albumdir)) {
      die("The album cannot be found.");
    }
    $dir = opendir($albumdir);
    $files = array();

    while (false !== ($file = readdir($dir))) {
      if (($dirs && is_dir($albumdir.$file) && substr($file, 0, 1) != '.')
          || (!$dirs && is_file($albumdir.$file) && 
          !is_videoThumb($albumdir,$file) && ( is_valid_image($file) || is_valid_video($file)))) {
        $files[] = $file;
      }
    }
    closedir($dir);
    
    return $files;
  }
  
}
  
  
?>