<?php
//*******************************************************************************
//* Album Class *****************************************************************
//*******************************************************************************

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

	/**
	 * Constructor for albums
	 *
	 * @param object &$gallery The parent gallery
	 * @param string $folder folder name of the album
	 * @param bool $cache load from cache if present
	 * @return Album
	 */
	function Album(&$gallery, $folder, $cache=true) {
		$folder = sanitize_path($folder);
		$this->name = $folder;
		$this->gallery = &$gallery;
		if ($folder == '') {
			$this->localpath = getAlbumFolder();
		} else {
			$this->localpath = getAlbumFolder() . $folder . "/";
		}
		if (hasDyanmicAlbumSuffix($folder)) {
			$this->localpath = substr($this->localpath, 0, -1);
		}

		// Second defense against upward folder traversal:
		if(!file_exists($this->localpath) || strpos($this->localpath, '..') !== false) {
			$this->exists = false;
			return false;
		}
		$new = parent::PersistentObject('albums', array('folder' => $this->name), 'folder', $cache);
		if (hasDyanmicAlbumSuffix($folder)) {
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
						$fields = '&searchfields=4';
					}
					$this->set('search_params', $words.$fields);
				}
			}
			$this->set('dynamic', 1);
			$this->set('mtime', filemtime($this->localpath));
			if ($new) {
				$title = $this->get('title');
				$this->set('title', substr($title, 0, -4));
				$this->setDateTime(strftime('%Y/%m/%d %T', filemtime($this->localpath)));
			}
			$this->save();
		}
	}

	/**
	 * Sets default values for a new album
	 *
	 * @return bool
	 */
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
	 * Returns the album password
	 *
	 * @return string
	 */
	function getPassword() { return $this->get('password'); }

	/**
	 * Sets the album password (md5 encrypted)
	 *
	 * @param string $pwd the cleartext password
	 */
	function setPassword($pwd) {
		if (empty($pwd)) {
			$this->set('password', "");
		} else {
			$this->set('password', md5($pwd));
		}
	}

	/**
	 * Returns the password hint for the album
	 *
	 * @return string
	 */
	function getPasswordHint() { return $this->get('password_hint'); }

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
	function getTitle() { return $this->get('title'); }

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
	function getDesc() { return $this->get('desc'); }

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
	function getTags() { return $this->get('tags'); }

	/**
	 * Stores tag information of an album
	 *
	 * @param string $tags the tag string
	 */
	function setTags($tags) { $this->set('tags', $tags); }


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
		if ($datetime == "") {
			$this->set('date', '0000-00-00 00:00:00');
		} else {
			$time = @strtotime($datetime);
			if ($time == -1 || $time == false) return;
			$this->set('date', date('Y-m-d H:i:s', $time));
		}
	}


	/**
	 * Returns the place data of an album
	 *
	 * @return string
	 */
	function getPlace() { return $this->get('place'); }

	/**
	 * Stores the album place
	 *
	 * @param string $place text for the place field
	 */
	function setPlace($place) { $this->set('place', $place); }


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
			return $this->get('image_sortdirection');
		} else {
			return $this->get('album_sortdirection');
		}
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
	 * Returns the sort type of the album
	 * Will return a parent sort type if the sort type for this album is empty
	 *
	 * @return string
	 */
	function getSortType() {
		$type = $this->get('sort_type');
		if (empty($type)) {
			$parentalbum = $this->getParent();
			if (is_null($parentalbum)) {
				$type = getOption('gallery_sorttype');
				$direction = getOption('gallery_sortdirection');
			} else {
				$direction = $parentalbum->getSortDirection('album');
				$type = $parentalbum->getSortType();
			}
			if (!empty($type)) {
				$this->set('sort_type', $type);
				$this->set('image_sortdirection', $direction);
				$this->save();
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
	function getSubalbumSortType() {
		$type = $this->get('subalbum_sort_type');
		if (empty($type)) {
			$parentalbum = $this->getParent();
			if (is_null($parentalbum)) {
				$type = getOption('gallery_sorttype');
				$direction = getOption('gallery_sortdirection');
			} else {
				$direction = $parentalbum->getSortDirection('album');
				$type = $parentalbum->getSortType();
			}
			if (!empty($type)) {
				$this->set('subalbum_sort_type', $type);
				$this->set('album_sortdirection', $direction);
				$this->save();
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
	 * Returns the DB key associated with the sort type
	 *
	 * @param string $sorttype the sort type
	 * @return string
	 */
	function getSortKey($sorttype=null) {
		if (is_null($sorttype)) { $sorttype = $this->getSortType(); }
		return albumSortKey($sorttype);
	}

	/**
	 * Returns the DB key associated with the subalbum sort type
	 *
	 * @param string $sorttype subalbum sort type
	 * @return string
	 */
	function getSubalbumSortKey($sorttype=null) {
		if (is_null($sorttype)) { $sorttype = $this->getSubalbumSortType(); }
		return subalbumSortKey($sorttype);
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
	 * @return array
	 */

	function getSubAlbums($page=0, $sorttype=null, $sortdirection=null) {
		if (is_null($this->subalbums)) {
			$dirs = $this->loadFileNames(true);
			$subalbums = array();

			foreach ($dirs as $dir) {
				$dir = $this->name . '/' . $dir;
				$subalbums[] = $dir;
			}
			$key = $this->getSubalbumSortKey($sorttype);
			if (is_null($sortdirection)) {
				if ($this->getSortDirection('album')) { $key .= ' DESC'; }
			} else {
				$key .= ' ' . $sortdirection;
			}
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
	 * Returns a of a slice of the images for this album. They will
	 * also be sorted according to the sort type of this album, or by filename if none
	 * has been set.
	 *
	 * @param  string $page  Which page of images should be returned. If zero, all images are returned.
	 * @param int $firstPageCount count of images that go on the album/image transition page
	 * @param  string $sorttype optional sort type
	 * @param  string $sortdirection optional sort direction
	 * @return array
	 */
	function getImages($page=0, $firstPageCount=0, $sorttype=null, $sortdirection=null) {
		if (is_null($this->images)) {
			// Load, sort, and store the images in this Album.
			$images = $this->loadFileNames();
			$images = $this->sortImageArray($images, $sorttype, $sortdirection);
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

			} else {
				if ($firstPageCount>0) {
					$fetchPage = $page - 2;
				} else {
					$fetchPage = $page - 1;
				}
				$images_per_page = getOption('images_per_page');
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
	function sortImageArray($images, $sorttype=NULL, $sortdirection=NULL) {
		global $_zp_loggedin;

		$hidden = array();
		$key = $this->getSortKey($sorttype);
		$direction = '';
		if (!is_null($sortdirection)) {
			$direction = $sortdirection;
		} else {
			if ($this->getSortDirection('image')) {
				$direction = ' DESC';
			}
		}
		$result = query("SELECT `filename`, `title`, `sort_order`, `show`, `id` FROM " . prefix("images")
		. " WHERE `albumid`=" . $this->id . " ORDER BY " . $key . $direction);

		$i = 0;
		$flippedimages = array_flip($images);
		$images_to_keys = array();
		$images_in_db = array();
		$images_invisible = array();
		while ($row = mysql_fetch_assoc($result)) { // see what images are in the database so we can check for visible
			$filename = $row['filename'];
			if (isset($flippedimages[$filename])) { // ignore db entries for images that no longer exist.
				if ($_zp_loggedin || $row['show']) {
					$images_to_keys[$filename] = $i;
					$i++;
				}
				$images_in_db[] = $filename;
			} else {
				$id = $row['id'];
				query("DELETE FROM ".prefix('images')." WHERE `id`=$id"); // delete the record
				query("DELETE FROM ".prefix('comments')." WHERE `type`='images' AND `ownerid`=$id"); // remove image comments
			}
		}
		// Place the images not yet in the database before those with sort columns.
		// This is consistent with the sort oder of a NULL sort_order key in manual sorts
		// but will almost certainly be wrong in all other cases.
		$images_not_in_db = array_diff($images, $images_in_db);
		foreach($images_not_in_db as $filename) {
			$images_to_keys[$filename] = $i;
			$i++;
		}
		$images = array_flip($images_to_keys);
		ksort($images);
		return $images;
	}


	/**
	 * Returns the number of images in this album (not counting its subalbums)
	 *
	 * @return int
	 */
	function getNumImages() {
		if (is_null($this->images)) {
			$this->getImages(0);
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
		if ($index >= 0 && $index < $this->getNumImages()) {
			return new Image($this, $this->images[$index]);
		}
		return false;
	}

	/**
	 * Gets the album's set thumbnail image from the database if one exists,
	 * otherwise, finds the first image in the album or sub-album and returns it
	 * as an Image object.
	 *
	 * @return Image
	 */
	function getAlbumThumbImage() {
		/* TODO: This should fail more gracefully when there are errors reading folders etc. */

		$albumdir = getAlbumFolder() . $this->name ."/";
		$thumb = $this->get('thumb');
		$i = strpos($thumb, '/');
		if ($root = ($i === 0)) {
			$thumb = substr($thumb, 1); // strip off the slash
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
		} else if (!$this->isDynamic()) {
			$dp = opendir($albumdir);
			if (is_null($this->images)) {
				$this->getImages(0);
			}
			$thumbs = $this->images;
			if (!is_null($thumbs)) {
				shuffle($thumbs);
				while (count($thumbs) > 0) {
					$thumb = array_pop($thumbs);
					if (is_valid_image($thumb)) {
						return new Image($this, $thumb);
					}
				}
			}
			// Otherwise, look in sub-albums.
			$subalbums = $this->getSubAlbums();
			if (!is_null($subalbums)) {
				shuffle($subalbums);
				while (count($subalbums) > 0) {
					$subalbum = new Album($this->gallery, array_pop($subalbums));
					$thumb = $subalbum->getAlbumThumbImage();
					if ($thumb != NULL && $thumb->exists) {
						return $thumb;
					}
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
	 * @return string
	 */
	function getAlbumThumb() {
		$image = $this->getAlbumThumbImage();
		return $image->getThumb();
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
			$albums = $parent->getSubAlbums(0);
		}
		$inx = array_search($this->name, $albums)+1;
		if ($inx >= 0 && $inx < count($albums)) {
			return new Album($parent, $albums[$inx]);
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
			$albums = $parent->getSubAlbums(0);
		}
		$inx = array_search($this->name, $albums)-1;
		if ($inx >= 0 && $inx < count($albums)) {
			return new Album($paraent, $albums[$inx]);
		}
		return null;
	}

	/**
	 * Returns the page number in the gallery of this album
	 *
	 * @return int
	 */
	function getGalleryPage() {
		$albums_per_page = getOption('albums_per_page');
		if ($this->index == null)
		$this->index = array_search($this->name, $this->gallery->getAlbums(0));
		return floor(($this->index / $albums_per_page)+1);
	}


	/**
	 * Delete the entire album PERMANENTLY. Be careful! This is unrecoverable.
	 * Returns true if successful
	 *
	 * @return bool
	 */
	function deleteAlbum() {
		if (!$this->isDynamic()) {
			foreach ($this->getSubAlbums() as $folder) {
				$subalbum = new Album($album, $folder);
				$subalbum->deleteAlbum();
			}
			foreach($this->getImages() as $filename) {
				$image = new Image($this, $filename);
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
		query("DELETE FROM " . prefix('comments') . "WHERE `type`='albums' AND `ownerid`=" . $this->id);
		query("DELETE FROM " . prefix('albums') . " WHERE `id` = " . $this->id);
		if ($this->isDynamic()) {
			return unlink($this->localpath);
		} else {
			return rmdir($this->localpath);
		}
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
			$sql2 = "DELETE FROM ".prefix('comments')." WHERE `type`='albums' AND `ownerid` = '" . array_pop($dead) . "'";
			foreach ($dead as $albumid) {
				$sql .= " OR `id` = '$albumid'";
				$sql2 .= " OR `ownerid` = '$albumid'";
			}
			query($sql);
			query($sql2);
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
	 * Returns an array with all the names.
	 *
	 * @param  $dirs Whether or not to return directories ONLY with the file array.
	 * @return array
	 */
	function loadFileNames($dirs=false) {
		if ($this->isDynamic()) {  // there are no 'real' files
			return array();
		}
		$albumdir = getAlbumFolder() . $this->name . "/";
		if (!is_dir($albumdir) || !is_readable($albumdir)) {
			$msg = "Error: The 'albums' directory (" . $this->albumdir . ") ";
			if (!is_dir($this->albumdir)) {
				$msg .= "cannot be found.";
			} else {
				$msg .= "is not readable.";
			}
			die($msg);
		}
		$dir = opendir($albumdir);
		$files = array();
		$videos = array();


		while (false !== ($file = readdir($dir))) {
			if ($dirs && (is_dir($albumdir.$file) && (substr($file, 0, 1) != '.') ||
			hasDyanmicAlbumSuffix($file))) {
				$files[] = $file;
			} else if (!$dirs && is_file($albumdir.$file)) {
				if (is_valid_video($file)) {
					$files[] = $file;
					$videos[] = $file;
				} else if (is_valid_image($file)) {
					$files[] = $file;
				}
			}
		}
		closedir($dir);
		if (count($videos) > 0) {
			$video_thumbs = array();
			foreach($videos as $video) {
				$video_root = substr($video, 0, strrpos($video,"."));
				foreach($files as $image) {
					$image_root = substr($image, 0, strrpos($image,"."));
					if ($image_root == $video_root && $image != $video) {
						$video_thumbs[] = $image;
					}
				}
			}
			$files = array_diff($files, $video_thumbs);
		}

		return $files;
	}

	/**
	 * Returns an array of comments for this album
	 *
	 * @param bool $moderated if false, ignores comments marked for moderation
	 * @return array
	 */
	function getComments($moderated=false) {
		$sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") .
 			" WHERE `type`='albums' AND `ownerid`='" . $this->id . "'";
		if (!$moderated) {
			$sql .= " AND `inmoderation`=0";
		}
		$sql .= " ORDER BY id";
		$comments = query_full_array($sql);
		$this->comments = $comments;
		return $this->comments;
	}

	/**
	 * Adds comments to the album
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
	 */
	function addComment($name, $email, $website, $comment, $code, $code_ok) {
		$goodMessage = postComment($name, $email, $website, $comment, $code, $code_ok, $this);
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
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='albums' AND `inmoderation`=0 AND `ownerid`=" . $this->id);
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
	function getCustomData() { return $this->get('custom_data'); }

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
	 * @return string
	 */
	function getSearchParams() {
		return $this->get('search_params');
	}

	/**
	 * Sets the search parameters of a dynamic album
	 *
	 * @param string $params The search string to produce the dynamic album
	 */
	function setSearchParams($params) {
		$this->set('search_params', $params);
	}

}

?>