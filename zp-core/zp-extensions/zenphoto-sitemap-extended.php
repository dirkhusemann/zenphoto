<?php
/**
 * Generates a sitemap.org compatible XML file, for use with Google and other search engines. It supports albums and images as well as optionally Zenpage pages, news articles and news categories.
 *
 * Renders the sitemap if a gallery page is called with "<zenphoto>/sitemap.php" in the URL. The sitemap is cached as a xml file within the root "cache_html/sitemap" folder.
 *
 * @author Malte Müller (acrylian) based on the plugin by Jeppe Toustrup (Tenzer) http://github.com/Tenzer/zenphoto-sitemap
 * @package plugins
 */

$plugin_is_filter = 5;
$plugin_description = 'Generates a sitemaps.org compatible XML file, for use with Google and other search engines. It supports albums and images as well as optionally Zenpage pages, news articles and news categories. Renders the sitemap if a gallery page is called with "<zenphoto>/sitemap.php" in the URL.';
$plugin_author = 'Malte Müller (acrylian) based on the <a href="http://github.com/Tenzer/zenphoto-sitemap">plugin</a> by Jeppe Toustrup (Tenzer)';
$plugin_version = '1.3.0';
$plugin_URL = 'http://www.zenphoto.org/documentation/plugins/_'.PLUGIN_FOLDER.'---zenphoto-sitemap-extended.php.html';
$option_interface = new sitemap();

zp_register_filter('admin_utilities_buttons', 'sitemap_cache_purgebutton');

$sitemapfolder = SERVERPATH.'/cache_html/sitemap';
if (!file_exists($sitemapfolder)) {
	if (!mkdir($sitemapfolder, CHMOD_VALUE)) {
		die(gettext("sitemap cache folder could not be created. Please try to create it manually via FTP with chmod 0777."));
	}
}
if (isset($_GET['action']) && $_GET['action']=='clear_sitemap_cache') { 
	clearSitemapCache();
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=external&msg='.gettext('sitemap cache cleared.'));
	exit();
} 

/**
 * Plugin option handling class
 *
 */
class sitemap {

	var $startmtime;
	var $disable = false; // manual disable caching a page

	function sitemap() {
		setOptionDefault('sitemap_cache_expire', 86400);
	}

	function getOptionsSupported() {
		return array(	gettext('Sitemap cache expire') => array('key' => 'sitemap_cache_expire', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("When the cache should expire in seconds. Default is 86400 seconds (1 day  = 24 hrs * 60 min * 60 sec).The cache can also be cleared on the admin overview page manually."))
		);
	}

	function handleOption($option, $currentValue) {
	}
}

/**
 * creates the Utilities button to purge the static sitemap cache
 * @param array $buttons
 * @return array
 */
function sitemap_cache_purgebutton($buttons) {
	$buttons[] = array(
								'button_text'=>gettext('Purge sitemap cache'),
								'formname'=>'clearcache_button',
								'action'=>PLUGIN_FOLDER.'/zenphoto-sitemap.php?action=clear_sitemap_cache',
								'icon'=>'images/edit-delete.png',
								'title'=>gettext('Clear the static sitemap cache. It will be recached if requested.'),
								'alt'=>'',
								'hidden'=> '<input type="hidden" name="action" value="clear_sitemap_cache">',
								'rights'=> ADMIN_RIGHTS
	);
	return $buttons;
}

/**
 * Simple helper function which simply outputs a string and ends it of with a new-line.
 * @param  $string String
 * @return void
 */
function echonl($string) {
	echo($string . "\n");
}

/**
 * A simple wrapper function for urlencode, which allows us to use it with array_walk().
 * @param  $value The array value from array_walk()
 * @param  $key The array key from array_walk() - Not used
 * @return void
 */
function urlencodeWrapper(&$value, $key) {
	$value = urlencode($value);
}

/**
 * Starts static sitemap caching
 *
 */
function startSitemapCache($caching=true) {
	if($caching) {
		$cachefilepath = SERVERPATH."/cache_html/sitemap/sitemap.xml";
		if(file_exists($cachefilepath) AND time()-filemtime($cachefilepath) < getOption('sitemap_cache_expire')) {
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
function endSitemapCache($caching=true) {
	if($caching) {
		$cachefilepath = SERVERPATH."/cache_html/sitemap/sitemap.xml";
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
	 * Cleans out the cache folder
	 *
	 */
	function clearSitemapCache() {
		$cachefolder = SERVERPATH."/cache_html/sitemap/";
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