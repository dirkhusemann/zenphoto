<?php
/*
 * Provides a link so that users who have logged into zenphoto can logout.
 *
 * Place a call on printUserLogout() where you want the logout link to appear.
 *
 */

$plugin_description = gettext("Provides a means for placing a user logout link on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---user_logout.php.html";

$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }

if (in_context(ZP_SEARCH)) {  // search page
	$authType = 'zp_search_auth';
	$check_auth = getOption('search_password');
	if (empty($check_auth)) {
		$authType = 'zp_gallery_auth';
	}
} else if (in_context(ZP_ALBUM)) { // album page
	$authType = "zp_album_auth_" . cookiecode($_zp_current_album->name);
	$check_auth = $_zp_current_album->getPassword();
	if (empty($check_auth)) {
		$parent = $_zp_current_album->getParent();
		while (!is_null($parent)) {
			$authType = "zp_album_auth_" . cookiecode($parent->name);
			$check_auth = $parent->getPassword();
			if (!empty($check_auth)) { break; }
			$parent = $parent->getParent();
		}
		if (empty($check_auth)) {
			// revert all tlhe way to the gallery
			$authType = 'zp_gallery_auth';
		}
	}
} else {  // index page
	$authType = 'zp_gallery_auth';
}
$saved_auth = zp_getCookie($authType);
if (isset($_GET['userlogout']) && !empty($saved_auth)) {
	zp_setcookie($authType, "", time()-368000, $cookiepath);
}

/**
 * Prints the logout link if the user is logged in.
 * This is for album passwords only, not admin users;
 *
 * @param string $before begore text
 * @param string $after after text
 */
function printUserLogout($before='', $after='') {
	global $saved_auth;
	if (!empty($saved_auth)) {
		echo $before.'<a href="?userlogout='."'true'\"".' title="'.gettext("logout").'" >'.gettext("logout").'</a>'.$after;
	}
}

?>