<?php
/**
 * Processes a new comment posting, sending the post to all who have previously commented.
 * NB: this is an example filter. It does not, for instance, check to see if a previous poster is also
 * an admin receiving email notifications anyway. 
 * 
 * @package plugins
 */
$plugin_description = gettext("Email all posters when a new comment is made on an item.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-comments.php.html";

register_filter('comment_post', 'emailReply', 2);

/**
 * Filters a new comment post and sends email replies to previous posters
 * @param object $comment the comment
 * @param object $owner the element commented upon.
 */
function emailReply($comment, $owner) {
	if ($comment->getInModeration()) {
		return $comment;  // we are not going to e-mail unless the comment has passed.
	}
	$oldcomments = $owner->comments;
	$emails = array();
	foreach ($oldcomments as $oldcomment) {
		$emails[] = $oldcomment['email'];
	}
	$emails = array_unique($emails);
	switch ($comment->getType()) {
		case "albums":
			$url = "album=" . urlencode($owner->name);
			$ur_album = getUrAlbum($owner);
			$action = sprintf(gettext('A reply has been posted on album "%1$s".'), $owner->name);
			break;
		case "zenpagenews":
			$url = "p=".ZENPAGE_NEWS."&title=" . urlencode($owner->getTitlelink());
			$action = sprintf(gettext('A reply has been posted on article "%1$s".'), $owner->getTitlelink());
			break;
		case "zenpagepage":
			$url = "p=".ZENPAGE_PAGES."&title=" . urlencode($owner->getTitlelink());
			$action = sprintf(gettext('A reply has been posted on page "%1$s".'), $owner->getTitlelink());
			break;
		default: // all image types
			$url = "album=" . urlencode($owner->album->name) . "&image=" . urlencode($owner->filename);
			$album = $owner->getAlbum();
			$ur_album = getUrAlbum($album);
			$action = sprintf(gettext('A reply has been posted on "%1$s" the album "%2$s".'), $owner->getTitle(), $owner->getAlbumName());
	}

	$message = $action . "\n\n" . 
							sprintf(gettext('Author: %1$s'."\n".'Email: %2$s'."\n".'Website: %3$s'."\n".'Comment:'."\n\n".'%4$s'),$comment->getname(), $comment->getEmail(), $comment->getWebsite(), $comment->getComment()) . "\n\n" .
							sprintf(gettext('You can view all comments about this item here:'."\n".'%1$s'), 'http://' . $_SERVER['SERVER_NAME'] . WEBPATH . '/index.php?'.$url) . "\n\n";
	$on = gettext('Reply posted');
	zp_mail("[" . get_language_string(getOption('gallery_title'), getOption('locale')) . "] $on", $message, "", $emails);
	
}
?>