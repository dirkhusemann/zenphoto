<?php
/**
 * Prints the content of a codeblock for an image, album or Zenpage newsarticle or page.
 * 
 * NOTE: This executes PHP and JavaScript code if available
 * 
 * @param int $number The codeblock you want to get
 * @param string $titlelink The titlelink of a specific page you want to get the codeblock of (only for Zenpage pages!)
 * 
 * @return string
 */
function printCodeblock($number='',$titlelink='') {
	$codeblock = getCodeblock($number,$titlelink);
	$context = get_context();
	try {
		@eval("?>".$codeblock);
	} catch (Exception $e) {
		debugLog('printCodeblock('.$number.','.$titlelink.') exception: '.$e->getMessage());
	}
	set_context($context);
}
?>