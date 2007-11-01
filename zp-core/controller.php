<?php

/*** controller.php ************************************************************
 * Root-level include that handles all user requests.
 ******************************************************************************/
 
require_once('functions-controller.php');


// Initialize the global objects and object arrays:
$_zp_gallery = new Gallery();
if($apiKey = $_zp_gallery->getOption('gmaps_apikey')){ 
  $_zp_phoogle = new PhoogleMapLite();
  $_zp_phoogle->setAPIkey($apiKey);
}
$_zp_gallery->setOptionDefault('spam_filter', 'none');
$_zp_gallery->setOptionDefault('email_new_comments', 1);
$_zp_gallery->setOptionDefault('gallery_sorttype', 'Manual');

if (!file_exists(getAlbumFolder() . 'videoDefault.png')) { copy(SERVERPATH . '/' . ZENFOLDER . '/images/videoDefault.png',  getAlbumFolder() . 'videoDefault.png'); }
if (!file_exists(getAlbumFolder() . 'zen-logo.jpg')) { copy(SERVERPATH . '/' . ZENFOLDER . '/images/zen-logo.jpg',  getAlbumFolder() . 'zen-logo.jpg'); } 

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
$_zp_current_search = NULL;
$_zp_current_search_restore = NULL;


/*** Request Handler **********************
 ******************************************/
// This is the main top-level action handler for user requests. It parses a
// request, validates the input, loads the appropriate objects, and sets
// the context. All that is done in functions-controller.php.

// Handle the request for an image or album.
zp_load_request();

// Handle any comments that might be posted.
zp_handle_comment();



/*** Server-side AJAX Functions ***********
 ******************************************/
// These handle asynchronous requests from the client for updating the 
// title and description, but only if the user is logged in.

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
  
  function saveTags($newtags) {
    if (get_magic_quotes_gpc()) $newtags = stripslashes($newtags);
    global $_zp_current_image, $_zp_current_album;
    if (in_context(ZP_IMAGE)) {
      $_zp_current_image->setTags($newtags);
      $_zp_current_image->save();
      return $newtags;
    } else if (in_context(ZP_ALBUM)) {
      $_zp_current_album->setTags($newtags);
      $_zp_current_album->save();
      return $newtags;
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
  sajax_export("saveTags");
  sajax_export("saveDesc");
  sajax_handle_client_request();
}



/*** Consistent URL redirection ***********
 ******************************************/
// Check to see if we use mod_rewrite, but got a query-string request for a page.
// If so, redirect with a 301 to the correct URL. This must come AFTER the Ajax init above,
// and is mostly helpful for SEO, but also for users. Consistent URLs are a Good Thing.

fix_path_redirect();



?>
