<?php

/*** controller.php ************************************************************
 * Root-level include that handles all user requests.
 ******************************************************************************/
 
require_once('functions-controller.php');


// Initialize the global objects and object arrays:
$_zp_gallery = new Gallery();
if($apiKey = getOption('gmaps_apikey')){ 
  $_zp_phoogle = new PhoogleMapLite();
  $_zp_phoogle->setAPIkey($apiKey);
}

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

/*** Captcha cleanup **********************
******************************************/
if (getOption('Use_Captcha')) {
  $d = getOption('last_captcha_purge');
  $expire = time() - 86400;
  if ($d > $expire) {
    chdir(SERVERCACHE . "/");
    $filelist = glob('code_*.png');
    if ($filelist) {
      foreach ($filelist as $file) {
        $file = SERVERCACHE . "/" . $file;
        if (filemtime($file) < $expire) {
          unlink($file);
        }
      }
    }
  }
  setOption('last_captcha_purge', time());
}

/*** check validity of mod_rewrite  *******
if (getOption('mod_rewrite')) {
  $htfile = '.htaccess';
  $ht = @file_get_contents($htfile);
  $htu = strtoupper($ht);
  $i = strpos($htu, 'REWRITEENGINE');
  if ($i === false) {
    $rw = '';
  } else {
    $j = strpos($htu, "\n", $i+13);
    $rw = trim(substr($htu, $i+13, $j-$i-13));
  }
  if ($rw != 'ON') {
    setOption('mod_rewrite', 0);
    zp_error("The <em>.htaccess</em> Rewrite Engine is not on. <em>mod_rewrite</em> has been reset. Refresh your browser to continue.");
    exit();
  } else {
    $d = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $i = strpos($htu, 'REWRITEBASE', $j);
    if ($i === false) {
      $base = false;
    } else {
      $j = strpos($htu, "\n", $i+11);
      $b = trim(substr($ht, $i+11, $j-$i-11));
      $base = ($b == $d);
    }
    if (!$base) {
      setOption('mod_rewrite', 0);
      zp_error("Your rewrite base is not correct. <em>mod_rewrite</em> has been reset. Perhaps you should run " .
               "<a href=\"".WEBPATH.ZENFOLDER."/setup.php\">Setup</a>. Refresh your browser to continue.");
      exit();
    }
  }
}
*** end of check for mod_rewrite ***********/

/*** Request Handler **********************
 ******************************************/
// This is the main top-level action handler for user requests. It parses a
// request, validates the input, loads the appropriate objects, and sets
// the context. All that is done in functions-controller.php.

// Handle the request for an image or album.
zp_load_request();

// handle any album passwords that might have been posted
zp_handle_password();

// Handle any comments that might be posted.
$_zp_comment_error = zp_handle_comment();


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
