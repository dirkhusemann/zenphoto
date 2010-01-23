<?php
/**
 * Provides users then means to  log in or out from the theme pages.
 *
 * Place a call on printUserLogin_out() where you want the logout link to appear.
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_description = gettext("Provides a means for placing a user login form or logout link on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---user_login-out.php.html";
$option_interface = new user_logout_options();

/**
 * Plugin option handling class
 *
 */
class user_logout_options {

	function user_logout_options() {
		setOptionDefault('user_logout_login_form', 0);
	}

	function getOptionsSupported() {
		return array(	gettext('Enable login form') => array('key' => 'user_logout_login_form', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If enabled, a login form will be displayed if the viewer is not logged in.'))
		);
	}
	function handleOption($option, $currentValue) {
	}
}


$__redirect = '';
if (isset($_GET['p'])) { $__redirect .= "&p=" . $_GET['p']; }
if (isset($_GET['searchfields'])) { $__redirect .= "&searchfields=" . $_GET['searchfields']; }
if (isset($_GET['words'])) { $__redirect .= "&words=" . $_GET['words']; }
if (isset($_GET['date'])) { $__redirect .= "&date=" . $_GET['date']; }
if (isset($_GET['album'])) { $__redirect .= "&album=" . $_GET['album']; }
if (isset($_GET['image'])) { $__redirect .= "&image=" . $_GET['image']; }
if (isset($_GET['title'])) { $__redirect .= "&title=" . $_GET['title']; }
if (isset($_GET['page'])) { $__redirect .= "&page=" . $_GET['page']; }

if (!OFFSET_PATH) {
	$cookies = array();
	$candidate = array();
	if (isset($_COOKIE)) $candidate = $_COOKIE;
	if (isset($_SESSION)) $candidate = Array_merge($candidate, $_SESSION);
	$candidate = array_unique($candidate);
	foreach ($candidate as $cookie=>$value) {
		if ($cookie == 'zenphoto_auth' || $cookie == 'zp_gallery_auth' || $cookie == 'zp_search_auth' || $cookie == 'zp_image_auth' || strpos($cookie, 'zp_album_auth_') !== false) {
			$cookies[] = $cookie;
		}
	}
	
	if (isset($_GET['userlog'])) { // process the logout.
		if ($_GET['userlog'] == 0) {
			foreach($cookies as $cookie) {
				zp_setcookie($cookie, "", time()-368000);
			}
			$_zp_loggedin = false;
			$saved_auth = NULL;
			$cookies = array();
			$_zp_pre_authorization = array();
			if (!empty($__redirect)) $__redirect = '?'.substr($__redirect, 1);
			header("Location: " . FULLWEBPATH . '/index.php'. $__redirect);
			exit();
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
 * @param string $logouttext optional replacement text for "Logout"
 */
function printUserLogin_out($before='', $after='', $showLoginForm=false, $logouttext=NULL) {
	global $cookies, $__redirect;
	if (is_null($logouttext)) $logouttext = gettext("Logout");
	if ($showLoginForm || getOption('user_logout_login_form')) {
		$showLoginForm = !checkforPassword(true);
	}
	if (empty($cookies)) {
		if ($showLoginForm) {
			?>
			<div class="passwordform">
			<?php
			printPasswordForm('', false);
			?>
			</div>
			<?php
		}
	} else {
		echo $before.'<a href="?userlog=0'.htmlspecialchars($__redirect).'" title="'.$logouttext.'" >'.$logouttext.'</a>'.$after;
	}
}

?>