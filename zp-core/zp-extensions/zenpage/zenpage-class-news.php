<?php
/**
 * zenpage news class
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */

class ZenpageNews extends PersistentObject {
	
	var $comments = NULL;//Contains an array of the comments of the current article
	var $commentcount; //Contains the number of comments
	
	function ZenpageNews($titlelink) {
		$titlelink = sanitize($titlelink);
		if (!is_string($titlelink) || empty($titlelink)) return NULL;
		$new = parent::PersistentObject('zenpage_news', array('titlelink'=>$titlelink), NULL, true);
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
	
	/**
	 * Gets the categories assigned to an news article
	 *
	 * @param int $article_id ID od the article
	 * @return array
	 */
	function getCategories() {
		$categories = query_full_array("SELECT cat.cat_name, cat.cat_link FROM ".prefix('zenpage_news_categories')." as cat,".prefix('zenpage_news2cat')." as newscat WHERE newscat.cat_id = cat.id AND newscat.news_id = ".$this->getID()." ORDER BY cat.cat_name");
		return $categories;
	}

/**
	 * Returns the expire date  of the news article
	 *
	 * @return string
	 */
	function getExpireDate() {
		$dt = $this->get("expiredate");
		if ($dt == '0000-00-00 00:00:00') {
			return NULL;
		} else {
			return $dt;
		}
	}
	
/**
	 * Returns the tag data of an album
	 *
	 * @return string
	 */
	function getTags() {
		return readTags($this->id, 'zenpage_news');
	}

	/**
	 * Stores tag information of an album
	 *
	 * @param string $tags the tag list
	 */
	function setTags($tags) {
		if (!is_array($tags)) {
			$tags = explode(',', $tags);
		}
		storeTags($tags, $this->id, 'zenpage_news');
	}
	
	
	/****************
	 * Comments
	 ****************/

	/**
	 * Returns true of comments are allowed
	 *
	 * @return bool
	 */
	function getCommentsAllowed() { return $this->get('commentson'); }
	
	
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
	 * Returns a comment object
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
	 * @return object
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
		$id = $this->getID();
		if (is_null($this->commentcount)) {
			if ($this->comments == null) {
				$count = query_single_row("SELECT COUNT(*) FROM " . prefix("comments") . " WHERE `type`='news' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $id);
				$this->commentcount = array_shift($count);
			} else {
				$this->commentcount = count($this->comments);
			}
		}
		return $this->commentcount;
	}
		
} // zenpage news class end


?>