<?php

require_once("functions-db.php");

// If the auth variable gets set somehow before this, get rid of it.
if (isset($_zp_loggedin)) unset($_zp_loggedin);
$_zp_loggedin = false;

// Fix the cookie's path for root installs.
$cookiepath = WEBPATH;
if (WEBPATH == '') { $cookiepath = '/'; }

if (isset($_COOKIE['zenphoto_auth'])) {
  $saved_auth = $_COOKIE['zenphoto_auth'];
  $check_auth = md5(zp_conf("adminuser").zp_conf("adminpass"));
  if ($saved_auth == $check_auth) {
    $_zp_loggedin = true;
  } else {
    // Clear the cookie
    setcookie("zenphoto_auth", "", time()-368000, $cookiepath);
  }
} else {
  // Handle the login form.
  if (isset($_POST['login']) && isset($_POST['user']) && isset($_POST['pass'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $redirect = $_POST['redirect'];
    if ($user == zp_conf("adminuser") && $pass == zp_conf("adminpass")) {
      // Correct auth info. Set the cookie.
      setcookie("zenphoto_auth", md5($user.$pass), time()+5184000, $cookiepath);
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