<?php
/**
 * zenpage template functions
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins
 * @subpackage zenpage
 */


/************************************************/
/* ZENPAGE TEMPLATE FUNCTIONS
/************************************************/

require_once(dirname(__FILE__)."/zenpage-functions.php");
require_once(PHPScript('5.0.0', '_zenpage_template_functions.php'));

/************************************************/
/* General functions
/************************************************/

/**
 * Checks if the current page is in news context.
 *
 * @return bool
 */
function is_News() {
	global $_zp_current_zenpage_news;
	return(!is_null($_zp_current_zenpage_news));
}

/**
 * Checks if the current page is the news page in general.
 *
 * @return bool
 */
function is_NewsPage() {
	global $_zp_gallery_page;
	return $_zp_gallery_page == getOption("zenpage_news_page").'.php';
}

/**
 * Checks if the current page is a single news article page
 *
 * @return bool
 */
function is_NewsArticle() {
	return is_News() && in_context(ZP_ZENPAGE_SINGLE);
}


/**
 * Checks if the current page is a news category page
 *
 * @return bool
 */
function is_NewsCategory() {
	return in_context(ZP_ZENPAGE_NEWS_CATEGORY);
}


/**
 * Checks if the current page is a news archive page
 *
 * @return bool
 */
function is_NewsArchive() {
	return in_context(ZP_ZENPAGE_NEWS_DATE);
}


/**
 * Checks if the current page is a zenpage page
 *
 * @return bool
 */
function is_Pages() {
	return in_context(ZP_ZENPAGE_PAGE);
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
			$newstype = 'image';
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
	return getNewsType() == $type;
}


/**
 * CombiNews feature: A general wrapper function to check if this is a 'normal' news article (type 'news' or one of the zenphoto news types
 *
 * @return bool
 */
function is_GalleryNewsType() {
	return is_NewsType("image") || is_NewsType("video") || is_NewsType("album"); // later to be extended with albums, too
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
				if($admin['user'] == $obj->getAuthor()) {
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
 * Returns the number of news articles.
 *
 * When in search context this is the count of the articles found. Otherwise
 * it is the count of articles that match the criteria.
 *
 * @param bool $combi
 * @return int
 */
function getNumNews($combi=true) {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_news_restore, $_zp_zenpage_articles, $_zp_gallery, $_zp_current_search;
	if (in_context(ZP_SEARCH)) {
		processExpired('zenpage_news');
		$_zp_zenpage_articles = $_zp_current_search->getSearchNews();
	} else if(getOption('zenpage_combinews') AND !is_NewsCategory() AND !is_NewsArchive()) {
		$_zp_zenpage_articles = getCombiNews(getOption("zenpage_articles_per_page"));
	} else {
		$_zp_zenpage_articles = getNewsArticles(getOption("zenpage_articles_per_page"));
	}
	return count($_zp_zenpage_articles);
}

/**
 * Returns the next news item on a page.
 * sets $_zp_current_zenpage_news to the next news item
 * Returns true if there is an new item to be shown
 *
 * @param string $sortorder "date" for sorting by date (default)
 * 													"title" for sorting by title
 * 													This parameter is not used for date archives and CombiNews mode
 * @param string $sortdirection "desc" (default) for descending sort order
 * 													    "asc" for ascending sort order
 * 											        This parameter is not used for date archives and CombiNews mode
 *
 * @return bool
 */
function next_news($sortorder="date", $sortdirection="desc") {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_news_restore, $_zp_zenpage_articles, $_zp_gallery, $_zp_current_search;
	if(!checkforPassword()) {
		if (is_null($_zp_zenpage_articles)) {
			if (in_context(ZP_SEARCH)) {
				processExpired('zenpage_news');
				$_zp_zenpage_articles = $_zp_current_search->getSearchNews();
			} else if(getOption('zenpage_combinews') AND !is_NewsCategory() AND !is_NewsArchive()) {
				$_zp_zenpage_articles = getCombiNews(getOption("zenpage_articles_per_page"));
			} else {
				$_zp_zenpage_articles = getNewsArticles(getOption("zenpage_articles_per_page"),'',NULL,false,$sortorder,$sortdirection);
			}
			//print_r($_zp_zenpage_articles); // debugging
			if (empty($_zp_zenpage_articles)) { return false; }
			$_zp_current_zenpage_news_restore = $_zp_current_zenpage_news;
			$news = array_shift($_zp_zenpage_articles);
			//print_r($news); // debugging
			if (is_array($news)) {
				if(getOption('zenpage_combinews') AND array_key_exists("type",$news) AND array_key_exists("albumname",$news)) {
					if($news['type'] == "images") {
						$albumobj = new Album($_zp_gallery,$news['albumname']);
						$_zp_current_zenpage_news = newImage($albumobj,$news['titlelink']);
					} else if($news['type'] == "albums") {
						switch(getOption("zenpage_combinews_mode")) {
							case "latestimagesbyalbum-thumbnail":
							case "latestimagesbyalbum-thumbnail-customcrop":
							case "latestimagesbyalbum-sizedimage":
								$_zp_current_zenpage_news = new Album($_zp_gallery,$news['titlelink']);
								$_zp_current_zenpage_news->set('date', $news['date']); // in this mode this stores the date of the images to group not the album (inconvenient workaround...)
								break;
							default:
								$_zp_current_zenpage_news = new Album($_zp_gallery,$news['albumname']);
								break;
						}
					} else {
						$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
					}
				} else {
					$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
				}
			}
			save_context();
			add_context(ZP_ZENPAGE_NEWS_ARTICLE);
			return true;
		} else if (empty($_zp_zenpage_articles)) {
			$_zp_zenpage_articles = NULL;
			$_zp_current_zenpage_news = $_zp_current_zenpage_news_restore;
			restore_context();
			return false;
		} else {
			$news = array_shift($_zp_zenpage_articles);
			if (is_array($news)) {
				if(getOption('zenpage_combinews') AND array_key_exists("type",$news) AND array_key_exists("albumname",$news)) {
					if($news['type'] == "images") {
						$albumobj = new Album($_zp_gallery,$news['albumname']);
						$_zp_current_zenpage_news = newImage($albumobj,$news['titlelink']);
					} else if($news['type'] == "albums") {
						switch(getOption("zenpage_combinews_mode")) {
							case "latestimagesbyalbum-thumbnail":
							case "latestimagesbyalbum-thumbnail-customcrop":
							case "latestimagesbyalbum-sizedimage":
								$_zp_current_zenpage_news = new Album($_zp_gallery,$news['titlelink']);
								$_zp_current_zenpage_news->set('date', $news['date']); // in this mode this stores the date of the images to group not the album (inconvenient workaround...)
								break;
							default:
								$_zp_current_zenpage_news = new Album($_zp_gallery,$news['albumname']);
								break;
						}
					} else {
						$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
					}
				} else {
					$_zp_current_zenpage_news = new ZenpageNews($news['titlelink']);
				}
			}
			return true;
		}
	} // checkpassword if end
}

/**
 * Gets the id of a news article/item
 *
 * @return int
 */
function getNewsID() {
	global $_zp_current_zenpage_news;
	if(!is_null($_zp_current_zenpage_news)) {
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
	$shortenindicator = getOption('zenpage_textshorten_indicator');
	$singular = get_language_string(getOption('combinews-customtitle-singular'));
	$plural =	get_language_string(getOption('combinews-customtitle-plural'));
	$limit = getOption('combinews-customtitle-imagetitles');
	if (!is_null($_zp_current_zenpage_news)) {
		if(is_NewsType("album") && (getOption("zenpage_combinews_mode") == "latestimagesbyalbum-thumbnail"	|| getOption("zenpage_combinews_mode") == "latestimagesbyalbum-thumbnail-customcrop" || getOption("zenpage_combinews_mode") == "latestimagesbyalbum-sizedimage")) {
			$result = query_full_array("SELECT filename FROM ".prefix('images')." AS images WHERE date LIKE '".$_zp_current_zenpage_news->getDateTime()."%' AND albumid = ".$_zp_current_zenpage_news->id." ORDER BY date DESC");
			$imagetitles = "";
			$countresult = count($result);
			$count = "";
			if($limit != 0) {
				foreach($result as $image) {
					$imageobj = newImage($_zp_current_zenpage_news,$image['filename']);
					$imagetitles .= $imageobj->getTitle();
					$count++;
					$append = ', ';
					if($count < $countresult) {
						$imagetitles .= $append;
					}
					if($count >= $limit && $count != $countresult) {
						$imagetitles .= $shortenindicator;
						break;
					}
				}
			}
			return sprintf(ngettext($singular,$plural,$countresult),$countresult,$_zp_current_zenpage_news->getTitle(),$imagetitles);
		} else {
			return $_zp_current_zenpage_news->getTitle();
		}
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
	if(!is_null($_zp_current_zenpage_news)) {
		$type = getNewsType();
		switch($type) {
			case "album":
				$link = getNewsAlbumURL();
				break;
			case "news":
				$link = $_zp_current_zenpage_news->getTitlelink();
				break;
			case "image":
			case "video":
				$link = $_zp_current_zenpage_news->getImageLink();
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
			echo "<a href=\"".htmlspecialchars(getNewsTitleLink())."\" title=\"".getBareNewsTitle()."\">".$before.getNewsTitle()."</a>";
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
function getNewsContent($shorten=false, $shortenindicator='') {
	global $_zp_flash_player, $_zp_current_image, $_zp_gallery, $_zp_current_zenpage_news, $_zp_page;
	$excerptbreak = false;
	if(empty($shortenindicator)) {
		$shortenindicator = getOption("zenpage_textshorten_indicator");
	}
	if(!$shorten && !is_NewsArticle()) {
		$shorten = getOption("zenpage_text_length");
	}
	$articlecontent = "";
	$size = getOption("zenpage_combinews_imagesize");
	$width = getOption('combinews-thumbnail-width');
	$height = getOption('combinews-thumbnail-height');
	$cropwidth = getOption('combinews-thumbnail-cropwidth');
	$cropheight= getOption('combinews-thumbnail-cropheight');
	$cropx = getOption('combinews-thumbnail-cropx');
	$cropy = getOption('combinews-thumbnail-cropy');
	$mode = getOption("zenpage_combinews_mode");
	$newstype = getNewsType();
	switch($newstype) {
		case "news":
			$articlecontent = $_zp_current_zenpage_news->getContent();
			if($shorten && stristr($articlecontent,"<!-- pagebreak -->") !== FALSE) {
				$array = explode("<!-- pagebreak -->",$articlecontent);
				$articlecontent = shortenContent($array[0], strlen($array[0]), $shortenindicator);
				if ($shortenindicator && count($array) <= 1 || ($array[1] == '</p>' || trim($array[1]) =='')) {
					$articlecontent = str_replace($shortenindicator, '', $articlecontent);
				}
			} else {
				$articlecontent = getNewsContentShorten($articlecontent,$shorten,$shortenindicator);
			}
			break;
		case "image":
			switch($mode) {
				case "latestimages-sizedimage":
					$articlecontent = "<a href='".htmlspecialchars($_zp_current_zenpage_news->getImageLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'>";
					if (isImagePhoto($_zp_current_zenpage_news)) {
						$articlecontent .= "<img src='".htmlspecialchars($_zp_current_zenpage_news->getSizedImage($size))."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' />";
					} else {
						$articlecontent .= "<img src='".htmlspecialchars($_zp_current_zenpage_news->getThumbImageFile(WEBPATH))."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' />";
					}
					$articlecontent .= "</a>";
					break;
				case "latestimages-thumbnail":
					$articlecontent = "<a href='".htmlspecialchars($_zp_current_zenpage_news->getImageLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'><img src='".htmlspecialchars($_zp_current_zenpage_news->getThumb())."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' /></a><br />";
					break;
				case "latestimages-thumbnail-customcrop":
					$articlecontent = "<a href='".htmlspecialchars($_zp_current_zenpage_news->getImageLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'><img src='".htmlspecialchars($_zp_current_zenpage_news->getCustomImage(NULL, $width, $height, $cropwidth, $cropheight, $cropx, $cropy))."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' /></a><br />";
					break;
			}
			$articlecontent .= "<p>".getNewsContentShorten($_zp_current_zenpage_news->getDesc(),$shorten,$shortenindicator)."</p>";
			break;
		case "video":
			$articlecontent = getNewsVideoContent($_zp_current_zenpage_news,$shorten);
			break;
		case "album":
			$_zp_page = 1;
			$albumdesc = "<p>".getNewsContentShorten($_zp_current_zenpage_news->getDesc(),$shorten,$shortenindicator)."</p>";
			switch($mode) {
				case "latestalbums-sizedimage":
					$albumthumbobj = $_zp_current_zenpage_news->getAlbumThumbImage();
					$class = get_class($albumthumbobj);
					if($class != "_Image") {
						$imgurl = htmlspecialchars($_zp_current_zenpage_news->getAlbumThumb());
					} else {
						$imgurl = htmlspecialchars($albumthumbobj->getSizedImage($size));
					}
					$articlecontent = "<a href='".htmlspecialchars($_zp_current_zenpage_news->getAlbumLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'><img src='".$imgurl."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' /></a><br />".$albumdesc;
					break;
				case "latestalbums-thumbnail":
					$articlecontent = "<a href='".htmlspecialchars($_zp_current_zenpage_news->getAlbumLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'><img src='".htmlspecialchars($_zp_current_zenpage_news->getAlbumThumb())."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' /></a><br />".$albumdesc;
					break;
				case "latestalbums-thumbnail-customcrop":
					$articlecontent = "<a href='".htmlspecialchars($_zp_current_zenpage_news->getAlbumLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'><img src='".htmlspecialchars($_zp_current_zenpage_news->getCustomImage(NULL, $width, $height, $cropwidth, $cropheight, $cropx, $cropy))."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' /></a><br />".$albumdesc;
					break;
				case "latestimagesbyalbum-thumbnail":
				case "latestimagesbyalbum-thumbnail-customcrop":
				case "latestimagesbyalbum-sizedimage":
					//echo "<a href='".htmlspecialchars($_zp_current_zenpage_news->getAlbumLink())."' title='".html_encode($_zp_current_zenpage_news->getTitle())."'><img src='".$_zp_current_zenpage_news->getAlbumThumb()."' alt='".html_encode($_zp_current_zenpage_news->getTitle())."' /></a><br />";
					$images = query_full_array("SELECT title, filename FROM ".prefix('images')." AS images WHERE date LIKE '".$_zp_current_zenpage_news->getDateTime()."%' AND albumid = ".$_zp_current_zenpage_news->id." ORDER BY date DESC");
					//echo "<pre>"; print_r($images); echo "</pre><br />";
					foreach($images as $image) {
						$imageobj = newImage($_zp_current_zenpage_news,$image['filename']);
						if(getOption('combinews-latestimagesbyalbum-imgdesc')) {
							$imagedesc = $imageobj->getDesc();
							$imagedesc = "<p>".getNewsContentShorten($imagedesc,$shorten,$shortenindicator)."</p>";
						}
						switch($mode) {
							case "latestimagesbyalbum-thumbnail":
								if(getOption('combinews-latestimagesbyalbum-imgtitle')) $articlecontent .= "<h4>".$imageobj->getTitle()."</h4>";
								$articlecontent .= "<a href='".htmlspecialchars($imageobj->getImageLink())."' title='".html_encode($imageobj->getTitle())."'><img src='".htmlspecialchars($imageobj->getThumb())."' alt='".html_encode($imageobj->getTitle())."' /></a>".$imagedesc;
								break;
							case "latestimagesbyalbum-thumbnail-customcrop":
								if(getOption('combinews-latestimagesbyalbum-imgtitle')) $articlecontent .= "<h4>".$imageobj->getTitle()."</h4>";
								if(isImageVideo($imageobj)) {
									$articlecontent .= getNewsVideoContent($imageobj,$shorten).$imagedesc;
								} else {
									$articlecontent .= "<a href='".htmlspecialchars($imageobj->getImageLink())."' title='".html_encode($imageobj->getTitle())."'><img src='".htmlspecialchars($imageobj->getCustomImage(NULL, $width, $height, $cropwidth, $cropheight, $cropx, $cropy))."' alt='".html_encode($imageobj->getTitle())."' /></a>".$imagedesc;
								}
								break;
							case "latestimagesbyalbum-sizedimage":
								if(getOption('combinews-latestimagesbyalbum-imgtitle')) $articlecontent .= "<h4>".$imageobj->getTitle()."</h4>";
								if(isImageVideo($imageobj)) {
									$articlecontent = getNewsVideoContent($imageobj).$imagedesc;
								} else {
									$articlecontent = "<a href='".htmlspecialchars($imageobj->getImageLink())."' title='".html_encode($imageobj->getTitle())."'><img src='".htmlspecialchars($imageobj->getSizedImage($size))."' alt='".html_encode($imageobj->getTitle())."' /></a>".$imagedesc;
								}
								break;
						} // switch "latest images by album end"
					} // foreach end
					break;
			} // switch "albums mode end"
	} // main switch end
	return $articlecontent;
}



/**
 * Prints the news article content. Note: TinyMCE used by Zenpage for news articles may already add a surrounding <p></p> to the content.
 *
 * If using the CombiNews feature this prints the thumbnail or sized image for a gallery item.
 * If using the 'CombiNews sized image' mode it shows movies directly and the description below.
 *
 * @param int $shorten $shorten The lengths of the content for the news main page for example (only for video/audio descriptions, not for normal image descriptions)
 */
function printNewsContent($shorten=false,$shortenindicator='') {
	global $_zp_flash_player, $_zp_current_image, $_zp_gallery, $_zp_current_zenpage_news, $_zp_page;
	echo getNewsContent($shorten,$shortenindicator);
}

/**
 * Helper function for getNewsContent to shorten the news article content or if using Zenpage CombiNews' the image/album description
 * If shorten is disable it sjust returns the string passed.
 *
 * @param string $articlecontent Then news article content or image/album description to shorten
 * @param bool $shorten true or false if the description to this object should be shortened.
 */
function getNewsContentShorten($articlecontent,$shorten,$shortenindicator) {
	if(!empty($shorten) && strlen($articlecontent) > $shorten) {
		return shortenContent($articlecontent,$shorten,$shortenindicator);
	} else {
		return $articlecontent;
	}
}

/**
 * Helper function for getNewsContent to get video/audio content if $imageobj is a video/audio object if using Zenpage CombiNews
 *
 * @param object $imageobj The object of an image
 * @param bool $shorten true or false if the description to this object should be shortened.
 */
function getNewsVideoContent($imageobj,$shorten=false) {
	global $_zp_flash_player, $_zp_current_image, $_zp_gallery, $_zp_page;
	$videocontent = "";
	$ext = strtolower(strrchr($imageobj->getFullImage(), "."));
	switch($ext) {
		case '.flv':
		case '.mp3':
		case '.mp4':
			if (is_null($_zp_flash_player)) {
				$videocontent = "<img src='" . WEBPATH . '/' . ZENFOLDER . "'/images/err-noflashplayer.gif' alt='".gettext('No flash player installed.')."' />";
			} else {
				$_zp_current_image = $imageobj;
				$videocontent = $_zp_flash_player->getPlayerConfig(getFullNewsImageURL(),getNewsTitle(),$_zp_current_image->get("id"));
			}
			$videocontent .= $_zp_current_image->getDesc();
			break;
		case '.3gp':
		case '.mov':
			$videocontent = $imageobj->getBody();
			$videocontent .= $imageobj->getDesc();
			break;
	}
	return $videocontent;
}

/**
 * Gets the extracontent of a news article if in single news articles view or returns FALSE
 *
 * @return string
 */
function getNewsExtraContent() {
	global $_zp_current_zenpage_news;
	if(is_News()) {
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
		$newsurl = htmlspecialchars(getNewsTitleLink());
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
		} else {
			return $_zp_current_zenpage_news->getTitle();
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
		} else {
			return $_zp_current_zenpage_news->getFolder();
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
	if(is_NewsType('image') || is_NewsType('video')) {
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
	Global $_zp_current_category;
	if(in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {
		return getCategoryTitle($_zp_current_category);
	}
	return false;
}


/**
 * Prints the currently selected news category
 *
 * @param string $before insert what you want to be show before it
 */
function printCurrentNewsCategory($before='') {
	if(in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {
		echo $before.getCurrentNewsCategory();
	}
}


/**
 * Gets the id of the current selected news category
 *
 * @return int
 */
function getCurrentNewsCategoryID() {
	if(in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {
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
	if(!is_null($_zp_current_zenpage_news) AND is_NewsType("news")) {
		$categories = $_zp_current_zenpage_news->getCategories();
		return $categories;
	}
	return false;
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
			echo  $before."<ul class=\"$class\">\n";
			$count = 0;
			foreach($categories as $cat) {
				$count++;
				$catname = get_language_string($cat['cat_name']);
				if($count >= $catcount) {
					$separator = "";
				}
				echo "<li><a href=\"".getNewsCategoryURL($cat['cat_link'])."\" title=\"".$catname."\">".$catname.'</a>'.$separator."</li>\n";
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
	return $count == 1;
}


/**
 * Gets the date of the current news article
 *
 * @return string
 */
function getNewsDate() {
	global $_zp_current_zenpage_news;
	if(!is_null($_zp_current_zenpage_news)) {
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
 * @param string $yearclass optional class for "year"
 * @param string $monthclass optional class for "month"
 * @param string $activeclass optional class for the currently active archive
 */
function printNewsArchive($class='archive', $yearclass='year', $monthclass='month', $activeclass="archive-active") {
	if (!empty($class)){ $class = "class=\"$class\""; }
	if (!empty($yearclass)){ $yearclass = "class=\"$yearclass\""; }
	if (!empty($monthclass)){ $monthclass = "class=\"$monthclass\""; }
	if (!empty($activeclass)){ $activeclass = "class=\"$activeclass\""; }
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
			if($nr != 1) { echo "</ul>\n</li>\n";}
			echo "<li $yearclass>$year\n<ul $monthclass>\n";
		}
		if(getCurrentNewsArchive('plain') == strftime('%Y-%m', strtotime($key))) {
			$active = $activeclass;
		} else {
			$active = "";
		}
		echo "<li $active><a href=\"".getNewsBaseURL().getNewsArchivePath().substr($key,0,7)."\" title=\"".$month." (".$val.")\" rel=\"nofollow\">$month ($val)</a></li>\n";
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
	global $_zp_post_date;
	if(in_context(ZP_ZENPAGE_NEWS_DATE)) {
		$archivedate = $_zp_post_date;
		if($mode == "formatted") {
		 $archivedate = strtotime($archivedate);
		 $archivedate = strftime($format,$archivedate);
		} 
		return $archivedate;
	} 
	return false;
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
	global $_zp_loggedin, $_zp_gallery_page, $_zp_gallery;
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_class_active != "") { $css_class_active = " class='".$css_class_active."'"; }
	$categories = getAllCategories();
	if(($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS | VIEW_ALL_RIGHTS))) {
		$published = "all";
		$pub = false;
	} else {
		$published = "published";
		$pub = true;
	}
	echo "<ul $css_id>";
	if(!empty($newsindex)) {
		if(($_zp_gallery_page == "news.php" OR (getOption("zenpage_zp_index_news") AND $_zp_gallery_page == "index.php")) AND !is_NewsCategory() AND !is_NewsArchive() AND !is_NewsArticle()) {
			echo "<li $css_class_active>".htmlspecialchars($newsindex);
		} else {
			echo "<li><a href=\"".getNewsIndexURL()."\" title=\"".strip_tags(htmlspecialchars($newsindex))."\">".htmlspecialchars($newsindex)."</a>";
		}
		if($counter) {
			if(getOption("zenpage_combinews")) {
				$totalcount = countCombiNews($pub);
			} else {
				$totalcount = countArticles("",$pub);
			}
			//$articlecount = countArticles("",$published);
			//$totalcount = $articlecount+$galleryitemcount;
			echo "<small> (".$totalcount.")</small>";
		}
		echo "</li>\n";
	}
	if(count($categories) != 0) {
		foreach($categories as $category) {
			$catname = htmlspecialchars(get_language_string($category['cat_name']));
			$catcount = countArticles($category['cat_link'],$published);
			if($counter) {
				$count = "<small> (".$catcount.")</small>";
			}
			if($catcount != 0) {
				if(getCurrentNewsCategoryID() == $category['id']) {
					echo "<li $css_class_active>".$catname." ".$count;
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
	if(!empty($category) AND $option =="none") {
		$latest = getNewsArticles($number,$category,NULL,true);
	} else {
		$latest = getNewsArticles($number,'',NULL,true);
	}
	$counter = "";
	foreach($latest as $news) {
		$counter++;
		$article = new ZenpageNews($news['titlelink']);
		$latestnews[$counter] = array(
					"id" => $article->getID(),
					"title" => $article->getTitle(),
					"titlelink" => $article->getTitlelink(),
					"category" => $article->getCategories(),
					"content" => $article->getContent(),
					"date" => $article->getDateTime(),
					"thumb" => "",
					"filename" => ""
		);
	}
	$latest = $latestnews;
	if($option == "with_latest_images" OR $option == "with_latest_images_date") {
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
		$latest = sortMultiArray($latest,"date",true);
	}
	if($option == "with_latest_albums" OR $option == "with_latestupdated_albums") {
		switch($option) {
			case "with_latest_albums":
				$albums = getAlbumStatistic($number, "latest");
				break;
			case "with_latestupdated_albums":
				$albums = getAlbumStatistic($number, "latestupdated");
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
		$latest = sortMultiArray($latest,"date",true);
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
	$latest = getLatestNews($number,$option,$category,true);
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
			if($option == "with_latest_images" OR $option == "with_latest_images_date") {
				$categories = $item['category']->getTitle();
				$title = htmlspecialchars($item['title']);
				$link = htmlspecialchars($item['titlelink']);
				$content = $item['content'];
				$thumb = "<a href=\"".$link."\" title=\"".strip_tags(htmlspecialchars($title))."\"><img src=\"".$item['thumb']."\" alt=\"".strip_tags($title)."\" /></a>\n";
				$type = "image";
			}
			if($option == "with_latest_albums" OR $option == "with_latestupdated_albums") {
				//$image = newImage(new Album($_zp_gallery, $item['categorylink']), $item['titlelink']);
				$category = $item['titlelink'];
				$categories = "";
				$link = htmlspecialchars($item['titlelink']);
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
			if($option == "with_latest_image_date" AND $type == "image") {
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
		if($count == $number) {
			break;
		}
	}
	echo "</ul>\n";
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
	return rewrite_path(getNewsBaseURL()."/category/".urlencode($catlink),getNewsBaseURL()."&amp;category=".urlencode($catlink),false);
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
 * Returns the full path of the news index page (news page 1) or if the "news on zp index" option is set a link to the gallery index.
 *
 * @return string
 */
function getNewsIndexURL() {
	if(getOption("zenpage_zp_index_news")) {
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
	return rewrite_path("/category/","&amp;category=",false);
}

/**
 * Returns partial path of news date archive
 *
 * @return string
 */
function getNewsArchivePath() {
	return rewrite_path("/archive/","&amp;date=",false);
}


/**
 * Returns partial path of news article title
 *
 * @return string
 */
function getNewsTitlePath() {
	return rewrite_path("/","&amp;title=",false);
}


/**
 * Returns partial path of a news page number path
 *
 * @return string
 */
function getNewsPagePath() {
	return rewrite_path("/","&amp;page=",false);
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
	Global $_zp_current_category;
	if (in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {
		return getNewsCategoryPath().urlencode($_zp_current_category);
	}
	return false;
}


/**
 * news archive path only for use in the news article pagination
 *
 * @return string
 */
function getNewsArchivePathNav() {
	global $_zp_post_date;
	if (in_context(ZP_ZENPAGE_NEWS_DATE)) {
		return getNewsArchivePath().$_zp_post_date;
	}
	return false;
}


/**
 * Returns the url to the previous news page
 *
 * @return string
 */
function getPrevNewsPageURL() {
	$page = getCurrentNewsPage();
	if($page != 1) {
		if(($page - 1) == 1) {
			if(is_NewsCategory()) {
				return getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().($page - 1);
			} else {
				return getNewsIndexURL();
			}
		} else {
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
	$page =  getCurrentNewsPage();
	$total_pages = ceil(getTotalArticles() / getOption("zenpage_articles_per_page"));
	if ($page != $total_pages) {
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
	$total = ceil(getTotalArticles() / getOption("zenpage_articles_per_page"));
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
			} else {
				if($i == 1 AND getOption("zenpage_zp_index_news")) {
					echo "<li><a href='".getNewsIndexURL()."' title='".gettext("Page")." ".$i."'>".$i."</a></li>\n";
				} else {
					echo "<li><a href='".getNewsBaseURL().getNewsCategoryPathNav().getNewsArchivePathNav().getNewsPagePath().$i."' title='".gettext("Page")." ".$i."'>".$i."</a></li>\n";
				}
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


function getTotalNewsPages() {
	if(getOption('zenpage_combinews') AND !is_NewsCategory() AND !is_NewsArchive()) {
		$articlecount = countCombiNews();
	} else {
		$articlecount = countArticles();
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
		$current = 0;
		if(!empty($option)) {
			$all_articles = getNewsArticles("","");
			$count = 0;
			foreach($all_articles as $article) {
				$newsobj = new ZenpageNews($article['titlelink']);
				$count++;
				$title[$count] = $newsobj->getTitle();
				$titlelink[$count] = $newsobj->getTitlelink();
				if($titlelink[$count] == $_zp_current_zenpage_news->getTitlelink()){
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
/* Functions - shared by Pages and News articles
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
function getCodeblock($number=0,$titlelink='') {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_page;
	$getcodeblock = '';
	if (empty($titlelink)) {
		if(is_News()) {
			if(get_class($_zp_current_zenpage_news) == "ZenpageNews") {
				$getcodeblock = $_zp_current_zenpage_news->getCodeblock();
			}
		}
		if(is_Pages()) {
			$getcodeblock = $_zp_current_zenpage_page->getCodeblock();
		}
	}	else { // direct page request
		$page = new ZenpagePage($titlelink);
		$getcodeblock = $page->getCodeblock();
	}
	if (empty($getcodeblock)) return '';
	$codeblock = unserialize($getcodeblock);
	return $codeblock[$number];
}


/**
 * Gets the statistic for pages, news articles or categories as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages, articles  and categories
 * 											 "news" for news articles
 * 											 "categories" for news categories
 * 											 "pages" for pages
 * @param string $mode "popular" most viewed for pages, news articles and categories
 * 										 "mostrated" for news articles and pages
 * 										 "toprated" for news articles and pages
 * @return array
 */
function getZenpageStatistic($number=10, $option="all",$mode="popular") {
	global $_zp_current_zenpage_news, $_zp_current_zenpage_pages;
	switch($option) {
		case "popular":
			$sortorder = "hitcounter"; break;
		case "mostrated":
			$sortorder = "total_votes"; break;
		case "toprated":
			$sortorder = "rating"; break;
		}
	if($option == "all" OR $option == "news") {
		$articles = query_full_array("SELECT id, title, titlelink, hitcounter, total_votes, rating FROM " . prefix('zenpage_news')." ORDER BY $sortorder DESC LIMIT $number");
		$counter = "";
		$statsarticles = array();
		foreach ($articles as $article) {
		$counter++;
			$statsarticles[$counter] = array(
					"id" => $article['id'],
					"title" => htmlspecialchars(get_language_string($article['title'])),
					"titlelink" => getNewsURL($article['titlelink']),
					"hitcounter" => $article['hitcounter'],
					"total_votes" => $article['total_votes'],
					"rating" => $article['rating'],
					"type" => "News"
			);
		}
		$stats = $statsarticles;
	}
	if(($option == "all" OR $option == "categories") AND ($mode != "mostrated" OR $mode != "toprated")) {
		$categories = query_full_array("SELECT id, cat_name as title, cat_link as titlelink, hitcounter FROM " . prefix('zenpage_news_categories')." ORDER BY $sortorder DESC LIMIT $number");
		$counter = "";
		$statscats = array();
		foreach ($categories as $cat) {
		$counter++;
			$statscats[$counter] = array(
					"id" => $cat['id'],
					"title" => htmlspecialchars(get_language_string($cat['title'])),
					"titlelink" => getNewsCategoryURL($cat['titlelink']),
					"hitcounter" => $cat['hitcounter'],
					"total_votes" => "",
					"rating" => "",
					"type" => "Category"
			);
		}
		$stats = $statscats;
	}
	if($option == "all" OR $option == "pages") {
		$pages = query_full_array("SELECT id, title, titlelink, hitcounter, total_votes, rating FROM " . prefix('zenpage_pages')." ORDER BY $sortorder DESC LIMIT $number");
		$counter = "";
		$statspages = array();
		foreach ($pages as $page) {
			$counter++;
			$statspages[$counter] = array(
					"id" => $cat['id'],
					"title" => htmlspecialchars(get_language_string($page['title'])),
					"titlelink" => getPageLinkURL($page['titlelink']),
					"hitcounter" => $page['hitcounter'],
					"total_votes" => $page['total_votes'],
					"rating" => $page['rating'],
					"type" => "Page"
			);
		}
		$stats = $statspages;
	}
	if($option == "all") {
		$stats = array_merge($statsarticles,$statscats,$statspages);
	}
	$stats = sortMultiArray($stats,$sortorder,true);
	return $stats;
}

/**
 * Prints the statistics Zenpage items as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param string $mode "popular" most viewed for pages, news articles and categories
 * 										 "mostrated" for news articles and pages
 * 										 "toprated" for news articles and pages
 * @param bool $showstats if the value should be shown
 */
function printZenpageStatistic($number=10, $option="all",$mode="popular",$showstats=true) {
	$stats = getZenpageStatistic($number, $option,$mode);
	echo "<ul id=$cssid>";
	foreach($stats as $item) {
		switch($mode) {
			case "popular":
				$cssid = "'zenpagemostpopular'";
				$statsvalue = $stats['hitcounter'];
				break;
			case "mostrated":
				$cssid ="'zenpagemostrated'";
				$statsvalue = $stats['total_votes'];
				break;
			case "toprated":
				$cssid ="'zenpagetoprated'";
				$statsvalue = $stats['rating'];
				break;
		}
		echo "<li><a href='".$item['titlelink']."' title='".strip_tags($item['title'])."'><h3>".$item['title']." <small>[".$item['type']."]";
		if($showhitcount) {
			echo " (".$statsvalue.")</small>";
		}
		echo "</h3></a>";
	}
	echo "</ul>";
}

/**
 * Prints the most popular pages, news articles and categories as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param bool $showstats if the votes should be shown
 */
function printMostPopularItems($number=10, $option="all",$showstats=true) {
	printZenpageStatistic($number, $option,"popular",$showstats);
}

/**
 * Prints the most rated pages and news articles as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param bool $showstats if the votes should be shown
 */
function printMostRatedItems($number=10, $option="all",$showstats=true) {
	printZenpageStatistic($number, $option,"mostrated",$showstats);
}

/**
 * Prints the top rated pages and news articles as an unordered list
 *
 * @param int $number The number of news items to get
 * @param string $option "all" pages and articles
 * 											 "news" for news articles
 * 											 "pages" for pages
 * @param bool $showstats if the votes should be shown
 */
function printTopRatedItems($number=10, $option="all",$showstats=true) {
	printZenpageStatistic($number, $option,"toprated",$showstats);
}




/************************************************/
/* Pages functions
/************************************************/
$_zp_zenpage_pagelist = NULL;

/**
 * Returns a count of the pages
 *
 * If in search context, the count is the number of items found. Otherwise it is
 * the total number of pages.
 *
 * @return int
 */
function getNumPages() {
	global $_zp_zenpage_pagelist, $_zp_current_search;
	processExpired('zenpage_pages');
	if (in_context(ZP_SEARCH)) {
		$_zp_zenpage_pagelist = $_zp_current_search->getSearchPages();
		$count = count($_zp_zenpage_pagelist);
	} else {
		$result = query('SELECT COUNT(*) FROM '.prefix('zenpage_pages'));
		$count = mysql_result($result, 0);
	}
	return $count;
}

/**
 * Returns a page from the search list
 *
 * @return object
 */
function next_page() {
	global $_zp_zenpage_pagelist,$_zp_current_search,$_zp_current_zenpage_page;
	if (!in_context(ZP_SEARCH)) {
		return false;
	}
	save_context();
	add_context(ZP_ZENPAGE_PAGE);
	if (is_null($_zp_zenpage_pagelist)) {
		processExpired('zenpage_pages');
		$_zp_zenpage_pagelist = $_zp_current_search->getSearchPages();
	}
	if (empty($_zp_zenpage_pagelist)) {
		$_zp_zenpage_pagelist = NULL;
		restore_context();
		return false;
	}
	$page = array_shift($_zp_zenpage_pagelist);
	$_zp_current_zenpage_page = new ZenpagePage($page['titlelink']);
	return true;
}

/**
 * Returns title of a page
 *
 * @return string
 */
function getPageTitle() {
	global $_zp_current_zenpage_page;
	if (!is_null($_zp_current_zenpage_page)) {
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
	if(is_Pages()) {
		return $_zp_current_zenpage_page->getTitlelink();
	}
}


/**
 * Prints titlelink of a page
 *
 * @return string
 */
function printPageTitleLink() {
	global $_zp_current_zenpage_page;
	echo '<a href="'.getPageLinkURL(getPageTitleLink()).'" title="'.getBarePageTitle().'">'.getPageTitle().'</a>';
}


/**
 * Returns the id of a page
 *
 * @return int
 */
function getPageID() {
	global $_zp_current_zenpage_page;
	if (is_Pages()) {
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
	if (is_Pages()) {
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
	if (!is_null($_zp_current_zenpage_page)) {
		$d = $_zp_current_zenpage_page->getDatetime();
		return zpFormattedDate(getOption('date_format'),strtotime($d));
	}
	return false;
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
	if (!is_null($_zp_current_zenpage_page)) {
		$d = $_zp_current_zenpage_page->getLastchange();
		return zpFormattedDate(getOption('date_format'),strtotime($d));
	}
	return false;
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
	if (is_Pages() AND empty($titlelink)) {
		return $_zp_current_zenpage_page->getContent();
	}
	// print content of a page directly on a normal zenphoto theme page or any other page for example
	if(!empty($titlelink)) {
		$page = new ZenpagePage($titlelink);
		if($page->getShow() OR (!$page->getShow() AND !$published)) {
			return 	$page->getContent();
		}
	}
	return false;
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
	if (is_Pages() AND empty($titlelink)) {
		return $_zp_current_zenpage_page->getExtracontent();
	}
	// print content of a page directly on a normal zenphoto theme page for example
	if(!empty($titlelink)) {
		$page = new ZenpagePage($titlelink);
		if($page->getShow() OR (!$page->getShow() AND !$published)) {
			return $page->getExtracontent();
		}
	}
	return false;
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
	if(is_Pages()) {
		return getAuthor($fullname);
	}
	return false;
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
	if (is_Pages()) {
		return $_zp_current_zenpage_page->getSortOrder();
	}
	return false;
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
		$pageobj = new ZenpagePage($page['titlelink']);
		if($pageobj->getParentid() == getPageID()) {
			$subcount++;
			$pagetitle = $pageobj->getTitle();
			$pagecontent = $pageobj->getContent();
			$readmorelink = " <a href=\"".getPageLinkURL($page['titlelink'])."\" title=\"".strip_tags($pagetitle)."\">".$readmore."</a>\n";
			if(stristr($pagecontent,"<!-- pagebreak -->") !== FALSE) {
				$array = explode("<!-- pagebreak -->",$pagecontent);
				$shortenindicator .= $readmorelink;
				$pagecontent = shortenContent($array[0], strlen($array[0]), $shortenindicator);
				if ($shortenindicator && count($array) <= 1 || ($array[1] == '</p>' || trim($array[1]) =='')) {
					$pagecontent = str_replace($shortenindicator, '', $pagecontent);
				} 
			} else if(strlen($pagecontent) > $excerptlength) {
				$pagecontent = shortenContent($pagecontent, $excerptlength, $shortenindicator.$readmorelink);
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
 * Prints a context sensitive menu of all pages as a unordered html list
 *
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"omit-top" only sub level pages
 * 												"list-sub" lists only the current pages direct offspring
 * @param string $css_id CSS id of the top level list
 * @param string $css_class_topactive class of the active item in the top level list
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $$css_class_active CSS class of the sub level list(s)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" (default) if you don't use it, it is not printed then.
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @return string
 */
function printPageMenu($option='list',$css_id='',$css_class_topactive='',$css_class='',$css_class_active='',$indexname='',$showsubs=0) {
	global $_zp_loggedin, $_zp_gallery_page, $_zp_current_zenpage_page;
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_class_topactive != "") { $css_class_topactive = " class='".$css_class_topactive."'"; }
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($css_class_active != "") { $css_class_active = " class='".$css_class_active."'"; }
	if ($showsubs === true) $showsubs = 9999999999;

	if(($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS | VIEW_ALL_RIGHTS))) {
		$published = FALSE;
	} else {
		$published = TRUE;
	}
	// don't highlight current pages or foldout if in search mode as next_page() sets page context
	if(in_context(ZP_SEARCH)) {
		$css_class_topactive != "";
		$css_class_active != "";
		rem_context(ZP_ZENPAGE_PAGE);
	}
	$pages = getPages($published);
	if (count($pages)==0) return; // nothing to do
	echo "<ul$css_id>";
	if(!empty($indexname)) {
		if($_zp_gallery_page == "index.php") {
			echo "<li $css_class_topactive>".$indexname."</li>";
		} else {
			echo "<li><a href='".htmlspecialchars(getGalleryIndexURL(true))."' title='".html_encode($indexname)."'>".$indexname."</a></li>";
		}
	}
	$baseindent = max(1,count(explode("-", getPageSortorder())));
	$indent = 1;
	$open = array($indent=>0);
	$pageid = getPageID();
	$parents = array(NULL);
	$order = explode('-', getPageSortorder());
	$mylevel = count($order);
	$myparentsort = array_shift($order);

	for ($c=0; $c<=$mylevel; $c++) {
		$parents[$c] = NULL;
	}
	foreach ($pages as $page) {
		$pageobj = new ZenpagePage($page['titlelink']);
		$level = max(1,count(explode('-', $pageobj->getSortOrder())));
		$process = (($level <= $showsubs && $option == "list") // user wants all the pages whose level is <= to the parameter
								|| ($option == 'list' || $option == 'list-top') && $level==1 // show the top level
								|| (($option == 'list' || ($option == 'omit-top' && $level>1))
										&& (($pageobj->getID() == $pageid) // current page
											|| ($pageobj->getParentID()==$pageid) // offspring of current page
											|| ($level <= $mylevel && $level > 1 && strpos($pageobj->getSortOrder(), $myparentsort) === 0)) // direct ancestor
									)
								|| ($option == 'list-sub'
										&& ($pageobj->getParentID()==$pageid) // offspring of the current page
									 )
								);
		if ($process) {
			if ($level > $indent) {
				echo "\n".str_pad("\t",$indent,"\t")."<ul$css_class>\n";
				$indent++;
				$parents[$indent] = NULL;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				$parents[$indent] = NULL;
				while ($indent > $level) {
					if ($open[$indent]) {
						$open[$indent]--;
						echo "</li>\n";
					}
					$indent--;
					echo str_pad("\t",$indent,"\t")."</ul>\n";
				}
			} else { // level == indent, have not changed
				if ($open[$indent]) { // level = indent
					echo str_pad("\t",$indent,"\t")."</li>\n";
					$open[$indent]--;
				} else {
					echo "\n";
				}
			}

			if ($open[$indent]) { // close an open LI if it exists
				echo "</li>\n";
				$open[$indent]--;
			}

			echo str_pad("\t",$indent-1,"\t");
			$open[$indent]++;
			$parents[$indent] = $pageobj->getID();
			if($level == 1) { // top level
				$class = $css_class_topactive;
			} else {
				$class = $css_class_active;
			}
			if(!is_null($_zp_current_zenpage_page)) {
				$gettitle = $_zp_current_zenpage_page->getTitlelink();
			} else {
				$gettitle = "";
			}
			if ($pageobj->getTitlelink() == $gettitle && !in_context(ZP_SEARCH)) {
				$current = $class;
			} else {
				$current = "";
			}
			echo "<li><a $current href=\"".getPageLinkURL($pageobj->getTitlelink())."\" title=\"".strip_tags($pageobj->getTitle())."\">".$pageobj->getTitle()."</a>";
		}
	}
	// cleanup any hanging list elements
	while ($indent > 1) {
		if ($open[$indent]) {
			echo "</li>\n";
			$open[$indent]--;
		}
		$indent--;
		echo str_pad("\t",$indent,"\t")."</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
		$open[$indent]--;
	} else {
		echo "\n";
	}

	echo "</ul>\n";
}

/**
 * If the titlelink is valid this will setup for the page
 * Returns true if page is setup and valid, otherwise returns false
 *
 * @param string $titlelink The page to setup
 *
 * @return bool
 */
function checkForPage($titlelink) {
	if(!empty($titlelink)) {
		$sql = 'SELECT `id` FROM '.prefix('zenpage_pages').' WHERE `titlelink`="'.$titlelink.'"';
		$result = query_single_row($sql);
		if (is_array($result)) {
			zenpage_setup_page($titlelink);
			return true;
		}
	}
	return false;
}

/**
 * Sets all the required items to make the titlelink the current pages page
 *
 * @param string $titlelink the titlelink of th epage to setup.
 */
function zenpage_setup_page($titlelink) {
	global $_zp_gallery_page, $_zp_current_zenpage_page;
	$_zp_gallery_page = ZENPAGE_PAGES.'.php';
	add_context(ZP_ZENPAGE_PAGE);
	$_zp_current_zenpage_page = new ZenpagePage($titlelink);
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
	return $obj->get('commentson');
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
	if ($type == "all" OR $type == "news") {
		$comments_news = query_full_array("SELECT c.id, c.name, c.type, c.website,"
		. " c.date, c.anon, c.comment, news.title, news.titlelink FROM ".prefix('comments')." AS c, ".prefix('zenpage_news')." AS news "
		. $whereNews
		. " ORDER BY c.id DESC LIMIT $number");
	}
	if ($type == "all" OR $type == "page") {
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
		$id = "id='".$id." ";
	}
	$comments = getLatestZenpageComments($number,$type,$itemID);
	echo "<ul $id>\n";
	foreach ($comments as $comment) {
		if($comment['anon']) {
			$author = "";
		} else {
			$author = " ".gettext("by")." ".$comment['name'];
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
	if($option == "Category" AND empty($categorylink) AND issset($_GET['category'])) {
		$categorylink = "&amp;category=".sanitize($_GET['category']);
	}
	if ($option == "Category" AND !empty($categorylink)) {
		$categorylink = "&amp;category=".sanitize($categorylink);
	}
	if ($option == "Category" AND !empty($categorylink) AND !issset($_GET['category'])) {
		$categorylink = "";
	}
	switch($option) {
		case "News":
			if (getOption('RSS_articles')) {
				echo $prev."<a $class href=\"".WEBPATH."/rss-news.php?lang=".$lang."\" title=\"".gettext("News RSS")."\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			}
			break;
		case "Category":
			if (getOption('RSS_articles')) {
				echo $prev."<a $class href=\"".WEBPATH."/rss-news.php?lang=".$lang.$categorylink."\" title=\"".gettext("News Category RSS")."\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			}
			break;
		case "NewsWithImages":
			if (getOption('RSS_articles')) {
				echo $prev."<a $class href=\"".WEBPATH."/rss-news.php?withimages&amp;lang=".$lang."\" title=\"".gettext("News and Gallery RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			}
			break;
		case "Comments":
			if (getOption('RSS_article_comments')) {
				echo $prev."<a $class href=\"http://".WEBPATH."/rss-news-comments.php?lang=".$lang."\" title=\"".gettext("Zenpage Comments RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			}
			break;
		case "Comments-news":
			if (getOption('RSS_article_comments')) {
				echo $prev."<a $class href=\"http://".WEBPATH."/rss-news-comments.php?id=".getNewsID()."&amp;title=".urlencode(getNewsTitle())."&amp;type=news&amp;lang=".$lang."\" title=\"".gettext("News article comments RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			}
			break;
		case "Comments-page":
			if (getOption('RSS_article_comments')) {
				echo $prev."<a $class href=\"http://".WEBPATH."/rss-news-comments.php?id=".getPageID()."&amp;title=".urlencode(getPageTitle())."&amp;type=page&amp;lang=".$lang."\" title=\"".gettext("Page Comments RSS")."\"  rel=\"nofollow\">".$linktext."$icon</a>".$next;
			}
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
	(secureServer()) ? $serverprotocol = "https://" : $serverprotocol = "http://";
	if(empty($lang)) {
		$lang = getOption("locale");
	}
	if($option == "Category" AND empty($categorylink) AND issset($_GET['category'])) {
		$categorylink = "&amp;category=".sanitize($_GET['category']);
	}
	if ($option == "Category" AND !empty($categorylink)) {
		$categorylink = "&amp;category=".sanitize($categorylink);
	}
	if ($option == "Category" AND !empty($categorylink) AND !issset($_GET['category'])) {
		$categorylink = "";
	}
	switch($option) {
		case "News":
			if (getOption('RSS_articles')) {
				return "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars(strip_tags($linktext),ENT_QUOTES)."\" href=\"http://".$serverprotocol.$host.WEBPATH."/rss-news.php?lang=".$lang."\" />\n";
			}
		case "Category":
			if (getOption('RSS_articles')) {
				return "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars(strip_tags($linktext),ENT_QUOTES)."\" href=\"http://".$serverprotocol.$host.WEBPATH."/rss-news.php?lang=".$lang."&amp;category=".$categorylink."\" />\n";
			}
		case "NewsWithImages":
			if (getOption('RSS_articles')) {
				return "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars(strip_tags($linktext),ENT_QUOTES)."\" href=\"http://".$serverprotocol.$host.WEBPATH."/rss-news.php?withimages&amp;lang=".$lang."\" />\n";
			}
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
	global $_zp_gallery;
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
?>