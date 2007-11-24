<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', true);
$setup = true;
if (file_exists("zp-config.php")) {
  require_once('functions-db.php');
  if (db_connect() && !(isset($_GET['upgrade']))) {
    $result = mysql_query("SELECT `name`, `value` FROM " . prefix('options') . " LIMIT 1", $mysql_connection);
    if ($result) {
      unset($setup);
    }
  } 
  require_once("admin-functions.php"); 
}
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
  <h1>zenphoto upgrade</h1>
<?php
if (file_exists("zp-config.php")) {
  $credentials = getOption('adminuser').getOption('adminpass');
  if (!empty($credentials)) {
    if (!zp_loggedin()  && (isset($_GET['upgrade']))) {  // Display the login form and exit.
      printLoginForm("/" . ZENFOLDER . "/upgrade.php");
      exit();
    }
  } 
    // Logged in. Do the setup.
    // These already have `backticks` around them!
    $tbl_albums   = prefix('albums');
    $tbl_comments = prefix('comments');
    $tbl_images   = prefix('images');
    $tbl_options  = prefix('options');
  
    $sql_statements = array();
	
    // v. 1.0.0b
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `sort_type` varchar(20);";
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `sort_order` int(11);";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `sort_order` int(11);";
    
    // v. 1.0.3b
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `height` INT UNSIGNED;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `width` INT UNSIGNED;";
    
    // v. 1.0.4b
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `parentid` int(11) unsigned default NULL;";
    
    // v. 1.0.9
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `mtime` int(32) default NULL;";
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `mtime` int(32) default NULL;";
    
    //v. 1.1
    $sql_statements[] = "CREATE TABLE IF NOT EXISTS $tbl_options (
      `id` int(11) unsigned NOT NULL auto_increment,
      `name` varchar(64) NOT NULL,
      `value` text NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE (`name`)
      );";
    
    $sql_statements[] = "ALTER TABLE $tbl_options DROP `bool`, DROP `description`;";
    $sql_statements[] = "ALTER TABLE $tbl_options CHANGE `value` `value` text;";
    $sql_statements[] = "ALTER TABLE $tbl_options DROP INDEX `name`;";
    $sql_statements[] = "ALTER TABLE $tbl_options ADD UNIQUE (`name`);";
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `commentson` int(1) UNSIGNED NOT NULL default '1';";   
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `subalbum_sort_type` varchar(20) default NULL;";   
    $sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `tags` text;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `location` tinytext;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `city` tinytext;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `state` tinytext;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `country` tinytext;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `date` datetime default NULL;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `tags` text;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `EXIFValid` int(1) UNSIGNED default NULL;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `hitcounter` int(11) UNSIGNED default NULL;";
    foreach (array_keys($_zp_exifvars) as $exifvar) {
      $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `$exifvar` varchar(52) default NULL;";
    }
	
	//v1.1.1
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `image_sortdirection` int(1) UNSIGNED default '0';";
 	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `album_sortdirection` int(1) UNSIGNED default '0';";
	
	//v1.1.3
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `total_value` int(11) UNSIGNED default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `total_votes` int(11) UNSIGNED default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `used_ips` longtext;";
  	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `password` varchar(255) default NULL;";
  	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `password_hint` text;";
 
    
    if (isset($_GET['upgrade']) && db_connect()) {
      echo "<h3>Upgrading tables...</h3>";
      foreach($sql_statements as $sql) {
        // Bypass the error-handling in query()... we don't want it to stop.
        // This is probably bad behavior, so maybe do some checks?
        @mysql_query($sql);
      }
      echo "<h3>Cleaning up...</h3>";
	  
	  require('option-defaults.php');
	  
      require_once("admin-functions.php"); 
      $gallery = new Gallery();	  
      $gallery->clearCache();
	  
      $needsrefresh = $gallery->garbageCollect(true, true);
	  
	  echo "<h3>Done!</h3>";
	  $credentials = getOption('adminuser') . getOption('adminpass');
	  if (empty($credentials)) {
        echo "<p>You need to <a href=\"admin.php?page=options\">set your admin user and password</a>.</p>";
	  } else {
        echo "<p>You can now <a href=\"../\">View your gallery</a>, or <a href=\"admin.php\">administrate.</a></p>";
	  }
      if ($needsrefresh) {
        echo "<p>The database refresh stopped early due to processing time. You may need to run refresh again from the admin pages.</a></p>";
      }
    
    } else if (db_connect()) {
      echo "<h3>database connected</h3>";
      echo "<p>We're all set to upgrade the database tables: <code>$tbl_albums</code>, <code>$tbl_images</code>, <code>$tbl_comments</code>, and <code>$tbl_options</code>.</p>";
      echo "<p><strong>It's probably a good idea to make a backup first.</strong></p>";
      echo "<p><a href=\"?upgrade\" title=\"Upgrade the database tables.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
    
    } else {
      echo "<h3>database not connected</h3>";
      echo "<p>Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.";
    } 
  
} else {
  // The config file hasn't been created yet. Probably still need to setup.
  ?>
  <ul>
    <li><strong>You have no zp-config.php</strong>. You probably want to run <a href="setup.php" title="Setup">setup</a> first. </li>
  </ul>
  
<?php } ?>
</body>
</html>