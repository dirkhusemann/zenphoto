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
 * Renders the sitemap if called via "www.yourdomain.com/zenphoto/sitemap.php". The sitemap is cached as a xml file within the root "cache_html/sitemap" folder.
 *
 * NOTE: The index links may not match if using the options for "Zenpage news on index" or a "custom home page" that some themes provide! Also it does not "know" about "custom pages" outside Zenpage or any special custom theme setup!
 *
 * IMPORTANT: A multilingual sitemap requires the seo_locale plugin and mod_rewrite.
 *
 * @author Malte Müller (acrylian) based on the plugin by Jeppe Toustrup (Tenzer) http://github.com/Tenzer/zenphoto-sitemap and on contributions by timo and Blue Dragonfly
 * @package plugins
 */

if (isset($_GET['action']) && $_GET['action']=='clear_sitemap_cache') { //button handler
	if (!defined('OFFSET_PATH')) define('OFFSET_PATH', 3);
	require_once(dirname(dirname(__FILE__)).'/folder-definitions.php');
	require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
	require_once(dirname(dirname(__FILE__)).'/admin-globals.php');
	
	admin_securityChecks(NULL, currentRelativeURL(__FILE__));
	
	clearSitemapCache();
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=external&msg='.gettext('sitemap cache cleared.'));
	exit();
}

$plugin_description = gettext('Generates a sitemaps.org compatible XML file, for use with Google and other search engines. It supports albums and images as well as optionally Zenpage pages, news articles and news categories. Renders the sitemap if called via "www.yourdomain.com/zenphoto/sitemap.php" in the URL.').'<p class="notebox">'.gettext('<strong>Note:</strong> The index links may not match if using the Zenpage option "news on index" that some themes provide! Also it does not "know" about "custom pages" outside Zenpage or any special custom theme setup!!').'</p>';
$plugin_author = 'Malte Müller (acrylian) based on the <a href="http://github.com/Tenzer/zenphoto-sitemap">plugin</a> by Jeppe Toustrup (Tenzer) and modifications by Timo and Blue Dragonfly';
$plugin_version = '1.3.1';
$plugin_URL = 'http://www.zenphoto.org/documentation/plugins/_'.PLUGIN_FOLDER.'---sitemap-extended.php.html';
$option_interface = new sitemap();

zp_register_filter('admin_utilities_buttons', 'sitemap_cache_purgebutton');

$sitemapfolder = SERVERPATH.'/cache_html/sitemap';
if (!file_exists($sitemapfolder)) {
	if (!mkdir($sitemapfolder, CHMOD_VALUE)) {
		die(gettext("sitemap cache folder could not be created. Please try to create it manually via FTP with chmod 0777."));
	}
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
		setOptionDefault('sitemap_disablecache', 0);
	}

	function getOptionsSupported() {
		return array(	gettext('Sitemap cache expire') => array('key' => 'sitemap_cache_expire', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("When the cache should expire in seconds. Default is 86400 seconds (1 day  = 24 hrs * 60 min * 60 sec).The cache can also be cleared on the admin overview page manually.")),
		gettext('Change frequency - Zenphoto index') => array('key' => 'sitemap_changefreq_index', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequency - albums') => array('key' => 'sitemap_changefreq_albums', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequency - images') => array('key' => 'sitemap_changefreq_images', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequency - Zenpage pages') => array('key' => 'sitemap_changefreq_pages', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequency - Zenpage news index') => array('key' => 'sitemap_changefreq_newsindex', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequency: Zenpage news articles') => array('key' => 'sitemap_changefreq_news', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
																					gettext("yearly")=>"yearly",
																					gettext("never")=>"never"),
										'desc' => ''),
		gettext('Change frequency - Zenpage news categories') => array('key' => 'sitemap_changefreq_newscats', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext("always")=>"always",
																					gettext("hourly")=>"hourly",
																					gettext("daily")=>"daily",
																					gettext("weekly")=>"weekly",
																					gettext("monthly")=>"monthly",
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
	gettext('Disable cache') => array('key' => 'sitemap_disablecache', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => '')
	);
	}

	function handleOption($option, $currentValue) {
	}
}

$sitemap_locales = generateLanguageList();

/**
 * creates the Utilities button to purge the static sitemap cache
 * @param array $buttons
 * @return array
 */
function sitemap_cache_purgebutton($buttons) {
	$buttons[] = array(
								'enable'=>true,
								'button_text'=>gettext('Purge sitemap cache'),
								'formname'=>'clearcache_button',
								'action'=>PLUGIN_FOLDER.'/sitemap-extended.php?action=clear_sitemap_cache',
								'icon'=>'images/edit-delete.png',
								'title'=>gettext('Clear the static sitemap cache. It will be re-cached if requested.'),
								'alt'=>'',
								'hidden'=> '<input type="hidden" name="action" value="clear_sitemap_cache" />',
								'rights'=> ADMIN_RIGHTS
	);
	return $buttons;
}
/**
 * Returns true if the site is set to "multilingual" and mod_rewrite and  and the seo_locale plugin are enabled.
 */
function sitemap_multilingual() {
	if(getOption('multi_lingual') && getOption('zp_plugin_seo_locale') && getOption('mod_rewrite')) {
		return true;
	} else {
		return false;
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
			$date = $obj->getDatetime();
			break;
		case 'mtime':
			$timestamp = $obj->get('mtime');
			if($timestamp == 0) {
				$date = $obj->getDatetime();
			} else {
				return gmstrftime('%Y-%m-%dT%H:%M:%SZ', $timestamp);
				// For more streamlined but PHP5-only equivalent, remove the above line and uncomment the following:
				// return gmstrftime(DATE_ISO8601, $timestamp);
			}
			break;
	}
	return sitemap_getISO8601Date($date);
	// For more streamlined but PHP5-only equivalent, remove the above line and uncomment the following:
	// return gmstrftime(DATE_ISO8601, strtotime($date));
}
/**
 * Prints the links to the index of a Zenphoto gallery incl. pagination
 * @param  int $albumsperpage In case your theme performes custom option settings that are different from the admin option, set the number here.
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapIndexLinks($albumsperpage='',$changefreq='') {
	global $_zp_gallery, $sitemap_locales;
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_index');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	if(sitemap_multilingual()) {
		foreach($sitemap_locales as $locale) {
			sitemap_echonl("\t<url>\n\t\t<loc>".FULLWEBPATH."/".$locale."/</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
		}
	} else {
	sitemap_echonl("\t<url>\n\t\t<loc>".FULLWEBPATH."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
	}
	set_context(ZP_INDEX);
	/*if(galleryAlbumsPerPage() != 0) {
		$toplevelpages = ceil($_zp_gallery->getNumAlbums() / galleryAlbumsPerPage());
		} else {
		$toplevelpages = false;
		} */
	$albums_per_page = getOption('albums_per_page');
	if(!empty($albumsperpage)) {
		setOption('albums_per_page',sanitize_numeric($albumsperpage),false);
	} else {
		setOption('albums_per_page',$albums_per_page);
	}
	$toplevelpages = getTotalPages();
	// print further index pages if avaiable
	if($toplevelpages) {
		for($x = 2;$x <= $toplevelpages; $x++) {
			if(sitemap_multilingual()) {
				foreach($sitemap_locales as $locale) {
					$url = FULLWEBPATH.'/'.rewrite_path($locale.'/page/'.$x,'index.php?page='.$x,false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
				}
			} else {
				$url = FULLWEBPATH.'/'.rewrite_path('page/'.$x,'index.php?page='.$x,false);
				sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
			}
		}
	}
	restore_context();
}

/**
 * Prints links to all albums incl. pagination and their images
 * @param  array $albumsperpage In case your theme performes custom option settings that are different from the admin option, use an array to set the number here for albums individudially.
 * 																Example: $albumsperpage	= array('<album1name>' => <desired albums per page>, '<album2name>' => <desired albums per page>);
 * @param  array $imagessperpage In case your theme performes custom option settings that are different from the admin option, use an array to set the number here for albums individudially. (see example above)
 * @param  string $albumchangefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @param  string $imagechangefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @param  string $albumlastmod "date or "mtime"
 * @param  string $imagelastmod "date or "mtime"
 * @return string
 */
function printSitemapAlbumsAndImages($albumsperpage='',$imagesperpage ='',$albumchangefreq='',$imagechangefreq='',$albumlastmod='',$imagelastmod='') {
	global $_zp_gallery, $_zp_current_album,$sitemap_locales;
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
	$imagesperpage = sanitize($imagesperpage);
	$albumsperpage = sanitize($albumsperpage);
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
			set_context(ZP_ALBUM);
			makeAlbumCurrent($albumobj);
			//getting the album pages
			$images_per_page = getOption('images_per_page');
			$albums_per_page = getOption('albums_per_page');
			if(is_array($imagesperpage)) {
				foreach($imagesperpage as $alb=>$number) {
					if($alb == $albumobj->name) {
						setOption('images_per_page',$number,false);
					} else {
						setOption('images_per_page',$images_per_page);
					}
				}
			}
			if(is_array($albumsperpage)) {
				foreach($albumsperpage as $alb=>$number) {
					if($alb == $albumobj->name) {
						setOption('albums_per_page',$number,false);
					} else {
						setOption('albums_per_page',$albums_per_page);
					}
				}
			}
			$pageCount = getTotalPages();
			$date = sitemap_getDateformat($albumobj,$albumlastmod);
			if(sitemap_multilingual()) {
				foreach($sitemap_locales as $locale) {
					$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.pathurlencode($albumobj->name),'?album='.pathurlencode($albumobj->name),false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$albumchangefreq."</changefreq>\n\t\t<priority>0.8</priority>\n\t</url>");
				}
			} else {
				$url = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name),'?album='.pathurlencode($albumobj->name),false);
				sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$albumchangefreq."</changefreq>\n\t\t<priority>0.8</priority>\n\t</url>");
			}
			// print album pages if avaiable
			if($pageCount > 1) {
				for($x = 2;$x <= $pageCount; $x++) {
					if(sitemap_multilingual()) {
						foreach($sitemap_locales as $locale) {
							$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.pathurlencode($albumobj->name).'/page/'.$x,'?album='.pathurlencode($albumobj->name).'&amp;page='.$x,false);
							sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$albumchangefreq."</changefreq>\n\t\t<priority>0.8</priority>\n\t</url>");
						}
					} else {
						$url = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name).'/page/'.$x,'?album='.pathurlencode($albumobj->name).'&amp;page='.$x,false);
						sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$albumchangefreq."</changefreq>\n\t\t<priority>0.8</priority>\n\t</url>");
					}
				}
			}
			// print images linksif avaiable
			$images = $albumobj->getImages();
			if($images) {
				foreach($images as $image) {
					$imageob = newImage($albumobj,$image);
					$date = sitemap_getDateformat($imageob,$imagelastmod);
					if(sitemap_multilingual()) {
						foreach($sitemap_locales as $locale) {
							$path = FULLWEBPATH.'/'.rewrite_path($locale.'/'.pathurlencode($albumobj->name).'/'.urlencode($imageob->filename).im_suffix(),'?album='.pathurlencode($albumobj->name).'&amp;image='.urlencode($imageob->filename),false);
							sitemap_echonl("\t<url>\n\t\t<loc>".$path."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$imagechangefreq."</changefreq>\n\t\t<priority>0.6</priority>\n\t</url>");
						}
					} else {
						$path = FULLWEBPATH.'/'.rewrite_path(pathurlencode($albumobj->name).'/'.urlencode($imageob->filename).im_suffix(),'?album='.pathurlencode($albumobj->name).'&amp;image='.urlencode($imageob->filename),false);
						sitemap_echonl("\t<url>\n\t\t<loc>".$path."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$imagechangefreq."</changefreq>\n\t\t<priority>0.6</priority>\n\t</url>");
					}
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
	global $sitemap_locales;
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
			if(!is_null($pageobj->getLastchange())) $lastchange = substr($pageobj->getLastchange(),0,10);
			if($date > $lastchange) $date = $lastchange;
			if(!isProtectedPage($pageobj)) {
				if(sitemap_multilingual()) {
					foreach($sitemap_locales as $locale) {
						$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.ZENPAGE_PAGES.'/'.urlencode($page['titlelink']),'?p='.ZENPAGE_PAGES.'&amp;title='.urlencode($page['titlelink']),false);
						sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
					}
				} else {
					$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_PAGES.'/'.urlencode($page['titlelink']),'?p='.ZENPAGE_PAGES.'&amp;title='.urlencode($page['titlelink']),false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
				}
			}
		}
	}
}
/**
 * Prints links to the main Zenpage news index incl. pagination
 * @param  int $articlesperpage In case your theme performes custom option settings that are different from the admin option, set the number here.
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpageNewsIndex($articlesperpage='',$changefreq='') {
	global $sitemap_locales;
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_newsindex');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	if(sitemap_multilingual()) {
		foreach($sitemap_locales as $locale) {
			$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.ZENPAGE_NEWS.'/1','?p='.ZENPAGE_NEWS.'&amp;page=1',false);
			sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
		}
	}else {
		$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/1','?p='.ZENPAGE_NEWS.'&amp;page=1',false);
		sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
	}
	// getting pages for the main news loop
	if(!empty($articlesperpage)) {
		$zenpage_articles_per_page = sanitize_numeric($articlesperpage);
	} else {
		$zenpage_articles_per_page = getOption("zenpage_articles_per_page");
	}
	$newspages = ceil(getTotalArticles() / $zenpage_articles_per_page);
	if($newspages > 1) {
		for($x = 2;$x <= $newspages; $x++) {
			if(sitemap_multilingual()) {
				foreach($sitemap_locales as $locale) {
					$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.ZENPAGE_NEWS.'/'.$x,'?p='.ZENPAGE_NEWS.'&amp;page='.$x,false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
				}
			} else {
				$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/'.$x,'?p='.ZENPAGE_NEWS.'&amp;page='.$x,false);
				sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".sitemap_getISO8601Date()."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
			}
		}
	}
}
/**
 * Prints to the Zenpage news articles
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpageNewsArticles($changefreq='') {
	global $sitemap_locales;
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
			if(!is_null($articleobj->getLastchange())) $lastchange = substr($articleobj->getLastchange(),0,10);
			if($date > $lastchange) $date = $lastchange;
			if(!inProtectedNewsCategory($articleobj)) {
				if(sitemap_multilingual()) {
					foreach($sitemap_locales as $locale) {
						$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.ZENPAGE_NEWS.'/'.urlencode($articleobj->getTitlelink()),'?p='.ZENPAGE_NEWS.'&amp;title=' . urlencode($articleobj->getTitlelink()),false);
						sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
					}
				}	else {
					$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/'.urlencode($articleobj->getTitlelink()),'?p='.ZENPAGE_NEWS.'&amp;title=' . urlencode($articleobj->getTitlelink()),false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<lastmod>".$date."</lastmod>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
				}
			}
		}
	}
}

/**
 * Prints links to Zenpage news categories incl. pagination
 * @param  array $albumsperpage In case your theme performes custom option settings that are different from the admin option, use an array to set the number here for categories individudially.
 * 																Example: $albumsperpage	= array('<category1name>' => <desired articles per page>, '<category2name>' => <desired articles per page>);
 * @param  string $changefreq One of the supported changefrequence values regarding sitemap.org. Default is empty or wrong is "daily".
 * @return string
 */
function printSitemapZenpageNewsCategories($articlesperpage='',$changefreq='') {
	global $sitemap_locales;
	if(empty($changefreq)) {
		$changefreq = getOption('sitemap_changefreq_newscats');
	} else {
		$changefreq = sitemap_getChangefreq($changefreq);
	}
	$newscats = getAllCategories();
	if($newscats) {
		// Add the correct URLs to the URL list
		foreach($newscats as $newscat) {
			if(!isProtectedNewsCategory($newscat['cat_link'])) {
				if(sitemap_multilingual()) {
					foreach($sitemap_locales as $locale) {
						$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']).'/1','?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']).'&amp;page=1',false);
						sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
					}
				} else {
					$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']).'/1','?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']).'&amp;page=1',false);
					sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
				}
				// getting pages for the categories
				if(!empty($articlesperpage)) {
					$zenpage_articles_per_page = sanitize_numeric($articlesperpage);
				} else {
					$zenpage_articles_per_page = getOption("zenpage_articles_per_page");
				}
				$articlecount = countArticles($newscat['cat_link']);
				$catpages = ceil($articlecount / $zenpage_articles_per_page);
				if($catpages > 1) {
					for($x = 2;$x <= $catpages ; $x++) {
						if(sitemap_multilingual()) {
							foreach($sitemap_locales as $locale) {
								$url = FULLWEBPATH.'/'.rewrite_path($locale.'/'.ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']).'/'.$x,'?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']).'&amp;page='.$x,false);
								sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
							}
						} else {
							$url = FULLWEBPATH.'/'.rewrite_path(ZENPAGE_NEWS.'/category/'.urlencode($newscat['cat_link']).'/'.$x,'?p='.ZENPAGE_NEWS.'&amp;category=' . urlencode($newscat['cat_link']).'&amp;page='.$x,false);
							sitemap_echonl("\t<url>\n\t\t<loc>".$url."</loc>\n\t\t<changefreq>".$changefreq."</changefreq>\n\t\t<priority>0.9</priority>\n\t</url>");
						}
					}
				}
			}
		}
	}
}

/**
 * Starts static sitemap caching
 *
 */
function startSitemapCache() {
	$disablecaching = getOption('sitemap_disablecache');
	if(zp_loggedin()) {
		$disablecaching = true;
	}
	if(!$disablecaching) {
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
function endSitemapCache() {
	$disablecaching = getOption('sitemap_disablecache');
	if(zp_loggedin()) {
		$disablecaching = true;
	}
	if(!$disablecaching) {
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
 * Cleans out the cache folder.
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

/**
 * Returns an ISO-8601 compliant date/time string for the given date/time.
 * While PHP5 can use the date format constant DATE_ISO8601, this function is designed to allow PHP4 use as well.
 * Eventually it can be deprecated, by:
 *   1. Replacing parameterless references to this function with date(DATE_ISO8601)
 *   2. Replacing references to this function in sitemap_getDateformat as documented there
 *
 */
function sitemap_getISO8601Date($date='') {
	if (empty($date)) {
		return gmstrftime('%Y-%m-%dT%H:%M:%SZ');
	} else {
		return gmstrftime('%Y-%m-%dT%H:%M:%SZ', strtotime($date));
	}
}