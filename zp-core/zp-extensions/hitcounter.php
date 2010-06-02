<?php
/**
 * Provides automatic hitcounter counting for Zenphoto objects
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_description = gettext('Automatically increments hitcounters on Zenphoto objects viewed by a "visitor".');
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.3.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---hitcounter.php.html";

$option_interface = new hitcounter_options();

zp_register_filter('load_theme_script', 'hitcounter_load_script');

/**
 * Plugin option handling class
 *
 */
class hitcounter_options {

	function hitcounter_options() {
		setOptionDefault('hitcounter_ignoreIPList',getUserIP());
		setOptionDefault('hitcounter_ignoreSearchCrawlers', false);
		setOptionDefault('hitcounter_searchCrawlerList', implode(',', array('Teoma', 'alexa', 'froogle', 'Gigabot', 'inktomi',
                      'looksmart', 'URL_Spider_SQL', 'Firefly', 'NationalDirectory',
                      'Ask Jeeves', 'TECNOSEEK', 'InfoSeek', 'WebFindBot', 'girafabot',
                      'crawler', 'www.galaxy.com', 'Googlebot', 'Scooter', 'Slurp',
                      'msnbot', 'appie', 'FAST', 'WebBug', 'Spade', 'ZyBorg', 'rabaz',
                      'Baiduspider', 'Feedfetcher-Google', 'TechnoratiSnoop', 'Rankivabot',
                      'Mediapartners-Google', 'Sogou web spider', 'WebAlta Crawler')));
	}

	function getOptionsSupported() {
		return array(	gettext('Ignore IP Addresses') => array(
							        'key' => 'hitcounter_ignoreIPList',
							        'type' => OPTION_TYPE_TEXTBOX,
							        'desc' => gettext('A comma-separated list of IP addresses to ignore. Defaults to the server IP address.'),
									),
									gettext('Ignore Search Crawlers') => array(
							        'key' => 'hitcounter_ignoreSearchCrawlers',
							        'type' => OPTION_TYPE_CHECKBOX,
							        'desc' => gettext('Ignore hits from search crawlers.'),
									),
									gettext('Search Crawler List') => array(
							        'key' => 'hitcounter_searchCrawlerList',
							        'type' => OPTION_TYPE_TEXTAREA,
							        'desc' => gettext('Comma-separated list of search bot user agent names.'),
									)
		);
	}

}

function hitcounter_load_script($obj) {
	$ignoreIPList = explode(',', str_replace(' ', '', getOption('hitcounter_ignoreIPList')));
	$ignoreSearchCrawlers = getOption('hitcounter_ignoreSearchCrawlers');

	$skip = false;

	if (in_array($_SERVER['REMOTE_ADDR'], $ignoreIPList)) {
		$skip = true;
	}

	if ($ignoreSearchCrawlers && !$skip) {
		$botList = explode(',', str_replace(', ', ',', getOption('hitcounter_searchCrawlerList')));

		foreach($botList as $bot) {
			if(stripos($_SERVER['HTTP_USER_AGENT'], $bot)) {
				$skip = true;
				break;
			}
		}
	}

	if (!$skip) {
		global $_zp_gallery_page, $_zp_current_album, $_zp_current_image, $_zp_current_zenpage_news, $_zp_current_zenpage_page;
		$hint = $show = false;
		if (!checkforPassword($hint, $show)) { // count only if permitted to access
			switch ($_zp_gallery_page) {
				case 'album.php':
					if (!isMyALbum($_zp_current_album->name, ALBUM_RIGHTS) && getCurrentPage() == 1) {
						$hc = $_zp_current_album->get('hitcounter')+1;
						$_zp_current_album->set('hitcounter', $hc);
						$_zp_current_album->save();
					}
					break;
				case 'image.php':
					if (!isMyALbum($_zp_current_album->name, ALBUM_RIGHTS)) { //update hit counter
						$hc = $_zp_current_image->get('hitcounter')+1;
						$_zp_current_image->set('hitcounter', $hc);
						$_zp_current_image->save();
					}
					break;
				case ZENPAGE_PAGES:
					if (!zp_loggedin(ZENPAGE_PAGES_RIGHTS)) {
						$hc = $_zp_current_zenpage_page->get('hitcounter')+1;
						$_zp_current_zenpage_page->set('hitcounter', $hc);
						$_zp_current_zenpage_page->save();
					}
					break;
				case ZENPAGE_NEWS:
					if (!zp_loggedin(ZENPAGE_NEWS_RIGHTS)) {
						if(is_NewsArticle()) {
							$hc = $_zp_current_zenpage_news->get('hitcounter')+1;
							$_zp_current_zenpage_news->set('hitcounter', $hc);
							$_zp_current_zenpage_news->save();
						} else if(is_NewsCategory()) {
							$catname = sanitize($_GET['category'],3);
							query("UPDATE ".prefix('zenpage_news_categories')." SET `hitcounter` = `hitcounter`+1 WHERE `cat_link` = '".zp_escape_string($catname)."'",true);
						}
					}
					break;
				default:
					if (!zp_loggedin()) {
						$page = stripSuffix($_zp_gallery_page);
						setOption('Page-Hitcounter-'.$page, getOption('Page-Hitcounter-'.$page)+1);
					}
					break;
			}
		}
	}
	return $obj;
}
?>