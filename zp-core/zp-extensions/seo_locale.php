<?php
/**
 * Provides for using URLs to force language selection.
 * This filter will detect a language setting from the URI and
 * set the locale accordingly.
 * 
 * The URL format is:
 * mod_rewrite
 *			/<languageid>/<standard url>
 * else
 * 			<standard url>?locale=<languageid>
 * Where <languageid> is the local identifier (e.g. en, en_US, fr_FR, etc.)
 * 
 * @author Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_is_filter = 5;
$plugin_description = gettext("Allows setting language locale through the URI.").'<p class="notebox">'.gettext('<strong>Note:</strong> This plugin is not activated for <em>back-end</em> (administrative) URLs. However, once activated, the language is remembered, even for the <em>back-end</em>.').'</p>';
$plugin_author = "Stephen Billard (sbillard)";
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---seo_locale.html";
$plugin_version = '1.3.0'; 

zp_register_filter('load_request', 'filterLocale_load_request');

function filterLocale_load_request() {
	if (isset($_GET['locale'])) {
		$l = strtoupper(sanitize($_GET['locale'],3));
	} else {
		$uri = urldecode(sanitize($_SERVER['REQUEST_URI'], 0));
		$path = substr($uri, strlen(WEBPATH)+1);
		$path = str_replace('\\','/',$path);
		if (substr($path,0,1) == '/') $path = substr($path,1);
		if (empty($path)) {
			$l = false;
		} else {
			$rest = strpos($path, '/');
			if ($rest === false) {
				$l = strtoupper($path);
			} else {
				$l = strtoupper(substr($path,0,$rest));
			}
		}
	}
	$languageSupport = generateLanguageList();
	$locale = NULL;
	if (!empty($l)) {
		foreach ($languageSupport as $key=>$value) {
			if (strtoupper($value) == $l) { // we got a match
				$locale = $value;
				break;
			} else if (preg_match('/^'.$l.'/', strtoupper($value))) { // we got a partial match
				$locale = $value;
				break;
			}
		}
	}
	if ($locale) {
		zp_setCookie('dynamic_locale', $locale);
		setupCurrentLocale($locale);
		if (!isset($_GET['locale'])) {  // we need to re-direct
			if (substr($path, -1, 1) == '/') $path = substr($path, 0, strlen($path)-1);
			$path = FULLWEBPATH.substr($path, strlen($l));
			if (strpos($path, '?') === false) {
				$uri = $path.'?locale='.$locale;
			} else {
				$uri = $path.'&locale='.$locale;
			}
			header("HTTP/1.0 302 Found");
			header("Status: 302 Found");
			header('Location: '.$uri);
			exit();
		}
	}
}
?>