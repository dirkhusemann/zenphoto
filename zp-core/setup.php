<?php
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', true);
$setup = true;
if (file_exists("zp-config.php")) {
  require_once("zp-config.php");
  require_once('functions-db.php');
  if (db_connect() && !(isset($_GET['create']))) {
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
<title>zenphoto setup</title>
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
  $credentials = getOption('adminuser').getOption('adminpass');
  if (!empty($credentials)) {
    if (!zp_loggedin() && (!isset($_GET['create']))) {  // Display the login form and exit.
      printLoginForm("/" . ZENFOLDER . "/setup.php");
      exit();
    }
  } 
    // Prefix the table names. These already have `backticks` around them!
    $tbl_albums = prefix('albums');
    $tbl_comments = prefix('comments');
    $tbl_images = prefix('images');
    $tbl_options  = prefix('options');
    // Prefix the constraint names:
    $cst_comments = prefix('comments_ibfk1');
    $cst_images = prefix('images_ibfk1');
  
    $db_schema = array();
	
    $db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_options (
      `id` int(11) unsigned NOT NULL auto_increment,
      `name` varchar(64) NOT NULL,
      `value` text NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE (`name`)
      );";
       
    $db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_albums (
      `id` int(11) unsigned NOT NULL auto_increment,
      `parentid` int(11) unsigned default NULL,
      `folder` varchar(255) NOT NULL default '',
      `title` varchar(255) NOT NULL default '',
      `desc` text,
      `date` datetime default NULL,
      `place` varchar(255) default NULL,
      `show` int(1) unsigned NOT NULL default '1',
      `closecomments` int(1) unsigned NOT NULL default '0',
      `thumb` varchar(255) default NULL,
      `mtime` int(32) default NULL,
      `sort_type` varchar(20) default NULL,
      `subalbum_sort_type` varchar(20) default NULL,
      `sort_order` int(11) unsigned default NULL,
	  `image_sortdirection` int(1) UNSIGNED default '0',
	  `album_sortdirection` int(1) UNSIGNED default '0',
      `password` varchar(255) default NULL,
	  `password_hint` text,
	  `tags` text,
      PRIMARY KEY  (`id`),
      KEY `folder` (`folder`)
      );";
  
    $db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_comments (
      `id` int(11) unsigned NOT NULL auto_increment,
      `imageid` int(11) unsigned NOT NULL default '0',
      `name` varchar(255) NOT NULL default '',
      `email` varchar(255) NOT NULL default '',
      `website` varchar(255) default NULL,
      `date` datetime default NULL,
      `comment` text NOT NULL,
      `inmoderation` int(1) unsigned NOT NULL default '0',
      PRIMARY KEY  (`id`),
      KEY `imageid` (`imageid`)
      );";
    
    $exifschema = '';
    foreach (array_keys($_zp_exifvars) as $exifvar) {
      $exifschema .= "`$exifvar` varchar(52) default NULL, ";
    }
  
    $db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_images (
      `id` int(11) unsigned NOT NULL auto_increment,
      `albumid` int(11) unsigned NOT NULL default '0',
      `filename` varchar(255) NOT NULL default '',
      `title` varchar(255) default NULL,
      `desc` text,
	  `location` tinytext,
	  `city` tinytext,
	  `state` tinytext,
	  `country` tinytext,
	  `credit` tinytext,
	  `copyright` tinytext,	  
	  `tags` text,
      `commentson` int(1) NOT NULL default '1',
      `show` int(1) NOT NULL default '1',
      `date` datetime default NULL,
      `sort_order` int(11) unsigned default NULL,
      `height` int(10) unsigned default NULL,
      `width` int(10) unsigned default NULL,
      `mtime` int(32) default NULL,
      `EXIFValid` int(1) unsigned default NULL,
	  `hitcounter` int(11) unsigned default NULL,
	  `total_value` int(11) unsigned default '0',
	  `total_votes` int(11) unsigned default '0',
	  `used_ips` longtext,
      $exifschema
      PRIMARY KEY  (`id`),
      KEY `filename` (`filename`,`albumid`)
      );";
  
    $db_schema[] = "ALTER TABLE $tbl_comments
      ADD CONSTRAINT $cst_comments FOREIGN KEY (`imageid`) REFERENCES $tbl_images (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
  
    $db_schema[] = "ALTER TABLE $tbl_images
      ADD CONSTRAINT $cst_images FOREIGN KEY (`albumid`) REFERENCES $tbl_albums (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
  
  
    if (isset($_GET['create']) && db_connect()) {
      echo "<h3>Creating tables...</h3>";
      foreach($db_schema as $sql) {
        query($sql);
      }
	  
	  require('option-defaults.php');
	  
	  echo "<h3>Done!</h3>";
      $adm = getOption('adminuser');
      $pas = getOption('adminpass');
      if (empty($adm) || empty($pas)) {
        echo "<p>You need to <a href=\"admin.php?page=options\">set your admin user and password</a>.</p>";
	  } else {
        echo "<p>You can now <a href=\"../\">View your gallery</a>, or <a href=\"admin.php\">administrate.</a></p>";
	  }
    
    } else if (db_connect()) {
      echo "<h3>database connected</h3>";
      echo "<p>We're all set to create the database tables: <code>$tbl_albums</code>, <code>$tbl_images</code>, <code>$tbl_comments, and <code>$tbl_options";
      echo "<p><a href=\"?create\" title=\"Create the database tables.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
    } else {
      echo "<h3>database not connected</h3>";
      echo "<p>Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created
        the database yet, now would be a good time.";
    }
} else {
  // The config file hasn't been created yet. Show the steps.
  ?>

  <ul>
    <li><strong>Step 1: Edit the <code>zp-config.php.example</code> file and rename it to <code>zp-config.php</code></strong> . You can find the file
      in the "zp-core" directory.</li>
    <li><strong>Step 2: Edit the .htaccess file in the root zenphoto folder</strong> if you have the mod_rewrite apache 
      module, and want cruft-free URLs. Just change the one line indicated to make it work.</li>
    <li><strong>Step 3: Change the permissions on the 'albums' and 'cache' folders to be writable by the server</strong> 
      (<code>chmod 777 cache</code>) (not necessary on Windows servers)
    <li><strong>Step 4: Come back to this page (just reload it if you're ready) and click "Go!"</strong>
  </ul>
  
  <?php } ?>
</body>
</html>