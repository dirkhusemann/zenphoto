<?php 
/**
 * Zenpage CMS plugin
 * 
 * @package plugins
 */
 $plugin_version = '1.2.9'; 
$plugin_description = gettext("A CMS plugin that adds the capability to run an entire gallery focused website with zenphoto. <br />" 
				."<strong>NOTE:</strong> This feature must be integrated into your theme. Of the distributed themes, only <code>zenpage default</code> and <code>effervescence+</code> themes support Zenpage.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/zenpage/_".PLUGIN_FOLDER."---zenpage---zenpage-template-functions.php.html";
$option_interface = new zenpagecms();

$zenpage_version = $plugin_version;

class zenpagecms {

	function zenpagecms() {
		setOptionDefault('zenpage_articles_per_page', '10');
		setOptionDefault('zenpage_text_length', '500');
		setOptionDefault('zenpage_textshorten_indicator', ' (...)');
		setOptionDefault('zenpage_read_more', 'Read more');
		setOptionDefault('zenpage_rss_items', '10');
		setOptionDefault('zenpage_rss_length', '100');
		setOptionDefault('zenpage_admin_articles', '15');
		setOptionDefault('zenpage_news_page', 'news');
		setOptionDefault('zenpage_pages_page', 'pages');
		setOptionDefault('zenpage_indexhitcounter', false); 
		setOptionDefault('zenpage_combinews', false);
		setOptionDefault('zenpage_combinews_readmore', 'Visit gallery page');
		setOptionDefault('zenpage_combinews_mode', 'latestimage-sizedimage');
		setOptionDefault('zenpage_combinews_imagesize', '300');
		setOptionDefault('zenpage_combinews_sortorder', 'mtime');
		setOptionDefault('zenpage_combinews_gallerylink', 'image');
		setOptionDefault('zenpage_tinymce_config', 'light');
	}

	function getOptionsSupported() {
		return array(gettext('Articles per page (theme)') => array('key' => 'zenpage_articles_per_page', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("How many news articles you want to show per page on the news or news category pages.")),
		gettext('News article text length') => array('key' => 'zenpage_text_length', 'type' => OPTION_TYPE_TEXTBOX, 
									'desc' => gettext("The length of news article excerpts in news or news category pages. Leave blank for full text.")),
		gettext('News article text shorten indicator') => array('key' => 'zenpage_textshorten_indicator', 'type' => OPTION_TYPE_TEXTBOX, 
									'desc' => gettext("Something that indicates that the article text is shortened, ' (...)' by default.")),
		gettext('Read more') => array('key' => 'zenpage_read_more', 'type' => OPTION_TYPE_TEXTBOX, 'multilingual' => 1,
										'desc' => gettext("The text for the link to the full article.")),
		gettext('RSS feed item number') => array('key' => 'zenpage_rss_items', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The number of news articles you want to appear in your site's News RSS feed.")),
		gettext('RSS feed textlength') => array('key' => 'zenpage_rss_length', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The text length of the zenpage RSS feed items. No value for full length.")),
		gettext('News articles per page (admin)') => array('key' => 'zenpage_admin_articles', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("How many news articles you want to show per page on the news article admin page.")),
		gettext('News page name') => array('key' => 'zenpage_news_page', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("If you want to rename the theme page <em>news.php</em> that is used to display news, you need to enter the new name without the .php suffix here. <strong>NOTE: </strong>If you use mod_rewrite you need to modify your <em>.htaccess</em> file manually, too!")),
		gettext('Pages page name') => array('key' => 'zenpage_pages_page', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("If you want to rename the theme page <em>pages.php</em> that is used to display pages, you need to enter the new name without the .php suffix here. <strong>NOTE: </strong>If you use mod_rewrite you need to modify your <em>.htaccess</em> file manually, too!")),
		gettext('CombiNews') => array('key' => 'zenpage_combinews', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("Set to enable the CombiNews feature to show news articles and latest images or albums together on the news section's overview page(s). <strong>NOTE:</strong> Images/albums and news articles are still separate, your Zenphoto gallery is not touched in any way! <strong>IMPORTANT: This feature requires MySQL 4.1 or later.</strong>")),
		gettext('CombiNews: Gallery page link') => array('key' => 'zenpage_combinews_readmore', 'type' => OPTION_TYPE_TEXTBOX, 'multilingual' => 1,
										'desc' => gettext("The text for the 'read more'/'view more' link to the gallery page for images/movies/audio.")),
		gettext('CombiNews: Mode') => array('key' => 'zenpage_combinews_mode', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext('latest images: sized image') => "latestimages-sizedimage", gettext('latest images: thumbnail') => "latestimages-thumbnail", gettext('latest albums: sized image') => "latestalbums-sizedimage", gettext('latest albums: thumbnail') => "latestalbums-thumbnail"),
										'desc' => gettext("What you want to show within the CombiNews section.")),
		gettext('CombiNews: Sized image size') => array('key' => 'zenpage_combinews_imagesize', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The size of the sized image shown the CombiNews section <em>(only in latest images-sizedimage or latest albums-sizedimage mode)</em>.")),
		gettext('CombiNews: Sortorder') => array('key' => 'zenpage_combinews_sortorder', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext('date') => "date", gettext('id') => "id", gettext('mtime') => "mtime"),
										'desc' => gettext("The sort order for your gallery items within the CombiNews display. 'date' (date order), 'id' (added to db order), 'mtime' (upload order). NOTE: If you experience unexpected results, this refers only to the images that are fetched from the database. Even if they are fetched by id or mtime they will be sorted by date with the articles afterwards since articles only have a date.")),
		gettext('CombiNews: Gallery link') => array('key' => 'zenpage_combinews_gallerylink', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext('image') => "image", gettext('album') => "album"),
										'desc' => gettext("Choose if you want to link from the image entry it's image page directly or to the album page (if CombiNews mode is set for albums the link is automatically only linking to albums). This affects all links of the entry (<em>title</em>, <em>image</em> and the <em>visit gallery link</em>")),
		gettext('TinyMCE configuration') => array('key' => 'zenpage_tinymce_config', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => array(gettext('full') => "full", gettext('light') => "light"),
										'desc' => gettext("Choose if you want to load the TinyMCE text editor for pages and articles light or full featured.")),
		);
	}

	function handleOption($option, $currentValue) {
	}
}

require_once(dirname(__FILE__)."/zenpage/zenpage-template-functions.php");

?>
