<?php


// template-functions.php - Headers may be sent in this file ONLY.


require_once("classes.php");

// Contexts (Bitwise and combinable)
define("ZP_INDEX",   1);
define("ZP_ALBUM",   2);
define("ZP_IMAGE",   4);
define("ZP_COMMENT", 8);
define("ZP_GROUP",  16);

// Get the requested page number:
if (isset($_GET['page'])) { 
  $_zp_page = $_GET['page']; 
} else {
  $_zp_page = 1;
}

// Initialize the global objects and object arrays for this page here:
$_zp_gallery = new Gallery();
$_zp_current_album = NULL;
$_zp_current_album_restore = NULL;
$_zp_albums = NULL;
$_zp_current_image = NULL;
$_zp_current_image_restore = NULL;
$_zp_images = NULL;
$_zp_current_comment = NULL;
$_zp_comments = NULL;
$_zp_current_context = ZP_INDEX;
$_zp_current_context_restore = NULL;

// Parse the GET request to see what exactly is requested...
if (isset($_GET['album'])) {
  $g_album = get_magic_quotes_gpc() ? stripslashes($_GET['album']) : $_GET['album'];
	if (isset($_GET['image'])) {
    $g_image = get_magic_quotes_gpc() ? stripslashes($_GET['image']) : $_GET['image'];
		$_zp_current_context = ZP_IMAGE | ZP_ALBUM | ZP_INDEX;

		// An image page. Instantiate objects.
    $_zp_current_album = new Album($_zp_gallery, $g_album);
		$_zp_current_image = new Image($_zp_current_album, $g_image);

    // TODO: Better error handling than this.
    if (!$_zp_current_album->exists) {
      die("The album " . $g_album . " does not exist.");
    } else if (!$_zp_current_image->exists) {
      die("The image " . $g_image . " does not exist.");
    }
		
    
    //// Comment form handling.
    if (isset($_POST['comment'])) {
      if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['comment'])) {
        if (isset($_POST['website'])) $website = strip_tags($_POST['website']); else $website = "";
        $commentadded = $_zp_current_image->addComment(strip_tags($_POST['name']), strip_tags($_POST['email']), $website, 
          strip_tags($_POST['comment'], zp_conf('allowed_tags')));
        // Then redirect to this image page to prevent re-submission.
        if ($commentadded) {
          // Comment added with no errors, redirect to the image... save cookie if requested.
          if (isset($_POST['remember'])) {
            // Should always re-cookie to update info in case it's changed...
            $info = array(strip($_POST['name']), strip($_POST['email']), strip($website));
            setcookie("zenphoto", implode('|~*~|', $info), time()+5184000, "/");
            $stored = array($_POST['name'], $_POST['email'], $website, $_POST['comment'], isset($_POST['remember']));
          } else {
            setcookie("zenphoto", "", time()-368000, "/");
            $stored = array("","","",false);
          }
          $g_album = urlencode($g_album); $g_image = urlencode($g_image);
          header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/" . 
            (zp_conf('mod_rewrite') ? "$g_album/$g_image" : "index.php?album=$g_album&image=$g_image"));
          exit;
        } else {
          $stored = array($_POST['name'], $_POST['email'], $website, $_POST['comment'], false);
          if (isset($_POST['remember'])) $stored[3] = true;
          $error = true;
        }
        
      }
    } else if (isset($_COOKIE['zenphoto'])) {
      // Comment form was not submitted; get the saved info from the cookie.
      $stored = explode('|~*~|', stripslashes($_COOKIE['zenphoto'])); $stored[] = true;
    } else { 
      $stored = array("","","", false); 
    } 
    
	} else {
		$_zp_current_context = ZP_ALBUM | ZP_INDEX;
		// Album default view; for album.php
		$_zp_current_album = new Album($_zp_gallery, $g_album);

    // TODO: Better error handling than this.
    if (!$_zp_current_album->exists) {
      die("The album " . $g_album . " does not exist.");
    }
	}
} else {
	$_zp_current_context = ZP_INDEX;
}

// Contextual manipulation.
function get_context() { 
  global $_zp_current_context;
  return $_zp_current_context;
}
function set_context($context) {
	global $_zp_current_context;
	$_zp_current_context = $context;
}
function in_context($context) {
	return get_context() & $context;
}
function add_context($context) {
	set_context(get_context() | $context);
}
function rem_context($context) {
	global $_zp_current_context;
	set_context(get_context() & ~$context);
}
// Use save and restore rather than add/remove when modifying contexts.
function save_context() {
  global $_zp_current_context, $_zp_current_context_restore;
  $_zp_current_context_restore = $_zp_current_context;
}
function restore_context() {
  global $_zp_current_context, $_zp_current_context_restore;
  $_zp_current_context = $_zp_current_context_restore;
}

if (zp_loggedin()) {
  
  function saveTitle($newtitle) {
    if (get_magic_quotes_gpc()) $newtitle = stripslashes($newtitle);
    global $_zp_current_image, $_zp_current_album;
    if (in_context(ZP_IMAGE)) {
      $_zp_current_image->setTitle($newtitle);
      return $newtitle;
    } else if (in_context(ZP_ALBUM)) {
      $_zp_current_album->setTitle($newtitle);
      return $newtitle;
    } else {
      return false;
    }
  }
  
  function saveDesc($newdesc) {
    if (get_magic_quotes_gpc()) $newdesc = stripslashes($newdesc);
    global $_zp_current_image, $_zp_current_album;
    if (in_context(ZP_IMAGE)) {
      $_zp_current_image->setDesc($newdesc);
      return $newdesc;
    } else if (in_context(ZP_ALBUM)) {
      $_zp_current_album->setDesc($newdesc);
      return $newdesc;
    } else {
      return false;
    }
  }
  
  // Load Sajax (AJAX Library) now that we have all objects set.
  require_once("Sajax.php");
  sajax_init();
  $sajax_debug_mode = 0;
  sajax_export("saveTitle");
  sajax_export("saveDesc");
  sajax_handle_client_request();
  
  
}
  
function zenJavascript() {
  if (zp_loggedin()) {
    echo "  <script type=\"text/javascript\" src=\"".WEBPATH."/zen/ajax.js\"></script>\n";
    echo "  <script type=\"text/javascript\">\n";
    sajax_show_javascript();
    echo "  </script>";
  }
}




/******************************************/
/*********** Template Functions ***********/
/******************************************/


/*** Generic Helper Functions *************/
/******************************************/

function printLink($url, $text, $title=NULL, $class=NULL, $id=NULL) {
  echo "<a href=\"" . $url . "\"" . 
  (($title) ? " title=\"$title\"" : "") .
  (($class) ? " class=\"$class\"" : "") . 
  (($id) ? " id=\"$id\"" : "") . ">" .
  $text . "</a>";
}


function printVersion() {
  echo zp_conf('version');
}

// Prints a link to administration if the current user is logged-in
function printAdminLink($text, $before='', $after='', $title=NULL, $class=NULL, $id=NULL) {
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH.'/zen/admin.php', $text, $title, $class, $id);
    echo $after;
  }
}




/*** Gallery Index (album list) Context ***/
/******************************************/

function getGalleryTitle() { 
	return zp_conf('gallery_title');
}
function printGalleryTitle() { 
	echo getGalleryTitle(); 
}

function getMainSiteName() { 
	return zp_conf('main_site_name');
}
function getMainSiteURL() { 
	return zp_conf('main_site_url');
}
function printMainSiteLink($title=NULL, $class=NULL, $id=NULL) { 
	printLink(getMainSiteURL(), getMainSiteName(), $title, $class, $id);
}

function getGalleryIndexURL() {
	global $_zp_current_album;
  if (in_context(ZP_ALBUM)) {
    // Link to the page the current album belongs to.
    if (zp_conf('mod_rewrite')) {
      return WEBPATH . "/page/" . $_zp_current_album->getGalleryPage();
    } else {
      return WEBPATH . "/index.php?page=" . $_zp_current_album->getGalleryPage();
    }
  } else {
    return WEBPATH;
  }
}

function getNumAlbums() { 
	global $_zp_gallery;
	return $_zp_gallery->getNumAlbums();
}

// WHILE next_album(): context switches to Album.
// Switch back to index when there are no more albums.
function next_album() {
	global $_zp_albums, $_zp_gallery, $_zp_current_album, $_zp_page, $_zp_current_album_restore;
	if (is_null($_zp_albums)) {
		$_zp_albums = $_zp_gallery->getAlbums($_zp_page);
    $_zp_current_album_restore = $_zp_current_album;
		$_zp_current_album = array_shift($_zp_albums);
    save_context();
		add_context(ZP_ALBUM);
		return true;
	} else if (empty($_zp_albums)) {
		$_zp_albums = NULL;
		$_zp_current_album = $_zp_current_album_restore;
		restore_context();
		return false;
	} else {
		$_zp_current_album = array_shift($_zp_albums);
		return true;
	}
}


/*** Album AND Gallery Context ************/
/******************************************/
// (Common functions shared by Albums and the Gallery Index)

function getCurrentPage() { 
	global $_zp_page;
	return $_zp_page;
}
function getTotalPages() { 
	global $_zp_current_album, $_zp_gallery;
	if (in_context(ZP_ALBUM)) {
		return ceil($_zp_current_album->getNumImages() / zp_conf('images_per_page'));
	} else if (in_context(ZP_INDEX)) {
		return ceil($_zp_gallery->getNumAlbums() / zp_conf('albums_per_page'));
	} else {
    return null;
  }
}

function getPageURL($page) {
  global $_zp_current_album, $_zp_gallery;
  $total = getTotalPages();
  if ($page <= $total && $page > 0) {
    if (in_context(ZP_ALBUM)) {
      if (zp_conf('mod_rewrite')) {
        return WEBPATH . "/" . urlencode($_zp_current_album->name) . (($page > 1) ? "/page/" . $page . "/" : "");
      } else {
        return WEBPATH . "/index.php?album=" . urlencode($_zp_current_album->name) . (($page > 1) ? "&page=" . $page : "");
      }
    } else if (in_context(ZP_INDEX)) {
      if (zp_conf('mod_rewrite')) {
        return WEBPATH . (($page > 1) ? "/page/" . $page . "/" : "");
      } else {
        return WEBPATH . "/index.php" . (($page > 1) ? "?page=" . $page : "");
      }
    }
  }
  return null;
}

function hasNextPage() { return (getCurrentPage() < getTotalPages()); }

function getNextPageURL() { 
  return getPageURL(getCurrentPage() + 1);
}

function printNextPageLink($text, $title=NULL, $class=NULL, $id=NULL) { 
  if (hasNextPage()) {
    printLink(getNextPageURL(), $text, $title, $class, $id);
  } else {
    echo "<span class=\"disabledlink\">$text</span>";
  }
}

function hasPrevPage() { return (getCurrentPage() > 1); }

function getPrevPageURL() { 
  return getPageURL(getCurrentPage() - 1);
}

function printPrevPageLink($text, $title=NULL, $class=NULL, $id=NULL) {
  if (hasPrevPage()) {
    printLink(getPrevPageURL(), $text, $title, $class, $id);
  } else {
    echo "<span class=\"disabledlink\">$text</span>";
  }
}

function printPageNav($prevtext, $separator, $nexttext, $class="pagenav", $id=NULL) {
  echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
  printPrevPageLink($prevtext, "Previous Page");
  echo " $separator ";
  printNextPageLink($nexttext, "Next Page");
  echo "</div>\n";
}


function printPageList($class="pagelist", $id=NULL) {
  printPageListWithNav(null, null, false, $class, $id);
}


function printPageListWithNav($prevtext, $nexttext, $nextprev=true, $class="pagelist", $id=NULL) {
  echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
  
  $total = getTotalPages();
  $current = getCurrentPage();
  
  echo "\n<ul class=\"$class\">";
    if ($nextprev) {
      echo "\n  <li class=\"prev\">"; 
        printPrevPageLink($prevtext, "Previous Page");
      echo "</li>";
    }
    
    for ($i=1; $i <= $total; $i++) {
      echo "\n  <li" . (($i == $current) ? " class=\"current\"" : "") . ">";
      printLink(getPageURL($i), $i, "Page $i" . (($i == $current) ? " (Current Page)" : ""));
      echo "</li>";
    }
    
    if ($nextprev) {
      echo "\n  <li class=\"next\">"; 
        printNextPageLink($nexttext, "Next Page");
      echo "</li>"; 
    }
  echo "\n</ul>";
  echo "\n</div>\n";
}


/*** Album Context ************************/
/******************************************/

function getAlbumTitle() { 
	if(!in_context(ZP_ALBUM)) return false;
	global $_zp_current_album;
	return $_zp_current_album->getTitle();
}
function printAlbumTitle($editable=false) { 
	global $_zp_current_album;
	if ($editable && zp_loggedin()) {
		echo "<div id=\"albumTitleEditable\" style=\"display: inline;\">" . getAlbumTitle() . "</div>\n";
    echo "<script>initEditableTitle('albumTitleEditable');</script>";
	} else {
    echo getAlbumTitle();	
	}
}

function getAlbumDate() {
  global $_zp_current_album;
  return $_zp_current_album->getDateTime();
}

function printAlbumDate($before="Date: ", $nonemessage="", $format="F jS, Y") {
  $date = getAlbumDate();
  if ($date) {
    echo $before . myts_date($format, $date);
  } else {
    echo $nonemessage;
  }
}

function getAlbumPlace() {
  global $_zp_current_album;
  return $_zp_current_album->getPlace();
}

function printAlbumPlace() {
  echo getAlbumPlace();
}

function getAlbumDesc() { 
	if(!in_context(ZP_ALBUM)) return false;
	global $_zp_current_album;
	return str_replace("\n", "<br />", $_zp_current_album->getDesc());
}
function printAlbumDesc($editable=false) { 
	global $_zp_current_album;
	if ($editable && zp_loggedin()) {
		echo "<div id=\"albumDescEditable\" style=\"display: block;\">" . getAlbumDesc() . "</div>\n";
    echo "<script>initEditableDesc('albumDescEditable');</script>";
	} else {
    echo getAlbumDesc();	
	}
	
}

function getAlbumLinkURL() { 
	global $_zp_current_album, $_zp_current_image;
  if (in_context(ZP_IMAGE)) {
    // Link to the page the current image belongs to.
    if (zp_conf('mod_rewrite')) {
      return WEBPATH . "/" . urlencode($_zp_current_album->name) . 
        "/page/" . $_zp_current_image->getAlbumPage();
    } else {
      return WEBPATH . "/index.php?album=" . urlencode($_zp_current_album->name) . 
        "&page=" . $_zp_current_image->getAlbumPage();
    }
  } else {
    if (zp_conf('mod_rewrite')) {
      return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/";
    } else {
      return WEBPATH . "/index.php?album=" . urlencode($_zp_current_album->name);
    }
  }
}

function printAlbumLink($text, $title, $class=NULL, $id=NULL) { 
	printLink(getAlbumLinkURL(), $text, $title, $class, $id);
}

/**
 * Print a link that allows the user to sort the current album if they are logged in.
 * If they are already sorting, the Save button is displayed.
 * 
 * @param  text   The text to display in the link
 * @param  title  The title attribute for the link
 * @param  class  The class of the link
 * @param  id     The id of the link
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printSortableAlbumLink($text, $title, $class=NULL, $id=NULL) {
  global $_zp_sortable_list, $_zp_current_album;
  if (zp_loggedin()) {
    if (!isset($_GET['sortable'])) {
      printLink(WEBPATH . "/zen/albumsort.php?page=edit&album=" . urlencode($_zp_current_album->getFolder()), 
        $text, $title, $class, $id);
    } else {
      // TODO: this doesn't really work yet
      $_zp_sortable_list->printForm(getAlbumLinkURL(), 'POST', 'Save', 'button');
    }
  }
}

/**
 * Print a link that allows the user to sort the Gallery if they are logged in.
 * If they are already sorting, the Save button is displayed.
 * 
 * @param  text   The text to display in the link
 * @param  title  The title attribute for the link
 * @param  class  The class of the link
 * @param  id     The id of the link
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printSortableGalleryLink($text, $title, $class=NULL, $id=NULL) {
  global $_zp_sortable_list, $_zp_current_album;
  if (zp_loggedin()) {
    if (!isset($_GET['sortable'])) {
      printLink(WEBPATH . "/zen/admin.php?page=edit", $text, $title, $class, $id);
    } else {
      // TODO: this doesn't really work yet
      $_zp_sortable_list->printForm(WEBPATH . "/zen/admin.php?page=edit", 'POST', 'Save', 'button');
    }
  }
}

function getAlbumThumb() { 
	global $_zp_current_album;
	return $_zp_current_album->getAlbumThumb();
}

function printAlbumThumbImage($alt, $class=NULL, $id=NULL) { 
	echo "<img src=\"" . getAlbumThumb() . "\" alt=\"$alt\"" .
		(($class) ? " class=\"$class\"" : "") . 
		(($id) ? " id=\"$id\"" : "") . " />";
}

function getNumImages() { 
	global $_zp_current_album;
	return $_zp_current_album->getNumImages();
}


function next_image() { 
	global $_zp_images, $_zp_current_image, $_zp_current_album, $_zp_page, $_zp_current_image_restore;
	if (is_null($_zp_images)) {
		$_zp_images = $_zp_current_album->getImages($_zp_page);
    $_zp_current_image_restore = $_zp_current_image;
		$_zp_current_image = array_shift($_zp_images);
		save_context();
    add_context(ZP_IMAGE);
		return true;
	} else if (empty($_zp_images)) {
		$_zp_images = NULL;
		$_zp_current_image = $_zp_current_image_restore;
		restore_context();
		return false;
	} else {
		$_zp_current_image = array_shift($_zp_images);
		return true;
	}
}


/*** Image Context ************************/
/******************************************/

function getImageTitle() { 
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_image;
	return $_zp_current_image->getTitle();
}
function printImageTitle($editable=false) { 
	global $_zp_current_image;
	if ($editable && zp_loggedin()) {
		echo "<div id=\"imageTitleEditable\" style=\"display: inline;\">" . getImageTitle() . "</div>\n";
    echo "<script>initEditableTitle('imageTitleEditable');</script>";
	} else {
    echo getImageTitle();	
	}
}

/**
 * Get the unique ID of this image.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function getImageID() {
  if (!in_context(ZP_IMAGE)) return false;
	global $_zp_current_image;
	return $_zp_current_image->getImageID();
}

/**
 * Print the unique ID of this image.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printImageID() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  echo "image_".getImageID();
}

/**
 * Get the sort order of this image.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function getImageSortOrder() {
  if (!in_context(ZP_IMAGE)) return false;
	global $_zp_current_image;
	return $_zp_current_image->getSortOrder();
}

/**
 * Print the sort order of this image.
 *
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printImageSortOrder() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  echo getImageSortOrder();
}

function getImageDesc() { 
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_image;
	return str_replace("\n", "<br />", $_zp_current_image->getDesc());
}

function printImageDesc($editable=false) {	
	global $_zp_current_image;
	if ($editable && zp_loggedin()) {
		echo "<div id=\"imageDescEditable\" style=\"display: block;\">" . getImageDesc() . "</div>\n";
    echo "<script>initEditableDesc('imageDescEditable');</script>";
	} else {
    echo getImageDesc();
	}
}


function hasNextImage() { global $_zp_current_image; return $_zp_current_image->getNextImage(); }
function hasPrevImage() { global $_zp_current_image; return $_zp_current_image->getPrevImage(); }

function getNextImageURL() {
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_album, $_zp_current_image;
	
	$nextimg = $_zp_current_image->getNextImage();
	
	if (zp_conf('mod_rewrite')) {
		return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/" . urlencode($nextimg->getFileName());
	} else {
		return WEBPATH . "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($nextimg->getFileName());
	}
}

function getPrevImageURL() {
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_album, $_zp_current_image;
	
	$previmg = $_zp_current_image->getPrevImage();
	
	if (zp_conf('mod_rewrite')) {
		return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/" . urlencode($previmg->getFileName());
	} else {
		return WEBPATH . "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($previmg->getFileName());
	}
}


function printPreloadScript() {
  global $_zp_current_image;
  $size = zp_conf('image_size');
  if (hasNextImage() || hasPrevImage()) {
    echo "<script type=\"text/javascript\">\n";
    if (hasNextImage()) {
      $nextimg = $_zp_current_image->getNextImage();
      echo "  nextimg = new Image();\n  nextimg.src = \"" . $nextimg->getSizedImage($size) . "\";\n";
    }
    if (hasPrevImage()) { 
      $previmg = $_zp_current_image->getPrevImage();
      echo "  previmg = new Image();\n  previmg.src = \"" . $previmg->getSizedImage($size) . "\";\n";
    }
    
    echo "</script>\n\n";
  }
}


function getPrevImageThumb() {
  if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_image;
  $img = $_zp_current_image->getPrevImage();
  return $img->getThumb();
}

function getNextImageThumb() {
  if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_image;
  $img = $_zp_current_image->getNextImage();
  return $img->getThumb();
}


function getImageLinkURL() { 
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_album, $_zp_current_image;
	if (zp_conf('mod_rewrite')) {
		return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/" . urlencode($_zp_current_image->name);
	} else {
		return WEBPATH . "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($_zp_current_image->name);
	}
}

function printImageLink($text, $title, $class=NULL, $id=NULL) {
	printLink(getImageLinkURL(), $text, $title, $class, $id);
}

function getImageThumb() { 
	global $_zp_current_image;
	return $_zp_current_image->getThumb();
}

function printImageThumb($alt, $class=NULL, $id=NULL) { 
	echo "<img src=\"" . getImageThumb() . "\" alt=\"$alt\"" .
    ((zp_conf('thumb_crop')) ? " width=\"".zp_conf('thumb_crop_width')."\" height=\"".zp_conf('thumb_crop_height')."\"" : "") .
		(($class) ? " class=\"$class\"" : "") . 
		(($id) ? " id=\"$id\"" : "") . " />";
}


/**
 * Print the entire <div> for a thumbnail. If we are in sorting mode, then only
 * the image is inserted, if not, then the hyperlink to the image is also added.
 * 
 * @author Todd Papaioannou (lucky@luckyspin.org)
 * @since  1.0.0
 */
function printImageDiv() {
  
  if (!isset($_GET['sortable'])) {
    echo '<a href="'.getImageLinkURL().'" title="'.getImageTitle().'">';
  }       
  printImageThumb(getImageTitle());
          
  if (!isset($_GET['sortable'])) {
    echo '</a>';
  }
}

// TODO:
function getImageEXIFData() { }

function getDefaultSizedImage() { 
	global $_zp_current_image;
	return $_zp_current_image->getSizedImage(zp_conf('image_size'));
}

function printDefaultSizedImage($alt, $class=NULL, $id=NULL) { 
	echo "<img src=\"" . getDefaultSizedImage() . "\" alt=\"$alt\"" .
		(($class) ? " class=\"$class\"" : "") . 
		(($id) ? " id=\"$id\"" : "") . " />";
}

function getFullImageURL() {
	global $_zp_current_image;
	return $_zp_current_image->getFullImage();
}

function getSizedImageURL($size) { 
	global $_zp_current_image;
	return $_zp_current_image->getSizedImage($size);
}

function printSizedImageLink($size, $text, $title, $class=NULL, $id=NULL) { 
  printLink(getSizedImageURL($size), $text, $title, $class, $id);
}



function getCommentCount() { 
  global $_zp_current_image;
  return $_zp_current_image->getCommentCount();
}

function getCommentsAllowed() {
  global $_zp_current_image;
  return $_zp_current_image->getCommentsAllowed();
}

// Iterate through comments; use the ZP_COMMENT context.
function next_comment() {
  global $_zp_current_image, $_zp_current_comment, $_zp_comments;
	if (is_null($_zp_current_comment)) {
		$_zp_comments = $_zp_current_image->getComments();
    if (empty($_zp_comments)) { return false; }
		$_zp_current_comment = array_shift($_zp_comments);
		add_context(ZP_COMMENT);
		return true;
	} else if (empty($_zp_comments)) {
		$_zp_comments = NULL;
		$_zp_current_comment = NULL;
		rem_context(ZP_COMMENT);
		return false;
	} else {
		$_zp_current_comment = array_shift($_zp_comments);
		return true;
	}
}

/*** Comment Context **********************/
/******************************************/

function getCommentAuthorName() { global $_zp_current_comment; return $_zp_current_comment['name']; }

function getCommentAuthorEmail() { global $_zp_current_comment; return $_zp_current_comment['email']; }

function getCommentAuthorSite() { global $_zp_current_comment; return $_zp_current_comment['website']; }

function printCommentAuthorLink($title=NULL, $class=NULL, $id=NULL) {
  $site = getCommentAuthorSite();
  $name = getCommentAuthorName();
  if (empty($site)) {
    echo $name;
  } else {
    if (is_null($title)) $title = "Visit $name";
    printLink($site, $name, $title, $class, $id);
  }
}

function getCommentDate() { global $_zp_current_comment; return myts_date("F jS, Y", $_zp_current_comment['date']); }

function getCommentTime() { global $_zp_current_comment; return myts_date("g:i a", $_zp_current_comment['date']); }

function getCommentBody() { 
  global $_zp_current_comment; 
  return str_replace("\n", "<br />", stripslashes($_zp_current_comment['comment'])); 
}

function printEditCommentLink($text, $before='', $after='', $title=NULL, $class=NULL, $id=NULL) {
  global $_zp_current_comment;
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH . '/admin/?page=editcomment&id=' . $_zp_current_comment['id'], $text, $title, $class, $id);
    echo $after;
  }
}

/*** End template functions ***/

?>
