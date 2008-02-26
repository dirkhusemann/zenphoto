<?php
define('HTACCESS_VERSION', '1.1.4.0');  // be sure to change this the one in .htaccess when the .htaccess file is updated.
define('CHMOD_VALUE', 0777);
$checked = isset($_GET['checked']);
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
define('OFFSET_PATH', true);
$upgrade = false;

if (!$checked && !file_exists('zp-config.php')) {
	@copy('zp-config.php.example', 'zp-config.php');
}
function updateItem($item, $value) {
	global $zp_cfg;
	$i = strpos($zp_cfg, $item);
	$i = strpos($zp_cfg, '=', $i);
	$j = strpos($zp_cfg, "\n", $i);
	$zp_cfg = substr($zp_cfg, 0, $i) . '= "' . $value . '";' . substr($zp_cfg, $j);
}
if (isset($_POST['mysql'])) { //try to update the zp-config file
	$zp_cfg = @file_get_contents('zp-config.php');
	if (isset($_POST['mysql_user'])) {
		updateItem('mysql_user', $_POST['mysql_user']);
	}
	if (isset($_POST['mysql_pass'])) {
		updateItem('mysql_pass', $_POST['mysql_pass']);
	}
	if (isset($_POST['mysql_host'])) {
		updateItem('mysql_host', $_POST['mysql_host']);
	}
	if (isset($_POST['mysql_database'])) {
		updateItem('mysql_database', $_POST['mysql_database']);
	}
	if (isset($_POST['mysql_prefix'])) {
		updateItem('mysql_prefix', $_POST['mysql_prefix']);
	}
	@chmod('zp-config.php', CHMOD_VALUE);
	if (is_writeable('zp-config.php')) {
		if ($handle = fopen('zp-config.php', 'w')) {
			if (fwrite($handle, $zp_cfg)) {
				$base = true;
			}
		}
		fclose($handle);
	}
}
if (file_exists("zp-config.php")) {
	require("zp-config.php");
	if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])){
		if (@mysql_select_db($_zp_conf_vars['mysql_database'])) {
			$result = @mysql_query("SELECT `id` FROM " . $_zp_conf_vars['mysql_prefix'].'options' . " LIMIT 1", $connection);
			if (mysql_num_rows($result) > 0) $upgrade = true;
			require_once("admin-functions.php");
		}
	}
}?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>zenphoto <?php echo $upgrade ? "upgrade" : "setup" ; ?></title>
<style type="text/css">
body {
	margin: 0px 20% 0px;
	background-color: #f4f4f8;
	font-family: Arial, Helvetica, Verdana, sans-serif;
	font-size: 10pt;
}

li {
	margin-bottom: 1em;
}

#main {
	background-color: #f0f0f4;
	padding: 30px 20px;
}

h1 {
	font-weight: normal;
	font-size: 24pt;
}

h1,h2,h3,h4,h5 {
	padding: 0px;
	margin: 0px;
	margin-bottom: .15em;
	color: #69777d;
}

h3 span {
	margin-bottom: 5px;
}

#content {
	padding: 15px;
	border: 1px solid #dddde2;
	background-color: #fff;
	margin-bottom: 20px;
}

A:link,A:visited {
	text-decoration: none;
	color: #36C;
}

A:hover,A:active {
	text-decoration: underline;
	color: #F60;
	background-color: #FFFCF4;
}

code {
	color: #090;
}

cite {
	color: #09C;
	font-style: normal;
	font-size: 8pt;
}

.bug,a.bug {
	color: #D60 !important;
	font-family: monospace;
}

.pass {
	background: url(images/pass.png) top left no-repeat;
	padding-left: 20px;
	line-height: 20px;
}

.fail {
	background: url(images/fail.png) top left no-repeat;
	padding-left: 20px;
	line-height: 20px;
}

.warn {
	background: url(images/warn.png) top left no-repeat;
	padding-left: 20px;
	line-height: 20px;
}

.error {
	line-height: 1;
	border-top: 1px solid #FF9595;
	border-bottom: 1px solid #FF9595;
	background-color: #FFEAEA;
	padding: 10px 8px 10px 8px;
	margin-left: 20px;
}

h4 {
	font-weight: normal;
	font-size: 10pt;
	margin-left: 2em;
	margin-bottom: .15em;
	margin-top: .35em;
}
</style>
</head>
<body>
<div id="main">
<h1><img src="images/zen-logo.gif" title="Zen Photo Setup"
	align="absbottom" /> <?php echo $upgrade ? "Upgrade" : "Setup" ; ?></h1>
<div id="content"><?php
if (!$checked) {
	// Some descriptions about setup/upgrade.
	?> <?php if ($upgrade) { ?> Zenphoto has detected that you're upgrading
to a new version. <br />
<br />

	<?php } else { ?> Welcome to Zenphoto! This page will set up Zenphoto
on your web server.<br />
<br />
	<?php } ?> <strong>Systems Check:</strong><br />

	<?php

	/*****************************************************************************
	 *                                                                           *
	 *                             SYSTEMS CHECK                                 *
	 *                                                                           *
	 ******************************************************************************/
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
	function folderCheck($which, $path, $external) {
		if (!is_dir($path) && !$external) {
			@mkdir($path, CHMOD_VALUE);
		}
		@chmod($path, CHMOD_VALUE);
		$folders = explode('/', $path);
		$folder = $folders[count($folders)-1];
		if (empty($folder)) $folder = $folders[count($folders)-2];  // trailing slash
		if ($external) {
			$append = $path;
		} else {
			$append = $folder;
		}
		$f = '';
		if (!is_dir($path)) {
			$e = '';
			if (!$external) $d = " and <strong>setup</strong> could not create it";
			$sfx = " [<em>$append</em> does not exist$d]";
			$msg = " You must create the folder $folder. <code>mkdir($path, 0777)</code>.";
		} else if (!is_writable($path)) {
			$sfx = " [<em>$append</em> is not writeable and <strong>setup</strong> could not make it so]";
			$msg =  "Change the permissions on the <code>$folder</code> folder to be writable by the server " .
							"(<code>chmod 777 " . $append . "</code>)";
		} else if (($folder != $which) || $external) {
			$msg = '';
			$f = " (<em>$append</em>)";
		}

		return checkMark(is_dir($path) && is_writable($path), " <em>$which</em> folder$f", $sfx, $msg);
	}
	function versionCheck($required, $found) {
		$nr = explode(".", $required . '.0.0.0');
		$vr = $nr[0]*10000 + $nr[1]*100 + $nr[2];
		$nf = explode(".", $found . '.0.0.0');
		$vf = $nf[0]*10000 + $nf[1]*100 + $nf[2];
		return ($vf >= $vr);
	}

	$good = true;

	$required = '4.1.0';
	$phpv = phpversion();
	$good = checkMark(versionCheck($required, $phpv), " PHP version $phpv", "", "Version $required or greater is required.") && $good;

	if (ini_get('safe_mode')) {
		$safe = -1;
	} else {
		$safe = true;
	}
	checkMark($safe, "PHP Safe Mode", " [is set]", "Zenphoto functionality is reduced when PHP <code>safe mode</code> restrictions are in effect.");

	/* Check for GD and JPEG support. */
	$gd = extension_loaded('gd');
	$good = checkMark($gd, " PHP GD support", ' is not installed', 'You need to install GD support in your PHP') && $good;
	if ($gd) {
		$imgtypes = imagetypes();
		if (!($imgtypes & IMG_JPG)) { $missing[] = 'JPEG'; }
		if (!($imgtypes & IMG_GIF)) { $missing[] = 'GIF'; }
		if (!($imgtypes & IMG_PNG)) { $missing[] = 'PNG'; }
		if (count($missing) > 0) {
			if (count($missing) < 3) {
				$imgmissing = $missing[0];
				if (count($missing) == 2) { $imgmissing .= ' or '.$missing[1]; }
				$err = -1;
				$mandate = "should";
			} else {
				$imgmissing = $missing[0].', '.$missing[1].', or '.$missing[2];
				$err = 0;
				$good = false;
				$mandate = "need to";
			}
			checkMark($err, " PHP GD image support", '', "Your PHP GD does not support $imgmissing. ".
	                    "<br/>The unsupported image types will not be viewable in your albums.".
	                    "<br/>To correct this you $mandate install GD with appropriate image support in your PHP");
		}
	}
	$sql = extension_loaded('mysql');
	$good = checkMark($sql, " PHP MySQL support", '', 'You need to install MySQL support in your PHP') && $good;

	if (file_exists("zp-config.php")) {
		require("zp-config.php");
		$cfg = true;
	} else {
		$cfg = false;
	}
	$good = checkMark($cfg, " <em>zp-config.php</em> file", " [does not exist]",
 							"Edit the <code>zp-config.php.example</code> file and rename it to <code>zp-config.php</code> " .
 							"<br/><br/>You can find the file in the \"zp-core\" directory.") && $good;
	if ($sql) {
		if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])) {
			$db = $_zp_conf_vars['mysql_database'];
			$db = @mysql_select_db($db);
		}
	}
	if ($connection) {
		$mysqlv = trim(mysql_get_server_info());
		$i = strpos($mysqlv, "-");
		if ($i !== false) {
			$mysqlv = substr($mysqlv, 0, $i);
		}
		$required = '3.23.36';
		$sqlv = versionCheck($required, $mysqlv);;
	}
	if ($cfg) {
		@chmod('zp-config.php', CHMOD_VALUE);
		if ((!$sql || !$connection  || !$db) && is_writable('zp-config.php')) {
			$good = checkMark(false, " MySQL setup in zp-config.php", '', '') && $good;
			// input form for the information
			?>
			<div class="error">Fill in the missing information below and <strong>setup</strong>
				will attempt to update your <code>zp-config.php</code> file.<br />
			<br />
			<form action="#" method="post">
			<input type="hidden" name="mysql" value="yes" />
			<table>
				<tr>
				<td>MySQL admin user</td>
				<td><input type="text" size="40" name="mysql_user"
					value="<?php echo $_zp_conf_vars['mysql_user']?>" />&nbsp;*</td>
			</tr>
			<tr>
				<td>MySQL admin password</td>
				<td><input type="password" size="40" name="mysql_pass"
			value="<?php echo $_zp_conf_vars['mysql_pass']?>" />&nbsp;*</td>
			</tr>
			<tr>
				<td>MySQL host</td>
				<td><input type="text" size="40" name="mysql_host"
					value="<?php echo $_zp_conf_vars['mysql_host']?>" /></td>
			</tr>
			<tr>
				<td>MySQL database</td>
				<td><input type="text" size="40" name="mysql_database"
					value="<?php echo $_zp_conf_vars['mysql_database']?>" />&nbsp;*</td>
			</tr>
			<tr>
				<td>Database table prefix</td>
				<td><input type="text" size="40" name="mysql_prefix"
					value="<?php echo $_zp_conf_vars['mysql_prefix']?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td align="right">* required</td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" value="save" /></td>
				<td></td>
			</tr>
		</table>
		</form>
		</div>
			 <?php
		} else {
			$good = checkMark(!$mySQLadmin, " MySQL setup in <em>zp-config.php</em>", '',
											"You have not set your <strong>MySQL</strong> <code>user</code>, " .
											"<code>password</code>, etc. in your <code>zp-confgi.php</code> file ".
											"and <strong>setup</strong> is not able to write to the file.") && $good;
		}
	}
	$good = checkMark($connection, " connect to MySQL", '', "Could not connect to the <strong>MySQL</strong> server. Check the <code>user</code>, " .
			"<code>password</code>, and <code>database host</code> in your <code>zp-config.php</code> file and try again. ") && $good;
	if ($connection) {
		$good = checkMark($sqlv, " MySQL version $mysqlv", "", "Version $required or greater is required") && $good;
		$good = checkMark($db, " connect to the database <code>" . $_zp_conf_vars['mysql_database'] . "</code>", '',
			"Could not access the <strong>MySQL</strong> database (<code>" . $_zp_conf_vars['mysql_database'] ."</code>). Check the <code>user</code>, " .
			"<code>password</code>, and <code>database name</code> and try again. " .
			"Make sure the database has been created, and the <code>user</code> has access to it. " .
			"Also check the <code>MySQL host</code>.") && $good;

		$dbn = "`".$_zp_conf_vars['mysql_database']. "`.*";
		$sql = "SHOW GRANTS;";
		$result = mysql_query($sql, $mysql_connection);
		$access = -1;
		$rightsfound = 'unknown';
		if ($result) {
			$report = "<br/><br/><em>Grants found:</em>";
			while ($row = mysql_fetch_row($result)) {
				$report .= "<br/><br/>".$row[0];
				$r = str_replace(',', '', $row[0]);
				$i = strpos($r, "ON");
				$j = strpos($r, "TO", $i);
				$found = stripslashes(trim(substr($r, $i+2, $j-$i-2)));
				$rights = array_flip(explode(' ', $r));
				$rightsfound = 'insufficient';
				if (($found == $dbn) || ($found == "*.*")) {
					if (isset($rights['ALL']) || (isset($rights['SELECT']) && isset($rights['INSERT']) &&
					isset($rights['UPDATE']) && isset($rights['DELETE']))) {
						$access = 1;
					}
					$report .= " *";
				}
			}
		} else {
			$report = "<br/><br/>The <em>SHOW GRANTS</em> query failed.";
		}
		checkMark($access, " MySQL access rights", " [$rightsfound]",
 											"Your MySQL user must have <code>Select</code>, <code>Insert</code>, ". 
 											"<code>Update</code>, and <code>Delete</code> rights." . $report);

		$sql = "SHOW TABLES FROM `".$_zp_conf_vars['mysql_database']."` LIKE '".$_zp_conf_vars['mysql_prefix']."%';";
		$result = mysql_query($sql, $mysql_connection);
		$tablelist = '';
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$tableslist .= "<code>" . $row[0] . "</code>, ";
			}
		}
		if (!empty($tableslist)) { $tableslist = " found " . substr($tableslist, 0, -2); }
		if (!$result) { $result = -1; }
		$dbn = $_zp_conf_vars['mysql_database'];
		checkMark($result, " MySQL <em>show tables</em>$tableslist", " [Failed]", "MySQL did not return a list of the database tables for <code>$dbn</code>." .
 											"<br/><strong>Setup</strong> will attempt to create all tables. This will not over write any existing tables.");

	}

	$msg = " <em>.htaccess</em> file";
	$htfile = '../.htaccess';
	$ht = @file_get_contents($htfile);
	$htu = strtoupper($ht);
	$vr = "";
	$ch = 1;
	if (empty($htu)) {
		$ch = -1;
		$err = "is empty or does not exist";
		$desc = "Edit the <code>.htaccess</code> file in the root zenphoto folder if you have the mod_rewrite apache ".
						"module, and want cruft-free URLs. Just change the one line indicated to make it work. " .
						"<br/><br/>You can ignore this warning if you do not intend to set the option <code>mod_rewrite</code>.";
	} else {
		$i = strpos($htu, 'VERSION');
		if ($i !== false) {
			$j = strpos($htu, "\n", $i+7);
			$vr = trim(substr($htu, $i+7, $j-$i-7));
		}
		$ch = $vr == HTACCESS_VERSION;
		if (!$ch) {
			$err = "wrong version";
			$desc = "You need to upload the copy of the .htaccess file that was included with the zenphoto distribution.";
		}
	}
	if ($ch) {
		$i = strpos($htu, 'REWRITEENGINE');
		if ($i === false) {
			$rw = '';
		} else {
			$j = strpos($htu, "\n", $i+13);
			$rw = trim(substr($htu, $i+13, $j-$i-13));
		}
		$mod = '';
		if (!empty($rw)) {
			$msg .= " (<em>RewriteEngine</em> is <strong>$rw</strong>)";
			$mod = "&mod_rewrite=$rw";
		}
	}
	checkMark($ch, $msg, " [$err]", $desc);

	$base = true;
	$f = '';
	if ($rw == 'ON') {
		$d = dirname(dirname($_SERVER['SCRIPT_NAME']));
		$i = strpos($htu, 'REWRITEBASE', $j);
		if ($i === false) {
			$base = false;
			$b = "<em>missing</em>";
			$i = $j+1;
		} else {
			$j = strpos($htu, "\n", $i+11);
			$b = trim(substr($ht, $i+11, $j-$i-11));
			$base = ($b == $d);
		}
		$f = '';
		if (!$base) { // try and fix it
			@chmod($htfile, CHMOD_VALUE);
			if (is_writeable($htfile)) {
				$ht = substr($ht, 0, $i) . "RewriteBase $d\n" . substr($ht, $j+1);
				if ($handle = fopen($htfile, 'w')) {
					if (fwrite($handle, $ht)) {
						$base = true;
						$f = " (fixed)";
						$b = $d;
					}
				}
				fclose($handle);
			}
		}
		$good = checkMark($base, "<em>.htaccess</em> RewriteBase is <code>$b</code> $f", " [Does not match install folder]",
											"Setup was not able to write to the file change RewriteBase match the install folder." .
											"<br/>Either make the file writeable or ".
											"set <code>RewriteBase</code> in your <code>.htaccess</code> file to <code>$d</code>.") && $good;
	}

	if (is_null($_zp_conf_vars['external_album_folder'])) {
		$good = folderCheck('albums', dirname(dirname(__FILE__)) . $_zp_conf_vars['album_folder'], false) && $good;
	} else {
		$good = folderCheck('albums', $_zp_conf_vars['external_album_folder'], true) && $good;
	}

	$good = folderCheck('cache', dirname(dirname(__FILE__)) . "/cache/", false) && $good;
	if ($connection) { mysql_close($connection); }
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
	require("zp-config.php");
	require_once('functions-db.php');
	$task = '';
	if (isset($_GET['create'])) {
		$task = 'create';
		$create = array_flip(explode(',', $_GET['create']));
	}
	if (isset($_GET['update'])) {
		$task = 'update';
	}

	if (db_connect() && empty($task)) {

		$sql = "SHOW TABLES FROM `".$_zp_conf_vars['mysql_database']."` LIKE '".$_zp_conf_vars['mysql_prefix']."%';";
		$result = mysql_query($sql, $mysql_connection);
		$tables = array();
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$tables[$row[0]] = 'update';
			}
		}
		$expected_tables = array($_zp_conf_vars['mysql_prefix'].'options', $_zp_conf_vars['mysql_prefix'].'albums',
		$_zp_conf_vars['mysql_prefix'].'images', $_zp_conf_vars['mysql_prefix'].'comments',
		$_zp_conf_vars['mysql_prefix'].'administrators', $_zp_conf_vars['mysql_prefix'].'admintoalbum');
		foreach ($expected_tables as $needed) {
			if (!isset($tables[$needed])) {
				$tables[$needed] = 'create';
			}
		}

		if (!($tables['administrators'] == 'create')) {
			if (!($_zp_loggedin & ADMIN_RIGHTS) && (!isset($_GET['create']) && !isset($_GET['update']))) {  // Display the login form and exit.
				if ($_zp_loggedin) { echo "<br/><br/>You need <em>USER ADMIN</em> rights to run setup."; }
				printLoginForm("/" . ZENFOLDER . "/setup.php?checked$mod", false);
				exit();
			}
		}
	}

	// Prefix the table names. These already have `backticks` around them!
	$tbl_albums = prefix('albums');
	$tbl_comments = prefix('comments');
	$tbl_images = prefix('images');
	$tbl_options  = prefix('options');
	$tbl_administrators = prefix('administrators');
	$tbl_admintoalbum = prefix('admintoalbum');
	// Prefix the constraint names:
	$cst_comments = prefix('comments_ibfk1');
	$cst_images = prefix('images_ibfk1');

	$db_schema = array();

	/***********************************************************************************
	 Add new fields in the upgrade section. This section should remain static except for new
	 tables. This tactic keeps all changes in one place so that noting gets accidentaly omitted.
		************************************************************************************/

	// v. 1.1.5
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'administrators'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_administrators (
		`id` int(11) unsigned NOT NULL auto_increment,
		`user` varchar(64) NOT NULL,
		`password` text NOT NULL,
		`name` text,
		`email` text,
		`rights` int,
		PRIMARY KEY  (`id`),
		UNIQUE (`user`)
		);";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'admintoalbum'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_admintoalbum (
		`id` int(11) unsigned NOT NULL auto_increment,
		`adminid` int(11) unsigned NOT NULL,
		`albumid` int(11) unsigned NOT NULL,
		PRIMARY KEY  (`id`)
		);";
	}

	// v. 1.1
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'options'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_options (
		`id` int(11) unsigned NOT NULL auto_increment,
		`name` varchar(64) NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`name`)
		);";
	}

	// base implementation
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'albums'])) {
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
	}

	if (isset($create[$_zp_conf_vars['mysql_prefix'].'comments'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_comments (
		`id` int(11) unsigned NOT NULL auto_increment,
		`ownerid` int(11) unsigned NOT NULL default '0',
		`name` varchar(255) NOT NULL default '',
		`email` varchar(255) NOT NULL default '',
		`website` varchar(255) default NULL,
		`date` datetime default NULL,
		`comment` text NOT NULL,
		`inmoderation` int(1) unsigned NOT NULL default '0',
		PRIMARY KEY  (`id`),
		KEY `ownerid` (`ownerid`)
		);";
		$db_schema[] = "ALTER TABLE $tbl_comments ".
			"ADD CONSTRAINT $cst_comments FOREIGN KEY (`ownerid`) REFERENCES $tbl_images (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
	}

	if (isset($create[$_zp_conf_vars['mysql_prefix'].'images'])) {
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
		$db_schema[] = "ALTER TABLE $tbl_images ".
			"ADD CONSTRAINT $cst_images FOREIGN KEY (`albumid`) REFERENCES $tbl_albums (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
	}

	/****************************************************************************************
	 ******                             UPGRADE SECTION                                ******
	 ******                                                                            ******
	 ******                          Add all new fields below                          ******
	 ******                                                                            ******
	 *****************************************************************************************/
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
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `password` varchar(255) NOT NULL default '';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `password_hint` text;";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `hitcounter` int(11) UNSIGNED default NULL;";

	//v1.1.4
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD COLUMN `type` varchar(52) NOT NULL default 'images';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `total_value` int(11) UNSIGNED default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `total_votes` int(11) UNSIGNED default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `used_ips` longtext;";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `custom_data` text default NULL";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `custom_data` text default NULL";
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `password` `password` varchar(255) NOT NULL DEFAULT ''";

	//v1.1.5
	$sql_statements[] = "ALTER TABLE $tbl_comments CHANGE `imageid` `ownerid` int(11) UNSIGNED NOT NULL default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_comments DROP INDEX `imageid`;";
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD INDEX (`ownerid`);";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `dynamic` int(1) UNSIGNED default '0'";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `search_params` text default NULL";
	




	/**************************************************************************************
	 ******                            END of UPGRADE SECTION                                                           ******
	 ******                                                                                                                                                     ******
	 ******                           Add all new fields above                                                                  ******
	 ******                                                                                                                                                     ******
	 ***************************************************************************************/

	if (isset($_GET['create']) || isset($_GET['update']) && db_connect()) {

		echo "<h3>About to $task tables...</h3>";
		// Bypass the error-handling in query()... we don't want it to stop.
		// This is probably bad behavior, so maybe do some checks?
		foreach($db_schema as $sql) {
			@mysql_query($sql);
		}
		// always run the update queries to insure the tables are up to current level
		foreach($sql_statements as $sql) {
			@mysql_query($sql);
		}

		// set defaults on any options that need it
		require('option-defaults.php');


		echo "<h3>Done with table $task!</h3>";

		$rsd = getOption('admin_reset_date');

		if (empty($rsd)) {
			echo "<p>You need to <a href=\"admin.php?page=options\">set your admin user and password</a>.</p>";
		} else {
			echo "<p>You can now <a href=\"../\">View your gallery</a>, or <a href=\"admin.php\">administrate.</a></p>";
		}

	} else if (db_connect()) {
		echo "<h3>$dbmsg</h3>";
		echo "<p>We are all set to ";
		$db_list = '';
		foreach ($expected_tables as $table) {
			if ($tables[$table] == 'create') {
				$create[] = $table;
				if (!empty($db_list)) { $db_list .= ', '; }
				$db_list .= "<code>$table</code>";
			}
		}
		if (($nc = count($create)) > 0) {
			echo "create the database table";
			if ($nc > 1) { echo "s"; }
			echo ": $db_list ";
		}
		$db_list = '';
		foreach ($expected_tables as $table) {
			if ($tables[$table] == 'update') {
				$update[] = $table;
				if (!empty($db_list)) { $db_list .= ', '; }
				$db_list .= "<code>$table</code>";
			}
		}
		if (($nu = count($update)) > 0) {
			if ($nc > 0) { echo "and "; }
			echo "update the database table";
			if ($nu > 1) { echo "s"; }
			echo ": $db_list";
		}
		echo ".</p>";
		$task = '';
		if ($nc > 0) {
			$task = "create=" . implode(',', $create);
		}
		if ($nu > 0) {
			if (empty($task)) {
				$task = "update";
			} else {
				$task .= "&update";
			}
		}
		if (isset($_GET['mod_rewrite'])) {
			$mod = '&mod_rewrite='.$_GET['mod_rewrite'];
		}
		echo "<p><a href=\"?checked&$task$mod\" title=\"create and or update the database tables.\" style=\"font-size: 15pt; font-weight: bold;\">Go!</a></p>";
	} else {
		echo "<div class=\"error\">";
		echo "<h3>database did not connect</h3>";
		echo "<p>You should run setup.php to check your configuration. If you haven't created
				the database yet, now would be a good time.";
		echo "</div>";
	}
} else {
	// The config file hasn't been created yet. Show the steps.
	?>

	<div class="error">The zp-config.php file does not exist. You should run
	setup.php to check your configuration and create this file.</div>

	<?php
} ?>
</div>
</div>
</body>
</html>
