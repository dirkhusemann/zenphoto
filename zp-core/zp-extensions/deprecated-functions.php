<?php
/**
 * These functions have been removed from mainstream Zenphoto as they have been
 * supplanted.
 *
 * They are not maintained and they are not guarentted to function correctly with the
 * current version of Zenphoto.
 *
 * @package plugins
 */
$plugin_description = gettext("Deprecated Zenphoto functions. These functions have been removed from mainstream Zenphoto as they have been supplanted. They are not maintained and they are not guaranteed to function correctly with the current version of Zenphoto.");
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---deprecated-functions.php.html";
$option_interface = new deprecated_functions();
$plugin_is_filter = 9;

class deprecated_functions {

	var $listed_functions = array();

	function deprecated_functions() {
		$deprecated = file_get_contents(__FILE__);
		preg_match_all('/function (.*)\(/',$deprecated,$functions);
		$this->listed_functions = $functions[1];
		// remove the items from this class and notify function, leaving only the deprecated functions
		unset($this->listed_functions[0]);	// class instantiation
		unset($this->listed_functions[1]);	// text from the preg match
		unset($this->listed_functions[2]);	// getOptionsSupported()
		unset($this->listed_functions[3]);	// deprecated_function_notify()
		foreach ($this->listed_functions as $key=>$funct) {
			if ($funct == '_emitPluginScripts') {	// special case!!!!
				unset($this->listed_functions[$key]);
			} else {
				setOptionDefault('deprecated_'.$funct,1);
				/* enable to test new deprecated function messages
				if ((OFFSET_PATH == 0) && getOption('deprecated_'.$funct)) {
					echo "<br/>$funct::";
					call_user_func_array($funct,array(0,0,0,0));
				}
				*/
			}
		}
	}

	function getOptionsSupported() {
		$list = array();
		foreach ($this->listed_functions as $funct) {
			$list[$funct] = 'deprecated_'.$funct;
		}
		return array(gettext('Functions')=>array('key' => 'deprecated_Function_list', 'type' => OPTION_TYPE_CHECKBOX_UL,
												'checkboxes' => $list,
												'desc' => gettext('Send a <em>deprecated</em> notification message if the function name is checked.')));
	}
}

/*
 * used to provided deprecated function notification.
 */
function deprecated_function_notify($use) {
	$fcn = get_caller_method();
	if (empty($fcn) || getOption('deprecated_'.$fcn)) {
		if (empty($fcn)) $fcn = gettext('function');
		if (!empty($use)) $use = ' '.$use;
		trigger_error(sprintf(gettext('%s is deprecated'),$fcn).$use, E_USER_NOTICE);
	}
}

// IMPORTANT:: place all deprecated functions below this line!!!

function getZenpageHitcounter($mode="",$obj=NULL) {
	deprecated_function_notify(gettext('Use getHitcounter().'));
	global $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_gallery_page, $_zp_current_category;
	switch($mode) {
		case "news":
			if((is_NewsArticle() OR is_News()) AND !is_object($obj)) {
				$obj = $_zp_current_zenpage_news;
				$hc = $obj->get('hitcounter');
			} else if(is_object($obj)) {
				$hc = $obj->get('hitcounter');
			}
			return $hc;
			break;
		case "page":
			if(is_Pages() AND !is_object($obj)) {
				$obj = $_zp_current_zenpage_page;
				$hc = $obj->get('hitcounter');
			} else if(is_object($obj)) {
				$hc = $obj->get('hitcounter');
			}
			return $hc;
			break;
		case "category":
			if(!is_object($obj) || is_NewsCategory() AND !empty($obj)) {
				$catname = $_zp_current_category;
				$hc = query_single_row("SELECT hitcounter FROM ".prefix('zenpage_news_categories')." WHERE cat_link = '".$catname."'");
				return $hc["hitcounter"];
			}
			break;
	}
}

function printImageRating($object=NULL) {
	deprecated_function_notify(gettext('Use printRating().'));
	global $_zp_current_image;
	if (is_null($object)) $object = $_zp_current_image;
	printRating(3, $object);
}

function printAlbumRating($object=NULL) {
	deprecated_function_notify(gettext('Use printRating().'));
	global $_zp_current_album;
	if (is_null($object)) $object = $_zp_current_album;
	printRating(3, $object);
}

function printImageEXIFData() {
	deprecated_function_notify(gettext('Use printImageMetadata().'));
	if (isImageVideo()) {
	} else {
		printImageMetadata();
	}
}


function printCustomSizedImageMaxHeight($maxheight) {
	deprecated_function_notify(gettext('Use printCustomSizedImageMaxSpace().'));
	if (getFullWidth() === getFullHeight() OR getDefaultHeight() > $maxheight) {
		printCustomSizedImage(getImageTitle(), null, null, $maxheight, null, null, null, null, null, null);
	} else {
		printDefaultSizedImage(getImageTitle());
	}
}

function getCommentDate($format = NULL) {
	deprecated_function_notify(gettext('Use getCommentDateTime().'));
	if (is_null($format)) {
		$format = getOption('date_format');
		$time_tags = array('%H', '%I', '%R', '%T', '%r');
		foreach ($time_tags as $tag) { // strip off any time formatting
			$t = strpos($format, $tag);
			if ($t !== false) {
				$format = trim(substr($format, 0, $t));
			}
		}
	}
	global $_zp_current_comment;
	return myts_date($format, $_zp_current_comment['date']);
}

function getCommentTime($format = '%I:%M %p') {
	deprecated_function_notify(gettext('Use getCommentDateTime().'));
	global $_zp_current_comment;
	return myts_date($format, $_zp_current_comment['date']);
}

function hitcounter($option='image', $viewonly=false, $id=NULL) {
	deprecated_function_notify(gettext('Use getHitcounter().'));
	switch($option) {
		case "image":
			if (is_null($id)) {
				$id = getImageID();
			}
			$dbtable = prefix('images');
			break;
		case "album":
			if (is_null($id)) {
				$id = getAlbumID();
			}
			$dbtable = prefix('albums');
			break;
	}
	$sql = "SELECT `hitcounter` FROM $dbtable WHERE `id` = $id";
	$result = query_single_row($sql);
	$resultupdate = $result['hitcounter'];
	return $resultupdate;
}

function my_truncate_string($string, $length) {
	deprecated_function_notify(gettext('Use truncate_string().'));
	if (strlen($string) > $length) {
		$short = substr($string, 0, $length);
		return $short. '...';
	} else {
		return $string;
	}
}

function getImageEXIFData() {
	deprecated_function_notify(gettext('Use getImageMetaData().'));
	global $_zp_current_image;
	if (is_null($_zp_current_image)) return false;
	return $_zp_current_image->getMetaData();
}

function getAlbumPlace() {
	deprecated_function_notify(gettext('Use getAlbumLocation().'));
	global $_zp_current_album;
	return $_zp_current_album->getLocation();
}

function printAlbumPlace($editable=false, $editclass='', $messageIfEmpty = true) {
	deprecated_function_notify(gettext('Use printAlbumLocation().'));
	if ( $messageIfEmpty === true ) {
		$messageIfEmpty = gettext('(No place...)');
	}
	printEditable('album', 'location', $editable, $editclass, $messageIfEmpty, !getOption('tinyMCEPresent'));
}


/***************************
 * ZENPAGE PLUGIN FUNCTIONS
 ***************************/

function zenpageHitcounter($option='pages', $viewonly=false, $id=NULL) {
	deprecated_function_notify(gettext('Use getHitcounter().'));
	global $_zp_current_zenpage_page, $_zp_current_zenpage_news;
	switch($option) {
		case "pages":
			if (is_null($id)) {
				$id = getPageID();
			}
			$dbtable = prefix('zenpage_pages');
			$doUpdate = true;
			break;
		case "category":
			if (is_null($id)) {
				$id = getCurrentNewsCategoryID();
			}
			$dbtable = prefix('zenpage_news_categories');
			$doUpdate = getCurrentNewsPage() == 1; // only count initial page for a hit on an album
			break;
		case "news":
			if (is_null($id)) {
				$id = getNewsID();
			}
			$dbtable = prefix('zenpage_news');
			$doUpdate = true;
			break;
	}
	if(($option == "pages" AND is_Pages()) OR ($option == "news" AND is_NewsArticle()) OR ($option == "category" AND is_NewsCategory())) {
		if ((zp_loggedin(ZENPAGE_PAGES_RIGHTS | ZENPAGE_NEWS_RIGHTS)) || $viewonly) { $doUpdate = false; }
		$hitcounter = "hitcounter";
		$whereID = " WHERE `id` = $id";
		$sql = "SELECT `".$hitcounter."` FROM $dbtable $whereID";
		if ($doUpdate) { $sql .= " FOR UPDATE"; }
		$result = query_single_row($sql);
		$resultupdate = $result['hitcounter'];
		if ($doUpdate) {
			$resultupdate++;
			query("UPDATE $dbtable SET `".$hitcounter."`= $resultupdate $whereID");
		}
		return $resultupdate;
	}
}

function rewrite_path_zenpage($rewrite='',$plain='') {
	deprecated_function_notify(gettext('Use rewrite_path().'));
	if (getOption('mod_rewrite')) {
		return $rewrite;
	} else {
		return $plain;
	}
}

function getNewsImageTags() {
	deprecated_function_notify(gettext('Use object->getTags() method.'));
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		return $_zp_current_zenpage_news->getTags();
	} else {
		return false;
	}
}

function printNewsImageTags($option='links',$preText=NULL,$class='taglist',$separator=', ',$editable=TRUE) {
	deprecated_function_notify(gettext(''));
	global $_zp_current_zenpage_news;
	if(is_GalleryNewsType()) {
		$singletag = getNewsImageTags();
		$tagstring = implode(', ', $singletag);
		if (empty($tagstring)) { $preText = ""; }
		if (count($singletag) > 0) {
			echo "<ul class=\"".$class."\">\n";
			if (!empty($preText)) {
				echo "<li class=\"tags_title\">".$preText."</li>";
			}
			$ct = count($singletag);
			foreach ($singletag as $atag) {
				if ($x++ == $ct) { $separator = ""; }
				if ($option == "links") {
					$links1 = "<a href=\"".html_encode(getSearchURL($atag, '', 'tags', 0, 0))."\" title=\"".$atag."\" rel=\"nofollow\">";
					$links2 = "</a>";
				}
				echo "\t<li>".$links1.html_encode($atag).$links2.$separator."</li>\n";
			}

			echo "</ul>";

			echo "<br clear=\"all\" />\n";
		}
	}
}

function getNumSubalbums() {
	deprecated_function_notify(gettext('Use getNumAlbums().'));
	return getNumAlbums();
}

function getAllSubalbums($param=NULL) {
	deprecated_function_notify(gettext('Use getAllAlbums().'));
	return getAllAlbums($param);
}

function addPluginScript($script) {
	deprecated_function_notify(gettext('Register a "theme_head" filter.'));
	global $_zp_plugin_scripts;
	$_zp_plugin_scripts[] = $script;

	if (!function_exists('_emitPluginScripts')) {
		function _emitPluginScripts() {
			global $_zp_plugin_scripts;
			if (is_array($_zp_plugin_scripts)) {
				foreach ($_zp_plugin_scripts as $script) {
					echo $script."\n";
				}
			}
		}
		zp_register_filter('theme_head','_emitPluginScripts');
	}
}

function zenJavascript() {
	deprecated_function_notify(gettext('Use zp_appl_filter("theme_head").'));
	zp_apply_filter('theme_head');
}

function normalizeColumns($albumColumns=NULL, $imageColumns=NULL) {
	deprecated_function_notify(gettext('Use instead the theme options for images and albums per row.'), E_USER_NOTICE);
	global $_firstPageImages;
	setOption('albums_per_row',$albumColumns);
	setOption('images_per_row',$imageColumns);
	setThemeColumns();
	return $_firstPageImages;
}

?>