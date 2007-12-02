<?php

require_once("functions-db.php");

// If the auth variable gets set somehow before this, get rid of it.
if (isset($_zp_loggedin)) unset($_zp_loggedin);
$_zp_loggedin = false;
$_zp_null_account = false;
// Fix the cookie's path for root installs.
$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }
$adm = getOption('adminuser');
$pas = getOption('adminpass');
if (isset($_GET['id'])) { // paassword reset query
  $offer = $_GET['id'];
  $ref = md5(getOption('admin_reset_date') . getOption('adminuser') . getOption('adminpass'));
  if ($ref === $offer) {
    setOption('adminpass', '');
	$pas = '';
  }
}
if (empty($adm) || empty($pas)) {
  $_zp_null_account = true;  // account requires setup
} 
$check_auth = md5($adm . $pas);
if (isset($_COOKIE['zenphoto_auth']) && !isset($_POST['login'])) {
  $saved_auth = $_COOKIE['zenphoto_auth'];
  if ($saved_auth == $check_auth) {
    $_zp_loggedin = true;
  } else {
    // Clear the cookie
    setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
  }
} else {
  // Handle the login form.
  if (isset($_POST['login']) && isset($_POST['user']) && isset($_POST['pass'])) {
    $post_user = $_POST['user'];
	if ($_POST['pass'] == $pas) { // old cleartext password
	  $post_pass = $_POST['pass'];
      $_zp_null_account = true;  // require saving the credentials again to get password encrypted
	} else { 
	  $post_pass = md5($post_user . $_POST['pass']);
	}
    $redirect = $_POST['redirect'];
    if (($adm == $post_user) && ($pas == $post_pass)) {
      // Correct auth info. Set the cookie.
      setcookie("zenphoto_auth", md5($post_user . $post_pass), time()+5184000, $cookiepath);
      $_zp_loggedin = true;
      //// FIXME: Breaks IIS
      if (!empty($redirect)) { header("Location: " . FULLWEBPATH . $redirect); }
      //// 
    } else {
     // Clear the cookie, just in case
      setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
      $error = true;
    }
  }
}
unset($saved_auth, $check_auth, $user, $pass);
// Handle a logout action.
if (isset($_POST['logout']) || isset($_GET['logout'])) {
  setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
  header("Location: " . FULLWEBPATH . "/");
}

function zp_loggedin() {
  global $_zp_loggedin;
  return $_zp_loggedin;
}


?>