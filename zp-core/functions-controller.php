<?php

/*** functions-controller.php **************************************************
 * Common functions used in the controller for getting/setting current classes,
 * redirecting URLs, and working with the context.
 ******************************************************************************/


/*** Context Manipulation Functions *******/
/******************************************/

/* Contexts are simply constants that tell us what variables are available to us
 * at any given time. They should be set and unset with those variables.
 */
 
// Contexts (Bitwise and combinable)
define("ZP_INDEX",   1);
define("ZP_ALBUM",   2);
define("ZP_IMAGE",   4);
define("ZP_COMMENT", 8);
define("ZP_GROUP",  16);
define("ZP_SEARCH", 32);

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


function im_suffix() { 
  return zp_conf('mod_rewrite_image_suffix'); 
}


// Determines if this request used a query string (as opposed to mod_rewrite).
// A valid encoded URL is only allowed to have one question mark: for a query string.
function is_query_request() {
  return (strpos($_SERVER['REQUEST_URI'], '?') !== false);
}


/**
 * Returns the URL of any main page (image/album/page#/etc.) in any form
 * desired (rewrite or query-string).
 * @param $with_rewrite boolean or null, whether the returned path should be in rewrite form.
 *   Defaults to null, meaning use the mod_rewrite configuration to decide.
 * @param $album : the Album object to use in the path. Defaults to the current album (if null).
 * @param $image : the Image object to use in the path. Defaults to the current image (if null).
 * @param $page : the page number to use in the path. Defaults to the current page (if null).
 */
function zpurl($with_rewrite=NULL, $album=NULL, $image=NULL, $page=NULL) {
  global $_zp_current_album, $_zp_current_image, $_zp_page;
  // Set defaults
  if ($with_rewrite === NULL)  $with_rewrite = zp_conf('mod_rewrite');
  if (!$album)  $album = $_zp_current_album;
  if (!$image)  $image = $_zp_current_image;
  if (!$page)   $page  = $_zp_page;

  $url = '';
  if ($with_rewrite) {
    if (in_context(ZP_IMAGE)) {
      $url = pathurlencode($album->name) . '/' . rawurlencode($image->filename . im_suffix());
    } else if (in_context(ZP_ALBUM)) {
      $url = pathurlencode($album->name) . ($page > 1 ? '/page/'.$page : '');
    } else if (in_context(ZP_INDEX)) {
      $url = ($page > 1 ? 'page/' . $page : '');
    }
  } else {
    if (in_context(ZP_IMAGE)) {
      $url = 'index.php?album=' . pathurlencode($album->name) . '&image='. rawurlencode($image->filename);
    } else if (in_context(ZP_ALBUM)) {
      $url = 'index.php?album=' . pathurlencode($album->name) . ($page > 1 ? '&page='.$page : '');
    } else if (in_context(ZP_INDEX)) {
      $url = 'index.php' . ($page > 1 ? '?page='.$page : '');
    }
  }
  if ($url == im_suffix() || empty($url)) { $url = ''; }
  return $url;
}


/**
 * Checks to see if the current URL matches the correct one, redirects to the
 * corrected URL if not with a 301 Moved Permanently.
 */
function fix_path_redirect() {
  if (zp_conf('mod_rewrite') && strlen(im_suffix()) > 0
      && in_context(ZP_IMAGE) && substr($_SERVER['REQUEST_URI'], -strlen(im_suffix())) != im_suffix() ) {
    $redirecturl = zpurl(true);
    header("HTTP/1.0 301 Moved Permanently");
    header('Location: ' . FULLWEBPATH . '/' . $redirecturl);
    exit;
  }
}


/******************************************************************************
 ***** Action Handling and context data loading functions *********************
 ******************************************************************************/
 
function zp_handle_comment() {
  global $_zp_current_image, $stored, $error;
  if (isset($_POST['comment'])) {
    if (in_context(ZP_IMAGE) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['comment'])) {
      if (isset($_POST['website'])) $website = strip_tags($_POST['website']); else $website = "";
      $commentadded = $_zp_current_image->addComment(strip_tags($_POST['name']), strip_tags($_POST['email']), 
          $website, kses($_POST['comment'], zp_conf('allowed_tags')));
      if ($commentadded == 2) {
        unset($error);
        if (isset($_POST['remember'])) {
          // Should always re-cookie to update info in case it's changed...
          $info = array(strip($_POST['name']), strip($_POST['email']), strip($website));
          setcookie('zenphoto', implode('|~*~|', $info), time()+5184000, '/');
        } else {
          setcookie('zenphoto', '', time()-368000, '/');
        }
        // Redirect to this image page to prevent re-submission. 
        header('Location: ' . FULLWEBPATH . '/' . zpurl());
        exit;
      } else {
        $stored = array($_POST['name'], $_POST['email'], $website, $_POST['comment'], false);
        if (isset($_POST['remember'])) $stored[3] = true;
        $error = 1 + $commentadded;
      }
    }
  } else if (isset($_COOKIE['zenphoto'])) {
    // Comment form was not submitted; get the saved info from the cookie.
    $stored = explode('|~*~|', stripslashes($_COOKIE['zenphoto'])); $stored[] = true;
  } else {
    $stored = array('','','', false); 
  }
  return $error;
}

function zp_load_page($pagenum=NULL) {
  global $_zp_page;
  if (!is_numeric($pagenum)) {
    $_zp_page = isset($_GET['page']) ? $_GET['page'] : 1;
  } else {
    $_zp_page = round($pagenum);
  }
}


/**
 * Loads the gallery if it hasn't already been loaded. This function doesn't
 * really do anything, since the gallery is always loaded in init...
 */
function zp_load_gallery() {
  global $_zp_gallery;
  if ($_zp_gallery == NULL)
    $_zp_gallery = new Gallery();
  set_context(ZP_INDEX);
  return $_zp_gallery;
}

/**
 * Loads the search object if it hasn't already been loaded. 
 */
function zp_load_search() {
  global $_zp_current_search;
  if ($_zp_current_search == NULL)
    $_zp_current_search = new SearchEngine();
  set_context(ZP_INDEX | ZP_SEARCH);
  return $_zp_current_search;
}

/**
 * zp_load_album - loads the album given by the folder name $folder into the 
 * global context, and sets the context appropriately.
 * @param $folder the folder name of the album to load. Ex: 'testalbum', 'test/subalbum', etc.
 * @param $force_cache whether to force the use of the global object cache.
 * @return the loaded album object on success, or (===false) on failure.
 */
function zp_load_album($folder, $force_nocache=false) {
  global $_zp_current_album, $_zp_gallery;
  $_zp_current_album = new Album($_zp_gallery, $folder, !$force_nocache);
  if (!$_zp_current_album->exists) return false;
  set_context(ZP_ALBUM | ZP_INDEX);
  return $_zp_current_album;
}

/**
 * zp_load_image - loads the image given by the $folder and $filename into the
 * global context, and sets the context appropriately.
 * @param $folder is the folder name of the album this image is in. Ex: 'testalbum'
 * @param $filename is the filename of the image to load.
 * @return the loaded album object on success, or (===false) on failure.
 */
function zp_load_image($folder, $filename) {
  global $_zp_current_image, $_zp_current_album;
  if ($_zp_current_album == NULL || $_zp_current_album->name != $folder)
    $album = zp_load_album($folder);
  $_zp_current_image = new Image($album, $filename);
  if (!$_zp_current_image->exists) return false;
  set_context(ZP_IMAGE | ZP_ALBUM | ZP_INDEX);
  return $_zp_current_image;
}


function zp_load_request() {
  list($album, $image) = rewrite_get_album_image('album','image');
  zp_load_page();
  $success = true;
  if (!empty($image)) {
    $success = zp_load_image($album, $image);
  } else if (!empty($album)) {
    $success = zp_load_album($album);
  }
  // Error message for objects not found.
  if ($success === false) {
    // Replace this with a redirect to an error page in the theme if it exists, or a default ZP error page.
    echo "<strong>Zenphoto Error:</strong> the requested object (album=" . $album . ': image=' . $image . ") was not found. Please go back and try again.";
    exit();
  }
}


?>