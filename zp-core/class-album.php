<?php
/**
 * Album Class
 * @package classes
 */

// force UTF-8 Ø

class Album extends PersistentObject {

	var $name;             // Folder name of the album (full path from the albums folder)
	var $localpath;				 // Latin1 full server path to the album
	var $exists = true;    // Does the folder exist?
	var $images = null;    // Full images array storage.
	var $subalbums = null; // Full album array storage.
	var $parent = null;    // The parent album name
	var $parentalbum = null; // The parent album's album object (lazy)
	var $gallery;
	var $searchengine;           // cache the search engine for dynamic albums
	var $comments;      // Image comment array.
	var $commentcount;  // The number of comments on this image.
	var $index;
	var $themeoverride;
	var $lastimagesort = NULL;  // remember the order for the last album/image sorts
	var $lastsubalbumsort = NULL;
	var $albumthumbnail = NULL; // remember the album thumb for the duration of the script
	var $sidecars = array();	// keeps the list of suffixes associated with this album

	/**
	 * Constructor for albums
	 *
	 * @param object &$gallery The parent gallery
	 * @param string $folder8 folder name (UTF8) of the album
	 * @param bool $cache load from cache if present
	 * @return Album
	 */
	function Album(&$gallery, $folder8, $cache=true) {
		if (!is_object($gallery) || strtolower(get_class($gallery)) != 'gallery') {
			debugLogBacktrace('Bad gallery in instantiation of album '.$folder8);
			$gallery = new Gallery();
		}
		$folder8 = sanitize_path($folder8);
		$folderFS = internalToFilesystem($folder8);
		$this->gallery = &$gallery;
		if (empty($folder8)) {
			$localpath = getAlbumFolder();
		} else {
			$localpath = getAlbumFolder() . $folderFS . "/";
		}
		if (filesystemToInternal($folderFS) != $folder8) { // an attempt to spoof the album name.
			$this->exists = false;
			return;
		}
		if ($dynamic = hasDynamicAlbumSuffix($folder8)) {
			$localpath = substr($localpath, 0, -1);
			$this->set('dynamic', 1);
		}

		// Must be a valid (local) folder:
		$valid = file_exists($localpath) && ($dynamic || is_dir($localpath));
		if(!$valid || strpos($localpath, '..') !== false) {
			$this->exists = false;
			return;
		}
		$this->localpath = $localpath;
		$this->name = $folder8;
		$new = parent::PersistentObject('albums', array('folder' => $this->name), 'folder', $cache, empty($folder8));

		if ($dynamic) {
			$new = !$this->get('search_params');
			if ($new || (filemtime($this->localpath) > $this->get('mtime'))) {
				$data = file_get_contents($this->localpath);
				while (!empty($data)) {
					$data1 = trim(substr($data, 0, $i = strpos($data, "\n")));
					if ($i === false) {
						$data1 = $data;
						$data = '';
					} else {
						$data = substr($data, $i + 1);
					}
					if (strpos($data1, 'WORDS=') !== false) {
						$words = "words=".urlencode(substr($data1, 6));
					}
					if (strpos($data1, 'THUMB=') !== false) {
						$thumb = trim(substr($data1, 6));
						$this->set('thumb', $thumb);
					}
					if (strpos($data1, 'FIELDS=') !== false) {
						$fields = "&searchfields=".trim(substr($data1, 7));
					}
				}
				if (!empty($words)) {
					if (empty($fields)) {
						$fields = '&searchfields=tags';
					}
					$this->set('search_params', $words.$fields);
				}

				$this->set('mtime', filemtime($this->localpath));
				if ($new) {
					$title = $this->get('title');
					$this->set('title', substr($title, 0, -4)); // Strip the .'.alb' suffix
					$this->setDateTime(strftime('%Y-%m-%d %T', $this->get('mtime')));
				}
				$this->set('dynamic', 1);
				$this->save();
			}
		}
		zp_apply_filter('album_instantiate', $this);
		if ($new) zp_apply_filter('new_album', $this);
	}

	/**
	 * Sets default values for a new album
	 *
	 * @return bool
	 */
	function setDefaults() {
		// Set default data for a new Album (title and parent_id)
		$parentalbum = $this->getParent();
		$this->set('mtime', filemtime($this->localpath));
		$this->setDateTime(strftime('%Y-%m-%d %T', $this->get('mtime')));
		$title = trim($this->name);
		if (!is_null($parentalbum)) {
			$this->set('parentid', $parentalbum->getAlbumId());
			$title = substr($title, strrpos($title, '/')+1);
		}
		$this->set('title', sanitize($title, 2));

		return true;
	}

	/**
	 * Returns the folder on the filesystem
	 *
	 * @return string
	 */
	function getFolder() { return $this->name; }

	/**
	 * Returns the id of this album in the db
	 *
	 * @return int
	 */
	function getAlbumID() { return $this->id; }

	/**
	 * Returns The parent Album of this Album. NULL if this is a top-level album.
	 *
	 * @return object
	 */
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

	/**
	 * Returns the album guest user
	 *
	 * @return string
	 */
	function getUser() { return $this->get('user');	}

	/**
	 * Sets the album guest user
	 *
	 * @param string $user
	 */
	function setUser($user) { $this->set('user', $user);	}

	/**
	 * Returns the album password
	 *
	 * @return string
	 */
	function getPassword() { return $this->get('password'); }

	/**
	 * Sets the encrypted album password
	 *
	 * @param string $pwd the cleartext password
	 */
	function setPassword($pwd) {
		if (empty($pwd)) {
			$this->set('password', "");
		} else {
			$this->set('password', passwordHash($this->get('user'), $pwd));
		}
	}

	/**
	 * Returns the password hint for the album
	 *
	 * @return string
	 */
	function getPasswordHint() {
		return get_language_string($this->get('password_hint'));
	}

	/**
	 * Sets the album password hint
	 *
	 * @param string $hint the hint text
	 */
	function setPasswordHint($hint) { $this->set('password_hint', $hint); }


	/**
	 * Returns the album title
	 *
	 * @return string
	 */
	function getTitle() {
		return get_language_string($this->get('title'));
	}

	/**
	 * Stores the album title
	 *
	 * @param string $title the title
	 */
	function setTitle($title) { $this->set('title', $title); }


	/**
	 * Returns the album description
	 *
	 * @return string
	 */
	function getDesc() {
		return get_language_string($this->get('desc'));
	}

	/**
	 * Stores the album description
	 *
	 * @param string $desc description text
	 */
	function setDesc($desc) { $this->set('desc', $desc); }


	/**
	 * Returns the tag data of an album
	 *
	 * @return string
	 */
	function getTags() {
		return readTags($this->id, 'albums');
	}

	/**
	 * Stores tag information of an album
	 *
	 * @param string $tags the tag list
	 */
	function setTags($tags) {
		if (!is_array($tags)) {
			$tags = explode(',', $tags);
		}
		storeTags($tags, $this->id, 'albums');
	}


	/**
	 * Returns the unformatted date of the album
	 *
	 * @return int
	 */
	function getDateTime() { return $this->get('date'); }

	/**
	 * Stores the album date
	 *
	 * @param string $datetime formatted date
	 */
	function setDateTime($datetime) {
		if ($datetime) {
			$newtime = dateTimeConvert($datetime);
			if ($newtime === false) return;
			$this->set('date', $newtime);
		} else {
			$this->set('date', NULL);
		}
	}


	/**
	 * Returns the place data of an album
	 *
	 * @return string
	 */
	function getLocation() {
		return get_language_string($this->get('location'));
	}

	/**
	 * Stores the album place
	 *
	 * @param string $place text for the place field
	 */
	function setLocation($place) { $this->set('location', $place); }


	/**
	 * Returns either the subalbum sort direction or the image sort direction of the album
	 *
	 * @param string $what 'image_sortdirection' if you want the image direction,
	 *        'album_sortdirection' if you want it for the album
	 *
	 * @return string
	 */
	function getSortDirection($what) {
		if ($what == 'image') {
			$direction = $this->get('image_sortdirection');
			$type = $this->get('sort_type');
		} else {
			$direction = $this->get('album_sortdirection');
			$type = $this->get('subalbum_sort_type');
		}
		if (empty($type)) { // using inherited type, so use inherited direction
			$parentalbum = $this->getParent();
			if (is_null($parentalbum)) {
				if ($what == 'image') {
					$direction = getOption('image_sortdirection');
				} else {
					$direction = getOption('gallery_sortdirection');
				}
			} else {
				$direction = $parentalbum->getSortDirection($what);
			}
		}
		return $direction;
	}

	/**
	 * sets sort directions for the album
	 *
	 * @param string $what 'image_sortdirection' if you want the image direction,
	 *        'album_sortdirection' if you want it for the album
	 * @param string $val the direction
	 */
	function setSortDirection($what, $val) {
		if ($val) { $b = 1; } else { $b = 0; }
		if ($what == 'image') {
			$this->set('image_sortdirection', $b);
		} else {
			$this->set('album_sortdirection', $b);
		}
	}

	/**
	 * Returns the sort type of the album images
	 * Will return a parent sort type if the sort type for this album is empty
	 *
	 * @return string
	 */
	function getSortType() {
		$type = $this->get('sort_type');
		if (empty($type)) {
			$parentalbum = $this->getParent();
			if (is_null($parentalbum)) {
				$type = getOption('image_sorttype');
			} else {
				$type = $parentalbum->getSortType();
			}
		}
		return $type;
	}

	/**
	 * Stores the sort type for the album
	 *
	 * @param string $sorttype the album sort type
	 */
	function setSortType($sorttype) { $this->set('sort_type', $sorttype); }

	/**
	 * Returns the sort type for subalbums in this album.
	 *
	 * Will return a parent sort type if the sort type for this album is empty.
	 *
	 * @return string
	 */
	function getAlbumSortType() {
		$type = $this->get('subalbum_sort_type');
		if (empty($type)) {
			$parentalbum = $this->getParent();
			if (is_null($parentalbum)) {
				$type = getOption('gallery_sorttype');
			} else {
				$type = $parentalbum->getAlbumSortType();
			}
		}
		return $type;
	}

	/**
	 * Stores the subalbum sort type for this abum
	 *
	 * @param string $sorttype the subalbum sort type
	 */
	function setSubalbumSortType($sorttype) { $this->set('subalbum_sort_type', $sorttype); }

	/**
	 * Returns the image sort order for this album
	 *
	 * @return string
	 */
	function getSortOrder() { return $this->get('sort_order'); }

	/**
	 * Stores the image sort order for this album
	 *
	 * @param string $sortorder image sort order
	 */
	function setSortOrder($sortorder) { $this->set('sort_order', $sortorder); }

	/**
	 * Returns the DB key associated with the image sort type
	 *
	 * @param string $sorttype the sort type
	 * @return string
	 */
	function getImageSortKey($sorttype=null) {
		if (is_null($sorttype)) { $sorttype = $this->getSortType(); }
		return lookupSortKey($sorttype, 'filename', 'filename');
	}

	/**
	 * Returns the DB key associated with the subalbum sort type
	 *
	 * @param string $sorttype subalbum sort type
	 * @return string
	 */
	function getAlbumSortKey($sorttype=null) {
		if (empty($sorttype)) { $sorttype = $this->getAlbumSortType(); }
		return lookupSortKey($sorttype, 'sort_order', 'folder');
	}


	/**
	 * Returns true if the album is published
	 *
	 * @return bool
	 */
	function getShow() { return $this->get('show'); }

	/**
	 * Stores the published value for the album
	 *
	 * @param bool $show True if the album is published
	 */
	function setShow($show) { $this->set('show', $show ? 1 : 0); }

	/**
	 * Returns all folder names for all the subdirectories.
	 *
	 * @param string $page  Which page of subalbums to display.
	 * @param string $sorttype The sort strategy
	 * @param string $sortdirection The direction of the sort
	 * @param bool $care set to false if the order does not matter
	 * @return array
	 */

	function getAlbums($page=0, $sorttype=null, $sortdirection=null, $care=true) {
		if (is_null($this->subalbums) || $care && $sorttype.$sortdirection !== $this->lastsubalbumsort ) {
			if ($this->isDynamic()) {
				$search = $this->getSearchEngine();
				$subalbums = $search->getAlbums($page,NULL,NULL,false);
			} else {
				$dirs = $this->loadFileNames(true);
				$subalbums = array();
				foreach ($dirs as $dir) {
					$dir = $this->name . '/' . $dir;
					$subalbums[] = $dir;
				}
			}
			$key = $this->getAlbumSortKey($sorttype);
			$this->subalbums = $this->gallery->sortAlbumArray($this, $subalbums, $key, $sortdirection);
			$this->lastsubalbumsort = $sorttype.$sortdirection;
		}

		if ($page == 0) {
			return $this->subalbums;
		} else {
			$albums_per_page = max(1, getOption('albums_per_page'));
			return array_slice($this->subalbums, $albums_per_page*($page-1), $albums_per_page);
		}
	}

	/**
	 * Returns the count of subalbums
	 *
	 * @return int
	 */
	function getNumAlbums() {
		return count($this->getAlbums(0,NULL,NULL,false));
	}

	/**
	 * Returns a of a slice of the images for this album. They will
	 * also be sorted according to the sort type of this album, or by filename if none
	 * has been set.
	 *
	 * @param string $page  Which page of images should be returned. If zero, all images are returned.
	 * @param int $firstPageCount count of images that go on the album/image transition page
	 * @param string $sorttype optional sort type
	 * @param string $sortdirection optional sort direction
	 * @parem bool $care set to false if the order of the images does not matter
	 *
	 * @return array
	 */
	function getImages($page=0, $firstPageCount=0, $sorttype=null, $sortdirection=null, $care=true) {
		if (is_null($this->images) || $care && $sorttype.$sortdirection !== $this->lastimagesort) {
			if ($this->isDynamic()) {
				$searchengine = $this->getSearchEngine();
				$images = $searchengine->getSearchImages($sorttype, $sortdirection);
			} else {
				// Load, sort, and store the images in this Album.
				$images = $this->loadFileNames();
				$images = $this->sortImageArray($images, $sorttype, $sortdirection);
			}
			$this->images = $images;
			$this->lastimagesort = $sorttype.$sortdirection;
		}
		// Return the cut of images based on $page. Page 0 means show all.
		if ($page == 0) {
			return $this->images;
		} else {
			// Only return $firstPageCount images if we are on the first page and $firstPageCount > 0
			if (($page==1) && ($firstPageCount>0)) {
				$pageStart = 0;
				$images_per_page = $firstPageCount;

			} else {
				if ($firstPageCount>0) {
					$fetchPage = $page - 2;
				} else {
					$fetchPage = $page - 1;
				}
				$images_per_page = max(1, getOption('images_per_page'));
				$pageStart = $firstPageCount + $images_per_page * $fetchPage;

			}
			$slice = array_slice($this->images, $pageStart , $images_per_page);

			return $slice;
		}
	}


	/**
	 * sortImageArray will sort an array of Images based on the given key. The
	 * key must be one of (filename, title, sort_order) at the moment.
	 *
	 * @param array $images The array of filenames to be sorted.
	 * @param  string $sorttype optional sort type
	 * @param  string $sortdirection optional sort direction
	 * @return array
	 */
	function sortImageArray($images, $sorttype, $sortdirection) {
		$mine = isMyAlbum($this->name, ALL_RIGHTS);
		$sortkey = str_replace('`','',$this->getImageSortKey($sorttype));
		if (($sortkey == '`sort_order`') || ($sortkey == 'RAND()')) { // manual sort is always ascending
			$order = false;
		} else {
			if (!is_null($sortdirection)) {
				$order = strtoupper($sortdirection) == 'DESC';
			} else {
				$order = $this->getSortDirection('image');
			}
		}

		$result = query($sql = "SELECT * FROM " . prefix("images") . " WHERE `albumid`= " . $this->id);
		$results = array();
		while ($row = mysql_fetch_assoc($result)) {
			$results[] = $row;
		}
		foreach ($results as $rowkey=>$row) {
			$filename = $row['filename'];
			if (($key = array_search($filename,$images)) !== false) {	// the image exists in the filesystem
				unset($images[$key]);
			} else {																									// the image no longer exists
				$id = $row['id'];
				query("DELETE FROM ".prefix('images')." WHERE `id`=$id"); // delete the record
				query("DELETE FROM ".prefix('comments')." WHERE `type` IN (".zp_image_types("'").") AND `ownerid`= '$id'"); // remove image comments
				unset($results[$rowkey]);
			}
		}
		foreach ($images as $filename) {	// these images are not in the database
			$imageobj = newImage($this,$filename);
			$results[] = $imageobj->data;
		}
		// now put the results into the right order
		switch ($sortkey) {
			case 'title':
			case 'desc':
				$results = sortByMultilingual($results, $sortkey, $order);
				break;
			case 'RAND()':
				shuffle($results);
				break;
			default:
				$results = sortMultiArray($results, $sortkey, $order);
				break;
		}
		// the results are now in the correct order
		$images_ordered = array();
		foreach ($results as $key=>$row) { // check for visible
			if ($row['show'] || $mine) {	// don't display it
				$images_ordered[] = $row['filename'];
			}
		}
		return $images_ordered;
	}


	/**
	 * Returns the number of images in this album (not counting its subalbums)
	 *
	 * @return int
	 */
	function getNumImages() {
		if (is_null($this->images)) {
			return count($this->getImages(0,0,NULL,NULL,false));
		}
		return count($this->images);
	}

	/**
	 * Returns an image from the album based on the index passed.
	 *
	 * @param int $index
	 * @return int
	 */
	function getImage($index) {
		$images = $this->getImages();
		if ($index >= 0 && $index < count($images)) {
			if ($this->isDynamic()) {
				$album = new Album($this->gallery, $images[$index]['folder']);
				return newImage($album, $images[$index]['filename']);
			} else {
				return newImage($this, $this->images[$index]);
			}
			return false;
		}
	}

	/**
	 * Gets the album's set thumbnail image from the database if one exists,
	 * otherwise, finds the first image in the album or sub-album and returns it
	 * as an Image object.
	 *
	 * @return Image
	 */
	function getAlbumThumbImage() {

		if (!is_null($this->albumthumbnail)) return $this->albumthumbnail;

		$albumdir = $this->localpath;
		$thumb = $this->get('thumb');
		$i = strpos($thumb, '/');
		if ($root = ($i === 0)) {
			$thumb = substr($thumb, 1); // strip off the slash
			$albumdir = getAlbumFolder();
		}
		$shuffle = empty($thumb);
		$field = getOption('AlbumThumbSelectField');
		$direction = getOption('AlbumThumbSelectDirection');
		if (!empty($thumb) && !is_numeric($thumb) && file_exists($albumdir.internalToFilesystem($thumb))) {
			if ($i===false) {
				return newImage($this, $thumb);
			} else {
				$pieces = explode('/', $thumb);
				$i = count($pieces);
				$thumb = $pieces[$i-1];
				unset($pieces[$i-1]);
				$albumdir = implode('/', $pieces);
				if (!$root) { $albumdir = $this->name . "/" . $albumdir; } else { $albumdir = $albumdir . "/";}
				$this->albumthumbnail = newImage(new Album($this->gallery, $albumdir), $thumb);
				return $this->albumthumbnail;
			}
		} else {
			$this->getImages(0, 0, $field, $direction);
			$thumbs = $this->images;
			if (!is_null($thumbs)) {
				if ($shuffle) {
					shuffle($thumbs);
				}
				$mine = isMyAlbum($this->name,ALL_RIGHTS);
				$other = NULL;
				while (count($thumbs) > 0) {	// first check for images
					$thumb = array_shift($thumbs);
					$thumb = newImage($this, $thumb);
					if ($mine || $thumb->getShow()) {
						if (isImagePhoto($thumb)) {	// legitimate image
							$this->albumthumbnail = $thumb;
							return $this->albumthumbnail;
						} else {
							if (!is_null($thumb->objectsThumb)) {	//	"other" image with a thumb sidecar
								$this->albumthumbnail = $thumb;
								return $this->albumthumbnail;
							} else {
								if (is_null($other)) $other = $thumb;
							}
						}
					}
				}
				if (!is_null($other)) {	//	"other" image, default thumb
					$this->albumthumbnail = $other;
					return $this->albumthumbnail;
				}
			}
		}
		// Otherwise, look in sub-albums.
		$subalbums = $this->getAlbums();
		if (!is_null($subalbums)) {
			if ($shuffle) {
				shuffle($subalbums);
			}
			while (count($subalbums) > 0) {
				$folder = array_pop($subalbums);
				$subalbum = new Album($this->gallery, $folder);
				$pwd = $subalbum->getPassword();
				if (($subalbum->getShow() && empty($pwd)) || isMyALbum($folder, ALL_RIGHTS)) {
					$thumb = $subalbum->getAlbumThumbImage();
					if (strtolower(get_class($thumb)) !== 'transientimage' && $thumb->exists) {
						$this->albumthumbnail =  $thumb;
						return $thumb;
					}
				}
			}
		}

		$nullimage = SERVERPATH.'/'.ZENFOLDER.'/images/imageDefault.png';
		if (OFFSET_PATH == 0) { // check for theme imageDefault.png if we are in the gallery
			$theme = '';
			$uralbum = getUralbum($this);
			$albumtheme = $uralbum->getAlbumTheme();
			if (!empty($albumtheme)) {
				$theme = $albumtheme;
			} else {
				$theme = $this->gallery->getCurrentTheme();
			}
			if (!empty($theme)) {
				$themeimage = SERVERPATH.'/'.THEMEFOLDER.'/'.$theme.'/images/imageDefault.png';
				if (file_exists(internalToFilesystem($themeimage))) {
					$nullimage = $themeimage;
				}
			}
		}
		$this->albumthumbnail = new transientimage($this, $nullimage);
		return $this->albumthumbnail;
	}

	/**
	 * Gets the thumbnail URL for the album thumbnail image as returned by $this->getAlbumThumbImage();
	 * @return string
	 */
	function getAlbumThumb() {
		$image = $this->getAlbumThumbImage();
		return $image->getThumb('album');
	}

	/**
	 * Stores the thumbnail path for an album thumg
	 *
	 * @param string $filename thumbnail path
	 */
	function setAlbumThumb($filename) { $this->set('thumb', $filename); }

	/**
	 * Returns an URL to the album, including the current page number
	 *
	 * @return string
	 */
	function getAlbumLink() {
		global $_zp_page;

		$rewrite = pathurlencode($this->name) . '/';
		$plain = '/index.php?album=' . urlencode($this->name). '/';
		if ($_zp_page) {
			$rewrite .= "page/$_zp_page";
			$plain .= "&page=$_zp_page";
		}
		return rewrite_path($rewrite, $plain);
	}

	/**
	 * Returns the album following the current album
	 *
	 * @return object
	 */
	function getNextAlbum() {
		if (is_null($parent = $this->getParent())) {
			$albums = $this->gallery->getAlbums(0);
		} else {
			$albums = $parent->getAlbums(0);
		}
		$inx = array_search($this->name, $albums)+1;
		if ($inx >= 0 && $inx < count($albums)) {
			return new Album($this->gallery, $albums[$inx]);
		}
		return null;
	}

	/**
	 * Returns the album prior to the current album
	 *
	 * @return object
	 */
	function getPrevAlbum() {
		if (is_null($parent = $this->getParent())) {
			$albums = $this->gallery->getAlbums(0);
		} else {
			$albums = $parent->getAlbums(0);
		}
		$inx = array_search($this->name, $albums)-1;
		if ($inx >= 0 && $inx < count($albums)) {
			return new Album($this->gallery, $albums[$inx]);
		}
		return null;
	}

	/**
	 * Returns the page number in the gallery of this album
	 *
	 * @return int
	 */
	function getGalleryPage() {
		if ($this->index == null)
			$this->index = array_search($this->name, $this->gallery->getAlbums(0));
		return floor(($this->index / galleryAlbumsPerPage())+1);
	}

	/**
	 * changes the parent of an album for move/copy
	 *
	 * @param string $newfolder The folder name of the new parent
	 */
	function updateParent($newfolder) {
		$this->name = $newfolder;
		$parentname = dirname($newfolder);
		if ($parentname == '/' || $parentname == '.') $parentname = '';
		if (empty($parentname)) {
			$this->set('parentid', NULL);
		} else {
			$parent = new Album($this->gallery, $parentname);
			$this->set('parentid', $parent->getAlbumid());
		}
		$this->save();
	}

	/**
	 * Delete the entire album PERMANENTLY. Be careful! This is unrecoverable.
	 * Returns true if successful
	 *
	 * @return bool
	 */
	function deleteAlbum() {
		if (!$this->isDynamic()) {
			foreach ($this->getAlbums() as $folder) {
				$subalbum = new Album($this->gallery, $folder);
				$subalbum->deleteAlbum();
			}
			foreach($this->getImages() as $filename) {
				$image = newImage($this, $filename);
				$image->deleteImage(true);
			}
			chdir($this->localpath);
			$filelist = safe_glob('*');
			foreach($filelist as $file) {
				if (($file != '.') && ($file != '..')) {
					unlink($this->localpath . $file); // clean out any other files in the folder
				}
			}
		}
		query("DELETE FROM " . prefix('options') . "WHERE `ownerid`=" . $this->id);
		query("DELETE FROM " . prefix('comments') . "WHERE `type`='albums' AND `ownerid`=" . $this->id);
		query("DELETE FROM " . prefix('obj_to_tag') . "WHERE `type`='albums' AND `objectid`=" . $this->id);
		query("DELETE FROM " . prefix('albums') . " WHERE `id` = " . $this->id);
		if ($this->isDynamic()) {
			@unlink($this->localpath.'.xmp'); // delete the sidecar
			return unlink($this->localpath);
		} else {
			@unlink(substr($this->localpath,0,-1).'.xmp'); // delete the sidecar
			return rmdir($this->localpath);
		}
	}

	/**
	 * Move this album to the location specified by $newfolder, copying all
	 * metadata, subalbums, and subalbums' metadata with it.
	 * @param $newfolder string the folder to move to, including the name of the current folder (possibly renamed).
	 * @return int 0 on success and error indicator on failure.
	 *
	 */
	function moveAlbum($newfolder) {
		// First, ensure the new base directory exists.
		$oldfolder = $this->name;
		$dest = getAlbumFolder().internalToFilesystem($newfolder);
		// Check to see if the destination already exists
		if (file_exists($dest)) {
			// Disallow moving an album over an existing one.
			return 3;
		}
		if (substr($newfolder, count($oldfolder)) == $oldfolder) {
			// Disallow moving to a subfolder of the current folder.
			return 4;
		}
		if ($this->isDynamic()) {
			if (@rename($this->localpath, $dest))	{
				$success = true;
				$filestomove = safe_glob(substr($this->localpath,0,strrpos($this->localpath,'.')).'.*');
				foreach ($filestomove as $file) {
					if(in_array(strtolower(getSuffix($file)), $this->sidecars)) {
						$success = $success && @rename($file, dirname($dest).'/'.basename($file));
					}
				}
				$oldf = zp_escape_string($oldfolder);
				$sql = "UPDATE " . prefix('albums') . " SET folder='" . zp_escape_string($newfolder) . "' WHERE `id` = '".$this->getAlbumID()."'";
				$success = $success && query($sql);
				$this->updateParent($newfolder);
				if ($success) {
					return false;
				}
			}
			return 1;
		} else {
			if (mkdir_recursive(dirname($dest)) === TRUE) {
				// Make the move (rename).
				$rename = @rename($this->localpath, $dest);
				@rename(substr($this->localpath,0,-1).'.xmp', $dest.'.xmp');  // move the sidecar
				// Then: rename the cache folder
				$cacherename = @rename(SERVERCACHE . '/' . $oldfolder, SERVERCACHE . '/' . $newfolder);
				// Then: go through the db and change the album (and subalbum) paths. No ID changes are necessary for a move.
				// Get the subalbums.
				$oldf = zp_escape_string($oldfolder);
				$sql = "SELECT id, folder FROM " . prefix('albums') . " WHERE folder LIKE '$oldf%'";
				$result = query_full_array($sql);
				foreach ($result as $subrow) {
					$newsubfolder = $subrow['folder'];
					$newsubfolder = $newfolder . substr($newsubfolder, strlen($oldfolder));
					$newsubfolder = zp_escape_string($newsubfolder);
					$sql = "UPDATE ".prefix('albums'). " SET folder='$newsubfolder' WHERE id=".$subrow['id'];
					$subresult = query($sql);
					// Handle result here.
				}
				$this->updateParent($newfolder);
				return 0;
			}
		}
		return 1;
	}
	/**
	 * Rename this album folder. Alias for moveAlbum($newfoldername);
	 * @param $newfolder the new folder name of this album (including subalbum paths)
	 * @return boolean true on success or false on failure.
	 */
	function renameAlbum($newfolder) {
		return $this->moveAlbum($newfolder);
	}

	/**
	 * returns the SQL to insert a row like $subrow into $table
	 *
	 * @param string $subrow
	 * @param string $table
	 * @return string
	 */
	function replicateSQL($subrow, $table) {
		// From PersistentObject::copy()
		$insert_data = $subrow;
		unset($insert_data['id']);
		if (empty($insert_data)) { return true; }
		$sql = 'INSERT INTO ' . prefix($table) . ' (';
		$i = 0;
		foreach(array_keys($insert_data) as $col) {
			if ($i > 0) $sql .= ", ";
			$sql .= "`$col`";
			$i++;
		}
		$sql .= ') VALUES (';
		$i = 0;
		foreach(array_values($insert_data) as $value) {
			if ($i > 0) $sql .= ', ';
			if (is_null($value)) {
				$sql .= "NULL";
			} else {
				$sql .= "'" . zp_escape_string($value) . "'";
			}
			$i++;
		}
		$sql .= ');';
		return $sql;
	}

	/**
	 * Replicates the database data for copied albums.
	 * Returns the success of the replication.
	 *
	 * @param array $subrow the Row of data
	 * @param string $oldfolder the folder name of the old album
	 * @param string $newfolder the folder name of the new album
	 * @param bool $owner_row set to true if this is the owner album (and we have to change the parent ID)
	 * @return bool
	 */
	function replicateDBRow($subrow, $oldfolder, $newfolder, $owner_row) {
		$newsubfolder = $subrow['folder'];
		$newsubfolder = $newfolder . substr($newsubfolder, strlen($oldfolder));
		$newsubfolder = zp_escape_string($newsubfolder);
		$subrow['folder'] = $newsubfolder;

		if ($owner_row) {
			$parentname = dirname($newfolder);
			if ($parentname == '/' || $parentname == '.') $parentname = '';
			if (empty($parentname)) {
				$subrow['parentid'] = NULL;
			} else {
				$parent = new Album($this->gallery, $parentname);
				$subrow['parentid'] =  $parent->getAlbumid();
			}
		}
		$sql = $this->replicateSQL($subrow, 'albums');
		return query($sql);
	}

	/**
	 * Copy this album to the location specified by $newfolder, copying all
	 * metadata, subalbums, and subalbums' metadata with it.
	 * @param $newfolder string the folder to copy to, including the name of the current folder (possibly renamed).
	 * @return int 0 on success and error indicator on failure.
	 *
	 */
	function copyAlbum($newfolder) {
		// First, ensure the new base directory exists.
		$oldfolder = $this->name;
		$dest = getAlbumFolder().'/'.internalToFilesystem($newfolder);
		// Check to see if the destination directory already exists
		if (file_exists($dest)) {
			// Disallow moving an album over an existing one.
			return 3;
		}
		if (substr($newfolder, count($oldfolder)) == $oldfolder) {
			// Disallow copying to a subfolder of the current folder (infinite loop).
			return 4;
		}
		if ($this->isDynamic()) {
			if (@copy($this->localpath, $dest)) {
				$success = true;
				$filestocopy = safe_glob(substr($this->localpath,0,strrpos($this->localpath,'.')).'.*');
				foreach ($filestocopy as $file) {
					if(in_array(strtolower(getSuffix($file)), $this->sidecars)) {
						$success = $success && @copy($file, dirname($dest).'/'.basename($file));
					}
				}
				$oldf = zp_escape_string($oldfolder);
				$sql = "SELECT * FROM " . prefix('albums') . " WHERE `id` = '".$this->getAlbumID()."'";
				$subrow = query_single_row($sql);
				$success = $success && $this->replicateDBRow($subrow, $oldfolder, $newfolder, true);
				if ($success) return 0;
				return 1;
			} else {
				return 2;
			}
		} else {
			if (mkdir_recursive(dirname($dest)) === TRUE) {
				// Make the move (rename).
				$num = dircopy($this->localpath, $dest);
				@copy(substr($this->localpath,0,-1).'.xmp',$dest.'.xmp'); // copy the sidecar
				// Get the subalbums.
				$oldf = zp_escape_string($oldfolder);
				$sql = "SELECT * FROM " . prefix('albums') . " WHERE folder LIKE '$oldf%'";
				$result = query_full_array($sql);

				$allsuccess = true;
				foreach ($result as $subrow) {
					$success = $this->replicateDBRow($subrow, $oldfolder, $newfolder, $subrow['folder'] == $oldfolder);

					if ($success) {
						$oldID = $subrow['id'];
						$newID = mysql_insert_id();
						$sql = 'SELECT * FROM '.prefix('images').' WHERE `albumid`='.$oldID;
						$imageresult = query_full_array($sql);
						foreach ($imageresult as $imagerow) {
							$imagerow['albumid'] = $newID;
							$sql = $this->replicateSQL($imagerow, 'images');
							$success = query($sql);
							if ($success !== true) {
								$allsuccess = false;
							}
						}
					}

					if (!($success == true && mysql_affected_rows() == 1)) {
						$allsuccess = false;
					}
				}
				if ($allsuccess) return 0;
				return 1;
			}
		}
		return 1;
	}

	/**
	 * Returns true of comments are allowed
	 *
	 * @return bool
	 */
	function getCommentsAllowed() { return $this->get('commentson'); }

	/**
	 * Stores the value for comments allwed
	 *
	 * @param bool $commentson true if comments are enabled
	 */
	function setCommentsAllowed($commentson) { $this->set('commentson', $commentson ? 1 : 0); }

	/**
	 * For every image in the album, look for its file. Delete from the database
	 * if the file does not exist. Same for each sub-directory/album.
	 *
	 * @param bool $deep set to true for a thorough cleansing
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
			$sql2 = "DELETE FROM ".prefix('comments')." WHERE `type`='albums' AND `ownerid` = '" . array_pop($dead) . "'";
			foreach ($dead as $id) {
				$sql .= " OR `id` = '$id'";
				$sql2 .= " OR `ownerid` = '$id'";
			}
			query($sql);
			query($sql2);
		}

		// Get all sub-albums and make sure they exist.
		$result = query("SELECT * FROM ".prefix('albums')." WHERE `folder` LIKE '" . zp_escape_string($this->name) . "/%'");
		$dead = array();
		$live = array();
		// Does the dirname from the db row exist on disk?
		while($row = mysql_fetch_assoc($result)) {
			if (!is_dir(getAlbumFolder() . internalToFilesystem($row['folder'])) || in_array($row['folder'], $live)
			|| substr($row['folder'], -1) == '/' || substr($row['folder'], 0, 1) == '/') {
				$dead[] = $row['id'];
			} else {
				$live[] = $row['folder'];
			}
		}
		if (count($dead) > 0) {
			$sql = "DELETE FROM ".prefix('albums')." WHERE `id` = '" . array_pop($dead) . "'";
			$sql2 = "DELETE FROM ".prefix('comments')." WHERE `type`='albums' AND `ownerid` = '" . array_pop($dead) . "'";
			foreach ($dead as $albumid) {
				$sql .= " OR `id` = '$albumid'";
				$sql2 .= " OR `ownerid` = '$albumid'";
			}
			query($sql);
			query($sql2);
		}

		if ($deep) {
			foreach($this->getAlbums(0) as $dir) {
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
		if (!$this->isDynamic()) return; // nothing to do
		$images = $this->getImages(0);
		$subalbums = $this->getAlbums(0);
		foreach($subalbums as $dir) {
			$album = new Album($this->gallery, $dir);
			$album->preLoad();
		}
	}


	/**
	 * Load all of the filenames that are found in this Albums directory on disk.
	 * Returns an array with all the names.
	 *
	 * @param  $dirs Whether or not to return directories ONLY with the file array.
	 * @return array
	 */
	function loadFileNames($dirs=false) {
		if ($this->isDynamic()) {  // there are no 'real' files
			return array();
		}
		$albumdir = $this->localpath;
		if (!is_dir($albumdir) || !is_readable($albumdir)) {
			if (!is_dir($albumdir)) {
				$msg = sprintf(gettext("Error: The album named %s cannot be found."), $this->name);
			} else {
				$msg = sprintf(gettext("Error: The album %s is not readable."), $this->name);
			}
			zp_error($msg,false);
			return array();
		}
		$dir = opendir($albumdir);
		$files = array();
		$others = array();

		while (false !== ($file = readdir($dir))) {
			$file8 = filesystemToInternal($file);
			if ($dirs && (is_dir($albumdir.$file) && (substr($file, 0, 1) != '.') || hasDynamicAlbumSuffix($file))) {
				$files[] = $file8;
			} else if (!$dirs && is_file($albumdir.$file)) {
				if (is_valid_other_type($file)) {
					$files[] = $file8;
					$others[] = $file8;
				} else if (is_valid_image($file)) {
					$files[] = $file8;
				}
			}
		}
		closedir($dir);
		if (count($others) > 0) {
			$others_thumbs = array();
			foreach($others as $other) {
				$others_root = substr($other, 0, strrpos($other,"."));
				foreach($files as $image) {
					$image_root = substr($image, 0, strrpos($image,"."));
					if ($image_root == $others_root && $image != $other) {
						$others_thumbs[] = $image;
					}
				}
			}
			$files = array_diff($files, $others_thumbs);
		}

		if ($dirs) $filter = 'album_filter'; else $filter = 'image_filter';
		return zp_apply_filter($filter, $files);
	}

	/**
	 * Returns an array of comments for this album
	 *
	 * @param bool $moderated if false, ignores comments marked for moderation
	 * @param bool $private if false ignores private comments
	 * @param bool $desc set to true for descending order
	 * @return array
	 */
	function getComments($moderated=false, $private=false, $desc=false) {
		$sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") .
			" WHERE `type`='albums' AND `ownerid`='" . $this->id . "'";
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
	 * Adds comments to the album
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
	 * Returns the count of comments in the album. Ignores comments in moderation
	 *
	 * @return int
	 */
	function getCommentCount() {
		if (is_null($this->commentcount)) {
			if ($this->comments == null) {
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='albums' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $this->id);
				$this->commentcount = array_shift($count);
			} else {
				$this->commentcount = count($this->comments);
			}
		}
		return $this->commentcount;
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
	 * Returns true if the album is "dynamic"
	 *
	 * @return bool
	 */
	function isDynamic() {
		return $this->get('dynamic');
	}

	/**
	 * Returns the search parameters for a dynamic album
	 *
	 * @param bool $processed set false to process the parms
	 *
	 * @return string
	 */
	function getSearchParams($processed=false) {
		if ($processed) {
			if (is_null($this->getSearchEngine())) return NULL;
			return $this->searchengine->getSearchParams(false);
		} else {
			return $this->get('search_params');
		}
	}

	/**
	 * Sets the search parameters of a dynamic album
	 *
	 * @param string $params The search string to produce the dynamic album
	 */
	function setSearchParams($params) {
		$this->set('search_params', $params);
	}

	/**
	 * Returns the search engine for a dynamic album
	 *
	 * @return object
	 */
	function getSearchEngine() {
		if (!$this->isDynamic()) return null;
		if (!is_null($this->searchengine)) return $this->searchengine;
		$this->searchengine = new SearchEngine(true);
		$params = $this->get('search_params');
		$params .= '&albumname='.$this->name;
		$this->searchengine->setSearchParams($params);
		return $this->searchengine;
	}

	/**
	 * Returns the theme for the album
	 *
	 * @return string
	 */
	function getAlbumTheme() {
		return $this->get('album_theme');
	}
	/**
	 * Sets the theme of the album
	 *
	 * @param string $theme
	 */
	function setAlbumTheme($theme) {
		$this->set('album_theme', $theme);
	}
	
// TODO: remove these functions
	/**
	 * deprecated function.
	 */
	function getSubAlbums($page=0, $sorttype=null, $sortdirection=null) {
		trigger_error(gettext('getSubAlbums is deprecated. Use getAlbums().'), E_USER_NOTICE);
		return $this->getAlbums($page, $sorttype, $sortdirection);
	}
	/**
	 * deprecated function.
	 */
		function getNumSubAlbums() {
		trigger_error(gettext('getNumSubAlbums is deprecated. Use getNumAlbums().'), E_USER_NOTICE);
		return $this->getNumAlbums();
	}
	
}
?>