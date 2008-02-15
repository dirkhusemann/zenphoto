<?php

/* this is a simple SPAM filtering. 
 *   It uses a word black list and checks for excessive URLs
 * 
 * Interface functions:
 *     getOptionsSupported()
 *        called from admin Options tab
 *        returns an array of the option names the theme supports
 *        the array is indexed by the option name. The value for each option is an array:
 *          'type' => 0 says for admin to use a standard textbox for the option
 *          'type' => 1 says for admin to use a standard checkbox for the option
 *          'type' => 2 will cause admin to call handleOption to generate the HTML for the option
 *          'desc' => text to be displayed for the option description.
 *             
 *     handleOption($option, $currentValue)
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
 *
 *  This filter was adapted from "simple-filter.php" written by Joe Tan; http://tantannoodles.com/
 *
 */
 
class SpamFilter  {

	var $iSupport = array('Words_to_die_on' => array('type' => 2, 'desc' => 'SPAM blacklist words (separate with commas)'),
												'Patterns_to_die_on' => array('type' => 2, 'desc' => 'SPAM blacklist <a href="http://en.wikipedia.org/wiki/Regular_expression">regular expressions</a> (separate with spaces)'),
												'Excessive_URL_count' => array('type' => 0, 'desc' => 'Message is considered SPAM if there are more than this many URLs in it'),
												'Forgiving' => array('type' => 1, 'desc' => 'Mark suspected SPAM for moderation rather than as SPAM'));
												
	var $wordsToDieOn = array('cialis','ebony','nude','porn','porno','pussy','upskirt','ringtones','phentermine','viagra', 'levitra'); /* the word black list */
	
	var $patternsToDieOn = array('\[url=.*\]');
	
	var $excessiveURLCount = 5;
	
	function SpamFilter() {
		setOptionDefault('Words_to_die_on', implode(',', $this->wordsToDieOn));
		setOptionDefault('Patterns_to_die_on', implode(' ', $this->patternsToDieOn));
		setOptionDefault('Excessive_URL_count', $this->excessiveURLCount);
		setOptionDefault('Forgiving', 0);
}
	
	function getOptionsSupported() {
		return $this->iSupport;
	}
 	function handleOption($option, $currentValue) {
 		if ($option=='Words_to_die_on') {
 			$list = explode(',', $currentValue);
 			sort($list);
	 	echo '<textarea name="' . $option . '" cols="42" rows="4">' . implode(',', $list) . "</textarea>\n";
 		} else if ($option=='Patterns_to_die_on') {
	 	echo '<textarea name="' . $option . '" cols="42" rows="2">' . $currentValue . "</textarea>\n";
	 }
	}

	function filterMessage($author, $email, $website, $body, $imageLink) {
		$forgive = getOption('Forgiving');
		$list = getOption('Words_to_die_on');
		$list = strtolower($list);
		$this->wordsToDieOn = explode(',', $list);
		$list = getOption('Patterns_to_die_on');
		$list = strtolower($list);
		$this->patternsToDieOn = explode(' ', $list);
		$this->excessiveURLCount = getOption('Excessive_URL_count');
		$die = 2;  // good comment until proven bad
		if ($body) {
			if (($num = substr_count($body, 'http://')) >= $this->excessiveURLCount) { // too many links
				$die = $forgive;
			} else {
				if ($pattern = $this->hasSpamPattern($body)) {
					$die = $forgive;
				} else {
					if ($spamWords = $this->hasSpamWords($body)) {
						$die = $forgive;
					}
				}
			}
		}
		return $die;  
	}

	function hasSpamPattern($text) {
		$patterns = $this->patternsToDieOn;
		foreach ($patterns as $pattern) {
			if (eregi('('.trim($pattern).')', $text, $matches)) {
				return $matches[1];
			}
		}
		return false;
	}
	
	function hasSpamWords($text) {
		$words = $this->getWords($text);
		$blacklist = $this->wordsToDieOn;
		$intersect = array_intersect($blacklist , $words);
		return $intersect ;
	}
	
	function getWords($text, $notUnique=false) {
		if ($notUnique) {
			return preg_split("/[\W]+/", strtolower(strip_tags($text)));
		} else {
			return array_unique(preg_split("/[\W]+/", strtolower(strip_tags($text))));
		}
	}

}

?>
