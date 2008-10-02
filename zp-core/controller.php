<?php

/**
 * controller.php
 * Root-level include that handles all user requests.
 * @package core
 */

// force UTF-8 Ø


require_once('functions-controller.php');


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
$_zp_current_search = NULL;
$_zp_pre_authorization = array();

// load the class plugins
foreach (getEnabledPlugins() as $extension) {
	if (strpos($extension, 'class-') !== false) {
		require_once(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . $extension);
	}
}

/*** Request Handler **********************
 ******************************************/
// This is the main top-level action handler for user requests. It parses a
// request, validates the input, loads the appropriate objects, and sets
// the context. All that is done in functions-controller.php.

// Handle the request for an image or album.
$zp_request = zp_load_request();

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
		$newtitle = sanitize($newtitle, 2);
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
		$newtags = sanitize($newtags, 3);
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
		$newdesc = sanitize($newdesc, 1);
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
	require_once("lib-sajax.php");
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
