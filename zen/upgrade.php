<?php
  define('OFFSET_PATH', true);
  if (file_exists("zp-config.php")) { require_once("admin-functions.php"); }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>zenphoto upgrade</title>
<style type="text/css">
  body { 
    margin: 20px 20% 10px;
    padding: 20px;
    background-color: #f3f3f3; 
    font-family: Arial, Helvetica, Verdana, sans-serif;
    font-size: 10pt;
  }
  h1, h2, h3, h4, h5 { padding: 0px; margin: 0px; margin-bottom: .15em; }

  A:link, A:visited {
    text-decoration: none;
    color: #36C;
  }
  A:hover, A:active {
    text-decoration: underline;
    color: #F60;
    background-color: #FFFCF4;
  }
  LI { margin-bottom: 1em; }

</style>
</head>
<body>
  <h1>zenphoto setup</h1>
<?php

if (file_exists("zp-config.php")) {
  
  // Are we logged in?
  if (!zp_loggedin()) {
  
  // Display the login form and exit. 
  printLoginForm("/zen/upgrade.php");
  exit();
  
  } else {
    // Logged in. Do the setup.
    $tbl_albums   = prefix('albums');
    $tbl_comments = prefix('comments');
    $tbl_images   = prefix('images');
  
    $sql_statements = array();
    
    // v. 1.0.0b
    $sql_statements[] = "ALTER TABLE `$tbl_albums` ADD COLUMN `sort_type` varchar(20);";
    $sql_statements[] = "ALTER TABLE `$tbl_albums` ADD COLUMN `sort_order` int(11);";
    $sql_statements[] = "ALTER TABLE `$tbl_images` ADD COLUMN `sort_order` int(11);";
    
    // v. 1.0.3b
    $sql_statements[] = "ALTER TABLE `$tbl_images` ADD COLUMN `height` INT UNSIGNED;";
    $sql_statements[] = "ALTER TABLE `$tbl_images` ADD COLUMN `width` INT UNSIGNED;";
    
    // v. 1.0.4b
    $sql_statements[] = "ALTER TABLE `$tbl_albums` ADD COLUMN `parentid` int(11) unsigned default NULL;";
    
    if (isset($_GET['upgrade']) && db_connect()) {
      echo "<h3>Upgrading tables...</h3>";
      foreach($sql_statements as $sql) {
        // Bypass the error-handling in query()... we don't want it to stop.
        // This is probably bad behavior, so maybe do some checks?
        @mysql_query($sql);
      }
      echo "<h3>Done!</h3>";
      echo "<p>You can now <a href=\"../\">View your gallery</a>, or <a href=\"../admin/\">administrate.</a></p>";
    
    } else if (db_connect()) {
      echo "<h3>database connected</h3>";
      echo "<p>We're all set to upgrade the database tables: <code>$tbl_albums</code> and <code>$tbl_images</code>.</p>";
      echo "<p><strong>It's probably a good idea to make a backup first.</strong></p>";
      echo "<p><a href=\"?upgrade\" title=\"Upgrade the database tables.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
    } else {
      echo "<h3>database not connected</h3>";
      echo "<p>Check the config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.";
    }
  } 
  
} else {
  // The config file hasn't been created yet. Probably still need to setup.
  ?>
  <ul>
    <li><strong>You have no zp-config.php</strong>. You probably want to run <a href="setup.php" title="Setup">setup</a> first. </li>
  </ul>
  
<? } ?>
</body>
</html>