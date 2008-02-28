<?php
require_once("lib-utf8.php");
require_once("functions-db.php");

// If the auth variable gets set somehow before this, get rid of it.
if (isset($_zp_loggedin)) unset($_zp_loggedin);
$_zp_loggedin = false;
// Fix the cookie's path for root installs.
$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }
if (isset($_GET['ticket'])) { // password reset query
	$offer = $_GET['ticket'];
	$admins = getAdministrators();
	$admin = array_shift($admins);
	$req = getOption('admin_reset_date');
	$adm = $admin['user'];
	$pas = $admin['pass'];
	$ref = md5($req . $adm . $pas);
	if ($ref === $offer) {
		if (time() <= ($req + (3 * 24 * 60 * 60))) { // you have one week to use the request
			setOption('admin_reset_date', NULL);
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
		$redirect = $_POST['redirect'];
		if ($_zp_loggedin = checkLogon($post_user, $post_pass)) {
			zp_setcookie("zenphoto_auth", md5($post_user . $post_pass), time()+5184000, $cookiepath);
			//// FIXME: Breaks IIS
			if (!empty($redirect)) { header("Location: " . FULLWEBPATH . $redirect); }
		} else {
			// Clear the cookie, just in case
			zp_setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
			// was it a request for a reset?
			$code = md5(trim($_POST['pass']));
			if ($code == $_POST['code_h']) {
				if (!empty($post_user)) { $requestor = ' from a user who tried to log in as "'.$post_user.'"'; }
				$admins = getAdministrators();
				$user = array_shift($admins);
				$adm = $user['user'];
				$pas = $user['pass'];
				setOption('admin_reset_date', time());
				$req = getOption('admin_reset_date');
				$ref = md5($req . $adm . $pas);
				$msg .= "\nYou are receiving this e-mail because of a password reset request on your Zenphoto gallery$requestor." .
						"\nTo reset your Zenphoto Admin passwords visit: ".FULLWEBPATH."/".ZENFOLDER."/admin.php?ticket=$ref" .
						"\nIf you do not wish to reset your passwords just ignore this message. This ticket will automatically expire in 3 days.";
				zp_mail('The Zenphoto information you requested',  $msg);
				$_zp_login_error = 2;
			} else {
				$_zp_login_error = 1;
			}
		}
	}
}
unset($saved_auth, $check_auth, $user, $pass);
// Handle a logout action.
if (isset($_POST['logout']) || isset($_GET['logout'])) {
	zp_setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
	$redirect = "";
	if (isset($_GET['p'])) {
		$redirect = "index.php?p=" . $_GET['p'];
		if (isset($_GET['searchfields'])) { $redirect .= "&searchfields=" . $_GET['searchfields']; }
		if (isset($_GET['words'])) { $redirect .= "&words=" . $_GET['words']; }
		if (isset($_GET['date'])) { $redirect .= "&date=" . $_GET['date']; }
	} else {
		$redirect = "index.php?";
		if (isset($_GET['album'])) { $redirect .= "album=" . $_GET['album']; }
		if (isset($_GET['image'])) { $redirect .= "&image=" . $_GET['image']; }
	}
	if (isset($_GET['page'])) {
		if (empty($redirect)) {
			$redirect = "?page=" . $_GET['page'];
		} else {
			$redirect .= "&page=" . $_GET['page'];
		}
	}

	header("Location: " . FULLWEBPATH . "/$redirect");
}

function zp_loggedin($rights=ALL_RIGHTS) {
	global $_zp_loggedin;
	return $_zp_loggedin & $rights;
}


?>