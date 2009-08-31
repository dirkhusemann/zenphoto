<?php
/**
 * processes the authorization (or login) of admin users
 * @package admin
 */

// force UTF-8 Ø

if (file_exists(dirname(__FILE__).'/lib-auth_custom.php')) { // load a custom authroization package if it is present
	require_once(dirname(__FILE__).'/lib-auth_custom.php');
} else {
	require_once(dirname(__FILE__).'/lib-auth.php');
}

// If the auth variable gets set somehow before this, get rid of it.
$_zp_loggedin = false;
$_zp_reset_admin = NULL;
// Fix the cookie's path for root installs.
$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }
if (isset($_GET['ticket'])) { // password reset query
	$_zp_ticket = $_GET['ticket'];
	$post_user = $_GET['user'];
	$admins = getAdministrators();
	foreach ($admins as $tuser) {
		if ($tuser['user'] == $post_user && !empty($tuser['email'])) {
			$admin = $tuser;
			$_zp_request_date = getOption('admin_reset_date');
			$adm = $admin['user'];
			$pas = $admin['pass'];
			$ref = md5($_zp_request_date . $adm . $pas);
			if ($ref === $_zp_ticket) {
				if (time() <= ($_zp_request_date + (3 * 24 * 60 * 60))) { // you have one week to use the request
					setOption('admin_reset_date', NULL);
					$_zp_reset_admin = $tuser;
				}
			}
			break;
		}
	}
}

if (!isset($_POST['login'])) {
	$_zp_loggedin = checkAuthorization(zp_getCookie('zenphoto_auth'));
	if (!$_zp_loggedin) {
		// Clear the cookie
		zp_setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
	}
} else {
	// Handle the login form.
	if (isset($_POST['login']) && isset($_POST['user']) && isset($_POST['pass'])) {
		$post_user = $_POST['user'];
		$post_pass = $_POST['pass'];
		$redirect = sanitize_path($_POST['redirect']);
		$_zp_loggedin = checkLogon($post_user, $post_pass, true);
		$_zp_loggedin = zp_apply_filter('admin_login_attempt', $_zp_loggedin, $post_user, $post_pass);
		if ($_zp_loggedin) {
			zp_setcookie("zenphoto_auth", passwordHash($post_user, $post_pass), time()+COOKIE_PESISTENCE, $cookiepath);
			if (!empty($redirect)) { header("Location: " . FULLWEBPATH . '/'. $redirect); }
		} else {
			// Clear the cookie, just in case
			zp_setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
			// was it a request for a reset?
			if (isset($_POST['code_h']) && $_zp_captcha->checkCaptcha(trim($post_pass), sanitize($_POST['code_h'],3))) {
				require_once(dirname(__FILE__).'/class-load.php'); // be sure that the plugins are loaded for the mail handler		
				if (empty($post_user)) {
					$requestor = gettext('You are receiving this e-mail because of a password reset request on your Zenphoto gallery.');
				} else {
					$requestor = sprintf(gettext("You are receiving this e-mail because of a password reset request on your Zenphoto gallery from a user who tried to log in as %s."),$post_user);
				}
				$admins = getAdministrators();
				$mails = array();	
				$user = NULL;
				if (!empty($post_user)) {
					$user = null;
					foreach ($admins as $tuser) {
						if ($tuser['user'] == $post_user && !empty($tuser['email'])) {
							$name = $tuser['name'];
							if (empty($name)) {
								$name = $tuser['user'];
							}
							$mails[$name] = $tuser['email'];
							$user = $tuser;
							break;
						}
					}
				}
				$tuser = array_shift($admins);
				reset($mails);
				if ($tuser['email'] && count($mails) == 0 || current($mails) != $tuser['email']) {
					$name = $tuser['name'];
					if (empty($name)) {
							$name = $tuser['user'];
					}
					$mails[$name] = $tuser['email'];
					if (is_null($user)) {
						$user = $tuser;
					}
				}
				$adm = $user['user'];
				$pas = $user['pass'];
				setOption('admin_reset_date', time());
				$req = getOption('admin_reset_date');
				$ref = md5($req . $adm . $pas);
				$msg = "\n".$requestor.
						"\n".sprintf(gettext("To reset your Zenphoto Admin passwords visit: %s"),FULLWEBPATH."/".ZENFOLDER."/admin-options.php?ticket=$ref&user=$adm") .
						"\n".gettext("If you do not wish to reset your passwords just ignore this message. This ticket will automatically expire in 3 days.");
				$err_msg = zp_mail(gettext("The Zenphoto information you requested"),  $msg, NULL, NULL, $mails);
				if (empty($err_msg)) {
					$_zp_login_error = 2;
				} else {
					$_zp_login_error = $err_msg;
				}
			} else {
				$_zp_login_error = 1;
			}
		}
	}
}
unset($saved_auth, $check_auth, $user, $pass);
// Handle a logout action.
if (isset($_REQUEST['logout'])) {
	zp_setcookie("zenphoto_auth", "*", time()-368000, $cookiepath);
	$redirect = '';
	if (isset($_GET['p'])) { $redirect .= "&p=" . $_GET['p']; }
	if (isset($_GET['searchfields'])) { $redirect .= "&searchfields=" . $_GET['searchfields']; }
	if (isset($_GET['words'])) { $redirect .= "&words=" . $_GET['words']; }
	if (isset($_GET['date'])) { $redirect .= "&date=" . $_GET['date']; }
	if (isset($_GET['album'])) { $redirect .= "&album=" . $_GET['album']; }
	if (isset($_GET['image'])) { $redirect .= "&image=" . $_GET['image']; }
	if (isset($_GET['title'])) { $redirect .= "&title=" . $_GET['title']; }
	if (isset($_GET['page'])) { $redirect .= "&page=" . $_GET['page']; }
	if (!empty($redirect)) $redirect = '?'.substr($redirect, 1);
	header("Location: " . FULLWEBPATH . '/index.php'. $redirect);
	exit();
}

function zp_loggedin($rights=ALL_RIGHTS) {
	global $_zp_loggedin;
	return $_zp_loggedin & $rights;
}

?>