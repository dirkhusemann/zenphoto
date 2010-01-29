<?php 
/**
 * tinyZenpage - A TinyMCE plugin for Zenphoto with Zenpage
 * @author Malte Müller (acrylian)
 * @license GPL v2
 */
// sorry about all the inline css but something by TinyMCE's main css seems to override most setting on the css file no matter what I do...Too "lazy" for further investigation...:-)

$galleryobj = new Gallery(); 
$host = "http://".htmlentities($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');
/**
 * Prints all albums of the Zenphoto gallery as a partial drop down menu (<option></option> parts).
 * 
 * @return string
 */
function printFullAlbumsList() {
	global $galleryobj;
	$albumlist = $galleryobj->getAlbums();
	foreach($albumlist as $album) {
		if (isMyAlbum($album, ALL_RIGHTS)) {
			$albumobj = new Album($galleryobj,$album);
			echo "<option value='".urlencode($albumobj->name)."'>".$albumobj->getTitle().unpublishedZenphotoItemCheck($albumobj)." (".$albumobj->getNumImages().")</option>";
			printSubLevelAlbums($albumobj);
		}
	}
}
	
/**
 * Recursive helper function for printFullAlbumsList() to get all sub albums of each top level album
 * 
 * @return string
 */
function printSubLevelAlbums(&$albumobj) {
	global $galleryobj;
	$albumlist = $albumobj->getAlbums();
	foreach($albumlist as $album) {
		$subalbumobj = new Album($galleryobj,$album);
		$subalbumname = $subalbumobj->name;
		$level = substr_count($subalbumname,"/");
		$arrow = "";
		for($count = 1; $count <= $level; $count++) {
			$arrow .= "&raquo; ";
		}
		echo "<option value='".urlencode($subalbumobj->name)."'>";
		echo $arrow.$subalbumobj->getTitle().unpublishedZenphotoItemCheck($subalbumobj)." (".$subalbumobj->getNumImages().")</option>";
		printSubLevelAlbums($subalbumobj);
	}
}

 /**
 	* checks if a album or image is unpublished and returns a '*'
	*
  * @return string
 	*/
function unpublishedZenphotoItemCheck($obj,$dropdown=true) {
	$span1 = "";
	$span2 = "";
	if($obj->getShow() != "1") {
		if(!$dropdown) {
			$span1 = "<span style='color: red; font-weight: bold'>";
			$span2 = "</span>";
		}
		$show = $span1."*".$span2;
	} else {
		$show = "";
	}
	return $show;
}


/**
 * shortens a string, truncate_string() was not exact enough.
 * 
 * @param $title int Title of the image
 * @param $length int The desired length
 * @return string
 */
function shortentitle($title,$length) {
	if(strlen($title) > $length) {
		return substr($title,0,$length)."...";
	} else {
		return $title;
	}
}

/**
 * Prints the images as thumbnails of the selected album
 *
 * @param $number int The number of images per page
 *
 * @return string
 */
function printImageslist($number) {
	global $galleryobj, $host;
	if(isset($_GET['album']) AND !empty($_GET['album'])) {
		$album = urldecode(sanitize($_GET['album']));
		$albumobj = new Album($galleryobj,$album);
		$images = $albumobj->getImages();

		// This should be done with sprintf here but somehow the variables are always empty then...
		echo "<h3 style='margin-bottom:10px'>".gettext("Album:")." <em>".$albumobj->getTitle().unpublishedZenphotoItemCheck($albumobj,false)."</em> / ".gettext("Album folder:")." <em>".$albumobj->name."</em><br /><small>".gettext("(Click on image to include)")."</small></h3>";
		if($albumobj->getNumImages() != 0) {
			$images_per_page = $number;
			if(isset($_GET['page'])) {
				$currentpage = sanitize_numeric($_GET['page']);
			} else {
				$currentpage = 1;
			}
			$imagecount = $albumobj->getNumImages();
			$pagestotal = ceil($imagecount / $images_per_page);
			for ($nr = 1;$nr <= $pagestotal; $nr++) {
				$startimage[$nr] = $nr * $images_per_page - $images_per_page; // get start image number
				$endimage[$nr] = $nr * $images_per_page - 1; // get end image number
			}
			$number = $startimage[$currentpage];
			printTinyPageNav($pagestotal,$currentpage);
			for ($nr = $number;$nr <= $images_per_page*$currentpage; $nr++)	{
				if ($nr === $imagecount){
					break;
				}
				if($albumobj->isDynamic()) {
					$linkalbumobj = new Album($galleryobj,$images[$nr]['folder']);
					$imageobj = newImage($linkalbumobj,$images[$nr]['filename']);	
				} else {
					$linkalbumobj = $albumobj;
					$imageobj = newImage($albumobj,$images[$nr]);
				}
				$imgurl = $host.WEBPATH.'/'.ZENFOLDER."/i.php?a=".urlencode(urlencode($linkalbumobj->name))."&amp;i=".urlencode(urlencode($imageobj->filename));
				$imgsizeurl = $imageobj->getCustomImage(85, NULL, NULL, 85, 85, NULL, NULL, TRUE);
				echo "<div style='width: 85px; height: 100px; float: left; margin: 10px 10px 10px 13px'>\n";
				echo "<a href=\"javascript:ZenpageDialog.insert('".$imgurl."','".urlencode(urlencode($imageobj->filename))."','".urlencode(urlencode($imageobj->getTitle()))."','".urlencode(urlencode($linkalbumobj->getTitle()))."','zenphoto');\" title='".$imageobj->getTitle()." (".$imageobj->filename.")'><img src='".$imgsizeurl."' style='border: 1px solid gray; padding: 1px' /></a>\n";
				echo "<a href='zoom.php?keepThis=true&amp;image=".urlencode($imageobj->filename)."&amp;album=".urlencode($linkalbumobj->name)."&amp;&TB_iframe=true&amp;height=450&amp;width=450' title='Zoom' class='thickbox' style='outline: none;'><img src='img/magnify.png' alt='' style='border: 0' /></a> ".shortentitle($imageobj->getTitle(),8).unpublishedZenphotoItemCheck($imageobj,false);
				echo "</div>\n";
				if ($nr === $endimage[$currentpage]){
					break;
				}
			} // for end
		} else {
			$albumthumb = $albumobj->getAlbumThumbImage();
			$albumthumbalbum = $albumthumb->getAlbum();
			$imgurl = urlencode(urlencode($host.WEBPATH.'/'.ZENFOLDER."/i.php?a=".$albumthumbalbum->name."&amp;i=".$albumthumb->filename));
			$imgsizeurl = $albumthumb->getCustomImage(85, NULL, NULL, 85, 85, NULL, NULL, TRUE);
			echo "<p style='margin-left: 8px'>".gettext("<strong>Note:</strong> This album does not contain any images.")."</p>";
			echo "<div style='width: 85px; height: 100px; float: left; margin: 10px 10px 10px 13px'>";
			echo "<a href=\"javascript:ZenpageDialog.insert('".$imgurl."','','','".urlencode(urlencode($albumobj->getTitle()))."','zenphoto');\" title='".$albumobj->getTitle()." (".$albumobj->name.")'><img src='".$imgsizeurl."' style='border: 1px solid gray; padding: 1px' /></a>";
			echo "</div>";
		}	// if/else  no image end
	} // if GET album end
}

/**
 * Checks if an album has images for display on the form
 * 
 * @return bool
 */
function checkAlbumForImages() {
	global $galleryobj;
	if(isset($_GET['album']) AND !empty($_GET['album'])) {
		$album = urldecode(sanitize($_GET['album']));
		$albumobj = new Album($galleryobj,$album);
		if($albumobj->getNumImages() != 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return TRUE;
	}
}

/**
 * Checks if the full Zenphoto include form options should be shown
 * 
 * @return bool
 */
function showZenphotoOptions() {
	if((!isset($_GET['zenpage']) OR !isset($_GET['album'])) OR (isset($_GET['album']) AND !empty($_GET['album']))) {
		return TRUE;
	} else {
		return FALSE;
	}
}


/**
 * Prints the page navigation for albums
 *
 * @param $pagestotal int The number page in total
 * @param $currentpage int Number of the current page
 * 
 * @return string
 */
function printTinyPageNav($pagestotal="",$currentpage="") {
	if($pagestotal > 1) {
		echo "<ul style='display: inline; margin-left: -33px;'>";
		if($currentpage != 1) {
			echo "<li class=\"prev\" style='display: inline; margin-left: 5px;'><a href='tinyzenpage.php?album=".urlencode(sanitize($_GET['album']))."&amp;page=".($currentpage-1)."'>&laquo; prev</a></li>";
		} else {
			echo "<li class=\"prev\" style='display: inline; margin-left: 5px; color: gray'>&laquo; prev</li>";
		}
		$j=max(1, min($currentpage-3, $pagestotal-6));
		if ($j != 1) {
			echo "\n <li style='display: inline; margin-left: 5px;'>";
			echo "<a href=\"tinyzenpage.php?album=".sanitize($_GET['album'])."&amp;page=".max($j-4,1)."\">...</a>";
			echo '</li>';
		}
		for ($i=$j; $i <= min($pagestotal, $j+6); $i++) {
			if($i == $currentpage) {
				echo "<li style='display: inline; margin-left: 5px;'>".$i."</li>\n";
			} else {
				echo "<li style='display: inline; margin-left: 5px;'><a href='tinyzenpage.php?album=".urlencode(sanitize($_GET['album']))."&amp;page=".$i."' title='".gettext("Page")." ".$i."'>".$i."</a></li>\n";
			}
		}
		if ($i <= $pagestotal) {
			echo "\n <li style='display: inline; margin-left: 5px;'>";
			echo "<a href=\"tinyzenpage.php?album=".urlencode(sanitize($_GET['album']))."&amp;page=".min($j+10,$pagestotal)."\">...</a>";
			echo '</li>';
		}
		if($currentpage != $pagestotal) {
			echo "<li class=\"next\" style='display: inline; margin-left: 5px;'><a href='tinyzenpage.php?album=".urlencode(sanitize($_GET['album']))."&amp;page=".($currentpage+1)."'>next &raquo;</a></li>";
		} else {
			echo "<li class=\"next\" style='display: inline; margin-left: 5px; color: gray'>next &raquo;</li>";
		}
		echo "</ul><br />";
	}
}

 /**
 	* Prints the Zenpage items as a partial dropdown (pages, news articles, categories)
 	* 
  * @return string
 	*/
function printZenpageItems() {
	$pages = getPages(false);
	$pagenumber = count($pages);
	$categories = getAllCategories();
	$catcount = count($categories);
	echo "<option value='pages'>".gettext("pages")." (".$pagenumber.")</option>";
	echo "<option value='articles'>".gettext("articles")." (".countArticles("","all").")</option>";
	echo "<option value='categories'>".gettext("categories")." (".$catcount.")</option>";
}

 /**
 	* Prints all available pages in Zenpage
 	* 
  * @return string
 	*/
function printAllPagesList() {
	global $host;
	if(isset($_GET['zenpage']) AND $_GET['zenpage'] == "pages") {
		echo "<h3 style='margin-bottom:10px;'>Zenpage: <em>".sanitize($_GET['zenpage'])."</em> <small>(Click on page title to include a link)</small></h3>";
		echo "<ul style='list-style-type: none; width: 85%;'>";
		$pages = getPages(false);

		$indent = 1;
		$open = array(1=>0);
		$topped = false;
		foreach ($pages as $key=>$page) {
			$itemcss = "padding: 5px 0px 5px 0px;";
			$pageobj = new ZenpagePage($page['titlelink']);
			$level = max(1,count(explode('-', $pageobj->getSortOrder())));
			if ($level > $indent) {
				$itemcss .= " border-top: 1px dotted gray;";
				$topped = true;
				echo "\n"."<ul style='list-style-type: none; margin: 5px 0px 0px -20px;'>\n";
				$indent++;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				while ($indent > $level) {
					$open[$indent]--;
					$indent--;
					echo "</li>\n"."</ul>\n";
				}
			} else {
				if ($open[$indent]) {
					echo "</li>\n";
					$open[$indent]--;
				} else {
					echo "\n";
				}
			}
			if ($open[$indent]) {
				echo "</li>\n";
			}
			if (!$topped) {
				$itemcss .= ' border-top: 1px dotted gray; ';
			}
			if ($topped = !array_key_exists($key+1, $pages) || count(explode('-', $pages[$key+1]['sort_order'])) == $level) { // another at this level or at the absolute end
				$itemcss .= " border-bottom: 1px dotted gray;";
			}
			echo "<li id='".$pageobj->getID()."' style='".$itemcss."'>";
			echo "<a href=\"javascript:ZenpageDialog.insert('".ZENPAGE_PAGES."/".$pageobj->getTitlelink()."','".$pageobj->getTitlelink()."','".urlencode($pageobj->getTitle())."','','pages');\" title='".truncate_string(strip_tags($pageobj->getContent()),300)."'>".$pageobj->getTitle().unpublishedZenpageItemCheck($pageobj)."</a>";
			$open[$indent]++;
		}
		while ($indent > 1) {
			echo "</li>\n";
			$open[$indent]--;
			$indent--;
			echo "</ul>";
		}
		if ($open[$indent]) {
			echo "</li>\n";
		} else {
			echo "\n";
		}
	
	echo "</ul>";
	} // if end
}


 /**
 	* checks if a news article or page is unpublished and returns a '*'
	*
  * @return string
 	*/
function unpublishedZenpageItemCheck($page) {
	if($page->getShow() === "0") { 
		$unpublishednote = "<span style='color: red; font-weight: bold'>*</span>"; 
	} else {
		$unpublishednote = "";
	}
	return $unpublishednote;
}


/**
 	* Prints all available articles or categories in Zenpage
  *
 	* @return string
 	*/
function printNewsItemsList() {
	global $_zp_current_zenpage_news,$host;
	if(isset($_GET['zenpage']) AND ($_GET['zenpage'] == "articles" OR $_GET['zenpage'] == "categories")) {
		echo "<h3 style='margin-bottom:10px'>Zenpage: <em>".sanitize($_GET['zenpage'])."</em> <small>".gettext("(Click on article title to include a link)")."</small></h3>";
		echo "<ul style='list-style-type: none; width: 85%;'>";
		if($_GET['zenpage'] == "articles") {
			$items = getNewsArticles("","","all");
		}		
		if($_GET['zenpage'] == "categories") {
			$items = getAllCategories();
	  }
		$count = 0;
		foreach($items as $item) { 
			if($_GET['zenpage'] == "articles") {
				$newsobj = new ZenpageNews($item['titlelink']);
			}
			$count++;
			if($count === 1) {
				$firstitemcss = "border-top: 1px dotted gray; border-bottom: 1px dotted gray; padding: 5px 0px 5px 0px;";
			} else {
				$firstitemcss = "border-bottom: 1px dotted gray; padding: 5px 0px 5px 0px;";
			}
			echo "<li style='".$firstitemcss."'>";
			if($_GET['zenpage'] == "articles") { 
				echo "<a href=\"javascript:ZenpageDialog.insert('".ZENPAGE_NEWS."/".$newsobj->getTitlelink()."','".$newsobj->getTitlelink()."','".$newsobj->getTitle()."','','articles');\" title='".truncate_string(strip_tags($newsobj->getContent()),300)."'>".$newsobj->getTitle().unpublishedZenpageItemCheck($newsobj)."</a>";
			}
			if($_GET['zenpage'] == "categories") { 
				echo "<a href=\"javascript:ZenpageDialog.insert('".ZENPAGE_NEWS."/category/".$item['cat_link']."','".$item['cat_link']."','".get_language_string($item['cat_name'])."','','categories');\" title='".$item['cat_link']."'>".get_language_string($item['cat_name'])."</a>";
			}
			echo "</li>";
		}
		echo "</ul>";
	}
}

 /**
 	* Set the locale for gettext translation of this plugin. Somehow zenphoto's setPluginDomain() does not work here...
 	* 
 	*/
function setTinyZenpageLocale() {
	$encoding = getOption('charset');
	$locale = getOption("locale");
	@putenv("LANG=$locale");
	$result = setlocale(LC_ALL, $locale);
	$domain = 'tinyzenpage';
	$domainpath = "locale/";
	bindtextdomain($domain, $domainpath);
	// function only since php 4.2.0
	if(function_exists('bind_textdomain_codeset')) {
		bind_textdomain_codeset($domain, $encoding);
	}
	textdomain($domain);
}

	?>