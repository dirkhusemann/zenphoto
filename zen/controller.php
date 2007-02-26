<?php

/*** controller.php ************************************************************
 * Root-level include that handles all user requests.
 ******************************************************************************/
 
require_once('functions-controller.php');


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


/*** Request Handler **********************/
/******************************************/
// This is the main top-level action handler for user requests. It parses a
// request, validates the input, loads the appropriate objects, and sets
// the context. 

// rewrite_get_album_image() parses the album and image from the requested URL
// if mod_rewrite is on, and replaces the query variables with corrected ones.
// This is because of bugs in mod_rewrite that disallow certain characters.
list($ralbum, $rimage) = rewrite_get_album_image('album','image');
if (!empty($ralbum)) $_GET['album'] = $ralbum;
if (!empty($rimage)) $_GET['image'] = $rimage;

// Parse the GET request to see what's requested
// TODO: Refactor into functions for each context (load_album, load_image, etc).
if (isset($_GET['album'])) {
  $g_album = sanitize($_GET['album']);
  // Defense against upward folder traversal, empty albums, extra characters, etc.
  $g_album = preg_replace(array('/^\/+/','/\/+$/','/\/\/+/','/\.\.+/'), '', $g_album);

  if (isset($_GET['image'])) {
    $g_image = sanitize($_GET['image']);

    $_zp_current_context = ZP_IMAGE | ZP_ALBUM | ZP_INDEX;

    // An image page. Instantiate objects.
    $_zp_current_album = new Album($_zp_gallery, $g_album);
    $_zp_current_image = new Image($_zp_current_album, $g_image);

    // TODO: Better error handling than this.
    if (!$_zp_current_album->exists) {
      die('<b>Zenphoto error:</b> album does not exist.');
    } else if (!$_zp_current_image->exists) {
      die('<b>Zenphoto error:</b> image does not exist.');
    }
    
    //// Comment form handling.
    // TODO: This needs to be a function add_comment(...)
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
          $g_album = pathurlencode($g_album); 
          $g_image = urlencode($g_image);
          header("Location: " . FULLWEBPATH . "/" . 
            (zp_conf('mod_rewrite') ? $g_album .'/'.$g_image.im_suffix() : "index.php?album=$g_album&image=$g_image"));
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



/*** Server-side AJAX Functions ***********/
/******************************************/

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



/*** Consistent URL redirection ***********/
/******************************************/
// Check to see if we use mod_rewrite, but got a query-string request for a page.
// If so, redirect with a 301 to the correct URL. This must come AFTER the Ajax init above,
// and is mostly helpful for SEO, but also for users. Consistent URLs are a Good Thing.

fix_path_redirect();



?>
