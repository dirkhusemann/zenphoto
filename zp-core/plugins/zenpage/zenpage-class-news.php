<?php
/**
 * zenpage news class
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
if (!defined('ZENPAGE_NEWS')) {
	define("ZENPAGE_NEWS",getOption("zenpage_news_page"));
}

class ZenpageNews extends PersistentObject {
	
	var $total_articles=''; // the number of all news articles or of the articles of a category
	var $total_pages;//The number of pages for news article pagination
	var $all_categories = NULL; //Contains an array of all categories (for category menu for example)
  var $zenpages = '';//Contains an array of the current articles, current single article or the current page
	var $comments = NULL;//Contains an array of the comments of the current article
	var $commentcount; //Contains the number of comments
	
	function ZenpageNews($titlelink="") {
		$titlelink = sanitize($titlelink);
		$new = parent::PersistentObject('zenpage_news', array('titlelink'=>$titlelink), NULL, true, empty($titlelink));
		$this->all_categories = $this->getAllCategories();
		if(getOption('zenpage_combinews') AND !isset($_GET['title']) AND !isset($_GET['category']) AND !isset($_GET['date']) AND OFFSET_PATH != 4) {
			$this->total_articles = $this->countCombiNews();
		} else {
			if(isset($_GET['category'])) {
				$category = sanitize($_GET['category']);
			} else {
				$category = "";
			}
 			$this->total_articles = $this->countArticles($category);
		}
	}

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
		if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS) AND !$admin) {
			$published = "all";
		} else if($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS) AND $admin) {
			$published = $published;
		} else {
			$published = "published";
		}
		$show = "";
		if (!empty($category)) {
			$cat = " cat.cat_id = '".$this->getCategoryID($category)."' AND cat.news_id = news.id ";
		} elseif(isset($_GET['category'])) {
			$cat = " cat.cat_id = '".$this->getCategoryID(sanitize($_GET['category']))."' AND cat.news_id = news.id ";
		} else {
			$cat ="";
		}
		if(isset($_GET['date'])) {
			$postdate = sanitize($_GET['date']);
		} else {
			$postdate = NULL;
		}
		$limit = $this->getLimitAndOffset($articles_per_page);
		 
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
			$catid = $this->getCategoryID($category);

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
		if(strstr(dirname($_SERVER['REQUEST_URI']), '/plugins/zenpage')) {
			$page = $this->getCurrentAdminNewsPage(); // TODO maybe useless since the $_GET['page'] is removed for getting the active main admin tab, too lazy to revert now
		} else {
			$page = $this->getCurrentNewsPage();
		}
		if(!empty($articles_per_page)) {
			$this->total_pages = ceil($this->total_articles / $articles_per_page);
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
		$limit = $this->getLimitAndOffset(getOption("zenpage_articles_per_page"));
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
	
	
	

	/**
	 * Returns the id of the news article
	 *
	 * @return string
	 */
	function getID() {
		return $this->get("id");
	}

	/**
	 * Returns the title of the news article
	 *
	 * @return string
	 */
	function getTitle() {
		return get_language_string($this->get("title"));
	}

	/**
	 * Returns the content of the news article
	 *
	 * @return string
	 */
	function getContent() {
		return get_language_string($this->get("content"));
	}

	/**
	 * Returns the extra content of the news article
	 *
	 * @return string
	 */
	function getExtraContent() {
		return get_language_string($this->get("extracontent"));
	}

	/**
	 * Returns the news article title sortorder
	 *
	 * @return string
	 */
	function getSortOrder() {
		return $this->get("sort_order");
	}
	
	/**
	 * Returns the show status of the news article, "1" if published
	 *
	 * @return string
	 */
	function getShow() {
		return $this->get("show");
	}

	/**
	 * Returns the titlelink of the news article
	 *
	 * @return string
	 */
	function getTitlelink() {
		return $this->get("titlelink");
	}

	/**
	 * Returns the codeblocks of the news article as an serialized array
	 *
	 * @return array
	 */
	function getCodeblock() {
		return $this->get("codeblock");
	}

	/**
	 * Returns the author of the news article
	 *
	 * @return string
	 */
	function getAuthor() {
		return $this->get("author");
	}

	/**
	 * Returns the date of the news article
	 *
	 * @return string
	 */
	function getDateTime() {
		return $this->get("date");
	}

	/**
	 * Returns the last change date of the news article
	 *
	 * @return string
	 */
	function getLastchange() {
		return $this->get("lastchange");
	}

	/**
	 * Returns the last change author of the news article
	 *
	 * @return string
	 */
	function getLastchangeAuthor() {
		return $this->get("lastchangeauthor");
	}

	/**
	 * Returns the comments status of the news article, "1" if comments are enabled
	 *
	 * @return string
	 */
	function getCommentson() {
		return $this->get("commentson");
	}

	/**
	 * Returns the hitcount of the news article
	 *
	 * @return string
	 */
	function getHitcounter() {
		return $this->get("hitcounter");
	}

	/**
	 * Returns the locked status of the news article, "1" if locked (only used on the admin)
	 *
	 * @return string
	 */
	function getLocked() {
		return $this->get("locked");
	}

	/**
	 * Returns the permalink status  of the news article, "1" if enabled (only used on the admin)
	 *
	 * @return string
	 */
	function getPermalink() {
		return $this->get("permalink");
	}
	
	/**********************************************
	 * News category functions
	 **********************************************/

	/**
	 * Gets the category link of a category
	 *
	 * @param string $catname the title of the category
	 * @return string
	 */
	function getCategoryLink($catname) {
		foreach($this->all_categories as $cat) {
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
		foreach($this->all_categories as $cat) {
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
		foreach($this->all_categories as $cat) {
			if($cat['cat_link'] === $catlink) {
				return $cat['id'];
			}
		}
	}


	/**
	 * Gets the categories assigned to an news article
	 *
	 * @param int $article_id ID od the article
	 * @return array
	 */
	function getCategories($article_id) {
		$categories = query_full_array("SELECT cat.cat_name, cat.cat_link FROM ".prefix('zenpage_news_categories')." as cat,".prefix('zenpage_news2cat')." as newscat WHERE newscat.cat_id = cat.id AND newscat.news_id = ".$article_id." ORDER BY cat.cat_name");
		return $categories;
	}


	/**
	 * Gets all categories
	 *
	 * @return array
	 */
	function getAllCategories() {
		if(is_null($this->all_categories) OR isset($_GET['delete']) OR isset($_GET['update']) OR isset($_GET['save'])) {
			$result = query_full_array("SELECT id, cat_name, cat_link, hitcounter, permalink FROM ".prefix('zenpage_news_categories')." ORDER by cat_name");
		} else {
			$result = $this->all_categories;
		}
		$result = sortByMultilingual($result,"cat_name",false);
		return $result;
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
	
	/****************
	 * Comments
	 ****************/

	/**
	 * Returns an array of comments of the current news article
	 *
	 * @param bool $moderated if false, comments in moderation are ignored
	 * @param bool $private if false ignores private comments
	 * @param bool $desc set to true for descending order
	 * @return array
	 */
	function getComments($moderated=false, $private=false, $desc=false) {
		$sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") .
 			" WHERE `type`='news' AND `ownerid`='" . $this->getID() . "'";
		if (!$moderated) {
			$sql .= " AND `inmoderation`=0";
		}
		if (!$private) {
			$sql .= " AND `private`=0";
		}
		$sql .= " ORDER BY id";
		if ($desc) {
			$sql .= ' DESC';
		}
		$comments = query_full_array($sql);
		$this->comments = $comments;
		return $this->comments;
	}


	/**
	 * Adds a comment to the news article
	 * assumes data is coming straight from GET or POST
	 *
	 * Returns a code for the success of the comment add:
	 *    0: Bad entry
	 *    1: Marked for moderation
	 *    2: Successfully posted
	 *
	 * @param string $name Comment author name
	 * @param string $email Comment author email
	 * @param string $website Comment author website
	 * @param string $comment body of the comment
	 * @param string $code Captcha code entered
	 * @param string $code_ok Captcha md5 expected
	 * @param string $ip the IP address of the comment poster
	 * @param bool $private set to true if the comment is for the admin only
	 * @param bool $anon set to true if the poster wishes to remain anonymous
	 * @return int
	 */
	function addComment($name, $email, $website, $comment, $code, $code_ok, $ip, $private, $anon) {
		$goodMessage = postComment($name, $email, $website, $comment, $code, $code_ok, $this, $ip, $private, $anon);
		return $goodMessage;
	}


	/**
	 * Returns the count of comments for the current news article. Comments in moderation are not counted
	 *
	 * @return int
	 */
	function getCommentCount() {
		global $_zp_current_zenpage_news;
		$id = $_zp_current_zenpage_news->getID();
		if (is_null($this->commentcount)) {
			if ($this->comments == null) {
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='news' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $id);
				$this->commentcount = array_shift($count);
			} else {
				$this->commentcount = count($this->comments);
			}
		} else { // probably because of the slightly different setup of zenpage this extra part is necessary to get the right comment count withn next_comment() loop
			$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='news' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $id);
			$this->commentcount = array_shift($count);
		}
		return $this->commentcount;
	}
} // zenpage news class end


?>