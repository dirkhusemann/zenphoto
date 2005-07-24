<?php require_once("classes.php"); /* Don't put anything before this line! */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title>zenphoto administration</title>
    <link rel="stylesheet" href="admin.css" type="text/css" />
  </head>
  
  <body>

<?php if (!zp_loggedin()) {  /* Display the login form and exit. */ ?>
  
  <div id="loginform">
  
  <form name="login" action="#" method="POST">
    <input type="hidden" name="login" value="1" />
    <table>
      <tr><td>Login</td><td><input class="textfield" name="user" type="text" size="20" /></td></tr>
      <tr><td>Password</td><td><input class="textfield" name="pass" type="password" size="20" /></td></tr>
      <tr><td colspan="2"><input class="button" type="submit" value="Log in" /></td></tr>
    </table>
  </form>
  
  </div>

<?php } else { /* Display the admin pages. Do action handling first. */
  
  if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == "save_descriptions") {
      
    }
  }
  
  if (isset($_GET['page'])) { $page = $_GET['page']; } else { $page = "home"; }
?>
<div id="main">
  <div id="logout"><a href="?logout">logout</a></div>
  <ul id="nav">
    <li<?= $page == "home" ? " class=\"current\"" : "" ?>><a href="?page=home">overview</a></li>
    <li<?= $page == "comments" ? " class=\"current\"" : "" ?>><a href="?page=comments">comments</a></li>
    <li<?= $page == "upload" ? " class=\"current\"" : "" ?>><a href="?page=upload">upload</a></li>
    <li<?= $page == "edit" ? " class=\"current\"" : "" ?>><a href="?page=edit">edit</a></li>
    <li><a href="../">view gallery</a></li>
  </ul>
  
  
  <div id="content">
  
    <?php if ($page == "edit") { ?>
      <h1>edit photos</h1>
      
    <?php } else if ($page == "upload") { ?>
      <h1>upload photos</h1>
      
    <?php } else if ($page == "comments") { ?>
      <h1>comments</h1>
      
    <?php } else { $page = "home"; ?>
      <h1>zenphoto administration</h1>
      
    <?php } ?>
  
  </div>  
</div>

  
<?php } /* No admin-only content allowed after this bracket! */ ?>

  </body>
</html>
