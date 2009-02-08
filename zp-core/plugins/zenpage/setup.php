<?php 
/**
 * zenpage setup
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
define('OFFSET_PATH', 4);
require_once('../../admin-functions.php');
require_once('zenpage-version.php');
if (!(zp_loggedin(ADMIN_RIGHTS | ZENPAGE_RIGHTS))) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}

function printError($dbtable) {
	if(mysql_error()) {
		echo "<div class=\"fail\">".gettext("Table")." <code>".$dbtable."</code> ".gettext("could not be created!")."</div>";
		echo "<div class=\"error\"><strong>".gettext("Error:")."</strong> ".mysql_error()."</div>";
	} else {
		echo "<div class=\"pass\">".gettext("Table")." <code>".$dbtable."</code> ".gettext("has been successfully created (or already exists).")."</div>";
	}
}

function folderCreation($folder='') {
	if(mkdir_recursive($folder, 0777)) {
		echo "<div class=\"pass\">".gettext("Upload folder")." <code>".$folder."</code> ".gettext("has been successfully created (or already exists).")."</div>";
	} else {
		echo "<div class=\"fail\">".gettext("Upload folder")." <code>".$folder."</code> ".gettext("could not be created. Please create it manually via FTP with chmod 0777.")."</div>";
	}
}

db_connect();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo gettext("Setup Zenpage - a CMS plugin for Zenphoto"); ?></title>
<link rel="stylesheet" href="setup.css" type="text/css" /> 
</head>
<body>
<div class="logo"><img src='images/zenpage-logo.gif' /></div>
<div id="main" style="text-align: center">
<div style="text-align:left">
<?php 
$savedrelease =  getOption("zenpage_release");
if(ZENPAGE_RELEASE > $savedrelease) {
	echo "You are about to upgrade Zenpage.";
} else if (empty($savedrelease)) {
	echo "You are about to install Zenpage.";
} else if(ZENPAGE_RELEASE === $savedrelease) {
	echo "<span style='color: green'>Your Zenpage installation seems to be up to date. You probably don't need to run setup.</span>";
}
?>
<?php 
echo "<p>".gettext("Zenpage will now create the required database tables and the optional upload folder.")."</p>";
?>
<p><em><?php echo gettext("Creating the database tables for zenpage:"); ?></em></p>
<?php 
	if (substr(trim(mysql_get_server_info()), 0, 1) > '4') {
		$collation = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci';
	} else {
		$collation = '';
	}

$db_schema = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `title` text NOT NULL default '',
      `content` text,
      `extracontent` text,
      `show` int(1) unsigned NOT NULL default '1',
      `date` datetime, 
      `titlelink` varchar(255) NOT NULL default '',
      `commentson` int(11) unsigned NOT NULL,
      `codeblock` text,
      `author` varchar(64) NOT NULL,
      `lastchange` datetime default NULL,
      `lastchangeauthor` varchar(64) NOT NULL,
      `hitcounter` int(11) unsigned default 0,
      `permalink` int(1) unsigned NOT NULL default 0,
      `locked` int(1) unsigned NOT NULL default 0,
      PRIMARY KEY  (`id`),
			UNIQUE (`titlelink`)
      ) $collation;";
$result = mysql_query($db_schema) or mysql_error();
printError(prefix('zenpage_news'));

$db_schema = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news_categories')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `cat_name` text NOT NULL default '', 
      `cat_link` varchar(255) NOT NULL default '',
      `permalink` int(1) unsigned NOT NULL default 0,
      `hitcounter` int(11) unsigned default 0,
       PRIMARY KEY  (`id`),
			 UNIQUE (`cat_link`)
       ) $collation;";
$result = mysql_query($db_schema) or mysql_error();
printError(prefix('zenpage_news_categories'));

$db_schema = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news2cat')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `cat_id` int(11) unsigned NOT NULL, 
      `news_id` int(11) unsigned NOT NULL,
      PRIMARY KEY  (`id`)
      ) $collation;";
$result = mysql_query($db_schema) or mysql_error();
printError(prefix('zenpage_news2cat'));


$db_schema = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_pages')." (
      `id` int(11) unsigned NOT NULL auto_increment,
      `parentid` int(11) unsigned default NULL,
      `title` text NOT NULL default '',
      `content` text,
      `extracontent` text,
      `sort_order`varchar(20) NOT NULL default '',
			`show` int(1) unsigned NOT NULL default '1',
      `titlelink` varchar(255) NOT NULL default '',
      `commentson` int(11) unsigned NOT NULL,
      `codeblock` text,
      `author` varchar(64) NOT NULL,
      `date` datetime default NULL,
      `lastchange` datetime default NULL,
      `lastchangeauthor` varchar(64) NOT NULL,
      `hitcounter` int(11) unsigned default 0,
      `permalink` int(1) unsigned NOT NULL default 0,
      `locked` int(1) unsigned NOT NULL default 0,
      PRIMARY KEY  (`id`),
      UNIQUE (`titlelink`)
      ) $collation;";
$result = mysql_query($db_schema) or mysql_error();
printError(prefix('zenpage_pages'));

$updates = array();
$updates['ALTER TABLE '.prefix('zenpage_news_categories').' DROP INDEX `cat_link`;'] = false;
$updates['ALTER TABLE '.prefix('zenpage_news_categories').' ADD UNIQUE (`cat_link`);'] = true;
$updates['ALTER TABLE '.prefix('zenpage_news').' DROP INDEX `titlelink`;'] = false;
$updates['ALTER TABLE '.prefix('zenpage_news').' ADD UNIQUE (`titlelink`);'] = true;
$updates['ALTER TABLE '.prefix('zenpage_pages').' DROP INDEX `titlelink`;'] = false;
$updates['ALTER TABLE '.prefix('zenpage_pages').' ADD UNIQUE (`titlelink`);'] = true;
$errors = array();
foreach ($updates as $sql=>$error) {
	$result = mysql_query($sql) or mysql_error();
	if(mysql_error() && $error) {
		$errors[] = mysql_error();
	}
}
if(!empty($errors)) {
	echo "<div class=\"fail\">".gettext("Table update failed!")."</div>";
	echo "<div class=\"error\"><strong>".gettext("Error:")."</strong> ".implode('<br/>', $errors)."</div>";
} else {
	echo "<div class=\"pass\">".gettext("Table updates have been successfully been applied.")."</div>";
}
?>
<br />

<p><em><?php echo gettext("Creating the zenpage upload folder in the root directory of your zenphoto installation:"); ?></em></p>
<?php
$folder = "../../../uploaded/";
folderCreation($folder);
setOption('zenpage_release', ZENPAGE_RELEASE);

echo "<br /><p>".gettext("Zenpage is ready to go!")."</p>";
echo "<div class='buttons'>";
if (isset($_GET['admin'])) {
	echo "<a href='". WEBPATH . '/' . ZENFOLDER . "/plugins/zenpage/'><img src='../../images/pass.png' />".gettext("Go")."</a>";
} else {
	echo "<a href='". WEBPATH . "/index.php'><img src='../../images/pass.png' />".gettext("Go")."</a>";
}
echo "</div>";
echo "<br /><br clear:both />";
?>
</div>
</div>
</body>
</html>