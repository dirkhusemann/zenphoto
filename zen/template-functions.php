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
$_zp_albums = NULL;
$_zp_current_image = NULL;
$_zp_images = NULL;
$_zp_current_comment = NULL;
$_zp_comments = NULL;
$_zp_current_context = ZP_INDEX;

// Parse the GET request to see what exactly is requested...

if (isset($_GET['album'])) {
  $g_album = get_magic_quotes_gpc() ? stripslashes($_GET['album']) : $_GET['album'];
	if (isset($_GET['image'])) {
    $g_image = get_magic_quotes_gpc() ? stripslashes($_GET['image']) : $_GET['image'];
		$_zp_current_context = ZP_IMAGE | ZP_ALBUM | ZP_INDEX;
		// An image page; for image.php.
		$_zp_current_image = new Image(new Album($_zp_gallery, $g_album), $g_image);
		$_zp_current_album = $_zp_current_image->getAlbum();
    
    //// Comment form handling.
    if (isset($_POST['comment'])) {
      if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['comment'])) {
        if (isset($_POST['website'])) $website = $_POST['website']; else $website = "";
        $commentadded = $_zp_current_image->addComment($_POST['name'], $_POST['email'], $website, $_POST['comment']);
        // Then redirect to this image page to prevent re-submission.
        if ($commentadded) {
          // Comment added with no errors, redirect to the image... save cookie if requested.
          if (isset($_POST['remember'])) {
            // Should always re-cookie to update info in case it's changed...
            $info = array($_POST['name'], $_POST['email'], $website);
            setcookie("zenphoto", implode('|~*~|', $info), time()+5184000, "/");
            $stored = array($_POST['name'], $_POST['email'], $website, $_POST['comment'], isset($_POST['remember']));
          } else {
            setcookie("zenphoto", "", time()-368000, "/");
            $stored = array("","","",false);
          }
          $g_album = urlencode($g_album); $g_image = urlencode($g_image);
          header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/" . 
            (zp_conf('mod_rewrite') ? "$g_album/$g_image" : "image.php?album=$g_album&image=$g_image"));
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
	}
} else {
	$_zp_current_context = ZP_INDEX;
}

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
	global $_zp_albums, $_zp_gallery, $_zp_current_album, $_zp_page;
	if (is_null($_zp_albums)) {
		$_zp_albums = $_zp_gallery->getAlbums($_zp_page);
		$_zp_current_album = new Album($_zp_gallery, array_shift($_zp_albums));
		add_context(ZP_ALBUM);
		return true;
	} else if (empty($_zp_albums)) {
		$_zp_albums = NULL;
		$_zp_current_album = NULL;
		rem_context(ZP_ALBUM);
		return false;
	} else {
		$_zp_current_album = new Album($_zp_gallery, array_shift($_zp_albums));
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
        return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/page/" . $page . "/";
      } else {
        return WEBPATH . "/album.php?album=" . urlencode($_zp_current_album->name) . "&page=" . $page;
      }
    } else if (in_context(ZP_INDEX)) {
      if (zp_conf('mod_rewrite')) {
        return WEBPATH . "/page/" . $page . "/";
      } else {
        return WEBPATH . "/index.php?page=" . $page;
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
      return WEBPATH . "/album.php?album=" . urlencode($_zp_current_album->name) . 
        "&page=" . $_zp_current_image->getAlbumPage();
    }
  } else {
    if (zp_conf('mod_rewrite')) {
      return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/";
    } else {
      return WEBPATH . "/album.php?album=" . urlencode($_zp_current_album->name);
    }
  }
}

function printAlbumLink($text, $title, $class=NULL, $id=NULL) { 
	printLink(getAlbumLinkURL(), $text, $title, $class, $id);
}

// TODO:
function getAlbumPlace() { }
function printAlbumPlace($editable=true) { }

function getAlbumThumb() { 
	global $_zp_current_album;
	return $_zp_current_album->getAlbumThumb();
}
function printAlbumThumbImage($alt, $class=NULL, $id=NULL) { 
	echo "<img src=\"" . getAlbumThumb() . "\" alt=\"$alt\"" .
		(($class) ? " class=\"$class\"" : "") . 
		(($id) ? " id=\"$id\"" : "") . " />";
}

// TODO:
function getAlbumDate() { }
function printAlbumDate($editable=true) { }

function getNumImages() { 
	global $_zp_current_album;
	return $_zp_current_album->getNumImages();
}


function next_image() { 
	global $_zp_images, $_zp_current_image, $_zp_current_album, $_zp_page;
	if (is_null($_zp_images)) {
		$_zp_images = $_zp_current_album->getImages($_zp_page);
		$_zp_current_image = new Image($_zp_current_album, array_shift($_zp_images));
		add_context(ZP_IMAGE);
		return true;
	} else if (empty($_zp_images)) {
		$_zp_images = NULL;
		$_zp_current_image = NULL;
		rem_context(ZP_IMAGE);
		return false;
	} else {
		$_zp_current_image = new Image($_zp_current_album, array_shift($_zp_images));
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
	if (zp_conf('mod_rewrite')) {
		return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/" . urlencode($_zp_current_image->getNextImage());
	} else {
		return WEBPATH . "/image.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($_zp_current_image->getNextImage());
	}
}
function getPrevImageURL() {
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_album, $_zp_current_image;
	if (zp_conf('mod_rewrite')) {
		return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/" . urlencode($_zp_current_image->getPrevImage());
	} else {
		return WEBPATH . "/image.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($_zp_current_image->getPrevImage());
	}
}


function getImageLinkURL() { 
	if(!in_context(ZP_IMAGE)) return false;
	global $_zp_current_album, $_zp_current_image;
	if (zp_conf('mod_rewrite')) {
		return WEBPATH . "/" . urlencode($_zp_current_album->name) . "/" . urlencode($_zp_current_image->name);
	} else {
		return WEBPATH . "/image.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($_zp_current_image->name);
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
		(($class) ? " class=\"$class\"" : "") . 
		(($id) ? " id=\"$id\"" : "") . " />";
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

// TODO:
function getSizedImageURL($size) { }
function getSizedImageLink($size, $text, $title, $class=NULL, $id=NULL) { }


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

/*** End template functions ***/

?>
