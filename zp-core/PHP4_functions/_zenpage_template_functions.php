<?php
/**
 * Prints the content of a codeblock for a page or news article
 * 
 * NOTE: This executes PHP and JavaScript code if available
 * 
 * @param int $number The codeblock you want to get
 * @param string $titlelink The titlelink of a specific page you want to get the codeblock of (only for pages!)
 * 
 * @return string
 */
function printCodeblock($number='',$titlelink='') {
	$codeblock = getCodeblock($number,$titlelink);
	eval("?>".$codeblock);
}

?>