<?php
/**
 * Functions used to generate RSS feeds.
 * @package functions
 */


/**
 * Returns the host
 *
 * @return string
 */
function getRSSHost() {
	$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');
	return $host;
}

/**
 * Returns the feed's URI
 *
 * @return string
 */
function getRSSURI() {
	$uri = htmlentities($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"], ENT_QUOTES, 'UTF-8');
	return $uri;
}


/**
 * Returns the item id from the feed URL for item specific rss
 *
 * @return string
 */
function getRSSID() {
	if(isset($_GET['id'])) {
		$id = sanitize_numeric($_GET['id']);
	} else {
		$id = "";
	}
	return $id;
}

/**
 * Returns the title from the feed's url for item specific rss
 *
 * @return string
 */
function getRSSTitle() {
	if(isset($_GET['title'])) {
		$title = " - ".sanitize($_GET['title']);
	} else {
		$title = NULL;
	}
	return $title;
}

/**
 * Returns the title from the feed's url for latest albums or a specific album rss
 *
 * @return string
 */
function getRSSAlbumTitle() {
	$rssmode = getRSSAlbumsmode();
	if(isset($_GET['albumtitle'])) {
		$albumname = " (".sanitize(urldecode($_GET['albumtitle'])).")";
	} else if ($rssmode === "albums") {
		$albumname = gettext("Latest Albums");
	} else {
		$albumname = "";
	}
	return $albumname;
}

/**
 * Returns the rss type from the feed's url
 *
 * @return string
 */
function getRSSType() {
	if(isset($_GET['type'])) {
		$type = sanitize($_GET['type']);
	} else {
		$type = "all";
	}
	return $type;
}

/**
 * Returns the locale of the feed either from the feed's url if passed directly or by standard option
 *
 * @return string
 */
function getRSSLocale() {
	if(isset($_GET['lang'])) {
		$locale = sanitize($_GET['lang']);
	} else {
		$locale = getOption('locale');
	}
	return $locale;
}

/**
 * Replaces "_" with "-" in the locale for usage in the feed's xml
 *
 * @return string
 */
function getRSSLocaleXML() {
	$locale = getRSSLocale();
	$validlocale = strtr($locale,"_","-");
	return $validlocale;
}

/**
 * Returns the albums rss mode from the feed's url
 *
 * @return string
 */
function getRSSAlbumsmode() {
	if(isset($_GET['albumsmode'])) {
		$rssmode = "albums";
	} else {
		$rssmode = "";
	}
	return $rssmode;
}


/**
 * Returns the image path, album path and modrewrite suffix
 *
* @param string $arrayfield "albumpath", "imagepath" or "modrewritesuffix"
 * @return string
 */
function getRSSImageAndAlbumPaths($arrayfield) {
	$arrayfield = sanitize($arrayfield);
	if(getOption('mod_rewrite')) {
		$albumpath = "/"; $imagepath = "/";
		$modrewritesuffix = getOption('mod_rewrite_image_suffix');
	} else  {
		$albumpath = "/index.php?album=";
		$imagepath = "&amp;image=";
		$modrewritesuffix = "";
	}
	$array = array(
	"albumpath" => $albumpath,
	"imagepath" => $imagepath,
	"modrewritesuffix" => $modrewritesuffix
	);
	return $array[$arrayfield];
}

/**
 * Returns the size of the images to be used in the feed.
 *
 * @return int
 */
function getRSSImageSize() {
	$rssmode = getRSSAlbumsmode();
	if(isset($_GET['size'])) {
		$size = sanitize_numeric($_GET['size']);
	} else {
		$size = NULL;
	}
	if(is_numeric($size) && !is_null($size) && $size < getOption('feed_imagesize')) {
		$size = $size;
	} else {
		if($rssmode == "albums") {
			$size = getOption('feed_imagesize_albums'); // uncropped image size
		} else {
			$size = getOption('feed_imagesize'); // uncropped image size
		}
	}
	return $size;
}

/**
 * Returns the albumname and TRUE or FALSE for the collection mode (album + subalbums)
 *
* @param string $arrayfield "albumfolder" or "collection"
 * @return mixed
 */
function getRSSAlbumnameAndCollection($arrayfield) {
	$arrayfield = sanitize($arrayfield);
	if(!empty($arrayfield)) {
		if(isset($_GET['albumname'])) {
			$albumfolder = sanitize_path($_GET['albumname']);
			$collection = FALSE;
		} else if(isset($_GET['folder'])) {
			$albumfolder = sanitize_path($_GET['folder']);
			$collection = TRUE;
		} else {
			$albumfolder = NULL;
			$collection = FALSE;
		}
		$array = array(
	"albumfolder" => $albumfolder, 
	"collection" => $collection
		);
		return $array[$arrayfield];
	}
}

/**
 * Returns the News Catagory title or catlink (name) or the mode (all news or category only) for the Zenpage news feed.
 *
 * @param string $arrayfield "catlink", "catttitle" or "option"
 * @return string
 */
function getRSSNewsCatOptions($arrayfield) {
	$arrayfield = sanitize($arrayfield);
	if(!empty($arrayfield)) {
		if(isset($_GET['category'])) {
			$catlink = sanitize($_GET['category']);
			$cattitle = htmlspecialchars(getCategoryTitle($catlink));
			$option = "category";
		} else {
			$catlink = "";
			$cattitle = "";
			$option = "news";
		}
		$array = array(
"catlink" => $catlink,
"cattitle" => $cattitle,
"option" => $option
		);
		return $array[$arrayfield];
	}
}

/**
 * Returns the mimetype for the standard gallery items
 *
 * @param string $ext The extension/suffix of the filename
 * @return string
 */
function getMimeType($ext) {
	switch($ext) {
		case  ".flv":
			$mimetype = "video/x-flv";
			break;
		case ".mp3":
			$mimetype = "audio/mpeg";
			break;
		case ".mp4":
			$mimetype = "video/mpeg";
			break;
		case ".3gp":
			$mimetype = "video/3gpp";
			break;
		case ".mov":
			$mimetype = "video/quicktime";
			break;
		case ".jpg":
		case ".jpeg":
			$mimetype = "image/jpeg";
			break;
		case ".gif":
			$mimetype = "image/gif";
			break;
		case ".png":
			$mimetype = "image/png";
			break;
		default:
			$mimetype = "image/jpeg";
			break;
	}
	return $mimetype;
}

/**
 * Gets the rss file name from the feed url and clears out query items and special chars
 *
 * @return string
 */
function getRSSCacheFilename() {
	$uri = getRSSURI();
	$array = explode("/",$uri);
	$filename = array_pop($array);
	$replace = array(
	"albumname="=>"_",
	"albumsmode="=>"_",
	"title=" => "_",
	"folder=" => "_",
	"type=" => "-",
	"albumtitle=" => "_",
	"category=" => "_",
	"id=" => "_",
	"lang=" => "_",
	"&amp;" => "_", 
	"&" => "_", 
	".php" => "",
	"/"=>"-",
	"?"=> ""
	);
	$filename = strtr($filename,$replace);
	$filename = preg_replace("/__/","_",$filename);
	$filename = seoFriendly($filename);
	return $filename.".xml";
}

/**
 * Starts static RSS caching
 *
 */
function startRSSCache() {
	if(getOption("feed_cache")) {
		$cachefilepath = SERVERPATH."/cache_html/rss/".getRSSCacheFilename();
		if(file_exists($cachefilepath) AND time()-filemtime($cachefilepath) < getOption("feed_cache_expire")) {
			echo file_get_contents($cachefilepath); // PHP >= 4.3
			exit();
		} else {
			if(file_exists($cachefilepath)) {
				@unlink($cachefilepath);
			}
			ob_start();
		}
	}
}

/**
 * Ends the static RSS caching.
 *
 */
function endRSSCache() {
	if(getOption("feed_cache")) {
		$cachefilepath = SERVERPATH."/cache_html/rss/".getRSSCacheFilename();
		if(!empty($cachefilepath)) {
			$pagecontent = ob_get_clean();
			$fh = fopen($cachefilepath,"w");
			fputs($fh, $pagecontent);
			fclose($fh);
			echo $pagecontent;
		}
	}
}


/**
	 * Cleans out the RSS cache folder
	 *
	 * @param string $cachefolder the sub-folder to clean
	 */
	function clearRSSCache($cachefolder=NULL) {
		if (is_null($cachefolder)) {
			$cachefolder = "../cache_html/rss/";
		}
		if (is_dir($cachefolder)) {
			$handle = opendir($cachefolder);
			while (false !== ($filename = readdir($handle))) {
				$fullname = $cachefolder . '/' . $filename;
				if (is_dir($fullname) && !(substr($filename, 0, 1) == '.')) {
					if (($filename != '.') && ($filename != '..')) {
						clearRSSCache($fullname);
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
?>