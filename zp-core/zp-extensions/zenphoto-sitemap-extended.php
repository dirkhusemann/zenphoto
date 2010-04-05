<?php
/**
 * Generates a sitemap.org compatible XML file, for use with Google and other search engines. It supports albums and images as well as optionally Zenpage pages, news articles and news categories.
 * <?xml version="1.0" encoding="UTF-8"?>
 *<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
 *  <url>
 *    <loc>http://www.example.com/</loc>
 *    <lastmod>2005-01-01</lastmod> // except for index, Zenpage news index and news categories as they don't have a date attached (optional anyway)
 *    <changefreq>monthly</changefreq>
 * </url>
 *</urlset>
 *
 * Renders the sitemap if a gallery page is called with "<zenphoto>/sitemap.php" in the URL. The sitemap is cached as a xml file within the root "cache_html/sitemap" folder.
 *
 * NOTE: The index links may not match if using the options for "Zenpage news on index" or a "custom home page" that some themes provide! Also it does not "know" about "custom pages" outside Zenpage or any special custom theme setup!
 *
 * @author Malte Müller (acrylian) based on the plugin by Jeppe Toustrup (Tenzer) http://github.com/Tenzer/zenphoto-sitemap
 * @package plugins
 */

$plugin_is_filter = 5;
$plugin_description = 'Generates a sitemaps.org compatible XML file, for use with Google and other search engines. It supports albums and images as well as optionally Zenpage pages, news articles and news categories. Renders the sitemap if a gallery page is called with "<zenphoto>/sitemap.php" in the URL. NOTE: The index links may not match if using the Zenpage option "news on index" that some themes provide! Also it does not "know" about "custom pages" outside Zenpage or any special custom theme setup!!';
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
		setOptionDefault('sitemap_changefreq_index', 'daily');
		setOptionDefault('sitemap_changefreq_albums', 'daily');
		setOptionDefault('sitemap_changefreq_images', 'daily');
		setOptionDefault('sitemap_changefreq_pages', 'weekly');
		setOptionDefault('sitemap_changefreq_newsindex','daily');
		setOptionDefault('sitemap_changefreq_news', 'daily');
		setOptionDefault('sitemap_changefreq_newscats', 'weekly');
		setOptionDefault('sitemap_lastmod_albums', 'mtime');
		setOptionDefault('sitemap_lastmod_images', 'mtime');
	}

	function getOptionsSupported() {
		return array(	gettext('Sitemap cache expire') => array('key' => 'sitemap_cache_expire', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("When the cache should expire in seconds. Default is 86400 seconds (1 day  = 24 hrs * 60 min * 60 sec).The cache can also be cleared on the admin overview page manually.")),
		gettext('Change frequence - Zenphoto index') => array('key' => 'sitemap_changefreq_index', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequence - albums') => array('key' => 'sitemap_changefreq_albums', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequence - images') => array('key' => 'sitemap_changefreq_images', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequence - Zenpage pages') => array('key' => 'sitemap_changefreq_pages', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequence - Zenpage news index') => array('key' => 'sitemap_changefreq_newsindex', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequence: Zenpage news articles') => array('key' => 'sitemap_changefreq_news', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequence - Zenpage news categories') => array('key' => 'sitemap_changefreq_newscats', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
	gettext('Last modification date - albums') => array('key' => 'sitemap_lastmod_albums', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("date")=>"date",
																					gettext("mtime")=>"mtime"),
										'desc' => ''),
	gettext('Last modification date - images') => array('key' => 'sitemap_lastmod_images', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("date")=>"date",
																					gettext("mtime")=>"mtime"),
										'desc' => ''),
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
	if(zp_loggedin(ADMIN_RIGHTS)) {
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
}

/**
 * Simple helper function which simply outputs a string and ends it of with a new-line.
 * @param  string $string text string
 * @return string
 */
function sitemap_echonl($string) {
	echo($string . "\n");
}
/**
 * Checks the changefreq value if entered manually and makes sure it is only one of the supported regarding sitemap.org
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function sitemap_getChangefreq($changefreq='') {
	$changefreq = sanitize($changefreq);
	switch($changefreq) {
		case 'always':
		case 'hourly':
		case 'daily':
		case 'weekly':
		case 'monthly':
		case 'yearly':
		case 'never':
			$changefreq = $changefreq;
			break;
		default:
			$changefreq = 'daily';
			break;
	}
	return $changefreq;
}
/**
 * Gets the dateformat for images and albums only.
 * @param object $obj image or album object
 * @param  string $option "date" or "mtime". If "mtime" is discovered to be not set, the date values is taken instead so we don't get 1970-01-10 dates
 * @return string
 */
function sitemap_getDateformat($obj,$option) {
	$date = '';
	switch($option) {
		case 'date':
		default:
			$date = substr($obj->getDatetime(),0,10);
			break;
		case 'mtime':
			$timestamp = $obj->get('mtime');
			if($timestamp == 0) {
				$date = substr($obj->getDatetime(),0,10);
			} else {
				$date = strftime('%Y-%m-%d',$timestamp);
			}
			break;
	}
	return $date;
}
/**
 * Prints the links to the index of a Zenphoto gallery incl. pagination
 *@param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapIndexLinks($changefreq='') {
	global $_zp_gallery;
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_index');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	if(galleryAlbumsPerPage() != 0) {
		$toplevelpages = ceil($_zp_gallery->getNumAlbums() / galleryAlbumsPerPage());
	} else {
		$toplevelpages = false;
	}
	// print further index pages if avaiable
	if($toplevelpages) {
		for($x = 2;$x <= $toplevelpages; $x++) {
			$url = FULLWEBPATH.'/'.rewrite_path('page/'.$x,'index.php?page='.$x,false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
		}
	}
}

/**
 * Prints links to all albums incl. pagination and their images
 * @param  string $albumchangefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @param  string $imagechangefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @param  string $albumlastmod "date or "mtime"
 * @param  string $imagelastmod "date or "mtime"
 * @return string
 */
function printSitemapAlbumsAndImages($albumchangefreq='',$imagechangefreq='',$albumlastmod='',$imagelastmod='') {
	global $_zp_gallery, $_zp_current_album;
	if(empty($albumchangefreq)) {
		$albumchangefreq = getOption('sitemap_changefreq_albums');
	} else {
		$albumchangefreq = sitemap_getChangefreq($albumchangefreq);
	}
	if(empty($imagechangefreq)) {
		$imagechangefreq = getOption('sitemap_changefreq_images');
	} else {
		$imagechangefreq = sitemap_getChangefreq($imagechangefreq);
	}
	if(empty($albumlastmod)) {
		$albumlastmod = getOption('sitemap_lastmod_albums');
	} else {
		$albumlastmod = sanitize($albumlastmod);
	}
	if(empty($imagelastmod)) {
		$imagelastmod = getOption('sitemap_lastmod_images');
	} else {
		$imagelastmod = sanitize($imagelastmod);
	}
	$passwordcheck = '';
	$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
	foreach($albumscheck as $albumcheck) {
		if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
		$albumpasswordcheck= " AND id != ".$albumcheck['id'];
		$passwordcheck = $passwordcheck.$albumpasswordcheck;
		}
	}
	$albumWhere = "WHERE `dynamic`=0 AND `show`=1".$passwordcheck;
	// Find public albums
	$albums = query_full_array('SELECT `folder`,`date` FROM ' . prefix('albums') . $albumWhere);
	if($albums) {
		foreach($albums as $album) {
			$albumobj = new Album($_zp_gallery,$album['folder']);
			//getting the album pages
			set_context(ZP_ALBUM);
			makeAlbumCurrent($albumobj);
			$pageCount = getTotalPages();
			$date = sitemap_getDateformat($albumobj,$albumlastmod);
			$url = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name),'?album='.pathurlencode($albumobj->name),false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$albumchangefreq."</changefreq>\n\t</url>");
			// print album pages if avaiable
			if($pageCount > 1) {
				for($x = 2;$x <= $pageCount; $x++) {
					$url = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name).'/page/'.$x,'?album='.pathurlencode($albumobj->name).'&amp;page='.$x,false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$albumchangefreq."</changefreq>\n\t</url>");
				}
			}
			$images = $albumobj->getImages();
			if($images) {
				foreach($images as $image) {
					$imageob = newImage($albumobj,$image);
					$date = sitemap_getDateformat($imageob,$imagelastmod);
					$path = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name).'/'.urlencode($imageob->filename),'?album='.pathurlencode($albumobj->name).'&amp;image='.urlencode($imageob->filename),false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$path."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$imagechangefreq."</changefreq>\n\t</url>");
				}
			}
		}
	}
	restore_context();
}
/**
 * Prints links to all Zenpage pages
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpagePages($changefreq='') {
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_pages');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	$pages = getPages(true);
	if($pages) {
		foreach($pages as $page) {
			$pageobj = new ZenpagePage($page['titlelink']);
			$date = substr($pageobj->getDatetime(),0,10);
			$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_PAGES.'/'.urlencode($page['titlelink']),'?p='.ZENPAGE_PAGES.'&amp;title='.urlencode($page['titlelink']),false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
		}
	}
}
/**
 * Prints links to the main Zenpage news index incl. pagination
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpageNewsIndex($changefreq='') {
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_newsindex');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/1','?p='.ZENPAGE_NEWS.'&amp;page=1',false);
	sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
	// getting pages for the main news loop
	$newspages = ceil(getTotalArticles() / getOption("zenpage_articles_per_page"));
	if($newspages > 1) {
		for($x = 2;$x <= $newspages; $x++) {
			$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/'.$x,'?p='.ZENPAGE_NEWS.'&amp;page='.$x,false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".date('Y-m-d')."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
		}
	}
}
/**
 * Prints to the Zenpage news articles
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpageNewsArticles($changefreq='') {
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_news');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	$articles = getNewsArticles('','','published',true,"date","desc"); //query_full_array("SELECT titlelink, `date` FROM ".prefix('zenpage_news'));// normally getNewsArticles() should be user but has currently a bug in 1.2.9 regarding getting all articles...
	if($articles) {
		foreach($articles as $article) {
			$articleobj = new ZenpageNews($article['titlelink']);
			$date = substr($articleobj->getDatetime(),0,10);
			$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/'.urlencode($articleobj->getTitlelink()),'?p='.ZENPAGE_NEWS.'&amp;title=' . urlencode($articleobj->getTitlelink()),false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
		}
	}
}

/**
 * Prints links to Zenpage news categories incl. pagination
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpageNewsCategories($changefreq='') {
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_newscats');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	$newscats = getAllCategories();
	if($newscats) {
		// Add the correct URLs to the URL list
		foreach($newscats as $newscat) {
			$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']).'/1','?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']).'&amp;page=1',false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
			// getting pages for the categories
			$articlecount = countArticles($newscat['cat_link']);
			$catpages = ceil($articlecount / getOption("zenpage_articles_per_page"));
			if($catpages > 1) {
				for($x = 2;$x <= $catpages ; $x++) {
					$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']).'/'.$x,'?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']).'&amp;page='.$x,false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t</url>");
				}
			}
		}
	}
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