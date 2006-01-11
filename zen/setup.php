<?php if (file_exists("config.php")) { require_once("auth_zp.php"); } ?>
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

if (file_exists("config.php")) {
  if (!zp_loggedin()) {  /* Display the login form and exit. */ ?>
  
    <div id="loginform">
    
    <form name="login" action="#" method="POST">
      <input type="hidden" name="login" value="1" />
      <input type="hidden" name="redirect" value="/zen/setup.php" />
      <table>
        <tr><td>Login</td><td><input class="textfield" name="user" type="text" size="20" /></td></tr>
        <tr><td>Password</td><td><input class="textfield" name="pass" type="password" size="20" /></td></tr>
        <tr><td colspan="2"><input class="button" type="submit" value="Log in" /></td></tr>
      </table>
    </form>
    </body></html>
  <?php
  exit();
  } else {
    // Logged in. Do the setup.
    $tbl_albums = prefix('albums');
    $tbl_comments = prefix('comments');
    $tbl_images = prefix('images');
  
    $db_schema = array();
    $db_schema[] = "CREATE TABLE IF NOT EXISTS `$tbl_albums` (
      `id` int(11) NOT NULL auto_increment,
      `folder` varchar(255) NOT NULL default '',
      `title` varchar(255) NOT NULL default '',
      `desc` text,
      `date` datetime default NULL,
      `place` varchar(255) default NULL,
      `show` int(1) NOT NULL default '1',
      `thumb` varchar(255) default NULL,
      `sort_type` varchar(20) default NULL,
      `sort_order` int(11) default NULL,
      PRIMARY KEY  (`id`),
      KEY `folder` (`folder`)
      );";
  
    $db_schema[] = "CREATE TABLE IF NOT EXISTS `$tbl_comments` (
      `id` int(11) NOT NULL auto_increment,
      `imageid` int(11) NOT NULL default '0',
      `name` varchar(255) NOT NULL default '',
      `email` varchar(255) NOT NULL default '',
      `website` varchar(255) default NULL,
      `date` datetime default NULL,
      `comment` text NOT NULL,
      `inmoderation` int(1) NOT NULL default '0',
      PRIMARY KEY  (`id`),
      KEY `imageid` (`imageid`)
      );";
  
    $db_schema[] = "CREATE TABLE IF NOT EXISTS `$tbl_images` (
      `id` int(11) NOT NULL auto_increment,
      `albumid` int(11) NOT NULL default '0',
      `filename` varchar(255) NOT NULL default '',
      `title` varchar(255) default NULL,
      `desc` text,
      `commentson` int(1) NOT NULL default '1',
      `show` int(1) NOT NULL default '1',
      `sort_order` int(11) default NULL,
      PRIMARY KEY  (`id`),
      KEY `filename` (`filename`,`albumid`),
      KEY `albumid` (`albumid`)
      );";
  
    $db_schema[] = "ALTER TABLE `$tbl_comments`
      ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`imageid`) REFERENCES `$tbl_images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
  
    $db_schema[] = "ALTER TABLE `$tbl_images`
      ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`albumid`) REFERENCES `$tbl_albums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
  
  
    if (isset($_GET['create']) && db_connect()) {
      echo "<h3>Creating tables...</h3>";
      foreach($db_schema as $sql) {
        query($sql);
      }
      echo "<h3>Done!</h3>";
      echo "<p>You can now <a href=\"../\">View your gallery</a>, or <a href=\"admin.php\">administrate.</a></p>";
    
    } else if (db_connect()) {
      echo "<h3>database connected</h3>";
      echo "<p>We're all set to create the database tables: <code>$tbl_albums</code>, <code>$tbl_images</code>, and <code>$tbl_comments</code>.</p>";
      echo "<p><a href=\"?create\" title=\"Create the database tables.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
    } else {
      echo "<h3>database not connected</h3>";
      echo "<p>Check the config.php file to make sure you've got the right username, password, host, and database. If you haven't created
        the database yet, now would be a good time.";
    }
  }
} else {
  // The config file hasn't been created yet. Show the steps.
  ?>

  <ul>
    <li><strong>Step 1: Edit the <code>config.php.example</code> file and rename it to <code>config.php</code></strong> . You can find the file
      in the "zen" directory.</li>
    <li><strong>Step 2: Edit the .htaccess file in the root zenphoto folder</strong> if you have the mod_rewrite apache 
      module, and want cruft-free URLs. Just change the one line indicated to make it work.</li>
    <li><strong>Step 3: Change the permissions on the 'albums' and 'cache' folders to be writable by the server</strong> 
      (<code>chmod 777 cache</code>) (not necessary on Windows servers)
    <li><strong>Step 4: Come back to this page (just reload it if you're ready) and click "Go!"</strong>
  </ul>
  
  <? } ?>
</body>
</html>