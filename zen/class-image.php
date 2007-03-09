<?php
/* *****************************************************************************
 *******************************************************************************
 * Image Class *****************************************************************
 ******************************************************************************/

class Image extends PersistentObject {

  var $filename;      // true filename of the image.
  var $exists = true; // Does the image exist?
  var $webpath;       // The full URL path to the original image.
  var $localpath;     // The full SERVER path to the original image.
  var $album;         // An album object for the album containing this image.
  var $comments;      // Image comment array.
  var $commentcount;  // The number of comments on this image.
  var $index;         // The index of the current image in the album array.
  var $sortorder;     // The position that this image should be shown in the album
  var $filemtime;     // Last modified time of this image

  // Constructor
  function Image(&$album, $filename, $cache=true) {
    $filename = sanitize_path($filename);
    
    $this->album = &$album;
    $this->webpath = WEBPATH . "/albums/" . $album->name . "/" . $filename;
    $this->localpath = SERVERPATH . "/albums/" . $album->name . "/" . $filename;
    // Check if the file exists.
    if(!file_exists($this->localpath) || is_dir($this->localpath)) {
      $this->exists = false;
      return false;
    }
    $this->filename = $filename;
    $this->filemtime = filemtime($this->localpath);
    $this->comments = null;

    parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', $cache);
  }
  
  
  function setDefaults() {
    $title = substr($this->filename, 0, strrpos($this->filename, '.'));
    if (empty($title)) $title = $this->filename;
    $this->set('title', $title);
    return true;
  }
  
  
  function getFileName() {
    return $this->filename;
  }

    
  // Get the width and height of the original image-- uses lazy evaluation.
  // TODO: Update them if they change by looking at file modification time, which must be stored in the database.
  // FIXME: Temporarily getting dimensions each time they're requested.
  function updateDimensions() {
    //if ($this->exists && (is_null($this->get('width')) || is_null($this->get('height')))) {
      $size = getimagesize($this->localpath);
      $this->set('width', $size[0]);
      $this->set('height', $size[1]);
      //$this->save();
    //}
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
  function getSortOrder() { return $this->get('sort_order'); }
  function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }

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
               "http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/index.php?album=" . urlencode($this->album->name) . "&image=" . urlencode($this->filename) . "\n" .
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

  
  /**** Image Methods ****/
  
  // Returns a path to the original image in the original folder.
  function getFullImage() {
    return WEBPATH . "/albums/" . pathurlencode($this->album->name) . "/" . rawurlencode($this->filename);
  }

  function getSizedImage($size) {
    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, 
      getImageParameters(array($size)));
    if (file_exists(SERVERCACHE . $cachefilename) 
      && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . '/cache' . pathurlencode($cachefilename);
    } else {
      return rewrite_path(
        pathurlencode($this->album->name).'/image/'.$size.'/'.urlencode($this->filename),
        'zen/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename) . '&s=' . $size);
    }
  }
  
  // Get a custom sized version of this image based on the parameters.
  function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy) {
    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, 
      getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy)));
    if (file_exists(SERVERCACHE . $cachefilename)
      && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . '/cache' . pathurlencode($cachefilename);
    } else {
      return WEBPATH . '/zen/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename)
        . ($size ? "&s=$size" : "" ) . ($width ? "&w=$width" : "") . ($height ? "&h=$height" : "") 
        . ($cropw ? "&cw=$cropw" : "") . ($croph ? "&ch=$croph" : "")
        . ($cropx ? "&cx=$cropx" : "") . ($cropy ? "&cy=$cropy" : "") ;
    }
  }

  // Get a default-sized thumbnail of this image.
  function getThumb() {
    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, getImageParameters(array('thumb')));
    if (file_exists(SERVERCACHE . $cachefilename)
      && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . '/cache' . pathurlencode($cachefilename);
    } else {
      return rewrite_path(
        pathurlencode($this->album->name) . '/image/thumb/' . urlencode($this->filename),
        'zen/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename) . '&s=thumb');
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
?>
