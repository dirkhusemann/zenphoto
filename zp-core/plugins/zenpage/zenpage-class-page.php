<?php
if (!defined('ZENPAGE_PAGES')) {
	define("ZENPAGE_PAGES",getOption("zenpage_pages_page"));
}

class ZenpagePage extends PersistentObject {
	
	var $all_pages = NULL;//Contains an array of all normal content pages
	var $comments = NULL;//Contains an array of the comments of the current article
	var $commentcount; //Contains the number of comments
	
	function ZenpagePage($titlelink="") {
		if(!empty($titlelink)) {
			$titlelink = sanitize($titlelink);
			$new = parent::PersistentObject('zenpage_pages', array('titlelink'=>$titlelink));
		}
	}
	

	/**
	 * Gets all pages or published ones.
	 *
	 * @param bool $published TRUE for published or FALSE for all pages including unpublished
	 * @return array
	 */
	function getPages($published=true) {
		if($published) {
			$show = " WHERE `show` = 1";
		} else {
			$show = " ";
		}
		if(is_null($this->all_pages)) {
			$this->all_pages  = query_full_array("SELECT titlelink,sort_order FROM ".prefix('zenpage_pages').$show." ORDER by `sort_order`");
			return $this->all_pages;
		} else {
			return $this->all_pages;
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
	$allpages = $this->getPages();
	$currentparentid = $parentid;
	foreach($allpages as $page) {
		$pageobj = new ZenpagePage($page['titlelink']);
		if($pageobj->getID() === $currentparentid) {
			$pageobjtitlelink = $pageobj->getTitlelink();
			$pageobjparentid = $pageobj->getParentID();
			array_unshift($parentpages,$pageobjtitlelink);
		 	$this->getParentPages($pageobjparentid,false);
		} 
	}
	return $parentpages;
}
	

	/**
	 * Returns the id of the page
	 *
	 * @return string
	 */
	function getID() {
		return $this->get("id");
	}

	/**
	 * Returns the partent id of the page
	 *
	 * @return string
	 */
	function getParentID() {
		return $this->get("parentid");
	}
		
	/**
	 * Returns the title of the page
	 *
	 * @return string
	 */
	function getTitle() {
		return get_language_string($this->get("title"));
	}
	
	/**
	 * Returns the content of the page
	 *
	 * @return string
	 */
	function getContent() {
		return get_language_string($this->get("content"));
	}
	
	/**
	 * Returns the extra content of the page
	 *
	 * @return string
	 */
	function getExtraContent() {
		return get_language_string($this->get("extracontent"));
	}
		
	/**
	 * Returns the sort order of the page
	 *
	 * @return string
	 */
	function getSortOrder() {
		return $this->get("sort_order");
	}
	
	/**
	 * Returns the show status of the page, "1" if published
	 *
	 * @return string
	 */
	function getShow() {
		return $this->get("show");
	}
	
	/**
	 * Returns the titlelink of the page
	 *
	 * @return string
	 */
	function getTitlelink() {
		return $this->get("titlelink");
	}

	/**
	 * Returns the codeblocks of the page as an serialized array
	 *
	 * @return array
	 */
	function getCodeblock() {
		return $this->get("codeblock");
	}

	/**
	 * Returns the author of the page
	 *
	 * @return string
	 */
	function getAuthor() {
		return $this->get("author");
	}

	/**
	 * Returns the date of the page
	 *
	 * @return string
	 */
	function getDateTime() {
		return $this->get("date");
	}
	
	/**
	 * Returns the last change date of the page
	 *
	 * @return string
	 */
	function getLastchange() {
		return $this->get("lastchange");
	}
		
	/**
	 * Returns the last change author of the page
	 *
	 * @return string
	 */
	function getLastchangeAuthor() {
		return $this->get("lastchangeauthor");
	}

	/**
	 * Returns the comment status of the page, "1" if comments are enabled
	 *
	 * @return string
	 */
	function getCommentson() {
		return $this->get("commentson");
	}
	
	/**
	 * Returns the hitcount of the page
	 *
	 * @return string
	 */
	function getHitcounter() {
		return $this->get("hitcounter");
	}
	
	/**
	 * Returns the locked status of the page (only used on admin)
	 *
	 * @return string
	 */
	function getLocked() {
		return $this->get("locked");
	}
	
	/**
	 * Returns the perma link status of the page (only used on admin)
	 *
	 * @return string
	 */
	function getPermalink() {
		return $this->get("permalink");
	}
	
	/****************
	 * Comments
	 ****************/

	/**
	 * Returns an array of comments of the current page
	 *
	 * @param bool $moderated if false, comments in moderation are ignored
	 * @param bool $private if false ignores private comments
	 * @param bool $desc set to true for descending order
	 * @return array
	 */
	function getComments($moderated=false, $private=false, $desc=false) {
		$sql = "SELECT *, (date + 0) AS date FROM " . prefix("comments") .
 			" WHERE `type`='pages' AND `ownerid`='" . $this->get('id') . "'";
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
	 * Adds a comment to the  page
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
	 * Returns the count of comments for the current page. Comments in moderation are not counted
	 *
	 * @return int
	 */
	function getCommentCount() {
		global $_zp_current_zenpage_page;
		$id = $this->get('id');
	
		if (is_null($this->commentcount)) {
			if ($this->comments == null) {
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='pages' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $id);
				$this->commentcount = array_shift($count);
			} else {
				$this->commentcount = count($this->comments);
			}
		} else { // probably because of the slightly different setup of zenpage this extra part is necessary to get the right comment count withn next_comment() loop
			$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='pages' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $id);
			$this->commentcount = array_shift($count);
		}
		return $this->commentcount;
	}
	
}
?>