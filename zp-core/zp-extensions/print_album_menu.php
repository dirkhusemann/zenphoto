<?php
/** printAlbumMenu for Zenphoto
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_description = gettext("Adds a theme function printAlbumMenu() to print an album menu either as a nested list (context sensitive) or as a dropdown menu.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.3.1'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---print_album_menu.php.html";

$option_interface = new print_album_menu();

/**
 * Plugin option handling class
 *
 */
class print_album_menu {

	function register_user_options() {
		setOptionDefault('print_album_menu_showsubs', 0);
		setOptionDefault('print_album_menu_count', 1);
	}

	function getOptionsSupported() {
		return array(	gettext('"List" subalbum level') => array('key' => 'print_album_menu_showsubs', 'type' => OPTION_TYPE_TEXTBOX, 
										'desc' => gettext('The depth of subalbum levels shown with the <code>printAlbumMenu</code> and <code>printAlbumMenuList</code> "List" option. Note: themes may override this default.')),
									gettext('Show counts') => array('key' => 'print_album_menu_count', 'type' => OPTION_TYPE_CHECKBOX, 
										'desc' => gettext('If checked, image and album counts will be included in the list. Note: Themes may override this option.'))
									);
	}
	function handleOption($option, $currentValue) {
	}
}


/**
 * Prints a list of all albums context sensitive.
 * Since 1.4.3 this is a wrapper function for the separate functions printAlbumMenuList() and printAlbumMenuJump().
 * that was included to remain compatiblility with older installs of this menu.
 *
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the zenphoto admin plugins tab.
 * Call the function printAlbumMenu() at the point where you want the menu to appear.
 *
 * @param string $option 
 * * 								"list" for html list, 
 * 									"list-top" for only the top level albums, 
 * 									"omit-top" same as list, but the first level of albums is omitted
 * 									"list-sub" lists the offspring level of subalbums for the current album
 * 									"jump" dropdown menu of all albums(not context sensitive)
 * 
 * @param bool $showcount true for a image counter or subalbum count in brackets behind the album name, false for no image numbers or leave blank
 * @param string $css_id insert css id for the main album list, leave blank if you don't use (only list mode)
 * @param string $css_class_topactive insert css class for the active link in the main album list (only list mode)
 * @param string $css_class insert css class for the sub album lists (only list mode)
 * @param string $css_class_active insert css class for the active link in the sub album lists (only list mode)
 * @param string $indexname insert the name how you want to call the link to the gallery index (insert "" if you don't use it, it is not printed then)
 * @param int C Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param bool $keeptopactive If set to TRUE the toplevel album entry will stay marked as active if within its subalbums ("list" only)
 * @return html list or drop down jump menu of the albums
 * @since 1.2
 */

function printAlbumMenu($option,$showcount=NULL,$css_id='',$css_class_topactive='',$css_class='',$css_class_active='', $indexname="Gallery Index", $showsubs=NULL,$firstimagelink=false,$keeptopactive=false) {
	if ($option == "jump") {
		printAlbumMenuJump($showcount,$indexname,$firstimagelink);
	} else {
		printAlbumMenuList($option,$showcount,$css_id,$css_class_topactive,$css_class,$css_class_active, $indexname, $showsubs,$firstimagelink,$keeptopactive);
	}
}

/**
 * Prints a nested html list of all albums context sensitive.
 *
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the zenphoto admin plugins tab;
 * Call the function printAlbumMenuList() at the point where you want the menu to appear.
 *
 * @param string $option 
 * 									"list" for html list, 
 * 									"list-top" for only the top level albums, 
 * 									"omit-top" same as list, but the first level of albums is omitted
 * 									"list-sub" lists the offspring level of subalbums for the current album
 * @param bool $showcount true for a image counter in brackets behind the album name, false for no image numbers or leave blank
 * @param string $css_id insert css id for the main album list, leave blank if you don't use (only list mode)
 * @param string $css_id_active insert css class for the active link in the main album list (only list mode)
 * @param string $css_class insert css class for the sub album lists (only list mode)
 * @param string $css_class_active insert css class for the active link in the sub album lists (only list mode)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" if you don't use it, it is not printed then.
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param bool $keeptopactive If set to TRUE the toplevel album entry will stay marked as active if within its subalbums ("list" only)
 * @param bool $startlist set to true to output the UL tab
 * @return html list of the albums
 */

function printAlbumMenuList($option,$showcount=NULL,$css_id='',$css_class_topactive='',$css_class='',$css_class_active='', $indexname="Gallery Index", $showsubs=NULL,$firstimagelink=false,$keeptopactive=false,$startlist=true) {
	global $_zp_gallery, $_zp_current_album, $_zp_gallery_page;
	
	// if in search mode don't use the foldout contextsensitiveness and show only toplevel albums
	if(in_context(ZP_SEARCH_LINKED)) {
		$option = "list-top";
	}

	$albumpath = rewrite_path("/", "/index.php?album=");
	if(empty($_zp_current_album) || ($_zp_gallery_page != 'album.php' && $_zp_gallery_page != 'image.php')) {
		$currentfolder = "";
	} else {
		$currentfolder = $_zp_current_album->name;
	}

	// check if css parameters are used
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_class_topactive != "") { $css_class_topactive = " class='".$css_class_topactive."'"; }
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($css_class_active != "") { $css_class_active = " class='".$css_class_active."'"; }
	
	if ($startlist) echo "<ul".$css_id.">\n"; // top level list
	/**** Top level start with Index link  ****/
	if($option === "list" OR $option === "list-top") {
		if(!empty($indexname)) {
			echo "<li><a href='".html_encode(getGalleryIndexURL())."' title='".html_encode($indexname)."'>".$indexname."</a></li>";
		}
	}

	if ($option == 'list-sub' && in_context(ZP_ALBUM)) {
		$albums = $_zp_current_album->getAlbums();
	} else {
		$albums = $_zp_gallery->getAlbums();
	}

	printAlbumMenuListAlbum($albums, $albumpath, $currentfolder, $option, $showcount, $showsubs, $css_class, $css_class_topactive, $css_class_active,$firstimagelink,$keeptopactive);

	if ($startlist) echo "</ul>\n";

}


/**
 * Handles an album for printAlbumMenuList
 *
 * @param array $albums albums array
 * @param string $path for createAlbumMenuLink
 * @param string $folder 
 * @param string $option see printAlbumMenuList
 * @param string $showcount see printAlbumMenuList
 * @param int $showsubs see printAlbumMenuList
 * @param string $css_class see printAlbumMenuList
 * @param string $css_class_topactive see printAlbumMenuList
 * @param string $css_class_active see printAlbumMenuList
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param bool $keeptopactive If set to TRUE the toplevel album entry will stay marked as active if within its subalbums ("list" only)
 */
function printAlbumMenuListAlbum($albums, $path, $folder, $option, $showcount, $showsubs, $css_class, $css_class_topactive, $css_class_active,$firstimagelink,$keeptopactive) {
	global $_zp_gallery,$_zp_current_album;
	if (is_null($showcount)) $showcount = getOption('print_album_menu_count');
	if (is_null($showsubs)) $showsubs = getOption('print_album_menu_showsubs');
	if ($showsubs && !is_numeric($showsubs)) $showsubs = 9999999999;
	if(empty($keeptopactive)) $keeptopactive = false;
	$pagelevel = count(explode('/', $folder));
	$currenturalbumname = "";
	foreach ($albums as $album) {
		$level = count(explode('/', $album));
		$process =  (($level < $showsubs && $option == "list") // user wants all the pages whose level is <= to the parameter
									|| ($option != 'list-top' // not top only
											&& strpos($folder, $album) === 0 // within the family
											&& $level<=$pagelevel) // but not too deep
								);
								
		$topalbum = new Album($_zp_gallery,$album,true);
		if ($level>1
				|| ($option != 'omit-top') 
				) { // listing current level album
			if ($level==1) {
				$css_class_t = $css_class_topactive;
				
			} else {
				$css_class_t = $css_class_active;
			}
			if($keeptopactive) {
				if(isset($_zp_current_album) && is_object($_zp_current_album)) {
					$currenturalbum = getUrAlbum($_zp_current_album);
					$currenturalbumname = $currenturalbum->name;
				}
			}
			$count = "";
			if($showcount) {
				if($topalbum->getNumImages() > 0) {
					$topalbumnumimages = $topalbum->getNumImages();
					$count = "<small> ".sprintf(ngettext('(%u image)', '(%u images)',$topalbumnumimages),$topalbumnumimages)."</small>";
				}
				$toplevelsubalbums = $topalbum->getAlbums();
				$toplevelsubalbums = count($toplevelsubalbums);
				if($toplevelsubalbums > 0) {
					$count = "<small> ".sprintf(ngettext('(%u album)', '(%u albums)',$toplevelsubalbums),$toplevelsubalbums)."</small>";
				}
			}
			
			if(in_context(ZP_ALBUM) && !in_context(ZP_SEARCH_LINKED) && (getAlbumID() == $topalbum->getAlbumID() || $topalbum->name == $currenturalbumname)) {
				$current = $css_class_t.' ';
			} else {
				$current = "";
			}
			if($firstimagelink && $topalbum->getNumImages() != 0) {
				$imgurl = getFirstImageOfAlbum($topalbum);
				$link = "<li><a ".$current."href='".$imgurl."' title='".html_encode($topalbum->getTitle())."'>".html_encode($topalbum->getTitle())."</a>".$count;
			} else {
				$link = "<li><a ".$current."href='".html_encode($path.pathurlencode($topalbum->name))."' title='".html_encode($topalbum->getTitle())."'>".html_encode($topalbum->getTitle())."</a>".$count;
			}
			echo $link;
		}
		if ($process) { // listing subalbums
			$subalbums = $topalbum->getAlbums();
			if (!empty($subalbums)) {
				echo "\n<ul".$css_class.">\n";
				printAlbumMenuListAlbum($subalbums, $path, $folder, $option, $showcount, $showsubs, $css_class, $css_class_topactive, $css_class_active,$firstimagelink,false);
				echo "\n</ul>\n";

			}
		}
		if($option == 'list' || $option == 'list-top' || $level>1) { // close the LI
			echo "\n</li>\n";
		}

	}
}

/**
 * Prints a dropdown menu of all albums(not context sensitive)
 * Is used by the wrapper function printAlbumMenu() if the options "jump" is choosen. For standalone use, too.
 *
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the zenphoto admin plugins tab;
 * Call the function printAlbumMenuJump() at the point where you want the menu to appear.
 *
 * @param string $option "count" for a image counter in brackets behind the album name, "" = for no image numbers
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" if you don't use it, it is not printed then.
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 */
function printAlbumMenuJump($option="count", $indexname="Gallery Index",$firstimagelink=false) {
	global $_zp_gallery, $_zp_current_album, $_zp_gallery_page;
	$albumpath = rewrite_path("/", "/index.php?album=");
	if(!empty($_zp_current_album) || $_zp_gallery_page != 'album.php') {
		$currentfolder = $_zp_current_album->name;
	}
	?>
	<script type="text/javaScript">
		// <!-- <![CDATA[
		function gotoLink(form) {
		 	var OptionIndex=form.ListBoxURL.selectedIndex;
			parent.location = form.ListBoxURL.options[OptionIndex].value;
		}
		// ]]> -->
	</script>
	<form name="AutoListBox" action="#">
		<p>
			<select name="ListBoxURL" size="1" onchange="gotoLink(this.form);">
			<?php
			if(!empty($indexname)) {
				$selected = checkSelectedAlbum("", "index");
				 ?>
			<option <?php echo $selected; ?> value="<?php echo html_encode(getGalleryIndexURL()); ?>"><?php echo $indexname; ?></option>
			<?php 
			}
			$albums = $_zp_gallery->getAlbums();
			printAlbumMenuJumpAlbum($albums,$option,$albumpath,$firstimagelink);
			?>
			</select>
		</p>
	</form>
	<?php
}

/**
 * Handles a single album level for printAlbumMenuJump
 *
 * @param array $albums list of album names
 * @param string $showcount see printAlbumMenuJump
 * @param string $albumpath path of the page album
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param int $level current level
 */
function printAlbumMenuJumpAlbum($albums,$option,$albumpath,$firstimagelink,$level=1) {
	global $_zp_gallery;
	foreach ($albums as $album) {
		$subalbum = new Album($_zp_gallery,$album,true);


		if($option === "count" AND $subalbum->getNumImages() > 0) {
			$count = " (".$subalbum->getNumImages().")";
		} else {
			$count = "";
		}
		$arrow = str_replace(':', '&raquo; ', str_pad("", $level-1, ":"));
						
		$selected = checkSelectedAlbum($subalbum->name, "album");
		if($firstimagelink && $subalbum->getNumImages() != 0) {
			$imgurl = getFirstImageOfAlbum($subalbum);
			$link = "<option $selected value='".$imgurl."'>".$arrow.strip_tags($subalbum->getTitle()).$count."</option>";
		} else {
			$link = "<option $selected value='".html_encode($albumpath.pathurlencode($subalbum->name))."'>".$arrow.strip_tags($subalbum->getTitle()).$count."</option>";
		}
		echo $link;
		$subalbums = $subalbum->getAlbums();
		if (!empty($subalbums)) {
			printAlbumMenuJumpAlbum($subalbums,$option,$albumpath,$firstimagelink,$level+1);
		}
	}

}

/**
 * A printAlbumMenu() helper function for the jump menu mode of printAlbumMenu() that only
 * checks which the current album so that the entry in the in the dropdown jump menu can be selected
 * Not for standalone use.
 *
 * @param string $checkalbum The album folder name to check
 * @param string $option "index" for index level, "album" for album level
 * @return string returns nothing or "selected"
 */
function checkSelectedAlbum($checkalbum, $option) {
	global $_zp_current_album, $_zp_gallery_page;
	if(is_object($_zp_current_album)) {
		$currentalbumname = $_zp_current_album->name;
	} else {
		$currentalbumname = "";
	}
	$selected = "";
	switch ($option) {
		case "index":
			if($_zp_gallery_page === "index.php") {
				$selected = "selected";
			}
			break;
		case "album":
			if($currentalbumname === $checkalbum) {
				$selected = "selected";
			}
			break;
	}
	return $selected;
}

/**
 * Returns the link of the first image in $albumobj
 * 
 * @param object $albumobj The object of the album to use
 */
function getFirstImageOfAlbum($albumobj) {
	$images = $albumobj->getImages();
	$firstimage = newImage($albumobj,array_shift($images));
	$link = $firstimage->getImageLink();
	return $link;
}
?>