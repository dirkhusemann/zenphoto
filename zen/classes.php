<?php


// classes.php - HEADERS STILL NOT SENT! Do not output text from this file.

// Load the authentication functions, UTF-8 Library, and kses.
require_once("auth_zp.php");
require_once("utf8.php");
require_once("kses.php");


/*******************************************************************************
 *******************************************************************************
 * Persistent Object Class *****************************************************
 *
 * Parent ABSTRACT class of all persistent objects. This class should not be
 * instantiated, only used for subclasses. This cannot be enforced, but please follow it!
 *
 * Documentation/Instructions:
 * A child class should run the follwing in its constructor:
 * $new = parent::PersistentObject('tablename', array('uniquestring'=>$value, 'uniqueid'=>$uniqueid));
 * where 'tablename' is the name of the database table to use for this object type, and
 * array('uniquestring'=>$value, ...) defines a unique set of columns (keys) and their current values
 * which uniquely identifies a single record in that database table for this object.
 * The return value of the constructor (stored in $new in the above example) will be === TRUE if
 * a new record was created, and === FALSE if an existing record was updated. This can then be
 * used to set() default values for new objects and save() them.
 *
 *******************************************************************************
 ******************************************************************************/

// ABSTRACT
class PersistentObject {

  var $data;
  var $updates;
  var $table;
  var $unique_set;
  var $id;
  
  function PersistentObject($tablename, $unique_set) {
    // Initialize the variables.
    // Load the data into the data array using $this->load()
    $this->data = array();
    $this->updates = array();
    $this->table = $tablename;
    $this->unique_set = $unique_set;

    return $this->load();
  }
  
  /**
   * Set a variable in this object. Does not persist to the database until 
   * save() is called. So, IMPORTANT: Call save() after set() to persist.
   */
  function set($var, $value) {
    $this->updates[$var] = $value;
  }
  
  /**
   * Get the value of a variable. If $current is false, return the value
   * as of the last save of this object.
   */
  function get($var, $current=true) {
    if ($current && isset($this->updates[$var])) {
      return $this->updates[$var];
    } else if (isset($this->data[$var])) {
      return $this->data[$var];
    } else {
      return null;
    }
  }
  
  /** 
   * Load the data array from the database, using the unique id set to get the unique record.
   * @return false if the record already exists, true if a new record was created.
   *   The return value can be used to insert default data for new objects.
   */
  function load() {
    // Get the database record for this object.
    $entry = query_single_row("SELECT * FROM " . prefix($this->table) .
      getWhereClause($this->unique_set) . " LIMIT 1;");
    if (!$entry) {
      $this->save();
      return true;
    } else {
      $this->data = $entry;
      $this->id = $entry['id'];
      return false;
    }
  }

  /** 
   * Save the updates made to this object since the last update. Returns
   * true if successful, false if not.
   */
  function save() {
    if ($this->id == null) {
      // Create a new object and set the id from the one returned.
      $insert_data = array_merge($this->unique_set, $this->updates);
      $sql = "INSERT INTO " . prefix($this->table) . " (";
      if (empty($insert_data)) { return true; }
      $i = 0;
      foreach(array_keys($insert_data) as $col) {
        if ($i > 0) $sql .= ", ";
        $sql .= "`$col`";
        $i++;
      }
      $sql .= ") VALUES (";
      $i = 0;
      foreach(array_values($insert_data) as $value) {
        if ($i > 0) $sql .= ", ";
        $sql .= "'" . mysql_escape_string($value) . "'";
        $i++;
      }
      $sql .= ");";
      $success = query($sql);
      if ($success == false || mysql_affected_rows() != 1) { return false; }
      $this->id = mysql_insert_id();
      $this->updates = array();

    } else {
      // Save the existing object (updates only) based on the existing id.
      if (empty($this->updates)) {
        return true;
      } else {
        $sql = "UPDATE " . prefix($this->table) . " SET";
        $i = 0;
        foreach ($this->updates as $col => $value) {
          if ($i > 0) $sql .= ",";
          $sql .= " `$col` = '". mysql_escape_string($value) . "'";
          $this->data[$col] = $value;
          $i++;
        }
        $sql .= " WHERE id=" . $this->id . ";";
        $success = query($sql);
        if ($success == false || mysql_affected_rows() != 1) { return false; }
        $this->updates = array();
      }
    }
    return true;
  }

}




/*******************************************************************************
 *******************************************************************************
 * Image Class *****************************************************************
 ******************************************************************************/

class Image extends PersistentObject {

  var $filename;      // true filename of the image.
  var $exists = true; // Does the image exist?
  var $webpath;       // The full URL path to the original image.
  var $localpath;     // The full SERVER path to the original image.
  var $name;          // $filename with the extension stripped off.
  var $album;         // An album object for the album containing this image.
  var $comments;      // Image comment array.
  var $commentcount;  // The number of comments on this image.
  var $index;         // The index of the current image in the album array.
  var $sortorder;     // The position that this image should be shown in the album

  // Constructor
  function Image($album, $filename) {
    // $album is an Album object; it should already be created.
    $this->album = $album;
    $this->webpath = WEBPATH . "/albums/" . $album->name . "/" . $filename;
    $this->encwebpath = WEBPATH . "/albums/" . pathurlencode($album->name) . "/" . rawurlencode($filename);
    $this->localpath = SERVERPATH . "/albums/" . $album->name . "/" . $filename;
    // Check if the file exists.
    if(!file_exists($this->localpath) || is_dir($this->localpath)) {
      $this->exists = false;
      return false;
    }
    $this->filename = $filename;
    $this->name = $filename;
    $this->comments = null;

    $new = parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id));
    if ($new) {
      $title = substr($this->name, 0, strrpos($this->name, '.'));
      if (empty($title)) $title = $this->name;
      $this->set('title', $title);
      $this->save();
    }
  }
  
  
  function getFileName() {
    return $this->filename;
  }

    
  // Get the width and height of the original image-- uses lazy evaluation.
  // TODO: Update them if they change by looking at file modification time.
  function updateDimensions() {
    if ($this->exists && (is_null($this->get('width')) || is_null($this->get('height')))) {
      $size = getimagesize($this->localpath);
      $this->set('width', $size[0]);
      $this->set('height', $size[1]);
      $this->save();
    }
  }
  
  function getWidth() {
    $this->updateDimensions();
    return $this->get('width');
  }
  
  function getHeight() {
    $this->updateDimensions();
    return $this->get('height');
  }
    
  // Album (Object) and Album Name
  function getAlbum() {  return $this->album; }
  function getAlbumName() { return $this->album->name; }

  // Title
  function getTitle() { return $this->get('title'); }
  function setTitle($title) { $this->set('title', $title); }

  // Description
  function getDesc() { return $this->get('desc'); }
  function setDesc($desc) { $this->set('desc', $desc); }
  
  // Sort order
  function getSortOrder() { return $this->get('sortorder'); }
  function setSortOrder($sortorder) { $this->set('sortorder', $sortorder); }

  // Show this image?
  function getShow() { return $this->get('show'); }
  function setShow($show) { $this->set('show', $show ? 1 : 0); }

  
  // Permanently delete this image (be careful!)
  function deleteImage($clean = true) {
    //echo $this->localpath;
    unlink($this->localpath);
    if ($clean) { query("DELETE FROM ".prefix('images')." WHERE `id` = " . $this->id); }
  }
  
  
  // Are comments allowed?
  function getCommentsAllowed() { return $this->get('commentson'); }
  function setCommentsAllowed($commentson) { $this->set('commentson', $commentson ? 1 : 0); }


  function getComments() {
    $comments = query_full_array("SELECT *, (date + 0) AS date FROM " . prefix("comments") . 
       " WHERE imageid='" . $this->id . "' ORDER BY id");
    $this->comments = $comments;
    return $this->comments;
  }
  
  // addComment: assumes data is coming straight from GET or POST
  function addComment($name, $email, $website, $comment) {
    $this->getComments();
    $name = trim($name);
    $email = trim($email);
    $website = trim($website);
    // Let the comment have trailing line breaks and space? Nah...
    // Also (in)validate HTML here, and in $name.
    $comment = trim($comment);
    if (empty($email) || !is_valid_email_zp($email) || empty($name) || empty($comment)) {
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
          " ('" . $this->id .
          "', '" . escape($name) . 
          "', '" . escape($email) . 
          "', '" . escape($website) . 
          "', '" . escape($comment) . 
          "', NOW())");
          
    // Notify the admin user
    $message = "A comment has been posted in your album " . $this->getAlbumName() . " about " . $this->getTitle() . "\n" .
               "\n" .
               "Author: " . $name . "\n" .
               "Email: " . $email . "\n" .
               "Website: " . $website . "\n" .
               "Comment:\n" . $comment . "\n" .
               "\n" .
               "You can view all comments about this image here:\n" .
               "http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/index.php?album=" . urlencode($this->album->name) . "&image=" . urlencode($this->name) . "\n" .
               "\n" .
               "You can edit the comment here:\n" .
               "http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/zen/admin.php?page=comments\n";
    zp_mail("[" . zp_conf('gallery_title') . "] Comment posted about: " . $this->getTitle(), $message);           
    
    return true;
  }

  function getCommentCount() { 
    if (is_null($this->commentcount)) {
      if ($this->comments == null) {
        $count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE imageid = " . $this->id);
        $this->commentcount = array_shift($count);
      } else {
        $this->commentcount = count($this->comments);
      }
    }
    return $this->commentcount;
  }

  // Returns a path to the original image in the original folder.
  function getFullImage() {
    return $this->encwebpath;
  }

  function getSizedImage($size) {
    if (zp_conf('mod_rewrite')) {
      return WEBPATH . "/".urlencode($this->album->name)."/image/".$size."/".urlencode($this->filename);
    } else {
      return WEBPATH . "/zen/i.php?a=" . urlencode($this->album->name) . "&i=" . urlencode($this->filename) . "&s=" . $size;
    }
  }
  
  // Get a custom sized version of this image based on the parameters.
  function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy) {
    return WEBPATH . "/zen/i.php?a=" . urlencode($this->album->name) . "&i=" . urlencode($this->filename)
    . ($size ? "&s=$size" : "" ) . ($width ? "&w=$width" : "") . ($height ? "&h=$height" : "") 
    . ($cropw ? "&cw=$cropw" : "") . ($croph ? "&ch=$croph" : "")
    . ($cropx ? "&cx=$cropx" : "") . ($cropy ? "&cy=$cropy" : "") ;
  }

  // Get a default-sized thumbnail of this image.
  function getThumb() {
    if (zp_conf('mod_rewrite')) {
      return WEBPATH . "/" . urlencode($this->album->name) . "/image/thumb/" . urlencode($this->filename);
    } else {
      return WEBPATH . "/zen/i.php?a=" . urlencode($this->album->name) . "&i=" . urlencode($this->filename) . "&s=thumb";
    }
  }
  
  // Get the index of this image in the album, taking sorting into account.
  function getIndex() {
    if ($this->index == NULL) {
      $images = $this->album->getImages(0);
      $i=0;
      for ($i=0; $i < count($images); $i++) {
        $image = $images[$i];
        if ($this->filename == $image) {
          $this->index = $i;
          break;
        }
      } 
    }

    return $this->index;
  }

  // TODO: To keep the Image in array abstraction these next two methods
  // should really return an Image.
  
  // Returns the next Image.
  function getNextImage() {
    $this->getIndex();
    $image = $this->album->getImage($this->index+1);
    
    if ($image != NULL) {
      return $image;
    } else {
      return NULL;
    }
  }
  
  // Return the previous Image
  function getPrevImage() {
    $this->getIndex();
    $image = $this->album->getImage($this->index-1);
    
    if ($image != NULL) {
      return $image;
    } else {
      return NULL;
    }
  }
  
  function getAlbumPage() {
    $this->getIndex();
    $images_per_page = zp_conf('images_per_page');
    return floor(($this->index / $images_per_page)+1);
  }

  // Tag methods?
 
}


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
    // First, instantiate the album object (->$this->$album). If the album doesn't exist yet, it'll be created.
    $this->name = $folder;
    $this->gallery = $gallery;
    $this->localpath = SERVERPATH . "/albums/" . $folder . "/";
    if(!file_exists($this->localpath)) {
      $this->exists = false;
      return false;
    }
    $new = parent::PersistentObject('albums', array('folder' => $this->name));
    if ($new) {
      // Set default data for a new Album
      $parentalbum = $this->getParent();
      $title = $this->name;
      if (!is_null($parentalbum)) { 
        $this->set('parentid', $parentalbum->getAlbumId());
        $title = substr($this->name, strrpos($this->name, '/')+1);
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
    //echo $this->localpath;
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
