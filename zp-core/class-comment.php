<?php
/**
 *Comment Class
 * @package classes
 */

// force UTF-8 Ø
class Comment extends PersistentObject {
	
	/**
	 * This is a simple class so that we have a convienient "handle" for manipulating comments.
	 *
	 * @return Comment
	 */
	
	/**
	 * Constructor for a comment
	 *
	 * @param int $id set to the ID of the comment if not a new one.
	 * @return Comment
	 */
	function Comment($id=NULL) {
		$new = parent::PersistentObject('comments', array('id'=>$id));
	}
	
	function setDefaults() {
		$this->set('date', date('Y-m-d H:i:s'));
	}
	
	// convienence get & set functions
	function getDateTime() { return $this->get('date'); }
	function setDateTime($datetime) {
		if ($datetime == "") {
			$this->set('date', '0000-00-00 00:00:00');
		} else {
			$newtime = dateTimeConvert($datetime);
			if ($newtime === false) return;
			$this->set('date', $newtime);
		}
	}
	
	function getOwnerID() { return $this->get('ownerid'); }
	function setOwnerID($value) { $this->set('ownerid', $value); }
	
	function getName() { return $this->get('name'); }
	function setName($value) { $this->set('name', $value); }
	
	function getEmail() { return $this->get('email'); }
	function setEmail($value) { $this->set('email', $value); }
	
	function getWebsite() { return $this->get('website'); }
	function setWebsite($value) { $this->set('website', $value); }
	
	function getComment() { return $this->get('comment'); }
	function setComment($value) { $this->set('comment', $value); }
	
	function getInModeration() { return $this->get('inmoderation'); }
	function setInModeration($value) { $this->set('inmoderation', $value); }
	
	function getType() {
		$type = $this->get('type');
		$image_types = explode(',',zp_image_types(''));
		if (in_array($type, $image_types)) {
			$type = 'images';
		}
		return $type;
	}
	function setType($type) {
		$image_types = explode(',',zp_image_types(''));
		if (in_array($type, $image_types)) {
			$type = 'images';
		}
		$this->set('type', $type);
	}
	
	function getIP() { return $this->get('ip'); }
	function setIP($value) { $this->set('ip', $value); }
	
	function getPrivate() { return $this->get('private'); }
	function setPrivate($value) { $this->set('private', $value); }
	
	function getAnon() { return $this->get('anon'); }
	function setAnon($value) { $this->set('anon', $value); }
	
	function getCustomData() { return $this->get('custom_data'); }
	function setCustomData($value) { $this->set('custom_data', $value); }
}
?>