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
  var $name;          // $filename with the extension stripped off.
  var $album;         // An album object for the album containing this image.
  var $comments;      // Image comment array.
  var $commentcount;  // The number of comments on this image.
  var $index;         // The index of the current image in the album array.
  var $sortorder;     // The position that this image should be shown in the album
  var $filemtime;     // Last modified time of this image

  // Zenvideo
  var $video;   //Is the "image" a video ?
  var $videoThumb = NULL; // Thumbnail of the video 
  
  // Constructor
  function Image(&$album, $filename) {
    // $album is an Album object; it should already be created.
    $this->album = &$album;
    if ($album->name == '') {
      $this->webpath = getAlbumFolder(WEBPATH) . $filename;
      $this->encwebpath = getAlbumFolder(WEBPATH) . rawurlencode($filename);
      $this->localpath = getAlbumFolder() . $filename;
    } else {
      $this->webpath = getAlbumFolder(WEBPATH) . $album->name . "/" . $filename;
      $this->encwebpath = getAlbumFolder(WEBPATH) . pathurlencode($album->name) . "/" . rawurlencode($filename);
      $this->localpath = getAlbumFolder() . $album->name . "/" . $filename;
    }
    // Check if the file exists.
    if (!file_exists($this->localpath) || is_dir($this->localpath)) {
      $this->exists = false;
      return false;
    }
    $this->filename = $filename;
    $this->filemtime = filemtime($this->localpath);
    $this->name = $filename;
    $this->comments = null;
  
    // Zenvideo: Check if the image is a video or not
    if (is_valid_video($filename)) {
      $this->video = true;
      $this->videoThumb = checkVideoThumb(getAlbumFolder() . $this->album->name, $filename);
    }  

    // This is where the magic happens...
    $new = parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id));  
    if ($new) {
      $metadata = getImageMetadata($this->localpath);
      if (isset($metadata['date'])) {
        $newDate = $metadata['date'];
      } else {
        $newDate = strftime('%Y/%m/%d %T', filectime($this->localpath));
      }
      $this->set('date', $newDate);
      if (is_null($this->album->getDateTime())) {
        $this->album->setDateTime($newDate);   //  not necessarily the right one, but will do. Can be changed in Admin
        $this->album->save();
      }
      
      if (isset($metadata['title'])) { 
        $title = $metadata['title']; 
      } else {
        $title = substr($this->name, 0, strrpos($this->name, '.'));
        if (empty($title)) $title = $this->name;
      }
      $this->set('title', $title);
      
      if (isset($metadata['desc'])) { 
        $this->set('desc', $metadata['desc']);
      }
      if (isset($metadata['tags'])) {
        $this->setTags($metadata['tags']); 
      }    
      if (isset($metadata['location'])) {
        $this->setLocation($metadata['location']); 
      }    
      if (isset($metadata['city'])) {
        $this->setCity($metadata['city']); 
      }    
      if (isset($metadata['state'])) {
        $this->setState($metadata['state']); 
      }    
      if (isset($metadata['country'])) {
        $this->setCountry($metadata['country']); 
      }    
      $this->set('mtime', filemtime($this->localpath));
      $this->save();
    }
  }
    
  function getFileName() {
    return $this->filename;
  }
  
  
  function fileChanged() {
    $storedmtime = $this->get('mtime');
    return (empty($storedmtime) || $this->filemtime > $storedmtime);
  }
  
  
  function getExifData() {
    global $_zp_exifvars;
    $exif = array();
    if (is_null($v = $this->get('EXIFValid')) || ($v = 1) || $this->fileChanged()) {
      $exifraw = read_exif_data_raw($this->localpath, false);
      if ($exifraw['ValidEXIFData']) {
        foreach($_zp_exifvars as $field => $exifvar) {
          $exif[$field] = $exifraw[$exifvar[0]][$exifvar[1]];
          $this->set($field, $exif[$field]);
        }
        $this->set('EXIFValid', 1);
      } else {
        $this->set('EXIFValid', 0);
      }
      $this->set('mtime', $this->filemtime);
      $this->save();
    } else {
      // Put together an array of EXIF data to return
      if ($this->get('EXIFValid') == 1) {
       foreach($_zp_exifvars as $field => $exifvar) {
          $exif[$field] = $this->get($field);
        }
      } else {
        return false;
      }
    }
    return $exif;
  }

    
  // Get the width and height of the original image-- uses lazy evaluation.
  // TODO: Update them if they change by looking at file modification time, which must be stored in the database.
  // FIXME: Temporarily getting dimensions each time they're requested. Should sync with EXIF extraction.
  function updateDimensions() {
    //if ($this->exists && (is_null($this->get('width')) || is_null($this->get('height')))) {
      $size = getimagesize($this->localpath);
      $this->set('width', $size[0]);
      $this->set('height', $size[1]);
      //$this->save();
    //}
  }
  
  //ZenVideo: function getWidth modified to return default width of videos
  function getWidth() {
    if (!$this->video) {
      $this->updateDimensions();
      return $this->get('width');
    } else {
      return "320";
    }
  }

  //ZenVideo: function getHeight modified to return default height of videos
  function getHeight() {
    if (!$this->video) {
      $this->updateDimensions();
      return $this->get('height');
    } else {
      return "240";
    }
  }
  
  //ZenVideo: Get informations about video type.
  function getVideo() {return $this->video; }
  function getVideoThumb() {return $this->videoThumb; }
    
  // Album (Object) and Album Name
  function getAlbum() {  return $this->album; }
  function getAlbumName() { return $this->album->name; }

  // Title
  function getTitle() { return $this->get('title'); }
  function setTitle($title) { $this->set('title', $title); }

  // Description
  function getDesc() { return $this->get('desc'); }
  function setDesc($desc) { $this->set('desc', $desc); }
  
   // Location, city, state, and country
  function getLocation() { return $this->get('location'); }
  function setLocation($location) { $this->set('location', $location); }
  function getCity() { return $this->get('city'); }
  function setCity($city) { $this->set('city', $city); }
  function getState() { return $this->get('state'); }
  function setState($state) { $this->set('state', $state); }
  function getCountry() { return $this->get('country'); }
  function setcountry($country) { $this->set('country', $country); }
  
   // Tags
  function getTags() { return $this->get('tags'); }
  function setTags($tags) { $this->set('tags', $tags); }
  
  // Date
  function getDateTime() { return $this->get('date'); }
  function setDateTime($datetime) { 
    if ($datetime == "") {
      $this->set('date', '0000-00-00 00:00:00');
    } else {
      $this->set('date', date('Y-m-d H:i:s', strtotime($datetime))); 
    }
  }
  
  // Sort order
  function getSortOrder() { return $this->get('sort_order'); }
  function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }

  // Show this image?
  function getShow() { return $this->get('show'); }
  function setShow($show) { $this->set('show', $show ? 1 : 0); }

  
  // Permanently delete this image (be careful!)
  function deleteImage($clean = true) {
    //echo $this->localpath;
    $result = unlink($this->localpath); 
    if ($clean && $result) { query("DELETE FROM ".prefix('images')." WHERE `id` = " . $this->id); } 
    return $result; 
  }
  
  
  // Are comments allowed?
  function getCommentsAllowed() { return $this->get('commentson'); }
  function setCommentsAllowed($commentson) { $this->set('commentson', $commentson ? 1 : 0); }


  function getComments($moderated=false) {
    $sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") . 
       " WHERE imageid='" . $this->id . "'";
    if (!$moderated) {
      $sql .= " AND inmoderation = 0";
    } 
    $sql .= " ORDER BY id";
    $comments = query_full_array($sql);
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
      return 0;
    }
    
    if (!empty($website) && substr($website, 0, 7) != "http://") {
      $website = "http://" . $website;
    }

    $goodMessage = 2;
    $gallery = new gallery();
    if (!(false === ($requirePath = getPlugin('spamfilters/'.$gallery->getOption('spam_filter').".php", false)))) {
      require_once($requirePath);
      $spamfilter = new SpamFilter();
      $goodMessage = $spamfilter->filterMessage($name, $email, $website, $comment, $this->getFullImage());
    }      
      
    if ($goodMessage) {
      if ($goodMessage == 1) {
        $moderate = 1;
      } else {
        $moderate = 0;
      }

      // Update the database entry with the new comment
      query("INSERT INTO " . prefix("comments") . " (imageid, name, email, website, comment, inmoderation, date) VALUES " .
            " ('" . $this->id .
            "', '" . escape($name) . 
            "', '" . escape($email) . 
            "', '" . escape($website) . 
            "', '" . escape($comment) . 
            "', '" . $moderate . 
            "', NOW())");
        
      if (!$moderate) {
        //  add to comments array and notify the admin user
   
        $newcomment = array();
        $newcomment['name'] = $name;
        $newcomment['email'] = $email;
        $newcomment['website'] = $website;
        $newcomment['comment'] = $comment;
        $newcomment['date'] = time();
        $this->comments[] = $newcomment; 
        
        if ($gallery->getOption('email_new_comments')) {
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
                     "http://" . $_SERVER['SERVER_NAME'] . WEBPATH . "/" . ZENFOLDER . "/admin.php?page=comments\n";
          zp_mail("[" . zp_conf('gallery_title') . "] Comment posted about: " . $this->getTitle(), $message);     
        }      
      }
    }
    return $goodMessage;
  }

  function getCommentCount() { 
    if (is_null($this->commentcount)) {
      if ($this->comments == null) {
        $count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE inmoderation=0 AND imageid = " . $this->id);
        $this->commentcount = array_shift($count);
      } else {
        $this->commentcount = count($this->comments);
      }
    }
    return $this->commentcount;
  }

  
  /**** Image Methods ****/
  
  function getImageLink() {
    return rewrite_path('/' . pathurlencode($this->album->name) . '/' . urlencode($this->filename) . im_suffix(),
      '/index.php?album=' . urlencode($this->album->name) . '&image=' . urlencode($this->filename));
  }
  
  // Returns a path to the original image in the original folder.
  function getFullImage() {
    return getAlbumFolder(WEBPATH) . pathurlencode($this->album->name) . "/" . rawurlencode($this->filename);
  }

  function getSizedImage($size) {
    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, getImageParameters(array($size)));
    if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
    } else {
      return rewrite_path(
        pathurlencode($this->album->name).'/image/'.$size.'/'.urlencode($this->filename),
        ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename) . '&s=' . $size);
    }
  }
  
  // Get a custom sized version of this image based on the parameters.
  function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy) {
    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, 
      getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy)));
    if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
    } else {
      return WEBPATH . '/' . ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename)
        . ($size ? "&s=$size" : "" ) . ($width ? "&w=$width" : "") . ($height ? "&h=$height" : "") 
        . ($cropw ? "&cw=$cropw" : "") . ($croph ? "&ch=$croph" : "")
        . ($cropx ? "&cx=$cropx" : "") . ($cropy ? "&cy=$cropy" : "") ;
    }
  }

  // Get a default-sized thumbnail of this image.
  // ZenVideo: [OLD] Return a thumb or default Thumb, if the file is a video.
  function getThumb() {
    if ($this->video) {      //The file is a video 
      if ($this->videoThumb == NULL) {
        return WEBPATH . "/" . ZENFOLDER . "/i.php?a=.&i=videoDefault.png&s=thumb";
      } else {
        return WEBPATH . "/" . ZENFOLDER . "/i.php?a=".urlencode($this->album->name)."&i=".urlencode($this->videoThumb)."&s=thumb&vwm=".zp_conf('perform_video_watermark');
      }
    }

    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, getImageParameters(array('thumb')));
    if (file_exists(SERVERCACHE . $cachefilename)
      && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
    } else {
      return rewrite_path(
        pathurlencode($this->album->name) . '/image/thumb/' . urlencode($this->filename),
        ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename) . '&s=thumb');
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
