<?php
/**
 * functions-i18n.php -- support functions for internationalization
 * @package core
 */
/**
 * Returns an array of available language locales.
 * 
 * @return array
 *
 */
function generateLanguageList() {
	global $_zp_languages;
	$dir = @opendir(SERVERPATH . "/" . ZENFOLDER ."/locale/");
	$locales = array();
	
	if (OFFSET_PATH === 1) {  // for admin only
		$locales[gettext("HTTP Accept Language")] = '';
	}
	if ($dir !== false) {
		while ($dirname = readdir($dir)) {
			if (is_dir(SERVERPATH . "/" . ZENFOLDER ."/locale/".$dirname) && (substr($dirname, 0, 1) != '.')) {
				$language = $_zp_languages[$dirname];
				if (empty($language)) {
					$language = $dirname;
				}
				$locales[$language] = $dirname;
			}
		}
		closedir($dir);
	}
	return $locales;
}

/**
 * Generates the option list for the language selectin <select>
 *
 */
function generateLanguageOptionList() {
	$locales = generateLanguageList();
	generateListFromArray(array(getOption('locale', OFFSET_PATH===1)), $locales);
}


/**
 * Sets the optional textdomain for separate translation files for plugins.
 * The plugin translation files must be located within 
 * zp-core/plugins/<plugin name>/locale/<language locale>/LC_MESSAGES/ and must 
 * have the name of the plugin (<plugin name>.po  <plugin name>.mo)
 *
 * @param string $plugindomain The name of the plugin
 */
function setPluginDomain($plugindomain='') {
	if(!empty($plugindomain)) {
		setupCurrentLocale($plugindomain);
	}
}

/**
 * Setup code for gettext translation
 *
 */
function setupCurrentLocale($plugindomain='') {
	global $_zp_languages;
	if(empty($plugindomain)) {
	$encoding = getOption('charset');
	if (empty($encoding)) $encoding = 'UTF-8';
	$locale = getOption("locale");
	@putenv("LANG=$locale");
	// gettext setup
	setlocale(LC_ALL, $locale);
	
	// Set the text domain as 'messages'
	$domain = 'zenphoto';
	$domainpath = SERVERPATH . "/" . ZENFOLDER . "/locale/";
	} else {
	$domain = $plugindomain;
	$domainpath = SERVERPATH . "/" . ZENFOLDER . "/plugins/".$domain."/locale/";
	}
	bindtextdomain($domain, $domainpath);
	// function only since php 4.2.0
	if(function_exists('bind_textdomain_codeset')) {
		bind_textdomain_codeset($domain, $encoding);
	}
	textdomain($domain);
	$_zp_languages = array(
		'af' => gettext('Afrikaans'),
		'ar' => gettext('Arabic'),
		'bn_BD' => gettext('Bengali'),
		'eu' => gettext('Basque'),
		'be_BY' => gettext('Belarusian'),
		'bg_BG' => gettext('Bulgarian'),
		'ca' => gettext('Catalan'),
		'zh_CN' => gettext('Chinese'),
		'zh_HK' => gettext('Chinese Hong Kong'),
		'zh_TW' => gettext('Chinese Taiwan'),
		'hr' => gettext('Croatian'),
		'cs_CZ' => gettext('Czech'),
		'da_DK' => gettext('Danish'),
		'nl_NL' => gettext('Dutch'),
		'en_US' => gettext('English (US)'),
		'en_UK' => gettext('English (UK)'),
		'eo' => gettext('Esperanto'),
		'et' => gettext('Estonian'),
		'fa_IR' => gettext('Persian'), 
		'fo' => gettext('Faroese'),
		'fi_FI' => gettext('Finnish'),
		'fr_FR' => gettext('French'),
		'gl_ES' => gettext('Galician'),
		'de_DE' => gettext('German'),
		'el' => gettext('Greek'),
		'he_IL' => gettext('Hebrew'),
		'hu_HU' => gettext('Hungarian'),
		'is_IS' => gettext('Icelandic'),
		'id_ID' => gettext('Indonesian'),
		'it_IT' => gettext('Italian'),
		'km_KH' => gettext('Cambodian'),
		'ko_KR' => gettext('Korean'),
		'lv' => gettext('Latvian'),
		'lt' => gettext('Lithuanian'),
		'mk_MK' => gettext('Macedonian'),
		'mg_MG' => gettext('Malagasy'),
		'ms_MY' => gettext('Malay'),
		'ni_ID' => gettext('Nias'),
		'nb_NO' => gettext('Norwegian'),
		'pl_PL' => gettext('Polish'),
		'pt_BR' => gettext('Brazilian Portuguese'),
		'pt_PT' => gettext('European Portuguese'),
		'ro' => gettext('Romanian'),
		'ru_RU' => gettext('Russian'),
		'sr_RS' => gettext('Serbian'),
		'si_LK' => gettext('Sinhala'),
		'sl_SI' => gettext('Slovenian'),
		'sk' => gettext('Slovak'),
		'es_ES' => gettext('Spanish'),
		'sv_SE' => gettext('Swedish'),
		'th' => gettext('Thai'),
		'tr' => gettext('Turkish'),
		'ua_UA' => gettext('Ukrainian'),
		'uz_UZ' => gettext('Uzbek'),
		'vi_VN' => gettext('vi_VN'),
		'cy' => gettext('Welsh')
		);
}

/**
 * This function will parse a given HTTP Accepted language instruction 
 * (or retrieve it from $_SERVER if not provided) and will return a sorted 
 * array. For example, it will parse fr;en-us;q=0.8 
 * 
 * Thanks to Fredbird.org for this code.
 *
 * @param string $str optional language string
 * @return array
 */
function parseHttpAcceptLanguage($str=NULL) {
	if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return array();
	// getting http instruction if not provided
	$str=$str?$str:$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	// exploding accepted languages 
	$langs=explode(',',$str);
	// creating output list
	$accepted=array();
	foreach ($langs as $lang) {
		// parsing language preference instructions
		// 2_digit_code[-longer_code][;q=coefficient]
		ereg('([a-z]{1,2})(-([a-z0-9]+))?(;q=([0-9\.]+))?',$lang,$found);
		// 2 digit lang code
		$code=$found[1];
		// lang code complement
		$morecode=$found[3];
		// full lang code
		$fullcode=$morecode?$code.'_'.$morecode:$code;
		// coefficient
		$coef=sprintf('%3.1f',$found[5]?$found[5]:'1');
		// for sorting by coefficient
		$key=$coef.'-'.$code;
		// adding 
		$accepted[$key]=array('code'=>$code,'coef'=>$coef,'morecode'=>$morecode,'fullcode'=>$fullcode);
	}
	// sorting the list by coefficient desc
	krsort($accepted);
	return $accepted;
}

/**
 * Returns a saved (or posted) locale. Posted locales are stored as a cookie.
 *
 * Sets the 'locale' option to the result (non-persistent)
 */
function getUserLocale() {
	$cookiepath = WEBPATH;
	if (WEBPATH == '') { $cookiepath = '/'; }
	if (isset($_POST['dynamic-locale'])) {
		$locale = sanitize($_POST['dynamic-locale']);
		zp_setCookie('dynamic_locale', $locale, time()+5184000, $cookiepath);
	} else {
		$localeOption = getOption('locale');
		$locale = zp_getCookie('dynamic_locale');
		if (empty($localeOption) && ($locale === false)) {  // if one is not set, see if there is a match from 'HTTP_ACCEPT_LANGUAGE'
			$languageSupport = generateLanguageList();
			$userLang = parseHttpAcceptLanguage();			
			foreach ($userLang as $lang) {
				$l = strtoupper($lang['fullcode']);
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

?>