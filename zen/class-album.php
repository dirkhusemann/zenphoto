<?php
/*******************************************************************************
 *******************************************************************************
 * Album Class *****************************************************************
 ******************************************************************************/

class Album extends PersistentObject {

  var $name;             // Folder name of the album (full path from /albums/)
  var $exists = true;    // Does the folder exist?
  var $images = NULL;    // Full images array storage.
  var $subalbums = NULL; // Full album array storage.
  var $parent = NULL;    // The parent album name
  var $gallery;
  var $index;
  var $sort_key = 'filename';
  var $themeoverride;

  // Constructor
  function Album($gallery, $folder) {
    $folder = str_replace('//','/', $folder);
    $this->name = $folder;
    $this->gallery = $gallery;
    $this->localpath = SERVERPATH . "/albums/" . $folder . "/";
    // Second defense against reverse folder traversal:
    if(!file_exists($this->localpath) || strpos($this->localpath, '..') !== FALSE) {
      $this->exists = false;
      return false;
    }
    $new = parent::PersistentObject('albums', array('folder' => $this->name));
    if ($new) {
      // Set default data for a new Album (title and parent_id)
      $parentalbum = $this->getParent();
      $title = str_replace(array('-','_','+','~'), ' ', $this->name);
      if (!is_null($parentalbum)) {
        $this->set('parentid', $parentalbum->getAlbumId());
        $title = substr($title, strrpos($title, '/')+1);
      }
      $this->set('title', $title);
      $this->save();
    }
    $this->sort_key = ($this->data['sort_type'] == "Title") ? 'title' : ($this->data['sort_type'] == "Manual") ? 'sort_order' : 'filename';
  }
  
  // Folder on the filesystem
  function getFolder() { return $this->name; }
  
  // The id of this album in the db
  function getAlbumID() { return $this->id; }
  
  // The parent Album of this Album. NULL if this is a top-level album.
  function getParent() {
    $slashpos = strrpos($this->name, "/");
    if ($slashpos) {
      $parent = substr($this->name, 0, $slashpos);
      $parentalbum = new Album($this->gallery, $parent);
      if ($parentalbum->exists) {
        return $parentalbum;
      }
    }
    return NULL;   
  }
  
  // Title
  function getTitle() { return $this->get('title'); }
  function setTitle($title) { $this->set('title', $title); }
  
  // Description
  function getDesc() { return $this->get('desc'); }
  function setDesc($desc) { $this->set('desc', $desc); }
  
  // Date/Time
  function getDateTime() { return $this->get('date'); }
  function setDateTime($myts) { $this->set('date', $myts); }
  
  // Place
  function getPlace() { return $this->get('place'); }
  function setPlace($place) { $this->set('place', $place); }
  
  // Sort type
  function getSortType() { return $this->get('sort_type'); }
  function setSortType($sorttype) { $this->set('sort_type', $sorttype);  }
  
  // Sort type
  function getSortOrder() { return $this->get('sort_order'); }
  function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }
  function getSortKey() { return $this->sort_key; }

  // Show this album?
  function getShow() { return $this->get('show'); }
  function setShow($show) { $this->set('show', $show ? 1 : 0); }
  
  
  /**
   * Returns all the subdirectories in this album as Album objects (sub-albums).
   * @param page  Which page of subalbums to display.
   * @return an array of Album objects.
   */
  function getSubAlbums($page=0) {
    if (is_null($this->subalbums)) {
      $dirs = $this->loadFileNames(true);
      $subalbums = array();
      
      foreach ($dirs as $dir) {
        $dir = $this->name . '/' . $dir;
        $subalbums[] = $dir;
      }
      
      // Sort here?
      $this->subalbums = $subalbums;
    }
    return $this->subalbums;
  }
  
  
  /**
   * Get Images will return all of a slice of the images for this album. They will
   * also be sorted according to the sort type of this album, or by filename if none
   * has been set.
   *
   * @param  page  Which page of images should be returned.
   * @return An array of Image objects.
   */
  function getImages($page=0) {
    if (is_null($this->images)) {
      // Load, sort, and store the images in this Album.
      $images = $this->loadFileNames();
      $images = $this->sortImageArray($images);
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
  
  
  /**
   * sortImageArray will sort an array of Images based on the given key. The
   * key must be one of (filename, title, sort_order) at the moment.
   *
   * @param images The array of filenames to be sorted.
   * @return A new array of filenames sorted according to the set key.
   */
  function sortImageArray($images) {
    $key = $this->getSortKey();
    $result = query("SELECT filename, title, sort_order FROM " . prefix("images")
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
      }
      $i++;
    }

    // Place the images not yet in the database after those with sort columns.
    $images_not_in_db = array_diff($images, $images_in_db);
    foreach($images_not_in_db as $filename) {
      $images_to_keys[$filename] = $i;
      $i++;
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
    $albumdir = SERVERPATH . "/albums/{$this->name}/";
    $thumb = $this->get('thumb');
    if ($thumb != NULL && file_exists($albumdir.$thumb)) {
      return new Image($this, $thumb);
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
    }
    return NULL;
  }
  
  /**
   * Gets the thumbnail URL for the album thumbnail image as returned by $this->getAlbumThumbImage();
   */
  function getAlbumThumb() {
    $image = $this->getAlbumThumbImage();
    if (!is_null($image)) {
      return $image->getThumb();
    } else {
      return null;
    }
  }
  
  function setAlbumThumb($filename) { $this->set('thumb', $filename); }
  
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
  
  
  // Delete the entire album PERMANENTLY. Be careful! This is unrecoverable.
  function deleteAlbum() {
    foreach($this->getImages() as $filename) {
      // False here means don't clean up (cascade already took care of it)
      $image = new Image($this, $filename);
      $image->deleteImage(false);
    }
    query("DELETE FROM " . prefix('albums') . " WHERE `id` = " . $this->id);
    rmdir($this->localpath);
  }
  
  
  /**
   * For every image in the album, look for its file. Delete from the database
   * if the file does not exist. Same for each sub-directory/album.
   */
  function garbageCollect($deep=false) {
    if (is_null($this->images)) $this->getImages();
    $result = query("SELECT * FROM ".prefix('images')." WHERE `albumid` = ".$this->id);
    $dead = array();
    
    // Read in all of the files on disk
    $files = $this->loadFileNames();
    
    // Does the filename from the db row match any in the files on disk?
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['filename'], $files)) {
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
    
    // Get all sub-albums and make sure they exist.
    $result = query("SELECT * FROM ".prefix('albums')." WHERE `parentid` = ".$this->id);
    $dead = array();
    $subdirs = $this->loadFileNames(true);
    $subalbums = $this->getSubAlbums();
    
    for($i=0; $i < count($subdirs); $i++) {
      $subdirs[$i] = $this->getFolder() . '/' . $subdirs[$i];
    }
    
    // Does the dirname from the db row match any in the subdirs on disk?
    while($row = mysql_fetch_assoc($result)) {
      if (!in_array($row['folder'], $subdirs)) {
        $dead[] = $row['id'];
      }
    }
    if (count($dead) > 0) {
      $sql = "DELETE FROM ".prefix('albums')." WHERE `id` = " . array_pop($dead);
      foreach ($dead as $albumid) {
        $sql .= " OR `id` = $albumid";
      }
      query($sql);
    }
      
    if ($deep) {
      foreach($subalbums as $dir) {
        $subalbum = new Album($this->gallery, $dir);
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
   *
   * @return An array of file names.
   * @param  $dirs Whether or not to return directories ONLY with the file array. Default is false.
   * 
   * @author Todd Papaioannou (lucky@luckyspin.org)
   * @since  1.0.0
   */
  function loadFileNames($dirs=false) {
    
    // This is where we'll look for files
    $albumdir = SERVERPATH . "/albums/{$this->name}/";
    
    // Be defensive
    if (!is_dir($albumdir) || !is_readable($albumdir)) {
      die("The album cannot be found.");
    }

    $dir = opendir($albumdir);
    $files = array();
    
    // Walk through the list and add them to the array
    while ($file = readdir($dir)) {
      if (($dirs && is_dir($albumdir.$file) && substr($file, 0, 1) != '.')
          || (!$dirs && is_file($albumdir.$file) && is_valid_image($file))) {
        $files[] = $file;
      }
    }
    closedir($dir);
    
    return $files;
  }
  
}


?>
