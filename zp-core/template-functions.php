<?php

/**
* Functions used to display content in themes.
*/

/**
 * Load the classes
 */ 
require_once('classes.php');

/**
 * Invoke the controller to handle requests
 */ 
require_once('controller.php');


//******************************************************************************
//*** Template Functions *******************************************************
//******************************************************************************

/*** Generic Helper Functions *************/
/******************************************/

/**
 * General link printing function
 * @param string $url The link URL
 * @param string $text The text to go with the link
 * @param string $title Text for the title tag
 * @param string $class optional class
 * @param string $id optional id
 */
function printLink($url, $text, $title=NULL, $class=NULL, $id=NULL) {
  echo "<a href=\"" . htmlspecialchars($url) . "\"" .
  (($title) ? " title=\"" . htmlspecialchars($title, ENT_QUOTES) . "\"" : "") .
  (($class) ? " class=\"$class\"" : "") .
  (($id) ? " id=\"$id\"" : "") . ">" .
  $text . "</a>";
}
/**
 * Returns the zenphoto version string
 * @return string the version string
 */
function printVersion() {
  echo getOption('version');
}

/**
 * Prints the admin edit link for albums if the current user is logged-in

 * Returns true if the user is logged in
 * @param string $text text for the link
 * @param string $before text do display before the link
 * @param string $after  text do display after the link
 * @return bool
 * @since 1.1
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
 * Prints the admin edit link for subalbums if the current user is logged-in
 * @param string $text text for the link
 * @param string $before text do display before the link
 * @param string $after  text do display after the link
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
 * Prints the clickable drop down toolbox on any theme page with generic admin helpers
 * @param string $context index, album, image or search
 * @param string $id the html/css theming id
 * @since 1.1
 */
function printAdminToolbox($context=null, $id='admin') {
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

//*** Gallery Index (album list) Context ***
//******************************************

/**
 * Returns the title of the ZenPhoto Gallery without printing it.
 *
 * @return string
 */
function getGalleryTitle() {
  return getOption('gallery_title');
}
/**
 * Prints the title of the gallery.
 */
function printGalleryTitle() {
  echo getGalleryTitle();
}

/**
 * Returns the name of the main website if zenphoto is part of a website without printing it
 * and if added this in zp-config.php..
 *
 * @return string
 */
function getMainSiteName() {
  return getOption('website_title');
}
/**
 * Returns the URL of the main website if zenphoto is part of a website without
 * printing it and if added this in zp-config.php..
 *
 * @return string
 */
function getMainSiteURL() {
  return getOption('website_url');
}
/**
 * Prints the URL of the main website if zenphoto is part of a website
 * and if added this in zp-config.php.
 *
 * @param string $title Title text
 * @param string $class optional css class
 * @param string $id optional css id
 */
function printMainSiteLink($title=NULL, $class=NULL, $id=NULL) {
  printLink(getMainSiteURL(), getMainSiteName(), $title, $class, $id);
}

/**
 * Returns the URL of  index.php of zenphoto without printing it
 *
 * @return string
 */
function getGalleryIndexURL() {
  global $_zp_current_album;
  if (in_context(ZP_ALBUM) && $_zp_current_album->getGalleryPage() > 1) {
    $page = $_zp_current_album->getGalleryPage();
    return rewrite_path("/page/" . $page, "/index.php?page=" . $page);
  } else {
    return WEBPATH . "/";
  }
}

/**
 * Returns the number of albums without printing it.
 *
 * @return int
 */
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

 * Returns true if there are albums, false if none
 *
 * @param bool $all true to go through all the albums
 * @param string $sorttype what you want to sort the albums by
 * @return bool 
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

/**
 * Returns the number of albums without printing it.
 *
 * @return int
 */
function getCurrentPage() {
  global $_zp_page;
  return $_zp_page;
}

/**
 * Returns the count of subalbums in the album
 *
 * @return int
 */
function getNumSubalbums() {
  global $_zp_current_album;
  return count($_zp_current_album->getSubalbums());
}

/**
* Returns the number of pages if you have several pages  without printing it
*
* @param bool $oneImagePage set to true if your theme collapses all image thumbs
* or their equivalent to one page. This is typical with flash viewer themes
*
* @return int
*/
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

/**
 * Returns the URL of a page. Use alway with a variable like getPageURL(1)
 * for the first page for example. Use this function when you know the total pages
 *
 * @param int $page
 * @param int $total
 * @return int
 */
function getPageURL_($page, $total) {
  global $_zp_current_album, $_zp_gallery, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    $searchwords = $_zp_current_search->words;
    $searchdate = $_zp_current_search->dates;
    $searchfields = $_zp_current_search->fields;
    $searchpagepath = getSearchURL($searchwords, $searchdate, $searchfields, $page);
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

/**
* Returns the URL of a page. Use alway with a variable like getPageURL(1)
* for the first page for example.
*
* @param int $page
* @return string
*/
function getPageURL($page) {
  $total = getTotalPages();
  return(getPageURL_($page, $total));
}

/**
* Returns true if there is a next page
*
* @return bool
*/
function hasNextPage() { return (getCurrentPage() < getTotalPages()); }

/**
* Returns the URL of the next page. Use within If or while loops for pagination.
*
* @return string
*/
function getNextPageURL() {
  return getPageURL(getCurrentPage() + 1);
}

/**
* Prints the URL of the next page.
*
* @param string $text text for the URL
* @param string $title
* @param string $class
* @param string $id
*/
function printNextPageLink($text, $title=NULL, $class=NULL, $id=NULL) {
  if (hasNextPage()) {
    printLink(getNextPageURL(), $text, $title, $class, $id);
  } else {
    echo "<span class=\"disabledlink\">$text</span>";
  }
}

/**
* Returns TRUE if there is a previous page. Use within If or while loops for pagination.
*
* @return bool
*/
function hasPrevPage() { return (getCurrentPage() > 1); }

/**
* Returns the URL of the previous page.
*
* @return string
*/
function getPrevPageURL() {
  return getPageURL(getCurrentPage() - 1);
}

/**
* Returns the URL of the previous page.
*
* @param string $text The linktext that should be printed as a link
* @param string $title The text the html-tag "title" should contain
* @param string $class Insert here the CSS-class name you want to style the link with
* @param string $id Insert here the CSS-ID name you want to style the link with
*/
function printPrevPageLink($text, $title=NULL, $class=NULL, $id=NULL) {
  if (hasPrevPage()) {
    printLink(getPrevPageURL(), $text, $title, $class, $id);
  } else {
    echo "<span class=\"disabledlink\">$text</span>";
  }
}

/**
* Prints a page navigation including previous and next page links
*
* @param string $prevtext Insert here the linktext like 'previous page'
* @param string $separator Insert here what you like to be shown between the prev and next links
* @param string $nexttext Insert here the linktext like "next page"
* @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
* @param string $id Insert here the CSS-ID name if you want to style the link with this
*/
function printPageNav($prevtext, $separator, $nexttext, $class='pagenav', $id=NULL) {
  echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
  printPrevPageLink($prevtext, "Previous Page");
  echo " $separator ";
  printNextPageLink($nexttext, "Next Page");
  echo "</div>\n";
}


/**
 * Prints a list of all pages.
 *
 * @param string $class the css class to use, "pagelist" by default
 * @param string $id the css id to use
 */
function printPageList($class='pagelist', $id=NULL) {
  printPageListWithNav(null, null, false, false, $class, $id);
}


/**
* Prints a full page navigation including previous and next page links with a list of all pages in between.
*
* @param string $prevtext Insert here the linktext like 'previous page'
* @param string $nexttext Insert here the linktext like 'next page'
* @param bool $oneImagePage set to true if there is only one image page as, for instance, in flash themes
* @param string $nextprev set to true to get the 'next' and 'prev' links printed
* @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
* @param string $id Insert here the CSS-ID name if you want to style the link with this
*/
function printPageListWithNav($prevtext, $nexttext, $oneImagePage=false, $nextprev=true, $class='pagelist', $id=NULL) {
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

//*** Album Context ************************
//******************************************


/**
* Returns the title of the current album.
*
* @return string
*/
function getAlbumTitle() {
  if(!in_context(ZP_ALBUM)) return false;
  global $_zp_current_album;
  return $_zp_current_album->getTitle();
}
/**
* Prints the title of the current album. If you are logged in you can click on this to modify the name on the fly.
*
* @param bool $editable set to true to allow editing (for the admin)
*/
function printAlbumTitle($editable=false) {
  global $_zp_current_album;
  if ($editable && zp_loggedin()) {
    echo "<div id=\"albumTitleEditable\" style=\"display: inline;\">" . htmlspecialchars(getAlbumTitle()) . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableTitle('albumTitleEditable');</script>";
  } else {
    echo htmlspecialchars(getAlbumTitle());
  }
}


/**
* Gets the 'n' for n of m albums
*
* @return int
*/
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


/**
* Returns the names of the parents of the subalbums.
*
* @return object
*/
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

/**
* Prints the breadcrumb navigation for album, gallery and image view.
*
* @param string $before Insert here the text to be printed before the links
* @param string $between Insert here the text to be printed between the links
* @param string $after Insert here the text to be printed after the links
*/
function printParentBreadcrumb($before = '', $between=' | ', $after = ' | ') {
  global $_zp_current_search;
  echo $before;
  if (in_context(ZP_SEARCH)) {
    $page = $_zp_current_search->page;
    $searchwords = $_zp_current_search->words;
    $searchdate = $_zp_current_search->dates;
    $searchfields = $_zp_current_search->fields;
    $searchpagepath = getSearchURL($searchwords, $searchdate, $searchfields, $page);
    echo "<a href=\"" . $searchpagepath . "\"><em>Search</em></a>";
  } else {
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
  }
  echo $after;
}

/**
 * Prints a link to the 'main website'
 * Only prints the link if the url is not empty and does not point back the the gallery page
 *
 * @param string $before text to precede the link
  * @param string $after text to follow the link
 */
function printHomeLink($before='', $after='') {
  $site = getOption('website_url');
  if (!empty($site)) {
    if (substr($site,-1) == "/") { $site = substr($site, 0, -1); }
    $title = getOption('website_title');
    if (empty($title)) { $title = 'Home'; }
    if ($site != FULLWEBPATH) {
      echo $before . "<a href =\"" . $site . "\">" . $title . "</a>" . $after;
    }
  }
  
}

/**
* Returns the formatted date field of the album
*
* @param string $format
* @return string
*/
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

/**
* Returns the date of the current album
*
* @param string $before Insert here the text to be printed before the date.
* @param string $nonemessage Insert here the text to be printed if there is no date.
* @param string $format Format string for the date formatting
*/
function printAlbumDate($before='Date: ', $nonemessage='', $format='F jS, Y') {
  $date = getAlbumDate($format);
  if ($date) {
    echo $before . $date;
  } else {
    echo $nonemessage;
  }
}

/**
* Returns the place of the album.
*
* @return string
*/
function getAlbumPlace() {
  global $_zp_current_album;
  return $_zp_current_album->getPlace();
}

/**
* Prints the place of the album.
*
*/
function printAlbumPlace() {
  echo getAlbumPlace();
}

/**
* Returns the album description of the current album.
*
* @return string
*/
function getAlbumDesc() {
  if(!in_context(ZP_ALBUM)) return false;
  global $_zp_current_album;
  return str_replace("\n", "<br />", $_zp_current_album->getDesc());
}
/**
* Prints the album description of the current album.
*
* @param bool $editable
*/
function printAlbumDesc($editable=false) {
  global $_zp_current_album;
  if ($editable && zp_loggedin()) {
    echo "<div id=\"albumDescEditable\" style=\"display: block;\">" . getAlbumDesc() . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableDesc('albumDescEditable');</script>";
  } else {
    echo getAlbumDesc();
  }
}

/**
 * Returns the custom_data field of the current album
 *
 * @return string
 */
function getAlbumCustomData() {
  global $_zp_current_album;
  return $_zp_current_album->getCustomData();
}

/**
 * Sets the album custom_data field
 *
 * @param string $val
 */
function setAlbumCustomData($val) {
  global $_zp_current_album;
  $_zp_current_album->setCustomData($val); 
  $_zp_current_album->save(); 
}

/**
* Returns the album link url of the current album.
*
* @return string
*/
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

/**
* Prints the album link url of the current album.
*
* @param string $text Insert the link text here.
* @param string $title Insert the title text here.
* @param string $class Insert here the CSS-class name with with you want to style the link.
* @param string $id Insert here the CSS-id name with with you want to style the link.
*/
function printAlbumLink($text, $title, $class=NULL, $id=NULL) {
  printLink(getAlbumLinkURL(), $text, $title, $class, $id);
}

/**
* Print a link that allows the user to sort the current album if they are logged in.
* If they are already sorting, the Save button is displayed.
*
* @param string $text Insert the link text here.
* @param string $title Insert the title text here.
* @param string $class Insert here the CSS-class name with with you want to style the link.
* @param string $id Insert here the CSS-id name with with you want to style the link.
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
 *
 * @param string $text Insert the link text here.
 * @param string $title Insert the title text here.
 * @param string $class Insert here the CSS-class name with with you want to style the link.
 * @param string $id Insert here the CSS-id name with with you want to style the link.
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

/**
* Returns the name of the defined album thumbnail image.
*
* @return string
*/
function getAlbumThumb() {
  global $_zp_current_album;
  return $_zp_current_album->getAlbumThumb();
}

/**
* Prints the album thumbnail image.
*
* @param string $alt Insert the text for the alternate image name here.
* @param string $class Insert here the CSS-class name with with you want to style the link.
* @param string $id Insert here the CSS-id name with with you want to style the link.
*  */
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

/**
* Returns a link to a custom sized thumbnail of the current album
*
* @param int $size the size of the image to have
* @param int $width width
* @param int $height height
* @param int $cropw cropwidth
* @param int $croph crop height
* @param int $cropx crop part x axis
* @param int $cropy crop part y axis
*
* @return string
*/

function getCustomAlbumThumb($size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=null) {
  global $_zp_current_album;
  $thumb = $_zp_current_album->getAlbumThumbImage();
  return $thumb->getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, true);
}

/**
* Prints a link to a custom sized thumbnail of the current album
*
* @param string $alt Alt atribute text
* @param int $size size
* @param int $width width
* @param int $height height
* @param int $cropw cropwidth
* @param int $croph crop height
* @param int $cropx crop part x axis
* @param int $cropy crop part y axis
* @param string $class css class
* @param string $id css id
*
* @return string
*/
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


/**
* Get the URL of the next album in the gallery.
*
* @return string
*/
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

/**
* Get the URL of the previous album in the gallery.
*
* @return string
*/
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

/**
* Returns true if this page has image thumbs on it
*
* @return bool
*/
function isImagePage() {
  global $_zp_page;
  return ($_zp_page - getTotalPages(true)) >= 0;
}

/**
* Returns true if this page has album thumbs on it
*
* @return bool
*/
function isAlbumPage() {
  global $_zp_page;
  if (in_context(ZP_SEARCH)) {
    $pageCount = Ceil(getNumAlbums() / getOption('albums_per_page'));
  } else {
    $pageCount = Ceil(getNumSubalbums() / getOption('albums_per_page'));
  }
  return ($_zp_page <= $pageCount);
}

/**
* Returns the number of images in the album.
*
* @return int
*/
function getNumImages() {
  global $_zp_current_album, $_zp_current_search;
  if (in_context(ZP_SEARCH)) {
    return $_zp_current_search->getNumImages();
  } else {
    return $_zp_current_album->getNumImages();
  }
}

/**

 * Returns the count of all the images in the album and any subalbums

 *

 * @param object $album The album whose image count you want

 * @return int

 * @since 1.1.4

 */

function getTotalImagesIn($album) {

  global $_zp_gallery;

  $sum = $album->getNumImages();

  $subalbums = $album->getSubalbums(0);

  while (count($subalbums) > 0) {

    $albumname = array_pop($subalbums);

    $album = new Album($_zp_gallery, $albumname);

    $sum = $sum + getTotalImagesIn($album);

  }

  return $sum;

}


/**
 * Returns the next image on a page.
 * sets $_zp_current_image to the next image in the album.

 * Returns true if there is an image to be shown
 *
 *@param bool $all set to true disable pagination
 *@param int $firstPageCount the number of images which can go on the page that transitions between albums and images
 *@param string $sorttype overrides the default sort type
 *@param bool $overridePassword the password chedk
 *@return bool
 *
 * @return bool
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

//*** Image Context ************************
//******************************************

/**
* Returns the title of the current image.
*
* @return string
*/
function getImageTitle() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getTitle();
}

/**
* Returns the title of the current image.
*
* @param bool $editable if set to true and the admin is logged in allows editing of the title
*/
function printImageTitle($editable=false) {
  global $_zp_current_image;
  if ($editable && zp_loggedin()) {
    echo "<div id=\"imageTitle\" style=\"display: inline;\">" . htmlspecialchars(getImageTitle()) . "</div>\n";
    echo "<script type=\"text/javascript\">initEditableTitle('imageTitle');</script>";
  } else {
    echo "<div id=\"imageTitle\" style=\"display: inline;\">" . htmlspecialchars(getImageTitle()) . "</div>\n";
  }
}

/**
* Returns the 'n' of n of m images
*
* @return int
*/
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

/**
* Returns the image date of the current image in yyyy-mm-dd hh:mm:ss format. 
* Pass it a date format string for custom formatting
*
* @param string $format formatting string for the data
* @return string
*/
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

/**
* Prints the data from the current image
*
* @param string $before Text to put out before the date (if there is a date)
* @param string $nonemessage Text to put out if there is no date
* @param string $format format string for the date
*/
function printImageDate($before='Date: ', $nonemessage='', $format='F jS, Y') {
  $date = getImageDate($format);
  if ($date) {
    echo $before . $date;
  } else {
    echo $nonemessage;
  }
}

// IPTC fields
/**
* Returns the Location field of the current image
*
* @return string
*/
function getImageLocation() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getLocation();
}

/**
* Returns the City field of the current image
*
* @return string
*/
function getImageCity() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getcity();
}

/**
* Returns the State field of the current image
*
* @return string
*/
function getImageState() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getState();
}

/**
* Returns the Country field of the current image
*
* @return string
*/
function getImageCountry() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getCountry();
}

/**
* Returns video argument of the current Image.
*
* @return bool
*/
function getImageVideo() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getVideo();
}

/**
* Returns video Thumbnail of the current Image.
*
* @return string
*/
function getImageVideoThumb() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getVideoThumb();
}

/**
* Returns the description field of the current image
* new lines are replaced with <br/> tags
*
* @return string
*/
function getImageDesc() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  $desc = str_replace("\r\n", "\n", $_zp_current_image->getDesc());
  return str_replace("\n", "<br/>", $desc);
}

/**
* Prints the description field of the current image
*
* @param bool $editable set true to allow editing by the admin
*/
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
* A composit for getting image data
*
* @param string $field which field you want
* @return string
*/
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

/**
 * Returns the custom_data field of the current image
 *
 * @return string
 */
function getImageCustomData() { 
  Global $_zp_current_image;
  return $_zp_current_image->getCustomData();
}

/**
 * Sets the image custom_data field
 *
 * @param string $val
 */
function setImageCustomData($val) {
  Global $_zp_current_image;
  $_zp_current_image->setCustomData($val);
  $_zp_current_image->save();
  }

/**
* A composit for printing image data
*
* @param string $field which data you want
* @param string $label the html label for the paragraph
*/
function printImageData($field, $label) {
  global $_zp_current_image;
  if(getImageData($field)) { // only print it if there's something there
    echo "<p class=\"metadata\"><strong>" . $label . "</strong> " . getImageData($field) . "</p>\n";
  }
}

/**
 * Get the unique ID of the current image.
 *
 * @return int
 */
function getImageID() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->id;
}

/**
 * Print the unique ID of the current image.
 */
function printImageID() {
  if (!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  echo "image_".getImageID();
}

/**
 * Get the sort order of this image.
 *
 * @return string
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

/**
* True if there is a next image
*
* @return bool
*/
function hasNextImage() { global $_zp_current_image; return $_zp_current_image->getNextImage(); }
/**
* True if there is a previous image
*
* @return bool
*/
function hasPrevImage() { global $_zp_current_image; return $_zp_current_image->getPrevImage(); }

/**
* Returns the url of the next image.
*
* @return string
*/
function getNextImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  $nextimg = $_zp_current_image->getNextImage();
  return rewrite_path("/" . pathurlencode($nextimg->album->name) . "/" . urlencode($nextimg->filename) . im_suffix(),
    "/index.php?album=" . urlencode($nextimg->album->name) . "&image=" . urlencode($nextimg->filename));
}

/**
* Returns the url of the previous image.
*
* @return string
*/
function getPrevImageURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_album, $_zp_current_image;
  $previmg = $_zp_current_image->getPrevImage();
  return rewrite_path("/" . pathurlencode($previmg->album->name) . "/" . urlencode($previmg->filename) . im_suffix(),
    "/index.php?album=" . urlencode($previmg->album->name) . "&image=" . urlencode($previmg->filename));
}


/**
* Prints out the javascript to preload the next and previous images
*
*/
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

/**
* Returns the thumbnail of the previous image.
*
* @return string
*/
function getPrevImageThumb() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  $img = $_zp_current_image->getPrevImage();
  return $img->getThumb();
}

/**
* Returns the thumbnail of the next image.
*
* @return string
*/
function getNextImageThumb() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  $img = $_zp_current_image->getNextImage();
  return $img->getThumb();
}

/**
* Returns the url of the current image.
*
* @return string
*/
function getImageLinkURL() {
  if(!in_context(ZP_IMAGE)) return false;
  global $_zp_current_image;
  return $_zp_current_image->getImageLink();
}

/**
* Prints the link to the current  image.
*
* @param string $text text for the link
* @param string $title title tag for the link
* @param string $class optional style class for the link
* @param string $id optional style id for the link
*/
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

/**
* Returns the EXIF infromation from the current image
*
* @return array
*/
function getImageEXIFData() {
  global $_zp_current_image;
  return $_zp_current_image->getExifData();
}

/**
* Prints image data. Deprecated, use printImageMetadata
*
*/
function printImageEXIFData() { if (getImageVideo()) { } else { printImageMetadata(); } }

/**
* Prints the EXIF data of the current image
*
* @param string $title title tag for the class
* @param bool $toggle set to true to get a java toggle on the display of the data
* @param string $id style class id
* @param string $class style class
*/
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
 * @param  string $zoomlevel the zoom in for the map
 * @param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 * @param int $width is the image width of the map. NULL will use the default
 * @param int $height is the image height of the map. NULL will use the default
 * @since 1.1.3
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

/**
* Returns true if the curent image has EXIF location data
*
* @return bool
*/
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
 * @param  string $zoomlevel the zoom in for the map. NULL will use the default (auto-zoom based on points)
 * @param string $type of map to produce: allowed values are G_NORMAL_MAP | G_SATELLITE_MAP | G_HYBRID_MAP
 * @param int $width is the image width of the map. NULL will use the default
 * @param int $height is the image height of the map. NULL will use the default
 * @since 1.1.3
 */
function printAlbumMap($zoomlevel=NULL, $type=NULL, $width=NULL, $height=NULL){
  global $_zp_phoogle;
  if(getOption('gmaps_apikey') != ''){
    $foundLocation = false;
    if($zoomlevel){ $_zp_phoogle->setZoomLevel($zoomlevel); }
    if (!is_null($type)) { $_zp_phoogle->setMapType($type); }
    if (!is_null($width)) { $_zp_phoogle->setWidth($width); }
    if (!is_null($height)) { $_zp_phoogle->setHeight($height); }
    while (next_image(false)) {
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


/**
* Returns a link to a custom sized version of he current image
*
* @param int $size size
* @param int $width width
* @param int $height height
* @param int $cw crop width
* @param int $ch crop height
* @param int $cx crop x axis
* @param int $cy crop y axis
* @return string
*/
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

/**
* Returns an array [width, height] of the default-sized image.
*
* @return array
*/
function getSizeDefaultImage() {
  return getSizeCustomImage(getOption('image_size'));
}

/**
* Returns an array [width, height] of the original image.
*
* @return array
*/
function getSizeFullImage() {
  global $_zp_current_image;
  return array($_zp_current_image->getWidth(), $_zp_current_image->getHeight());
}

/**
* The width of the default-sized image (in printDefaultSizedImage)
*
* @return int
*/
function getDefaultWidth() {
  $size = getSizeDefaultImage(); return $size[0];
}

/**
* Returns the height of the default-sized image (in printDefaultSizedImage)
*
* @return int
*/
function getDefaultHeight() {
  $size = getSizeDefaultImage(); return $size[1];
}

/**
* Returns the width of the original image
*
* @return int
*/
function getFullWidth() {
  $size = getSizeFullImage(); return $size[0];
}

/**
* Returns the height of the original image
*
* @return int
*/
function getFullHeight() {
  $size = getSizeFullImage(); return $size[1];
}

/**
* Returns true if the image is landscape-oriented (width is greater than height)
*
* @return bool
*/
function isLandscape() {
  if (getFullWidth() >= getFullHeight()) return true;
  return false;
}

/**
* Returns the url to the default sized image.
*
* @return string
*/
function getDefaultSizedImage() {
  global $_zp_current_image;
  return $_zp_current_image->getSizedImage(getOption('image_size'));
}

/**
* Show video player with video loaded or display the image.
*
* @param string $alt Alt text
* @param string $class Optional style class
* @param string $id Optional style id
*/
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

/**
* Returns the url to the thumbnail current image.
*
* @return string
*/
function getImageThumb() {
  global $_zp_current_image;
  return $_zp_current_image->getThumb();
}
/**
 * @param string $alt Alt text
 * @param string $class optional class tag
 * @param string $id optional id tag
 */
function printImageThumb($alt, $class=NULL, $id=NULL) {
  global $_zp_current_image;
  if (!$_zp_current_image->getShow()) {
    $class .= " not_visible";
  }

  $album = $_zp_current_image->getAlbum();

  $pwd = $album->getPassword();

  if (zp_loggedin() && !empty($pwd)) {
    $class .= " password_protected";
  }

  $class = trim($class);
  echo "<img src=\"" . htmlspecialchars(getImageThumb()) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
  ((getOption('thumb_crop')) ? " width=\"".getOption('thumb_crop_width')."\" height=\"".getOption('thumb_crop_height')."\"" : "") .
  (($class) ? " class=\"$class\"" : "") .
  (($id) ? " id=\"$id\"" : "") . " />";
}

/**
* Returns the url to original image.
*
* @return string
*/
function getFullImageURL() {
  global $_zp_current_image;
  return $_zp_current_image->getFullImage();
}
/**
 * Returns an url to the password protected/watermarked current image
 * 
 * @return string 
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

/**
* Returns a link to the current image custom sized to $size
*
* @param int $size
*/
function getSizedImageURL($size) {
  getCustomImageURL($size);
}

/**
* Returns the url to the  image in that dimensions you define with this function.
*
* @param int $size the size of the image to have
* @param int $width width
* @param int $height height
* @param int $cropw cropwidth
* @param int $croph crop height
* @param int $cropx crop part x axis
* @param int $cropy crop part y axis
* @param bool $thumbStandin set true to inhibit watermarking

* * @return string
*/
function getCustomImageURL($size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=NULL, $thumbStandin=false) {
  global $_zp_current_image;
  return $_zp_current_image->getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin);
}

/**
* Print normal video or custom sized images.
* Note: a class of 'not_visible' or 'password_protected' will be added as appropriate
*
* @param string $alt Alt text for the url
* @param int $size size
* @param int $width width
* @param int $height height 
* @param int $cropw crop width
* @param int $croph crop height
* @param int $cropx crop x axis
* @param int $cropy crop y axis
* @param string $class Optional style class
* @param string $id Optional style id
* @param bool $thumbStandin set true to inhibit watermarking

* */
function printCustomSizedImage($alt, $size, $width=NULL, $height=NULL, $cropw=NULL, $croph=NULL, $cropx=NULL, $cropy=NULL, $class=NULL, $id=NULL, $thumbStandin=false) {
  global $_zp_current_album, $_zp_current_image;
  if (!$_zp_current_album->getShow()) {
    $class .= " not_visible";
  } else {
    $pwd = $_zp_current_album->getPassword();
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
  		echo "<img src=\"" . htmlspecialchars(getCustomImageURL($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin)) . "\" alt=\"" . htmlspecialchars($alt, ENT_QUOTES) . "\"" .
    	" width=\"" . $sizearr[0] . "\" height=\"" . $sizearr[1] . "\"" .
  		(($class) ? " class=\"$class\"" : "") .
  		(($id) ? " id=\"$id\"" : "") . " />";
  }
}
/**
* Prints out a sized image up to $maxheight tall (as width the value set in the admin option is taken)
*
* @param int $maxheight how bif the picture should be
*/
function printCustomSizedImageMaxHeight($maxheight) {
  if (getFullWidth() === getFullHeight() OR getDefaultHeight() > $maxheight) {
    printCustomSizedImage(getImageTitle(), null, null, $maxheight, null, null, null, null, null, null);
  } else {
    printDefaultSizedImage(getImageTitle());
  }
}
/**
 * Prints link to an image of specific size
 * @param int $size how big
 * @param string $text URL text
 * @param string $title URL title
 * @param string $class optional URL class
 * @param string $id optional URL id
 */
function printSizedImageLink($size, $text, $title, $class=NULL, $id=NULL) {
  printLink(getSizedImageURL($size), $text, $title, $class, $id);
}
/**

* Retuns the count of comments on the current image

*
* @return int
*/
function getCommentCount() {
  global $_zp_current_image, $_zp_current_album;

  if (in_context(ZP_IMAGE)) {
    return $_zp_current_image->getCommentCount();

  } else {

    return $_zp_current_album->getCommentCount();

  }
}
/**
* Returns true if neither the album nor the image have comments closed
*
* @return bool
*/
function getCommentsAllowed() {
  global $_zp_current_image, $_zp_current_album;

  if (in_context(ZP_IMAGE)) {

    return $_zp_current_image->getCommentsAllowed();

  } else {

    return $_zp_current_album->getCommentsAllowed();

  }
}
/**
 *Iterate through comments; use the ZP_COMMENT context.
 *Return true if there are more comments

 * 

 *@return bool

 *  */
function next_comment() {
  global $_zp_current_image, $_zp_current_album, $_zp_current_comment, $_zp_comments;
  if (is_null($_zp_current_comment)) {

    if (in_context(ZP_IMAGE)) {

      $_zp_comments = $_zp_current_image->getComments();  

    } else {

      $_zp_comments = $_zp_current_album->getComments();  

    }
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

/**
* Returns the comment author's name
*
* @return string
*/
function getCommentAuthorName() { global $_zp_current_comment; return $_zp_current_comment['name']; }

/**
* Returns the comment author's email
*
* @return string
*/
function getCommentAuthorEmail() { global $_zp_current_comment; return $_zp_current_comment['email']; }
/**
* Returns the comment author's website
*
* @return string
*/
function getCommentAuthorSite() { global $_zp_current_comment; return $_zp_current_comment['website']; }
/**
 * Prints a link to the author

 *
 * @param string $title URL title tag
 * @param string $class optional class tag
 * @param string $id optional id tag
 */
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

/**
 * Retrieves the date of the current comment

 * Returns a formatted date
 *
 * @param string $format how to format the result
 * @return string
 */
function getCommentDate($format = 'F jS, Y') { global $_zp_current_comment; return myts_date($format, $_zp_current_comment['date']); }
/**
 * Retrieves the time of the current comment
 * Returns a formatted time

 * @param string $format how to format the result
 * @return string
 */
function getCommentTime($format = 'g:i a') { global $_zp_current_comment; return myts_date($format, $_zp_current_comment['date']); }

/**
* Returns the body of the current comment
*
* @return string
*/
function getCommentBody() {
  global $_zp_current_comment;
  return str_replace("\n", "<br />", stripslashes($_zp_current_comment['comment']));
}

/**
 * Creates a link to the admin comment edit page for the current comment
 *
 * @param string $text Link text
 * @param string $before text to go before the link
 * @param string $after text to go after the link
 * @param string $title title text
 * @param string $class optional css clasee
 * @param string $id optional css id
 */
function printEditCommentLink($text, $before='', $after='', $title=NULL, $class=NULL, $id=NULL) {
  global $_zp_current_comment;
  if (zp_loggedin()) {
    echo $before;
    printLink(WEBPATH . '/' . ZENFOLDER . '/admin.php?page=editcomment&id=' . $_zp_current_comment['id'], $text, $title, $class, $id);
    echo $after;
  }
}

/**
 * Tool to put an out error message if a comment possting was not accepted
 *
 * @param string $class optional division class for the message
 */
function printCommentErrors($class = 'error') {
  global $_zp_comment_error;
  if (isset($_zp_comment_error)) {
    echo "<div class=$class>";

    switch ($_zp_comment_error) {

      case -1: echo "You must supply an e-mail address."; break;

      case -2: echo "You must entert your name."; break;

      case -3: echo "You must supply an WEB page URL."; break;

      case -4: echo "Captcha verification failed."; break;

      case -5: echo "You must enter something in the comment text."; break;

      case  1: echo "You comment failed the SPAM filter check."; break;

      case  2: echo "You comment has been marked for moderation."; break;

    }
    echo "</div>";
  }
  return $_zp_comment_error;
}
/**
 * Creates an URL for a download of a zipped copy of the current album
 */
function printAlbumZip(){
  global $_zp_current_album;
  echo'<a href="' . rewrite_path("/" . pathurlencode($_zp_current_album->name),
		"/index.php?album=" . urlencode($_zp_current_album->name)) .
		'&zipfile" title="Download Zip of the Album">Download a zip file ' .
		'of this album</a>';
}

/**
* Prints out latest comments for the current image
*
* @param int $number how many comments you want.
* @param string $type 'images' for image comments, 'albums' for album comments
*/
function printLatestComments($number, $type='images') {
  echo '<div id="showlatestcomments">';
  echo '<ul>';
  $sql = "SELECT c.id, c.name, c.website, c.date, c.comment, "; 

  if ($type=='images') {

    $sql .= "i.title, i.filename, ";

  }

  $sql .= "a.folder, a.title AS albumtitle, ";
  $sql .= " FROM ".prefix('comments') . " AS c, ";

  if ($type=='images') {

    $sql .= prefix('images') . " AS i, ";

  }

  $sql .= prefix('albums')." AS a ";
  $sql .= " WHERE `type`=$type AND "; 

  if ($type=='images') {

    $sql .= "c.imageid = i.id AND i.albumid = a.id ";

  } else {

    $sql .= "c.imageid = a.id ";

 

  }

  $sql .= "ORDER BY c.id DESC LIMIT $number";

  $comments = Query_full_array($sql);
  foreach ($comments as $comment) {
    $author = $comment['name'];
    $album = $comment['folder'];
    $image = $comment['filename'];
    $albumtitle = $comment['albumtitle'];

    if ($type=='images') {
      if ($comment['title'] == "")  {
        $title = $image;
      }	else {
        $title = $comment['title'];
      }

      $title .= ' / ';

    } else {

      $title = '';

    }
    $website = $comment['website'];
    $comment = my_truncate_string($comment['comment'], 40);

    $link = $author.' commented on '.$albumtitle.$title ;
    $short_link = my_truncate_string($link, 40);

    echo '<li><div class="commentmeta"><a href="';

    if (getOption('mod_rewrite') == false) {
      echo WEBPATH.'/index.php?album='.urlencode($album);

      if ($type=='images') {

        echo '&image='.urlencode($image).'/"';

      }
    } else {
      echo WEBPATH.'/'.$album.'/';

      if ($type=='images') {

        echo $image.'" ';

      }
    }

    echo 'title="'.$link.'">';
    echo $short_link.'</a>:</div><div class="commentbody">'.$comment.'</div></li>';
  }
  echo '</ul>';
  echo '</div>';
}

/**
 * Increments (optionally) and returns the hitcounter
 *
 * @param string $option "image" for image hit counter (default), "album" for album hit counter
 * @param bool $viewonly set to true if you don't want to increment the counter.

 * @param int $id Optional record id of the object if not the current image or album
 * @return string
 * @since 1.1.3
 */
function hitcounter($option='image', $viewonly=false, $id=NULL) {
  switch($option) {
    case "image":

      if (is_null($id)) {
        $id = getImageID();

      }
      $dbtable = prefix('images');
      $doUpdate = true;
      break;
    case "album":

      if (is_null($id)) {
        $id = getAlbumID();

      }
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
 * Retuns a list of album statistic accordingly to $option
 *
 * @param int $number the number of albums to get
 * @param string $option "popular" for the most popular albums, 

 *     "latest" for the latest uploaded, "mostrated" for the most voted, 

 *     "toprated" for the best voted
 * @return string
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
    case "mostrated":
      $sortorder = "total_votes"; break;
    case "toprated":
      $sortorder = "(total_value/total_votes)"; break;  
  }
  $albums = query("SELECT id, title, folder, thumb FROM " . prefix('albums') . $albumWhere . " ORDER BY ".$sortorder." DESC LIMIT $number");
  return $albums;
}

/**
 * Prints album statistic according to $option
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular albums, 

 *                  "latest" for the latest uploaded, 

 *                  "latest" for the latest uploaded, 

 *                  "mostrated" for the most voted, 

 *                  "toprated" for the best voted
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
 * Prints the most popular albums
 *
 * @param string $number the number of albums to get
 */
function printPopularAlbums($number=5) {
  printAlbumStatistic($number,"popular");
}

/**
 * Prints the latest albums
 *
 * @param string $number the number of albums to get
 */
function printLatestAlbums($number=5) {
  printAlbumStatistic($number,"latest");
}

/**
 * Prints the most rated albums
 *
 * @param string $number the number of albums to get
 */
function printMostRatedAlbums($number=5) {
  printAlbumStatistic($number,"mostrated");
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 */
function printTopRatedAlbums($number=5) {
  printAlbumStatistic($number,"toprated");
}

/**
 * Returns a list of image statistic according to $option
 *
 * @param string $number the number of images to get
 * @param string $option "popular" for the most popular images, 
 *                       "latest" for the latest uploaded, 
 *                       "latest" for the latest uploaded, 
 *                       "mostrated" for the most voted, 
 *                       "toprated" for the best voted
 * @param string $album title of an specific album 
 * @return string
 */
function getImageStatistic($number, $option, $album='') {
  global $_zp_gallery;
  if (zp_loggedin()) {
    $albumWhere = "";
    $imageWhere = "";
  } else {
    $albumWhere = " AND albums.show=1 AND albums.password=''";
    $imageWhere = " AND images.show=1";
  }
  if(!empty($album)) {
    $specificalbum = " albums.title = '".$album."' AND ";
  } else {
    $specificalbum = "";
  }
  switch ($option) {
    case "popular":
      $sortorder = "images.hitcounter"; break;
    case "latest":
      $sortorder = "images.id"; break;
    case "mostrated":
      $sortorder = "images.total_votes"; break;
    case "toprated":
      $sortorder = "(images.total_value/images.total_votes)"; break;
  }
  $imageArray = array();
  $images = query_full_array("SELECT images.albumid, images.filename AS filename, images.title AS title, " .
                             "albums.folder AS folder, images.show, albums.show, albums.password FROM " .
  prefix('images') . " AS images, " . prefix('albums') . " AS albums " .
                              " WHERE ".$specificalbum."images.albumid = albums.id " . $imageWhere . $albumWhere .
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
 * Prints image statistic according to $option
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular images, 
 *                       "latest" for the latest uploaded, 
 *                       "latest" for the latest uploaded, 
 *                       "mostrated" for the most voted, 
 *                       "toprated" for the best voted
 * @param string $album title of an specific album
 * @return string
 */
function printImageStatistic($number, $option, $album='') {
  $images = getImageStatistic($number, $option, $album);
  echo "\n<div id=\"$option_images\">\n";
  foreach ($images as $image) {
    echo '<a href="' . $image->getImageLink() . '" title="' . htmlspecialchars($image->getTitle(), ENT_QUOTES) . "\">\n";
    echo '<img src="' . $image->getThumb() . "\"></a>\n";
  }
  echo "</div>\n";
}

/**
 * Prints the most popular images
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 */
function printPopularImages($number=5, $album='') {
  printImageStatistic($number, "popular",$album);
}

/**
 * Prints the latest images
 *
 * @param string $number the number of images to get
 * @param string $album title of an specific album
 */
function printLatestImages($number=5, $album='') {
  printImageStatistic($number, "latest", $album);
}

/**
* Returns  an array of album ids whose parent is the folder
 * @param string $albumfolder folder name if you want a album different >>from the current album
* @return array
*/
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

/**
* Returns a randomly selected image from the gallery. (May be NULL if none exists)
*
* @return object
*/
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

/**
* Returns  a randomly selected image from the album or its subalbums. (May be NULL if none exists)
*
* @return object
*/
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
/**
 * Puts up random image thumbs from the gallery
 *
 * @param int $number how many images
 * @param string $class optional class
 * @param string $option optional
 */
function printRandomImages($number=5, $class=null, $option='all') {
  if (!is_null($class)) {
    $class = 'class="' . $class . '";';
    echo "<ul".$class.">";
  }
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

/**
* Returns the rating of the designated image
*
* @param string $option 'totalvalue' or 'totalvotes'
* @param int $id Record id for the image
* @return int
*/
function getImageRating($option, $id) {
  return getRating($option,"image",$id);
}

/**
* Returns the average rating of the image
*
* @param int $id the id of the image
* @return real
*/
function getImageRatingCurrent($id) {
  $votes = getImageRating("totalvotes",$id);
  $value = getImageRating("totalvalue",$id);
  if($votes != 0)
  { $rating =  round($value/$votes, 1);
  }
  return $rating;
}


/**

* For internal use by the function printRating()
* Returns true if the IP has voted
*
* @param int $id the record ID of the image

* @param string $option 'image' or 'album' depending on the requestor
* @return bool
*/
function checkIp($id, $option) {
  $ip = $_SERVER['REMOTE_ADDR'];
  switch($option) {
    case "image":
      $dbtable = prefix('images');
      break;
    case "album":
      $dbtable = prefix('albums');
      break;
  }
  $ipcheck = query_full_array("SELECT used_ips FROM $dbtable WHERE used_ips LIKE '%".$ip."%' AND id= $id");
  return $ipcheck;
}

/**
* Prints the image rating information for the current image
*
*/
function printImageRating() {
  printRating("image");
}



/**
 * Prints the rating accordingly to option, it's a combined function for image and album rating
 *
 * @param string $option "image" for image rating, "album" for album rating.
 * @see printImageRating() and printAlbumRating()
 * 
 */
function printRating($option) {
  switch($option) {
    case "image":
      $id = getImageID();
      $value = getImageRating("totalvalue", $id);
      $votes = getImageRating("totalvotes", $id);
      break;
    case "album":
      $id = getAlbumID();
      $value = getAlbumRating("totalvalue", $id);
      $votes = getAlbumRating("totalvotes", $id);
      break;
  }
  if($votes != 0) { 
    $ratingpx = round(($value/$votes)*25);
  }
  $zenpath = WEBPATH."/".ZENFOLDER."/plugins";
  echo "<div id=\"rating\">\n";
  echo "<ul class=\"star-rating\">\n";
  echo "<li class=\"current-rating\" id=\"current-rating\" style=\"width:".$ratingpx."px\"></li>\n";
  if(!checkIP($id,$option)){
    echo "<li><a href=\"javascript:rate(1,$id,$votes,$value,'".rawurlencode($zenpath)."','$option')\" title=\"1 star out of 5\"' class=\"one-star\">2</a></li>\n";
    echo "<li><a href=\"javascript:rate(2,$id,$votes,$value,'".rawurlencode($zenpath)."','$option'')\" title=\"2 stars out of 5\" class=\"two-stars\">2</a></li>\n";
    echo "<li><a href=\"javascript:rate(3,$id,$votes,$value,'".rawurlencode($zenpath)."','$option')\" title=\"3 stars out of 5\" class=\"three-stars\">2</a></li>\n";
    echo "<li><a href=\"javascript:rate(4,$id,$votes,$value,'".rawurlencode($zenpath)."','$option')\" title=\"4 stars out of 5\" class=\"four-stars\">2</a></li>\n";
    echo "<li><a href=\"javascript:rate(5,$id,$votes,$value,'".rawurlencode($zenpath)."','$option')\" title=\"5 stars out of 5\" class=\"five-stars\">2</a></li>\n";
  }
  echo "</ul>\n";
  echo "<div id =\"vote\">\n";
  switch($option) {
    case "image":
      echo "Rating: ".getImageRatingCurrent($id)." (Total votes: ".$votes.")";
      break; 
    case "album":
      echo "Rating: ".getAlbumRatingCurrent($id)." (Total votes: ".$votes.")";
      break;
  }
  echo "</div>\n";
  echo "</div>\n"; 
}

/**
 * Get the rating for an image or album, 
 *
 * @param string $option 'totalvalue' or 'totalvotes' 
 * @param string $option2 'image' or 'album'
 * @param int $id id of the image or album
 * @see getImageRating() and getAlbumRating()
 * @return unknown
 */
function getRating($option,$option2,$id) {
  switch ($option) {
    case "totalvalue":
      $rating = "total_value"; break;
    case "totalvotes":
      $rating = "total_votes"; break;
  }  
  switch ($option2) {
    case "image":
      if(!$id) { 
          $id = getImageID(); 
      }  
      $dbtable = prefix('images');
      break; 
    case "album":
      if(!$id) { 
        $id = getAlbumID(); 
      }  
      $dbtable = prefix('albums');
      break;
  }
   $result = query_single_row("SELECT ".$rating." FROM $dbtable WHERE id = $id");
   return $result[$rating]; 
}

/**
 * Prints the image rating information for the current image 
 *
 */
function printAlbumRating() {
  printRating("album");
}

/**
* Returns the average rating of the album
*
* @param int $id Record id for the album
* @return real
*/
function getAlbumRatingCurrent($id) {
  $votes = getAlbumRating("totalvotes",$id);
  $value = getAlbumRating("totalvalue",$id);
  if($votes != 0)
  { $rating =  round($value/$votes, 1);
  }
  return $rating;
}


/**
* Returns the rating of the designated album
*
* @param string $option 'totalvalue' or 'totalvotes'
* @param int $id Record id for the album
* @return int
*/
function getAlbumRating($option, $id) {
  $rating =  getRating($option,"album",$id);
  return $rating;
}

/**
* Prints the n top rated images
*
* @param int $number The number if images desired
*/
function printTopRatedImages($number=5) {
  printImageStatistic($number, "toprated");
}


/**
* Prints the n most rated images
*
* @param int $number The number if images desired
*/
function printMostRatedImages($number=5) {
  printImageStatistic($number, "mostrated");
}


/**
 * Shortens a string to $length
 *
 * @param string $string the string to be shortened
 * @param int $length the desired length for the string
 * @return string
 */
function my_truncate_string($string, $length) {
  if (strlen($string) > $length) {
    $short = substr($string, 0, $length);
    return $short. '...';
  } else {
    return $string;
  }
}

/**
 * Returns a list of tags for either an image or album, depends on the page called from
 *
 * @return string
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
 * Prints a list of tags, editable by admin
 *
 * @param string $option links by default, if anything else the
 *               tags will not link to all other photos with the same tah
 * @param string $preText text to go before the printed tags
 * @param string $class css class to apply to the UL list
 * @param string $separator what charactor shall separate the tags
 * @param bool $editable true to allow admin to edit the tags
 * @since 1.1
 */
function printTags($option='links',$preText=NULL,$class='taglist',$separator=', ',$editable=TRUE) {
  $tags = getTags();
  $singletag = explode(",", $tags);
  if (empty($tags)) { $preText = ""; }
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
          $links1 = "<a href=\"".getSearchURL($singletag[$x], '', SEARCH_TAGS, 0, 0)."\" title=\"".$singletag[$x]."\" rel=\"nofollow\">";
          $links2 = "</a>";
        }
        echo "\t<li>".$links1.htmlspecialchars($singletag[$x], ENT_QUOTES).$links2.$separator."</li>\n";
      }
    }
    echo "</ul><br clear=\"all\" />\n";
  }
}

/**
 * Grabs the entire galleries tags
 *
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
 * Either prints all of the galleries tgs as a UL list or a cloud
 *
 * @param string $option "cloud" for tag cloud, "list" for simple list
 * @param string $class CSS class
 * @param string $sort "results" for relevance list, "abc" for alphabetical, blank for unsorted
 * @param bool $counter TRUE if you want the tag count within brackets behind the tag
 * @param bool $links text to go before the printed tags
 * @param int $maxfontsize largest font size the cloud should display
 * @param int $maxcount the maximum count for a tag to appear in the output
 * @param int $mincount the minimum count for a tag to appear in the output

 * @param int $limit set to limit the number of tags displayed to the top $numtags
 * @since 1.1
 */
function printAllTagsAs($option,$class='',$sort='abc',$counter=FALSE,$links=TRUE,$maxfontsize=2,$maxcount=50,$mincount=10, $limit=NULL) {
  define('MINFONTSIZE', 0.8);

  $option = strtolower($option);
  if ($class != "") { $class = "class=\"".$class."\""; }
  $tagcount = getAllTags();
  if (!is_array($tagcount)) { return false; }

  
  if (!is_null($limit)) {

    $tagcount = array_slice($tagcount, 0, $limit);

  }

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

    if ($option == "cloud") { // calculate font sizes, formula from wikipedia
      if ($val <= $mincount) {
        $size = MINFONTSIZE;  
      } else {
        $size = min(max(round(($maxfontsize*($val-$mincount))/($maxcount-$mincount), 2), MINFONTSIZE), $maxfontsize);
      }

      $size = " style=\"font-size:".$size."em;\"";

    } else {

      $size = '';
    }
    if ($val >= $mincount) {
      if(!$links) {
        echo "\t<li$size>".$key.$counter."</li>\n";
      } else {
        $key = str_replace('"', '', $key);
        echo "\t<li style=\"display:inline; list-style-type:none\"><a href=\"".
        getSearchURL($key, '', SEARCH_TAGS, 0, 0)."\"$size rel=\"nofollow\">".
        $key.$counter."</a></li>\n";
      }
    }

  } // while end
  echo "</ul>\n";
}
/**
 * Retrieves a list of all unique years & months
 *
 * @return array
 */
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
/**
 * Prints a compendum of dates and links to a search page that will show results of the date
 *
 * @param string $class optional class
 * @param string $yearid optional class for "year"
 * @param string $monthid optional class for "month"
 */
function printAllDates($class='archive', $yearid='year', $monthid='month') {
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
    echo "<li><a href=\"".getSearchURl('', substr($key, 0, 7), 0, 0)."\" rel=\"nofollow\">$month ($val)</a></li>\n";
  }
  echo "</ul>\n</li>\n</ul>\n";
}

/**
 * Produces the url to a custom page (e.g. one that is not album.php, image.php, or index.php)
 *
 * @param string $linktext Text for the URL
 * @param int $page page number to include in URL
 * @param string $q query string to add to url
 * @return string
 */
function getCustomPageURL($page, $q='') {
  if (getOption('mod_rewrite')) {
    $result .= WEBPATH."/page/$page";
    if (!empty($q)) { $result .= "?$q"; }
  } else {
    $result .= WEBPATH."/index.php?p=$page";
    if (!empty($q)) { $result .= "&$q"; }
  }
  return $result;
}

/**
 * Prints the url to a custom page (e.g. one that is not album.php, image.php, or index.php)
 *
 * @param string $linktext Text for the URL
 * @param int $page page number to include in URL
 * @param string $q query string to add to url
 * @param string $prev text to insert before the URL
 * @param string $next text to follow the URL
 * @param string $class optional class
 */
function printCustomPageURL($linktext, $page, $q='', $prev, $next, $class) {
  if (!is_null($class)) {
    $class = 'class="' . $class . '";';
  }
  echo $prev."<a href=\"".getCustomPageURL($page, $q)." $class \">$linktext</a>".$next;
}

/**
* Returns  the URL to an image
*
* @return string
*/
function getURL($image) {
  if (getOption('mod_rewrite')) {
    return WEBPATH . "/" . pathurlencode($image->getAlbumName()) . "/" . urlencode($image->name);
  } else {
    return WEBPATH . "/index.php?album=" . pathurlencode($image->getAlbumName()) . "&image=" . urlencode($image->name);
  }
}
/**
* Returns the record number of the album in the database
* @return int
*/
function getAlbumId() {
  global $_zp_current_album;
  if (!isset($_zp_current_album)) { return null; }
  return $_zp_current_album->getAlbumId();
}

/**
 * Prints a RSS link
 *
 * @param string $option type of RSS (Gallery, Album, Comments)
 * @param string $prev text to before before the link
 * @param string $linktext title of the link
 * @param string $next text to appear after the link
 * @param bool $printIcon print an RSS icon beside it? if true, the icon is zp-core/images/rss.gif
 * @param string $class css class
 * @since 1.1
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
 * Prints the RSS link for use in the HTML HEAD
 *
 * @param string $option type of RSS (Gallery, Album, Comments)
 * @param string $linktext title of the link
 * @since 1.1
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


//*** Search functions *******************************************************
//****************************************************************************

/**
 * Returns a search URL
 *
 * @param string $words the search words target
 * @param string $dates the dates that limit the search
 * @param int $fields the fields on which to search
 * @param int $page the page number for the URL
 * @return string
 * @since 1.1.3
 */
function getSearchURL($words, $dates, $fields, $page) {
  if ($mr = getOption('mod_rewrite')) {
    $url = WEBPATH."/page/search/";
  } else {
    $url = WEBPATH."/index.php?p=search";
  }

  if ($fields == (SEARCH_TITLE + SEARCH_DESC + SEARCH_TAGS + SEARCH_FILENAME +
                  SEARCH_LOCATION + SEARCH_CITY + SEARCH_STATE + SEARCH_COUNTRY + SEARCH_FOLDER)) { $fields = 0; }

  if (($fields == SEARCH_TAGS) && $mr ) {
    $url .= "tags/";
  }

  if (!empty($words)) {
    if($mr) {
      $url .= "$words";
    } else {
      $url .= "&words=$words";
    }
  }

  if (!empty($dates)) {
    if($mr) {
      $url .= "archive/$dates";
    } else {
      $url .= "&date=$dates";
    }
  }
  
  if ($page > 1) {
    if ($mr) {
      $url .= "/$page";
    } else {
      $url .= "&page=$page";
    }
  }
  if (($fields != 0) && ($fields != SEARCH_TAGS)) {
    if($mr) {
      $url .= "?searchfields=$fields";
    } else {
      $url .= "&searchfields=$fields";
    }
  }
  return $url;
}

/**
 * Prints the search form

 * 

 * Search works on a list of tags entered into the search form. Tags are separated by commas and

 * may contain spaces or any other character other than a comma or a peck mark. To include commas in

 * tag, enclose it in peck marks (`). To use peck marks in a search submit a feature request.
 *
 * @param string $prevtext text to go before the search form
 * @param bool $fieldSelect prints a drop down of searchable elements
 *        = 0 all fields
 *        = NULL means no drop-down selection
 * @param string $id css id for the search form, default is 'search'
 * @since 1.1.3
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
 * Returns the search results seperated by $separator
 *
 * @param string $separator what to put inbetween the search results, default ' | '
 * @return string
 * @since 1.1
 */
function getSearchWords($separator=' | ') {
  if (in_context(ZP_SEARCH)) {
    global $_zp_current_search;
    $tags = $_zp_current_search->getSearchString();
    return implode($separator, $tags);
  }
  return false;
}

/**
 * Returns the date of the search
 *
 * @param string $format formatting of the date, default 'F Y'
 * @return string
 * @since 1.1
 */
function getSearchDate($format='F Y') {
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

define("IMAGE", 1);
define("ALBUM", 2);
/**
 * Checks to see if comment posting is allowed for an image/album

 * Returns true if comment posting should be allowed
 *
 * @param int $what the degree of control desired allowed values: ALBUM, IMAGE, and ALBUM+IMAGE
 * @return bool 
 */
function OpenedForComments($what=3) {
  global $_zp_current_image, $_zp_current_album;
  $result = true;
  if (IMAGE & $what) { $result = $result && $_zp_current_image->getCommentsAllowed(); }
  if (ALBUM & $what) { $result = $result && $_zp_current_album->getCommentsAllowed(); }
  return $result;
}

/**
 * Finds the name of the themeColor option selected on the admin options tab

 * Returns  the css file name for the theme
 *
 * @param string $zenCSS path to the css file
 * @param string $themeColor name of the css file
 * @param string $defaultColor name of the default css file
 * @return string
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
 * Passed # of album columns, # of image columns of the theme.
 * Updates (non-persistent) images_per_page and albums_per_page so that the rows are filled.

 * Returns # of images that will go on the album/image transition page.
 *
 * @param int $albumColumns number of album columns on the page
 * @param int $imageColumns number of image columns on the page
 * @return int
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

//************************************************************************************************
// album password handling
//************************************************************************************************


/**
 * Checks to see if a password is needed
 * displays a password form if log-on is required

 * Returns true if a login form has been displayed

 * 

 * The password protection is hereditary. 

 * 

 * This normally only impacts direct url access to an album or image since if

 * you are going down the tree you will be stopped at the first place a password is required.

 * 

 * If the gallery is password protected then every album & image will require that password.

 * If an album is password protected then all subalbums and images treed below that album will require 

 * the password. If there are multiple passwords in the tree and you direct link, the password that is 

 * required will be that of the nearest parent that has a password. (The gallery is the ur-parrent to all

 * albums.)
 *
 * @param bool $silent set to true to inhibit the logon form
 * @return bool 
 * @since 1.1.3
 */
function checkforPassword($silent=false) {
  global $_zp_current_album, $_zp_current_search, $_zp_album_authorized, $_zp_gallery;
  if (ZP_loggedin()) { return false; }  // you're the admin, you don't need the passwords.
  if (in_context(ZP_SEARCH)) {  // search page
    $hash = getOption('search_password');
    $hint = getOption('search_hint');
    if (empty($hash)) {
      $hash = getOption('gallery_password');
      $hint = getOption('gallery_hint');
    }
    if (!empty($hash)) {
      if ($_zp_album_authorized != $hash) {
        if (!$silent) {
          printPasswordForm($hint);
        }
        return true;
      }
    }
  } else if (isset($_GET['album'])) {  // album page
    $album = new album($_zp_gallery, $_GET['album']);
    $hash = $album->getPassword();
    $hint = $album->getPasswordHint();

    if (empty($hash)) {
      $album = $album->getParent();
      while (!is_null($album)) {
        $hash = $album->getPassword();
        if (!empty($hash)) { //whoo, sneeky url jump,
          $hint = $album->getPasswordHint();
          if ($_zp_album_authorized == $hash) {
            return false;
          } else {
            if (!$silent) {
              printPasswordForm($hint);
            }
            return true;
          }
        }
        $album = $album->getParent();
      }
      // revert all tlhe way to the gallery
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
    } else {
      if ($_zp_album_authorized != $hash) {
        if (!$silent) {
          printPasswordForm($hint);
        }
        return true;
      }
    }
  } else {  // index page
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
 * Prints the album password form
 * 
 * @param $hint hint to the password
 *
 *@since 1.1.3
 */
function printPasswordForm($hint) {
  global $_zp_login_error, $_zp_password_form_printed, $_zp_current_search;
  if ($_zp_password_form_printed) { return; }
  $_zp_password_form_printed = true;
  if ($_zp_login_error) {
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

/**
 * Simple captcha for comments
 * thanks to gregb34 who posted the original code
 *
 * Prints a captcha entry form and posts the input with the comment posts
 * @param string $preText lead-in text
 * @param string $midText text that goes between the captcha image and the input field
 * @param string $postText text that closes the captcha
 * @param int $size the text-width of the input field
 * @since 1.1.4
 **/
function printCaptcha($preText='', $midText='', $postText='', $size=4) {
  $lettre='abcdefghijklmnpqrstuvwxyz';
  $chiffre='123456789';

  $lettre1=$lettre[rand(0,24)];
  $lettre2=$lettre[rand(0,24)];
  $chiffre1=$chiffre[rand(0,8)];
  if (rand(0,1)) {
    $string = $lettre1.$lettre2.$chiffre1;
  } else {
    $string = $lettre1.$chiffre1.$lettre2;
  }
  $code=md5($string);

  //header ("Content-type: image/png");
  $image = imagecreate(65,20);

  $fond = imagecolorallocate($image, 255, 255, 255);
  ImageFill ($image,65,20, $fond);

  $ligne = imagecolorallocate($image,150,150,150);

  $i = 7;
  while($i<=15) {
    ImageLine($image, 0,$i, 65,$i, $ligne);
    $i = $i+7;
  }

  $i = 10;
  while($i<=65) {
    ImageLine($image,$i,0,$i,20, $ligne);
    $i = $i+10;
  }

  $lettre = imagecolorallocate($image,0,0,0);
  imagestring($image,10,5+rand(0,6),0,substr($string, 0, 1),$lettre);
  imagestring($image,10,20+rand(0,6),0,substr($string, 1, 1),$lettre);
  imagestring($image,10,35+rand(0,6),0,substr($string, 2, 1),$lettre);

  $rectangle = imagecolorallocate($image,48,57,85);
  ImageRectangle ($image,0,0,64,19,$rectangle);

  $img = "code_" . $code . ".png";

  imagepng($image, SERVERCACHE . "/" . $img);

  $inputBox =  "<input type=\"text\" id=\"code\" name=\"code\" size=\"" . $size . "\" class=\"inputbox\" />";
  $captcha = "<input type=\"hidden\" name=\"code_h\" value=\"" . $code . "\"/>" .
             "<label for=\"code\"><img src=\"" . WEBPATH . "/cache/". $img . "\" alt=\"Code\"/></label>&nbsp;";

  echo $preText;
  echo $captcha;
  echo $midText;
  echo $inputBox;
  echo $postText;
}

/*** End template functions ***/

?>