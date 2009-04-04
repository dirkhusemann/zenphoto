<?php
/** 
 * A plugin to print the most common html meta tags to the head of your site's pages using general existing Zenphoto info like gallery description, tags or Zenpage news categories.
 *  
 * @author Malte Müller (acrylian)
 * @version 1.0
 * @package plugins 
 */

$plugin_description = gettext("A plugin to print the most common html meta tags to the head of your site's pages using general existing Zenphoto info like gallery description, tags or Zenpage news categories."); 
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0';
$plugin_URL = "";
$option_interface = new htmlmetatags();

class htmlmetatags {

	function htmlmetatags() {
		setOptionDefault('htmlmeta_cache_control', 'no-cache');
		setOptionDefault('htmlmeta_pragma', 'no-cache');
		setOptionDefault('htmlmeta_robots', 'index');
		setOptionDefault('htmlmeta_revisit_after', '10 Days');
		setOptionDefault('htmlmeta_expires', '43200');
		setOptionDefault('htmlmeta_tags', '');
		
		// the html meta tag selector prechecked ones
		setOptionDefault('htmlmeta_http-equiv-language', '1');
		setOptionDefault('htmlmeta_name-language', '1');
		setOptionDefault('htmlmeta_htmlmeta_tags', '1');
		setOptionDefault('htmlmeta_name=content-language', '1');
		setOptionDefault('htmlmeta_http-equiv-content-type', '1');
		setOptionDefault('htmlmeta_http-equiv-cache-control', '1');
		setOptionDefault('htmlmeta_http-equiv-pragma', '1');
		setOptionDefault('htmlmeta_http-equiv-content-style-type','1');
		setOptionDefault('htmlmeta_name-title', '1');
		setOptionDefault('htmlmeta_name=keywords', '1');
		setOptionDefault('htmlmeta_name-description', '1');
		setOptionDefault('htmlmeta_name-robot', '1');
		setOptionDefault('htmlmeta_name-publisher', '1');
		setOptionDefault('htmlmeta_name-creator', '1');
		setOptionDefault('htmlmeta_name-author', '1');
		setOptionDefault('htmlmeta_name-copyright', '1');
		setOptionDefault('htmlmeta_name-generator', '1');
		setOptionDefault('htmlmeta_name-revisit-after', '1');
		setOptionDefault('htmlmeta_name-expires', '1');
		setOptionDefault('htmlmeta_name-generator', '1');
		setOptionDefault('htmlmeta_name-date', '1');
	}

 // Gettext calls are removed because some terms like "noindex" are fixed terms that should not be translated so user know what setting they make.
	function getOptionsSupported() {
		return array(gettext('Cache control') => array('key' => 'htmlmeta_cache_control', 'type' => 5,
										'selections' => array('no-cache' => "no-cache",'public' => "public", 'private' => "private",'no-store' => "no-store"),
										'desc' => gettext("If the browser cache should be used.")),
		gettext('Pragma') => array('key' => 'htmlmeta_pragma', 'type' => 5,
										'selections' => array('no-cache' => "no-cache",'cache' => "cache"),
										'desc' => gettext("If the pages should be allowed to be cached on proxy servers.")),
		gettext('Robots') => array('key' => 'htmlmeta_robots', 'type' => 5,
										'selections' => array('noindex' => "noindex", 'index' => "index",	'nofollow' => "nofollow", 'noindex,nofollow' => "noindex,nofollow",'noindex,follow' => "noindex,follow", 'index,nofollow' => "index,nofollow",	'none' => "none"),
										'desc' => gettext("If and how robots are allowed to visit the site. Default is 'index'. Note that you also should use a robot.txt file.")),
		gettext('Revisit after') => array('key' => 'htmlmeta_revisit_after', 'type' => 0, 
									'desc' => gettext("Request the crawler to revisit the page after x days.")),
		gettext('Expires') => array('key' => 'htmlmeta_expires', 'type' => 0, 
									'desc' => gettext("When the page should be loaded directly from the server and not from any cache. You can either set a date/time in international date format <em>Sat, 15 Dec 2001 12:00:00 GMT (example)</em> or a number. A number then means seconds, the default value <em>43200</em> means 12 hours.")),
		gettext('HTML meta tags') => array('key' => 'htmlmeta_tags', 'type' => 7,
										"checkboxes" => array(
"http-equiv='language'" => "htmlmeta_http-equiv-language",
"name = 'language'"=>  "htmlmeta_name-language",
"content-language" => "htmlmeta_name-content-language",
"http-equiv-content-type/charset (recommended)" => "htmlmeta_http-equiv-content-type",
"http-equiv='imagetoolbar' ('false')" => "htmlmeta_http-equiv-imagetoolbar",
"http-equiv='cache-control'" => "htmlmeta_http-equiv-cache-control",
"http-equiv='pragma'" => "htmlmeta_http-equiv-pragma",
"http-equiv='content-style-type'" => "htmlmeta_http-equiv-content-style-type",
"name='title'" => "htmlmeta_name-title",
"name='keywords'" => "htmlmeta_name-keywords",
"name='description'" => "htmlmeta_name-description",
"name='page-topic'" => "htmlmeta_name-page-topic",
"name='robots'" => "htmlmeta_name-robots",
"name='publisher'" => "htmlmeta_name-publisher",
"name='creator'" => "htmlmeta_name-creator",
"name='author'" => "htmlmeta_name-author",
"name='copyright'" => "htmlmeta_name-copyright",
"name='rights'" => "htmlmeta_name-rights",
"name='generator' ('Zenphoto')" => "htmlmeta_name-generator",
"name='revisit-after'" => "htmlmeta_name-revisit-after",
"name='expires'" => "htmlmeta_name-expires",
"name='date'" => "htmlmeta_name-date",
"name='DC.title'" => "htmlmeta_name-DC.title",
"name='DC.keywords'" => "htmlmeta_name-DC.keywords",
"name='DC.description'" => "htmlmeta_name-DC.description",
"name='DC.language'" => "htmlmeta_name-DC.language",
"name='DC.subject'" => "htmlmeta_name-DC.subject",
"name='DC.publisher'" => "htmlmeta_name-DC.publisher",
"name='DC.creator'" => "htmlmeta_name-DC.creator",
"name='DC.date'" => "htmlmeta_name-DC.date",
"name='DC.type'" => "htmlmeta_name-DC.type",
"name='DC.format'" => "htmlmeta_name-DC.format",
"name='DC.identifier'" => "htmlmeta_name-DC.identifier",
"name='DC.rights'" => "htmlmeta_name-DC.rights",
"name='DC.source'" => "htmlmeta_name-DC.source",
"name='DC.relation'" => "htmlmeta_name-DC.relation",
"name='DC.Date.created'" => "htmlmeta_name-DC.Date.created"
),
										"desc" => gettext("Which of the html meta tags should be used. For info about these in detail please refer to the net."))
		
		);
	}
}

/**
 * Prints html meta data to be used in the <head> section of a page
 *
 */
function printHTMLMetaData() {	
	global $_zp_gallery, $_zp_current_album, $_zp_current_image, $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_gallery_page;
	$url = sanitize("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

	// Convert locale shorttag to allowed html meta format
	$locale = getOption("locale");
	$locale = strtr($locale,"_","-");

	// generate page title, get date
		$pagetitle = "";
		$date = strftime(getOption("date_format")); // if we don't have a item date use current date
		$desc = getBareGalleryDesc();
	  if(is_object($_zp_current_image) AND is_object($_zp_current_album)) {
			$pagetitle = getBareImageTitle()." (". getBareAlbumTitle().") - ";
			$date = getImageDate();
			$desc = getBareImageDesc();
		}
		if(is_object($_zp_current_album) AND !is_object($_zp_current_image)) {
			$pagetitle = getBareAlbumTitle()." - ";
			$date = getAlbumDate();
			$desc = getBareAlbumDesc();
		}
		if(function_exists("is_NewsArticle")) {
			if(is_NewsArticle()) {
				$pagetitle = getBareNewsTitle()." - ";
				$date = getNewsDate();
				$desc = strip_tags(getNewsContent());
			} else 	if(is_NewsCategory()) {
				$pagetitle = getCategoryTitle()." - ";
				$date = strftime(getOption("date_format"));
				$desc = "";
			} else if(is_Pages()) {
				$pagetitle = getBarePageTitle()." - ";
				$date = getPageDate();
				$desc = strip_tags(getPageContent());
			} 
		}
		// shorten desc to the allowed 200 characters if necesssary.
		if(strlen($desc) > 200) {
			$desc = substr($desc,0,200);
		}

		$pagetitle = $pagetitle.getBareGalleryTitle();

		// get master admin
		$admins = getAdministrators();
		$admincount = 0;
		foreach($admins as $admin) {
			$admincount++;
			$author = $admin['name'];
			if($admincount === 1 ) break;
		}
?>
<?php if(getOption('htmlmeta_http-equiv-language')) { ?><meta http-equiv="language" content="<?php echo $locale; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-language')) { ?><meta name="language" content="<?php echo $locale; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-content-language')) { ?><meta name="content-language" content="<?php echo $locale; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_http-equiv-content-type')) { ?><meta http-equiv="content-type" content="text/html; charset=<?php echo getOption("charset"); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_http-equiv-imagetoolbar')) { ?><meta http-equiv="imagetoolbar" content="false" /><?php } ?>
<?php if(getOption('htmlmeta_http-equiv-cache-control')) { ?><meta http-equiv="cache-control" content="<?php echo getOption("htmlmeta_cache_control"); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_http-equiv-pragma')) { ?><meta http-equiv="pragma" content="<?php echo getOption("htmlmeta_pragma"); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_http-equiv-content-style-type')) { ?><meta http-equiv="Content-Style-Type" content="text/css" /><?php } ?>
<?php if(getOption('htmlmeta_name-title')) { ?><meta name="title" content="<?php echo $pagetitle; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-keywords')) { ?><meta name="keywords" content="<?php printMetaKeywords(); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-description')) { ?><meta name="description" content="<?php echo $desc; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-page-topic')) { ?><meta name="page-topic" content="<?php echo $desc; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-robots')) { ?><meta name="robots" content="<?php echo getOption("htmlmeta_robots"); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-publisher')) { ?><meta name="publisher" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-creator')) { ?><meta name="creator" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-author')) { ?><meta name="author" content="<?php echo $author; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-copyright')) { ?><meta name="copyright" content=" (c) <?php echo FULLWEBPATH; ?> - <?php echo $author; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-rights')) { ?><meta name="rights" content="<?php echo $author; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-rights')) { ?><meta name="generator" content="Zenphoto <?php echo ZENPHOTO_VERSION . ' [' . ZENPHOTO_RELEASE . ']'; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-revisit-after')) { ?><meta name="revisit-after" content="<?php echo getOption("htmlmeta_revisit_after"); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-expires')) { ?><meta name="expires" content="<?php echo getOption("htmlmeta_expires"); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-expires')) { ?><meta name="date" content="<?php echo $date; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.titl')) { ?><meta name="DC.title" content="<?php echo $pagetitle; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.keywords')) { ?><meta name="DC.keywords" content="<?php printMetaKeywords(); ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.description')) { ?><meta name="DC.description" content="<?php echo $desc; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.language')) { ?><meta name="DC.language" content="<?php echo $locale; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.subject')) { ?><meta name="DC.subject" content="<?php echo $desc; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.publisher')) { ?><meta name="DC.publisher" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.creator')) { ?><meta name="DC.creator" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.date')) { ?><meta name="DC.date" content="<?php echo $date; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.type')) { ?><meta name="DC.type" content="Text" /> <!-- ? --><?php } ?>
<?php if(getOption('htmlmeta_name-DC.format')) { ?><meta name="DC.format" content="text/html" /><!-- What else? --><?php } ?>
<?php if(getOption('htmlmeta_name-DC.identifier')) { ?><meta name="DC.identifier" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.rights')) { ?><meta name="DC.rights" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.source')) { ?><meta name="DC.source" content="<?php echo $url; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.relation')) { ?><meta name="DC.relation" content="<?php echo FULLWEBPATH; ?>" /><?php } ?>
<?php if(getOption('htmlmeta_name-DC.Date.created')) { ?><meta name="DC.Date.created" content="<?php echo $date; ?>" /><?php } ?>
<?php 
}
	
	/**
	 * Helper function to list tags/categories as keywords separated by comma. Limited to 30 keywords currently.
	 *
	 * @param array $array the array of the tags or categories to list
	 */
	function printMetaKeywords() {
		global $_zp_gallery, $_zp_current_album, $_zp_current_image, $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_gallery_page;
		if(is_object($_zp_current_album) OR is_object($_zp_current_image)) {
			$tags = getTags();
			printMetaAlbumAndImageTags($tags,"gallery");
		} else if($_zp_gallery_page === "index.php") {
			$tags = array_keys(getAllTagsCount()); // get all if no specific item is set
			printMetaAlbumAndImageTags($tags,"gallery");
		} 
		if(function_exists("getNewsCategories")) {
			if(is_NewsArticle()) {
				$tags = getNewsCategories(getNewsID());
				printMetaAlbumAndImageTags($tags,"zenpage");
			} else if(is_News()) {
				$tags = getAllCategories();
				printMetaAlbumAndImageTags($tags,"zenpage");
			} else if (is_NewsCategory()) {
				printCurrentNewsCategory();
			}
		}
	}
	/**
	 * Helper function to print the album and image tags or the news article categorieslist within printMetaKeywords()
	 * Shorens the length to the allowed 1000 characters.
	 *
	 * @param array $tags the array of the tags to list
	 * @param string $mode "gallery" or "zenpage"
	 */
	function printMetaAlbumAndImageTags($tags,$mode="") {
		$count = 0;
		if(!is_array($tags)) {
			foreach($tags as $keyword) {
				$count++;
				if($count === 1) {
					$comma = "";
				} else {
					$comma = ",";
				}
				switch($mode) {
					case "gallery":
						$tags = $tags.$comma.$keyword;
						break;
					case "zenpage":
						$tags = $tags.$comma.$keyword["cat_name"];
						break;
				}
			}
			if(strlen($tags) > 1000) {
				$tags = substr($tags,0,1000);
			}
			echo $tags;
		}
	}

?>