<?php
/**
 * zenpage template functions
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */


/**
 * Some global variable setup
 *
 */
global $_zp_current_zenpage_page, $_zp_current_zenpage_news;

/************************************************/
/* ZENPAGE TEMPLATE FUNCTIONS
 /************************************************/

/************************************************/
/* General functions
/************************************************/

/**
 * Same as zenphoto's rewrite_path() except it's without WEBPATH, needed for some partial urls
 * 
 * @param $rewrite The path with mod_rewrite
 * @param $plain The path without
 * 
 * @return string
 */
function rewrite_path_zenpage($rewrite='',$plain='') {
	if (getOption('mod_rewrite')) {
		return $rewrite;
	} else {
		return $plain;
	}
}


function zenpage404($type, $obj) {
	global $_zp_gallery_page, $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_themeroot;
	$_zp_gallery_page = '404.php';
	$errpage = THEMEFOLDER.'/'.UTF8ToFilesystem(getOption('current_theme')).'/404.php';
	unset($album);
	unset($image);
	$obj = sprintf(gettext('%1$s=>%2$s'), $type, $obj.'....');
	header("HTTP/1.0 404 Not Found");
	if (file_exists(SERVERPATH . "/" . $errpage)) {
		include($errpage);
	} else {
		include(ZENFOLDER. '/404.php');
	}
	exit;
}


/**
 * Returns if the current page is $page (examples: isPage("news") or isPage("pages"))
 *
 * @param string $page The name of the page to check for
 * @return bool
 */
function isPage($page) {
	if(isset($_GET['p'])) {
		$currentpagetype = $_GET["p"];
	} else {
		$currentpagetype = NULL;
	}
	if($currentpagetype === $page) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * Checks if the current page is a news or single news article page.
 *
 * @return bool
 */
function is_News() {
	global $_zp_gallery_page;
	if (isPage(ZENPAGE_NEWS) OR (getOption('zenpage_zp_index_news') AND $_zp_gallery_page == "index.php")) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * Checks if the current page is a single news article page
 *
 * @return bool
 */
function is_NewsArticle() {
	if (isPage(ZENPAGE_NEWS) AND isset($_GET['title'])) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * Checks if the current page is a news category page
 *
 * @return bool
 */
function is_NewsCategory() {
	if (isPage(ZENPAGE_NEWS) AND isset($_GET['category'])) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * Checks if the current page is a news archive page
 *
 * @return bool
 */
function is_NewsArchive() {
	if (isPage(ZENPAGE_NEWS) AND isset($_GET['date'])) {
		return TRUE;
	} else {
		return FALSE;
	}
}



/**
 * Checks if the current page is a zenpage page
 *
 * @return bool
 */
function is_Pages() {
	if (isPage(ZENPAGE_PAGES) AND isset($_GET['title'])) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Checks if we are on the theme's index and if an unpublished Zenpage page is set as homepage with the "zenpage_homepage" option 
 * Returns true or false.
 *
 * @return bool
 */
function is_Homepage() {
	global $_zp_gallery_page;
	if(getOption("zenpage_homepage") != "none" AND $_zp_gallery_page == "index.php") {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * Loads pages.php and sets up the pages $_zp_current_zenpage_page (via zenpage_load_page()) if the homepage option is set..
 * To be used on top of index.php
 */
function use_Homepage() {
	global $_zp_gallery_page, $_zp_current_album, $_zp_themeroot;
	if(is_Homepage()) {
		$themedir = SERVERPATH . "/themes/".basename($_zp_themeroot);
		zenpage_load_page(getOption("zenpage_homepage"));
		require($themedir."/".ZENPAGE_PAGES.".php");
		exit;
	}
}
/**
 * Gets the news type of a news item. 
 * "news" for a news article or if using the CombiNews feature
 * "flvmovie" (for flv, mp3 and mp4), "image", "3gpmovie" or "quicktime"
 *  *
 * @return string
 */
function getNewsType() {
	global $_zp_current_zenpage_news;
	$ownerclass = strtolower(get_class($_zp_current_zenpage_news));
	switch($ownerclass) {
		case "video":
			$newstype = "video";
			break;
		case "album":
			$newstype = "album";
			break;
		case "zenpagenews":
			$newstype = "news";
			break;
		default:
			$newstype = "image";
			break;
	}
 return $newstype;
}


/**
 * Checks what type the current news item is (See get NewsType())
 *
 * @param string $type The type to check for
 * 										 "news" for a news article or if using the CombiNews feature
 * 										"flvmovie" (for flv, mp3 and mp4), "image", "3gpmovie" or "quicktime"
 * @return bool
 */
function is_NewsType($type) {
	if(getNewsType() === $type) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * CombiNews feature: A general wrapper function to check if this is a 'normal' news article (type 'news' or one of the zenphoto news types
 *
 * @return bool
 */
function is_GalleryNewsType() {
	if(is_NewsType("image") OR is_NewsType("video") OR is_NewsType("album")) { // later to be extended with albums, too
		return TRUE;
	} else {
		return FALSE;
	}
}



/**
 * THIS FUNCTION IS DEPRECATED! Use getZenpageHitcounter()!
 * 
 * Increments (optionally) and returns the hitcounter for a news category (page 1), a single news article or a page
 * Does not increment the hitcounter if the viewer is logged in as the gallery admin.
 * Also does currently not work if the static cache is enabled
 *
 * @param string $option "pages" for a page, "news" for a news article, "category" for a news category (page 1 only)
 * @param bool $viewonly set to true if you don't want to increment the counter.
 * @param int $id Optional record id of the object if not the current image or album
 * @return string
 */
function zenpageHitcounter($option='pages', $viewonly=false, $id=NULL) {
	global $_zp_current_zenpage_page, $_zp_current_zenpage_news, $_zp_loggedin;
	trigger_error(gettext('hitcounter is deprecated. Use getZenpageHitcounter().'), E_USER_NOTICE);
	switch($option) {
		case "pages":
			if (is_null($id)) {
				$id = getPageID();
			}
			$dbtable = prefix('zenpage_pages');
			$doUpdate = true;
			break;
		case "category":
			if (is_null($id)) {
				$id = getCurrentNewsCategoryID();
			}
			$dbtable = prefix('zenpage_news_categories');
			$doUpdate = getCurrentNewsPage() == 1; // only count initial page for a hit on an album
			break;
		case "news":
			if (is_null($id)) {
				$id = getNewsID();
			}
			$dbtable = prefix('zenpage_news');
			$doUpdate = true;
			break;
	}
	if(($option === "pages" AND is_Pages()) OR ($option === "news" AND is_NewsArticle()) OR ($option === "category" AND is_NewsCategory())) {
		if (($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) || $viewonly) { $doUpdate = false; }
		$hitcounter = "hitcounter";
		$whereID = " WHERE `id` = $id";
		$sql = "SELECT `".$hitcounter."` FROM $dbtable $whereID";
		if ($doUpdate) { $sql .= " FOR UPDATE"; }
		$result = query_single_row($sql);
		$resultupdate = $result['hitcounter'];
		if ($doUpdate) {
			$resultupdate++;
			query("UPDATE $dbtable SET `".$hitcounter."`= $resultupdate $whereID");
		}
		return $resultupdate;
	}
}

/**
 * Gets the hitcount of a page, news article or news category
 * 
 * @param string $mode Pass "news", "page" or "category" to get the hitcounter of the current page, article or category if one is set
 * @param mixed $obj If you want to get the hitcount of a specific page or article you additonally can to pass its object.
 * 									 If you want to get the hitcount of a specific category you need to pass its cat_link. 
 * 									 In any case $mode must be set!
 * @return int
 */
function getZenpageHitcounter($mode="",$obj="") {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_gallery_page;
	$obj = sanitize($obj);
	switch($mode) {
		case "news":
			if((is_NewsArticle() OR is_News()) AND !is_object($obj)) {
				$obj = $_zp_current_zenpage_news;
				$hc = $obj->get('hitcounter');
			} else if(is_object($obj)) {
				$hc = $obj->get('hitcounter');
			}
			return $hc;
			break;
		case "page":
			if(is_Pages() AND !is_object($obj)) {
				$obj = $_zp_current_zenpage_page;
				$hc = $obj->get('hitcounter');
			} else if(is_object($obj)) {
				$hc = $obj->get('hitcounter');
			}
			return $hc;
			break;
		case "category":
			if(is_NewsCategory() AND !empty($obj)) {
				$catname = sanitize($_GET['category']);
			} else if(!is_object($obj)) {
				$catname = sanitize($_GET['category']);
			}
			$hc = query_single_row("SELECT hitcounter FROM ".prefix('zenpage_news_categories')." WHERE cat_link = '".$catname."'");
			return $hc["hitcounter"];
			break;
	}
}


/**
 * Wrapper function to get the author of a news article or page: Used by getNewsAuthor() and getPageAuthor().
 * 
 * @param bool $fullname False for the user name, true for the full name
 *
 * @return string
 */
function getAuthor($fullname=false) {
	global $_zp_current_zenpage_page, $_zp_current_zenpage_news;
		if(is_Pages()) {
			$obj = $_zp_current_zenpage_page;
		}
		if(is_News()) {
			$obj = 	$_zp_current_zenpage_news;
		}
	if(is_Pages() OR is_News()) {
		if($fullname) {
			$admins = getAdministrators();
			foreach ($admins as $admin) {
				if($admin['user'] === $obj->getAuthor()) {
					return $admin['name'];
				}
			}
		} else {
			return $obj->getAuthor();
		}
	}
}

/************************************************/
/* News article functions
 /************************************************/


/**
 * Returns the next news item on a page.
 * sets $_zp_current_zenpage_news to the next news item
 * Returns true if there is an new item to be shown
 *
 * @return bool
 */
function next_news() {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_news_restore, $_zp_zenpage_articles, $_zp_gallery;
	if(!checkforPassword()) {
		if(is_News() AND !is_NewsArticle()) {
			if (is_null($_zp_zenpage_articles)) {
				if(getOption('zenpage_combinews') AND !is_NewsCategory() AND !is_NewsArchive()) {
					$_zp_zenpage_articles = getCombiNews(getOption("zenpage_articles_per_page"));
				} else {
					$_zp_zenpage_articles = getNewsArticles(getOption("zenpage_articles_per_page"));
				}
				//print_r($_zp_zenpage_articles); // debugging
				if (empty($_zp_zenpage_articles)) { return false; }
				$_zp_current_zenpage_news_restore = $_zp_current_zenpage_news;
				$news = array_shift($_zp_zenpage_articles);
				//print_r($news); // debugging
				if (is_array($news)) {
					if(getOption('zenpage_combinews') AND array_key_exists("type",$news) AND array_key_exists("albumname",$news)) {
						if($news['type'] === "images") {
							$albumobj = new Album($_zp_gallery,$news['albumname']);
							$_zp_current_zenpage_news = newImage($albumobj,$news['titlelink']);
						} else if($news['type'] === "albums") {
							$_zp_current_zenpage_news = new Album($_zp_gallery,$news['albumname']);
						} else {
							$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
						}
					} else {
						$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
					}
				}
				return true;
			} else if (empty($_zp_zenpage_articles)) {
				$_zp_zenpage_articles = NULL;
				$_zp_current_zenpage_news = $_zp_current_zenpage_news_restore;
				return false;
			} else {
				$news = array_shift($_zp_zenpage_articles);
				if (is_array($news)) {
					if(getOption('zenpage_combinews') AND array_key_exists("type",$news) AND array_key_exists("albumname",$news)) {
						if($news['type'] === "images") {
							$albumobj = new Album($_zp_gallery,$news['albumname']);
							$_zp_current_zenpage_news = newImage($albumobj,$news['titlelink']);
						} else if($news['type'] === "albums") {
							$_zp_current_zenpage_news = new Album($_zp_gallery,$news['albumname']);
						} else {
							$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
						}
					} else {
						$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
					}
				}
				return true;
			}
		} // if news end
	} // checkpassword if end
}

/**
 * Gets the id of a news article/item
 *
 * @return int
 */
function getNewsID() {
	global $_zp_current_zenpage_news;
	if(is_News()) {
		return $_zp_current_zenpage_news->getID();
	}
}


/**
 * Gets the news article title
 *
 * @return string
 */
function getNewsTitle() {
	global $_zp_current_zenpage_news;
	if(is_News()) {
		return $_zp_current_zenpage_news->getTitle();
	} 
}


/**
 * prints the news article title
 *
 * @param string $before insert if you want to use for the breadcrumb navigation or in the html title tag
 */
function printNewsTitle($before='') {
	if (getNewsTitle()) {
		echo $before.getNewsTitle();
	}
}

/**
 * Returns the raw title of a news article.
 * 
 * @param string $before insert if you want to use for the breadcrumb navigation or in the html title tag
 *
 * @return string
 */
function getBareNewsTitle($before='') {
	return html_encode($before.getNewsTitle());
}


/**
 * Returns the titlelink (url name) of the current news article.
 * 
 * If using the CombiNews feature this also returns the full path to a image.php page if the item is an image.
 *
 * @return string
 */
function getNewsTitleLink() {
	global $_zp_current_zenpage_news;
	if(is_News()) {
		$type = getNewsType();
		switch($type) {
			case "image":
			case "video":
				$link = $_zp_current_zenpage_news->getImageLink();
				break;
			case "album":
				$link = $_zp_current_zenpage_news->getAlbumLink();
				break;
			case "news":
				$link = $_zp_current_zenpage_news->getTitlelink();
				break;
		}
		return $link;
	}
}


/**
 * Prints the titlelin of a news article as a full html link
 *
 * @param string $before insert what you want to be show before the titlelink.
 */
function printNewsTitleLink($before='') {
	if (getNewsTitle()) {
		if(is_NewsType("news")) {
			echo "<a href=\"".getNewsURL(getNewsTitleLink())."\" title=\"".getBareNewsTitle()."\">".$before.getNewsTitle()."</a>";
		} else if (is_GalleryNewsType()) {
			echo "<a href=\"".getNewsTitleLink()."\" title=\"".getBareNewsTitle()."\">".$before.getNewsTitle()."</a>";
		}
	}
}


/**
 * Gets the content of a news article
 * 
 * If using the CombiNews feature this returns the description for gallery items (see printNewsContent for more)
 *
 * @param int $shorten The optional length of the content for the news list for example, will override the plugin option setting if set, "" (empty) for full content (not used for image descriptions!)
 * @param string $shortenindicator The optional placeholder that indicates that the content is shortened, if this is set it overrides the plugin options setting.
 * * @return string
 */
function getNewsContent($shorten='', $shortenindicator='') {
	global $_zp_current_zenpage_news;
	if(empty($shortenindicator)) {
		$shortenindicator = getOption("zenpage_textshorten_indicator");
	}
	if(empty($shorten) AND !is_NewsArticle()) {
		$shorten = getOption("zenpage_text_length");
	}
	$newstype = getNewsType();
	switch($newstype) {
		case "news":
			$articlecontent = $_zp_current_zenpage_news->getContent();
			break;
		case "image":
		case "video":
		case "album":
			$articlecontent = $_zp_current_zenpage_news->getDesc();
			break;
	}
	if(!empty($shorten) AND strlen($articlecontent) > $shorten) {
		$articlecontent = shortenContent($articlecontent,$shorten,$shortenindicator);
	}
	return $articlecontent;
}


/**
 * Prints the news article content. Note: TinyMCE used by Zenpage for news articles by default already adds a surrounding <p></p> to the content.
 *
 * If using the CombiNews feature this prints the thumbnail or sized image for a gallery item.
 * If using the 'CombiNews sized image' mode it shows movies directly and the description below.
 *
 * @param int $shorten $shorten The lengths of the content for the news main page for example (only for video/audio descriptions, not for normal image descriptions)
 */
function printNewsContent($shorten='',$shortenindicator='') {
	global $_zp_flash_player, $_zp_current_image, $_zp_gallery, $_zp_current_zenpage_news, $_zp_page;
	$size = getOption("zenpage_combinews_imagesize");
	$mode = getOption("zenpage_combinews_mode");
	$type = getNewsType();
	switch ($type) {
		case "news":
			echo getNewsContent($shorten,$shortenindicator);
			break;
		case "image":
			switch($mode) {
				case "latestimages-sizedimage":
					echo "<a href='".$_zp_current_zenpage_news->getImageLink()."' title'".strip_tags($_zp_current_zenpage_news->getTitle())."'><img src='".$_zp_current_zenpage_news->getSizedImage($size)."' alt='".$_zp_current_zenpage_news->getTitle()."' /></a><br />";
					break;
				case "latestimages-thumbnail":
					echo "<a href='".$_zp_current_zenpage_news->getImageLink()."' title'".strip_tags($_zp_current_zenpage_news->getTitle())."'><img src='".$_zp_current_zenpage_news->getThumb()."' alt='".$_zp_current_zenpage_news->getTitle()."' /></a><br />";
					break;
			}
			echo getNewsContent("");
			break;
		case "video":
			$ext = strtolower(strrchr(getFullNewsImageURL(), "."));
			switch($ext) {
				case '.flv':
				case '.mp3':
				case '.mp4':
					if (is_null($_zp_flash_player)) {
						echo  "<img src='" . WEBPATH . '/' . ZENFOLDER . "'/images/err-noflashplayer.gif' alt='".gettext('No flash player installed.')."' />";
					} else {
						$newalbum = new Album($_zp_gallery,getNewsAlbumName());
						$_zp_current_image = newImage($newalbum,getNewsFilename());
						$_zp_flash_player->printPlayerConfig(getFullNewsImageURL(),getNewsTitle(),$_zp_current_image->get("id"));
					}
					echo getNewsContent($shorten);
					break;
				case '.3gp':
					echo '</a>
					<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="'.
					DEFAULT_3GP_WIDTH.'" height="'.DEFAULT_3GP_HEIGHT.
					'" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
					<param name="src" value="' . getFullNewsImageURL() . '"/>
					<param name="autoplay" value="false" />
					<param name="type" value="video/quicktime" />
					<param name="controller" value="true" />
					<embed src="' . getFullNewsImageURL() . '" width="'.DEFAULT_3GP_WIDTH.'" height="'.DEFAULT_3GP_HEIGHT.'" autoplay="false" controller"true" type="video/quicktime"
						pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
						</object><a>';
					echo getNewsContent($shorten);
				break;
				case '.mov':
					echo '</a>
			 		<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="'.DEFAULT_MOV_WIDTH.'" height="'.DEFAULT_MOV_HEIGHT.'" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
				 	<param name="src" value="' . getFullNewsImageURL() . '"/>
				 	<param name="autoplay" value="false" />
				 	<param name="type" value="video/quicktime" />
				 	<param name="controller" value="true" />
				 	<embed src="' . getFullNewsImageURL() . '" width="'.DEFAULT_MOV_WIDTH.'" height="'.DEFAULT_MOV_HEIGHT.'" autoplay="false" controller"true" type="video/quicktime"
				 		pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
					</object><a>';
				echo getNewsContent($shorten);
				break;
			}
			break;
		case "album":
			$_zp_page = 1;
			switch($mode) {
				case "latestalbums-sizedimage":
					$albumthumbobj = $_zp_current_zenpage_news->getAlbumThumbImage();
					echo "<a href='".$_zp_current_zenpage_news->getAlbumLink()."' title'".strip_tags($_zp_current_zenpage_news->getTitle())."'><img src='".$albumthumbobj->getSizedImage($size)."' alt='".$_zp_current_zenpage_news->getTitle()."' /></a><br />";
					break;
				case "latestalbums-thumbnail":
					echo "<a href='".$_zp_current_zenpage_news->getAlbumLink()."' title'".strip_tags($_zp_current_zenpage_news->getTitle())."'><img src='".$_zp_current_zenpage_news->getAlbumThumb()."' alt='".$_zp_current_zenpage_news->getTitle()."' /></a><br />";
					break;
			}
			echo getNewsContent("");
			break;
	}
}


/**
 * Gets the extracontent of a news article if in single news articles view or returns FALSE
 * 
 * @return string
 */
function getNewsExtraContent() { 
	global $_zp_current_zenpage_news;
	if(is_NewsArticle() AND is_NewsType("news")) {
		$extracontent = $_zp_current_zenpage_news->getExtraContent();
		return $extracontent;
	} else {
		return FALSE;
	}
}


/**
 * Prints the extracontent of a news article if in single news articles view
 * 
 * @return string
 */
function printNewsExtraContent() { 
	echo getNewsExtraContent();
}

/**
 * Returns the text for the read more link or if using CombiNews feature also the link to the image.php gallery page
 *
 * @return string
 */
function getNewsReadMore() {
	global $_zp_current_zenpage_news;
	if(!isset($_GET['title'])) {
		$type = getNewsType();
		switch($type) {
			case "news":
				$readmore = get_language_string(getOption("zenpage_read_more"));
				$content = $_zp_current_zenpage_news->getContent();
				break;
			case "image":
			case "video":
			case "album":
				$readmore = get_language_string(getOption("zenpage_combinews_readmore"));
				$content = $_zp_current_zenpage_news->getDesc();
				break;
		}
		$shorten = getOption("zenpage_text_length");
		if((strlen($content) > $shorten) AND !empty($shorten) OR is_GalleryNewsType()) {
			return $readmore;
		}
	}
}


/**
 * Prints the read more link or if using CombiNews feature also the link to the image.php gallery page as a full html link
 *
 * @param string $readmore The readmore text to be shown for the full news article link. If empty the option setting is used.
 * @return string
 */
function printNewsReadMoreLink($readmore='') {
	if(empty($readmore)) {
		$readmore = getNewsReadMore();
	}
	if(is_NewsType("news")) {
		$newsurl = getNewsURL(getNewsTitleLink());
	} else {
		$newsurl = getNewsTitleLink();
	}
	echo "<a href='".$newsurl."' title=\"".getBareNewsTitle()."\">".htmlspecialchars($readmore)."</a>";
}




/**
 * Gets the author of a news article
 *
 * @return string
 */
function getNewsAuthor($fullname=false) { 
	if(is_News() AND is_NewsType("news")) {
		return getAuthor($fullname);
	}
}


/**
 * Prints the author of a news article
 *
 * @return string
 */
function printNewsAuthor($fullname=false) {
	if (getNewsTitle()) {
		echo getNewsAuthor($fullname);
	}
}

/**TODO NOT NEEDED ANYMORE
 * CombiNews feature only: returns the filename with extension if image or movie/audio or false.
 *
 * @return mixed
 */
function getNewsFilename() {
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		return $_zp_current_zenpage_news->filename;
	} else {
		return false;
	}
}

/**
 * CombiNews feature only: returns the album title if image or movie/audio or false.
 *
 * @return mixed
 */
function getNewsAlbumTitle() {
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		if(!is_NewsType("album")) {
			$albumobj = $_zp_current_zenpage_news->getAlbum();
			return $albumobj->getTitle();
		}
	} else {
		return false;
	}
}

/**
 * CombiNews feature only: returns the raw title of an album if image or movie/audio or false.
 *
 * @return string
 */
function getBareNewsAlbumTitle() {
	return html_encode(getNewsAlbumTitle());
}

/**
 * CombiNews feature only: returns the album name (folder) if image or movie/audio or returns false.
 *
 * @return mixed
 */
function getNewsAlbumName() {
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		if(!is_NewsType("album")) {
			$albumobj = $_zp_current_zenpage_news->getAlbum();
			return $albumobj->getFolder();	
		}
	} else {
		return false;
	}
}


/**
 * CombiNews feature only: returns the url to an album if image or movie/audio or returns false.
 *
 * @return mixed
 */
function getNewsAlbumURL() {
	if(getNewsAlbumName()) {
		return rewrite_path("/".getNewsAlbumName(),"index.php?album=".getNewsAlbumName());
	} else {
		return false;
	}
}

/**
 * CombiNews feature only: Returns the fullimage link if image or movie/audio or false.
 *
 * @return mixed
 */
function getFullNewsImageURL() {
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		return $_zp_current_zenpage_news->getFullImage();
	} else { 
		return false;
	}
}


/**
 * Gets the current selected news category
 *
 * @return string
 */
function getCurrentNewsCategory() {
	if(isset($_GET['category'])) {
		$category = getCategoryTitle(sanitize($_GET['category']));
		return $category;
	}
}


/**
 * Prints the currently selected news category
 *
 * @param string $before insert what you want to be show before it
 */
function printCurrentNewsCategory($before='') {
	if(isset($_GET['category'])) {
		echo $before.getCurrentNewsCategory();
	}
}


/**
 * Gets the id of the current selected news category
 *
 * @return int
 */
function getCurrentNewsCategoryID() {
	if(isset($_GET['category'])) {
		$categoryID = getCategoryID(sanitize($_GET['category']));
		return $categoryID;
	}
}


/**
 * Gets the categories of the current news article
 *
 * @return array
 */
function getNewsCategories() {
	global $_zp_current_zenpage_news;
	if(is_News() AND is_NewsType("news")) {
		$categories = $_zp_current_zenpage_news->getCategories(getNewsID());
		return $categories;
	}
}


/**
 * Prints the categories of current article as a unordered html list
 *
 * @param string $separator A separator to be shown between the category names if you choose to style the list inline
 * @param string $class The CSS class for styling
 * @return string
 */
function printNewsCategories($separator='',$before='',$class='') {
	$categories = getNewsCategories();
	$catcount = count($categories);
	if($catcount != 0) {
		if(is_NewsType("news")) {
			echo "<ul class=\"$class\">\n $before ";
			$count = 0;
			foreach($categories as $cat) {
				$count++;
				$catname = get_language_string($cat['cat_name']);
				if($count >= $catcount) {
					$separator = "";
				}
				echo "<li><a href=\"".getNewsCategoryURL($cat['cat_link'])."\" title=\"".$catname."\">".$catname.$separator."</a></li>\n";
			}
			echo "</ul>\n";
		}
	}
}


/**
 * Checks if an article is in a category and returns TRUE or FALSE
 *
 * @param string $catlink The categorylink of a category
 * @return bool
 */
function inNewsCategory($catlink) {
	$categories = getNewsCategories();
	$count = 0;
	foreach($categories as $cat) {
		if($catlink == $cat['cat_link']) {
			$count = 1;
			break;
		}
	}
	if($count === 1) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * CombiNews feature: Returns a list of tags of an image.
 *
 * @return array
 */
function getNewsImageTags() {
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		return $_zp_current_zenpage_news->getTags();
	} else {
		return false;
	}
}

/**
 * CombiNews feature: Prints a list of tags of an image. These tags are not editable.
 *
 * @param string $option links by default, if anything else the
 *               tags will not link to all other photos with the same tag
 * @param string $preText text to go before the printed tags
 * @param string $class css class to apply to the UL list
 * @param string $separator what charactor shall separate the tags
 * @param bool $editable true to allow admin to edit the tags
 * @return string
 */
function printNewsImageTags($option='links',$preText=NULL,$class='taglist',$separator=', ',$editable=TRUE) {
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		$singletag = getNewsImageTags();
		$tagstring = implode(', ', $singletag);
		if (empty($tagstring)) { $preText = ""; }
		if (count($singletag) > 0) {
			echo "<ul class=\"".$class."\">\n";
			if (!empty($preText)) {
				echo "<li class=\"tags_title\">".$preText."</li>";
			}
			$ct = count($singletag);
			for ($x = 0; $x < $ct; $x++) {
				if ($x === $ct - 1) { $separator = ""; }
				if ($option === "links") {
					$links1 = "<a href=\"".htmlspecialchars(getSearchURL($singletag[$x], '', SEARCH_TAGS, 0, 0))."\" title=\"".$singletag[$x]."\" rel=\"nofollow\">";
					$links2 = "</a>";
				}
				echo "\t<li>".$links1.htmlspecialchars($singletag[$x], ENT_QUOTES).$links2.$separator."</li>\n";
			}

			echo "</ul>";

			echo "<br clear=\"all\" />\n";
		}
	}
}


/**
 * Gets the date of the current news article
 *
 * @return string
 */
function getNewsDate() {
	global $_zp_current_zenpage_news;
	if(is_News()) {
		$d = $_zp_current_zenpage_news->getDateTime();
		return zpFormattedDate(getOption("date_format"), strtotime($d));
	}
	return false;
}


/**
 * Prints the date of the current news article
 *
 * @return string
 */
function printNewsDate() {
	echo htmlspecialchars(getNewsDate());
}


/**
 * Prints the monthy news archives sorted by year
 * NOTE: This does only include news articles.
 *
 * @param string $class optional class
 * @param string $yearid optional class for "year"
 * @param string $monthid optional class for "month"
 */
function printNewsArchive($class='archive', $yearid='year', $monthid='month') {
	if (!empty($class)){ $class = "class=\"$class\""; }
	if (!empty($yearid)){ $yearid = "class=\"$yearid\""; }
	if (!empty($monthid)){ $monthid = "class=\"$monthid\""; }
	$datecount = getAllArticleDates();
	$lastyear = "";
	$nr = "";
	echo "\n<ul $class>\n";
	while (list($key, $val) = each($datecount)) {
		$nr++;
		if ($key == '0000-00-01') {
			$year = "no date";
			$month = "";
		} else {
			$dt = strftime('%Y-%B', strtotime($key));
			$year = substr($dt, 0, 4);
			$month = substr($dt, 5);
		}
		if ($lastyear != $year) {
			$lastyear = $year;
			if($nr != 1) {  echo "</ul>\n</li>\n";}
			echo "<li $yearid>$year\n<ul $monthid>\n";
		}
		echo "<li><a href=\"".getNewsBaseURL().getNewsArchivePath().substr($key,0,7)."\" title=\"".$month." (".$val.")\" rel=\"nofollow\">$month ($val)</a></li>\n";
	}
	echo "</ul>\n</li>\n</ul>\n";
}


/**
 * Gets the current select news date (year-month) or formatted
 * 
 * @param string $mode "formatted" for a formatted date or "plain" for the pure year-month (for example "2008-09") archive date 
 * @param string $format If $mode="formatted" how the date should be printed (see PHP's strftime() function for the requirements)
 * @return string
 */
function getCurrentNewsArchive($mode='formatted',$format='%B %Y') {
	if(isset($_GET['date'])) {
		$archivedate = sanitize($_GET['date']);
		if($mode = "formatted") {
		 $archivedate = strtotime($archivedate);
		 $archivedate = strftime($format,$archivedate);
		}
		return $archivedate;
	}
}


/**
 * Prints the current select news date (year-month) or formatted
 * 
 * @param string $before What you want to print before the archive if using in a breadcrumb navigation for example
 * @param string $mode "formatted" for a formatted date or "plain" for the pure year-month (for example "2008-09") archive date 
 * @param string $format If $mode="formatted" how the date should be printed (see PHP's strftime() function for the requirements)
 * @return string
 */
function printCurrentNewsArchive($before='',$mode='formatted',$format='%B %Y') {
	if(getCurrentNewsArchive()) {
		echo $before.getCurrentNewsArchive($mode,$format);
	}
}


/**
 * Prints all news categories as a unordered html list
 * 
 * @param string $newsindex How you want to call the link the main news page without a category, leave empty if you don't want to print it at all.
 * @param bool $counter TRUE or FALSE (default TRUE). If you want to show the number of articles behind the category name within brackets, 
 * @param string $css_id The CSS id for the list
 * @param string $css_class_active The css class for the active menu item
 *
 * @return string
 */
function printAllNewsCategories($newsindex='All news', $counter=TRUE, $css_id='',$css_class_active='') {
	global $_zp_loggedin;
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_class_active != "") { $css_class_active = " class='".$css_class_active."'"; }
	$categories = getAllCategories();
	if(($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS | VIEWALL_RIGHTS))) {
		$published = "all";
	} else {
		$published = "published";
	}
	echo "<ul $css_id>";
	if(!empty($newsindex)) {
		if(is_NewsCategory() OR is_NewsArticle() OR !is_News() OR is_NewsArchive()) {
			echo "<li><a href=\"".getNewsIndexURL()."\" title=\"".strip_tags(htmlspecialchars($newsindex))."\">".htmlspecialchars($newsindex)."</a>";
		} else {
			echo "<li $css_class_active>".htmlspecialchars($newsindex);
		}
		if($counter AND !getOption("zenpage_combinews")) {
			echo " (".countArticles("",$published).")";
		}
		echo "</li>\n";
	}
	if(count($categories) != 0) {
		foreach($categories as $category) {
			$catname = htmlspecialchars(get_language_string($category['cat_name']));
			$catcount = countArticles($category['cat_link'],$published);
			if($counter) {
				$count = " (".$catcount.")";
			}
			if($catcount != 0) {
				if(getCurrentNewsCategoryID() === $category['id']) {
					echo "<li><span $css_class_active>".$catname."</span>".$count;
				} else {
					echo "<li><a href=\"".getNewsCategoryURL($category['cat_link'])."\" title=\"".$catname."\">".$catname."</a>".$count;
				}
				echo "</li>\n";
			}
		}
	}
	echo "</ul>\n";
}


/**
 * Gets the latest news either only news articles or with the latest images or albums
 * 
 * NOTE: Latest images and albums require Zenphoto's image_album_statistic plugin
 *
 * @param int $number The number of news items to get
 * @param string $option "none" for only news articles
 * 											 "with_latest_images" for news articles with the latest images by id
 * 											 "with_latest_images_date" for news articles with the latest images by date
 * 											 "with_latest_images_mtime" for news articles with the latest images by mtime (upload date)
 * 											 "with_latest_albums" for news articles with the latest albums by id
 * 											 "with_latestupdated_albums" for news articles with the latest updated albums
 * @param string $category Optional news articles by category (only "none" option"
 * @return array
 */
function getLatestNews($number=5,$option='none', $category='') {
	global $_zp_current_zenpage_news;
	if(!empty($category) AND $option="none") {
		$latest = getNewsArticles($number,$category);
	} else {
		$latest = getNewsArticles($number);
	}
	$counter = "";
	foreach($latest as $news) {
		$counter++;
		$article = new ZenpageNews($news['titlelink']);
		$latestnews[$counter] = array(
					"id" => $article->getID(),
					"title" => $article->getTitle(), 
					"titlelink" => $article->getTitlelink(),
				  "category" => $article->getCategories($article->getID()),
					"content" => $article->getContent(),
					"date" => $article->getDateTime(),
				  "thumb" => "",
					"filename" => ""
		);
	}
	$latest = $latestnews;
	if($option === "with_latest_images" OR $option === "with_latest_images_date") {
		switch($option) {
			case "with_latest_images":
				$images = getImageStatistic($number, "latest");
				break;
			case "with_latest_images_date":
				$images = getImageStatistic($number, "latest-date");
				break;
			case "with_latest_images_mtime":
				$images = getImageStatistic($number, "latest-mtime");
				break;
		}
		$latestimages = array();
		$counter = "";
		foreach($images as $image) {
			$counter++;
			$latestimages[$counter] = array(
					"id" => $image->get("id"),
					"title" => $image->getTitle(), 
					"titlelink" => $image->getImageLink(),
				  "category" => $image->getAlbum(),
					"content" => $image->getDesc(),
					"date" => $image->getDateTime(),
				  "thumb" => $image->getThumb(),
					"filename" => $image->getFileName()
			);
		}
		//$latestimages = array_merge($latestimages, $item);
		$latest = array_merge($latest, $latestimages);
		$latest = sortMultiArray($latest,"date","desc",true,false);
	}
	if($option === "with_latest_albums" OR $option === "with_latestupdated_albums") {
		switch($option) {
			case "with_latest_albums":
				$albums = getAlbumStatistic($number, "latest");
				break;
			case "with_latestupdated_albums":
				$albums = getAlbumStatistic($number, "latest");
				break;
		}
		$latestalbums = array();
		$counter = "";
		foreach($albums as $album) {
			$counter++;
			$tempalbum = new Album($_zp_gallery, $album['folder']);
			$tempalbumthumb = $tempalbum->getAlbumThumbImage();
			$latestalbums[$counter] = array(
					"id" => $tempalbum->getAlbumID(),
					"title" => $tempalbum->getTitle(), 
					"titlelink" => $tempalbum->getFolder(),
					"category" => "",
					"content" => $tempalbum->getDesc(),
					"date" => $tempalbum->getDateTime(),
					"thumb" => $tempalbumthumb->getThumb(),
					"filename" => ""
			);
		}
		//$latestalbums = array_merge($latestalbums, $item);
		$latest = array_merge($latestnews, $latestalbums);
		$latest = sortMultiArray($latest,"date","desc",true,false);
	}
	return $latest;
}




/**
 * Prints the latest news either only news articles or with the latest images or albums as a unordered html list
 * 
 * NOTE: Latest images and albums require the image_album_statistic plugin
 *
 * @param int $number The number of news items to get
 * @param string $option "none" for only news articles
 * 											 "with_latest_images" for news articles with the latest images by id
 * 											 "with_latest_images_date" for news articles with the latest images by date
 * 											 "with_latest_images_mtime" for news articles with the latest images by mtime (upload date)
 * 											 "with_latest_albums" for news articles with the latest albums by id
 * 											 "with_latestupdated_albums" for news articles with the latest updated albums
 * @param string $category Optional news articles by category (only "none" option"
 * @param bool $showdate If the date should be shown
 * @param bool $showcontent If the content should be shown
 * @param int $contentlength The lengths of the content
 * @param bool $showcat If the categories should be shown
 * @return string
 */
function printLatestNews($number=5,$option='with_latest_images', $category='', $showdate=true, $showcontent=true, $contentlength=70, $showcat=true){
	global $_zp_gallery, $_zp_current_zenpage_news;
	$latest = getLatestNews($number,$option,$category);
	echo "\n<ul id=\"latestnews\">\n";
	$count = "";
	foreach($latest as $item) {
		$count++;
		$category = ""; 
		$categories = "";
		//get the type of the news item
		if(empty($item['thumb'])) {
			$title = htmlspecialchars($item['title']);
			$link = getNewsURL($item['titlelink']);
			$count2 = 0;
			$newsobj = new ZenpageNews($item['titlelink']);
			$category = $newsobj->getCategories();
			foreach($category as $cat){
				$count2++;
				if($count2 != 1) {
					$categories = $categories.", ";
				}
				$categories = $categories.get_language_string($cat['cat_name']);
			} 
			$thumb = "";
			$content = strip_tags($item['content']);
			$type = "news";
		} else {
			if($option === "with_latest_images" OR $option === "with_latest_images_date") {
				$categories = $item['category']->getTitle();
				$title = htmlspecialchars($item['title']);
				$link = $item['titlelink'];
				$content = $item['content'];
				$thumb = "<a href=\"".$link."\" title=\"".strip_tags(htmlspecialchars($title))."\"><img src=\"".$item['thumb']."\" alt=\"".strip_tags($title)."\" /></a>\n";
				$type = "image";
			}
			if($option === "with_latest_albums" OR $option === "with_latestupdated_albums") {
				//$image = newImage(new Album($_zp_gallery, $item['categorylink']), $item['titlelink']);
				$category = $item['titlelink'];
				$categories = "";
				$link = $item['titlelink'];
				$title = htmlspecialchars($item['title']);
				$thumb = "<a href=\"".$link."\" title=\"".$title."\"><img src=\"".$item['thumb']."\" alt=\"".strip_tags($title)."\" /></a>\n";
				$content = $item['content'];
				$type = "album";
			}
		}
		echo "<li>";
		if(!empty($thumb)) { 
			echo $thumb; 
		}
		echo "<h3><a href=\"".$link."\" title=\"".strip_tags(htmlspecialchars($title,ENT_QUOTES))."\">".htmlspecialchars($title)."</a></h3>\n";;
	  if($showdate) {
			if($option === "with_latest_image_date" AND $type === "image") {
				$date = zpFormattedDate(getOption('date_format'),$item['date']);
			} else {
				$date = zpFormattedDate(getOption('date_format'),strtotime($item['date']));
			}
			echo "<p class=\"latestnews-date\">". $date."</p>\n";
		}
		if($showcontent) {
			echo "<p class=\"latestnews-desc\">".truncate_string($content, $contentlength)."</p>\n";
		}
		if($showcat AND $type != "album") {
			echo "<p class=\"latestnews-cats\">(".$categories.")</p>\n";
		}
		echo "</li>\n";
		if($count === $number) {
			break;
		}
	}
	echo "</ul>\n";
}


function getMostPopularItems($number=10, $option="all") {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_pages;
	if($option === "all" OR $option === "news") {
		$articles = query_full_array("SELECT id, title, titlelink, hitcounter FROM " . prefix('zenpage_news')." ORDER BY hitcounter DESC LIMIT $number");
		$counter = "";
		$poparticles = array();
		foreach ($articles as $article) {
		$counter++;
			$poparticles[$counter] = array(
					"id" => $article['id'],
					"title" => htmlspecialchars(get_language_string($article['title'])), 
					"titlelink" => getNewsURL($article['titlelink']),
				  "hitcounter" => $article['hitcounter'],
					"type" => "News"
			);
		}	
		$mostpopular = $poparticles;
	}
	if($option === "all" OR $option === "categories") {
		$categories = query_full_array("SELECT id, cat_name as title, cat_link as titlelink, hitcounter FROM " . prefix('zenpage_news_categories')." ORDER BY hitcounter DESC LIMIT $number");
		$counter = "";
		$popcats = array();
		foreach ($categories as $cat) {
		$counter++;
			$popcats[$counter] = array(
					"id" => $cat['id'],
					"title" => htmlspecialchars(get_language_string($cat['title'])), 
					"titlelink" => getNewsCategoryURL($cat['titlelink']),
				  "hitcounter" => $cat['hitcounter'],
					"type" => "Category"
			);
		}		
		$mostpopular = $popcats;
	}
	if($option === "all" OR $option === "pages") {
		$pages = query_full_array("SELECT id, title, titlelink, hitcounter FROM " . prefix('zenpage_pages')." ORDER BY hitcounter DESC LIMIT $number");
		$counter = "";
		$poppages = array();
		foreach ($pages as $page) {
			$counter++;
			$poppages[$counter] = array(
					"id" => $cat['id'],
					"title" => htmlspecialchars(get_language_string($page['title'])), 
					"titlelink" => getPageLinkURL($page['titlelink']),
				  "hitcounter" => $page['hitcounter'],
					"type" => "Page"
			);
		}
		$mostpopular = $poppages;
	}
	if($option === "all") {
		$mostpopular = array_merge($poparticles,$popcats,$poppages);
	}
	$mostpopular = sortMultiArray($mostpopular,"hitcounter","desc",true,false);
	return $mostpopular;
}


function printMostPopularItems($number=10, $option="all",$showhitcount=true) {
	$mostpopular = getMostPopularItems($number,$option);
	echo "<ul id='zenpagemostpopular'>";
	foreach($mostpopular as $item) {
		echo "<li><a href='".$item['titlelink']."' title='".strip_tags($item['title'])."'><h3>".$item['title']." <small>[".$item['type']."]";
		if($showhitcount) {
		echo " (".$item['hitcounter'].")</small>";
		}
		echo "</h3></a>";
	}
	echo "</ul>";
}

/************************************************/
/* News article URL functions
/************************************************/

/**
 * Returns the full path to a news category
 * 
 * @param string $catlink The category link of a category
 *
 * @return string
 */
function getNewsCategoryURL($catlink='') {
	return rewrite_path_zenpage(getNewsBaseURL()."/category/".urlencode($catlink),getNewsBaseURL()."&amp;category=".urlencode($catlink));
}


/**
 * Prints the full link to a news category
 * 
 * @param string $before If you want to print text before the link
 * @param string $catlink The category link of a category
  *
 * @return string
 */
function printNewsCategoryURL($before='',$catlink='') {
	if (!empty($catlink)) {
		echo "<a href=\"".getNewsCategoryURL($catlink)."\" title=\"".htmlspecialchars(getCategoryTitle($catlink))."\">".$before.htmlspecialchars(getCategoryTitle($catlink))."</a>";
	}
}


/**
 * Returns the full path of the news index page (news page 1)
 *
 * @return string
 */
function getNewsIndexURL() {
	if(getOption('zenpage_zp_index_news')) {
		return getGalleryIndexURL(false);
	} else {
		return rewrite_path(urlencode(ZENPAGE_NEWS), "/index.php?p=".ZENPAGE_NEWS);
	}
}


/**
 * Prints the full link of the news index page (news page 1)
 *
 * @param string $name The linktext
 * @param string $before The text to appear before the link text
 * @return string
 */
function printNewsIndexURL($name='', $before='') {
	echo $before."<a href=\"".getNewsIndexURL()."\" title=\"".strip_tags(htmlspecialchars($name))."\">".htmlspecialchars($name)."</a>";
}


/**
 * Returns the base /news or index.php?p=news url 
 *
 * @return string
 */
function getNewsBaseURL() {
	return rewrite_path(urlencode(ZENPAGE_NEWS), "/index.php?p=".urlencode(ZENPAGE_NEWS));
}


/**
 * Returns partial path of news category
 *
 * @return string
 */
function getNewsCategoryPath() {
	return rewrite_path_zenpage("/category/","&amp;category=");
}

/**
 * Returns partial path of news date archive
 *
 * @return string
 */
function getNewsArchivePath() {
	return rewrite_path_zenpage("/archive/","&amp;date=");
}


/**
 * Returns partial path of news article title
 *
 * @return string
 */
function getNewsTitlePath() {
	return rewrite_path_zenpage("/","&amp;title=");
}


/**
 * Returns partial path of a news page number path
 *
 * @return string
 */
function getNewsPagePath() { 
	return rewrite_path_zenpage("/","&amp;page=");
}


/**
 * Returns the url to a news article
 * 
 * @param string $titlelink The titlelink of a news article
 *
 * @return string
 */
function getNewsURL($titlelink='') {
	if(!empty($titlelink)) {
		$path = getNewsBaseURL().getNewsTitlePath().urlencode($titlelink);
		return $path;
	}
}


/**
 * Prints the url to a news article
 * 
 * @param string $titlelink The titlelink of a news article
 *
 * @return string
 */
function printNewsURL($titlelink='') {
	echo getNewsURL($titlelink);
}


/************************************************************/
/* News index / category / date archive pagination functions
 /***********************************************************/


/**
 * News cat path only for use in the news article pagination
 * 
 * @return string
 */
function getNewsCategoryPathNav() {
	if (isset($_GET['category'])) {
		$newscatpath = getNewsCategoryPath().urlencode(sanitize($_GET['category']));
	}	else {
		$newscatpath = "";
	}
	return $newscatpath;
}


/**
 * news archive path only for use in the news article pagination
 * 
 * @return string
 */
function getNewsArchivePathNav() {
	if (isset($_GET['date'])) {
		$archivepath = getNewsArchivePath().sanitize($_GET['date']);
	}	else {
		$archivepath = "";
	}
	return $archivepath;
}


/**
 * Returns the url to the previous news page
 * 
 * @return string
 */
function getPrevNewsPageURL() {
	$page = getCurrentNewsPage();
	if($page != 1) {
		if (is_News() AND $page == 2 AND getOption("zenpage_zp_index_news") AND !is_NewsCategory() AND !is_NewsArchive()) {
			return getGalleryIndexURL();
		} else  {
	 		return getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().($page - 1);
		} 
		
	} else {
		return false;
	}
}


/**
 * Prints the link to the previous news page
 * 
 * @param string $prev The linktext
 * @param string $class The CSS class for the disabled link
 * 
 * @return string
 */
function printPrevNewsPageLink($prev='&laquo; prev',$class='disabledlink') {
	$page = getCurrentNewsPage();
	if(getPrevNewsPageURL()) {
		echo "<a href='".getPrevNewsPageURL()."' title='".gettext("Prev page")." ".($page - 1)."' >".$prev."</a>\n";
	} else {
		echo "<span class=\"$class\">".$prev."</span>\n";
	}
}


/**
 * Returns the url to the next news page
 * 
 * @return string
 */
function getNextNewsPageURL() {
	global $_zp_zenpage_total_pages;
	$page =  getCurrentNewsPage();
	$total_pages = $_zp_zenpage_total_pages;;
	if ($page != $total_pages)	{
		return getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().($page + 1);
	} else {
		return false;
	}
}


/**
 * Prints the link to the next news page
 * 
 * @param string $next The linktext
 * @param string $class The CSS class for the disabled link
 * 
 * @return string
 */
function printNextNewsPageLink($next='next &raquo;', $class='disabledlink') {
	$page = getCurrentNewsPage();
	if (getNextNewsPageURL())	{
		echo "<a href='".getNextNewsPageURL()."' title='".gettext("Next page")." ".($page + 1)."'>".$next."</a>\n";
	} else {
		echo "<span class=\"$class\">".$next."</span>\n";
	}
}

/**
 * Prints the page number list for news page navigation 
 * 
 * @param string $class The CSS class for the disabled link
 * 
 * @return string
 */
function printNewsPageList($class='pagelist') {
	printNewsPageListWithNav("", "", false, $class);
}


/**
 * Prints the full news page navigation with prev/next links and the page number list
 * 
 * @param string $next The next page link text
 * @param string $prev The prev page link text
 * @param bool $nextprev If the prev/next links should be printed
 * @param string $class The CSS class for the disabled link
 * 
 * @return string
 */
function printNewsPageListWithNav($next='next &raquo;', $prev='&laquo; prev', $nextprev=true, $class='pagelist') {
	global $_zp_zenpage_total_pages;
	//echo "total pages: ". $_zp_current_zenpage_news->total_pages; // for debugging
	$total = $_zp_zenpage_total_pages;
	$current = getCurrentNewsPage();

	if($total > 1) {
		echo "<ul class=\"$class\">";
		if($nextprev) {
			echo "<li class=\"prev\">"; printPrevNewsPageLink($prev); echo "</li>";
		}
		$j=max(1, min($current-3, $total-6));
		if ($j != 1) {
			echo "\n <li>";
			echo "<a href=\"".getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().max($j-4,1)."\">...</a>";
			echo '</li>';
		}
		for ($i=$j; $i <= min($total, $j+6); $i++) {
			if($i == $current) {
				echo "<li>".$i."</li>\n";
			} else if ($i === 1 AND getOption("zenpage_zp_index_news") AND !is_NewsCategory() AND !is_NewsArchive()) {
				echo "<li><a href='".getGalleryIndexURL()."' title='".gettext("Page")." ".$i."'>".$i."</a></li>\n";
			} else {
				echo "<li><a href='".getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().$i."' title='".gettext("Page")." ".$i."'>".$i."</a></li>\n";
			}
		}
		if ($i <= $total) {
			echo "\n <li>";
			echo "<a href=\"".getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().min($j+10,$total)."\">...</a>";
			echo '</li>';
		}

		if($nextprev) {
			echo "<li class=\"next\">"; printNextNewsPageLink($next); echo "</li>";
		}
		echo "</ul>";
	}
}


/************************************************************************/
/* Single news article pagination functions (previous and next article)
/************************************************************************/


/**
 * Returns the title and the titlelink of the next or previous article in single news article pagination as an array
 * Returns false if there is none (or option is empty)
 * 
 * NOTE: This is not available if using the CombiNews feature 
 * 
 * @param string $option "prev" or "next"
 * 
 * @return mixed
 */
function getNextPrevNews($option='') {
	global $_zp_current_zenpage_news, $_zp_loggedin;
	$article_url = array();
	if(!getOption("zenpage_combinews")) {
		if(($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
			$published = "all";
		} else {
			$published = "published";
		}
		$current = 0;
		if(!empty($option)) {
			$all_articles = getNewsArticles("","",$published);
			$count = 0;
			foreach($all_articles as $article) {
				$newsobj = new ZenpageNews($article['titlelink']);
				$count++;
				$title[$count] = $newsobj->getTitle();
				$titlelink[$count] = $newsobj->getTitlelink();
				if($titlelink[$count] === $_GET['title']){
					$current = $count;
				}
			}
			switch($option) {
				case "prev":
					$prev = $current - 1;
					if($prev > 0) {
						$articlelink = getNewsURL($title[$prev]);
						$articletitle = $title[$prev];
						$article_url = array("link" => getNewsURL($titlelink[$prev]), "title" => $title[$prev]);
					}
					break;
				case "next":
					$next = $current + 1;
					if($next <= $count){
						$articlelink = getNewsURL($title[$next]);
						$articletitle = $title[$next];
						$article_url = array("link" => getNewsURL($titlelink[$next]), "title" => $title[$next]);
					}
					break;
			}
			return $article_url;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Returns the title and the titlelink of the next article in single news article pagination as an array
 * Returns false if there is none (or option is empty)
 * 
 * NOTE: This is not available if using the CombiNews feature 
 * 
 * @return mixed
 */
function getNextNewsURL() {
	return getNextPrevNews("next");
}


/**
 * Returns the title and the titlelink of the previous article in single news article pagination as an array
 * Returns false if there is none (or option is empty)
 * 
 * NOTE: This is not available if using the CombiNews feature 
 * 
 * @return mixed
 */
function getPrevNewsURL() {
	return getNextPrevNews("prev");
}


/**
 * Prints the link of the next article in single news article pagination if available
 * 
 * NOTE: This is not available if using the CombiNews feature 
 * 
 * @param string $next If you want to show something with the title of the article like a symbol
 * 
 * @return string
 */
function printNextNewsLink($next=" &raquo;") {
	$article_url = getNextPrevNews("next");
	if(array_key_exists('link', $article_url) && $article_url['link'] != "") {
		echo "<a href=\"".$article_url['link']."\" title=\"".strip_tags($article_url['title'])."\">".$article_url['title']."</a> ".$next;
	}
}


/**
 * Prints the link of the previous article in single news article pagination if available
 * 
 * NOTE: This is not available if using the CombiNews feature 
 * 
 * @param string $next If you want to show something with the title of the article like a symbol
 * 
 * @return string
 */
function printPrevNewsLink($prev="&laquo; ") {
	$article_url = getNextPrevNews("prev");
	if(array_key_exists('link', $article_url) && $article_url['link'] != "") {
		echo $prev." <a href=\"".$article_url['link']."\" title=\"".strip_tags($article_url['title'])."\">".$article_url['title']."</a>";
	}
}


/**********************************************************/
/* Codeblock functions - shared by Pages and News articles
 /**********************************************************/

/**
 * Gets the content of a codeblock for a page or news article.
 * Additionally you can print codeblocks of a published or unpublished specific page (not news artcle!) by request directly.
 * 
 * Note: Echoing this array's content does not execute it. Also no special chars will be escaped.
 * Use printCodeblock() if you need to execute script code.
 * 
 * Note: Meant for script code this field is not multilingual.
 * 
 * @param int $number The codeblock you want to get
 * @param string $titlelink The titlelink of a specific page you want to get the codeblock of (only for pages!)
 * 
 * @return string
 */
function getCodeblock($number='',$titlelink='') {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_page;
	$codeblock = "";
	if(is_News() AND isset($_GET['title'])) { // single news article or page
		$codeblock = unserialize(base64_decode($_zp_current_zenpage_news->getCodeblock()));
		$codeblock = strip($codeblock[$number]);
	}
	if(is_Pages()) { // single news article or page
		$codeblock = unserialize(base64_decode($_zp_current_zenpage_page->getCodeblock()));
		$codeblock = strip($codeblock[$number]);
	}
	if((is_News() AND !is_Pages()) AND !isset($_GET['title']) AND is_NewsType("news")) { // news loop
		$codeblock = unserialize(base64_decode($_zp_current_zenpage_news->getCodeblock()));
		$codeblock = strip($codeblock[$number]);
	} 
	if(!empty($titlelink)) { // direct page request
		$page = new ZenpagePage($titlelink);
		$codeblock = unserialize(base64_decode($page->getCodeblock()));
		$codeblock = strip($codeblock[$number]);
	} 
	return stripslashes($codeblock);
}


/**
 * Prints the content of a codeblock for a page or news article
 * 
 * NOTE: This executes PHP and JavaScript code if available
 * 
 * @param int $number The codeblock you want to get
 * @param string $titlelink The titlelink of a specific page you want to get the codeblock of (only for pages!)
 * 
 * @return string
 */
function printCodeblock($number='',$titlelink='') {
	$codeblock = getCodeblock($number,$titlelink);
	eval("?>$codeblock");
}


/************************************************/
/* Pages functions
 /************************************************/

/**
 * Returns title of a page
 * 
 * @return string
 */
function getPageTitle() {
	global $_zp_current_zenpage_page;
	if (is_Pages() OR is_Homepage()) {
		return $_zp_current_zenpage_page->getTitle();
	} 
}


/**
 * Prints the title of a page
 * 
 * @return string
 */
function printPageTitle($before='') {
	echo $before.htmlspecialchars(getPageTitle());
}

/**
 * Returns the raw title of a page.
 *
 * @return string
 */
function getBarePageTitle() {
	return html_encode(getPageTitle());
}

/**
 * Returns titlelink of a page
 * 
 * @return string
 */
function getPageTitleLink() {
	global $_zp_current_zenpage_page;
	if(is_Pages() OR is_Homepage()) {
		return $_zp_current_zenpage_page->getTitlelink();
	}
}


/**
 * Prints titlelink of a page
 * 
 * @return string
 */
function printPageTitleLink() {
	echo getPageTitleLink();
}


/**
 * Returns the id of a page
 * 
 * @return int
 */
function getPageID() {
	global $_zp_current_zenpage_page;
	if (is_Pages() OR is_Homepage()) {
		return $_zp_current_zenpage_page->getID();
	}
}


/**
 * Prints the id of a page
 * 
 * @return string
 */
function printPageID() {
	echo getPageID();
}


/**
 * Returns the id of the parent page of a page
 * 
 * @return int
 */
function getPageParentID() {
	global $_zp_current_zenpage_page;
	if (is_Pages() OR is_Homepage()) {
		return $_zp_current_zenpage_page->getParentid();
	}
}


/**
 * Returns the creation date of a page
 * 
 * @return string
 */
function getPageDate() {
	global $_zp_current_zenpage_page;
	if (is_Pages() OR is_Homepage()) {
		$d = $_zp_current_zenpage_page->getDatetime();
	}
	return zpFormattedDate(getOption('date_format'),strtotime($d)); 
}


/**
 * Prints the creation date of a page
 * 
 * @return string
 */
function printPageDate() {
	echo getPageDate();
}


/**
 * Returns the last change date of a page if available
 * 
 * @return string
 */
function getPageLastChangeDate() {
	global $_zp_current_zenpage_page;
	if (is_Pages() OR is_Homepage()) {
		$d = $_zp_current_zenpage_page->getLastchange();
	}
	if(!empty($d)) {
		return zpFormattedDate(getOption('date_format'),strtotime($d)); 
	}
}


/**
 * Prints the last change date of a page
 * 
 * @param string $before The text you want to show before the link
 * @return string
 */
function printPageLastChangeDate() {
	echo htmlspecialchars($before).getPageLastChangeDate();
}


/**
 * Returns page content either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even unpublished page ($published = false) as a gallery description or on another custom page for example
 * 
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set set this to false if you want to call an unpublished page's content. True is default
 * 
 * @return mixed
 */
function getPageContent($titlelink='',$published=true) {
	global $_zp_current_zenpage_page;
	if ((is_Pages() OR is_Homepage()) AND empty($titlelink)) {
		return $_zp_current_zenpage_page->getContent();
	} 
	// print content of a page directly on a normal zenphoto theme page or any other page for example
	if(!empty($titlelink)) {
		$page = new ZenpagePage($titlelink);
		if($page->getShow() === "1" OR ($page->getShow() != "1" AND $published === false)) {
			return 	$page->getContent();
		}
	}
	if (!is_Pages() AND empty($titlelink)) {
		return false;
	} 
}

/**
 * Print page content either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even unpublished page ($published = false) as a gallery description or on another custom page for example
 * 
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set set this to false if you want to call an unpublished page's content. True is default
 * @return mixed
 */
function printPageContent($titlelink='',$published=true) {
	echo getPageContent($titlelink,$published);
}


/**
 * Returns page extra content either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even unpublished page ($published = false) as a gallery description or on another custom page for example
 * 
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set set this to false if you want to call an unpublished page's extra content. True is default
 * @return mixed
 */
function getPageExtraContent($titlelink='',$published=true) {
	global $_zp_current_zenpage_page;
	if ((is_Pages() OR is_Homepage()) AND empty($titlelink)) {
		return $_zp_current_zenpage_page->getExtracontent();
	} 
	// print content of a page directly on a normal zenphoto theme page for example
	if(!empty($titlelink)) {
		$page = new ZenpagePage($titlelink);
		if($page->getShow() === "1" OR ($page->getShow() != "1" AND $published === false)) {
			return $page->getExtracontent();
		}
	}
	if (!is_Pages() AND empty($titlelink)) {
		return false;
	} 
}


/**
 * Prints page extra content if on a page either of the current page or if requested by titlelink directly. If not both return false
 * Set the titlelink of a page to call a specific even unpublished page ($published = false) as a gallery description or on another custom page for example
 * 
 * @param string $titlelink the titlelink of the page to print the content from
 * @param bool $published If titlelink is set set this to false if you want to call an unpublished page's extra content. True is default
 * @return mixed
 */
function printPageExtraContent($titlelink='',$published=true) {
	echo getPageExtraContent($titlelink,$published);
}


/**
 * Returns the author of a page 
 * 
 * @param bool $fullname True if you want to get the full name if set, false if you want the login/screenname
 * 
 * @return string
 */
function getPageAuthor($fullname=false) {
	if(is_Pages() OR is_Homepage()) {
		return getAuthor($fullname);
	}
}


/**
 * Prints the author of a page
 * 
 * @param bool $fullname True if you want to get the full name if set, false if you want the login/screenname
 * @return string
 */
function printPageAuthor($fullname=false) {
	if (getNewsTitle()) {
		echo getPageAuthor($fullname);
	}
}


/**
 * Returns the sortorder of a page
 * 
 * @return string
 */
function getPageSortorder() {
	global  $_zp_current_zenpage_page;
	if (is_Pages() OR is_Homepage()) {
		return $_zp_current_zenpage_page->getSortOrder();
	}
}



/**
 * Returns path to the pages.php page
 * 
 * @return string
 */
function getPageLinkPath() {
	return rewrite_path(ZENPAGE_PAGES."/", "/index.php?p=".ZENPAGE_PAGES."&amp;title=");
}


/**
 * Returns full path to a specific page
 * 
 * @return string
 */
function getPageLinkURL($titlelink) {
	return getPageLinkPath().$titlelink;
}


/**
 * Prints full path to a specific page
 * 
 * @return string
 */
function printPageLinkURL($titlelink) {
	echo getPageLinkURL($titlelink);
}





/**
 * Prints excerpts of the direct sub pages (1 level) of a page for a kind of overview. The setup is:
 * <div class='pageexcerpt'>
 * <h4>page title</h3>
 * <p>page content excerpt</p>
 * <p>read more</p>
  * </div>
 * 
 * @param int $excerptlength The length of the page content, if nothing specifically set, the plugin option value for 'news article text length' is used
 * @param string $readmore The text for the link to the full page. If empty the read more setting from the options is used.
 * @param string $shortenindicator The optional placeholder that indicates that the content is shortened, if this is not set the plugin option "news article text shorten indicator" is used.
 * @return string
 */
function printSubPagesExcerpts($excerptlength='', $readmore='', $shortenindicator='') {
	global  $_zp_current_zenpage_page, $_zp_loggedin;
	if(empty($readmore)) {
		$readmore = getOption("zenpage_read_more");
	} 
	if(empty($shortenindicator)) {
		$shortenindicator = getOption("zenpage_textshorten_indicator");
	}
	if(($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
		$published = FALSE;
	} else {
		$published = TRUE;
	}
	$pages = getPages($published);
	$subcount = 0;
	if(empty($excerptlength)) {
		$excerptlength = getOption("zenpage_text_length");
	}
	foreach($pages as $page) {
		$pageobj = new ZenpagePage($page);
		if($pageobj->getParentid() === getPageID()) {
			$subcount++;
			$pagetitle = $pageobj->getTitle();
			$pagecontent = $pageobj->getContent();
			if(strlen($pagecontent) > $excerptlength) {
				$pagecontent = shortenContent($pagecontent, $excerptlength, $shortenindicator. 
						" <a href=\"".getPageLinkURL($page['titlelink'])."\" title=\"".strip_tags($pagetitle)."\">".$readmore."</a>\n");
			}
			echo "\n<div class='pageexcerpt'>\n";
			echo "<h4><a href=\"".getPageLinkURL($page['titlelink'])."\" title=\"".strip_tags($pagetitle)."\">".$pagetitle."</a></h4>";
			echo $pagecontent; 
			echo "</div>\n";
		}
	}
}


/**
 * Prints the parent pages breadcrumb navigation for the current page
 *
 * @param string $before Text to place before the breadcrumb item
 * @param string $after Text to place after the breadcrumb item
 */
function printParentPagesBreadcrumb($before='', $after='') {
	$parentid = getPageParentID();
	$parentpages = getParentPages($parentid);
	foreach($parentpages as $parentpage) {
		$parentobj = new ZenpagePage($parentpage);
		echo $before."<a href='".htmlspecialchars(getPageLinkURL($parentpage))."'>".htmlspecialchars($parentobj->getTitle())."</a>".$after;
	}
}


/**
 * Prints a context sensitive menu of all pages up to the 4th sublevel as a unordered html list
 *
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"list-sub" only sub level pages
 * @param string $css_id CSS id of the top level list
 * @param string $css_class_topactive class of the active item in the top level list
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $$css_class_active CSS class of the sub level list(s)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" (default) if you don't use it, it is not printed then.
 * @return string
 */
function printPageMenu($option='list',$css_id='',$css_class_topactive='',$css_class='',$css_class_active='',$indexname='') {
	global $_zp_loggedin, $_zp_gallery_page;

	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_class_topactive != "") { $css_class_topactive = " class='".$css_class_topactive."'"; }
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($css_class_active != "") { $css_class_active = " class='".$css_class_active."'"; }
	
	if(($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS | VIEWALL_RIGHTS))) {
		$published = FALSE;
	} else {
		$published = TRUE;
	}
	$pages = getPages($published);
	if(is_Homepage()) {
		$currentpageorder = "0";
	} else {
		$currentpageorder = getPageSortorder();
	}
	$currentlevel = explode("-", $currentpageorder);
	if($option === "list" OR $option === "list-top") { 
		echo "<ul $css_id>\n";
	}
	if(!empty($indexname)) {
		if($_zp_gallery_page === "index.php") {
			echo "<li $css_class_topactive>".$indexname."</li>";
		} else {
			echo "<li><a href='".htmlspecialchars(getGalleryIndexURL())."' title='".html_encode($indexname)."'>".$indexname."</a></li>";
		}
	}
	foreach($pages as $page) {
		$count = 0;
		$pageobj = new ZenpagePage($page['titlelink']);
		if($pageobj->getParentID() == NULL OR $pageobj->getParentID() == "0") {
			$count++;
			if($option === "list" OR $option === "list-top") {
				createPageMenuLink($pageobj,$css_class_topactive);
			}
			if($option === "list" OR $option === "list-sub") {
					
				// sublevel 1 start
				$subcount1 = 0;
				foreach($pages as $sub1) {
					$sub1pageobj = new ZenpagePage($sub1['titlelink']);
					if(checkPageDisplayLevel($sub1pageobj,$pageobj,$currentpageorder,$currentlevel,1)) {
						$subcount1++;
						if($subcount1 === 1) {
							echo "\n<ul $css_class>\n";
						}
						createPageMenuLink($sub1pageobj,$css_class_active);

						// sublevel 2 start
						$subcount2 = 0;
						foreach($pages as $sub2) {
							$sub2pageobj = new ZenpagePage($sub2['titlelink']);
							if(checkPageDisplayLevel($sub2pageobj,$sub1pageobj,$currentpageorder,$currentlevel,2)) {
								$subcount2++;
								if($subcount2 === 1) {
									echo "\n<ul $css_class>\n";
								}
								createPageMenuLink($sub2pageobj,$css_class_active);

								// sublevel 3 start
								$subcount3 = 0;
								foreach($pages as $sub3) {
									$sub3pageobj = new ZenpagePage($sub3['titlelink']);
									if(checkPageDisplayLevel($sub3pageobj,$sub2pageobj,$currentpageorder,$currentlevel,3)) {
										$subcount3++;
										if($subcount3 === 1) {
											echo "\n<ul $css_class>\n";
										}
										createPageMenuLink($sub3pageobj,$css_class_active);

										// sublevel 4 start
										$subcount4 = 0;
										foreach($pages as $sub4) {
											$sub4pageobj = new ZenpagePage($sub4['titlelink']);
											if(checkPageDisplayLevel($sub4pageobj,$sub3pageobj,$currentpageorder,$currentlevel,4)) {
												$subcount4++;
												if($subcount4 === 1) {
													echo "\n<ul $css_class>\n";
												}
												createPageMenuLink($sub4pageobj,$css_class_active);
												if($subcount4 >= 1) {
													echo "</li>\n"; // sublevel 4 li end
												}
											}
										}
										if($subcount4 >= 1) { // sublevel 4 end
											echo "</ul>\n";
										}
										if($subcount3 >= 1) {
											echo "</li>\n"; // sublevel 3 li end
										}
									}
								}
								if($subcount3 >= 1) { // sublevel 3 end
									echo "</ul>\n";
								}
								if($subcount2 >= 1) {
									echo "</li>\n"; // sublevel 2 li end
								}
							}
						}
						if($subcount2 >= 1) { // sublevel 2 end
							echo "</ul>\n";
						}
						if($subcount1 >= 1) {
							echo "</li>\n"; // sublevel 1 li end
						}
					}
				}
				if($subcount1 >= 1 AND $option != "list-sub") { // sublevel 1 end
					echo "</ul>\n";
				}
			}
		} // if end for "list" or "list-sub" if
		if($count === 1 AND $option != "list-sub") {
			echo "</li>\n"; // top level li end
		}
	} // foreach end
	echo "</ul>\n";
}


/**
 * Helper function for printPageMenu() that checks the current page and level 
 * 
 * Not for standalone use.
 * 
 * @param string $page Array of the page in the list
 * @param string $parentpage Array of the parent page in the list
 * @param string $curentpageorder The current sort order of the page in the list
 * @param string $currentlevel The current level we are on
 * @param string $level The level the page is in (1-4)
 * @return string
 */
function checkPageDisplayLevel($pageobj,$parentpageobj,$currentpageorder,$currentlevel,$level) {
	$sortorder = $pageobj->getSortorder();
	$sublevel = explode("-",$sortorder );
	switch ($level) {
		case 1: // the array_key_exists calls where added to preven PHP notices
			if(array_key_exists('0',$sublevel) AND array_key_exists('0',$currentlevel)) {
				$pageorder = $sublevel[0];
				$currentlevelcheck = $currentlevel[0];
			} else {
				$pageorder = false;
				$currentlevelcheck = false;
			}
			break;
		case 2:
			if(array_key_exists('0',$sublevel) AND array_key_exists('1',$sublevel)
			AND array_key_exists('0',$currentlevel) AND array_key_exists('1',$currentlevel)) {
				$pageorder = $sublevel[0]."-".$sublevel[1];
				$currentlevelcheck = $currentlevel[0]."-".$currentlevel[1];
			} else {
				$pageorder = false;
				$currentlevelcheck = false;
			}
			break;
		case 3:
			if(array_key_exists('0',$sublevel) AND array_key_exists('1',$sublevel) AND array_key_exists('2',$sublevel)
			AND array_key_exists('0',$currentlevel) AND array_key_exists('1',$currentlevel) AND array_key_exists('2',$currentlevel)) {
				$pageorder = $sublevel[0]."-".$sublevel[1]."-".$sublevel[2];
				$currentlevelcheck = $currentlevel[0]."-".$currentlevel[1]."-".$currentlevel[2];
			} else {
				$pageorder = false;
				$currentlevelcheck = false;
			}
			break;
		case 4:
			if(array_key_exists('0',$sublevel) AND array_key_exists('1',$sublevel) AND array_key_exists('2',$sublevel) AND array_key_exists('3',$sublevel)
			AND array_key_exists('0',$currentlevel) AND array_key_exists('1',$currentlevel) AND array_key_exists('2',$currentlevel) AND array_key_exists('3',$currentlevel)) {
				$pageorder = $sublevel[0]."-".$sublevel[1]."-".$sublevel[2]."-".$sublevel[3];
				$currentlevelcheck = $currentlevel[0]."-".$currentlevel[1]."-".$currentlevel[2]."-".$currentlevel[3];
			} else {
				$pageorder = false;
				$currentlevelcheck = false;
			}
			break;
	}
	// if in parentalbum) OR (if in subalbum)
	if(( 
	$parentpageobj->getSortorder() === $pageorder
	AND count($sublevel) === $level+1
	AND $currentpageorder === $pageorder)
	OR
	(getPageID() != $parentpageobj->getID()
	AND $parentpageobj->getID() === $pageobj->getParentID()
	AND count($sublevel) === $level+1
	AND $currentlevelcheck === $parentpageobj->getSortorder()
	)) {
		return true;
	} else {
		return false;
	}
}


/**
 * Helper function for printPageMenu() that create the page entry for the list
 * 
 * Not for standalone use.
 * 
 * @param obj $pageobj Array of the page to create the link entry for
 * @param string $css_active class of the active item in the top level list (submitted by printPageMenu() as class='<class>')
 * @return string
 */
function createPageMenuLink($pageobj, $css_active='') {
	global $_zp_current_zenpage_page;
	if(!empty($css_active)) {
		$class = $css_active;
	} else {
		$class= "";
	}
	if(isset($_GET['title'])) {
		$gettitle = $_GET['title']; 
	} else {
		$gettitle = "";
	}
	if ($pageobj->getTitlelink() == $gettitle) {
		echo "<li $class>".$pageobj->getTitle(); 
	} else {
		echo "<li><a href=\"".getPageLinkURL($pageobj->getTitlelink())."\" title=\"".strip_tags($pageobj->getTitle())."\">".$pageobj->getTitle()."</a>";
	}
}


/************************************************/
/* Comments
/************************************************/

/**
 * Returns if comments are open for this news article or page (TRUE or FALSE)
 * 
 * @return bool
 */
function zenpageOpenedForComments() {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_page;
	if(is_NewsArticle()) {
		$obj = $_zp_current_zenpage_news;
	}
	if(is_Pages()) {
		$obj = $_zp_current_zenpage_page;
	}
	if($obj->get('commentson')) {
		return true;
	} else {
		return false;
	}
}


/**
 * Gets latest comments for news articles and pages
 *
 * @param int $number how many comments you want.
 * @param string $type 	"all" for all latest comments for all news articles and all pages
 * 											"news" for the lastest comments of one specific news article
 * 											"page" for the lastest comments of one specific page
 * @param int $itemID the ID of the element to get the comments for if $type != "all" 
 */
function getLatestZenpageComments($number,$type="all",$itemID="") {
	$itemID = sanitize_numeric($itemID);
	switch ($type) {
		case "news":
			$whereNews = " WHERE news.show = 1 AND news.id = ".$itemID." AND c.ownerid = news.id AND c.type = 'news' AND c.private = 0 AND c.inmoderation = 0";
			break;
		case "page":
			$wherePages = " WHERE pages.show = 1 AND pages.id = ".$itemID." AND c.ownerid = pages.id AND c.type = 'pages' AND c.private = 0 AND c.inmoderation = 0";
			break;
		case "all":
			$whereNews = " WHERE news.show = 1 AND c.ownerid = news.id AND c.type = 'news'";			
			$wherePages = " WHERE pages.show = 1 AND c.ownerid = pages.id AND c.type = 'pages'";
			break;
	}
	$comments_news = array();
	$comments_pages = array();
	if ($type === "all" OR $type === "news") {
		$comments_news = query_full_array("SELECT c.id, c.name, c.type, c.website,"
		. " c.date, c.anon, c.comment, news.title, news.titlelink FROM ".prefix('comments')." AS c, ".prefix('zenpage_news')." AS news "
		. $whereNews
		. " ORDER BY c.id DESC LIMIT $number");
	}
	if ($type === "all" OR $type === "page") {
		$comments_pages = query_full_array("SELECT c.id, c.name, c.type, c.website,"
		. " c.date, c.anon, c.comment, pages.title, pages.titlelink FROM ".prefix('comments')." AS c, ".prefix('zenpage_pages')." AS pages "
		. $wherePages
		. " ORDER BY c.id DESC LIMIT $number");
	}
	$comments = array();
	foreach ($comments_news as $comment) {
		$comments[$comment['id']] = $comment;
	}
	foreach ($comments_pages as $comment) {
		$comments[$comment['id']] = $comment;
	}
	krsort($comments);
	return array_slice($comments, 0, $number);
}


/**
 * Prints out latest comments for news articles and pages as a unordered list
 *
 * @param int $number how many comments you want.
 * @param string $shorten the number of characters to shorten the comment display
 * @param string $id The css id to style the list
 * @param string $type 	"all" for all latest comments for all news articles and all pages
 * 											"news" for the lastest comments of one specific news article
 * 											"page" for the lastest comments of one specific page
 * @param int $itemID the ID of the element to get the comments for if $type != "all"
 */
function printLatestZenpageComments($number, $shorten='123', $id='showlatestcomments',$type="all",$itemID="") {
	if(empty($class)) {
		$id = "";
	} else {
		$id = "id='".$classs." ";
	}
	$comments = getLatestZenpageComments($number,$type,$itemID);
	echo "<ul $id>\n";
	foreach ($comments as $comment) {
		if($comment['anon'] === "0") {
			$author = " ".gettext("by")." ".$comment['name'];
		} else {
			$author = "";
		}
		$date = $comment['date'];
		$title = get_language_string($comment['title']);
		$titlelink = $comment['titlelink'];
		$website = $comment['website'];
		$shortcomment = truncate_string($comment['comment'], $shorten);
		
		echo "<li><a href=\"".getNewsURL($titlelink)."\" class=\"commentmeta\">".$title.$author."</a><br />\n";
		echo "<span class=\"commentbody\">".$shortcomment."</span></li>";
	}
	echo "</ul>\n";
}


/************************************************/
/* RSS functions
/************************************************/

/**
 * Prints a RSS link
 *
 * @param string $option type of RSS: "News" feed for all news articles
 * 																		"Category" for only the news articles of the category that is currently selected
 * 																		"NewsWithImages" for all news articles and latest images
 * 																		"Comments" for all news articles and latest images
 * 																		"Comments-news" for comments of only the news article it is called from
 * 																		"Comments-page" for comments of only the page it is called from
 * @param string $categorylink The specific category you want a rss feed from (only 'Category' mode)
 * @param string $prev text to before before the link
 * @param string $linktext title of the link
 * @param string $next text to appear after the link
 * @param bool $printIcon print an RSS icon beside it? if true, the icon is zp-core/images/rss.gif
 * @param string $class css class
 * @param string $lang optional to display a feed link for a specific language (currently works for latest images only). Enter the locale like "de_DE" (the locale must be installed on your Zenphoto to work of course). If empty the locale set in the admin option or the language selector (getOption('locale') is used.
 */
function printZenpageRSSLink($option='News', $categorylink='', $prev='', $linktext='', $next='', $printIcon=true, $class=null, $lang='') {
	global $_zp_current_album;
	$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');
	if ($printIcon) {
		$icon = ' <img src="' . FULLWEBPATH . '/' . ZENFOLDER . '/images/rss.gif" alt="RSS Feed" />';
	} else {
		$icon = '';
	}
	if (!is_null($class)) {
		$class = 'class="' . $class . '"';
	}
	if(empty($lang)) {
		$lang = getOption("locale");
	} 
	if($option === "Category" AND empty($categorylink) AND issset($_GET['category'])) {
		$categorylink = "&amp;category=".sanitize($_GET['category']);
	} 
	if ($option === "Category" AND !empty($categorylink)) {
		$categorylink = "&amp;category=".sanitize($categorylink);
	} 
	if ($option === "Category" AND !empty($categorylink) AND !issset($_GET['category'])) {
		$categorylink = "";
	}
	switch($option) {
		case "News":
			echo $prev."<a $class href=\"http://".$host.WEBPATH."/rss-news.php?lang=".$lang."\" title=\"".gettext("News RSS")."\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "Category":
			echo $prev."<a $class href=\"http://".$host.WEBPATH."/rss-news.php?lang=".$lang.$categorylink."\" title=\"".gettext("News Category RSS")."\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "NewsWithImages":
			echo $prev."<a $class href=\"http://".$host.WEBPATH."/rss-news.php?withimages&amp;lang=".$lang."\" title=\"".gettext("News and Gallery RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "Comments":
			echo $prev."<a $class href=\"http://".$host.WEBPATH."/rss-news-comments.php?lang=".$lang."\" title=\"".gettext("Zenpage Comments RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "Comments-news":
			echo $prev."<a $class href=\"http://".$host.WEBPATH."/rss-news-comments.php?id=".getNewsID()."&amp;title=".urlencode(getNewsTitle())."&amp;type=news&amp;lang=".$lang."\" title=\"".gettext("News article comments RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "Comments-page":
			echo $prev."<a $class href=\"http://".$host.WEBPATH."/rss-news-comments.php?id=".getPageID()."&amp;title=".urlencode(getPageTitle())."&amp;type=page&amp;lang=".$lang."\" title=\"".gettext("Page Comments RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
	}
}


/**
 * Returns the RSS link for use in the HTML HEAD
 *
 * @param string $option type of RSS: "News" feed for all news articles
 * 																		"Category" for only the news articles of a specific category
 * 																		"NewsWithImages" for all news articles and latest images
 * @param string $categorylink The specific category you want a rss feed from (only 'Category' mode)
 * @param string $linktext title of the link
 * @param string $lang optional to display a feed link for a specific language (currently works for latest images only). Enter the locale like "de_DE" (the locale must be installed on your Zenphoto to work of course). If empty the locale set in the admin option or the language selector (getOption('locale') is used.
 *
 * @return string
 */
function getZenpageRSSHeaderLink($option='', $categorylink='', $linktext='', $lang='') {
	$host = htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');
	if(empty($lang)) {
		$lang = getOption("locale");
	}
	if($option === "Category" AND empty($categorylink) AND issset($_GET['category'])) {
		$categorylink = "&amp;category=".sanitize($_GET['category']);
	}
	if ($option === "Category" AND !empty($categorylink)) {
		$categorylink = "&amp;category=".sanitize($categorylink);
	}
	if ($option === "Category" AND !empty($categorylink) AND !issset($_GET['category'])) {
		$categorylink = "";
	}
	switch($option) {
		case "News":
			return "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars(strip_tags($linktext),ENT_QUOTES)."\" href=\"http://".$host.WEBPATH."/rss-news.php?lang=".$lang."\" />\n";
		case "Category":
			return "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars(strip_tags($linktext),ENT_QUOTES)."\" href=\"http://".$host.WEBPATH."/rss-news.php?lang=".$lang."&amp;category=".$categorylink."\" />\n";
		case "NewsWithImages":
			return "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars(strip_tags($linktext),ENT_QUOTES)."\" href=\"http://".$host.WEBPATH."/rss-news.php?withimages&amp;lang=".$lang."\" />\n";
	}
}


/**
 * Prints the RSS link for use in the HTML HEAD
 *
 * @param string $option type of RSS (News, NewsCategory, NewsWithLatestImages)
 * @param string $linktext title of the link
 *
 */
function printZenpageRSSHeaderLink($option, $linktext) {
	echo getZenpageRSSHeaderLink($option, $linktext);
}

/**
 * support to show an image from an album
 * The imagename is optional. If absent the album thumb image will be 
 * used and the link will be to the album. If present the link will be 
 * to the image.
 *
 * @param string $albumname
 * @param string $imagename
 * @param int $size the size to make the image. If omitted image will be 50% of 'image_size' option.
 */
function zenpageAlbumImage($albumname, $imagename=NULL, $size=NULL) {
	echo '<br />';
	$album = new Album($_zp_gallery, $albumname);
	if (is_null($size)) {
		$size = floor(getOption('image_size') * 0.5);
	}
	if (is_null($imagename)) {
		makeImageCurrent($album->getAlbumThumbImage());
		rem_context(ZP_IMAGE);
		echo '<a href="'.htmlspecialchars(getAlbumLinkURL($album)).'"   title="'.sprintf(gettext('View the %s album'), $albumname).'">';
		add_context(ZP_IMAGE);
		printCustomSizedImage(sprintf(gettext('View the photo album %s'), $albumname), $size);
		echo '</a>';
	} else {
		$image = newImage($album, $imagename);
		makeImageCurrent($image);
		echo '<a href="'.htmlspecialchars(getImageLinkURL($image)).'"   title="'.sprintf(gettext('View %s'), $imagename).'">';
		printCustomSizedImage(sprintf(gettext('View %s'), $imagename), $size);
		echo '</a>';
	}
	rem_context(ZP_IMAGE | ZP_ALBUM);
}
