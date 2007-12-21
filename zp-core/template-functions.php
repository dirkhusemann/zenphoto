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
  echo getOption('version');
}

/** 
 * Prints a link to administration if the current user is logged-in 
 */
function printAdminLink($text, $before='', $after='', $title=NULL, $class=NULL, $id=NULL) {
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH.'/' . ZENFOLDER . '/admin.php', $text, $title, $class, $id);
    echo $after;
    return true;
  }
  return false;
}

/**
 * prints the admin edit link for subalbums
 * @param string $text text for the link
 * @param string $before text do display before the link
 * @param string $after  text do display after the link
 * @return string with all the html for the admin toolbox
 * @since 1.1
 */
function printSubalbumAdmin($text, $before='', $after='') {
  global $_zp_current_album, $_zp_themeroot;
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH.'/' . ZENFOLDER . '/admin.php?page=edit&album=' . urlencode($_zp_current_album->name), $text, NULL, NULL, NULL); 
    echo $after;
   }
}

/**
 * prints the clickable drop down toolbox on any theme page with generic admin helpers
 * @param string $context index, album, image or search
 * @param string $id the html/css theming id
 * @return string with all the html for the admin toolbox
 * @since 1.1
 */
function printAdminToolbox($context=null, $id="admin") {
  global $_zp_current_album, $_zp_current_image, $_zp_current_search;
  if (zp_loggedin()) {
    $zf = WEBPATH."/".ZENFOLDER;
    $dataid = $id . '_data';
	$page = getCurrentPage();
	$redirect = '';
    echo "\n<script type=\"text/javascript\" src=\"".$zf."/js/admin.js\"></script>\n";
    if (is_null($context)) { $context = get_context(); }
    echo '<div id="' .$id. '">'."\n".'<a href="javascript: toggle('. "'" .$dataid."'".');"><h3>Admin Toolbox</h3></a>'."\n"."\n</div>"; 
    echo '<div id="' .$dataid. '" style="display: none;">'."\n"; 
    printAdminLink('Admin', '', "<br />\n"); 
    if ($context === ZP_INDEX) {
      printSortableGalleryLink('Sort gallery', 'Manual sorting');
      echo "<br />\n";
      printLink($zf . '/admin.php?page=upload' . urlencode($_zp_current_album->name), "New album", NULL, NULL, NULL); 
      echo "<br />\n";
	  if (isset($_GET['p'])) {
	    $redirect = "&p=" . $_GET['p'];
	  }
	  $redirect .= "&page=$page";
    } else if (!in_context(ZP_IMAGE | ZP_SEARCH)) {  // then it must be an album page
      printSubalbumAdmin('Edit album', '', "<br />\n");
      printSortableAlbumLink('Sort album', 'Manual sorting');
      echo "<br />\n";
	  $albumname = urlencode($_zp_current_album->name);
      printLink($zf . '/admin.php?page=upload&album=' . $albumname, "Upload Here", NULL, NULL, NULL); 
      echo "<br />\n";
      printLink($zf . '/admin.php?page=upload&new&album=' . $albumname, "New Album Here", NULL, NULL, NULL); 
      echo "<br />\n";
	  echo "<a href=\"javascript: confirmDeleteAlbum('".$zf."/admin.php?page=edit&action=deletealbum&album=" .
	        queryEncode($_zp_current_album->name) . "');\" title=\"Delete the album\">Delete album</a><br />\n";
	  $redirect = "&album=$albumname&page=$page";
    } else if (in_context(ZP_IMAGE)) {
	  $albumname = urlencode($_zp_current_album->name);
	  $imagename = queryEncode($_zp_current_image->filename);
      echo "<a href=\"javascript: confirmDeleteImage('".$zf."/admin.php?page=edit&action=deleteimage&album=" .
	       $albumname . "&image=". $imagename . "');\" title=\"Delete the image\">Delete image</a>";  
	  echo "<br />\n";
	  $redirect = "&album=$albumname&image=$imagename";
	} else if (in_context(ZP_SEARCH)) {
	  $redirect = "&p=search" . $_zp_current_search->getSearchParams() . "&page=$page";
	}
	
    echo "<a href=\"".$zf."/admin.php?logout$redirect\">Logout</a>\n";
    echo "</div>\n"; 
  }
}

/**  
 * Print any Javascript required by zenphoto. Every theme should include this somewhere in its <head>. 
 */
function zenJavascript() {
  global $_zp_phoogle;
  if(getOption('gmaps_apikey') != ''){$_zp_phoogle->printGoogleJS();}
  if (zp_loggedin()) {
    echo "  <script type=\"text/javascript\" src=\"" . WEBPATH . "/" . ZENFOLDER . "/js/ajax.js\"></script>\n";
    echo "  <script type=\"text/javascript\">\n";
    sajax_show_javascript();
    echo "  </script>\n";
  }
  echo "  <script type=\"text/javascript\" src=\"" . WEBPATH . "/" . ZENFOLDER . "/js/scripts-common.js\"></script>\n";
  if (in_context(ZP_IMAGE)) {
    echo "  <script type=\"text/javascript\" src=\"" . WEBPATH . "/" . ZENFOLDER . "/js/flvplayer.js\"></script>\n";
  }
}

/*** Gallery Index (album list) Context ***/
/******************************************/

function getGalleryTitle() { 
  return getOption('gallery_title');
}
function printGalleryTitle() { 
  echo getGalleryTitle(); 
}

function getMainSiteName() { 
  return getOption('website_title');
}
function getMainSiteURL() { 
  return getOption('website_url');
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

/**
 * WHILE next_album(): context switches to Album.
 * If we're already in the album context, this is a sub-albums loop, which,
 * quite simply, changes the source of the album list.
 * Switch back to the previous context when there are no more albums.
 * @param bit $all true to go through all the albums
 * @param string $sorttype what you want to sort the albums by
 * @return true if there is albums, false if none
 * @since 0.6
 */
function next_album($all=false, $sorttype=null) {
  global $_zp_albums, $_zp_gallery, $_zp_current_album, $_zp_page, $_zp_current_album_restore, $_zp_current_search;
  if (checkforPassword()) { return false; }
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

function getNumSubalbums() {
  global $_zp_current_album;
  return count($_zp_current_album->getSubalbums());
}

function getTotalPages($oneImagePage=false) { 
  global $_zp_gallery;
  if (in_context(ZP_ALBUM | ZP_SEARCH)) {
    if (in_context(ZP_SEARCH)) {
      $pageCount = ceil(getNumAlbums() / getOption('albums_per_page'));
    } else {
      $pageCount = ceil(getNumSubalbums() / getOption('albums_per_page'));
    }	
    $imageCount = getNumImages();  
    if ($oneImagePage) {
      $imageCount = min(1, $imageCount);
    }
    $pageCount = ($pageCount + ceil(($imageCount - getOption('images_first_page')) / getOption('images_per_page')));
    return $pageCount;
  } else if (in_context(ZP_INDEX)) {
    return ceil($_zp_gallery->getNumAlbums() / getOption('albums_per_page'));
  } else {
    return null;
  }
}

function getPageURL_($page, $total) {
  global $_zp_current_album, $_zp_gallery, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    $searchwords = $_zp_current_search->words;
	$searchdate = $_zp_current_search->getSearchDate();
	$searchfields = $_zp_current_search->getQueryFields();
	$searchpagepath = getSearchURL($searchwords, $searchdate, $searchfields).(($page > 1) ? "&page=" . $page : "") ;
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
  $total = getTotalPages($oneImagePage);
  if ($total < 2) { 
    $class .= ' disabled_nav';
  }
  echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
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
  global $_zp_current_album;
  if (!$_zp_current_album->getShow()) { 
    $class .= " not_visible"; 
  } else {
  $pwd = $_zp_current_album->getPassword();
    if (zp_loggedin() && !empty($pwd)) {
      $class .= " password_protected";
    }
  }
  $class = trim($class);
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
  global $_zp_current_album;
  if (!$_zp_current_album->getShow()) { 
    $class .= " not_visible"; 
  } else {
  $pwd = $_zp_current_album->getPassword();
    if (zp_loggedin() && !empty($pwd)) {
      $class .= " password_protected";
    }
  }
  $class = trim($class);
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
  global $_zp_current_album, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    $nextalbum = $_zp_current_search->getNextAlbum();
  } else if (in_context(ZP_ALBUM)) {
    $nextalbum = $_zp_current_album->getNextAlbum();
  } else {
    return false;
  }
  return rewrite_path("/" . pathurlencode($nextalbum->name),
    "/index.php?album=" . urlencode($nextalbum->name));
}

function getPrevAlbumURL() {
  global $_zp_current_album, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    $prevalbum = $_zp_current_search->getPrevAlbum();
  } else if(in_context(ZP_ALBUM)) {
    $prevalbum = $_zp_current_album->getPrevAlbum();
  } else {
    return false;
  }
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
    $pageCount = Ceil(getNumAlbums() / getOption('albums_per_page'));
  } else {
    $pageCount = Ceil(getNumSubalbums() / getOption('albums_per_page'));
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
/**
 * returns the next image on a page.
 * sets $_zp_current_image to the next image in the album.
 *@param bool $param1 set to true disable pagination
 *@paaram int $param2 the number of images which can go on the page that transitions between albums and images
 *@param string $param3 overrides the default sort type
 *@param bool overrides the password chedk
 *@return true if there is an image to be shown
 */
function next_image($all=false, $firstPageCount=0, $sorttype=null, $overridePassword=false) { 
  global $_zp_images, $_zp_current_image, $_zp_current_album, $_zp_page, $_zp_current_image_restore, 
         $_zp_conf_vars, $_zp_current_search, $_zp_gallery;
		 
  if (!$overridePassword) { if (checkforPassword()) { return false; } }
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
  $desc = str_replace("\r\n", "\n", $_zp_current_image->getDesc());
  return str_replace("\n", "<br/>", $desc);
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
// function to print any Image Data

function getImageData($field) {
 if(!in_context(ZP_IMAGE)) return false;
 global $_zp_current_image;
 switch ($field) {
   	case "location":
		return $_zp_current_image->getLocation();
		break;
	case "city":
		return $_zp_current_image->getCity();
		break;
	case "state":
		return $_zp_current_image->getState();
		break;
    case "country":
		return $_zp_current_image->getContry();
		break;
	case "credit":
		return $_zp_current_image->getCredit();
		break;
	case "copyright":
 		return $_zp_current_image->getCopyright();
 		break;
  }
}
 
function printImageData($field, $label) {
  global $_zp_current_image;
  if(getImageData($field)) { // only print it if there's something there 
  	echo "<p class=\"metadata\"><strong>" . $label . "</strong> " . getImageData($field) . "</p>\n";
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
  return rewrite_path("/" . pathurlencode($nextimg->album->name) . "/" . urlencode($nextimg->filename) . im_suffix(),
    "/index.php?album=" . urlencode($nextimg->album->name) . "&image=" . urlencode($nextimg->filename));
}

function getPrevImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  $previmg = $_zp_current_image->getPrevImage();
  return rewrite_path("/" . pathurlencode($previmg->album->name) . "/" . urlencode($previmg->filename) . im_suffix(),
    "/index.php?album=" . urlencode($previmg->album->name) . "&image=" . urlencode($previmg->filename));
}


function printPreloadScript() {
  global $_zp_current_image;
  $size = getOption('image_size');
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
      echo "    <tr><td align=\"right\">$label: </td> <td><strong>&nbsp;&nbsp;$value</strong></td></tr>\n";
    }
  }
  echo "  </table>\n</div>\n\n";
}
/**
 * Causes a Google map to be printed based on the gps data in the current image
 *@param  string $zoomlevel the zoom in for the map
 *@param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 *@param int $width is the image width of the map. NULL will use the default
  *@param int $height is the image height of the map. NULL will use the default
  *@return nothing
  *@since 1.1.3
*/
function printImageMap($zoomlevel='6', $type=NULL, $width=NULL, $height=NULL){
  global $_zp_phoogle;
  if(getOption('gmaps_apikey') != ''){
    $exif = getImageEXIFData();
    if(!empty($exif['EXIFGPSLatitude']) &&
       !empty($exif['EXIFGPSLongitude'])){
           
      $_zp_phoogle->setZoomLevel($zoomlevel);
	  if (!is_null($width)) { $_zp_phoogle->setWidth($width); }
	  if (!is_null($height)) { $_zp_phoogle->setHeight($height); }
	  if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
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
  if(getOption('gmaps_apikey') != ''){
    $exif = getImageEXIFData();
    if(!empty($exif['EXIFGPSLatitude']) && !empty($exif['EXIFGPSLongitude'])){ 
	  return true;
    } 
  }
  return false;
}

/**
 * Causes a Google map to be printed based on the gps data in all the images in the album
 *@param  string $zoomlevel the zoom in for the map
 *@param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 *@param int $width is the image width of the map. NULL will use the default
  *@param int $height is the image height of the map. NULL will use the default
  *@return nothing
  *@since 1.1.3
*/
function printAlbumMap($zoomlevel='8', $type=NULL, $width=NULL, $height=NULL){
  global $_zp_phoogle;
  if(getOption('gmaps_apikey') != ''){
    $foundLocation = false;
    $_zp_phoogle->setZoomLevel($zoomlevel);
	if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
	if (!is_null($width)) { $_zp_phoogle->setWidth($width); }
	if (!is_null($height)) { $_zp_phoogle->setHeight($height); }
    while (next_image(true)) {
      $exif = getImageEXIFData();
      if(!empty($exif['EXIFGPSLatitude']) &&
         !empty($exif['EXIFGPSLongitude'])){
        $foundLocation = true;
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
  $ls = getOption('image_use_longest_side');
  $us = getOption('image_allow_upscale');
  
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
  return getSizeCustomImage(getOption('image_size'));
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
  return $_zp_current_image->getSizedImage(getOption('image_size'));
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
  global $_zp_current_album, $_zp_current_image;
  if (!$_zp_current_image->getShow()) { 
    $class .= " not_visible"; 
  } else {
  $pwd = $_zp_current_album->getPassword();
    if (zp_loggedin() && !empty($pwd)) {
      $class .= " password_protected";
    }
  }
  $class = trim($class);
  echo "<img src=\"" . htmlspecialchars(getImageThumb()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    ((getOption('thumb_crop')) ? " width=\"".getOption('thumb_crop_width')."\" height=\"".getOption('thumb_crop_height')."\"" : "") .
    (($class) ? " class=\"$class\"" : "") . 
    (($id) ? " id=\"$id\"" : "") . " />";
}

function getFullImageURL() {
  global $_zp_current_image;
  return $_zp_current_image->getFullImage();
}
/**
* returns a password protected/watermarked url to the image
**/
function getProtectedImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  $path = $_zp_current_image->getImageLink();
  if (getOption('mod_rewrite')) {
    $path .= "?p=*full-image";
  } else {
    $path .= "&p=*full-image";
  }
  return $path;
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
  global $_zp_current_album, $_zp_current_image;
  if (!$_zp_current_album->getShow()) { 
    $class .= " not_visible"; 
  } else {
  $pwd = $_zp_current_image->getPassword();
    if (zp_loggedin() && !empty($pwd)) {
      $class .= " password_protected";
    }
  }
  $class = trim($class);
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

function printCustomSizedImageMaxHeight($maxheight) {
  if (getFullWidth() === getFullHeight() OR getDefaultHeight() > $maxheight) {
    printCustomSizedImage(getImageTitle(), null, null, $maxheight, null, null, null, null, null, null);
  } else {
  printDefaultSizedImage(getImageTitle());
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

function printCommentErrors($class = 'error') {
  global $error;
  if (isset($error)) { 
    echo "<div class=$class>";
    if ($error == 1) {
      echo "There was an error submitting your comment. Name, a valid e-mail address, and a spam-free comment are required.";
    } else {
      echo "Your comment has been marked for moderation.";
    }
    echo "</div>";
  } 
  return $error;
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
	  
    	  if (getOption('mod_rewrite') == false) {
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

/**
 * increments the hitcounter
 * @param string $option "image" for image hit counter (default), "album" for album hit counter
 * @param bool $view set to true if you don't want to increment the counter.
 * @return string  hitcount
 * @since 1.1.3
 */
function hitcounter($option="image", $viewonly=false) {
  switch($option) {
		case "image":
			$id = getImageID(); 
			$dbtable = prefix('images');
			$doUpdate = true;
		break;
		case "album":
			$id = getAlbumID(); 
			$dbtable = prefix('albums');
			$doUpdate = getCurrentPage() == 1; // only count initial page for a hit on an album
		break;
  }
  if (zp_loggedin() || $viewonly) { $doUpdate = false; }
  $sql = "SELECT `hitcounter` FROM $dbtable WHERE `id` = $id";
  if ($doUpdate) { $sql .= " FOR UPDATE"; }
  $result = query_single_row($sql);
  $resultupdate = $result['hitcounter'];
  if ($doUpdate) {
    $resultupdate++;
    query("UPDATE $dbtable SET `hitcounter`= $resultupdate WHERE `id` = $id");
  }
  return $resultupdate;
}

/**
 * grabs album statistic accordingly to $option
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular albums, "latest" for the latest uploaded
 * @return string with albums
 */
function getAlbumStatistic($number=5, $option) {
  if (zp_loggedin()) {
    $albumWhere = "";
  } else {
    $albumWhere = "WHERE `show`=1 AND `password`=''";
  }
  switch($option) { 
    case "popular": 
    	$sortorder = "hitcounter";
    break;
    case "latest": 
    	$sortorder = "id";
    break; 
  }
	$albums = query("SELECT id, title, folder, thumb FROM " . prefix('albums') . $albumWhere . " ORDER BY ".$sortorder." DESC LIMIT $number");
	return $albums;
}

/**
 * prints album statistic according to $option
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular albums, "latest" for the latest uploaded
 * @return string with albums
 */
function printAlbumStatistic($number, $option) {
	$albums = getAlbumStatistic($number, $option);
  echo "\n<div id=\"$option_albums\">\n";
  if (getOption('mod_rewrite')) 
		{ $albumlinkpath = WEBPATH."/"; 
  } else { 
    $albumlinkpath = "index.php?album="; 
  } 
  while ($album = mysql_fetch_array($albums)) {
    if ($album['thumb'] === NULL)
      { $image = query_single_row("SELECT * FROM " . prefix('images') . " WHERE albumid = ".$album['id']." ORDER BY sort_order DESC LIMIT 1");
    		$albumthumb = $image['filename'];
    } else {
    	$albumthumb = $album['thumb'];
    }
    echo "<a href=\"".$albumlinkpath.$album['folder']."\" title=\"" . $album['title'] . "\">\n";
    echo "<img src=\"".$albumlinkpath.$album['folder']."/image/thumb/".$albumthumb."\"></a>\n";
  }
  echo "</div>\n";
}

/**
 * prints the most popular albums
 * @param string $number the number of albums to get
 */
function printPopularAlbums($number=5) {
  printAlbumStatistic($number,"popular"); 
}

/**
 * prints the latest albums
 * @param string $number the number of albums to get
 */
function printLatestAlbums($number=5) {
  printAlbumStatistic($number,"latest"); 
}

/**
 * grabs image statistic according to $option
 * @param string $number the number of images to get
 * @param string $option "popular" for the most popular images, "latest" for the latest uploaded
 * @return string with images
 */
function getImageStatistic($number, $option) {
  global $_zp_gallery;
  if (zp_loggedin()) {
    $albumWhere = "";
    $imageWhere = "";
  } else {
    $albumWhere = " AND albums.show=1 AND albums.password=''";
    $imageWhere = " AND images.show=1";
  }
  switch ($option) {
    case "popular":
      $sortorder = "images.hitcounter"; break;
    case "latest":
      $sortorder = "images.id"; break;
    case "mostrated":
      $sortorder = "images.total_votes"; break;
    case "toprated":
      $sortorder = "(total_value/total_votes)"; break;
  }
  $imageArray = array();
  $images = query_full_array("SELECT images.albumid, images.filename AS filename, images.title AS title, " .
                             "albums.folder AS folder, images.show, albums.show, albums.password FROM " . 
                              prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
                              " WHERE images.albumid = albums.id " . $imageWhere . $albumWhere .
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

/**
 * prints image statistic according to $option
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular images, "latest" for the latest uploaded
 * @return string with images
 */
function printImageStatistic($number, $option) {
  $images = getImageStatistic($number, $option);
  echo "\n<div id=\"$option_images\">\n";
  foreach ($images as $image) {
    $imageURL = getURL($image); 
    echo '<a href="' . $imageURL . '" title="' . htmlspecialchars($image->getTitle(), ENT_QUOTES) . "\">\n";
    echo '<img src="' . $image->getThumb() . "\"></a>\n";
  }
  echo "</div>\n";
}

/**
 * prints the most popular images
 * @param string $number the number of images to get
 */
function printPopularImages($number=5) {
printImageStatistic($number, "popular");
}

/**
 * prints the latest images
 * @param string $number the number of images to get
 */
function printLatestImages($number=5) {
printImageStatistic($number, "latest");
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
  $query = "SELECT `id` FROM " . prefix('albums') . " WHERE `folder` LIKE '" . mysql_real_escape_string($albumfolder) . "%'"; 
  $subIDs = query_full_array($query);
  return $subIDs; 
}

function getRandomImages() {
  if (zp_loggedin()) {
    $albumWhere = '';
    $imageWhere = '';
  } else {
    $albumWhere = " AND ".prefix('albums') . ".show=1 AND " . prefix('albums') . ".password=''";
    $imageWhere = " AND " . prefix('images') . ".show=1";
  }
  $result = query_single_row('SELECT '.prefix('images').'.filename,'.prefix('images').'.title, '.prefix('albums').
                             '.folder, ' . prefix('images') . '.show, ' . prefix('albums') . '.show, ' . prefix('albums') . '.password '.
                             'FROM '.prefix('images'). ' INNER JOIN '.prefix('albums').
							 ' ON '.prefix('images').'.albumid = '.prefix('albums').'.id WHERE '.prefix('albums').'.folder!=""'.
							 $albumWhere . $imageWhere . ' ORDER BY RAND() LIMIT 1');
  $imageName = $result['filename'];
  if ($imageName =='') { return NULL; }
  $image = new Image(new Album(new Gallery(), $result['folder']), $imageName );
  return $image;
}

/* Returns an Image-object, randomly selected from current directory or any of it's subdirectories.
returns null if $_zp_current_album isn't set, and if no images can be found. You might want it to fall back to a
placeholder image instead. */
function getRandomImagesAlbum() {		
  if (zp_loggedin()) {
    $imageWhere = '';
  } else {
    $imageWhere = " AND `show`=1";
  }
  $images = array();
  $subIDs = getAllSubAlbumIDs($rootAlbum);
  if(is_null($subIDs)) {return null;}; //no subdirs avaliable
  foreach ($subIDs as $ID) {		
    $query = 'SELECT `id` , `albumid` , `filename` , `title` FROM '.prefix('images').' WHERE `albumid` = "'. $ID['id'] .'"' . $imageWhere; 		
    $images = array_merge($images, query_full_array($query)); 	
  }
  $image = NULL;
  while (is_null($image)) {
    if(count($images) < 1){return null;}; //no images avaliable in _any_ subdirectory
    $inx = array_rand($images);
    $randomImage = $images[$inx];
    $row = query_single_row("SELECT `folder`, `show`, `password` FROM " .prefix('albums'). " WHERE id = '" .$randomImage['albumid']. "'"); 	
    if (zp_loggedin() || (($row['show']==1) && empty($row['password']))) {
      $image = new Image(new Album(new Gallery(), $row['folder']), $randomImage['filename']);
      return $image;
    }	
    unset($images[$inx]);
  }
  return null;
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
      echo '<a href="' . $randomImageURL . '" title="View image: ' . htmlspecialchars($randomImage->getTitle(), ENT_QUOTES) . '">' .
      '<img src="' . $randomImage->getThumb() . 
      '" alt="'.htmlspecialchars($randomImage->getTitle(), ENT_QUOTES).'"'; 
      echo "/></a></li>\n";
    }
    echo "</ul>";
  }
}

function getImageRating($option, $id) {
if(!$id) { $id = getImageID(); }
  switch ($option) {
     case "totalvalue":
        $rating = "total_value"; break;
     case "totalvotes":
       $rating = "total_votes"; break;   
  }
  $result = query_single_row("SELECT ".$rating." FROM ". prefix('images') ." WHERE id = $id");
  return $result[$rating];
}

function getImageRatingCurrent($id) {
  $votes = getImageRating("totalvotes",$id);
  $value = getImageRating("totalvalue",$id);
  if($votes != 0)
    { $rating =  round($value/$votes, 1); 
  }
  return $rating;
} 

function checkIp($id) {
  $ip = $_SERVER['REMOTE_ADDR'];  
  $ipcheck = query_full_array("SELECT used_ips FROM ". prefix('images') ." WHERE used_ips LIKE '%".$ip."%' AND id= $id");
  return $ipcheck;
} 

function printImageRating() { 
  $id = getImageID();
  $value = getImageRating("totalvalue", $id);
  $votes = getImageRating("totalvotes", $id); 
  if($votes != 0) 
    { $ratingpx = round(($value/$votes)*25);
  }
  $zenpath = WEBPATH."/".ZENFOLDER."/plugins";
  echo "<div id=\"rating\">\n"; 
  echo "<h3>Rating:</h3>\n";
  echo "<ul class=\"star-rating\">\n"; 
  echo "<li class=\"current-rating\" id=\"current-rating\" style=\"width:".$ratingpx."px\"></li>\n"; 
  if(!checkIP($id)){  
    echo "<li><a href=\"javascript:rateImg(1,$id,$votes,$value,'".rawurlencode($zenpath)."')\" title=\"1 star out of 5\"' class=\"one-star\">2</a></li>\n";
    echo "<li><a href=\"javascript:rateImg(2,$id,$votes,$value,'".rawurlencode($zenpath)."')\" title=\"2 stars out of 5\" class=\"two-stars\">2</a></li>\n"; 
    echo "<li><a href=\"javascript:rateImg(3,$id,$votes,$value,'".rawurlencode($zenpath)."')\" title=\"3 stars out of 5\" class=\"three-stars\">2</a></li>\n"; 
    echo "<li><a href=\"javascript:rateImg(4,$id,$votes,$value,'".rawurlencode($zenpath)."')\" title=\"4 stars out of 5\" class=\"four-stars\">2</a></li>\n"; 
    echo "<li><a href=\"javascript:rateImg(5,$id,$votes,$value,'".rawurlencode($zenpath)."')\" title=\"5 stars out of 5\" class=\"five-stars\">2</a></li>\n";
  }
  echo "</ul>\n";
  echo "<div id =\"vote\">\n";
  echo "Rating: ".getImageRatingCurrent($id)." (Total votes: ".$votes.")";
  echo "</div>\n";
  echo "</div>\n";
}

function printTopRatedImages($number=5) {
printImageStatistic($number, "toprated");
}

function printMostRatedImages($number=5) {
printImageStatistic($number, "mostrated");
}

function my_truncate_string($string, $length) {
	if (strlen($string) > $length) {
		$short = substr($string, 0, $length);
		return $short. '...';
	} else {
		return $string;
	}
}

/**
 * grabs tags for either an image or album, depends on the page called from
 * @return string with all the tags
 * @since 1.1
 */
function getTags() {
  if(in_context(ZP_IMAGE)) {
    global $_zp_current_image;
    return $_zp_current_image->getTags();
  } else if (in_context(ZP_ALBUM)) {
    global $_zp_current_album;
    return $_zp_current_album->getTags();
  }
}

/**
 * prints a list of tags, editable by admin
 * @param string $option links by default, if anything else the 
      tags will not link to all other photos with the same tah
 * @param string $preText text to go before the printed tags
 * @param string $class css class to apply to the UL list
 * @param string $separator what charactor shall separate the tags
 * @param bit $editable true to allow admin to edit the tags
 * @return string with all the html for the admin toolbox
 * @since 1.1
 */
function printTags($option="links",$preText=NULL,$class='taglist',$separator=", ",$editable=TRUE) {
  $tags = getTags();
  $singletag = explode(",", $tags);
  if (empty($tags)) { $preText = ""; }
  if (!empty($preText)) { $preText = "<strong>".$preText."</strong>"; }
  if ($editable && zp_loggedin()) {
    echo "<div id=\"tagContainer\">".$preText."<div id=\"imageTags\" style=\"display: inline;\">" . htmlspecialchars(getTags(), ENT_QUOTES) . "</div></div>\n";
    echo "<script type=\"text/javascript\">initEditableTags('imageTags');</script>";
  } else {
    if (!empty($tags)) {
      echo "<ul class=\"".$class."\">\n";
      if (!empty($preText)) { 
	    echo "<li class=\"tags_title\">".$preText."</li>";
	  }
	  $ct = count($singletag);
      for ($x = 0; $x < $ct; $x++) {
        if ($x === $ct - 1) { $separator = ""; }
        if ($option === "links") {
          $links1 = "<a href=\"".getSearchURL($singletag[$x], '', SEARCH_TAGS)."\" title=\"".$singletag[$x]."\" rel=\"nofollow\">"; 
          $links2 = "</a>"; 
	    }
        echo "\t<li>".$links1.htmlspecialchars($singletag[$x], ENT_QUOTES).$links2.$separator."</li>\n";
	  }
    }
    echo "</ul><br clear=\"all\" />\n";  
  }
}

/**
 * grabs the entire galleries tags
 * @return string with all the tags
 * @since 1.1
 */
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

/**
 * either prints all of the galleries tgs as a UL list or a cloud
 * @param string $option "cloud" for tag cloud, "list" for simple list
 * @param string $class CSS class
 * @param string $sort "results" for relevance list, "abc" for alphabetical, blank for unsorted
 * @param bit $counter TRUE if you want the tag count within brackets behind the tag
 * @param bit $links text to go before the printed tags
 * @param string $maxfontsize largest font size the cloud should display
 * @param string $maxtagcout the maximum count for a tag to appear in the output
 * @param string $mintagcount the minimum count for a tag to appear in the output
 * @since 1.1
 */
function printAllTagsAs($option,$class="",$sort="abc",$counter=FALSE,$links=TRUE,$maxfontsize="2",$maxcount="50",$mincount="10") {
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
        echo "\t<li style=\"display:inline; list-style-type:none\"><a href=\"".
	         getSearchURL($key, '', SEARCH_TAGS)."\" style=\"font-size:".$size."em;\" rel=\"nofollow\">".
		     $key.$counter."</a></li>\n";  
	  }
	}
         
  } // while end 
  echo "</ul>\n";
}

function getAllDates() {
  $alldates = array();
  $cleandates = array();
  $sql = "SELECT `date` FROM ". prefix('images');
  if (!zp_loggedin()) { $sql .= " WHERE `show` = 1"; }
  $result = query_full_array($sql);
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
	echo "<li><a href=\"".getSearchURl('', substr($key, 0, 7))."\" rel=\"nofollow\">$month ($val)</a></li>\n";
  }
echo "</ul>\n</li>\n</ul>\n";
}

function getCustomPageURL($page, $q="") {
  if (getOption('mod_rewrite')) {
    $result .= WEBPATH."/page/$page";
	if (!empty($q)) { $result .= "?$q"; }
  } else {
    $result .= WEBPATH."/index.php?p=$page";
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
if (getOption('mod_rewrite')) {
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

/**
 * prints a RSS link
 *@param string $option type of RSS (Gallery, Album, Comments)
 *@param string $prev text to before before the link
 *@param string $linktext title of the link
 *@param string $next text to appear after the link
 *@param bit $printIcon print an RSS icon beside it? if true, the icon is zp-core/images/rss.gif
 *@param string $class css class
 *@return string link formatted for the HTML HEAD
 *@since 1.1
  */
function printRSSLink($option, $prev, $linktext, $next, $printIcon=true, $class=null) {
  if ($printIcon) {
    $icon = ' <img src="' . FULLWEBPATH . '/' . ZENFOLDER . '/images/rss.gif" alt="RSS Feed" />';
	} else {
    $icon = '';
	}
	if (!is_null($class)) {
    $class = 'class="' . $class . '";';
	}
	switch($option) {
		case "Gallery":
			echo $prev."<a $class href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "Album":
			echo $prev."<a $class href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php?albumnr=".getAlbumId()."&albumname=".getAlbumTitle()."\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
		case "Comments":
			echo $prev."<a $class href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss-comments.php\" rel=\"nofollow\">".$linktext."$icon</a>".$next;
			break;
	}
}

/**
 * prints the RSS link for use in the HTML HEAD
 *@param string $option type of RSS (Gallery, Album, Comments)
 *@param string $linktext title of the link
 *@return string link formatted for the HTML HEAD
 *@since 1.1
  */
function printRSSHeaderLink($option, $linktext) {
	switch($option) {
		case "Gallery":
			echo "<link rel=\"alternate\" type=\"application/rss+xml\" rel=\"nofollow\" title=\"".$linktext."\" href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php\" />\n";
			break;
		case "Album":
			echo "<link rel=\"alternate\" type=\"application/rss+xml\" rel=\"nofollow\" title=\"".$linktext."\" href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss.php?albumnr=".getAlbumId()."&albumname=".getAlbumTitle()."\" />\n";
			break;
		case "Comments":
			echo "<link rel=\"alternate\" type=\"application/rss+xml\" rel=\"nofollow\" title=\"".$linktext."\" href=\"http://".$_SERVER['HTTP_HOST'].WEBPATH."/rss-comments.php\" />\n";
			break;
	}
}


/*** Search functions *******************************************************
****************************************************************************/
/**
 * creates a search URL
 *@param string $param1 the search words target
 *@param string $param2 the dates that limit the search
 *@param integer $param3 the fields on which to search
 *@return string the url to be used for the search page
 *@since 1.1.3
  */
function getSearchURL($words, $dates, $fields=0) {
  $url = WEBPATH."/index.php?p=search";
  if($fields != 0) { $url .= "&searchfields=$fields"; }
  if (!empty($words)) { $url .= "&words=$words"; }
  if (!empty($dates)) { $url .= "&date=$dates"; }
  return $url;
}

/**
 *prints the search form
 *@param string $param1 text to go before the search form
 *@param bit $param2 prints a drop down of searchable elements
 *        = 0 all fields
 *        = NULL means no drop-down selection
 *@param string $param3 css id for the search form, default is 'search'
 *@return returns nothing
 *@since 1.1.3
 */
function printSearchForm($prevtext=NULL, $fieldSelect=NULL, $id='search') { 
  $zf = WEBPATH."/".ZENFOLDER;
  $dataid = $id . '_data';
  $searchwords = (isset($_POST['words']) ? htmlspecialchars(stripslashes($_REQUEST['words']), ENT_QUOTES) : ''); 
  
  echo "\n<div id=\"search\">";
  echo "\n<form method=\"post\" action=\"".WEBPATH."/index.php?p=search\" id=\"search_form\">"; 
  echo "\n$prevtext<input type=\"text\" name=\"words\" value=\"".$searchwords."\" id=\"search_input\" size=\"10\" />"; 
  echo "\n<input type=\"submit\" value=\"Search\" class=\"pushbutton\" id=\"search_submit\" />"; 
  
  $bits = array(SEARCH_TITLE, SEARCH_DESC, SEARCH_TAGS, SEARCH_FILENAME, SEARCH_LOCATION, SEARCH_CITY, SEARCH_STATE, SEARCH_COUNTRY);
  if ($fieldSelect === 0) { $fieldSelect = 32767; }
  $fields = getOption('search_fields') & $fieldSelect;
  
  $c = 0;
  foreach ($bits as $bit) {
    if ($bit & $fields) { $c++; }
    if ($c>1) break;
  }  
  if ($fieldSelect && ($c>1)) {  

    echo "\n<ul>";
    echo "\n<li class=\"top\">&raquo;</li>";
	if ($fields & SEARCH_TITLE) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_title\" value=1 checked> Title</li>";
	}
	if ($fields & SEARCH_DESC) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_desc\" value=1 checked> Description</li>";
	}
	if ($fields & SEARCH_TAGS) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_tags\" value=1 checked> Tags</li>";
	}
	if ($fields & SEARCH_FILENAME) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_filename\" value=1 checked> File/Folder name</li>";
	}
	if ($fields & SEARCH_LOCATION) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_location\" value=1 checked> Location</li>";
	}
	if ($fields & SEARCH_CITY) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_city\" value=1 checked> City</li>";
	}
	if ($fields & SEARCH_STATE) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_state\" value=1 checked> State</li>";
	}
	if ($fields & SEARCH_COUNTRY) {
      echo "\n<li class=\"item\"><input type=\"checkbox\" name=\"sf_country\" value=1 checked> Country</li>";
	}    
	echo "\n</ul>";
    echo "\n<div class=\"clear\"></div>";
  }
  echo "\n</form>\n"; 
  echo "\n</div>";  // search
  echo "\n<!-- end of search form -->\n";
} 

/**
 * grabs the search criteria
 * @param string $separator what to put inbetween the search results, default ' | '
 * @return returns search results, seperated by $separator
 * @since 1.1
 */
function getSearchWords($separator=" | ") {
  if (in_context(ZP_SEARCH)) {
    global $_zp_current_search;
	$tags = $_zp_current_search->getSearchString();
    return implode($separator, $tags);
  }
  return false;
}

/**
 * grabs the searched date
 * @param string $format formatting of the date, default 'F Y'
 * @return returns the date of the search
 * @since 1.1
 */
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

/**
 * finds the name of the themeColor option selected on the admin options tab
 * @param string $zenCSS path to the css file
 * @param string $themeColor name of the css file
 * @param string $defaultColor name of the default css file
 * @return returns the css file name for the theme
 * @since 1.1
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

/**
 * passed # of album columns, # of image columns of the theme
 * Updates (non-persistent) images_per_page and albums_per_page so that the rows are filled
 * @param int $albumColumns number of album columns on the page
 * @param int $imageColumns number of image columns on the page
 * @return returns # of images that will go on the album/image transition page
 * @since 1.1
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

/************************************************************************************************
 album password handling
 ************************************************************************************************/
/**
 * checks to see if a password is needed
 * displays a password form if log-on is required
 *@return bool true if a login form has been displayed
 *@since 1.1.3 
 **/
function checkforPassword($silent=false) {
  global $_zp_current_album, $_zp_current_search, $_zp_album_authorized, $_zp_gallery;
  if (ZP_loggedin()) { return false; }  // you're the admin, you don't need the passwords.
  if (in_context(ZP_SEARCH)) {
    $hash = getOption('search_password');
	$hint = getOption('search_hint');
    if (!is_null($hash)) {
	  if ($_zp_album_authorized != $hash) {
	    if (!$silent) {
		  printPasswordForm($hint);
		}
	    return true;
	  }
	}
  } else if (isset($_GET['album'])) {
    $album = new album($_zp_gallery, $_GET['album']);
    $hash = $album->getPassword();
    $hint = $album->getPasswordHint();
    if (empty($hash)) {
      $parent = $album->getParent();
      while (!is_null($parent)) {
        $pwd = $parent->getPassword();
        if (!empty($pwd)) { //whoo, sneeky url jump,
          return true;
        }
        $parent = $parent->getParent();
      }
    } else {
      if ($_zp_album_authorized != $hash) {
	    if (!$silent) {
	      printPasswordForm($hint);
		}
	    return true;
	  }
	}
    } else {
      $hash = getOption('gallery_password');
	  $hint = getOption('gallery_hint');
     if (!empty($hash)) {
	    if ($_zp_album_authorized != $hash) {
	      if (!$silent) {
		    printPasswordForm($hint);
		  }
	      return true;
	    }
	  }
	}

  return false;
}

/**
 * prints the album password form
 *@since 1.1.3
 */
function printPasswordForm($hint) {
  global $error, $_zp_password_form_printed, $_zp_current_search;
  if ($_zp_password_form_printed) { return; }
  $_zp_password_form_printed = true;
  if ($error) {
    echo "<div class=\"errorbox\" id=\"message\"><h2>There was an error logging in.</h2><br/>Check your password and try again.</div>";
  }
  $action = "#";
  if (in_context(ZP_SEARCH)) { 
    $action = "?p=search" . $_zp_current_search->getSearchParams(); 
  }
  echo "\n<p>The page you are trying to view is password protected.</p>";
  echo "\n<br/>";
  echo "\n  <form name=\"password\" action=\"$action\" method=\"POST\">";
  echo "\n    <input type=\"hidden\" name=\"password\" value=\"1\" />";
  
  echo "\n    <table>";
  echo "\n      <tr><td>Password</td><td><input class=\"textfield\" name=\"pass\" type=\"password\" size=\"20\" /></td></tr>";
  echo "\n      <tr><td colspan=\"2\"><input class=\"button\" type=\"submit\" value=\"Submit\" /></td></tr>";
  if (!empty($hint)) {
    echo "\n      <tr><td>Hint: " . $hint . "</td></tr>";
  }
  echo "\n    </table>";
  echo "\n  </form>";
}
/*** End template functions ***/

?>