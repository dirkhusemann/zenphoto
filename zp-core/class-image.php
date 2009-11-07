<?php
/**
 *Image Class
 * @package classes
 */

// force UTF-8 Ø

$_zp_extra_filetypes = array(); // contains file extensions and the handler class

/**
 * Returns a new "image" object based on the file extension
 *
 * @param object $album the owner album
 * @param string $filename the filename
 * @return object
 */
function newImage(&$album, $filename) {
	global $_zp_extra_filetypes;
	if (!is_object($album) || strtoLower(get_class($album)) != 'album' || !$album->exists) {
		debugLogBacktrace("Bad album object parameter to newImage($filename)");			
		return NULL;
	}
	if ($ext = is_valid_other_type($filename)) {
		$object = $_zp_extra_filetypes[$ext];
		return New $object($album, $filename);
	} else {
		if (is_valid_image($filename)) {
			return New _Image($album, $filename);
		}
	}
	debugLogBacktrace("Bad filename suffix in newImage($filename)");			
	return NULL;
}

/**
 * Returns true if the object is a zenphoto 'image'
 *
 * @param object $image
 * @return bool
 */
function isImageClass($image=NULL) {
	global $_zp_extra_filetypes;
	if (is_null($image)) {
		if (!in_context(ZP_IMAGE)) return false;
		global $_zp_current_image;
		$image = $_zp_current_image;
	}
	$reporting = error_reporting(0); // supress deprecated message PHP 5.0.0 to 5.3.0
	$rslt = is_a($image, _Image);
	error_reporting($reporting);
	return $rslt;
}

/**
 * handles 'picture' images
 */
class _Image extends PersistentObject {

	var $filename;      			// true filename of the image.
	var $exists = true; 			// Does the image exist?
	var $webpath;       			// The full URL path to the original image.
	var $localpath;     			// Latin1 full SERVER path to the original image.
	var $displayname;   			// $filename with the extension stripped off.
	var $album;         			// An album object for the album containing this image.
	var $comments;      			// Image comment array.
	var $commentcount;  			// The number of comments on this image.
	var $index;         			// The index of the current image in the album array.
	var $sortorder;     			// The position that this image should be shown in the album
	var $filemtime;     			// Last modified time of this image
	
	
	// Plugin handler support
	var $objectsThumb = NULL; // Thumbnail image for the object
	

	/**
	 * Constructor for class-image
	 * 
	 * Do not call this constructor directly unless you really know what you are doing!
	 * Use instead the function newImage() which will instantiate an object of the 
	 * correct class for the file type.
	 *
	 * @param object &$album the owning album
	 * @param sting $filename the filename of the image
	 * @return Image
	 */
	function _Image(&$album, $filename) {
		// $album is an Album object; it should already be created.
		if (!is_object($album)) return NULL;
		if (!$this->classSetup($album, $filename)) { // spoof attempt
			$this->exists = false;
			return;
		}
		// Check if the file exists.
		if (!file_exists($this->localpath) || is_dir($this->localpath)) {
			$this->exists = false;
			return;
		}

		// This is where the magic happens...
		$album_name = $album->name;
		$new = parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', false, empty($album_name));
		$mtime = filemtime($this->localpath);
		if ($new || ($mtime != $this->get('mtime'))) {
			$this->set('mtime', $mtime);
			$this->updateDimensions();		// deal with rotation issues
			$this->updateMetaData();			// extract info from image
			$alb = $this->album;
			if (!is_null($alb)) {
				if (is_null($alb->getDateTime()) || getOption('album_use_new_image_date')) {
					$this->album->setDateTime($this->get('date'));   //  not necessarily the right one, but will do. Can be changed in Admin
					$this->album->save();
				}
			}
			$this->save();
			if ($new) zp_apply_filter('new_image', $this);
		}
	}
	
	/**
	 * generic "image" class setup code
	 * Returns true if valid image.
	 *
	 * @param object $album the images' album
	 * @param string $filename of the image
	 * @return bool
	 * 
	 */
	function classSetup(&$album, $filename) {
		$fileFS = internalToFilesystem($filename);
		if ($filename != filesystemToInternal($fileFS)) { // image name spoof attempt
			return false;
		}
		$this->album = &$album;
		if ($album->name == '') {
			$this->webpath = getAlbumFolder(WEBPATH) . $filename;
			$this->encwebpath = getAlbumFolder(WEBPATH) . rawurlencode($filename);
			$this->localpath = getAlbumFolder() . internalToFilesystem($filename);
		} else {
			$this->webpath = getAlbumFolder(WEBPATH) . $album->name . "/" . $filename;
			$this->encwebpath = getAlbumFolder(WEBPATH) . pathurlencode($album->name) . "/" . rawurlencode($filename);
			$this->localpath = $album->localpath . $fileFS;
		}
		$this->filename = $filename;		
		$this->displayname = substr($this->filename, 0, strrpos($this->filename, '.'));
		if (empty($this->displayname)) $this->displayname = $this->filename;
		$this->comments = null;
		$this->filemtime = @filemtime($this->localpath);
		$this->imagetype = strtolower(get_class($this)).'s';
		return true;
	}
	
	function getDefaultTitle() {
		return $this->displayname;
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
	function getMetaData() {
		require_once(dirname(__FILE__).'/exif/exif.php');
		global $_zp_exifvars;
		$exif = array();
		// Put together an array of EXIF data to return
		foreach($_zp_exifvars as $field => $exifvar) {
			$exif[$field] = $this->get($field);
		}
		return $exif;
	}

	/**
	 * Parses Exif/IPTC data
	 * 
	 */
	function updateMetaData() {
		require_once(dirname(__FILE__).'/exif/exif.php');
		global $_zp_exifvars;
		$IPTCtags = array(
											'SKIP'								=>	'2#000',	//	Record Version										Size:64		
											'ObjectType'					=>	'2#003',	//	Object Type	Ref										Size:67	
											'ObjectAttr'					=>	'2#004',	//	Object Attribute Ref							Size:67	
											'ObjectName'					=>	'2#005',	//	Object name												Size:64		
											'EditStatus'					=>	'2#007',	//	Edit Status												Size:64		
											'EditorialUpdate'			=>	'2#008',	//	Editorial Update									Size:2		
											'Urgency'							=>	'2#010',	//	Urgency														Size:1			
											'SubRef'							=>	'2#012',	//	Subject	Reference									Size:236		
											'Category'						=>	'2#015',	//	Category 													Size:3			
											'SuppCategory'				=>	'2#020',	//	Supplemental category							Size:32		
											'FixtureID'						=>	'2#022',	//	Fixture	ID 												Size:32		
											'Keywords'						=>	'2#025',	//	Keywords 													Size:64			
											'ContentLocCode'			=>	'2#026',	//	Content	Location Code							Size:3
											'ContentLocName'			=>	'2#027',	//	Content	Location Name							Size:64
											'ReleaseDate'					=>	'2#030',	//	Release	Date 											Size:8		
											'ReleaseTime'					=>	'2#035',	//	Release	Time											Size:11		
											'ExpireDate'					=>	'2#037',	//	Expiration Date										Size:8	
											'ExpireTime'					=>	'2#038',	//	Expiration Time										Size:11	
											'SpecialInstru'				=>	'2#040',	//	Special Instructions							Size:256	
											'ActionAdvised'				=>	'2#042',	//	Action Advised										Size:2	
											'RefService'					=>	'2#045',	//	Reference Service									Size:10	
											'RefDate'							=>	'2#047',	//	Reference Date										Size:8	
											'RefNumber'						=>	'2#050',	//	Reference Number									Size:8	
											'DateCreated'					=>	'2#055',	//	Date created											Size:8	
											'TimeCreated'					=>	'2#060',	//	Time created											Size:11	
											'DigitizeDate'				=>	'2#062',	//	Digital Creation Date							Size:8
											'DigitizeTime'				=>	'2#063',	//	Digital Creation Time							Size:11
											'OriginatingProgram'	=>	'2#065',	//	Originating Program								Size:32
											'ProgramVersion'			=>	'2#070',	//	Program version										Size:10
											'ObjectCycle'					=>	'2#075',	//	Object Cycle											Size:1	
											'ByLine'							=>	'2#080',	//	ByLine 														Size:32		
											'ByLineTitle'					=>	'2#085',	//	ByLine Title											Size:32	
											'City'								=>	'2#090',	//	City															Size:32			
											'SubLocation'					=>	'2#092',	//	Sublocation												Size:32			
											'State'								=>	'2#095',	//	Province/State										Size:32			
											'LocationCode'				=>	'2#100',	//	Country/Primary	Location Code			Size:3
											'LocationName'				=>	'2#101',	//	Country/Primary	Location Name			Size:64
											'TransmissionRef'			=>	'2#103',	//	Original Transmission Reference		Size:32
											'ImageHeadline'				=>	'2#105',	//	Image headline										Size:256		
											'ImageCredit'					=>	'2#110',	//	Image credit											Size:32		
											'Source'							=>	'2#115',	//	Source														Size:32			
											'Copyright'						=>	'2#116',	//	Copyright Notice									Size:128		
											'Contact'							=>	'2#118',	//	Contact														Size:128			
											'ImageCaption'				=>	'2#120',	//	Image caption											Size:2000		
											'ImageCaptionWriter'	=>	'2#122',	//	Image caption writer							Size:32	
											'ImageType'						=>	'2#130',	//	Image type												Size:2		
											'Orientation'					=>	'2#131',	//	Image	 rientation									Size:1		
											'LangID'							=>	'2#135',	//	Language ID												Size:3		
											'Subfile'							=>	'8#010'		//	Subfile														Size:2
										);
		$this->set('hasMetadata',0);
		$result = array();
		if (get_class($this)=='_Image') {
			$localpath = $this->localpath;
		} else {
			$localpath = $this->getThumbImageFile();
		}
		$exifraw = read_exif_data_protected($localpath);
		if (isset($exifraw['ValidEXIFData'])) {
			$this->set('hasMetadata',1);
			foreach($_zp_exifvars as $field => $exifvar) {
				if (isset($exifraw[$exifvar[0]][$exifvar[1]])) {
					$exif = trim(sanitize($exifraw[$exifvar[0]][$exifvar[1]],3));
					$this->set($field, $exif);
				} else if (isset($exifraw[$exifvar[0]]['MakerNote'][$exifvar[1]])) {
					$exif = trim(sanitize($exifraw[$exifvar[0]]['MakerNote'][$exifvar[1]],3));
					$this->set($field, $exif);
				}
			}
		}
			/* check IPTC data */
			$iptcdata = zp_imageIPTC($localpath);
			if (!empty($iptcdata)) {
				$iptc = iptcparse($iptcdata);
				if ($iptc) {
					$this->set('hasMetadata',1);
					$characterset = $this->getIPTCTag('1#090', $iptc);
					if (!$characterset) {
						$characterset = getOption('IPTC_encoding');
					} else if (substr($characterset, 0, 1) == chr(27)) { // IPTC escape encoding
						$characterset = substr($characterset, 1);
						if ($characterset == '%G') {
							$characterset = 'UTF-8';
						} else { // we don't know, need to understand the IPTC standard here. In the mean time, default it.
							$characterset = getOption('IPTC_encoding');
						}
					} else if ($characterset == 'UTF8') {
						$characterset = 'UTF-8';
					}
					// Extract IPTC fields of interest
					foreach ($_zp_exifvars as $field=>$exifvar) {
						if ($exifvar[0]=='IPTC') {
							$datum = $this->getIPTCTag($IPTCtags[$exifvar[1]], $iptc);
							$this->set($field, $this->prepIPTCString($datum, $characterset));
						}
					}
					
					/* iptc keywords (tags) */
					$datum = $this->getIPTCTagArray('2#025', $iptc);
					if (is_array($datum)) {
						$tags = array();
						$result['tags'] = array();
						foreach ($datum as $item) {
							$tags[] = $this->prepIPTCString($item, $characterset);;
						}
						$this->setTags($tags);
					}
				}
				/* "import" metadata into Zenphoto fields as makes sense */
				
				/* iptc date */
				$date = $this->get('IPTCDateCreated');
				if (!empty($date)) {
					$date = substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
				}
				/* EXIF date */
				if (empty($date)) {
					$date = $this->get('EXIFDateTime');
				}
				if (empty($date)) {
					$date = $this->get('EXIFDateTimeOriginal');
				}
				if (empty($date)) {
					$date = $this->get('EXIFDateTimeDigitized');
				}
				if (empty($date)) {
					$this->set('date', strftime('%Y-%m-%d %T', $this->get('mtime')));
				} else {
					$this->setDateTime($date);
				}

				/* iptc title */
				$title = $this->get('IPTCObjectName');
				if (empty($title)) {
					$title = $this->get('IPTCImageHeadline');
				}
				//EXIF title [sic]
				if (empty($title)) {
					$title = $this->get('EXIFImageDescription');
				}
				if (empty($title)) {
					$this->set('title',$this->getDefaultTitle());
				} else {
					$this->setTitle($title);
				}

				/* iptc description */
				$this->setDesc($this->get('IPTCImageCaption'));

				/* iptc location, state, country */
				$this->setLocation($this->get('IPTCSubLocation'));
				$this->setCity($this->get('IPTCCity'));
				$this->setState($this->get('IPTCState'));
				$this->setCountry($this->get('IPTCLocationName'));

				/* iptc credit */
				$credit = $this->get('IPTCByLine');
				if (empty($credit)) {
					$credit = $this->get('IPTCImageCredit');
				}
				if (empty($credit)) {
					$credit = $this->get('IPTCSource');
				}
				$this->setCredit($credit);

				/* iptc copyright */
				$this->setCopyright($this->get('IPTCCopyright'));

			}

	}

	/**
	 * For internal use--fetches a single tag from IPTC data
	 *
	 * @param string $tag the metadata tag sought
	 * @return string
	 */
	function getIPTCTag($tag, $iptc) {
		if (isset($iptc[$tag])) {
			$iptcTag = $iptc[$tag];
			$r = "";
			$ct = count($iptcTag);
			for ($i=0; $i<$ct; $i++) {
				$w = $iptcTag[$i];
				if (!empty($r)) { $r .= ", "; }
				$r .= $w;
			}
			return trim($r);
		}
		return '';
	}
	
	/**
	 * For internal use--fetches the IPTC array for a single tag.
	 *
	 * @param string $tag the metadata tag sought
	 * @return array
	 */
	function getIPTCTagArray($tag, $iptc) {
		if (array_key_exists($tag, $iptc)) {
			return $iptc[$tag];
		}
		return NULL;
	}
	
	/**
	 * Returns the IPTC data converted into UTF8
	 *
	 * @param string $iptcstring the IPTC data
	 * @param string $characterset the internal encoding of the data
	 * @return string
	 */
	function prepIPTCString($iptcstring, $characterset) {
		global $_zp_UTF8;
		// Remove null byte at the end of the string if it exists.
		if (substr($iptcstring, -1) === 0x0) {
			$iptcstring = substr($iptcstring, 0, -1);
		}
		$outputset = getOption('charset');
		if ($characterset == $outputset) return $iptcstring;
		$iptcstring = $_zp_UTF8->convert($iptcstring, $characterset, $outputset);
		return trim(sanitize($iptcstring));
	}
	/**
	 * Update this object's values for width and height.
	 *
	 */
	function updateDimensions() {
		$discard = NULL;
		$size = zp_imageDims($this->localpath, $discard);
		$width = $size['width'];
		$height = $size['height'];
		if (zp_imageCanRotate() && getOption('auto_rotate'))  {
			// Swap the width and height values if the image should be rotated			
			$splits = preg_split('/!([(0-9)])/', $this->get('EXIFOrientation'));
			$rotation = $splits[0];
			switch ($rotation) {
				case 5:
				case 6:
				case 7:
				case 8:
					$width = $size['height'];
					$height =$size['width'];
					break;
			}
		}
		$this->set('width', $width);
		$this->set('height', $height);
	}

	/**
	 * Returns the width of the image
	 *
	 * @return int
	 */
	function getWidth() {
		$w = $this->get('width');
		if (empty($w)) {
			$this->updateDimensions();
			$this->save();
			$w = $this->get('width');
		}
		return $w;
	}

	/**
	 * Returns the height of the image
	 *
	 * @return int
	 */
	function getHeight() {
		$h = $this->get('height');
		if (empty($h)) {
			$this->updateDimensions();
			$this->save();
			$h = $this->get('height');
		}
		return $h;
	}

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
		return get_language_string($this->get('title'));
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
		return get_language_string($this->get('desc'));
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
		return get_language_string($this->get('location'));
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
		return get_language_string($this->get('city'));
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
		return get_language_string($this->get('state'));
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
		return get_language_string($this->get('country'));
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
		return get_language_string($this->get('credit'));
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
		return get_language_string($this->get('copyright'));
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
		return readTags($this->id, 'images');
	}

	/**
	 * Sets the tags of the image
	 *
	 * @param string $tags the tag list
	 */
	function setTags($tags) {
		if (!is_array($tags)) {
			$tags = explode(',', $tags);
		}
		storeTags(filterTags($tags), $this->id, 'images');
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
			$newtime = dateTimeConvert($datetime);
			if ($newtime === false) return;
			$this->set('date', $newtime);
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
		$result = true;
		$filestodelete = safe_glob(substr($this->localpath,0,strrpos($this->localpath,'.')).'.*');
		foreach ($filestodelete as $file) {
			$result = $result && @unlink($file);
		}
		if ($clean && $result) {
			query("DELETE FROM ".prefix('comments') . "WHERE `type` IN (".zp_image_types('"').") AND `ownerid`=" . $this->id);
			query("DELETE FROM ".prefix('images') . "WHERE `id` = " . $this->id);
		}
		return $result;
	}

	/**
	 * Moves an image to a new album and/or filename (rename).
	 * Returns  0 on success and error indicator on failure.
	 * @param Album $newalbum the album to move this file to. Must be a valid Album object.
	 * @param string $newfilename the new file name of the image in the specified album.
	 * @return int
	 */
	function moveImage($newalbum, $newfilename=null) {
		if (is_string($newalbum)) $newalbum = new Album($this->album->gallery, $newalbum, false);
		if ($newfilename == null) $newfilename = $this->filename;
		if ($newalbum->id == $this->album->id && $newfilename == $this->filename) {
			// Nothing to do - moving the file to the same place.
			return 2;
		}
		$newpath = $newalbum->localpath . internalToFilesystem($newfilename);
		if (file_exists($newpath)) {
			// If the file exists, don't overwrite it.
			return 2;
		}
		$result = true;
		$newfilename = substr(internalToFilesystem($newfilename),0,strrpos($newfilename,'.'));
		$filestomove = safe_glob(substr($this->localpath,0,strrpos($this->localpath,'.')).'.*');
		foreach ($filestomove as $file) {
			$result = $result && @rename($file, $newalbum->localpath . $newfilename.strrchr($file,'.'));
		}
		if ($result) {
			$result = $this->move(array('filename'=>$newfilename, 'albumid'=>$newalbum->id));
		}
		if ($result) return 0;
		return 1;
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
			return 2;
		}
		$newpath = $newalbum->localpath . internalToFilesystem($this->filename);
		if (file_exists($newpath)) {
			// If the file exists, don't overwrite it.
			return 2;
		}
		$result = true;
		$filestocopy = safe_glob(substr($this->localpath,0,strrpos($this->localpath,'.')).'.*');
		foreach ($filestocopy as $file) {
			$result = $result && @copy($file, $newalbum->localpath . basename($file));
		}
		if ($result) {
			$result = $this->copy(array('filename'=>$this->filename, 'albumid'=>$newalbum->id));
		}
		if ($result) return 0;
		return 1;
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
	 * @param bool $desc set to true for descending order
	 * @return array
	 */
	function getComments($moderated=false, $private=false, $desc=false) {
		$sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") .
 			" WHERE `type` IN (".zp_image_types("'").") AND `ownerid`='" . $this->id . "'";
		if (!$moderated) {
			$sql .= " AND `inmoderation`=0";
		}
		if (!$private) {
			$sql .= " AND `private`=0";
		}
		$sql .= " ORDER BY id";
		if ($desc) {
			$sql .= ' DESC';
		}
		$comments = query_full_array($sql);
		$this->comments = $comments;
		return $this->comments;
	}

	/**
	 * Adds a comment to the image
	 * assumes data is coming straight from GET or POST
	 *
	 * Returns a comment object
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
	 * @return object
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
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type` IN (".zp_image_types("'").") AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $this->id);
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
		$args = getImageParameters(array($size), $this->album->name);
		$cachefilename = getImageCacheFilename($this->album->name, $this->filename, $args);
		if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . '/'.CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
		} else {
			return getImageProcessorURI($args,$this->album->name,$this->filename);
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
	 * @param bool $gray set true to force grayscale
	 * @return string
	 */
	function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin=false, $gray=false) {
		if ($thumbStandin) {
			$wmt = getOption('Image_watermark');
		} else {
			$wmt = NULL;
		}
		$args = getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy, NULL, NULL, NULL, $thumbStandin, $wmt, NULL, NULL), $this->album->name);
		$cachefilename = getImageCacheFilename($this->album->name, $this->filename,	$args);
		if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . '/'.CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
		} else {
			return getImageProcessorURI($args, $this->album->name, $this->filename);
		}
	}

	/**
	 * Returns the image file name for the thumbnail image.
	 * 
	 * @param string $path override path 
	 *
	 * @return s
	 */
	function getThumbImageFile($path=NULL) {
		$local = $this->localpath;
		if (!is_null($path)) {
			$local = $path.str_replace(SERVERPATH,$local);
		}
		return $local;
	}

	/**
	 * Get a default-sized thumbnail of this image.
	 *
	 * @return string
	 */
	function getThumb($type='image') {
		if (getOption('thumb_crop') && !is_null($cy = $this->get('thumbY'))) {
			$ts = getOption('thumb_size');
			$sw = getOption('thumb_crop_width');
			$sh = getOption('thumb_crop_height');
			$cx = $this->get('thumbX');
			$cw = $this->get('thumbW');
			$ch = $this->get('thumbH');
			// upscale to thumb_size proportions
			if ($sw == $sh) { // square crop, set the size/width to thumbsize
				$sw = $sh = $ts;
			} else {
				if ($sw > $sh) {
					$r = $ts/$sw;
					$sw = $ts;
					$sh = $sh * $r;
				} else {
					$r = $ts/$sh;
					$sh = $ts;
					$sh = $r * $sh;
				}
			}
			return $this->getCustomImage(NULL, $sw, $sh, $cw, $ch, $cx, $cy, true);
		}
		$filename = $this->filename;
		$wmt = getOption('Image_watermark');
		$args = getImageParameters(array('thumb', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $wmt, NULL, NULL), $this->album->name);
		$cachefilename = getImageCacheFilename($alb = $this->album->name, $filename, $args);
		if (file_exists(SERVERCACHE . $cachefilename)	&& filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . '/'.CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
		} else {
			return getImageProcessorURI($args, $this->album->name, $this->filename); 
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
				$images = $this->album->getImages(0);
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
		return get_language_string($this->get('custom_data'));
	}

	/**
	 * Sets the custom data field
	 *
	 * @param string $val the value to be put in custom_data
	 */
	function setCustomData($val) { $this->set('custom_data', $val); }
	
	/**
	 * Returns the disk size of the image
	 *
	 * @return string
	 */
	function getImageFootprint() {
		return filesize($this->localpath); 
	}

}

?>
