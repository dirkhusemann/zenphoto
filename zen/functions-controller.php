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
 */
function getURL($with_rewrite=NULL, $album=NULL, $image=NULL, $page=NULL) {
  global $_zp_current_album, $_zp_current_image, $_zp_page;
  // Set defaults
  if ($with_rewrite === NULL)  $with_rewrite = zp_conf('mod_rewrite');
  if (!$album)  $album = $_zp_current_album;
  if (!$image)  $image = $_zp_current_image;
  if (!$page)   $page = $_zp_page;

  $url = '';
  if ($with_rewrite) {
    if (in_context(ZP_IMAGE)) {
      $url = pathurlencode($album->name) . '/' . $image->name . im_suffix();
    } else if (in_context(ZP_ALBUM)) {
      $url = pathurlencode($album->name) . ($page > 1 ? '/page/'.$page : '');
    } else if (in_context(ZP_INDEX)) {
      $url = ($page > 1 ? 'page/' . $page : '');
    }
  } else {
    if (in_context(ZP_IMAGE)) {
      $url = 'index.php?album=' . pathurlencode($album->name) . '&image='. $image->name;
    } else if (in_context(ZP_ALBUM)) {
      $url = 'index.php?album=' . pathurlencode($album->name) . ($page > 1 ? '&page='.$page : '');
    } else if (in_context(ZP_INDEX)) {
      $url = 'index.php' . ($page > 1 ? '?page='.$page : '');
    }
  }
  return $url;
}


/**
 * Checks to see if the current URL matches the correct one, redirects to the
 * corrected URL if not.
 */
function fix_path_redirect() {
  if (zp_conf('mod_rewrite')
      && (is_query_request() || (in_context(ZP_IMAGE) 
      && substr($_SERVER['REQUEST_URI'], -strlen(im_suffix())) != im_suffix()) )) {
    $redirecturl = getURL(true);
    $path = urldecode(substr($_SERVER['REQUEST_URI'], strlen(WEBPATH)+1));
    $path = preg_replace(array('/\/*$/'), '', $path);
    if (strlen($redirecturl) > 0 && $redirecturl != $path) {
      header("HTTP/1.0 301 Moved Permanently");
      header('Location: ' . FULLWEBPATH . '/' . $redirecturl);
      exit;
    }
  }
}

?>
