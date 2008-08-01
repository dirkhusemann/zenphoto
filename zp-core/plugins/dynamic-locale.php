<?php
/**
 * dynamic-locale -- plugin to allow the site viewer to select a localization.
 * This applies only to the theme pages--not Admin. Admin continues to use the
 * language option for its language.
 * 
 * Only the zenphoto and theme gettext() string are localized by this facility. 
 * 
 * If you want to support image descriptions, etc. in multiple languages you will
 * have to enable the multi-lingual option found next to the language selector on
 * the admin gallery configuration page. Then you will have to provide appropriate 
 * alternate translations for the fields you use. While there will be a place for 
 * strings for all zenphoto supported languages you need supply only those you choose. 
 * The others language strings will default to your local language. 
 *
 * Uses cookies to store the individual selection. Sets the 'locale' option
 * to the selected language (non-persistent.)
 * 
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins 
 */
$plugin_description = gettext("Enable <strong>dynamic-locale</strong> to allow viewers of your site to select the language translation of their choice.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---dynamic-locale.php.html";

/**
 * prints a form for selecting a locale
 * The POST handling is by getUserLocale() called in functions.php
 *
 */
function printLanguageSelector($class='') {
	global $_zp_languages;
	if (isset($_POST['dynamic-locale'])) {
		$locale = sanitize($_POST['dynamic-locale']);
		if (getOption('locale') != $locale) {
			echo '<h2><em>'.$_zp_languages[$locale].'</em> '.gettext("is not available.").
					 ' '.gettext("The locale").' '.$locale.' '.gettext("is not supported on your server.").'</h2>';
		}
	}
	if (!empty($class)) { $class = " class='$class'"; }
	echo "\n<div$class>\n";
	echo '<form action="#" method="post">'."\n";
	echo '<input type="hidden" name="oldlocale" value="'.getOption('locale').'" />';
	echo gettext("Select a language:").' ';
	echo '<select id="dynamic-locale" name="dynamic-locale" onchange="this.form.submit()">'."\n";
	generateLanguageOptionList();
	echo "</select>\n";
	echo "</form>\n";
	echo "</div>\n";
}

?>