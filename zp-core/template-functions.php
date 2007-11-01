<?php

/*** template-functions.php ****************************************************
 * Functions used to display content in themes.
 ******************************************************************************/

// Load the classes
require_once('classes.php');
// Invoke the controller to handle requests
require_once('controller.php');


/******************************************************************************/
/*** Template Functions *******************************************************/
/******************************************************************************/

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
    printLink(WEBPATH.'/' . ZENFOLDER . '/admin.php', $text, $title, $class, $id);
    echo $after;
  }
}

/* Subalbum administration if the current user is logged-in*/
function printSubalbumAdmin($text, $before='', $after='') {
  global $_zp_current_album, $_zp_themeroot;
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH.'/' . ZENFOLDER . '/admin.php?page=edit&album=' . urlencode($_zp_current_album->name), $text, NULL, NULL, NULL); 
    echo $after;
   }
}

/* Admin link toolbox */
function printAdminToolbox($context=null, $id="admin") {
  if (zp_loggedin()) {
    $dataid = $id . '_data';
    if (is_null($context)) { $context = get_context(); }
    echo '<div id="' .$id. '">'."\n".'<a href="javascript: toggle('. "'" .$dataid."'".');"><h3>Admin Toolbox</h3></a>'."\n".'</div>'; 
    echo '<div id="' .$dataid. '" style="display: none;">'."\n"; 
    printAdminLink('Admin', '', "<br />\n"); 
    if ($context == ZP_INDEX) {
      if (!in_context(ZP_SEARCH)) {
        printSortableGalleryLink('Sort Gallery', 'Manual sorting');
        echo "<br />\n";
	    }
    } else if (!in_context(ZP_IMAGE | ZP_SEARCH)) {
      printSubalbumAdmin('Edit album', '', "<br />\n");
      printSortableAlbumLink('Sort Album', 'Manual sorting');
      echo "<br />\n";
    }
    echo "<a href=\"".ZENFOLDER."/admin.php?logout\">Logout</a>\n";
    echo "</div>\n"; 
  }
}

/**  
 * Print any Javascript required by zenphoto. Every theme should include this somewhere in its <head>. 
 */
function zenJavascript() {
  global $_zp_phoogle;
  if(zp_conf('gmaps_apikey') != ''){$_zp_phoogle->printGoogleJS();}
  if (zp_loggedin()) {
    echo "  <script type=\"text/javascript\" src=\"".WEBPATH."/" . ZENFOLDER . "/ajax.js\"></script>\n";
    echo "  <script type=\"text/javascript\">\n";
    sajax_show_javascript();
    echo "  </script>";
  }
  echo "  <script type=\"text/javascript\" src=\"".WEBPATH."/" . ZENFOLDER . "/scripts-common.js\"></script>\n";
  echo "  <script type=\"text/javascript\" src=\"".WEBPATH."/" . ZENFOLDER . "/flvplayer.js\"></script>\n";
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
  return zp_conf('website_title');
}
function getMainSiteURL() { 
  return zp_conf('website_url');
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
  global $_zp_gallery, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    return $_zp_current_search->getNumAlbums();
  } else {
    return $_zp_gallery->getNumAlbums();
  }
}


/*** Album AND Gallery Context ************/
/******************************************/
// (Common functions shared by Albums and the Gallery Index)

// WHILE next_album(): context switches to Album.
// If we're already in the album context, this is a sub-albums loop, which,
// quite simply, changes the source of the album list.
// Switch back to the previous context when there are no more albums.
function next_album($all=false, $sorttype=null) {
  global $_zp_albums, $_zp_gallery, $_zp_current_album, $_zp_page, $_zp_current_album_restore, $_zp_current_search;
  if (is_null($_zp_albums)) {
    if (in_context(ZP_SEARCH)) {
	  $_zp_albums = $_zp_current_search-> getAlbums($all ? 0 : $_zp_page);
	} else if (in_context(ZP_ALBUM)) {
      $_zp_albums = $_zp_current_album->getSubAlbums($all ? 0 : $_zp_page, $sorttype);
    } else {
      $_zp_albums = $_zp_gallery->getAlbums($all ? 0 : $_zp_page, $sorttype);
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

function getIDforAlbum() { 
if(!in_context(ZP_ALBUM)) return false;
global $_zp_current_album;
return $_zp_current_album->getAlbumID();
}
function getNumSubalbums() {
  global $_zp_current_album;
  return count($_zp_current_album->getSubalbums());
}

function getTotalPages($oneImagePage=false) { 
  global $_zp_gallery;
  if (in_context(ZP_ALBUM | ZP_SEARCH)) {
    if (in_context(ZP_SEARCH)) {
      $pageCount = ceil(getNumAlbums() / zp_conf('albums_per_page'));
    } else {
      $pageCount = ceil(getNumSubalbums() / zp_conf('albums_per_page'));
    }	
    $imageCount = getNumImages();  
    if ($oneImagePage) {
      $imageCount = min(1, $imageCount);
    }
    $pageCount = ($pageCount + ceil(($imageCount - zp_conf('images_first_page')) / zp_conf('images_per_page')));
    return $pageCount;
  } else if (in_context(ZP_INDEX)) {
    return ceil($_zp_gallery->getNumAlbums() / zp_conf('albums_per_page'));
  } else {
    return null;
  }
}

function getPageURL_($page, $total) {
  global $_zp_current_album, $_zp_gallery, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    $searchwords = $_zp_current_search->words;
    if (empty($searchwords)) { 
      $searchwords = $_zp_current_search->dates; 
      $searchpagepath = "index.php?p=search&date=".$searchwords."&page=".$page;
    } else { 
      $searchpagepath = "index.php?p=search&words=".$searchwords."&page=".$page; 
    }
    return $searchpagepath;
  } else {
    if ($page <= $total && $page > 0) {
      if (in_context(ZP_ALBUM)) {
        return rewrite_path( pathurlencode($_zp_current_album->name) . (($page > 1) ? "/page/" . $page . "/" : ""), 
          "/index.php?album=" . pathurlencode($_zp_current_album->name) . (($page > 1) ? "&page=" . $page : "") );
      } else if (in_context(ZP_INDEX)) {
        return rewrite_path((($page > 1) ? "/page/" . $page . "/" : "/"), "/index.php" . (($page > 1) ? "?page=" . $page : ""));
      }       
    }
    return null; 
  }
}

function getPageURL($page) {
  $total = getTotalPages();
  return(getPageURL_($page, $total));
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
  printPageListWithNav(null, null, false, false, $class, $id);
}


function printPageListWithNav($prevtext, $nexttext, $oneImagePage=false, $nextprev=true, $class="pagelist", $id=NULL) {
  echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
  $total = getTotalPages($oneImagePage);
  $current = getCurrentPage();
  echo "\n<ul class=\"$class\">";
  if ($nextprev) {
    echo "\n  <li class=\"prev\">"; 
    printPrevPageLink($prevtext, "Previous Page");
    echo "</li>";
  }
  for ($i=($j=max(1, min($current-2, $total-6))); $i <= min($total, $j+6); $i++) {
    echo "\n  <li" . (($i == $current) ? " class=\"current\"" : "") . ">";
    printLink(getPageURL_($i, $total), $i, "Page $i" . (($i == $current) ? " (Current Page)" : ""));
    echo "</li>";
  }
  if ($i <= $total) {echo "\n <li><a>" . ". . ." . "</a></li>"; }
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

// gets the n for n of m albums	
function albumNumber() {
  global $_zp_current_album, $_zp_current_image, $_zp_current_search, $_zp_gallery;
  $name = $_zp_current_album->getFolder();
  if (in_context(ZP_SEARCH)) {
    $albums = $_zp_current_search->getAlbums();
  } else if (in_context(ZP_ALBUM)) {
    $parent = $_zp_current_album->getParent();
    if (is_null($parent)) {
      $albums = $_zp_gallery->getAlbums();
    } else {
        $albums = $parent->getSubalbums();
    }
  }
  $ct = count($albums);
  for ($c = 0; $c < $ct; $c++) {
    if ($name == $albums[$c]) {
      return $c+1;
    }
  }
  return false;
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
    $url = rewrite_path("/" . pathurlencode($parent->name) . "/", "/index.php?album=" . urlencode($parent->name));
    printLink($url, $parent->getTitle(), $parent->getDesc());
    $i++;
  }
  echo $after;
}

function getAlbumDate($format=null) {
  global $_zp_current_album;
  $d = $_zp_current_album->getDateTime();
  if (empty($d) || ($d == '0000-00-00 00:00:00')) { 
    return false; 
  }
  if (is_null($format)) {
    return $d;
  }
  return date($format, strtotime($d));  
}

function printAlbumDate($before="Date: ", $nonemessage="", $format="F jS, Y") {
  $date = getAlbumDate($format);
  if ($date) {
    echo $before . $date;
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
    return rewrite_path("/" . pathurlencode($_zp_current_album->name) . "/page/" . $_zp_current_image->getAlbumPage(),
      "/index.php?album=" . urlencode($_zp_current_album->name) . "&page=" . $_zp_current_image->getAlbumPage());
  } else {
    return rewrite_path("/" . pathurlencode($_zp_current_album->name) . "/",
      "/index.php?album=" . urlencode($_zp_current_album->name));
  }
}

function printAlbumLink($text, $title, $class=NULL, $id=NULL) { 
  printLink(getAlbumLinkURL(), $text, $title, $class, $id);
}

/**
 * Print a link that allows the user to sort the current album if they are logged in.
 * If they are already sorting, the Save button is displayed.
 */
function printSortableAlbumLink($text, $title, $class=NULL, $id=NULL) {
  global $_zp_sortable_list, $_zp_current_album;
  if (zp_loggedin()) {
    if (!isset($_GET['sortable'])) {
      printLink(WEBPATH . "/" . ZENFOLDER . "/albumsort.php?page=edit&album=" . urlencode($_zp_current_album->getFolder()), 
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
 */
function printSortableGalleryLink($text, $title, $class=NULL, $id=NULL) {
  global $_zp_sortable_list, $_zp_current_album;
  if (zp_loggedin()) {
    if (!isset($_GET['sortable'])) {
      printLink(WEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit", $text, $title, $class, $id);
    } else {
      // TODO: this doesn't really work yet
      $_zp_sortable_list->printForm(WEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit", 'POST', 'Save', 'button');
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
  /* set the HTML image width and height parameters in case this image was "zen-logo.gif" substituted for no thumbnail then the thumb layout is preserved */
  if ($sizeW = max(is_null($width) ? 0: $sizeW, is_null($cropw) ? 0 : $cropw)) {
    $sizing = ' width="' . $sizeW . '"'; 
  } else { 
    $sizing = null; 
  }
  if ($sizeH = max(is_null($height) ? 0 : $height, is_null($croph) ? 0 : $croph)) {
    $sizing = $sizing . ' height="' . $sizeH . '"';
  }
  echo "<img src=\"" . htmlspecialchars(getCustomAlbumThumb($size, $width, $height, $cropw, $croph, $cropx, $cropy)). "\"" . $sizing . " alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    (($class) ? " class=\"$class\"" : "") . 
    (($id) ? " id=\"$id\"" : "") . " />";
}


/** Get the URL of the next album in the gallery. */
function getNextAlbumURL() {
  if(!in_context(ZP_ALBUM)) return false;
  global $_zp_current_album;
  $nextalbum = $_zp_current_album->getNextAlbum();
  return rewrite_path("/" . pathurlencode($nextalbum->name),
    "/index.php?album=" . urlencode($nextalbum->name));
}

function getPrevAlbumURL() {
  if(!in_context(ZP_ALBUM)) return false;
  global $_zp_current_album;
  $prevalbum = $_zp_current_album->getPrevAlbum();
  return rewrite_path("/" . pathurlencode($prevalbum->name),
    "/index.php?album=" . urlencode($prevalbum->name));
}

function isImagePage() {
  global $_zp_page;
  return ($_zp_page - getTotalPages(true)) >= 0;
}

function isAlbumPage() {
  global $_zp_page;
  if (in_context(ZP_SEARCH)) {
    $pageCount = Ceil(getNumAlbums() / zp_conf('albums_per_page'));
  } else {
    $pageCount = Ceil(getNumSubalbums() / zp_conf('albums_per_page'));
  }
  return ($_zp_page <= $pageCount);
}

function getNumImages() { 
  global $_zp_current_album, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    return $_zp_current_search->getNumImages();
  } else {
    return $_zp_current_album->getNumImages();
  }
}

function next_image($all=false, $firstPageCount=0, $sorttype=null) { 
  global $_zp_images, $_zp_current_image, $_zp_current_album, $_zp_page, $_zp_current_image_restore, 
         $_zp_conf_vars, $_zp_current_search, $_zp_gallery;
  $imagePageOffset = getTotalPages(true) - 1; /* gives us the count of pages for album thumbs */
  if ($all) { 
    $imagePage = 1;
  } else {
    $_zp_conf_vars['images_first_page'] = $firstPageCount;  /* save this so pagination can see it */
    $imagePage = $_zp_page - $imagePageOffset;
  }
  if ($firstPageCount > 0) {
    $imagePage = $imagePage + 1;  /* can share with last album page */
  }
  
  if ($imagePage <= 0) {
    return false;  /* we are on an album page */
    }
	
  if (is_null($_zp_images)) {
    
//echo "\n<br>firstPageCount=$firstPageCount";
//echo "\n<br>_zp_page=$_zp_page";
//echo "\n<br>imagePage=$imagePage";

    if (in_context(ZP_SEARCH)) {
      $_zp_images = $_zp_current_search->getImages($all ? 0 : ($imagePage), $firstPageCount);
	} else {
      $_zp_images = $_zp_current_album->getImages($all ? 0 : ($imagePage), $firstPageCount, $sorttype);
	}
    if (empty($_zp_images)) { return false; }
    $_zp_current_image_restore = $_zp_current_image;
	if (in_context(ZP_SEARCH)) {
	  $img = array_shift($_zp_images); 
      $_zp_current_image = new Image(new Album($_zp_gallery, $img['folder']), $img['filename']);
	} else {
      $_zp_current_image = new Image($_zp_current_album, array_shift($_zp_images));
	}
    save_context();
    add_context(ZP_IMAGE);
    return true;
  } else if (empty($_zp_images)) {
    $_zp_images = NULL;
    $_zp_current_image = $_zp_current_image_restore;
    restore_context();
    return false;
  } else {
	if (in_context(ZP_SEARCH)) {
	  $img = array_shift($_zp_images); 
      $_zp_current_image = new Image(new Album($_zp_gallery, $img['folder']), $img['filename']);
	} else {
      $_zp_current_image = new Image($_zp_current_album, array_shift($_zp_images));
	}
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
    echo "<div id=\"imageTitle\" style=\"display: inline;\">" . htmlspecialchars(getImageTitle()) . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableTitle('imageTitle');</script>";
  } else {
    echo "<div id=\"imageTitle\" style=\"display: inline;\">" . htmlspecialchars(getImageTitle()) . "</div>\n";  
  }
}

// gets the n for n of m images	
  function imageNumber() {
    global $_zp_current_image, $_zp_current_search;
    $name = $_zp_current_image->getFileName();
	if (in_context(ZP_SEARCH)) {
	  $images = $_zp_current_search->getImages();
      $ct = count($images);
      for ($c = 0; $c < $ct; $c++) {
        if ($name == $images[$c] ['filename']) {
          return $c+1;
        }
	  }
	} else {
	  return $_zp_current_image->getIndex()+1;
    }
    return false;
  } 
  

// returns the image date in yyyy-mm-dd hh:mm:ss format
// pass it a date format string for custom formatting
  function getImageDate($format=null) {
    if(!in_context(ZP_IMAGE)) return false;
    global $_zp_current_image;
    $d = $_zp_current_image->getDateTime();
	if (empty($d) || ($d == '0000-00-00 00:00:00') ) { 
	  return false; 
	}
    if (is_null($format)) {
      return $d;
    }
  return date($format, strtotime($d));  
  }

function printImageDate($before="Date: ", $nonemessage="", $format="F jS, Y") {
  $date = getImageDate($format);
  if ($date) {
    echo $before . $date;
  } else {
    echo $nonemessage;
  }
}

// IPTC fields
function getImageLocation() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getLocation();
}

function getImageCity() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getcity();
}

function getImageState() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getState();
}

function getImageCountry() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getCountry();
}

//ZenVideo: Return video argument of an Image.
function getImageVideo() {
if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getVideo();
}

//ZenVideo: Return videoThumb argument of an Image.
function getImageVideoThumb() {
if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getVideoThumb();
}

function getImageDesc() { 
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return str_replace("\n", "<br />", $_zp_current_image->getDesc());
}

function printImageDesc($editable=false) {  
  global $_zp_current_image;
  if ($editable && zp_loggedin()) {
    echo "<div id=\"imageDesc\" style=\"display: block;\">" . getImageDesc() . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableDesc('imageDesc');</script>";
  } else {
    echo "<div id=\"imageDesc\" style=\"display: block;\">" . getImageDesc() . "</div>\n";
  }
}

/**
 * Get the unique ID of this image.
 */
function getImageID() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->id;
}

/**
 * Print the unique ID of this image.
 */
function printImageID() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  echo "image_".getImageID();
}

/**
 * Get the sort order of this image.
 */
function getImageSortOrder() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getSortOrder();
}

/**
 * Print the sort order of this image.
 */
function printImageSortOrder() {
  if (!in_context(ZP_IMAGE)) return false;
  echo getImageSortOrder();
}

function hasNextImage() { global $_zp_current_image; return $_zp_current_image->getNextImage(); }
function hasPrevImage() { global $_zp_current_image; return $_zp_current_image->getPrevImage(); }

function getNextImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  $nextimg = $_zp_current_image->getNextImage();
  return rewrite_path("/" . pathurlencode($_zp_current_album->name) . "/" . urlencode($nextimg->filename) . im_suffix(),
    "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($nextimg->filename));
}

function getPrevImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  $previmg = $_zp_current_image->getPrevImage();
  return rewrite_path("/" . pathurlencode($_zp_current_album->name) . "/" . urlencode($previmg->filename) . im_suffix(),
    "/index.php?album=" . urlencode($_zp_current_album->name) . "&image=" . urlencode($previmg->filename));
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
  global $_zp_current_image;
  return $_zp_current_image->getImageLink();
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

function getImageEXIFData() {
  global $_zp_current_image;
  return $_zp_current_image->getExifData();
}

/** Deprecated, name changed. */
function printImageEXIFData() { if (getImageVideo()) { } else { printImageMetadata(); } }

function printImageMetadata($title='Image Info', $toggle=true, $id='imagemetadata', $class=null) {
  global $_zp_exifvars;
  if (false === ($exif = getImageEXIFData())) { return; }
  $dataid = $id . '_data';
  echo "<div" . (($class) ? " class=\"$class\"" : "") . (($id) ? " id=\"$id\"" : "") . ">\n";
  if ($toggle) echo "<a href=\"javascript: toggle('$dataid');\">";
  echo "<strong>$title</strong>";
  if ($toggle) echo "</a>\n";
  echo "  <table id=\"$dataid\"" . ($toggle ? " style=\"display: none;\"" : '') . ">\n";
  foreach ($exif as $field => $value) {
    $display = $_zp_exifvars[$field][3];
    if ($display) {
      $label = $_zp_exifvars[$field][2];
      echo "    <tr><td>$label:</td> <td>$value</td></tr>\n";
    }
  }
  echo "  </table>\n</div>\n\n";
}

function printImageMap($zoomlevel='6'){
  global $_zp_phoogle;
  if(zp_conf('gmaps_apikey') != ''){
    $exif = getImageEXIFData();
    if(!empty($exif['EXIFGPSLatitude']) &&
       !empty($exif['EXIFGPSLongitude'])){
           
      $_zp_phoogle->setZoomLevel($zoomlevel);
      $lat = $exif['EXIFGPSLatitude'];
      $long = $exif['EXIFGPSLongitude'];
      if($exif['EXIFGPSLatitudeRef'] == 'S'){  $lat = '-' . $lat; }
      if($exif['EXIFGPSLongitudeRef'] == 'W'){  $long = '-' . $long; }
      $_zp_phoogle->addGeoPoint($lat, $long);
      $_zp_phoogle->showMap();
    }
  }
}

function hasMapData() {
  if(zp_conf('gmaps_apikey') != ''){
    $exif = getImageEXIFData();
    if(!empty($exif['EXIFGPSLatitude']) && !empty($exif['EXIFGPSLongitude'])){ 
	  return true;
    } 
  }
  return false;
}

function printAlbumMap($zoomlevel='8'){
  global $_zp_phoogle;
  if(zp_conf('gmaps_apikey') != ''){
    $foundLocation = false;
    $_zp_phoogle->setZoomLevel($zoomlevel);
    while (next_image(true)) {
      $exif = getImageEXIFData();
      if(!empty($exif['EXIFGPSLatitude']) &&
         !empty($exif['EXIFGPSLongitude'])){
        $foundLocation = true;
        $_zp_phoogle->setHeight(getDefaultHeight());
        $_zp_phoogle->setWidth(getDefaultWidth());
        $lat = $exif['EXIFGPSLatitude'];
        $long = $exif['EXIFGPSLongitude'];
        if($exif['EXIFGPSLatitudeRef'] == 'S'){  $lat = '-' . $lat; }
        if($exif['EXIFGPSLongitudeRef'] == 'W'){  $long = '-' . $long; }
        $infoHTML = '<a href="' . getImageLinkURL() . '"><img src="' .
          getImageThumb() . '" alt="' . getImageDesc() . '" ' .
          'style=" margin-left: 30%; margin-right: 10%; border: 0px; "/></a>' .
          '<p>' . getImageDesc() . '</p>';
        $_zp_phoogle->addGeoPoint($lat, $long, $infoHTML);
      }
    }
    if($foundLocation){ $_zp_phoogle->showMap(); }
  }
}


function getSizeCustomImage($size, $width=NULL, $height=NULL, $cw=NULL, $ch=NULL, $cx=NULL, $cy=NULL) {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  $h = $_zp_current_image->getHeight();
  $w = $_zp_current_image->getWidth();
  $ls = zp_conf('image_use_longest_side');
  $us = zp_conf('image_allow_upscale');
  
  $args = getImageParameters(array($size, $width, $height, $cw, $ch, $cx, $cy, null));
  @list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;
  
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
    if ($cw && $cw < $neww) $neww = $cw;
    if ($ch && $ch < $newh) $newh = $ch;
    if ($size && $ch && $cw) { $neww = $cw; $newh = $ch; }
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

function getDefaultSizedImage() { 
  global $_zp_current_image;
  return $_zp_current_image->getSizedImage(zp_conf('image_size'));
}

//ZenVideo: Show video player with video loaded or display the image.
function printDefaultSizedImage($alt, $class=NULL, $id=NULL) { 
  	  //Print videos
	  if(getImageVideo()) {
	  $ext = strtolower(strrchr(getFullImageURL(), "."));
	  if ($ext == ".flv") {
		//Player Embed...
	    echo '</a>
	    <p id="player"><a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.</p>
	    <script type="text/javascript">
		  var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER . '/flvplayer.swf","player","320","240","7");
		  so.addParam("allowfullscreen","true");
		  so.addVariable("file","' . getFullImageURL() . '&amp;title=' . getImageTitle() . '");
		  so.addVariable("displayheight","310");
		  so.write("player");
	    </script><a>';
	  }
	  elseif ($ext == ".3gp") {
	    echo '</a> 
		  <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="352" height="304" codebase="http://www.apple.com/qtactivex/qtplugin.cab"> 
		    <param name="src" value="' . getFullImageURL() . '"/> 
		    <param name="autoplay" value="false" />
		    <param name="type" value="video/quicktime" />
		    <param name="controller" value="true" />
		    <embed src="' . getFullImageURL() . '" width="352" height="304" autoplay="false" controller"true" type="video/quicktime"
		      pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
	        </object><a>';
		 }
		 elseif ($ext == ".mov") {
		   echo '</a> 
		     <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="640" height="496" codebase="http://www.apple.com/qtactivex/qtplugin.cab"> 
			   <param name="src" value="' . getFullImageURL() . '"/> 
			   <param name="autoplay" value="false" />
			   <param name="type" value="video/quicktime" />
			   <param name="controller" value="true" />
			   <embed src="' . getFullImageURL() . '" width="640" height="496" autoplay="false" controller"true" type="video/quicktime"
			     pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
			  </object><a>';
		  }
		  }
	  //Print images
	  else {
		echo "<img src=\"" . htmlspecialchars(getDefaultSizedImage()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    	" width=\"" . getDefaultWidth() . "\" height=\"" . getDefaultHeight() . "\"" .
    	(($class) ? " class=\"$class\"" : "") . 
    	(($id) ? " id=\"$id\"" : "") . " />";
	  }
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

//ZenVideo: Print normal video or custom sized images...
function printCustomSizedImage($alt, $size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=NULL, $class=NULL, $id=NULL) { 
  	//Print videos
	  if(getImageVideo()) {
	  $ext = strtolower(strrchr(getFullImageURL(), "."));
	  if ($ext == ".flv") {
		//Player Embed...
	    echo '</a>
	    <p id="player"><a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.</p>
	    <script type="text/javascript">
		  var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER . '/flvplayer.swf","player","320","240","7");
		  so.addParam("allowfullscreen","true");
		  so.addVariable("file","' . getFullImageURL() . '&amp;title=' . getImageTitle() . '");
		  so.addVariable("displayheight","310");
		  so.write("player");
	    </script><a>';
	  }
	  elseif ($ext == ".3gp") {
	    echo '</a> 
		  <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="352" height="304" codebase="http://www.apple.com/qtactivex/qtplugin.cab"> 
		    <param name="src" value="' . getFullImageURL() . '"/> 
		    <param name="autoplay" value="false" />
		    <param name="type" value="video/quicktime" />
		    <param name="controller" value="true" />
		    <embed src="' . getFullImageURL() . '" width="352" height="304" autoplay="false" controller"true" type="video/quicktime"
		      pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
	        </object><a>';
		 }
		 elseif ($ext == ".mov") {
		   echo '</a> 
		     <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="640" height="496" codebase="http://www.apple.com/qtactivex/qtplugin.cab"> 
			   <param name="src" value="' . getFullImageURL() . '"/> 
			   <param name="autoplay" value="false" />
			   <param name="type" value="video/quicktime" />
			   <param name="controller" value="true" />
			   <embed src="' . getFullImageURL() . '" width="640" height="496" autoplay="false" controller"true" type="video/quicktime"
			     pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
			  </object><a>';
		  }
		  }
	  //Print images
	  else {
		$sizearr = getSizeCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy);
  		echo "<img src=\"" . htmlspecialchars(getCustomImageURL($size, $width, $height, $cropw, $croph, $cropx, $cropy)) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    	" width=\"" . $sizearr[0] . "\" height=\"" . $sizearr[1] . "\"" .
    	(($class) ? " class=\"$class\"" : "") . 
    	(($id) ? " id=\"$id\"" : "") . " />";
	  }
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
    printLink(WEBPATH . '/' . ZENFOLDER . '/admin.php?page=editcomment&id=' . $_zp_current_comment['id'], $text, $title, $class, $id);
    echo $after;
  }
}

function printAlbumZip(){ 
	global $_zp_current_album; 
	echo'<a href="' . rewrite_path("/" . pathurlencode($_zp_current_album->name), 
		"/index.php?album=" . urlencode($_zp_current_album->name)) .  
		'?zipfile" title="Download Zip of the Album">Download a zip file ' . 
		'of this album</a>'; 
}

function printLatestComments($number) {
	echo '<div id="showlatestcomments">';
	echo '<ul>';
	$comments = query_full_array("SELECT c.id, i.title, i.filename, a.folder, a.title AS albumtitle, c.name, c.website,"
	  . " c.date, c.comment FROM ".prefix('comments')." AS c, ".prefix('images')." AS i, ".prefix('albums')." AS a "
	  . " WHERE c.imageid = i.id AND i.albumid = a.id ORDER BY c.id DESC LIMIT $number");
	foreach ($comments as $comment) {
	  $author = $comment['name'];
	  $album = $comment['folder'];
	  $image = $comment['filename'];
	  $albumtitle = $comment['albumtitle'];
		  if ($comment['title'] == "")  {
			$title = $image; 
		  }	else {
			$title = $comment['title'];
		  }	
	  $website = $comment['website'];
	  $comment = my_truncate_string($comment['comment'], 40);
	  
	  $link = $author.' commented on '.$albumtitle.' / '.$title ;
	  $short_link = my_truncate_string($link, 40);
	  
	  echo '<li><div class="commentmeta"><a href="';
	  
    	  if (zp_conf('mod_rewrite') == false) {
            echo WEBPATH.'/index.php?album='.urlencode($album).'&image='.urlencode($image).'/"';
          } else {
            echo WEBPATH.'/'.$album.'/'.$image.'" ';
          }
        
      echo 'title="'.$link.'">';
      echo $short_link.'</a>:</div><div class="commentbody">'.$comment.'</div></li>';	    
	}
	echo '</ul>';
	echo '</div>';	
}

function getImageStatistic($number, $option) {
  switch ($option) {
    case "popular":
      $sortorder = "images.hitcounter"; break;
    case "latest":
      $sortorder = "images.id"; break;
  }
  global $_zp_gallery;
  $imageArray = array();
  $images = query_full_array("SELECT images.albumid, images.filename AS filename, images.title AS title, albums.folder AS folder FROM " . 
            prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
      " WHERE images.albumid = albums.id AND images.show = 1" . 
      " AND albums.folder != ''".
      " ORDER BY ".$sortorder." DESC LIMIT $number");
  foreach ($images as $imagerow) {
    
    $filename = $imagerow['filename'];
    $albumfolder = $imagerow['folder'];
    
    $desc = $imagerow['title'];
    // Album is set as a reference, so we can't re-assign to the same variable!
    $image = new Image(new Album($_zp_gallery, $albumfolder), $filename);
    $imageArray [] = $image;
  }
  return $imageArray;
}

function printLatestImages($number=5) {
  $images = getImageStatistic($number, "latest");
  echo "\n<div id=\"latest_images\">\n";
  foreach ($images as $image) {
    $imageURL = getURL($image); 
    echo '<a href="' . $imageURL . '" title="' . $desc . "\">\n";
    echo '<img src="' . $image->getThumb() . "\"></a>\n";
  }
  echo "</div>\n";
}

function printPopularImages($number=5) {
  $images = getImageStatistic($number, "popular");
  echo "\n<div id=\"popular_images\">\n";
  foreach ($images as $image) {
    $imageURL = getURL($image); 
    echo '<a href="' . $imageURL . '" title="' . $desc . "\">\n";
    echo '<img src="' . $image->getThumb() . "\"></a>\n";
  }
  echo "</div>\n";
}

function getRandomImages() {
  $result = query_single_row('SELECT '.prefix('images').'.filename,'.prefix('images').'.title, '.prefix('albums').
                             '.folder FROM '.prefix('images').' INNER JOIN '.prefix('albums').
							 ' ON '.prefix('images').'.albumid = '.prefix('albums').'.id WHERE '.prefix('albums').'.folder!=""'.
							 ' ORDER BY RAND() LIMIT 1');
  $imageName = $result['filename'];
  if ($imageName =='') { return NULL; }
  $image = new Image(new Album(new Gallery(), $result['folder']), $imageName );
  return $image;
}

function printRandomImages($number, $class=null, $option="all") {
  if (!is_null($class)) {
	$class = 'class="' . $class . '";';
    echo "<ul".$class.">";
    for ($i=1; $i<=$number; $i++) {
      echo "<li>\n";
      switch($option) {
        case "all":
          $randomImage = getRandomImages(); break;
        case "album":
         $randomImage = getRandomImagesAlbum(); break;
      }
      $randomImageURL = getURL($randomImage); 
      echo '<a href="' . $randomImageURL . '" title="View image: ' . $randomImage->getTitle() . '">' .
      '<img src="' . $randomImage->getThumb() . 
      '" alt="'.$randomImage->getTitle().'"'; 
      echo "/></a></li>\n";
    }
    echo "</ul>";
  }
}

/* Returns an Image-object, randomly selected from current directory or any of it's subdirectories.
returns null if $_zp_current_album isn't set, and if no images can be found. You might want it to fall back to a
placeholder image instead. */
function getRandomImagesAlbum() {		
  $images = array();
  $subIDs = getAllSubAlbumIDs($rootAlbum);
  if($subIDs == null) {return null;}; //no subdirs avaliable
  foreach ($subIDs as $ID) {		
    $query = 'SELECT `id` , `albumid` , `filename` , `title` FROM '.prefix('images').' WHERE `albumid` = "'. $ID['id'] .'"'; 		
    $images = array_merge($images, query_full_array($query)); 	
  }
  if(count($images) < 1){return null;}; //no images avaliable in _any_ subdirectory
  $randomImage = $images[array_rand($images)];
  $folderPath = query_single_row("SELECT `folder` FROM " .prefix('albums'). " WHERE id = '" .$randomImage['albumid']. "'"  ); 	
  $image = new Image(new Album(new Gallery(), $folderPath['folder']), $randomImage['filename']);	
  return $image;
}

function my_truncate_string($string, $length) {
	if (strlen($string) > $length) {
		$short = substr($string, 0, $length);
		return $short. '...';
	} else {
		return $string;
	}
}

/*** tag functions   ***/
function getTags() {
  if(in_context(ZP_IMAGE)) {
    global $_zp_current_image;
    return $_zp_current_image->getTags();
  } else if (in_context(ZP_ALBUM)) {
    global $_zp_current_album;
    return $_zp_current_album->getTags();
  }
}

function printTags($option="",$preText=NULL,$class='taglist',$separator=", ",$editable=TRUE) {
  $tags = getTags();
  $singletag = explode(",", $tags);
  if (!empty($preText)) { $preText = "<strong>".$preText."</strong>"; }
  if ($editable && zp_loggedin()) {
    echo "<div id=\"tagContainer\">".$preText."<div id=\"imageTags\" style=\"display: inline;\">" . htmlspecialchars(getTags()) . "</div></div>\n";
    echo "<script type=\"text/javascript\">initEditableTags('imageTags');</script>";
  } else {
    echo "<ul class=\"".$class."\">\n";
    echo "<li>".$preText."</li>";
	$ct = count($singletag);
    for ($x = 0; $x < $ct; $x++) {
      if ($x === $ct - 1) { $separator = ""; }
      if ($option === "links") {
        $links1 = "<a href=\"".WEBPATH."/index.php?p=search&words=".$singletag[$x]."\" title=\"".$singletag[$x]."\">"; 
        $links2 = "</a>"; 
	  }
      echo "\t<li>".$links1.htmlspecialchars($singletag[$x]).$links2.$separator."</li>\n";
    }
    echo "</ul><br clear=all />\n";  
  }
}

function getAllTags() {
  $result = query_full_array("SELECT `tags` FROM ". prefix('images') ." WHERE `show` = 1");
  foreach($result as $row){
	$alltags = $alltags.$row['tags'].",";  // add comma after the last entry so that we can explode to array later
  }
  $result = query_full_array("SELECT `tags` FROM ". prefix('albums') ." WHERE `show` = 1");
  foreach($result as $row){
	$alltags = $alltags.$row['tags'].",";  // add comma after the last entry so that we can explode to array later
  } 
  $alltags = explode(",",$alltags);
  $cleantags = array();
  foreach ($alltags as $tag) {
    $clean = strtolower(trim($tag));
    if (!empty($clean)) {
      $cleantags[] = $clean;
    }
  }
  $tagcount = array_count_values($cleantags);
  return $tagcount;
}

function printAllTagsAs($option,$class="",$sort="abc",$counter=FALSE,$links=TRUE,$maxfontsize="2",$maxcount="50",$mincount="10") {
//$option = "Cloud" for tag cloud, "list" for simple list
//$class = CSS class
//$sort = "results" for relevance list, "abc" for alphabetical, blank for unsorted
//$counter = TRUE if you want the tag count within brackets behind the tag
//$links = false for no links
//$maxfontsize = Maximum font size to use in the tag cloud
//$maxtagcout = Maximum result value to be used
//$mintagcount = the lowest result value to be used for the cloud
  define('MINFONTSIZE', 0.8);
  $option = strtolower($option);
  if ($class != "") { $class = "class=\"".$class."\""; }
  $tagcount = getAllTags();
  if (!is_array($tagcount)) { return false; }
  switch($sort) {
   	case "results":
   	  arsort($tagcount); break;
   	case "abc":
   	  ksort($tagcount); break;
  } 	  
  echo "<ul style=\"display:inline; list-style-type:none\" ".$class.">\n";
  
  while (list($key, $val) = each($tagcount)) {  
    if(!$counter) { 
	  $counter = ""; 
	} else { 
	  $counter = " (".$val.") "; 
	}
          
    if ($option == "cloud") {      
      if ($val <= $mincount) { 
        $size = MINFONTSIZE;  // calculate font sizes, formula from wikipedia
      } else { 
	    $size =min(max(round(($maxfontsize*($val-$mincount))/($maxcount-$mincount), 2), MINFONTSIZE), $maxfontsize); 
      }    
    } else {
	  $size = MINFONTSIZE;
	}
   	
	if ($val >= $mincount) {
      if(!$links) {
        echo "\t<li style=\"font-size:".$size."em\">".$key.$counter."</li>\n";  
	  } else { 
	    $key = str_replace('"', '', $key);
        echo "\t<li style=\"display:inline; list-style-type:none\"><a href=\"".WEBPATH.
	         "/index.php?p=search&words=".$key."\" style=\"font-size:".$size."em;\">".
		     $key.$counter."</a></li>\n";  
	  }
	}
         
  } // while end 
  echo "</ul>\n";
}

function getAllDates() {
  $alldates = array();
  $result = query_full_array("SELECT `date` FROM ". prefix('images') ." WHERE `show` = 1");
  foreach($result as $row){
	$alldates[] = $row['date']; 
  }
  foreach ($alldates as $adate) {
    if (!empty($adate)) {
     $cleandates[] = substr($adate, 0, 7) . "-01";;
    }
  }
  $datecount = array_count_values($cleandates);
  ksort($datecount);
  return $datecount;
}

function printAllDates($class="archive", $yearid="year", $monthid="month") {
  if (!empty($class)){ $class = "class=\"$class\""; }
  if (!empty($yearid)){ $yearid = "id=\"$yearid\""; }
  if (!empty($monthid)){ $monthid = "id=\"$monthid\""; }
  $datecount = getAllDates();
  $lastyear = "";
  echo "\n<ul $class>\n";
  while (list($key, $val) = each($datecount)) {  
    $nr++;
    if ($key == '0000-00-01') { 
	  $year = "no date";
	  $month = "";
    } else {	  
      $dt = date('Y-F', strtotime($key));
	  $year = substr($dt, 0, 4);
	  $month = substr($dt, 5);
	}
	  
	if ($lastyear != $year) {
	  $lastyear = $year;	
	  if($nr != 1) {  echo "</ul>\n</li>\n";}
      echo "<li $yearid>$year\n<ul $monthid>\n";
	}
	echo "<li><a href=\"index.php?p=search&date=".substr($key, 0, 7)."\">$month ($val)</a></li>\n";
  }
echo "</ul>\n</li>\n</ul>\n";
}

function getCustomPageURL($page, $q="") {
  if (zp_conf('mod_rewrite')) {
    $result .= WEBPATH."/page/$page";
	if (!empty($q)) { $result .= "?$q"; }
  } else {
    $result .= WEBPATH."index.php?p=$page";
	if (!empty($q)) { $result .= "&$q"; }
  }
  return $result;
}

function printCustomPageURL($linktext, $page, $q="", $prev, $next, $class) {
  if (!is_null($class)) {
	$class = 'class="' . $class . '";';
  }
  echo $prev."<a href=\"".getCustomPageURL($page, $q)." $class \">$linktext</a>".$next;
}

function getURL($image) {
if (zp_conf('mod_rewrite')) {
  return WEBPATH . "/" . pathurlencode($image->getAlbumName()) . "/" . urlencode($image->name);
  } else {
  return WEBPATH . "/index.php?album=" . pathurlencode($image->getAlbumName()) . "&image=" . urlencode($image->name);
  }
}

function getAlbumId() {
  global $_zp_current_album;
  if (!isset($_zp_current_album)) { return null; }
    return $_zp_current_album->getAlbumId();
}

/* Returns the ID of all sub-albums, relative to the current album. If $_zp_current_album is not set, it'll return null. */
function getAllSubAlbumIDs($albumfolder='') {	
  global $_zp_current_album;
  if (empty($albumfolder)) {
    if (isset($_zp_current_album)) { 
	  $albumfolder = $_zp_current_album->getFolder();
	} else {
	  return null; 
	}	
  }
  $query = "SELECT `id` FROM " . prefix('albums') . " WHERE `folder` LIKE '" . $albumfolder . "%'"; 
  $subIDs = query_full_array($query);
  return $subIDs; 
}

function hitcounter() {
  $id = getImageID(); 
  $result = query_single_row("SELECT hitcounter FROM ". prefix('images') ." WHERE id = $id");
  $resultupdate = $result['hitcounter']+1;
  echo $resultupdate;
  $result2 = query_single_row("UPDATE ". prefix('images') ." SET `hitcounter`= $resultupdate WHERE id = $id");
}

/*** RSS Functions **********************/
/******************************************/
function printRSSLink($option, $prev, $linktext, $next, $printIcon=true, $class=null) {
  if ($printIcon) {
    $icon = ' <img src="' . FULLWEBPATH . '/' . ZENFOLDER . '/images/rss.gif" />';
	} else {
    $icon = '';
	}
	if (!is_null($class)) {
    $class = 'class="' . $class . '";';
	}
	switch($option) {
		case "Gallery":
			echo $prev."<a $class href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php\">".$linktext."$icon</a>".$next;
			break;
		case "Album":
			echo $prev."<a $class href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php?albumnr=".getIDforAlbum()."&albumname=".getAlbumTitle()."\">".$linktext."$icon</a>".$next;
			break;
		case "Comments":
			echo $prev."<a $class href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss-comments.php\">".$linktext."$icon</a>".$next;
			break;
	}
}

function printRSSHeaderLink($option, $linktext) {
	switch($option) {
		case "Gallery":
			echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".$linktext."\" href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php\" />";
			break;
		case "Album":
			echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".$linktext."\" href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php?albumnr=".getIDforAlbum()."&albumname=".getAlbumTitle()."\" />";
			break;
		case "Comments":
			echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".$linktext."\" href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss-comments.php\" />";
			break;
	}
}


/*** Search functions *******************************************************
based on the search engine tutorial from PHPFreaks.com (dissapeared strangely)
modified for zenphoto by Malte M�ller (acrylian)
****************************************************************************/
function printSearchForm($prevtext=NULL) { 
  $searchwords= (isset($_POST['words']) ? htmlspecialchars(stripslashes($_REQUEST['words'])) : ''); 
  echo "\n<div id=\"search\">\n";
  echo "<form method=\"POST\" action=\"".WEBPATH."/index.php?p=search\" id=\"search_form\">"; 
  echo $prevtext."<input type=\"text\" name=\"words\" value=\"".$searchwords."\" id=\"search_input\" size=\"10\" />\n"; 
  echo "<input type=\"submit\" value=\"Search\" class=\"pushbutton\" id=\"search_submit\">\n"; 
  echo "</form>\n"; 
  echo "</div>\n";
} 

function getSearchWords($separator=" | ") {
  if (in_context(ZP_SEARCH)) {
    global $_zp_current_search;
	$tags = $_zp_current_search->getSearchString();
    return implode($separator, $tags);
  }
  return false;
}

function getSearchDate($format="F Y") {
  if (in_context(ZP_SEARCH)) {
    global $_zp_current_search;
    $date = $_zp_current_search->getSearchDate();
	if (empty($date)) { return ""; }
	if ($date == '0000-00') { return "no date"; };
	$dt = strtotime($date."-01");
	return date($format, $dt);
  }
  return false;
}

/*** Option handling functions ************************/
/******************************************************/
function setOption($key, $value, $persistent=true) {
  global $_zp_gallery;
  return $_zp_gallery->setOption($key, $value, $persistent);
}

function getOption($key) {
  global $_zp_gallery;
  return $_zp_gallery->getOption($key);
}

function setOptionDefault($key, $default, $desc, $bool=false) {
  global $_zp_gallery;
  return $_zp_gallery->setOptionDefault($key, $default, $desc, $bool);
}  
/*** Open for Comments **********************************
  called with one parameter which specifies the degeree of control desired
  IMAGE will return the image level setting
  ALBUM will return the album level setting
  the Default, IMAGE+ALBUM will return the AND of the two levels
 ********************************************************/
define("IMAGE", 1);
define("ALBUM", 2);
function OpenedForComments($what=3) {
  global $_zp_current_image;
  $result = true;
  if (IMAGE & $what) { $result = $result && $_zp_current_image->getCommentsAllowed(); }
  if (ALBUM & $what) { $result = $result && $_zp_current_image->album->getCommentsAllowed(); }
  return $result;
}


/*** getTheme *****************************
 * finds the name of the themeColor option selected on the admin options tab
 */

function getTheme(&$zenCSS, &$themeColor, $defaultColor) {
  global $_zp_themeroot;
  $themeColor = getOption('Theme_colors');
  $zenCSS = $_zp_themeroot . '/styles/' . $themeColor . '.css';
  $unzenCSS = str_replace(WEBPATH, '', $zenCSS);
  if (!file_exists(SERVERPATH . $unzenCSS)) {
    $zenCSS = $_zp_themeroot. "/styles/" . $defaultColor . ".css";
    return ($themeColor == '');
  } else {
    return true;
  }
}

/*** normalizeColumns **************************
  * passed # of album columns, # of image columns of the theme
  * returns # of images that will go on the album/image transition page
  * Updates (non-persistent) images_per_page and albums_per_page so that the rows are filled
    */
function normalizeColumns($albumColumns, $imageColumns) {
  $albcount = getOption('albums_per_page');
  if (($albcount % $albumColumns) != 0) {  
    setOption('albums_per_page', $albcount = ((floor($albcount / $albumColumns) + 1) * $albumColumns), false);  
  }
  $imgcount = getOption('images_per_page');
  if (($imgcount % $imageColumns) != 0) {  
    setOption('images_per_page', $imgcount = ((floor($imgcount / $imageColumns) + 1) * $imageColumns - $imageReduction), false);  
  }
  if (in_context(ZP_ALBUM | ZP_SEARCH)) {
    if (in_context(ZP_SEARCH)) {
	  $count = getNumAlbums();
	} else {
      $count = GetNumSubalbums();
	}
	if ($count == 0) {
	  return 0; 
	}
    $rowssused = ceil(($count % $albcount) / $albumColumns);     /* number of album rows unused */
    $leftover = floor(getOption('images_per_page') / $imageColumns) - $rowssused;
    $firstPageImages = $leftover * $imageColumns;  /* number of images that fill the leftover rows */
	if ($firstPageImages == $imgcount) {
	  return 0;
	} else {
      return $firstPageImages;
	}
  }
  return false;
}

/*** End template functions ***/

?>