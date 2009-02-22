<?php
/**
 * zenpage functions
 * general functions used both on the admin backend and the theme
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */


/**
 * Some global variable setup
 *
 */
$_zp_zenpage_all_categories = getAllCategories();
if(getOption('zenpage_combinews') AND !isset($_GET['title']) AND !isset($_GET['category']) AND !isset($_GET['date']) AND OFFSET_PATH != 4) {
	$_zp_zenpage_total_articles = countCombiNews();
} else {
	if(isset($_GET['category'])) {
		$category = sanitize($_GET['category']);
	} else {
		$category = "";
	}
	$_zp_zenpage_total_articles = countArticles($category);
}


/************************************/
/* general page functions   */
/************************************/

/**
	 * Gets all pages or published ones.
	 *
	 * @param bool $published TRUE for published or FALSE for all pages including unpublished
	 * @return array
	 */
	function getPages($published=true) {
		global $_zp_zenpage_all_pages;
		if($published) {
			$show = " WHERE `show` = 1";
		} else {
			$show = " ";
		}
		if(is_null($_zp_zenpage_all_pages)) {
			$_zp_zenpage_all_pages  = query_full_array("SELECT titlelink,sort_order FROM ".prefix('zenpage_pages').$show." ORDER by `sort_order`");
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
		if($pageobj->getID() === $currentparentid) {
			$pageobjtitlelink = $pageobj->getTitlelink();
			$pageobjparentid = $pageobj->getParentID();
			array_unshift($parentpages,$pageobjtitlelink);
		 	getParentPages($pageobjparentid,false);
		} 
	}
	return $parentpages;
}
	

/************************************/
/* general news article functions   */
/************************************/

/**
	 * Gets news articles either all or by category or by archive date
	 *
	 * @param int $articles_per_page The number of articles to get
	 * @param string $category The categorylink of the category
	 * @param string $published "published" for an published articles,
	 * 													"unpublished" for an unpublised articles,
	 * 													"all" for all articles
	 * @return array
	 */
	function getNewsArticles($articles_per_page='', $category='', $published="published",$admin=false) {
		global $_zp_loggedin;
		if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS) AND $admin) {
			$published = "all";
		} else if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS) AND $admin) {
			$published = $published;
		} else {
			$published = "published";
		}
		$show = "";
		if (!empty($category)) {
			$cat = " cat.cat_id = '".getCategoryID($category)."' AND cat.news_id = news.id ";
		} elseif(isset($_GET['category'])) {
			$cat = " cat.cat_id = '".getCategoryID(sanitize($_GET['category']))."' AND cat.news_id = news.id ";
		} else {
			$cat ="";
		}
		if(isset($_GET['date'])) {
			$postdate = sanitize($_GET['date']);
		} else {
			$postdate = NULL;
		}
		$limit = getLimitAndOffset($articles_per_page);
		 
		/*** get articles by category ***/
		if (!empty($category) OR isset($_GET['category'])) {

			switch($published) {
				case "published":
					$show = " AND `show` = 1";
					break;
				case "unpublished":
					$show = " AND `show` = 0";
					break;
				case "all":
					$show = "";
					break;
			}
	
			if(isset($_GET['date'])) {
				$datesearch = " AND news.date LIKE '".$postdate."%' ";
			} else {
				$datesearch = "";
			}
			$result = query_full_array("SELECT news.titlelink FROM ".prefix('zenpage_news')." as news, ".prefix('zenpage_news2cat')." as cat WHERE".$cat.$show.$datesearch." ORDER BY news.date DESC".$limit);

			/***get all articles ***/
		} else {

			switch($published) {
				case "published":
					$show = " WHERE `show` = 1";
					break;
				case "unpublished":
					$show = " WHERE `show` = 0";
					break;
				case "all":
					$show = "";
					break;
			}
			if(isset($_GET['date'])) {
				switch($published) {
					case "published":
						$datesearch = " AND date LIKE '$postdate%' ";
						break;
					case "unpublished":
						$datesearch = " WHERE date LIKE '$postdate%' ";
						break;
					case "all":
						$datesearch = " WHERE date LIKE '$postdate%' ";
						break;
				}
			} else {
				$datesearch = "";
			}
			$result = query_full_array("SELECT titlelink FROM ".prefix('zenpage_news').$show.$datesearch." ORDER BY date DESC".$limit);
		}
		return $result;
	}
	

/**
	 * Counts news articles, either all or by category or archive date, published or unpublished
	 *
	 * @param string $category The categorylink of the category to count
	 * @param string $published "published" for an published articles,
	 * 													"unpublished" for an unpublised articles,
	 * 													"all" for all articles
	 * @return array
	 */
	function countArticles($category='', $published='published') {
		global $_zp_loggedin;
		if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
			$published = "all";
		} else {
			$published = "published";
		}
		$show="";
		if (empty($category)) {
			
			switch($published) {
				case "published":
					$show = " WHERE `show` = 1";
					break;
				case "unpublished":
					$show = " WHERE `show` = 0";
					break;
				case "all":
					$show = "";
					break;
			}
		
			// date archive query addition
			if(isset($_GET['date'])) {
				$postdate = sanitize($_GET['date']);
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
					$show = " AND news.show = 1";
					break;
				case "unpublished":
					$show = " AND news.show = 0";
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
	 * @return string
	 */
	function getLimitAndOffset($articles_per_page) {
		global $_zp_zenpage_total_pages, $_zp_zenpage_total_articles;
		if(strstr(dirname($_SERVER['REQUEST_URI']), PLUGIN_FOLDER.'zenpage')) {
			$page = getCurrentAdminNewsPage(); // TODO maybe useless since the $_GET['page'] is removed for getting the active main admin tab, too lazy to revert now
		} else {
			$page = getCurrentNewsPage();
		}
		if(!empty($articles_per_page)) {
			$_zp_zenpage_total_pages = ceil($_zp_zenpage_total_articles / $articles_per_page);
		}
		$offset = ($page - 1) * $articles_per_page;
			
		// Prevent sql limit/offset error when saving plugin options and on the plugins page
		if (empty($articles_per_page)) {
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
		global $_zp_loggedin;
		$alldates = array();
		$cleandates = array();
		$sql = "SELECT date FROM ". prefix('zenpage_news');
		if (!($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) { $sql .= " WHERE `show` = 1"; }
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
		arsort($datecount);
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
		if (!isset($_GET['page']) AND isPage(ZENPAGE_NEWS) AND !isset($_GET['category']) AND !isset($_GET['date']) AND getOption("zenpage_zp_index_news")) {
			$page = 2;
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
	 * @param int $articles_per_page The number of articles to get
	 * @param string $mode 	"latestimages-thumbnail"
	 * 											"latestimages-sizedimage"
	 * @param string $published "published" for published articles,
	 * 													"unpublished" for unpublished articles,
	 * 													"all" for all articles
	 * @return array
	 */
	function getCombiNews($articles_per_page, $mode='',$published='published') {
		global $_zp_gallery, $_zp_flash_player,$_zp_loggedin;
		if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
			$published = "all";
		} else {
			$published = "published";
		}
		if(empty($mode)) {
			$mode = getOption("zenpage_combinews_mode");
		} else {
			$mode = sanitize($mode);
		}
		if($published === "published") {
			$show = " WHERE `show` = 1 ";
			$imagesshow = " AND images.show = 1 ";
		} else {
			$show = "";
			$imagesshow = "";
		}
		$passwordcheck = "";
		if ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
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
		$limit = getLimitAndOffset(getOption("zenpage_articles_per_page"));
		$combinews_sortorder = getOption("zenpage_combinews_sortorder");
		if(empty($combinews_sortorder)) {
			$combinews_sortorder = "id";
		}
		switch($mode) {
			case "latestimages-thumbnail":
			case "latestimages-sizedimage":
				$sortorder = "images.".$combinews_sortorder;
				$type1 = query("SET @type1:='news'");
				$type2 = query("SET @type2:='images'");
				$result = query_full_array("
				(SELECT title as albumname, titlelink, date, @type1 as type FROM ".prefix('zenpage_news')." ".$show." ORDER BY date)
				UNION
				(SELECT albums.folder, images.filename, images.date, @type2 FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums 
				WHERE albums.id = images.albumid ".$imagesshow.$albumWhere." ORDER BY ".$sortorder.")
				ORDER By date DESC $limit
				");
				break;
			case "latestalbums-thumbnail":
			case "latestalbums-sizedimage":
				$sortorder = $combinews_sortorder;
				$type1 = query("SET @type1:='news'");
				$type2 = query("SET @type2:='albums'");
				$result = query_full_array("
				(SELECT title as albumname, titlelink, date, @type1 as type FROM ".prefix('zenpage_news')." ".$show." ORDER BY date)
				UNION
				(SELECT albums.folder, albums.title, albums.date, @type2 FROM ".prefix('albums')." AS albums 
				".$show.$albumWhere." ORDER BY ".$sortorder.")
				ORDER By date DESC $limit
				");
				break;
		}
		return $result;
	}


	/**
	 * CombiNews Feature: Counts all news articles and all images
	 *
	 * @return int
	 */
	function countCombiNews($published='') {
		global $_zp_loggedin;
		if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
			$published = "all";
		} else {
			$published = "published";
		}
		if($published === "published") {
			$newsshow = " WHERE `show` = 1 ";
			$imagesshow = " AND images.show = 1 ";
		} else {
			$newsshow = "";
			$imagesshow = "";
		}
		$result = query("SELECT Count(*) FROM ".prefix('zenpage_news')." $newsshow");
		$row = mysql_fetch_row($result);
		$count1 = $row[0];
		$passwordcheck = "";
		if ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
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
		$result2 = query_full_array("SELECT images.id FROM ".prefix('images')." AS images, ".prefix('albums')." AS albums
		WHERE albums.id = images.albumid ".$imagesshow.$albumWhere);
		$count2 = 0;
		foreach($result2 as $counter2) {
			$count2++;
		}
		$totalcount = $count1+$count2;
		return $totalcount;
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
		global $_zp_zenpage_all_categories;
		foreach($_zp_zenpage_all_categories as $cat) {
			if($cat['cat_name'] === $catname) {
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
		global $_zp_zenpage_all_categories;
		foreach($_zp_zenpage_all_categories as $cat) {
			if($cat['cat_link'] === $catlink) {
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
		global $_zp_zenpage_all_categories;
		foreach($_zp_zenpage_all_categories as $cat) {
			if($cat['cat_link'] === $catlink) {
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
			$_zp_zenpage_all_categories = query_full_array("SELECT id, cat_name, cat_link, hitcounter, permalink FROM ".prefix('zenpage_news_categories')." ORDER by cat_name");
		} 
		$_zp_zenpage_all_categories = sortByMultilingual($_zp_zenpage_all_categories,"cat_name",false);
		return $_zp_zenpage_all_categories;
	}


	/**
	 * Gets a category by id
	 *
	 * @param int $id id of the category
	 * @return array
	 */
	function getCategory($id) {
		$id = sanitize($id);
		$result = query_single_row("SELECT id, cat_name, cat_link, hitcounter, permalink FROM ".prefix('zenpage_news_categories')." WHERE id=".$id);
		return $result;
	}

?>