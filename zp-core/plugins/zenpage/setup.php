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
if (!(zp_loggedin(ADMIN_RIGHTS | ZENPAGE_RIGHTS))) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}

function printError($dbtable) {
	if(mysql_error()) {
		echo "<div class=\"fail\">".gettext("Table")." <code>".$dbtable."</code> ".gettext("could not be created!")."</div>";
		echo "<div class=\"error\"><strong>".gettext("Error:")."</strong> ".mysql_error()."</div>";
	} else {
		echo "<div class=\"pass\">".gettext("Table")." <code>".$dbtable."</code> ".gettext("has been successfully created.")."</div>";
	}
}

function folderCreation($folder) {
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
if(ZENPHOTO_RELEASE > $savedrelease) {
	echo gettext("You are about to upgrade Zenpage.");
} else if (empty($savedrelease)) {
	echo gettext("You are about to install Zenpage.");
} else if(ZENPHOTO_RELEASE === $savedrelease) {
	echo "<span style='color: green'>".gettext("Your Zenpage installation seems to be up to date. You probably don't need to run setup.")."</span>";
}
?>
<?php 
echo "<p>".gettext("Zenpage will now create any required database tables and folders.")."</p>";

$create = array(prefix('zenpage_pages'), prefix('zenpage_news2cat'), prefix('zenpage_news_categories'), prefix('zenpage_news'));

$sql = "SHOW TABLES FROM `".$_zp_conf_vars['mysql_database']."` LIKE '".$_zp_conf_vars['mysql_prefix']."%';";
$result = mysql_query($sql, $mysql_connection);
$tables = array();
if ($result) {
	while ($row = mysql_fetch_row($result)) {
		$db = '`'.$row[0].'`';
		$key = array_search($db, $create);
		if ($key !== false) {
			unset($create[$key]);
		}
	}
}

if (substr(trim(mysql_get_server_info()), 0, 1) > '4') {
	$collation = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci';
} else {
	$collation = '';
}
include ('setup_db.php');
if (!empty($create)) {
	?>
	<p>
	<em><?php echo gettext("Creating the database tables for zenpage:"); ?></em>
	<?php 
	foreach ($db_schema as $sql) {
		$i = strpos($sql, '`');
		$j = strpos($sql, '`', $i+1);
		$db = substr($sql, $i, $j - $i + 1);
		if (in_array($db, $create)) {
			$result = mysql_query($sql);
			printError($db);
		}
	}
	?>
	</p>
	<?php
} else {
	?>
	<p><div class="pass"><?php echo gettext("Tables already exist.") ?></div></p>
	<?php
}
$updates = array();
foreach ($sql_statements as $sql) {
	$result = mysql_query($sql);
	if (!$result && strpos($sql, 'DROP' !== false)) {
		$errors[] = mysql_error();
	}
}

if(!empty($errors)) {
	echo "<div class=\"fail\">".gettext("Table update failed!")."</div>";
	echo "<div class=\"error\"><strong>".gettext("Error:")."</strong> ".implode('<br/>', $errors)."</div>";
} else {
	echo "<div class=\"pass\">".gettext("Table updates have been successfully been applied.")."</div>";
}
$folder = "../../../uploaded/";
if (!is_dir($folder)) {
	?>
	<p><em><?php echo gettext("Creating the zenpage upload folder in the root directory of your zenphoto installation:"); ?></em></p>
	<?php
	folderCreation($folder);
} else {
	?>
	<p><div class="pass"><?php echo gettext("Upload folder already exists.") ?></div></p>
	<?php
}
setOption('zenpage_release', ZENPHOTO_RELEASE);

echo "<br /><p>".gettext("Zenpage is ready to go!")."</p>";
echo "<div class='buttons'>";
if (isset($_GET['admin'])) {
	echo "<a href='". WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . "zenpage/'><img src='../../images/pass.png' />".gettext("Go")."</a>";
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