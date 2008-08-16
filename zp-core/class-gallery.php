<?php
/**
 * Gallery Class
 * @package classes
 */

class Gallery {

	var $albumdir = NULL;
	var $albums = NULL;
	var $options = NULL;
	var $theme;
	var $themes;
	var $lastalbumsort = NULL;

	/**
	 * Creates an instance of a gallery
	 *
	 * @return Gallery
	 */
	function Gallery() {

		// Set our album directory
		$this->albumdir = getAlbumFolder();

		if (!is_dir($this->albumdir) || !is_readable($this->albumdir)) {
			$msg = gettext("Error: The 'albums' directory")." (" . $this->albumdir . ") ";
			if (!is_dir($this->albumdir)) {
				$msg .= gettext("cannot be found.");
			} else {
				$msg .= gettext("is not readable.");
			}
			die($msg);
		}
		getOption('nil'); // force loading of the $options
	}

	/**
	 * Returns the main albums directory
	 *
	 * @return string
	 */
	function getAlbumDir() { return $this->albumdir; }

	/**
	 * Returns the DB field corresponding to the sort type desired
	 *
	 * @param string $sorttype the desired sort
	 * @return string
	 */
	function getGallerySortKey($sorttype=null) {
		if (is_null($sorttype)) { $sorttype = getOption('gallery_sorttype'); }
		switch ($sorttype) {
			case "Title":
				return 'title';
			case "Manual":
				return 'sort_order';
			case "Filename":
				return 'folder';
			case "Date":
				return 'date';
			case "ID":
				return 'id';
		}
		return 'sort_order';
	}


	/**
	 * Get Albums will create our $albums array with a fully populated set of Album
	 * names in the correct order.
	 *
	 * Returns an array of albums (a pages worth if $page is not zero)
	 *
	 * @param int $page An option parameter that can be used to return a slice of the array.
	 * @param string $sorttype the kind of sort desired
	 * @param string $direction set to a direction to override the default option
	 *
	 * @return  array
	 */
	function getAlbums($page=0, $sorttype=null, $direction=null) {
		global $_zp_gallery_albums_per_page;

		// Have the albums been loaded yet?
		if (is_null($this->albums) || $sorttype.$direction !== $this->lastalbumsort) {

			$albumnames = $this->loadAlbumNames();
			$key = $this->getGallerySortKey($sorttype);
			if (is_null($direction)) {
				if (getOption('gallery_sortdirection')) { $key .= ' DESC'; }
			} else {
				$key .= ' '.$direction;
			}
			$albums = sortAlbumArray(NULL, $albumnames, $key);

			// Store the values
			$this->albums = $albums;
			$this->lastalbumsort = $sorttype.$direction;
		}

		if ($page == 0) {
			return $this->albums;
		} else {
			return array_slice($this->albums, $_zp_gallery_albums_per_page*($page-1), $_zp_gallery_albums_per_page);
		}
	}

	/**
	 * Load all of the albums names that are found in the Albums directory on disk.
	 * Returns an array containing this list.
	 *
	 * @return array
	 */
	function loadAlbumNames() {
		$albumdir = $this->getAlbumDir();
		if (!is_dir($albumdir) || !is_readable($albumdir)) {
			$msg = gettext("Error: The 'albums' directory")." (" . $this->albumdir . ") ";
			if (!is_dir($albumdir)) {
				$msg .= gettext("cannot be found.");
			} else {
				$msg .= gettext("is not readable.");
			}
			die($msg);
		}

		$dir = opendir($albumdir);
		$albums = array();

		while ($dirname = readdir($dir)) {
			if ((is_dir($albumdir.$dirname) && (substr($dirname, 0, 1) != '.')) ||
			hasDyanmicAlbumSuffix($dirname)) {
				$albums[] = $dirname;
			}
		}
		closedir($dir);
		return $albums;
	}


	/**
	 * Returns the a specific album in the array indicated by index.
	 * Takes care of bounds checking, no need to check input.
	 *
	 * @param int $index the index of the album sought
	 * @return Album
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
	 * @param bool $db whether or not to use the database (includes ALL detected albums) or the directories
	 * @param bool $publishedOnly set to true to exclude unpublished albums
	 * @return int
	 */
	function getNumAlbums($db=false, $publishedOnly=false) {
		$count = -1;
		if (!$db) {
			$this->getAlbums();
			$count = count($this->albums);
		} else {
			$sql = "SELECT count(*) FROM " . prefix('albums');
			if ($publishedOnly) {
				$sql .= ' WHERE `show`=1';
			}
			$result = query($sql);
			$count = mysql_result($result, 0);
		}
		return $count;
	}


	/**
	 * Populates the theme array and returns it. The theme array contains information about
	 * all the currently available themes.
	 * @return array
	 */
	function getThemes() {
		if (empty($this->themes)) {
			$themedir = SERVERPATH . "/themes";
			$themes = array();
			if ($dp = @opendir($themedir)) {
				while (false !== ($dir = readdir($dp))) {
					if (substr($dir, 0, 1) != "." && is_dir("$themedir/$dir")) {
						$themefile = $themedir . "/$dir/theme_description.php";
						if (file_exists($themefile)) {
							$theme_description = array();
							require($themefile);
							$themes[$dir] = sanitize($theme_description, 1);
						} else if (file_exists($themedir . "/$dir/theme.txt")) {
							$themes[$dir] = parseThemeDef($themedir . "/$dir/theme.txt");
						}
					}
				}
				ksort($themes);
			}
			$this->themes = $themes;
		}
		return $this->themes;
	}


	/**
	 * Returns the foldername of the current theme.
	 * if no theme is set, returns "default".
	 * @return string
	 */
	function getCurrentTheme() {
		if (empty($this->theme)) {
			$theme = getOption('current_theme');
			if (empty($theme)) {
				$theme = "default";
			}
			$this->theme = $theme;
		}
		return $this->theme;
	}


	/**
	 * Sets the current theme
	 * @param string the name of the current theme
	 */
	function setCurrentTheme($theme) {
		setOption('current_theme', $theme);
	}


	/**
	 * Returns the number of images from a database SELECT count(*)
	 * Ideally one should call garbageCollect() before to make sure the database is current.
	 * @parm bool $publishedOnly set to true to count only published images.
	 * @return int
	 */
	function getNumImages($publishedOnly=false) {
		if ($publishedOnly) {
			$rows = query_full_array("SELECT `id` FROM " . prefix('albums')." WHERE `show`=0");
			if (is_array($rows)) {
				$exclude = '';
				foreach ($rows as $row)	{
					if (!empty($row['id'])) {
						$exclude .= " `albumid`!=".$row['id'].' AND ';
					}
				}
				$exclude = substr($exclude, 0, strlen($exclude)-5);
			} else {
				$exclude = '';
			}
			if ($publishedOnly) {
				if (empty($exclude)) {
					$exclude = '`show`=1';
				} else {
					$exclude = ' `show`=1 AND '.$exclude;
				}
			}
			if (!empty($exclude)) {
				$exclude = ' WHERE '.$exclude;
			}
		} else {
			$exclude = '';
		}
		$result = query_single_row("SELECT count(*) as `image_count` FROM ".prefix('images').$exclude);
		return $result['image_count'];
	}


	/**
	 * Returns the count of comments
	 *
	 * @param bool $moderated set true if you want to see moderated comments
	 * @return array
	 */
	function getNumComments($moderated=false) {
		$sql = "SELECT count(*) FROM ".prefix('comments');
		if (!$moderated) {
			$sql .= " WHERE `inmoderation`=0";
		}
		$result = query_single_row($sql);
		return array_shift($result);
	}

	/** For every album in the gallery, look for its file. Delete from the database
	 * if the file does not exist. Do the same for images. Clean up comments that have
	 * been left orphaned.
	 *
	 * Returns true if the operation was interrupted because it was taking too long
	 *
	 * @param bool $cascade garbage collect every image and album in the gallery.
	 * @param bool $complete garbage collect every image and album in the *database* - completely cleans the database.
	 * @return bool
	 */
	function garbageCollect($cascade=true, $complete=false) {
		// Check for the existence of top-level albums (subalbums handled recursively).
		$result = query("SELECT * FROM " . prefix('albums'));
		$dead = array();
		$live = array(''); // purge the root album if it exists
		$deadalbumthemes = array();
		// Load the albums from disk
		$albumfolder = getAlbumFolder();
		while($row = mysql_fetch_assoc($result)) {
			if (!file_exists($albumfolder.$row['folder']) || in_array($row['folder'], $live)) {
				$dead[] = $row['id'];
				if ($row['album_theme'] !== '') {  // orphaned album theme options table
					$deadalbumthemes[$row['id']] = $row['folder'];
				}
			} else {
				$live[] = $row['folder'];
			}
		}

		if (count($dead) > 0) { /* delete the dead albums from the DB */
			$first = array_pop($dead);
			$sql1 = "DELETE FROM " . prefix('albums') . " WHERE `id`='$first'";
			$sql2 = "DELETE FROM " . prefix('images') . " WHERE `albumid`='$first'";
			$sql3 = "DELETE FROM " . prefix('comments') . " WHERE `type`='albums' AND `ownerid`='$first'";
			$sql4 = "DELETE FROM " . prefix('obj_to_tag'). " WHERE `type`='albums' AND `objectid`='$first'";
			foreach ($dead as $albumid) {
				$sql1 .= " OR `id` = '$albumid'";
				$sql2 .= " OR `albumid` = '$albumid'";
				$sql3 .= " OR `ownerid` = '$albumid'";
				$sql4 .= " OR `objectid` = '$albumid'";
			}
			$n = query($sql1);
			if (!$complete && $n > 0 && $cascade) {
				query($sql2);
				query($sql3);
				query($sql4);
			}
		}
		if (count($deadalbumthemes) > 0) { // delete the album theme options tables for dead albums
			foreach ($deadalbumthemes as $id=>$deadtable) {
				if (ALBUM_OPTIONS_TABLE) {
					$sql = 'DELETE FROM '.prefix('options').' WHERE `ownerid`='.$id;
				} else {
					$tbl_options = prefix(getOptionTableName($deadtable));
					$sql = "DROP TABLE $tbl_options";
				}
				query($sql, true);

			}
		}

		if ($complete) {

			/* refresh 'metadata' of dynamic albums */
			$albumfolder = getAlbumFolder();
			$albumids = query_full_array("SELECT `id`, `mtime`, `folder` FROM " . prefix('albums') . " WHERE `dynamic`='1'");
			foreach ($albumids as $album) {
				if (($mtime=filemtime($albumfolder.$album['folder'])) > $album['mtime']) {  // refresh
					$data = file_get_contents($albumfolder.$album['folder']);
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
						}
						if (strpos($data1, 'FIELDS=') !== false) {

							$fields = "&searchfields=".trim(substr($data1, 7));
						}
					}
					if (!empty($words)) {
						if (empty($fields)) {
							$fields = '&searchfields=4';
						}
					}
					$sql = "UPDATE ".prefix('albums')."SET `search_params`=\"$words.$fields\", `thumb`=\"$thumb\", `mtime`=\"$mtime\" WHERE `id`=\"".$album['id']."\"";
					query($sql);
				}
			}


			/* Delete all image entries that don't belong to an album at all. */

			$albumids = query_full_array("SELECT `id` FROM " . prefix('albums'));                  /* all the album IDs */
			$idsofalbums = array();
			foreach($albumids as $row) { $idsofalbums[] = $row['id']; }
			$imageAlbums = query_full_array("SELECT DISTINCT `albumid` FROM " . prefix('images')); /* albumids of all the images */
			$albumidsofimages = array();
			foreach($imageAlbums as $row) { $albumidsofimages[] = $row['albumid']; }
			$orphans = array_diff($albumidsofimages, $idsofalbums);                                /* albumids of images with no album */

			if (count($orphans) > 0 ) { /* delete dead images from the DB */
				$firstrow = array_pop($orphans);
				$sql = "DELETE FROM ".prefix('images')." WHERE `albumid`='" . $firstrow . "'";
				foreach($orphans as $id) {
					$sql .= " OR `albumid`='" . $id . "'";
				}
				query($sql);

				// Then go into existing albums recursively to clean them... very invasive.
				foreach ($this->getAlbums(0) as $folder) {
					$album = new Album($this, $folder);
					if (!$album->isDynamic()) {
						if(is_null($album->getDateTime())) {  // see if we can get one from an image
							$image = $album->getImage(0);
							if(!($image === false)) {
								$album->setDateTime($image->getDateTime());
							}
						}
						$album->garbageCollect(true);
						$album->preLoad();
					}
				}
			}

			/* Look for image records where the file no longer exists. While at it, check for images with IPTC data to update the DB */

			$deadman = strtotime('+ 10 sec');  // protect against too much processing.

			$sql = 'SELECT `id`, `albumid`, `filename`, `desc`, `title`, `date`, `mtime`';
			if (!useTagTable()) {
				$sql .= ', `tags`';
			}
			$sql .= ' FROM ' . prefix('images');
			$images = query_full_array($sql);
			foreach($images as $image) {

				$sql = 'SELECT `folder` FROM ' . prefix('albums') . ' WHERE `id`="' . $image['albumid'] . '";';
				$row = query_single_row($sql);
				$imageName = getAlbumFolder() . $row['folder'] . '/' . $image['filename'];
				if (file_exists($imageName)) {

					if ($image['mtime'] != filemtime($imageName)) { // file has changed since we last saw it
						/* check metadata */
						$metadata = getImageMetadata($imageName);
						$set = '';

						/* title */
						$defaultTitle = substr($image['filename'], 0, strrpos($image['filename'], '.'));
						if (empty($defaultTitle )) {
							$defaultTitle = $image['filename'];
						}
						if ($defaultTitle == $image['title']) { /* default title */
							if (isset($metadata['title'])) {
								$set = ',`title`="' . mysql_real_escape_string($metadata['title']) . '"';
							}
						}

						/* description */
						if (!isset($row['desc'])) {
							if (isset($metadata['desc'])) {
								$set .= ', `desc`="' . mysql_real_escape_string($metadata['desc']) . '"';
							}
						}

						/* tags */
						if (isset($metadata['tags'])) {
							if (useTagTable()) {
								$tags = $metadata['tags'];
								storeTags($tags, $image['id'], 'images');
							} else {
								$s = '';
								foreach ($metadata['tags'] as $tag) {
									$s .= $tag.',';
								}
								if (!empty($s)) {
									$set .= ', `tags`="' . mysql_real_escape_string(substr($s, 0, -1)) . '"';
								}
							}
						}


						/* location, city, state, and country */
						if (isset($metadata['location'])) {
							$set .= ', `location`="' . mysql_real_escape_string($metadata['location']) . '"';
						}
						if (isset($metadata['city'])) {
							$set .= ', `city`="' . mysql_real_escape_string($metadata['city']) . '"';
						}
						if (isset($metadata['state'])) {
							$set .= ', `state`="' . mysql_real_escape_string($metadata['state']) . '"';
						}
						if (isset($metadata['country'])) {
							$set .= ', `country`="' . mysql_real_escape_string($metadata['country']) . '"';
						}
						/* credit & copyright */
						if (isset($metadata['credit'])) {
							$set .= ', `credit`="' . escape($metadata['credit']) . '"';
						}
						if (isset($metadata['copyright'])) {
							$set .= ', `copyright`="' . escape($metadata['copyright']) . '"';
						}

						/* date (for sorting) */
						$newDate = strftime('%Y-%m-%d %T', filemtime($imageName));
						if (isset($metadata['date'])) {
							if (strtotime($metadata['date']) !== false) { // flaw in exif/iptc data?
								$newDate = $metadata['date'];
							}
						}
						$set .= ', `date`="'. $newDate . '"';

						/* update DB is necessary */
						$sql = "UPDATE " . prefix('images') . " SET `EXIFValid`=0,`mtime`=" . filemtime($imageName) . $set . " WHERE `id`='" . $image['id'] ."'";
						query($sql);

						if (time() > $deadman) { return true; }    // avoide excessive processing
					}
				} else {
					$sql = 'DELETE FROM ' . prefix('images') . ' WHERE `id`="' . $image['id'] . '";';
					$result = query($sql);
					$sql = 'DELETE FROM ' . prefix('comments') . ' WHERE `type`="images" AND `ownerid` ="' . $image['id'] . '";';
					$result = query($sql);
				}
			}

			/* clean the comments table */
			/* do the images */
			$imageids = query_full_array('SELECT `id` FROM ' . prefix('images'));      /* all the image IDs */
			$idsofimages = array();
			foreach($imageids as $row) { $idsofimages[] = $row['id']; }
			$commentImages = query_full_array("SELECT DISTINCT `ownerid` FROM " .
			prefix('comments') . 'WHERE `type`="images"');                         /* imageids of all the comments */
			$imageidsofcomments = array();
			foreach($commentImages as $row) { $imageidsofcomments [] = $row['ownerid']; }
			$orphans = array_diff($imageidsofcomments , $idsofimages );                 /* image ids of comments with no image */

			if (count($orphans) > 0 ) { /* delete dead comments from the DB */
				$firstrow = array_pop($orphans);
				$sql = "DELETE FROM " . prefix('comments') . " WHERE `type`='images' AND `ownerid`='" . $firstrow . "'";
				foreach($orphans as $id) {
					$sql .= " OR `ownerid`='" . $id . "'";
				}
				query($sql);
			}

			/* do the same for album comments */
			$albumids = query_full_array('SELECT `id` FROM ' . prefix('albums'));      /* all the album IDs */
			$idsofalbums = array();
			foreach($albumids as $row) { $idsofalbums[] = $row['id']; }
			$commentAlbums = query_full_array("SELECT DISTINCT `ownerid` FROM " .
			prefix('comments') . 'WHERE `type`="albums"');                         /* album ids of all the comments */
			$albumidsofcomments = array();
			foreach($commentAlbums as $row) { $albumidsofcomments [] = $row['ownerid']; }
			$orphans = array_diff($albumidsofcomments , $idsofalbums );                 /* album ids of comments with no album */
			if (count($orphans) > 0 ) { /* delete dead comments from the DB */
				$firstrow = array_pop($orphans);
				$sql = "DELETE FROM " . prefix('comments') . "WHERE `type`='albums' AND `ownerid`='" . $firstrow . "'";
				foreach($orphans as $id) {
					$sql .= " OR `ownerid`='" . $id . "'";
				}
				query($sql);
			}

			/* clean the tags table */
			/* do the images */
			$tagImages = query_full_array("SELECT DISTINCT `objectid` FROM ".prefix('obj_to_tag').'WHERE `type`="images"');                         /* imageids of all the comments */
			$imageidsoftags = array();
			foreach($tagImages as $row) { $imageidsoftags [] = $row['objectid']; }
			$orphans = array_diff($imageidsoftags , $idsofimages );                 /* image ids of comments with no image */
			if (count($orphans) > 0 ) { /* delete dead tags from the DB */
				$firstrow = array_pop($orphans);
				$sql = "DELETE FROM " . prefix('obj_to_tag') . " WHERE `type`='images' AND `objectid`='" . $firstrow . "'";
				foreach($orphans as $id) {
					$sql .= " OR `objectid`='" . $id . "'";
				}
				query($sql);
			}

			/* do the same for album tags */
			$tagAlbums = query_full_array("SELECT DISTINCT `objectid` FROM ".prefix('obj_to_tag').'WHERE `type`="albums"');                         /* album ids of all the comments */
			$albumidsoftags = array();
			foreach($tagAlbums as $row) { $albumidsoftags [] = $row['objectid']; }
			$orphans = array_diff($albumidsoftags , $idsofalbums );                 /* album ids of comments with no album */
			if (count($orphans) > 0 ) { /* delete dead tags from the DB */
				$firstrow = array_pop($orphans);
				$sql = "DELETE FROM " . prefix('obj_to_tag') . "WHERE `type`='albums' AND `objectid`='" . $firstrow . "'";
				foreach($orphans as $id) {
					$sql .= " OR `objectid`='" . $id . "'";
				}
				query($sql);
			}
		}
		return false;
	}

	/**
	 * Returns the size in bytes of the cache folder. WARNING: VERY SLOW.
	 * @return int
	 */
	function sizeOfCache() {
		$cachefolder = SERVERCACHE;
		if (is_dir($cachefolder)) {
			return dirsize($cachefolder);
		} else {
			return 0;
		}
	}


	/**
	 * Returns the size in bytes of the albums folder. WARNING: VERY SLOW.
	 * @return int
	 */
	function sizeOfImages() {
		$imagefolder = substr(getAlbumFolder(), 0, -1);
		if (is_dir($imagefolder)) {
			return dirsize($imagefolder);
		} else {
			return 0;
		}
	}


	/**
	 * Cleans out the cache folder
	 *
	 * @param string $cachefolder the sub-folder to clean
	 */
	function clearCache($cachefolder=NULL) {
		if (is_null($cachefolder)) {
			$cachefolder = SERVERCACHE;
		}
		if (is_dir($cachefolder)) {
			$handle = opendir($cachefolder);
			while (false !== ($filename = readdir($handle))) {
				$fullname = $cachefolder . '/' . $filename;
				if (is_dir($fullname) && !(substr($filename, 0, 1) == '.')) {
					if (($filename != '.') && ($filename != '..')) {
						$this->clearCache($fullname);
						rmdir($fullname);
					}
				} else {
					if (file_exists($fullname) && !(substr($filename, 0, 1) == '.')) {
						unlink($fullname);
					}
				}

			}
			closedir($handle);
		}
	}

}
?>
