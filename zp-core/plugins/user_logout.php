<?php
/**
 * Provides a link so that users who have logged into zenphoto can logout.
 *
 * Place a call on printUserLogout() where you want the logout link to appear.
 *
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */

$plugin_description = gettext("Provides a means for placing a user logout link on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---user_logout.php.html";

$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }
$saved_auth = NULL;

if (!OFFSET_PATH) {
	if (zp_loggedin()) {
		$authType = 'zenphoto_auth';
	} else {
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
					// revert all the way to the gallery
					$authType = 'zp_gallery_auth';
				}
			}
		} else {  // index page
			$authType = 'zp_gallery_auth';
		}
	}
	$saved_auth = zp_getCookie($authType);
	if (isset($_GET['userlog'])) { // process the logout.
		if ($_GET['userlog'] == 0) {
			$saved_auth = NULL;
			zp_setcookie($authType, "", time()-368000, $cookiepath);
			if ($authType == 'zenphoto_auth') {
				$_zp_loggedin = false;
			}
		}
	}
}

/**
 * Prints the logout link if the user is logged in.
 * This is for album passwords only, not admin users;
 *
 * @param string $before before text
 * @param string $after after text
 * @param bool $showLoginForm set to true to display a login form if no one is logged in
 */
function printUserLogout($before='', $after='', $showLoginForm=false) {
	global $saved_auth;
	if ($showLoginForm) {
		$showLoginForm = !checkforPassword(true);
	}
	if (empty($saved_auth)) {
		printPasswordForm('', false);
	} else {
		echo $before.'<a href="?userlog=0" title="'.gettext("logout").'" >'.gettext("logout").'</a>'.$after;
	}
}

?>