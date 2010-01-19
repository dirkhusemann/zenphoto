<?php
/**
 * zenpage page class
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */

class ZenpagePage extends PersistentObject {
	
	var $comments = NULL;//Contains an array of the comments of the current article
	var $commentcount; //Contains the number of comments
	
	function ZenpagePage($titlelink) {
		$titlelink = sanitize($titlelink);
		if (!is_string($titlelink) || empty($titlelink)) return NULL;
		$new = parent::PersistentObject('zenpage_pages', array('titlelink'=>$titlelink), NULL, true);
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
	
/**
	 * Returns the expire date  of the page
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
		return readTags($this->id, 'zenpage_pages');
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
		storeTags($tags, $this->id, 'zenpage_pages');
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
		}
		return $this->commentcount;
	}

}
?>