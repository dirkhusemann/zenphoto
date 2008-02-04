<?php

require_once("functions-db.php");

// If the auth variable gets set somehow before this, get rid of it.
if (isset($_zp_loggedin)) unset($_zp_loggedin);
$_zp_loggedin = false;
// Fix the cookie's path for root installs.
$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }
$adm = getOption('adminuser');
$pas = getOption('adminpass');
if (isset($_GET['ticket'])) { // paassword reset query
  $offer = $_GET['ticket'];
  $req = getOption('admin_reset_date');
  $ref = md5($req . $adm . $pas);
  if ($ref === $offer) {
    if (time() <= ($req + (3 * 24 * 60 * 60))) { // you have one week to use the request
      setOption('admin_reset_date', NULL);
	}
  }
}
$check_auth = md5($adm . $pas);
if ((($saved_auth = zp_getCookie('zenphoto_auth')) != '') && !isset($_POST['login'])) {
  if ($saved_auth == $check_auth) {
    $_zp_loggedin = true;
  } else {
    // Clear the cookie
    zp_setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
  }
} else {
  // Handle the login form.
  if (isset($_POST['login']) && isset($_POST['user']) && isset($_POST['pass'])) {
    $post_user = $_POST['user'];
    $rsd = getOption('admin_reset_date');
	if (($_POST['pass'] == $pas) && empty($rsd)) { // old cleartext password
	  $post_pass = $_POST['pass'];
	} else { 
	  $post_pass = md5($post_user . $_POST['pass']);
	}
    $redirect = $_POST['redirect'];
    if (($adm == $post_user) && ($pas == $post_pass)) {
      // Correct auth info. Set the cookie.
      zp_setcookie("zenphoto_auth", md5($post_user . $post_pass), time()+5184000, $cookiepath);
      $_zp_loggedin = true;
      //// FIXME: Breaks IIS
      if (!empty($redirect)) { header("Location: " . FULLWEBPATH . $redirect); }
      //// 
    } else {
     // Clear the cookie, just in case
      zp_setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
      $_zp_login_error = true;
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

function zp_loggedin() {
  global $_zp_loggedin;
  return $_zp_loggedin;
}


?>