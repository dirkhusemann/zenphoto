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

// Initialize the global objects and object arrays:
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

// Fix special characters in the album and image names if mod_rewrite is on:
// This is redundand and hacky; we need to either make the rewriting internal,
// or fix the bugs in mod_rewrite. The former is probably a good idea.

if (zp_conf('mod_rewrite')) {
  $zppath = substr($_SERVER['REQUEST_URI'], strlen(WEBPATH)+1);
  $qspos = strpos($zppath, '?');
  if ($qspos !== false) $zppath = substr($zppath, 0, $qspos);
  if (strpos($zppath, '/page/') === false && strpos($zppath, '.php') === false) {
    $zpitems = explode("/", $zppath);
    if (isset($zpitems[0]) && $zpitems[0] != 'page')
      $req_album = $zpitems[0];
    if (isset($zpitems[1]) && $zpitems[1] != 'page')
      $req_image = $zpitems[1];
    if (!empty($req_album)) $_GET['album'] = urldecode($req_album);
    if (!empty($req_image)) $_GET['image'] = urldecode($req_image);
  }
  if (!empty($req_album))
    $_GET['album'] = urldecode($req_album);
  if (!empty($req_image))
    $_GET['image'] = urldecode($req_image);
}


// Parse the GET request to see what's requested
if (isset($_GET['album'])) {
  $g_album = sanitize($_GET['album']);

  if (isset($_GET['image'])) {
    $g_image = sanitize($_GET['image']);

    $_zp_current_context = ZP_IMAGE | ZP_ALBUM | ZP_INDEX;

    // An image page. Instantiate objects.
    $_zp_current_album = new Album($_zp_gallery, $g_album);
    $_zp_current_image = new Image($_zp_current_album, $g_image);

    // TODO: Better error handling than this.
    if (!$_zp_current_album->exists) {
      die("<b>Zenphoto error:</b> album does not exist.");
    } else if (!$_zp_current_image->exists) {
      die("<b>Zenphoto error:</b> image does not exist.");
    }
    
    //// Comment form handling.
    if (isset($_POST['comment'])) {
      if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['comment'])) {
        if (isset($_POST['website'])) $website = strip_tags($_POST['website']); else $website = "";
        $commentadded = $_zp_current_image->addComment(strip_tags($_POST['name']), strip_tags($_POST['email']), $website, 
          kses($_POST['comment'], zp_conf('allowed_tags')));
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
          header("Location: " . FULLWEBPATH . "/" . 
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
      die("<b>Zenphoto error:</b> Album does not exist.");
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
      $_zp_current_image->save();
      return $newtitle;
    } else if (in_context(ZP_ALBUM)) {
      $_zp_current_album->setTitle($newtitle);
      $_zp_current_album->save();
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
      $_zp_current_image->save();
      return $newdesc;
    } else if (in_context(ZP_ALBUM)) {
      $_zp_current_album->setDesc($newdesc);
      $_zp_current_album->save();
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
  echo "<a href=\"" . htmlspecialchars($url) . "\"" . 
  (($title) ? " title=\"" . htmlspecialchars($title, ENT_QUOTES) . "\"" : "") .
  (($class) ? " class=\"$class\"" : "") . 
  (($id) ? " id=\"$id\"" : "") . ">" .
  $text . "</a>";
}

function printVersion() {
  echo zp_conf('version');
}

/** 
 * Prints a link to administration if the current user is logged-in 
 */
function printAdminLink($text, $before='', $after='', $title=NULL, $class=NULL, $id=NULL) {
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH.'/zen/admin.php', $text, $title, $class, $id);
    echo $after;
  }
}

/**
 * Returns either the rewrite path or the plain, non-mod_rewrite path
 * based on the mod_rewrite option in zp-config.php.
 * @param $rewrite is the path to return if rewrite is enabled. (eg: "/myalbum")
 * @param $plain is the path if rewrite is disabled (eg: "/?album=myalbum")
 * The given paths can start /with or without a slash, it will decide automatically.
 *
 * For future reference, this function could be used to specially escape items in
 * the rewrite chain, like the # character (a bug in mod_rewrite).
 */
function rewrite_path($rewrite, $plain) {
  $path = null;
  if (zp_conf('mod_rewrite')) {
    $path = $rewrite;
  } else {
    $path = $plain;
  }
  if (substr($path, 0, 1) == "/") $path = substr($path, 1);
  return WEBPATH . "/" . $path;
}




/*** Gallery Index (album list) Context ***/
/******************************************/

function getGalleryTitle() { 
  return zp_conf('gallery_title');
}
function printGalleryTitle() { 
  echo htmlspecialchars(getGalleryTitle()); 
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
  if (in_context(ZP_ALBUM) && $_zp_current_album->getGalleryPage() > 1) {
    $page = $_zp_current_album->getGalleryPage();
    return rewrite_path("/page/" . $page, "/index.php?page=" . $page);
  } else {
    return WEBPATH . "/";
  }
}

function getNumAlbums() { 
  global $_zp_gallery;
  return $_zp_gallery->getNumAlbums();
}


/*** Album AND Gallery Context ************/
/******************************************/
// (Common functions shared by Albums and the Gallery Index)

// WHILE next_album(): context switches to Album.
// If we're already in the album context, this is a sub-albums loop, which,
// quite simply, changes the source of the album list.
// Switch back to the previous context when there are no more albums.
function next_album() {
  global $_zp_albums, $_zp_gallery, $_zp_current_album, $_zp_page, $_zp_current_album_restore;
  if (is_null($_zp_albums)) {
    if (in_context(ZP_ALBUM)) {
      $_zp_albums = $_zp_current_album->getSubAlbums();
    } else {
      $_zp_albums = $_zp_gallery->getAlbums($_zp_page);
    }
    if (empty($_zp_albums)) { return false; }
    $_zp_current_album_restore = $_zp_current_album;
    $_zp_current_album = new Album($_zp_gallery, array_shift($_zp_albums));
    save_context();
    add_context(ZP_ALBUM);
    return true;
  } else if (empty($_zp_albums)) {
    $_zp_albums = NULL;
    $_zp_current_album = $_zp_current_album_restore;
    restore_context();
    return false;
  } else {
    $_zp_current_album = new Album($_zp_gallery, array_shift($_zp_albums));
    return true;
  }
}

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
      return rewrite_path( urlencode($_zp_current_album->name) . (($page > 1) ? "/page/" . $page . "/" : ""), 
        "/index.php?album=" . urlencode($_zp_current_album->name) . (($page > 1) ? "&page=" . $page : "") );
    } else if (in_context(ZP_INDEX)) {
      return rewrite_path((($page > 1) ? "/page/" . $page . "/" : "/"), "/index.php" . (($page > 1) ? "?page=" . $page : ""));
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
    echo "<div id=\"albumTitleEditable\" style=\"display: inline;\">" . htmlspecialchars(getAlbumTitle()) . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableTitle('albumTitleEditable');</script>";
  } else {
    echo htmlspecialchars(getAlbumTitle());  
  }
}

function getParentAlbums() {
  if(!in_context(ZP_ALBUM)) return false;
  global $_zp_current_album;
  $parents = array();
  $album = $_zp_current_album;
  while (!is_null($album = $album->getParent())) {
    array_unshift($parents, $album);
  }
  return $parents;
}

function printParentBreadcrumb($before = "", $between=" | ", $after = " | ") {
  $parents = getParentAlbums();
  $n = count($parents);
  if ($n == 0) return;
  $i = 0;
  foreach($parents as $parent) {
    if ($i > 0) echo $between;
    $url = rewrite_path("/" . urlencode($parent->name) . "/", "/index.php?album=" . urlencode($parent->name));
    printLink($url, $parent->getTitle(), $parent->getDesc());
    $i++;
  }
  echo $after;
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
    echo "<script type=\"text/javascript\">initEditableDesc('albumDescEditable');</script>";
  } else {
    echo getAlbumDesc();  
  }
  
}

function getAlbumLinkURL() {
  global $_zp_current_album, $_zp_current_image;
  if (in_context(ZP_IMAGE) && $_zp_current_image->getAlbumPage() > 1) {
    // Link to the page the current image belongs to.
    return rewrite_path("/" . urlencode($_zp_current_album->name) . "/page/" . $_zp_current_image->getAlbumPage(),
      "/index.php?album=" . urlencode($_zp_current_album->name) . "&page=" . $_zp_current_image->getAlbumPage());
  } else {
    return rewrite_path("/" . urlencode($_zp_current_album->name) . "/",
      "/index.php?album=" . urlencode($_zp_current_album->name));
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
  echo "<img src=\"" . htmlspecialchars(getAlbumThumb()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    (($class) ? " class=\"$class\"" : "") . 
    (($id) ? " id=\"$id\"" : "") . " />";
}

function getCustomAlbumThumb($size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=null) {
  global $_zp_current_album;
  $thumb = $_zp_current_album->getAlbumThumbImage();
  return $thumb->getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy);
}

function printCustomAlbumThumbImage($alt, $size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=null, $class=NULL, $id=NULL) {
  echo "<img src=\"" . htmlspecialchars(getCustomAlbumThumb($size, $width, $height, $cropw, $croph, $cropx, $cropy)) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
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
    if (empty($_zp_images)) { return false; }
    $_zp_current_image_restore = $_zp_current_image;
    $_zp_current_image = new Image($_zp_current_album, array_shift($_zp_images));
    save_context();
    add_context(ZP_IMAGE);
    return true;
  } else if (empty($_zp_images)) {
    $_zp_images = NULL;
    $_zp_current_image = $_zp_current_image_restore;
    restore_context();
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
    echo "<div id=\"imageTitleEditable\" style=\"display: inline;\">" . htmlspecialchars(getImageTitle()) . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableTitle('imageTitleEditable');</script>";
  } else {
    echo htmlspecialchars(getImageTitle());  
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
    echo "<script type=\"text/javascript\">initEditableDesc('imageDescEditable');</script>";
  } else {
    echo getImageDesc();
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


function hasNextImage() { global $_zp_current_image; return $_zp_current_image->getNextImage(); }
function hasPrevImage() { global $_zp_current_image; return $_zp_current_image->getPrevImage(); }

function getNextImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  
  $nextimg = $_zp_current_image->getNextImage();
  
  return rewrite_path("/" . urlencode($_zp_current_album->name) . "/" . urlencode($nextimg->getFileName()),
    "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($nextimg->getFileName()));
}

function getPrevImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  
  $previmg = $_zp_current_image->getPrevImage();
  
  return rewrite_path("/" . urlencode($_zp_current_album->name) . "/" . urlencode($previmg->getFileName()),
    "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($previmg->getFileName()));
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
  return rewrite_path("/" . urlencode($_zp_current_album->name) . "/" . urlencode($_zp_current_image->name),
    "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($_zp_current_image->name));
}

function printImageLink($text, $title, $class=NULL, $id=NULL) {
  printLink(getImageLinkURL(), $text, $title, $class, $id);
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
    echo '<a href="'.htmlspecialchars(getImageLinkURL()).'" title="'.htmlspecialchars(getImageTitle(), ENT_QUOTES).'">';
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

function getSizeCustomImage($size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=NULL) {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  $h = $_zp_current_image->getHeight();
  $w = $_zp_current_image->getWidth();
  $ls = zp_conf('image_use_longest_side');
  $us = zp_conf('image_allow_upscale');
  
  if (!empty($size)) {
    $dim = $size;
    $width = $height = false;
  } else if (!empty($width)) {
    $dim = $width;
    $size = $height = false;
  } else if (!empty($height)) {
    $dim = $height;
    $size = $width = false;
  }
  
  $hprop = round(($h / $w) * $dim);
  $wprop = round(($w / $h) * $dim);
  
  if (($size && $ls && $h > $w)
    || $height) {
    // Scale the height
    $newh = $dim;
    $neww = $wprop;
  } else {
    // Scale the width
    $neww = $dim;
    $newh = $hprop;
  }

  if (!$us && $newh >= $h && $neww >= $w) {
    return array($w, $h);
  } else {
    if ($cropw && $cropw < $neww) $neww = $cropw;
    if ($croph && $croph < $newh) $newh = $croph;
    return array($neww, $newh);
  }
}

// Returns an array [width, height] of the default-sized image.
function getSizeDefaultImage() {
  return getSizeCustomImage(zp_conf('image_size'));
}

// Returns an array [width, height] of the original image.
function getSizeFullImage() {
  global $_zp_current_image;
  return array($_zp_current_image->getWidth(), $_zp_current_image->getHeight());
}

// The width of the default-sized image (in printDefaultSizedImage)
function getDefaultWidth() {
  $size = getSizeDefaultImage(); return $size[0];
}
// The height of the default-sized image (in printDefaultSizedImage)
function getDefaultHeight() {
  $size = getSizeDefaultImage(); return $size[1];
}

// The width of the original image
function getFullWidth() {
  $size = getSizeFullImage(); return $size[0];
}

// The height of the original image
function getFullHeight() {
  $size = getSizeFullImage(); return $size[1];
}

// Returns true if the image is landscape-oriented (width is greater than height)
function isLandscape() {
  if (getFullWidth() >= getFullHeight()) return true;
  return false;
}


function printDefaultSizedImage($alt, $class=NULL, $id=NULL) { 
  echo "<img src=\"" . htmlspecialchars(getDefaultSizedImage()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    " width=\"" . getDefaultWidth() . "\" height=\"" . getDefaultHeight() . "\"" .
    (($class) ? " class=\"$class\"" : "") . 
    (($id) ? " id=\"$id\"" : "") . " />";
}


function getImageThumb() { 
  global $_zp_current_image;
  return $_zp_current_image->getThumb();
}

function printImageThumb($alt, $class=NULL, $id=NULL) { 
  echo "<img src=\"" . htmlspecialchars(getImageThumb()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    ((zp_conf('thumb_crop')) ? " width=\"".zp_conf('thumb_crop_width')."\" height=\"".zp_conf('thumb_crop_height')."\"" : "") .
    (($class) ? " class=\"$class\"" : "") . 
    (($id) ? " id=\"$id\"" : "") . " />";
}

function getFullImageURL() {
  global $_zp_current_image;
  return $_zp_current_image->getFullImage();
}

function getSizedImageURL($size) { 
  getCustomImageURL($size);
}

function getCustomImageURL($size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=NULL) {
  global $_zp_current_image;
  return $_zp_current_image->getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy);
}

function printCustomSizedImage($alt, $size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=NULL, $class=NULL, $id=NULL) { 
  $sizearr = getSizeCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy);
  echo "<img src=\"" . htmlspecialchars(getCustomImageURL($size, $width, $height, $cropw, $croph, $cropx, $cropy)) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    " width=\"" . $sizearr[0] . "\" height=\"" . $sizearr[1] . "\"" .
    (($class) ? " class=\"$class\"" : "") . 
    (($id) ? " id=\"$id\"" : "") . " />";
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
    echo htmlspecialchars($name);
  } else {
    if (is_null($title)) $title = "Visit $name";
    printLink($site, $name, $title, $class, $id);
  }
}

function getCommentDate($format = "F jS, Y") { global $_zp_current_comment; return myts_date($format, $_zp_current_comment['date']); }

function getCommentTime($format = "g:i a") { global $_zp_current_comment; return myts_date($format, $_zp_current_comment['date']); }

function getCommentBody() { 
  global $_zp_current_comment; 
  return str_replace("\n", "<br />", stripslashes($_zp_current_comment['comment'])); 
}

function printEditCommentLink($text, $before='', $after='', $title=NULL, $class=NULL, $id=NULL) {
  global $_zp_current_comment;
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH . '/zen/admin.php?page=editcomment&id=' . $_zp_current_comment['id'], $text, $title, $class, $id);
    echo $after;
  }
}

/*** End template functions ***/

?>
