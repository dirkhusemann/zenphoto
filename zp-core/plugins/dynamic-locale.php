<?php
/**
 * dynamic-locale -- plugin to allow the site viewer to select a localization.
 * This applies only to the theme pages--not Admin. Admin continues to use the
 * language option for its language.
 * 
 * Only the zenphoto and theme gettext() string are localized by this facility. 
 * if you want to support image descriptions, etc. in multiple languages you will
 * have to add code to your theme to do so. We suggest you create a set of custom 
 * functions to handle database strings. You will also need a scheme for storing
 * multiple versions of your text.
 * 
 * In your custom function use a switch statement to see which language is selected
 * and output the appropriate translation.
 * switch (getOption('locale')) {
 *   case 'de_DE': <German string>; break;
 *   case 'fr_FR': <French string>; break;
 *   etc.
 * }
 *
 * Uses cookies to store the individual selection. Sets the 'locale' option
 * to the selected language (non-persistent.)
 * 
 * 
 *
 */
$plugin_description = gettext("Enable <strong>dynamic-locale</strong> to allow viewers of your site to select the language translation of their choice.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---dynamic-locale.php.html";

/**
 * Returns a saved (or posted) locale. Posted locales are stored as a cookie.
 *
 * Sets the 'locale' option to the result (non-persistent)
 */
function getUserLocale() {
	global $_zp_languages;
	$cookiepath = WEBPATH;
	if (WEBPATH == '') { $cookiepath = '/'; }
	if (isset($_POST['dynamic-locale'])) {
		$locale = sanitize($_POST['dynamic-locale']);
		zp_setCookie('dynamic_locale', $locale, time()+5184000, $cookiepath);
	} else {
		$locale = zp_getCookie('dynamic_locale');
		if ($locale === false) {  // if one is not set, see if there is a match from 'HTTP_ACCEPT_LANGUAGE'
			$userLang = parseHttpAcceptLanguage();
			foreach ($userLang as $lang) {
				$l = strtoupper($lang['fullcode']);
				$languageSupport = generateLanguageList();
				foreach ($languageSupport as $key=>$value) {
					if (strtoupper($key) == $l) { // we got a match
						$locale = $key;
						break;
					}
				}
			}
		}
	}
	if ($locale !== false) {
		setOption('locale', $locale, false);
	}
}

/**
 * prints a form for selecting a locale
 * The POST handling is by getUserLocale() called in functions.php
 *
 */
function printLanguageSelector($class='') {
	if (!empty($class)) { $class = " class='$class'"; }
	echo "\n<div$class>\n";
	echo '<form action="#" method="post">'."\n";
	echo gettext("Select a language:").' ';
	echo '<select id="dynamic-locale" name="dynamic-locale" onchange="this.form.submit()">'."\n";
	generateLanguageOptionList();
	echo "</select>\n";
	echo "</form>\n";
	echo "</div>\n";
}

?>