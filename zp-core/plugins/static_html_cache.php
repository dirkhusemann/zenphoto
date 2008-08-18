<?php
/**
 * Static HTML Cache
 *
 * Caches all Zenphoto pages (incl. Zenpage support) except search.php (search results, date archive) and the custom error page 404.php
 *
 * @author Malte Müller (acrylian)
 * @version 1.0.0
 * @package plugins
 */
require_once(dirname(dirname(__FILE__)).'/functions.php');

$plugin_description = gettext("Adds static html cache functionality to Zenphoto v1.2 or higher. Caches all Zenphoto pages (incl. Zenpage support) except search.php (search results, date archive) and the custom error page 404.php. This plugin creates the folder <em>cache_html</em> and it's subfolders <em>index, images, albums</em> and <em>pages</em> in Zenphoto's root folder.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---static_html_cache.php.html";
$option_interface = new staticCache();
$_zp_HTML_cache = $option_interface; // register as the HTML cache handler

// insure that we have the folders available for the cache
if (!defined('STATIC_CACHE_FOLDER')) {
	define("STATIC_CACHE_FOLDER","cache_html");
}

$cache_path = SERVERPATH.'/'.STATIC_CACHE_FOLDER."/";
if (!file_exists($cache_path)) {
	if (!mkdir($cache_path, CHMOD_VALUE)) {
		die(gettext("Static HTML Cache folder could not be created. Please try to create it manually via FTP with chmod 0777."));
	}
}
$cachesubfolders = array("index", "albums","images","pages");
foreach($cachesubfolders as $cachesubfolder) {
	$folder = $cache_path.$cachesubfolder.'/';
	if (!file_exists($folder)) {
		if(!mkdir($folder, CHMOD_VALUE)) {
			die(gettext("Static HTML Cache folder could not be created. Please try to create it manually via FTP with chmod 0777."));
		}
	}
}

/**
 * Plugin option handling class
 *
 */
class staticCache {

	var $startmtime;
	
	function staticCache() {
		setOptionDefault('clear_static_cache', '');
		setOptionDefault('static_cache_expire', 86400);
	}

	function getOptionsSupported() {
		return array(	gettext('Clear static html cache') => array('key' => 'clear_static_cache', 'type' => 2,
										'desc' => gettext("Clears the static html cache.")),
		gettext('Static html cache expire') => array('key' => 'static_cache_expire', 'type' => 0,
										'desc' => gettext("When the cache should expire in seconds. Default is 86400 seconds (1 day  = 24 hrs * 60 min * 60 sec).")),
		);
	}

	function handleOption($option, $currentValue) {
		if($option=="clear_static_cache") {
			echo "<div class='buttons'>";
			echo "<a href='plugins/static_html_cache.php?clearcache&height=100&width=250' class='thickbox' title='Clear cache'><img src='images/burst.png' alt='' />".gettext("Clear cache")."</a>";
			echo "</div>";
		}
	}

	/**
	 * Starts the caching: Gets either an already cached file if existing or starts the output buffering.
	 *
	 * Place this function on zenphoto's root index.php file in line 75 right after the plugin loading loop
	 *
	 */
	function startHTMLCache() {
		$this->startmtime = microtime(true);
		$cachefilepath = STATIC_CACHE_FOLDER."/".$this->createCacheFilepath();
		if(file_exists($cachefilepath) AND !isset($_POST['comment']) && time()-filemtime($cachefilepath) < getOption("static_cache_expire")) { // don't use cache if comment is posted
			if(function_exists("file_get_contents")) {
				echo file_get_contents($cachefilepath); // PHP >= 4.3
			} else {
				$filearray = file($cachefilepath); // PHP < 4.3
				foreach($filearray as $array) {
					echo $array;
				}
			}
			$end = microtime(true); $final = $end - $this->startmtime; $final = round($final,4);
			echo "\n<!-- Cached content served by static_html_cache in ".$final."s -->";
			exit();
		} else {
			ob_start();
		}
	}

	/**
	 * Ends the caching: Ends the output buffering  and writes the html cache file from the buffer
	 *
	 * Place this function on zenphoto's root index.php file in the absolute last line
	 *
	 */
	function endHTMLCache() {
		$cachefilepath = STATIC_CACHE_FOLDER."/".$this->createCacheFilepath();
		if(!empty($cachefilepath)) {
			// Display speed information.
			$end = microtime(true); $final = $end - $this->startmtime; $final = round($final, 4);
			echo "\n<!-- Content generated dynamically in ".$final."s and cached. -->";
			// End
			$pagecontent = ob_get_clean();
			$fh = fopen($cachefilepath,"w");
			fputs($fh, $pagecontent);
			fclose($fh);
			echo $pagecontent;
		}
	}

	/**
	 * Creates the path and filename of the page to be cached.
	 *
	 * @return string
	 */
	function createCacheFilepath() {
		global $_zp_current_image, $_zp_current_album, $_zp_gallery_page;

		// just make sure these are really empty
		$cachefilepath = "";
		$album = "";
		$image = "";
		$searchfields = "";
		$words = "";
		$date = "";
		$title = ""; // zenpage support
		$category = ""; // zenpage support

		// get page number
		if(isset($_GET['page'])) {
			$page = "_".sanitize($_GET['page']);
		} else {
			$page = "_1";
		}
		if(isset($_POST['dynamic-locale'])) {
			$locale = "_".sanitize($_POST['dynamic-locale']);
		} else {
			$locale = "_".sanitize(getOption("locale"));
		}

		// index.php
		if(!isset($_GET['album']) AND !isset($_GET['image']) AND !isset($_GET['p'])) {
			$cachesubfolder = "index";
			$cachefilepath = $cachesubfolder."/index".$page.$locale.".html";
		}

		// album.php/image.php
		if(isset($_GET['album']) || isset($_GET['image'])) {
			$cachesubfolder = "albums";
			$album = $_zp_current_album->name;
			$album = str_replace("/","_",$album);
			if(isset($_zp_current_image)) {
				$cachesubfolder = "images";
				$image = "-".$_zp_current_image->name;
				$page = "";
			}
			$cachefilepath = $cachesubfolder."/".$album.$image.$page.$locale.".html";
		}

		// custom pages except error page and search
		if(isset($_GET['p']) AND $_GET['p'] != "404" AND $_GET['p'] != "search") {
			$cachesubfolder = "pages";
			$custompage = sanitize($_GET['p']);
			if(isset($_GET['title'])) {
				$title = "-".sanitize($_GET['title']);
			}
			if(isset($_GET['category'])) {
				$category = "-".sanitize($_GET['category']);
			}
			$cachefilepath = $cachesubfolder."/".$custompage.$category.$title.$page.$locale.".html";
		}
		return $cachefilepath;
	}

	/**
	 * Deletes a cache file
	 *
	 * @param string $cachefilepath Path to the cache file to be deleted
	 */
	function deleteStaticCacheFile($cachefilepath) {
		if(file_exists($cachefilepath)) {
			@unlink($cachefilepath);
		}
	}

	/**
	 * Cleans out the cache folder. (Adpated from the zenphoto image cache)
	 *
	 * @param string $cachefolder the sub-folder to clean
	 */
	function clearHTMLCache($folder='') {
		$cachesubfolders = array("index", "albums","images","pages");
		foreach($cachesubfolders as $cachesubfolder) {
			$cachefolder = "../../".STATIC_CACHE_FOLDER."/".$cachesubfolder;
			if (is_dir($cachefolder)) {
				$handle = opendir($cachefolder);
				while (false !== ($filename = readdir($handle))) {
					$fullname = $cachefolder . '/' . $filename;
					if (is_dir($fullname) && !(substr($filename, 0, 1) == '.')) {
						if (($filename != '.') && ($filename != '..')) {
							$this->clearHTMLCache($fullname);
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
		//clearstatcache();
	}
} // class


// creates the cache folders from the plugins page or clears the cache from the plugin options
if (isset($_GET['clearcache'])) {
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
	echo "\n<html xmlns=\"http://www.w3.org/1999/xhtml\">";
	echo "\n<head>";
	echo "\n  <title>".gettext("zenphoto administration")."</title>";
	echo "\n  <link rel=\"stylesheet\" href=\"../admin.css\" type=\"text/css\" />";
	echo "</head>";
	echo "<body>";
	$_zp_HTML_cache->clearHTMLCache();
	echo '<div style="margin-top: 20px; text-align: left;">';
	echo "<h2><img src='images/pass.png' style='position: relative; top: 3px; margin-right: 5px' />".gettext("Static HTML Cache cleared!")."</h2>";
	echo "<div class='buttons'><a href='#' onclick='self.parent.tb_remove();'>".gettext('Close')."</a></div>";
	echo '</div>';
	echo "</body>";
	echo "</html>";
	exit;
}
?>