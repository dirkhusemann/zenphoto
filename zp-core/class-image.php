<?php
/**
 *Image Class 
 * @package classes
 */

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
		if (!is_object($album)) return NULL;
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
			return NULL;
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
		$album_name = $album->name;
		$new = parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', false, empty($album_name));
		if ($new) {
			if ($this->video) {
				$size = array('320','240');
			} else {
				$size = getimagesize($this->localpath);
			}
			$this->set('width', $size[0]);
			$this->set('height', $size[1]);
			
			$metadata = getImageMetadata($this->localpath);
			if (isset($metadata['date'])) {
				$newDate = $metadata['date'];
			} else {
				$newDate = strftime('%Y/%m/%d %T', filemtime($this->localpath));
			}
			$this->set('date', $newDate);
			$alb = $this->album;
			if (!is_null($alb)) {
				if (is_null($alb->getDateTime()) || getOption('album_use_new_image_date')) {
					$this->album->setDateTime($newDate);   //  not necessarily the right one, but will do. Can be changed in Admin
					$this->album->save();
				}
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
		if (is_null($v = $this->get('EXIFValid')) || ($v != 1) || $this->fileChanged()) {
			$exifraw = read_exif_data_raw($this->localpath, false);
			if (isset($exifraw['ValidEXIFData'])) {
				foreach($_zp_exifvars as $field => $exifvar) {
					if (isset($exifraw[$exifvar[0]][$exifvar[1]])) {
						$exif[$field] = $exifraw[$exifvar[0]][$exifvar[1]];
						$this->set($field, $exif[$field]);
					}
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
	 *
	 */
	function updateDimensions() {
		if (!$this->fileChanged()) {
			if (!(($this->get('width') == 0) || ($this->get('height') == 0))) {
				return; // we already have the data
			}
		}

		if ($this->video) {
			$size = array('320','240');
		} else {
			$size = getimagesize($this->localpath);
		}
		$this->set('width', $size[0]);
		$this->set('height', $size[1]);
		$this->save();
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
	function getTitle() { 
		$t = $this->get('title'); 
		return get_language_string($t);
	}

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
	function getDesc() { 
		$t = $this->get('desc'); 
		return get_language_string($t);
	}

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
	function getLocation() { 
		$t = $this->get('location'); 
		return get_language_string($t);
	}

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
	function getCity() { 
		$t = $this->get('city'); 
		return get_language_string($t);
	}

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
	function getState() { 
		$t = $this->get('state'); 
		return get_language_string($t);
	}

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
	function getCountry() { 
		$t = $this->get('country'); 
		return get_language_string($t);
	}

	/**
	 * Stores the country field of the image
	 *
	 * @param string $country text for the country filed
	 */
	function setCountry($country) { $this->set('country', $country); }

	/**
	 * Returns the credit field of the image
	 *
	 * @return string
	 */
	function getCredit() { 
		$t = $this->get('credit'); 
		return get_language_string($t);
	}

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
	function getCopyright() { 
		$t = $this->get('copyright'); 
		return get_language_string($t);
	}

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
	function getTags() {
		if (useTagTable()) {
			$tags = readTags($this->id, 'images');
		} else {
			$tagstring = trim($this->get('tags'));
			if (empty($tagstring)) {
				$tags = array();
			} else {
				$tags = explode(",", $tagstring);
				natcasesort($tags);
			}
		}
		return $tags;
	}

	/**
	 * Sets the tags of the image
	 *
	 * @param string $tags the tag string
	 */
	function setTags($tags) { 	
		if (!is_array($tags)) {
			$tags = explode(',', $tags);
		}
		$tags = filterTags($tags);
		if (useTagTable()) {
			storeTags($tags, $this->id, 'images');
		} else {
			$tags = implode(",", $tags);
			$this->set('tags', $tags); 
		}
	}

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
	 * @param int $sortorder the order the images should appear in
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
		$result = @unlink($this->localpath);
		if ($clean && $result) {
			query("DELETE FROM ".prefix('comments') . "WHERE `type`='images' AND `ownerid`=" . $this->id);
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
		if (is_string($newalbum)) $newalbum = new Album($this->album->gallery, $newalbum, false);
		echo "Album moving to: exists? " . $newalbum->exists ." - ". $newalbum->name;
		if ($newfilename == null) $newfilename = $this->filename;
		if ($newalbum->id == $this->album->id && $newfilename == $this->filename) {
			// Nothing to do - moving the file to the same place.
			return true;
		}
		$newpath = getAlbumFolder() . $newalbum->name . "/" . $newfilename;
		$result = @rename($this->localpath, $newpath);
		if ($result) {
			$result = $this->move(array('filename'=>$newfilename, 'albumid'=>$newalbum->id));
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
	 * Copies the image to a new album, along with all metadata.
	 *
	 * @param string $newalbum the destination album
	 */
	function copyImage($newalbum) {
		if (is_string($newalbum)) $newalbum = new Album($this->album->gallery, $newalbum, false);
		if ($newalbum->id == $this->album->id) {
			// Nothing to do - moving the file to the same place.
			return true;
		}
		$newpath = getAlbumFolder() . $newalbum->name . "/" . $this->filename;
		$result = @copy($this->localpath, $newpath);
		if ($result) {
			$result = $this->copy(array('filename'=>$this->filename, 'albumid'=>$newalbum->id));
		}
		return $result;
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
	 * @param bool $private if false ignores private comments
	 * @return array
	 */
	function getComments($moderated=false, $private=false) {
		$sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") .
 			" WHERE `type`='images' AND `ownerid`='" . $this->id . "'";
		if (!$moderated) {
			$sql .= " AND `inmoderation`=0";
		}
		if (!$private) {
			$sql .= " AND `private`=0";
		}
		$sql .= " ORDER BY id DESC";
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
	 * @param string $ip the IP address of the comment poster
	 * @param bool $private set to true if the comment is for the admin only
	 * @param bool $anon set to true if the poster wishes to remain anonymous
	 * @return int
	 */
	function addComment($name, $email, $website, $comment, $code, $code_ok, $ip, $private, $anon) {
		$goodMessage = postComment($name, $email, $website, $comment, $code, $code_ok, $this, $ip, $private, $anon);
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
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='images' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $this->id);
				$this->commentcount = array_shift($count);
			} else {
				$this->commentcount = count($this->comments);
			}
		}
		return $this->commentcount;
	}


	/**** Image Methods ****/

	/**
	 * Returns an image page link for the image
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
				return WEBPATH . "/" . ZENFOLDER . "/i.php?a=.&i=multimediaDefault.png&s=thumb";
			} else {
				return WEBPATH . "/" . ZENFOLDER . "/i.php?a=".urlencode($this->album->name)."&i=".urlencode($this->videoThumb)."&s=thumb&vwm=".getOption('perform_video_watermark');
			}
		}

		$cachefilename = getImageCacheFilename($this->album->name, $this->filename, getImageParameters(array('thumb')));
		if (file_exists(SERVERCACHE . $cachefilename)
		&& filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
		} else {
			$alb = $this->album->name;
			$queryURL = ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($this->filename) . '&s=thumb';
			if (empty($alb)) {
				$alb = '/ ';
			}
			return rewrite_path(
			pathurlencode($alb) . '/image/thumb/' . urlencode($this->filename), $queryURL);
		}
	}

	/**
	 * Get the index of this image in the album, taking sorting into account.
	 *
	 * @return int
	 */
	function getIndex() {
		global $_zp_current_search, $_zp_current_album;
		if ($this->index == NULL) {
			$album = $this->getAlbum();
			if (!is_null($_zp_current_search) && !in_context(ZP_ALBUM_LINKED) || $album->isDynamic()) {
				if ($album->isDynamic()) {
					$images = $album->getImages();
					for ($i=0; $i < count($images); $i++) {
						$image = $images[$i];
						if ($this->filename == $image['filename']) {
							$this->index = $i;
							break;
						}
					}
				} else {
					$this->index = $_zp_current_search->getImageIndex($this->album->name, $this->filename);
				}
			} else {
				$images =  $this->album->getImages(0);
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
		$index = $this->getIndex();
		if (!is_null($_zp_current_search) && !in_context(ZP_ALBUM_LINKED)) {
			$image = $_zp_current_search->getImage($index+1);
		} else {
			$image = $this->album->getImage($index+1);
		}
		return $image;
	}

	/**
	 * Return the previous Image
	 *
	 * @return object
	 */
	function getPrevImage() {
		global $_zp_current_search;
		$index = $this->getIndex();
		if (!is_null($_zp_current_search) && !in_context(ZP_ALBUM_LINKED)) {
			$image = $_zp_current_search->getImage($index-1);
		} else {
			$image = $this->album->getImage($index-1);
		}
		return $image;
	}

	/**
	 * returns the custom data field
	 *
	 * @return string
	 */
	function getCustomData() { 
		$t = $this->get('custom_data'); 
		return get_language_string($t);
	}

	/**
	 * Sets the custom data field
	 *
	 * @param string $val the value to be put in custom_data
	 */
	function setCustomData($val) { $this->set('custom_data', $val); }

}

?>
