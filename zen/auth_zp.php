<?php

require_once("functions-db.php");

// If the auth variable gets set somehow before this, get rid of it.
if (isset($_zp_loggedin)) unset($_zp_loggedin);
$_zp_loggedin = false;
if (isset($_COOKIE['zenphoto_auth'])) {
  $saved_auth = $_COOKIE['zenphoto_auth'];
  $check_auth = sha1(zp_conf("adminuser").zp_conf("adminpass"));
  if ($saved_auth == $check_auth) {
    $_zp_loggedin = true;
  }
} else {
  // Handle the login form.
  if (isset($_POST['login']) && isset($_POST['user']) && isset($_POST['pass'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    if ($user == zp_conf("adminuser") && $pass == zp_conf("adminpass")) {
      // Correct auth info. Set the cookie.
      setcookie("zenphoto_auth", sha1($user.$pass), time()+5184000, "/");
      $_zp_loggedin = true;
    } else {
      $error = true;
    }
  }
}
unset($saved_auth, $check_auth, $user, $pass);
// Handle a logout action.
if (isset($_POST['logout']) || isset($_GET['logout'])) {
  setcookie("zenphoto_auth", "", time()-368000, "/");
  header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . WEBPATH . "/");
}

function zp_loggedin() {
  global $_zp_loggedin;
  return $_zp_loggedin;
}


?>
