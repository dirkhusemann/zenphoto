<?php
global $setup;
$checked = isset($_GET['checked']);
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', true);
$setup = true;
if (file_exists("zp-config.php")) {
  require_once("zp-config.php");
  global $_zp_conf_vars;
  if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])){
    if (mysql_select_db($_zp_conf_vars['mysql_database'])) { 
      $result = mysql_query("SELECT `id` FROM " . $_zp_conf_vars['mysql_prefix'].'options' . " LIMIT 1", $connection);
      if ($result) {
        unset($setup);
      }
    require_once("admin-functions.php"); 
    }
  } 
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>zenphoto setup</title>
<style type="text/css">
  body {margin: 0px 20% 0px; background-color: #f4f4f8; font-family: Arial, Helvetica, Verdana, sans-serif; font-size: 10pt;}
  li { margin-bottom: 1em; }
  #main { background-color: #f0f0f4; padding: 30px 20px; }
  h1 { font-weight: normal; font-size: 24pt; }
  h1, h2, h3, h4, h5 { padding: 0px; margin: 0px; margin-bottom: .15em; color: #69777d; }
  h3 span {margin-bottom: 5px;}
  #content {padding: 15px;border: 1px solid #dddde2;background-color: #fff;margin-bottom: 20px;}
  A:link, A:visited { text-decoration: none; color: #36C; }
  A:hover, A:active { text-decoration: underline; color: #F60; background-color: #FFFCF4; }
  code { color: #090; }
  cite { color: #09C; font-style: normal; font-size: 8pt;}
  .bug, a.bug { color: #D60 !important; font-family: monospace; }
  .pass {background:url(images/pass.png) top left no-repeat; padding-left: 20px; line-height:20px;}
  .fail {background:url(images/fail.png) top left no-repeat; padding-left: 20px; line-height:20px;}
  .warn {background:url(images/warn.png) top left no-repeat; padding-left: 20px; line-height:20px;}
  .error {line-height:1; border-top:1px solid #FF9595; border-bottom:1px solid #FF9595; background-color:#FFEAEA; padding:10px 8px 10px 8px;margin-left: 20px; }
  h4 { font-weight: normal; font-size: 10pt; margin-left: 2em; margin-bottom: .15em; margin-top: .35em;}
</style>
</head>
<body>
<div id="main">
<h1><img src="images/zen-logo.gif" title="Zen Photo Setup" /> setup</h1>
<div id="content">
<?php
if (!$checked) {

  /***********************************************************************
       *                                                                                                                                           *
       *                                          SYSTEMS CHECK                                                                  *
       *                                                                                                                                           *
      ************************************************************************/
  global $_zp_conf_vars;

  function checkMark($check, $text, $sfx, $msg) {
    if ($check > 0) {$check = 1; }
    echo "\n<br/><span class=\"";
	switch ($check) {
	  case 0: echo "fail"; break;
	  case -1: echo "warn"; break;
	  case 1: echo "pass"; break;
	}
    echo "\">$text</span>";
	if ($check <= 0) { 
	  if (!empty($sfx)) { echo $sfx; }
	  if (!empty($msg)) { echo "\n<p class=\"error\">$msg</p>"; }
	}
	return $check;
  }
  function folderCheck($folder) {
    $path = dirname(dirname(__FILE__)) . "/" . $folder;
    if (!is_dir($path)) {
      @mkdir($path, 0777);
    }
    @chmod($path, 0777);
	  if (!is_dir($path)) {
	    $sfx = " [Does not exist]"; 
	  } else {
	    $sfx = " [Not writeable]";
	  }
    return checkMark(is_dir($path) && is_writable($path), " <em>$folder</em> folder", $sfx, 
	       "Change the permissions on the <code>$folder</code> folder to be writable by the server</strong> " .  
           "(<code>chmod 777 $folder</code>)");
	}


  $good = true;
  
  $phpv = phpversion();
  $n = explode(".", $phpv);
  $v = $n[0]*10000 + $n[1]*100 + $n[2]; 
  $php = $v >= 40100;
  $good = checkMark($php, " PHP version 4.1.0 or greater", " [version is $phpv]", '') && $good; 

  $good = checkMark(extension_loaded('gd'), " PHP GD support", '', '') && $good;

  $sql = extension_loaded('mysql');
  $good = checkMark($sql, " PHP mySQL support", '', '') && $good;

  if (file_exists("zp-config.php")) {
    require_once("zp-config.php");
    $cfg = true;
  } else {
    $cfg = false;
  }
  $good = checkMark($cfg, " zp-config.php file", " [does not exist]",
               "Edit the <code>zp-config.php.example</code> file and rename it to <code>zp-config.php</code> " .
	           "<br/><br/>You can find the file in the \"zp-core\" directory.") && $good;
  if ($cfg) {
    $mySQLadmin = ($_zp_conf_vars['mysql_user'] == "user") ||
                  ($_zp_conf_vars['mysql_pass'] == "pass") ||
                  ($_zp_conf_vars['mysql_database'] == "database_name");
    $good = checkMark(!$mySQLadmin, " mySQL setup in zp-config.php", '', 
                      "You have not set your <strong>mySQL</strong> <code>user</code>, " .
	                  "<code>password</code>, etc. in your <code>zp-confgi.php</code> file.") && $good;
  }
  if ($sql) {
    if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])){
      $db = $_zp_conf_vars['mysql_database'];
	  $db = @mysql_select_db($db);
    }
  }
  $good = checkMark($connection, " connect to mySQL", '', '') && $good; 
  if ($connection) {
    $a = mysql_get_server_info();
    $mysqlv = substr($a, 0, strpos($a, "-"));
    $n = explode(".", $mysqlv);
    $v = $n[0]*10000 + $n[1]*100 + $n[2]; 
    $sqlv = $v >= 32300;
    $good = checkMark($sqlv, " mySQL version 3.2.3 or greater", " [version is $mysqlv]", "") && $good; 
    $good = checkMark($db, " connect to \"" . $_zp_conf_vars['mysql_database'] . "\"", '', '') && $good;
    }
	
  $ht = @file_get_contents('../.htaccess');
  $htu = strtoupper($ht);
  $i = strpos($htu, 'REWRITEENGINE');
  if ($i === false) {
    $rw = '';
  } else {
    $j = strpos($htu, "\n", $i+13);
    $rw = trim(substr($htu, $i+13, $j-$i-13));
  }
  $mod = '';
  $msg = " .htaccess file";
  if (!empty($rw)) { 
    $msg .= " (<em>RewriteEngine</em> is <strong>$rw</strong>)";
    $mod = "&mod_rewrite=$rw";
  }
  if (empty($ht)) { $ch = -1; } else { $ch = 1; }
  checkMark($ch, $msg, " [is empty or does not exist]", 
               "Edit the <code>.htaccess</code> file in the root zenphoto folder if you have the mod_rewrite apache ". 
               "module, and want cruft-free URLs. Just change the one line indicated to make it work. " .
			   "<br/><br/>You can ignore this warning if you do not intend to set the option <code>mod_rewrite</code>."); 

  $base = true;
  if ($rw == 'ON') {
    $i = strpos($htu, 'REWRITEBASE', $j);
    if ($i === false) {
      $base = false;
    } else {
      $j = strpos($htu, "\n", $i+11);
      $b = trim(substr($ht, $i+11, $j-$i-11));
	  $d = dirname(dirname($_SERVER['SCRIPT_NAME']));
      $good = checkMark($base = ($b == $d), " RewriteBase", " [Does not match install folder]", 
	               "Install folder is <code>$d</code> and RewriteBase is set to <code>$b</code>. ".
				   "Set <code>RewriteBase</code> in your <code>.htaccess</code> file to <code>$d</code>.") && $good;
    }
  }
  $good = folderCheck('albums') && $good;
  $good = folderCheck('cache') && $good;
 
  if ($good) {
    $dbmsg = "";
  } else {
    echo "<p>You need to address the problems indicated above then run <code>setup.php</code> again.</p>";
	exit();
  }
} else { 
  $dbmsg = "database connected";
} // system check
if (file_exists("zp-config.php")) {
  require_once("zp-config.php");
  require_once('functions-db.php');
  $task = '';
  if (isset($_GET['create'])) { $task = 'create'; }
  if (isset($_GET['update'])) { $task = 'update'; }
  
  
  if (db_connect() && empty($task)) {
    $task = 'update';
    $result = mysql_query("SELECT `name`, `value` FROM " . prefix('options') . " LIMIT 1", $mysql_connection);
    if ($result) {
      unset($setup);
    }
    $result = mysql_query("SELECT `id` FROM " . prefix('albums') . " LIMIT 1", $mysql_connection);
    if (empty($result)) {
      $task = 'create';
    }
    $result = mysql_query("SELECT `id` FROM " . prefix('images') . " LIMIT 1", $mysql_connection);
    if (empty($result)) {
      $task = 'create';
    }
    $result = mysql_query("SELECT `id` FROM " . prefix('comments') . " LIMIT 1", $mysql_connection);
    if (empty($result)) {
      $task = 'create';
    }
    $credentials = getOption('adminuser').getOption('adminpass');
    if (!empty($credentials)) {
      if (!zp_loggedin() && (!isset($_GET['create']) && !isset($_GET['update']))) {  // Display the login form and exit.
	    if (isset($_GET['mod_rewrite'])) {
	      $rw = "&mod_rewrite=" . $_GET['mod_rewrite'];
	    } else {
	      $rw = '';
	    }
        printLoginForm("/" . ZENFOLDER . "/setup.php?checked$rw", false);
        exit();
      }
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
	
	/*******************************************************************************
	 Add new fields and tables in the upgrade section. This section should remain static. 
	 This tactic keeps all changes in one place so that noting gets accidentaly omitted.
	********************************************************************************/
	
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
      `commentson` int(1) UNSIGNED NOT NULL default '1',   
      `thumb` varchar(255) default NULL,
      `mtime` int(32) default NULL,
      `sort_type` varchar(20) default NULL,
      `subalbum_sort_type` varchar(20) default NULL,
      `sort_order` int(11) unsigned default NULL,
	  `image_sortdirection` int(1) UNSIGNED default '0',
	  `album_sortdirection` int(1) UNSIGNED default '0',
	  `hitcounter` int(11) unsigned default NULL,
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
      PRIMARY KEY  (`id`),
      KEY `filename` (`filename`,`albumid`)
      );";
  
    $db_schema[] = "ALTER TABLE $tbl_comments
      ADD CONSTRAINT $cst_comments FOREIGN KEY (`imageid`) REFERENCES $tbl_images (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
  
    $db_schema[] = "ALTER TABLE $tbl_images
      ADD CONSTRAINT $cst_images FOREIGN KEY (`albumid`) REFERENCES $tbl_albums (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
	  
	/*******************************************************************************
	 ******                                              UPGRADE SECTION                                                      ******
	 ******                                                                                                                                        ******	 
	 ******                                    Add all new tables and fields below                                         ******
	 ******                                                                                                                                        ******	 
	********************************************************************************/
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
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `credit` tinytext;";
    $sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `copyright` tinytext;";	
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
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `hitcounter` int(11) UNSIGNED default NULL;";
 
	/*******************************************************************************
	 ******                                        END of UPGRADE SECTION                                              ******
	 ******                                                                                                                                        ******	 
	 ******                                    Add all new tables and fields above                                         ******
	 ******                                                                                                                                        ******	 
	********************************************************************************/   
  
    if (isset($_GET['create']) || isset($_GET['update']) && db_connect()) {
      echo "<h3>About to $task tables...</h3>";
      // Bypass the error-handling in query()... we don't want it to stop.
	  // Besides, we expect that some tables/fields already exist.
      // This is probably bad behavior, so maybe do some checks?
	  if ($task == 'create') {  // need to create the databases.
        foreach($db_schema as $sql) {
          @mysql_query($sql);
	    }
	  }
	  // always run the update queries to insure the tables are up to current level
      foreach($sql_statements as $sql) {
        @mysql_query($sql);
      }
	  
	  // set defaults on any options that need it
	  require('option-defaults.php');
	    
	  if ($task == 'update') {
        echo "<h3>Cleaning up...</h3>";
        require_once("admin-functions.php"); 
        $gallery = new Gallery();	  
        $gallery->clearCache();
	  
        $needsrefresh = $gallery->garbageCollect(true, true);
	  } else {
        $needsrefresh = false;
	  }
	    
	  echo "<h3>Done with table $task!</h3>";
      $adm = getOption('adminuser');
      $pas = getOption('adminpass');

      if (empty($adm) || empty($pas)) {
        echo "<p>You need to <a href=\"admin.php?page=options\">set your admin user and password</a>.</p>";
	  } else {
        echo "<p>You can now <a href=\"../\">View your gallery</a>, or <a href=\"admin.php\">administrate.</a></p>";
	  }
      if ($needsrefresh) {
        echo "<p>The database refresh stopped early due to processing time. You may need to run refresh again from the admin pages.</a></p>";
      }
    
    } else if (db_connect()) {
      echo "<h3>$dbmsg</h3>";
      echo "<p>We're all set to $task the database tables: <code>$tbl_albums</code>, <code>$tbl_images</code>, <code>$tbl_comments</code>, and <code>$tbl_options</code>";
	  if (isset($_GET['mod_rewrite'])) {
	    $rw = "&mod_rewrite=" . $_GET['mod_rewrite'];
	  } else {
	    $rw = '';
	  }
      echo "<p><a href=\"?checked&$task$rw\" title=\"$task the database tables.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
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
</div>
</div>
</body>
</html>