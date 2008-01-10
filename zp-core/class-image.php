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
  

  /**
   * Constructor for class-image
   *
   * @param object &$album the owning album
   * @param sting $filename the filename of the image
   * @return Image
   */
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
        $newDate = strftime('%Y/%m/%d %T', filemtime($this->localpath));
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
      if (isset($metadata['credit'])) {
        $this->setCredit($metadata['credit']); 
      }    
      if (isset($metadata['copyright'])) {
        $this->setCopyright($metadata['copyright']); 
      }    	  	  
      $this->set('mtime', filemtime($this->localpath));
      $this->save();
    }
  }
    
  /**
   * Returns the image filename
   *
   * @return string
   */
  function getFileName() {
    return $this->filename;
  }
  
  /**
   * Returns true if the file has changed since last time we looked
   *
   * @return bool
   */
  function fileChanged() {
    $storedmtime = $this->get('mtime');
    return (empty($storedmtime) || $this->filemtime > $storedmtime);
  }
  
  /**
   * Returns an array of EXIF data
   *
   * @return array
   */
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

  /** 
   * Update this object's values for width and height. Uses lazy evaluation.
   * TODO: Update them if they change by looking at file modification time, which must be stored in the database.
   * FIXME: Temporarily getting dimensions each time they're requested. Should be same as EXIF extraction (see TODO).
   *
   */
  function updateDimensions() {
    if ($this->video) {
      $size = array('320','240');
    } else {
      $size = getimagesize($this->localpath);
    }
    $this->set('width', $size[0]);
    $this->set('height', $size[1]);
  }
  
  /**
   * Returns the width of the image
   *
   * @return int
   */
  function getWidth() {
    $this->updateDimensions();
    return $this->get('width');
  }

  /**
   * Returns the height of the image
   *
   * @return int
   */
  function getHeight() {
    $this->updateDimensions();
    return $this->get('height');
  }
  
  /**
   * Returns true if this image is a video
   *
   * @return bool
   */
  function getVideo() { return $this->video; }
  
  /**
   * Returns the thumbnail for this video
   *
   * @return object 
   */
  function getVideoThumb() { return $this->videoThumb; }
    
  /**
   * Returns the album that holds this image
   *
   * @return object
   */
  function getAlbum() { return $this->album; }
  
  /**
   * Retuns the folder name of the album that holds this image
   *
   * @return string
   */
  function getAlbumName() { return $this->album->name; }


  /**
   * Returns the title of this image
   *
   * @return string
   */
  function getTitle() { return $this->get('title'); }
  
  /**
   * Stores the title of this image
   *
   * @param string $title text for the title
   */
  function setTitle($title) { $this->set('title', $title); }


  /**
   * Returns the description of the image
   *
   * @return string
   */
  function getDesc() { return $this->get('desc'); }
  
  /**
   * Stores the description of the image
   *
   * @param string $desc text for the description
   */
  function setDesc($desc) { $this->set('desc', $desc); }
  
  /**
   * Returns the location field of the image
   *
   * @return string
   */
  function getLocation() { return $this->get('location'); }
  
  /**
   * Stores the location field of the image
   *
   * @param string $location text for the location
   */
  function setLocation($location) { $this->set('location', $location); }
  
  /**
   * Returns the city field of the image
   *
   * @return string
   */
  function getCity() { return $this->get('city'); }
  
  /**
   * Stores the city field of the image
   *
   * @param string $city text for the city
   */
  function setCity($city) { $this->set('city', $city); }
  
  /**
   * Returns the state field of the image
   *
   * @return string
   */
  function getState() { return $this->get('state'); }
  
  /**
   * Stores the state field of the image
   *
   * @param string $state text for the state
   */
  function setState($state) { $this->set('state', $state); }
  
  /**
   * Returns the country field of the image
   *
   * @return string
   */
  function getCountry() { return $this->get('country'); }
  
  /**
   * Stores the country field of the image
   *
   * @param string $country text for the country filed
   */
  function setcountry($country) { $this->set('country', $country); }

  /**
   * Returns the credit field of the image
   *
   * @return string
   */
  function getCredit() { return $this->get('credit'); }
  
  /**
   * Stores the credit field of the image
   *
   * @param string $credit text for the credit field
   */
  function setCredit($credit) { $this->set('credit', $credit); }
 
  /**
   * Returns the copyright field of the image
   *
   * @return string
   */
  function getCopyright() { return $this->get('copyright'); }
  
  /**
   * Stores the text for the copyright field of the image
   *
   * @param string $copyright text for the copyright field
   */
  function setCopyright($copyright) { $this->set('copyright', $copyright); }   
  
  /**
   * Returns the tags of the image
   *
   * @return string
   */
  function getTags() { return $this->get('tags'); }
  
  /**
   * Sets the tags of the image
   *
   * @param string $tags the tag string
   */
  function setTags($tags) { $this->set('tags', $tags); }
  
  /**
   * Returns the unformatted date of the image
   *
   * @return string
   */
  function getDateTime() { return $this->get('date'); }
  
  /**
   * Stores the date of the image
   *
   * @param string $datetime the date
   */
  function setDateTime($datetime) { 
    if ($datetime == "") {
      $this->set('date', '0000-00-00 00:00:00');
    } else {
      $this->set('date', date('Y-m-d H:i:s', strtotime($datetime))); 
    }
  }

  /**
   * Returns the sort order value of the image
   *
   * @return int
   */
  function getSortOrder() { return $this->get('sort_order'); }
  
  /**
   * Sets the sort order value of the image
   *
   * @param int $sortorder
   */
  function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }

  /**
   * Returns true if the image is set visible
   *
   * @return bool
   */
  function getShow() { return $this->get('show'); }
  
  /**
   * Sets the visibility of the image
   *
   * @param bool $show
   */
  function setShow($show) { $this->set('show', $show ? 1 : 0); }

  /** 
   * Permanently delete this image (permanent: be careful!) 
   * Returns the result of the unlink operation (whether the delete was successful)
   * @param bool $clean whether to remove the database entry.
   * @return bool 
   */
  function deleteImage($clean=true) {
    $result = unlink($this->localpath); 
    if ($clean && $result) { 
      query("DELETE FROM ".prefix('comments') . "WHERE `type`='images' AND `imageid`=" . $this->id);
      query("DELETE FROM ".prefix('images') . "WHERE `id` = " . $this->id); 
    } 
    return $result; 
  }
  
  /**
   * Moves an image to a new album and/or filename (rename).
   * Returns  true on success and false on failure.
   * @param Album $newalbum the album to move this file to. Must be a valid Album object.
   * @param string $newfilename the new file name of the image in the specified album.
   * @return bool
   */
  function moveImage($newalbum, $newfilename=null) {
    if ($newfilename == null) $newfilename = $this->filename;
    if ($newalbum->id == $this->album->id && $newfilename == $this->filename) {
      // Nothing to do - moving the file to the same place.
      return true;
    }
    $newpath = getAlbumFolder() . $newalbum->name . "/" . $newfilename;
    $result = rename($this->localpath, $newpath);
    if ($result) {
      $result = query("UPDATE ".prefix('images')." SET albumid='" . $newalbum->id 
        . "', filename='" . $newfilename . " WHERE id=" . $this->id . " LIMIT 1");
    }
    return $result;
  }
  
  /**
   * Renames an image to a new filename, keeping it in the same album. Convenience for moveImage($image->album, $newfilename).
   * Returns  true on success and false on failure.
   * @param string $newfilename the new file name of the image file.
   * @return bool
   */
  function renameImage($newfilename) {
    return $this->moveImage($this->album, $newfilename);
  }
  
  /**
   * Copies the image to a new album
   *
   * @param string $newalbum the destination album
   */
  function copyImage($newalbum) {
    
  }
  
  /**
   * Retuns true if comments are allowed on the image
   *
   * @return bool
   */
  function getCommentsAllowed() { return $this->get('commentson'); }
  
  /**
   * Sets the comments allowed flag on the image
   *
   * @param bool $commentson true if they are allowed
   */
  function setCommentsAllowed($commentson) { $this->set('commentson', $commentson ? 1 : 0); }

  /**
   * Returns an array of comments
   *
   * @param bool $moderated if false, comments in moderation are ignored
   * @return array
   */
  function getComments($moderated=false) {
    $sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") . 
       " WHERE `type`='images' AND `imageid`='" . $this->id . "'";
    if (!$moderated) {
      $sql .= " AND `inmoderation`=0";
    } 
    $sql .= " ORDER BY id";
    $comments = query_full_array($sql);
    $this->comments = $comments;
    return $this->comments;
  }
  
  /**
   * Adds a comment to the image
   * assumes data is coming straight from GET or POST
   *
   * Returns a code for the success of the comment add:
   *    0: Bad entry
   *    1: Marked for moderation
   *    2: Successfully posted
   *
   * @param string $name Comment author name
   * @param string $email Comment author email
   * @param string $website Comment author website
   * @param string $comment body of the comment
   * @param string $code Captcha code entered
   * @param string $code_ok Captcha md5 expected
   * @return int
   *    */
  function addComment($name, $email, $website, $comment, $code, $code_ok) {
    $goodMessage = postComment($name, $email, $website, $comment, $code, $code_ok, $this);
    return $goodMessage;
  }

  /**
   * Returns the count of comments for the image. Comments in moderation are not counted
   *
   * @return int
   */
  function getCommentCount() { 
    if (is_null($this->commentcount)) {
      if ($this->comments == null) {
        $count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='images' AND `inmoderation`=0 AND `imageid`=" . $this->id);
        $this->commentcount = array_shift($count);
      } else {
        $this->commentcount = count($this->comments);
      }
    }
    return $this->commentcount;
  }

  
  /**** Image Methods ****/
  
  /**
   * Returns a link to the image
   *
   * @return string
   */
  function getImageLink() {
    return rewrite_path('/' . pathurlencode($this->album->name) . '/' . urlencode($this->filename) . im_suffix(),
      '/index.php?album=' . urlencode($this->album->name) . '&image=' . urlencode($this->filename));
  }
  
  /**
   * Returns a path to the original image in the original folder.
   *
   * @return string
   */
  function getFullImage() {
    return getAlbumFolder(WEBPATH) . pathurlencode($this->album->name) . "/" . rawurlencode($this->filename);
  }

  /**
   * Returns a path to a sized version of the image
   *
   * @param int $size how big an image is wanted
   * @return string
   */
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
  
/**
 *  Get a custom sized version of this image based on the parameters.
 * 
 * @param string $alt Alt text for the url
 * @param int $size size
 * @param int $width width
 * @param int $height height 
 * @param int $cropw crop width
 * @param int $croph crop height
 * @param int $cropx crop x axis
 * @param int $cropy crop y axis
 * @param string $class Optional style class
 * @param string $id Optional style id
 * @param bool $thumbStandin set true to inhibit watermarking
 * @return string
 */
  function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin=false) {
    $cachefilename = getImageCacheFilename($this->album->name, $this->filename, 
      getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy)));
    if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
      return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
    } else {
      return WEBPATH . '/' . ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename)
        . ($size ? "&s=$size" : "" ) . ($width ? "&w=$width" : "") . ($height ? "&h=$height" : "") 
        . ($cropw ? "&cw=$cropw" : "") . ($croph ? "&ch=$croph" : "")
        . ($cropx ? "&cx=$cropx" : "") . ($cropy ? "&cy=$cropy" : "") 
        . ($thumbStandin ? "&t=true" : "") ;
    }
  }


  /**
   * Get a default-sized thumbnail of this image.
   * ZenVideo: [OLD] Return a thumb or default Thumb, if the file is a video.
   *
   * @return string
   */
  function getThumb() {
    if ($this->video) {      //The file is a video 
      if ($this->videoThumb == NULL) {
        return WEBPATH . "/" . ZENFOLDER . "/i.php?a=.&i=videoDefault.png&s=thumb";
      } else {
        return WEBPATH . "/" . ZENFOLDER . "/i.php?a=".urlencode($this->album->name)."&i=".urlencode($this->videoThumb)."&s=thumb&vwm=".getOption('perform_video_watermark');
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

/** 
 * Get the index of this image in the album, taking sorting into account.
 *
 * @return int
 */
  function getIndex() {
    global $_zp_current_search;
    if ($this->index == NULL) {
      if (in_context(ZP_SEARCH)) {
	    $images =  $_zp_current_search->getImages(0);
        $i=0;
        for ($i=0; $i < count($images); $i++) {
          $image = $images[$i];
          if ($this->filename == $image['filename']) {
            $this->index = $i;
            break;
          }
        } 
	  } else {
	    $images =  $this->album->getImages(0);
        $i=0;
        for ($i=0; $i < count($images); $i++) {
          $image = $images[$i];
          if ($this->filename == $image) {
            $this->index = $i;
            break;
          }
        } 
	  }
    }
    return $this->index;
  }

  /**
   * Returns the next Image.
   *
   * @return object
   */
  function getNextImage() {
    global $_zp_current_search;
    $index = $this->getIndex() + 1;
	if (in_context(ZP_SEARCH)) {
      $image = $_zp_current_search->getImage($this->index+1);
	} else {
      $image = $this->album->getImage($this->index+1);
    }
    if ($image != NULL) {
      return $image;
    } else {
      return NULL;
    }
  }
  
  /**
   * Return the previous Image
   *
   * @return object
   */
  function getPrevImage() {
    global $_zp_current_search;
    $this->getIndex();
	if (in_context(ZP_SEARCH)) {
      $image = $_zp_current_search->getImage($this->index-1);
	} else {
      $image = $this->album->getImage($this->index-1);
	}
    
    if ($image != NULL) {
      return $image;
    } else {
      return NULL;
    }
  }
  
  /**
   * Returns the page number of this image in the album
   *
   * @return int
   */
  function getAlbumPage() {
    $this->getIndex();
    $images_per_page = getOption('images_per_page');
    return floor(($this->index / $images_per_page)+1);
  }
  
  /**
   * returns the custom data field
   *
   * @return string
   */
  function getCustomData() { return $this->get('custom_data'); }
  
  /**
   * Sets the custom data field
   *
   * @param string $val the value to be put in custom_data
   */
  function setCustomData($val) { $this->set('custom_data', $val); }
  
}

?>
