<?php
/**
 * General functions used both on the admin backend and theme
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins
 * @subpackage zenpage
 */


/**
 * Some global variable setup
 *
 */
$_zp_zenpage_all_categories = NULL; // for use by getAllCategories() only!


/**
 * Un-publishes pages/news whose expiration date has been reached
 *
 */
function processExpired($table) {
	$expire = date('Y-m-d H:i:s');
	query('update'.prefix($table).'SET `show`=0 WHERE `date`<="'.$expire.'"'.
		' AND `expiredate`<="'.$expire.'"'.
		' AND `expiredate`!="0000-00-00 00:00:00"'.
		' AND `expiredate`!=NULL');
}

/************************************/
/* general page functions   */
/************************************/

/**
	 * Gets the titlelink and sort order for all pages or published ones.
	 * 
	 * NOTE: Since this function only returns titlelinks for use with the object model it does not exclude pages that are password protected
	 *
	 * @param bool $published TRUE for published or FALSE for all pages including un-published
	 * @return array
	 */
	function getPages($published=NULL) {
		global $_zp_zenpage_all_pages;
		processExpired('zenpage_pages');
		if (is_null($published)) {
			if(zp_loggedin(ZENPAGE_PAGES_RIGHTS)) {
				$published = FALSE;
			} else {
				$published = TRUE;
			}
		}
		if($published) {
			$show = " WHERE `show` = 1 AND date <= '".date('Y-m-d H:i:s')."'";
		} else {
			$show = '';
		}
		$_zp_zenpage_all_pages = NULL; // Disabled cache var for now because it does not return un-publishded and published if logged on index.php somehow if logged in.
		if(is_null($_zp_zenpage_all_pages)) {
			$_zp_zenpage_all_pages  = query_full_array("SELECT * FROM ".prefix('zenpage_pages').$show." ORDER by `sort_order`");
			return $_zp_zenpage_all_pages;
		} else {
			return $_zp_zenpage_all_pages;
		}
	}


/**
 * Gets the parent pages recursivly to the page whose parentid is passed
 *
 * @param int $parentid The parentid of the page to get the parents of
 * @param bool $initparents If the
 * @return array
 */
function getParentPages(&$parentid,$initparents=true) {
	global $parentpages;
	if($initparents) {
		$parentpages = array();
	}
	$allpages = getPages();
	$currentparentid = $parentid;
	foreach($allpages as $page) {
		$pageobj = new ZenpagePage($page['titlelink']);
		if($pageobj->getID() == $currentparentid) {
			$pageobjtitlelink = $pageobj->getTitlelink();
			$pageobjparentid = $pageobj->getParentID();
			array_unshift($parentpages,$pageobjtitlelink);
			getParentPages($pageobjparentid,false);
		}
	}
	return $parentpages;
}

/**
 * Checks if the page is itself password protected or is inheriting protection from a parent pages.
 * NOTE: This function does only check if a password is set not if it has been entered! Use checkPagePassword() for that.
 *
 * @param obj $pageobj Optional page object to test directly, otherwise the current page is checked if available.
 */
function isProtectedPage($pageobj=NULL) {
	global $_zp_current_zenpage_page;
	if (is_null($pageobj)) $pageobj = $_zp_current_zenpage_page;
	$hint = $show = '';
	return checkPagePassword($pageobj, $hint, $show) != 'zp_unprotected';
}

/************************************/
/* general news article functions   */
/************************************/

/**
	 * Gets news articles titlelink either all or by category or by archive date.
	 * 
	 * NOTE: Since this function only returns titlelinks for use with the object model it does not exclude articles that are password protected via a category
	 * 
	 *
	 * @param int $articles_per_page The number of articles to get
	 * @param string $category The categorylink of the category
	 * @param string $published "published" for an published articles,
	 * 													"unpublished" for an unpublised articles,
	 * 													"sticky" for sticky articles,	
	 * 													"all" for all articles
	 * @param boolean $ignorepagination Since also used for the news loop this function automatically paginates the results if the "page" GET variable is set. To avoid this behaviour if using it directly to get articles set this TRUE (default FALSE)
	 * @param string $sortorder "date" for sorting by date (default)
	 * 													"title" for sorting by title
	 * 													This parameter is not used for date archives
	 * @param string $sortdirection "desc" (default) for descending sort order
	 * 													    "asc" for ascending sort order
	 * 											        This parameter is not used for date archives
	 * @param bool $sticky set to true to place "sticky" articles at the front of the list.
	 * @return array
	 */
	function getNewsArticles($articles_per_page='', $category='', $published=NULL,$ignorepagination=false,$sortorder="date", $sortdirection="desc",$sticky=true) {
		global $_zp_current_category, $_zp_post_date;
		processExpired('zenpage_news');
		if (is_null($published)) {
			if(zp_loggedin(ZENPAGE_NEWS_RIGHTS)) {
				$published = "all";
			} else {
				$published = "published";
			}
		}
		$show = "";
		$category = sanitize($category);
		$articles_per_page = sanitize_numeric($articles_per_page);
		if (!empty($category)) {
			$cat = " cat.cat_id = '".getCategoryID($category)."' AND cat.news_id = news.id ";
		} elseif(in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {
			$cat = " cat.cat_id = '".getCategoryID($_zp_current_category)."' AND cat.news_id = news.id ";
		} else {
			$cat ="";
		}
		if(in_context(ZP_ZENPAGE_NEWS_DATE)) {
			$postdate = $_zp_post_date;
		} else {
			$postdate = NULL;
		}
		$limit = getLimitAndOffset($articles_per_page,$ignorepagination);
		if ($sticky) {
			$sticky = 'sticky DESC,';
		}
	
		// sortorder and sortdirection (only used for all news articles and categories naturally)
		$sortorder = sanitize($sortorder);
		switch($sortorder) {
			case "date":
			default:
				$sort1 = "date";
			break;
			case "title":
				$sort1 = "title";
			break;
		}
		$sortdirection = sanitize($sortdirection);
		switch($sortdirection) {
			case "desc":
			default:
				$dir = "DESC";
			break;
			case "asc":
				$dir = "ASC";
				$sticky = false;	//makes no sense
			break;
		}
		/*** get articles by category ***/
		if (!empty($category) OR in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {

			switch($published) {
				case "published":
					$show = " AND `show` = 1 AND date <= '".date('Y-m-d H:i:s')."'";
					break;
				case "unpublished":
					$show = " AND `show` = 0 AND date <= '".date('Y-m-d H:i:s')."'";
					break;
				case 'sticky':
					$show = ' AND `sticky` <> 0';
					break;
				case "all":
					$show = "";
					break;
			}

			if(in_context(ZP_ZENPAGE_NEWS_DATE)) {
				$datesearch = " AND news.date LIKE '".$postdate."%' ";
				$order = " ORDER BY ".$sticky."news.date DESC";
			} else {
				$datesearch = "";
				$order = " ORDER BY ".$sticky."news.$sort1 $dir";
			}
			$sql = "SELECT news.titlelink FROM ".prefix('zenpage_news')." as news, ".prefix('zenpage_news2cat')." as cat WHERE".$cat.$show.$datesearch.$order.$limit;
			$result = query_full_array($sql);

			/***get all articles ***/
		} else {

			switch($published) {
				case "published":
					$show = " WHERE `show` = 1 AND date <= '".date('Y-m-d H:i:s')."'";
					break;
				case "unpublished":
					$show = " WHERE `show` = 0 AND date <= '".date('Y-m-d H:i:s')."'";
					break;
				case 'sticky':
					$show = ' WHERE `sticky` <> 0';
					break;
				case "all":
					$show = "";
					break;
			}
			if(in_context(ZP_ZENPAGE_NEWS_DATE)) {
				switch($published) {
					case "published":
						$datesearch = " AND date LIKE '$postdate%' ";
						break;
					case "unpublished":
						$datesearch = " WHERE date LIKE '$postdate%' ";
						break;
					case 'sticky':
						$show = ' WHERE `sticky` <> 0';
						break;
					case "all":
						$datesearch = " WHERE date LIKE '$postdate%' ";
						break;
				}
				$order = " ORDER BY $sticky date DESC";
			} else {
				$datesearch = "";
				$order = " ORDER BY ".$sticky.$sort1." ".$dir;
			}
			$sql = "SELECT titlelink FROM ".prefix('zenpage_news').$show.$datesearch." ".$order.$limit;
			$result = query_full_array($sql);
		}
		return $result;
	}


/**
	 * Counts news articles, either all or by category or archive date, published or un-published
	 *
	 * @param string $category The categorylink of the category to count
	 * @param string $published "published" for an published articles,
	 * 													"unpublished" for an unpublised articles,
	 * 													"all" for all articles
	 * @return array
	 */
	function countArticles($category='', $published='published') {
		global $_zp_post_date;
		if(zp_loggedin(ZENPAGE_NEWS_RIGHTS)) {
			$published = "all";
		} else {
			$published = "published";
		}
		$show="";
		if (empty($category)) {

			switch($published) {
				case "published":
					$show = " WHERE `show` = 1 AND date <= '".date('Y-m-d H:i:s')."'";
					break;
				case "unpublished":
					$show = " WHERE `show` = 0 AND date <= '".date('Y-m-d H:i:s')."'";
					break;
				case "all":
					$show = "";
					break;
			}

			// date archive query addition
			if(in_context(ZP_ZENPAGE_NEWS_DATE)) {
				$postdate = $_zp_post_date;
				if(empty($show)) {
					$and = " WHERE ";
				} else {
					$and = " AND ";
				}
				$datesearch = $and."date LIKE '$postdate%'";
			} else {
				$datesearch = "";
			}
			$result = query("SELECT COUNT(*) FROM ".prefix('zenpage_news').$show.$datesearch);
			$row = mysql_fetch_row($result);
			$count = $row[0];
			return $count;
		} else {
			$catid = getCategoryID($category);

			switch($published) {
				case "published":
					$show = " AND news.show = 1 AND news.date <= '".date('Y-m-d H:i:s')."'";
					break;
				case "unpublished":
					$show = " AND news.show = 0 AND news.date <= '".date('Y-m-d H:i:s')."'";
					break;
				case "all":
					$show = "";
					break;
			}
			$result = query_full_array("SELECT cat.cat_id FROM ".prefix('zenpage_news2cat')." as cat, ".prefix('zenpage_news')." as news WHERE cat.cat_id = '$catid' AND news.id = cat.news_id ".$show);
			$count = 0;
			foreach($result as $resultcount) {
				$count++;
			}
			return $count;
		}
	}

/**
	 * Gets the LIMIT and OFFSET for the MySQL query that gets the news articles
	 *
	 * @param int $articles_per_page The number of articles to get
	 * @param bool $ignorepagination If pagination should be ingored so always with the first is started (false is default)
	 * @return string
	 */
	function getLimitAndOffset($articles_per_page,$ignorepagination=false) {
		global $_zp_zenpage_total_pages;
		if(strstr(dirname($_SERVER['REQUEST_URI']), '/'.PLUGIN_FOLDER.'/zenpage')) {
			$page = getCurrentAdminNewsPage();
		} else {
			$page = getCurrentNewsPage();
		}
		if(!empty($articles_per_page)) {
			$_zp_zenpage_total_pages = ceil(getTotalArticles() / $articles_per_page);
		}
		if($ignorepagination) {
			$offset = 0;
		} else {
			$offset = ($page - 1) * $articles_per_page;
		}
		// Prevent sql limit/offset error when saving plugin options and on the plugins page
		if(empty($articles_per_page)) {
			$limit = "";
		} else {
			$limit = " LIMIT ".$offset.",".$articles_per_page;
		}
		return $limit;

	}



	/**
	 * Retrieves a list of all unique years & months
	 *
	 * @return array
	 */
	function getAllArticleDates() {
		$alldates = array();
		$cleandates = array();
		$sql = "SELECT date FROM ". prefix('zenpage_news');
		if (!zp_loggedin(ZENPAGE_NEWS_RIGHTS)) { $sql .= " WHERE `show` = 1"; }
		$result = query_full_array($sql);
		foreach($result as $row){
			$alldates[] = $row['date'];
		}
		foreach ($alldates as $adate) {
			if (!empty($adate)) {
				$cleandates[] = substr($adate, 0, 7) . "-01";;
			}
		}
		$datecount = array_count_values($cleandates);
		krsort($datecount);
		return $datecount;
	}


	/**
	 * Gets the current news page number
	 *
	 * @return int
	 */
	function getCurrentNewsPage() {
		if(isset($_GET['page'])) {
			$page = sanitize_numeric($_GET['page']);
		} else {
			$page = 1;
		}
		return $page;
	}


	/**
	 * Get current news page for admin news pagination
	 * Addition needed because $_GET['page'] conflict with zenphoto
	 * could probably removed now...
	 *
	 * @return int
	 */
	function getCurrentAdminNewsPage() {
		if(isset($_GET['pagenr'])) {
			$page = sanitize_numeric($_GET['pagenr']);
		} else {
			$page = 1;
		}
		return $page;
	}

	/**
	 * Gets news articles and images of a gallery to show them together on the news section
	 *
	 * NOTE: This feature requires MySQL 4.1 or later
	 * 
	 * NOTE: This function does not exclude articles that are password protected via a category
	 *
	 * @param int $articles_per_page The number of articles to get
	 * @param string $mode 	"latestimages-thumbnail"
	 *											"latestimages-thumbnail-customcrop"	
	 *											"latestimages-sizedimage"
	 *											"latestalbums-thumbnail"
	 *		 									"latestalbums-thumbnail-customcrop"
	 *		 									"latestalbums-sizedimage"
	 *		 									"latestimagesbyalbum-thumbnail"
	 *		 									"latestimagesbyalbum-thumbnail-customcrop"
	 *		 									"latestimagesbyalbum-sizedimage"
	 *		 									"latestupdatedalbums-thumbnail" (for RSS and getLatestNews() used only)
	 *		 									"latestupdatedalbums-thumbnail-customcrop" (for RSS and getLatestNews() used only)
	 *		 									"latestupdatedalbums-sizedimage" (for RSS and getLatestNews() used only)
	 *	NOTE: The "latestupdatedalbums" variants do NOT support pagination as required on the news loop!
	 *
	 * @param string $published "published" for published articles,
	 * 													"unpublished" for un-published articles,
	 * 													"all" for all articles
	 * @param string $sortorder 	id, date or mtime, only for latestimages-... modes
	 * @param bool $sticky set to true to place "sticky" articles at the front of the list.
	 * @return array
	 */
	function getCombiNews($articles_per_page='', $mode='',$published=NULL,$sortorder='',$sticky=true) {
		global $_zp_gallery, $_zp_flash_player;
		processExpired('zenpage_news');
		if (is_null($published)) {
			if(zp_loggedin(ZENPAGE_NEWS_RIGHTS)) {
				$published = "all";
			} else {
				$published = "published";
			}
		}
		if(empty($mode)) {
			$mode = getOption("zenpage_combinews_mode");
		} else {
			$mode = sanitize($mode);
		}
		if($published == "published") {
			$show = " WHERE `show` = 1 AND date <= '".date('Y-m-d H:i:s')."'";
			$imagesshow = " AND images.show = 1 ";
		} else {
			$show = "";
			$imagesshow = "";
		}
		$passwordcheck = "";
		if (zp_loggedin(ZENPAGE_NEWS_RIGHTS)) {
			$albumWhere = "";
			$passwordcheck = "";
		} else {
			$albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
			foreach($albumscheck as $albumcheck) {
				if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
					$albumpasswordcheck= " AND albums.id != ".$albumcheck['id'];
					$passwordcheck = $passwordcheck.$albumpasswordcheck;
				}
			}
			$albumWhere = "AND albums.show=1".$passwordcheck;
		}
		$articles_per_page = sanitize_numeric($articles_per_page);
		$limit = getLimitAndOffset($articles_per_page);
		if(empty($sortorder)) {
			$combinews_sortorder = getOption("zenpage_combinews_sortorder");
		} else {
			$combinews_sortorder = sanitize($sortorder);
		}
		$stickyorder = '';
		if($sticky) {
			$stickyorder = 'sticky DESC,';
		}
		$type3 = query("SET @type3:='0'");
		switch($mode) {
			case "latestimages-thumbnail":
			case "latestimages-thumbnail-customcrop":	
			case "latestimages-sizedimage":
				$sortorder = "images.".$combinews_sortorder;
				$type1 = query("SET @type1:='news'");
				$type2 = query("SET @type2:='images'");
				switch($combinews_sortorder) {
					case 'id':
					case 'date':
						$imagequery = "(SELECT albums.folder, images.filename, images.date, @type2, @type3 as sticky FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums
							WHERE albums.id = images.albumid ".$imagesshow.$albumWhere." ORDER BY ".$sortorder.")";
						break;
					case 'mtime':
						$imagequery = "(SELECT albums.folder, images.filename, FROM_UNIXTIME(images.mtime), @type2, @type3 as sticky FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums
							WHERE albums.id = images.albumid ".$imagesshow.$albumWhere." ORDER BY ".$sortorder.")";
						break;
				}
				$result = query_full_array("
				(SELECT title as albumname, titlelink, date, @type1 as type, sticky FROM ".prefix('zenpage_news')." ".$show." ORDER BY date)
				UNION
				".$imagequery."
				ORDER BY $stickyorder date DESC  $limit
				");
				break;
			case "latestalbums-thumbnail":
			case "latestalbums-thumbnail-customcrop":	
			case "latestalbums-sizedimage":
				$sortorder = $combinews_sortorder;
				$type1 = query("SET @type1:='news'");
				$type2 = query("SET @type2:='albums'");
				switch($combinews_sortorder) {
					case 'id':
					case 'date':
						$albumquery = "(SELECT albums.folder, albums.title, albums.date, @type2, @type3 as sticky FROM ".prefix('albums')." AS albums
							".$show.$albumWhere." ORDER BY ".$sortorder.")";
						break;
					case 'mtime':
						$albumquery = "(SELECT albums.folder, albums.title, FROM_UNIXTIME(albums.mtime), @type2, @type3 as sticky FROM ".prefix('albums')." AS albums
							".$show.$albumWhere." ORDER BY albums.".$sortorder.")";
						break;
				}
				$result = query_full_array("
				(SELECT title as albumname, titlelink, date, @type1 as type, sticky FROM ".prefix('zenpage_news')." ".$show." ORDER BY date)
				UNION
				".$albumquery."
				ORDER BY $stickyorder date DESC $limit
				");
				break;
			case "latestimagesbyalbum-thumbnail": 
			case "latestimagesbyalbum-thumbnail-customcrop":
			case "latestimagesbyalbum-sizedimage":
				$type1 = query("SET @type1:='news'");
				$type2 = query("SET @type2:='albums'");
				if(empty($combinews_sortorder) || $combinews_sortorder != "date" || $combinews_sortorder != "mtime" ) {
					$combinews_sortorder = "date";
				}
				$combinews_sortorder = "date";
				$sortorder = "images.".$combinews_sortorder;
				switch(		$combinews_sortorder) {
					case "date":
						$imagequery = "(SELECT DISTINCT DATE_FORMAT(".$sortorder.",'%Y-%m-%d'), albums.folder, DATE_FORMAT(images.`date`,'%Y-%m-%d'), @type2 FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums
						WHERE albums.id = images.albumid ".$imagesshow.$albumWhere." ORDER BY DATE_FORMAT(".$sortorder.",'%Y-%m-%d'))";
						break;
					case "mtime":
						$imagequery = "(SELECT DISTINCT FROM_UNIXTIME(".$sortorder.",'%Y-%m-%d'), albums.folder, DATE_FORMAT(images.`mtime`,'%Y-%m-%d'), @type2 FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums
						WHERE albums.id = images.albumid ".$imagesshow.$albumWhere." ORDER BY FROM_UNIXTIME(".$sortorder.",'%Y-%m-%d'))";
						break;
				}
				$result = query_full_array("
				(SELECT title as albumname, titlelink, date, @type1 as type FROM ".prefix('zenpage_news')." ".$show." ORDER BY date)
				UNION
				".$imagequery."
				ORDER By date DESC $limit
				");
				//echo "<pre>"; print_r($result); echo "</pre>";
				//$result = "";
				break;
			case "latestupdatedalbums-thumbnail":
			case "latestupdatedalbums-thumbnail-customcrop":
			case "latestupdatedalbums-sizedimage":
				$latest = getNewsArticles($articles_per_page,'',NULL,true);
				$counter = '';
				foreach($latest as $news) {
					$article = new ZenpageNews($news['titlelink']);
					if (checkNewsAccess($article, $hint, $show)) {
						$counter++;
						$latestnews[$counter] = array(
						"albumname" => $article->getTitle(),
						"titlelink" => $article->getTitlelink(),
						"date" => $article->getDateTime(),
						"type" => "news",
					);
					}
				}
				$albums = getAlbumStatistic($articles_per_page, "latestupdated");
				$latestalbums = array();
				$counter = "";
				foreach($albums as $album) {
					$counter++;
					$tempalbum = new Album($_zp_gallery, $album['folder']);
					$tempalbumthumb = $tempalbum->getAlbumThumbImage();
					$timestamp = $tempalbum->get('mtime');
					if($timestamp == 0) {
						$albumdate = $tempalbum->getDateTime();
					} else {
						$albumdate = strftime('%Y-%m-%d %T',$timestamp);
					}
					$latestalbums[$counter] = array(
					"albumname" => $tempalbum->getFolder(),
					"titlelink" => $tempalbum->getTitle(),
					"date" => $albumdate,
					"type" => 'albums',
					);
				}
				//$latestalbums = array_merge($latestalbums, $item);
				$latest = array_merge($latestnews, $latestalbums);
				$result = sortMultiArray($latest,"date",true);
			  if(count($result) > $articles_per_page) {
					$result = array_slice($result,0,10);
				}
				break;
		}
		//$result = "";
		return $result;
	}


	/**
	 * CombiNews Feature: Counts all news articles and all images
	 *
	 * @return int
	 */
	function countCombiNews($published=NULL) {
		global $_zp_gallery;
		$countGalleryitems = 0;
		$countArticles = 0;
		if(getOption("zenpage_combinews")) {
			$countArticles = countArticles();
			if(is_null($published)) {
				if(zp_loggedin(ZENPAGE_NEWS_RIGHTS)) {
					$published = FALSE;
				} else {
					$published = TRUE;
				}
			}
			$mode = getOption("zenpage_combinews_mode");
			if(is_object($_zp_gallery)) { // workaround if called on the admin pages....
				switch($mode) {
					case "latestimages-sizedimage":
					case "latestimages-thumbnail":
					case "latestimages-thumbnail-customcrop":	
						$countGalleryitems = $_zp_gallery->getNumImages($published);
						break;
					case "latestalbums-sizedimage":
					case "latestalbums-thumbnail":
					case "latestalbums-thumbnail-customcrop":	
						$countGalleryitems = $_zp_gallery->getNumAlbums(true,$published);
						break;
					case "latestimagesbyalbum-thumbnail":
					case "latestimagesbyalbum-thumbnail-customcrop":
					case "latestimagesbyalbum-sizedimage":
						($published) ? $show = "WHERE `show`= 1" : $show = "";
						$result = query("SELECT COUNT(DISTINCT Date(date),albumid) FROM " . prefix('images'). " ".$show);
						$countGalleryitems = mysql_result($result, 0);
						break;
				}
			} else {
				$countGalleryitems = 0;
			}
			$totalcount = $countArticles+$countGalleryitems;
			return $totalcount;
		}
	}

	/************************************/
	/* general news category functions  */
	/************************************/

/**
	 * Gets the category link of a category
	 *
	 * @param string $catname the title of the category
	 * @return string
	 */
	function getCategoryLink($catname) {
		foreach(getAllCategories() as $cat) {
			if($cat['cat_name'] == $catname) {
				return $cat['cat_link'];
			}
		}
	}


	/**
	 * Gets the category title of a category
	 *
	 * @param string $catlink the categorylink of the category
	 * @return string
	 */
	function getCategoryTitle($catlink) {
		foreach(getAllCategories() as $cat) {
			if($cat['cat_link'] == $catlink) {
				return htmlspecialchars(get_language_string($cat['cat_name']));
			}
		}
	}


	/**
	 * Gets the id of a category
	 *
	 * @param string $catlink the categorylink of the category id to get
	 * @return int
	 */
	function getCategoryID($catlink) {
		foreach(getAllCategories() as $cat) {
			if($cat['cat_link'] == $catlink) {
				return $cat['id'];
			}
		}
	}

/**
	 * Gets all categories
	 *
	 * @return array
	 */
	function getAllCategories() {
		global $_zp_zenpage_all_categories;
		if(is_null($_zp_zenpage_all_categories) OR isset($_GET['delete']) OR isset($_GET['update']) OR isset($_GET['save'])) {
			$_zp_zenpage_all_categories = query_full_array("SELECT * FROM ".prefix('zenpage_news_categories')." ORDER by cat_name", false, 'cat_link');
		}
		return $_zp_zenpage_all_categories;
	}

	/**
	 * Returns the articles count
	 *
	 */
	function getTotalArticles() {
		global $_zp_current_category;
		if(getOption('zenpage_combinews') AND !isset($_GET['title']) AND !isset($_GET['category']) AND !isset($_GET['date']) AND OFFSET_PATH != 4) {
			return countCombiNews();
		} else {
			return countArticles($_zp_current_category);
		}
	}

	/**
	 * Gets a category by id
	 *
	 * @param int $id id of the category
	 * @return array
	 */
	function getCategory($id) {
		$id = sanitize($id);
		$result = query_single_row("SELECT * FROM ".prefix('zenpage_news_categories')." WHERE id=".$id);
		return $result;
	}
	
	/**
 * Checks if an article (not CombiNews gallery items!) is in a password protected category and returns TRUE or FALSE
 * NOTE: This function does not check if the password has been entered! Use checkNewsAccess() for that.
 *
 * @param bool $checkProtection If set to TRUE (default) this check if the article is actually protected (remember only articles that are in the protected category only are!).
 * 															If set to FALSE it simply checks if it is in an otherwise protected category at all
 * @param obj $articleobj Optional news article object to check directly, if empty the current news article is checked if available
 * @return bool
 */
function inProtectedNewsCategory($articleobj=NULL,$checkProtection=true) {
	global $_zp_current_zenpage_news;
	if(empty($articleobj) && !is_null($_zp_current_zenpage_news) && get_class($_zp_current_zenpage_news) == 'zenpagenews') {
		$articleobj = $_zp_current_zenpage_news;
	}
	$categories = $articleobj->getCategories();
	if(count($categories) > 0) {
		foreach($categories as $cat) {
			if(empty($cat['password'])) {
				if ($checkProtection) {
					return false;
				}
			} else {
				if (!$checkProtection) {
					return true;
				}
			}
		}
		return $checkProtection;
	}
	return false;
}

/**
 * Checks if a category is protected and returns TRUE or FALSE
 * NOTE: This function does only check if a password is set not if it has been entered! Use checkNewsCategoryPassword() for that.
 *
 * @param string $catlink The optional categorylink of a category, if empty the current category is checked if available
 * @return bool
 */
function isProtectedNewsCategory($catlink='') {
	global $_zp_current_category;
	if(empty($catlink) && !is_null($_zp_current_category)) {
		$catlink = $_zp_current_category;
	}
	$hint = $show = '';
	return checkNewsCategoryPassword($catlink, $hint, $show) != 'zp_unprotected';
}

?>