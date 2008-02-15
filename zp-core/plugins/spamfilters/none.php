<?php

/* this is a shell plugin for SPAM filtering. It does nothing, but serves as the template
 * for more robust SPAM filters
 * 
 * Interface functions:
 *     getFilterOptionsSupported()
 *        called from admin Options tab
 *        returns an array of the option names the theme supports
 *        the array is indexed by the option name. The value for each option is an array:
 *          'type' => 0 says for admin to use a standard textbox for the option
 *          'type' => 1 says for admin to use a standard checkbox for the option
 *          'type' => 2 will cause admin to call handleOption to generate the HTML for the option
 *          'desc' => text to be displayed for the option description.
 *             
 *     handleFilterOption($option, $currentValue)
 *       $option is the name of the option being processed
 *       $currentValue is the "before" value of the option
 *
 *       this function is called by admin from within the table row/column where the option field is placed
 *       It must write the HTML that does the option handling UI
 *
 *     filterMessage($author, $email, $website, $body, $imageLink)
 *       $author is the author field of the comment
 *       $email is the email field of the comment
 *       $website is the website field of the comment
 *       $body is the comment text
 *       $imageLink is the url to the full image
 *
 *       called from class-image as we are about to post the comment to the database and send an email
 * 
 *       returns:
 *         0 if the message is SPAM
 *         1 if the message might be SPAM (it will be marked for moderation)
 *         2 if the message is not SPAM
 *
 *       class-image conditions the database store and email on this result.
 */
 
class SpamFilter  {
 
	var $iSupport = array('Action' => array('type' => 2, 'desc' => 'This action will be taken for all messages.'));
 
	function SpamFilter() {
		global $gallery;
		setOptionDefault('Action', 'pass');
	}

	function getOptionsSupported() {
		return $this->iSupport;
	}
	function handleOption($option, $currentValue) {
		if ($option == 'Action') {
			echo "<select id=\"Action\" name=\"Action\">";
			generateListFromArray(array($currentValue), array('pass', 'moderate', 'reject'));
			echo "</select>";
		}
	}

	function filterMessage($author, $email, $website, $body, $imageLink) {
		$strategy = getOption('Action');
		switch ($strategy) {
			case 'reject': return 0;
			case 'moderate': return 1;
		}
		return 2;
	}
}

?>
