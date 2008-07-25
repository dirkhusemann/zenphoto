<?php 
/**
 * install routine for zenphoto
 * @package setup
 */
header ('Content-Type: text/html; charset=UTF-8');
define('HTACCESS_VERSION', '1.1.6.0');  // be sure to change this the one in .htaccess when the .htaccess file is updated.
define('CHMOD_VALUE', 0777);

$debug = isset($_GET['debug']);
if (isset($_POST['debug'])) {
	$debug = isset($_POST['debug']);
}
$checked = isset($_GET['checked']);
$upgrade = false;

if(!function_exists("gettext")) {
	// load the drop-in replacement library
	require_once('lib-gettext/gettext.inc');
	$noxlate = -1;
} else {
	$noxlate = 1;
}
if (!defined('ZENFOLDER')) { define('ZENFOLDER', 'zp-core'); }
if (!defined('PLUGIN_FOLDER')) { define('PLUGIN_FOLDER', '/plugins/'); }
define('OFFSET_PATH', 2);

function setupLog($message, $reset=false) {
  global $debug;
	if ($debug) {
		if ($reset) { $mode = 'w'; } else { $mode = 'a'; }
		$f = fopen(dirname(dirname(__FILE__)) . '/' . ZENFOLDER . '/setup_log.txt', $mode);
		fwrite($f, $message . "\n");
		fclose($f);
	}
}
function updateItem($item, $value) {
	global $zp_cfg;
	$i = strpos($zp_cfg, $item);
	$i = strpos($zp_cfg, '=', $i);
	$j = strpos($zp_cfg, "\n", $i);
	$zp_cfg = substr($zp_cfg, 0, $i) . '= "' . $value . '";' . substr($zp_cfg, $j);
}

if (!$checked) {
	if ($oldconfig = !file_exists('zp-config.php')) {
		@copy('zp-config.php.example', 'zp-config.php');
	}
} else {
	setupLog("Completed system check");
}

if (isset($_POST['mysql'])) { //try to update the zp-config file
	setupLog("MySQL POST handling");
	$zp_cfg = @file_get_contents('zp-config.php');
	if (!$oldconfig) {
		updateItem('UTF-8', 'true');
	}
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
				setupLog("Updated zp-config.php");
				$base = true;
			}
		}
		fclose($handle);
	}
}
$result = true;
if (file_exists("zp-config.php")) {
	require("zp-config.php");
	if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])){
		if (@mysql_select_db($_zp_conf_vars['mysql_database'])) {
			$result = @mysql_query("SELECT `id` FROM " . $_zp_conf_vars['mysql_prefix'].'options' . " LIMIT 1", $connection);
			if ($result) {
				if (mysql_num_rows($result) > 0) $upgrade = true;
			}
			$environ = true;
			require_once("admin-functions.php");
		}
	}
}
if (!function_exists('setOption')) { // setup a primitive environment
	$environ = false;
	require_once('setup-primitive.php');
	require_once('functions-i18n.php');
}

if (!$checked) {
	if (!isset($_POST['mysql'])) {
		setupLog("Zenphoto Setup v".ZENPHOTO_VERSION.'['.ZENPHOTO_RELEASE.']', true);  // initialize the log file
	}
	if ($environ) {
		setupLog("Full environment");
	} else {
		setupLog("Primitive environment");
		if ($result) {
			setupLog("Query error: ".mysql_error());
		}
	}
} else {
	setupLog("Checked");
}

getUserLocale();
$setlocaleresult = setupCurrentLocale();

$taskDisplay = array('create' => gettext("create"), 'update' => gettext("update"));
?>

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
#footer {
	clear: both;
	color: #597580;
	font-size: 85%;
	margin: 8px 30px 0px;
	text-align: right;
}
#footer a {
	color: #597580;
	text-decoration: none;
	border-bottom: 1px dotted #597580;
}
#footer a:hover {
	color: #4B636B;
	text-decoration: none;
	border-bottom: 1px solid #4B636B;
}
/* Login
------------------------------ */
#loginform {
	padding: 10px;
	width: 300px;
	margin: 25px auto;
	font-size: 100%;
	background: #F7F8F9;
	border-top: 1px solid #BAC9CF;
	border-left: 1px solid #BAC9CF;
	border-right: 1px solid #BAC9CF;
	border-bottom: 5px solid #BAC9CF;
}

.button {
	cursor: pointer;
	padding: 5px 10px;
}

label {
	cursor: pointer;
}

label:hover {
	color: #000;
}

#loginform input.textfield {
	margin: 0px;
	font-size: 100%;
	padding: 4px;
}

#loginform table {
	margin: 0px auto;
	border: 0px;
}

#loginform td {
	padding: 4px;
}
</style>

</head>

<body>

<div id="main">

<h1><img src="images/zen-logo.gif" title="<?php echo gettext('Zen Photo Setup'); ?>" align="absbottom" /> 
<?php echo $upgrade ? gettext("Upgrade") : gettext("Setup") ; ?>
</h1>

<div id="content">
<?php
if (!$checked) {
	// Some descriptions about setup/upgrade.
  if ($upgrade) { 
    echo gettext("Zenphoto has detected that you're upgrading to a new version.");
		echo '<br /><br />';
	} else { 
		echo gettext("Welcome to Zenphoto! This page will set up Zenphoto on your web server."); 
	}
	echo '<br /><br />';
	echo '<strong>';
	echo gettext("Systems Check:"); 
	echo '</strong><br />';

	/*****************************************************************************
	 *                                                                           *
	 *                             SYSTEMS CHECK                                 *
	 *                                                                           *
	 ******************************************************************************/
	global $_zp_conf_vars;

	function checkMark($check, $text, $sfx, $msg) {
		$dsp = '';
		if ($check > 0) {$check = 1; }
		echo "\n<br/><span class=\"";
		switch ($check) {
			case 0: $dsp = "fail"; break;
			case -1: $dsp = "warn"; break;
			case 1: $dsp = "pass"; break;
		}
		echo $dsp."\">$text</span>";
		$dsp .= ' '.trim($text);
		if ($check <= 0) {
			if (!empty($sfx)) { 
				echo $sfx; 
				$dsp .= ' '.trim($sfx);
			}
			if (!empty($msg)) { 
				echo "\n<p class=\"error\">$msg</p>"; 
				$dsp .= ' '.trim($msg);
			}
		}
		setupLog($dsp);
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
			if (!$external) $d = " ".gettext("and <strong>setup</strong> could not create it");
			$sfx = " [<em>$append</em> ".gettext("does not exist")."$d]";
			$msg = " ".gettext("You must create the folder")." $folder. <code>mkdir($path, 0777)</code>.";
		} else if (!is_writable($path)) {
			$sfx = " [<em>$append</em> ".gettext("is not writeable and").' '."<strong>setup</strong> ".gettext("could not make it so]");
			$msg =  gettext("Change the permissions on the")." <code>$folder</code> ".gettext("folder to be writable by the server").' ' .
							"(<code>chmod 777 " . $append . "</code>)";
		} else {
			if (($folder != $which) || $external) {
				$f = " (<em>$append</em>)";
			}
			$msg = '';
			$sfx = '';
		}

		return checkMark(is_dir($path) && is_writable($path), " <em>$which</em>".' '.gettext("folder").$f, $sfx, $msg);
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
	$good = checkMark(versionCheck($required, $phpv), " ".gettext("PHP version")." $phpv", "", gettext("Version").' '.$required.' '.gettext("or greater is required.")) && $good;

	if (ini_get('safe_mode')) {
		$safe = -1;
	} else {
		$safe = true;
	}
	checkMark($safe, gettext("PHP Safe Mode"), ' '.gettext("[is set]"), gettext("Zenphoto functionality is reduced when PHP <code>safe mode</code> restrictions are in effect."));

	/* Check for GD and JPEG support. */
	$gd = extension_loaded('gd');
	$good = checkMark($gd, ' '.gettext("PHP GD support"), ' '.gettext('is not installed'), gettext('You need to install GD support in your PHP')) && $good;
	if ($gd) {
		$imgtypes = imagetypes();
		$missing = array();
		if (!($imgtypes & IMG_JPG)) { $missing[] = 'JPEG'; }
		if (!($imgtypes & IMG_GIF)) { $missing[] = 'GIF'; }
		if (!($imgtypes & IMG_PNG)) { $missing[] = 'PNG'; }
		if (count($missing) > 0) {
			if (count($missing) < 3) {
				$imgmissing = $missing[0];
				if (count($missing) == 2) { $imgmissing .= ' or '.$missing[1]; }
				$err = -1;
				$mandate = gettext("should");
			} else {
				$imgmissing = $missing[0].', '.$missing[1].', or '.$missing[2];
				$err = 0;
				$good = false;
				$mandate = gettext("need to");
			}
			checkMark($err, ' '.gettext("PHP GD image support"), '', gettext("Your PHP GD does not support")." $imgmissing. ".
	                    "<br/>".gettext("The unsupported image types will not be viewable in your albums.").
	                    "<br/>".gettext("To correct this you").' '.$mandate.' '.gettext("install GD with appropriate image support in your PHP"));
		}
	}

	/* check to see if glob() works */
	if (function_exists('safe_glob')) {
		$list = safe_glob('*.php');
		$gl = count($list) > 0;
	} else {
		$list = glob('*.php');
		if ($list !== false) {
			$gl = 1;
		} else {
			$gl = -1;
		}
	}
	$good = checkMark($gl, ' '.gettext("PHP <code>glob()</code> support"), ' '.gettext('is disabled'), gettext('You need to set the define <code>SAFE_GLOB</code> to <code>true</code> in <code>functions.php</code>')) && $good;

	checkMark($noxlate, gettext("PHP <code>gettext()</code> support"), ' '.gettext("[is not present]"), gettext("Localization of Zenphoto currently requires native PHP <code>gettext()</code> support"));
	if ($setlocaleresult === false) {
		checkMark(-1, 'PHP <code>setlocale()</code>', ' '.gettext("failed"), gettext("Locale functionality is not implemented on your platform or the specified locale does not exist. Language translation may not work."));
	}
	if (function_exists('mb_internal_encoding')) {
		$mb = 1;
	} else {
		$mb= -1;
	}
	checkMark($mb, gettext("PHP <code>mbstring</code> package"), ' '.gettext("[is not present]"), gettext("Strings generated internally by PHP may not display correctly. (e.g. dates)"));

	$sql = extension_loaded('mysql');
	$good = checkMark($sql, ' '.gettext(" PHP MySQL support"), '', gettext('You need to install MySQL support in your PHP')) && $good;
	if (file_exists("zp-config.php")) {
		require("zp-config.php");
		$cfg = true;
	} else {
		$cfg = false;
	}
	$good = checkMark($cfg, " <em>zp-config.php</em> ".gettext("file"), ' '.gettext("[does not exist]"),
 							gettext("Edit the <code>zp-config.php.example</code> file and rename it to <code>zp-config.php</code>").' ' .
 							"<br/><br/>".gettext("You can find the file in the \"zp-core\" directory.")) && $good;
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
		if ($adminstuff = (!$sql || !$connection  || !$db) && is_writable('zp-config.php')) {
			$good = checkMark(false, ' '.gettext("MySQL setup in").' zp-config.php', '', '') && $good;
			// input form for the information
			?>

<div class="error">
<?php echo gettext("Fill in the missing information below and <strong>setup</strong> will attempt to update your <code>zp-config.php</code> file."); ?><br />
<br />
<form action="#" method="post">
<input type="hidden" name="mysql"	value="yes" />
<?php 
if ($debug) {
	echo '<input type="hidden" name="debug" />';
}
?>
<table>
	<tr>
		<td><?php echo gettext("MySQL admin user") ?></td>
		<td><input type="text" size="40" name="mysql_user"
			value="<?php echo $_zp_conf_vars['mysql_user']?>" />&nbsp;*</td>
	</tr>
	<tr>
		<td><?php echo gettext("MySQL admin password") ?></td>
		<td><input type="password" size="40" name="mysql_pass"
			value="<?php echo $_zp_conf_vars['mysql_pass']?>" />&nbsp;*</td>
	</tr>
	<tr>
		<td><?php echo gettext("MySQL host") ?></td>
		<td><input type="text" size="40" name="mysql_host"
			value="<?php echo $_zp_conf_vars['mysql_host']?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("MySQL database") ?></td>
		<td><input type="text" size="40" name="mysql_database"
			value="<?php echo $_zp_conf_vars['mysql_database']?>" />&nbsp;*</td>
	</tr>
	<tr>
		<td><?php echo gettext("Database table prefix") ?></td>
		<td><input type="text" size="40" name="mysql_prefix"
			value="<?php echo $_zp_conf_vars['mysql_prefix']?>" /></td>
	</tr>
	<tr>
		<td></td>
		<td align="right">* <?php echo gettext("required") ?></td>
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
			$good = checkMark(!$adminstuff, ' '.gettext("MySQL setup in <em>zp-config.php</em>"), '',
											gettext("You have not set your <strong>MySQL</strong> <code>user</code>, <code>password</code>, etc. in your <code>zp-config.php</code> file and <strong>setup</strong> is not able to write to the file.")) && $good;
		}
	}
	$good = checkMark($connection, ' '.gettext("connect to MySQL"), '', gettext("Could not connect to the <strong>MySQL</strong> server. Check the <code>user</code>, <code>password</code>, and <code>database host</code> in your <code>zp-config.php</code> file and try again.").' ') && $good;
	if ($connection) {
		$good = checkMark($sqlv, ' '.gettext("MySQL version").' '.$mysqlv, "", gettext("Version").' '.$required.' '.gettext("or greater is required")) && $good;
		$good = checkMark($db, ' '.gettext("connect to the database <code>") . $_zp_conf_vars['mysql_database'] . "</code>", '',
			gettext("Could not access the <strong>MySQL</strong> database")." (<code>" . $_zp_conf_vars['mysql_database'] ."</code>). ".gettext("Check the <code>user</code>, <code>password</code>, and <code>database name</code> and try again.").' ' .
			gettext("Make sure the database has been created, and the <code>user</code> has access to it.").' ' .
			gettext("Also check the <code>MySQL host</code>.")) && $good;

		$dbn = "`".$_zp_conf_vars['mysql_database']. "`.*";
		$sql = "SHOW GRANTS;";
		$result = mysql_query($sql, $mysql_connection);
		$access = -1;
		$rightsfound = 'unknown';
		if ($result) {
			$report = "<br/><br/><em>".gettext("Grants found:")."</em>";
			while ($row = mysql_fetch_row($result)) {
				$report .= "<br/><br/>".$row[0];
				$r = str_replace(',', '', $row[0]);
				$i = strpos($r, "ON");
				$j = strpos($r, "TO", $i);
				$found = stripslashes(trim(substr($r, $i+2, $j-$i-2)));
				$rights = array_flip(explode(' ', $r));
				$rightsfound = 'insufficient';
				if (($found == $dbn) || ($found == "*.*")) {
					if (isset($rights['ALL']) || (isset($rights['SELECT']) && isset($rights['CREATE']) && 
							isset($rights['DROP']) && isset($rights['INSERT']) &&	isset($rights['UPDATE']) && 
							isset($rights['ALTER']) && isset($rights['DELETE']))) {
						$access = 1;
					}
					$report .= " *";
				}
			}
		} else {
			$report = "<br/><br/>".gettext("The <em>SHOW GRANTS</em> query failed.");
		}
		checkMark($access, ' '.gettext("MySQL access rights"), " [$rightsfound]",
 											gettext("Your MySQL user must have <code>Create</code>, <code>Drop</code>, <code>Select</code>, <code>Insert</code>, <code>Alter</code>, <code>Update</code>, and <code>Delete</code> rights.") . $report);

		$sql = "SHOW TABLES FROM `".$_zp_conf_vars['mysql_database']."` LIKE '".$_zp_conf_vars['mysql_prefix']."%';";
		$result = mysql_query($sql, $mysql_connection);
		$tableslist = '';
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$tableslist .= "<code>" . $row[0] . "</code>, ";
			}
		}
		if (!empty($tableslist)) { $tableslist = ' '.gettext("found").' '.substr($tableslist, 0, -2); }
		if (!$result) { $result = -1; }
		$dbn = $_zp_conf_vars['mysql_database'];
		checkMark($result, ' '.gettext("MySQL <em>show tables</em>")."$tableslist", ' '.gettext("[Failed]"), gettext("MySQL did not return a list of the database tables for <code>$dbn</code>.") .
 											"<br/>".gettext("<strong>Setup</strong> will attempt to create all tables. This will not over write any existing tables."));

	}

	$msg = " <em>.htaccess</em> ".gettext("file");
	if (!stristr($_SERVER['SERVER_SOFTWARE'], "apache") && !stristr($_SERVER['SERVER_SOFTWARE'], "litespeed")) {
		checkMark(-1, gettext("Server seems not to be Apache or Apache-compatible, skipping <code>.htaccess</code> test"), "", "");
	}	else {
		$htfile = '../.htaccess';
		$ht = @file_get_contents($htfile);
		$htu = strtoupper($ht);
		$vr = "";
		$ch = 1;
		if (empty($htu)) {
			$ch = -1;
			$err = gettext("is empty or does not exist");
			$desc = gettext("Edit the <code>.htaccess</code> file in the root zenphoto folder if you have the mod_rewrite module, and want cruft-free URLs.")
			.gettext("Just change the one line indicated to make it work.").' ' .
						"<br/><br/>".gettext("You can ignore this warning if you do not intend to set the option <code>mod_rewrite</code>.");
		} else {
			$i = strpos($htu, 'VERSION');
			if ($i !== false) {
				$j = strpos($htu, "\n", $i+7);
				$vr = trim(substr($htu, $i+7, $j-$i-7));
			}
			$ch = $vr == HTACCESS_VERSION;
			$err = gettext("wrong version");
			$desc = gettext("You need to upload the copy of the .htaccess file that was included with the zenphoto distribution.");
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
				$msg .= ' '.gettext("(<em>RewriteEngine</em> is").' '."<strong>$rw</strong>)";
				$mod = "&mod_rewrite=$rw";
			}
		}
		checkMark($ch, $msg, " [$err]", $desc);
	}

	$base = true;
	$f = '';
	if ($rw == 'ON') {
		$d = dirname(dirname($_SERVER['SCRIPT_NAME']));
		$i = strpos($htu, 'REWRITEBASE', $j);
		if ($i === false) {
			$base = false;
			$b = "<em>".gettext("missing")."</em>";
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
		$good = checkMark($base, gettext("<em>.htaccess</em> RewriteBase is")." <code>$b</code> $f", ' '.gettext("[Does not match install folder]"),
											gettext("Setup was not able to write to the file change RewriteBase match the install folder.") .
											"<br/>".gettext("Either make the file writeable or set <code>RewriteBase</code> in your <code>.htaccess</code> file to")." <code>$d</code>.") && $good;
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
		echo "<p>".gettext("You need to address the problems indicated above then run <code>setup.php</code> again.")."</p>";
		if ($noxlate > 0) {
			echo "\n</div>";
			echo "\n<div>\n";
			echo '<form action="#'.'" method="post">'."\n";
			if ($debug) {
				echo '<input type="hidden" name="debug" />';
			}
			echo gettext("Select a language:").' ';
			echo '<select id="dynamic-locale" name="dynamic-locale" onchange="this.form.submit()">'."\n";
			generateLanguageOptionList();
			echo "</select>\n";
			echo "</form>\n";
			echo "</div>\n";
		}
		printadminfooter();
		echo "</div>";
		echo "</body>";
		echo "</html>";
		exit();
	}
} else {
	$dbmsg = gettext("database connected");
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
		$_zp_conf_vars['mysql_prefix'].'administrators', $_zp_conf_vars['mysql_prefix'].'admintoalbum',
		$_zp_conf_vars['mysql_prefix'].'tags', $_zp_conf_vars['mysql_prefix'].'obj_to_tag');
		foreach ($expected_tables as $needed) {
			if (!isset($tables[$needed])) {
				$tables[$needed] = 'create';
			}
		}
		
		if (!($tables[$_zp_conf_vars['mysql_prefix'].'administrators'] == 'create')) {
			if (!($_zp_loggedin & ADMIN_RIGHTS) && (!isset($_GET['create']) && !isset($_GET['update']))) {  // Display the login form and exit.
				if ($_zp_loggedin) { echo "<br /><br/>".gettext("You need <em>USER ADMIN</em> rights to run setup."); }
				printLoginForm("/" . ZENFOLDER . "/setup.php?checked$mod", false);
				echo "\n</div>";
				printAdminFooter();
				echo "\n</body>";
				echo "\n</html>";
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
	$tbl_tags = prefix('tags');
	$tbl_obj_to_tag = prefix('obj_to_tag');
	// Prefix the constraint names:
	$cst_images = prefix('images_ibfk1');

	$db_schema = array();

	/***********************************************************************************
	 Add new fields in the upgrade section. This section should remain static except for new
	 tables. This tactic keeps all changes in one place so that noting gets accidentaly omitted.
	************************************************************************************/
	
	//v1.1.7
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'options'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_options (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`ownerid` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`name` varchar(255) NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`name`, `ownerid`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'tags'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_tags (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`name`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'obj_to_tag'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_obj_to_tag (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`tagid` int(11) UNSIGNED NOT NULL,
		`type` tinytext,
		`objectid` int(11) UNSIGNED NOT NULL,
		PRIMARY KEY  (`id`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	}
	
	// v. 1.1.5
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'administrators'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_administrators (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`user` varchar(64) NOT NULL,
		`password` text NOT NULL,
		`name` text,
		`email` text,
		`rights` int,
		PRIMARY KEY  (`id`),
		UNIQUE (`user`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'admintoalbum'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_admintoalbum (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`adminid` int(11) UNSIGNED NOT NULL,
		`albumid` int(11) UNSIGNED NOT NULL,
		PRIMARY KEY  (`id`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	}

	// v. 1.1
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'options'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_options (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`name` varchar(64) NOT NULL,
		`value` text NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`name`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	}

	// base implementation
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'albums'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_albums (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
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
		`hitcounter` int(11) unsigned default 0,
		`password` varchar(255) default NULL,
		`password_hint` text,
		PRIMARY KEY  (`id`),
		KEY `folder` (`folder`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
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
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
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
		`commentson` int(1) NOT NULL default '1',
		`show` int(1) NOT NULL default '1',
		`date` datetime default NULL,
		`sort_order` int(11) unsigned default NULL,
		`height` int(10) unsigned default NULL,
		`width` int(10) unsigned default NULL,
		`mtime` int(32) default NULL,
		`EXIFValid` int(1) unsigned default NULL,
		`hitcounter` int(11) unsigned default 0,
		`total_value` int(11) unsigned default '0',
		`total_votes` int(11) unsigned default '0',
		`used_ips` longtext,
		PRIMARY KEY  (`id`),
		KEY `filename` (`filename`,`albumid`)
		)	CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		$db_schema[] = "ALTER TABLE $tbl_images ".
			"ADD CONSTRAINT $cst_images FOREIGN KEY (`albumid`) REFERENCES $tbl_albums (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
	}

	/****************************************************************************************
	 ******                             UPGRADE SECTION                                ******
	 ******                                                                            ******
	 ******                          Add all new fields below                          ******
	 ******                                                                            ******
	 ****************************************************************************************/
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
//v1.1.7 omits	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `tags` text;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `location` tinytext;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `city` tinytext;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `state` tinytext;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `country` tinytext;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `credit` tinytext;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `copyright` tinytext;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `date` datetime default NULL;";
//v1.1.7 omits	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `tags` text;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `EXIFValid` int(1) UNSIGNED default NULL;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `hitcounter` int(11) UNSIGNED default 0;";
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
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `hitcounter` int(11) UNSIGNED default 0;";

	//v1.1.4
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD COLUMN `type` varchar(52) NOT NULL default 'images';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `total_value` int(11) UNSIGNED default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `total_votes` int(11) UNSIGNED default '0';";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `used_ips` longtext;";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `custom_data` text default NULL";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `custom_data` text default NULL";
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `password` `password` varchar(255) NOT NULL DEFAULT ''";

	//v1.1.5
	$sql_statements[] = " ALTER TABLE `zp_comments` DROP FOREIGN KEY `zp_comments_ibfk1`";
	$sql_statements[] = "ALTER TABLE $tbl_comments CHANGE `imageid` `ownerid` int(11) UNSIGNED NOT NULL default '0';";
  //	$sql_statements[] = "ALTER TABLE $tbl_comments DROP INDEX `imageid`;";
	$sql = "SHOW INDEX FROM `".$_zp_conf_vars['mysql_prefix']."comments`";
	$result = mysql_query($sql, $mysql_connection);
	$hasownerid = false;
	if ($result) {
		while ($row = mysql_fetch_row($result)) {
			if ($row[2] == 'ownerid') {
				$hasownerid = true;
			} else {
				if ($row[2] != 'PRIMARY') {
					$sql_statements[] = "ALTER TABLE $tbl_comments DROP INDEX `".$row[2]."`;";
				}
			}
		}
	}
	if (!$hasownerid) {
		$sql_statements[] = "ALTER TABLE $tbl_comments ADD INDEX (`ownerid`);";
	}
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `dynamic` int(1) UNSIGNED default '0'";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `search_params` text default NULL";
	
	//v1.1.6
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `album_theme` text default NULL";
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD COLUMN `IP` text default NULL";
	
	//v1.1.7
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD COLUMN `private` int(1) UNSIGNED default 0";
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD COLUMN `anon` int(1) UNSIGNED default 0";
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `user` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci default ''"; 	
	$sql_statements[] = "ALTER TABLE $tbl_tags CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$sql_statements[] = "ALTER TABLE $tbl_tags CHANGE `name` `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci";	
	$sql_statements[] = "ALTER TABLE $tbl_administrators CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$sql_statements[] = "ALTER TABLE $tbl_administrators CHANGE `name` `name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci";	
	$sql_statements[] = "ALTER TABLE $tbl_options ADD COLUMN `ownerid` int(11) UNSIGNED NOT NULL DEFAULT 0";
	$sql_statements[] = "ALTER TABLE $tbl_options DROP INDEX `name`";
	$sql_statements[] = "ALTER TABLE $tbl_options ADD UNIQUE `unique_option` (`name`, `ownerid`)";

	//v1.1.8 
	$sql_statements[] = "ALTER TABLE $tbl_options CHANGE `ownerid` `ownerid` int(11) UNSIGNED NOT NULL DEFAULT 0"; 
	$sql_statements[] = "ALTER TABLE $tbl_admintoalbum CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$sql_statements[] = "ALTER TABLE $tbl_obj_to_tag CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$sql_statements[] = "ALTER TABLE $tbl_options CHANGE `name` `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci";	
	
	
	
	/**************************************************************************************
	 ******                            END of UPGRADE SECTION                                                           ******
	 ******                                                                                                                                                     ******
	 ******                           Add all new fields above                                                                  ******
	 ******                                                                                                                                                     ******
	 ***************************************************************************************/

	$createTables = true;
	if (isset($_GET['create']) || isset($_GET['update']) && db_connect()) {
		echo "<h3>".gettext("About to").' '.$taskDisplay[substr($task,0,8)].' '.gettext("tables")."...</h3>";
		setupLog("Begin table creation");
		foreach($db_schema as $sql) {
			$result = mysql_query($sql);
			if (!$result) {
				$createTables = false;
				setupLog("MySQL Query"." ( $sql ) "."Failed. Error: ".mysql_error());
				echo '<div class="error">';
				echo gettext('Table creation failure').': '.mysql_error(); 
				echo '</div>';
			} else {
				setupLog("MySQL Query"." ( $sql ) "."Success.");
			}
		}
		// always run the update queries to insure the tables are up to current level
		setupLog("Begin table updates");
		foreach($sql_statements as $sql) {
			$result = mysql_query($sql);
			if (!$result) {
				setupLog("MySQL Query"." ( $sql ) ".gettext("Failed. Error:").' '.mysql_error());
			} else {
				setupLog("MySQL Query"." ( $sql ) ".gettext("Success."));
			}
		}

		// set defaults on any options that need it
		setupLog("Done with database creation and update");
		
		$prevRel = getOption('zenphoto_release');
		
		setupLog("Previous Release was $prevRel");
		
		$gallery = new Gallery();
		require('setup-option-defaults.php');
		
		// 1.1.6 special cleanup section for plugins
		$badplugs = array ('exifimagerotate.php', 'flip_image.php', 'image_mirror.php', 'image_rotate.php', 'supergallery-functions.php');
		foreach ($badplugs as $plug) {
			$path = SERVERPATH . '/' . ZENFOLDER . '/plugins/' . $plug;
			@unlink($path);
		}
		
		if ($prevRel < 1690) {  // cleanup root album DB records
			$gallery->garbageCollect(true, true);
		}
		
		// 1.1.7 conversion to/from the theme option tables
		$albums = $gallery->getAlbums();
		foreach ($albums as $albumname) {
			$album = new Album($gallery, $albumname);
			$theme = $album->getAlbumTheme();
			if (!empty($theme)) {
				if (ALBUM_OPTIONS_TABLE) { // convert any old style album theme option tables.
					$tbl = prefix(getOptionTableName($album->name));
					$sql = "SELECT `name`,`value` FROM " . $tbl;
					$result = query_full_array($sql, true);
					if (is_array($result)) {
						foreach ($result as $row) {
							setThemeOption($album, $row['name'], $row['value']);
						}
					}
					query('DROP TABLE '.$tbl, true);
				} else { // convert back to individual tables
					$result = query_full_array('SELECT * FROM '.prefix('options'),' WHERE `ownerid`!=0');  
					if (count($result) > 0) { // there was use of the album options in the options table
						$tbl_options = prefix(getOptionTableName($album->name));
						$sql = "CREATE TABLE IF NOT EXISTS $tbl_options (
							`id` int(11) unsigned NOT NULL auto_increment,
							`name` varchar(64) NOT NULL,
							`value` text NOT NULL,
							PRIMARY KEY  (`id`),
							UNIQUE (`name`)
							);";
						query($sql);
						$sql = "SELECT `name`,`value` FROM ".prefix('options').' WHERE `ownerid`='.$album->id;
						$result = query_full_array($sql);
						if (is_array($result)) {
							foreach ($result as $option) {
								query('INSERT INTO '.$tbl_options.'(`name`, `value`) VALUES ("'.$option['name'].'","'.$option['value'].'")');
							}
						}
					}
					query('DELETE FROM '.prefix('options').' WHERE `ownerid`='.$album->id);
				}
			}
		}

		// 1.2 force up-convert to tag tables
		$convert = false;
		$result = query_full_array("SHOW COLUMNS FROM ".prefix('images').' LIKE "%tags%"');
		if (is_array($result)) {
			foreach ($result as $row) {
				if ($row['Field'] == 'tags') {
					// TODO: 1.2 set this to true
					$convert = true;
					break;
				}
			}
		}
		if ($convert) {
			// convert the tags to a table
			$result = query_full_array("SELECT `tags` FROM ". prefix('images'));
			foreach($result as $row){
				$alltags = $alltags.$row['tags'].",";  // add comma after the last entry so that we can explode to array later
			}
			$result = query_full_array("SELECT `tags` FROM ". prefix('albums'));
			foreach($result as $row){
				$alltags = $alltags.$row['tags'].",";  // add comma after the last entry so that we can explode to array later
			}
			$alltags = explode(",",$alltags);
			$taglist = array();
			$seen = array();
			foreach ($alltags as $tag) {
				$clean = trim($tag);
				if (!empty($clean)) {
					$tagLC = utf8::strtolower($clean);
					if (!in_array($tagLC, $seen)) {
						$seen[] = $tagLC;
						$taglist[] = $clean;
					}
				}
			}
			$alltags = array_merge($taglist);
			foreach ($alltags as $tag) {
				query("INSERT INTO " . prefix('tags') . " (name) VALUES ('" . escape($tag) . "')", true);
			}
			$sql = "SELECT `id`, `tags` FROM ".prefix('albums');
			$result = query_full_array($sql);
			if (is_array($result)) {
				foreach ($result as $row) {
					if (!empty($row['tags'])) {
						$tags = explode(",", $row['tags']);
						storeTags($tags, $row['id'], 'albums');
					}
				}
			}
			$sql = "SELECT `id`, `tags` FROM ".prefix('images');
			$result = query_full_array($sql);
			if (is_array($result)) {
				foreach ($result as $row) {
					if (!empty($row['tags'])) {
						$tags = explode(",", $row['tags']);
						storeTags($tags, $row['id'], 'images');
					}
				}
			}
			query("ALTER TABLE ".prefix('albums')." DROP COLUMN `tags`");
			query("ALTER TABLE ".prefix('images')." DROP COLUMN `tags`");
		}

		echo "<h3>".gettext("Done with table").' '.$taskDisplay[substr($task,0,8)];
		if (!$createTables) echo ' '.gettext('with errors');
		echo "!</h3>";

		if ($createTables) {
			if ($_zp_loggedin == ADMIN_RIGHTS) {
				echo "<p>".gettext("You need to")." <a href=\"admin-options.php\">".gettext("set your admin user and password")."</a>.</p>";
			} else {
				echo "<p>".gettext("You can now")." <a href=\"../\">".gettext("View your gallery")."</a>".gettext(", or")." <a href=\"admin.php\">".gettext("administrate.")."</a></p>";
			}
		}

	} else if (db_connect()) {
		echo "<h3>$dbmsg</h3>";
		echo "<p>".gettext("We are all set to")." ";
		$db_list = '';
		$create = array();
		foreach ($expected_tables as $table) {
			if ($tables[$table] == 'create') {
				$create[] = $table;
				if (!empty($db_list)) { $db_list .= ', '; }
				$db_list .= "<code>$table</code>";
			}
		}
		if (($nc = count($create)) > 0) {
			if ($nc > 1) {
			  echo gettext("create the database tables");
			} else {
			  echo gettext("create the database table");
			}
			echo ": $db_list ";
		}
		$db_list = '';
		$update = array();
		foreach ($expected_tables as $table) {
			if ($tables[$table] == 'update') {
				$update[] = $table;
				if (!empty($db_list)) { $db_list .= ', '; }
				$db_list .= "<code>$table</code>";
			}
		}
		if (($nu = count($update)) > 0) {
			if ($nc > 0) { echo "and "; }
			echo gettext("update the database table");
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
		if ($debug) {
			$task .= '&debug';
		}
		if (isset($_GET['mod_rewrite'])) {
			$mod = '&mod_rewrite='.$_GET['mod_rewrite'];
		}
		echo "<p><a href=\"?checked&$task$mod\" title=\"".gettext("create and or update the database tables.")."\" style=\"font-size: 15pt; font-weight: bold;\">".gettext("Go!")."</a></p>";
	} else {
		echo "<div class=\"error\">";
		echo "<h3>".gettext("database did not connect")."</h3>";
		echo "<p>".gettext("You should run setup.php to check your configuration. If you haven't created the database yet, now would be a good time.");
		echo "</div>";
	}
} else {
	// The config file hasn't been created yet. Show the steps.
	?>

<div class="error"><?php echo gettext("The zp-config.php file does not exist. You should run setup.php to check your configuration and create this file."); ?></div>

<?php
}

?>
</div>
<?php
if (($noxlate > 0) && !isset($_GET['create']) && !isset($_GET['update'])) {
	echo "\n<div>\n";
	echo '<form action="#'.'" method="post">'."\n";
	if ($debug) {
		echo '<input type="hidden" name="debug" />';
	}
	echo gettext("Select a language:").' ';
	echo '<select id="dynamic-locale" name="dynamic-locale" onchange="this.form.submit()">'."\n";
	generateLanguageOptionList();
	echo "</select>\n";
	echo "</form>\n";
	echo "</div>\n";
}
printAdminFooter();
?></div>
</body>
</html>

