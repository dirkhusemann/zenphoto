<?php
/**
 * install routine for zenphoto
 * @package setup
 */

// force UTF-8 Ø

define('OFFSET_PATH', 2);

require_once(dirname(__FILE__).'/folder-definitions.php');
header("HTTP/1.0 200 OK");
header("Status: 200 OK");
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
header ('Content-Type: text/html; charset=UTF-8');

define('CONFIGFILE',dirname(dirname(__FILE__)).'/'.DATA_FOLDER.'/zp-config.php');
define('HTACCESS_VERSION', '1.2.8.0');  // be sure to change this the one in .htaccess when the .htaccess file is updated.

$permission_names = array(//0700=>gettext('paranoid'), // beware of this one, it can severly break things!!!
													0750=>gettext('strict+'),
													0755=>gettext('strict'),
													0775=>gettext('relaxed'),
													0777=>gettext('loose')
													);

$debug = isset($_REQUEST['debug']);

$setup_checked = isset($_GET['checked']);
$upgrade = false;

if(!function_exists("gettext")) {
	require_once(dirname(__FILE__).'/lib-gettext/gettext.inc');
	$noxlate = -1;
} else {
	$noxlate = 1;
}

require_once(dirname(__FILE__).'/lib-utf8.php');
$const_webpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
$const_webpath = str_replace("\\", '/', $const_webpath);
if ($const_webpath == '/') $const_webpath = '';
$serverpath = str_replace("\\", '/', dirname(dirname(__FILE__)));

$chmod = fileperms(dirname(__FILE__))&0777;

$en_US = dirname(__FILE__).'/locale/en_US/';
if (!file_exists($en_US)) {
	@mkdir(dirname(__FILE__).'/locale/', $chmod);
	@mkdir($en_US, $chmod);
}

function setupLog($message, $anyway=false, $reset=false) {
  global $debug, $chmod;  
	if ($debug || $anyway) {
		if (!file_exists(dirname(dirname(__FILE__)).'/'.DATA_FOLDER)) {
			mkdir_recursive(dirname(dirname(__FILE__)).'/'.DATA_FOLDER, $chmod);
		}
		if ($reset) { $mode = 'w'; } else { $mode = 'a'; }
		$f = fopen(dirname(dirname(__FILE__)).'/'.DATA_FOLDER . '/setup_log.txt', $mode);
		fwrite($f, $message . "\n");
		fclose($f);
	}
}

function updateItem($item, $value) {
	global $zp_cfg;
	$i = strpos($zp_cfg, $item);
	$i = strpos($zp_cfg, '=', $i);
	$j = strpos($zp_cfg, "\n", $i);
	$zp_cfg = substr($zp_cfg, 0, $i) . '= \'' . str_replace('\'', '\\\'',$value) . '\';' . substr($zp_cfg, $j);
}

function checkAlbumParentid($albumname, $id) {
	Global $_zp_gallery;
	$album = new Album($_zp_gallery, $albumname);	
	$oldid = $album->get('parentid');
	if ($oldid !== $id) {
		$album->set('parentid', $id);
		$album->save();
		if (is_null($oldid)) $oldid = '<em>NULL</em>';
		if (is_null($id)) $id = '<em>NULL</em>';
		printf('Fixed album <strong>%1$s</strong>: parentid was %2$s should have been %3$s<br />', $albumname,$oldid, $id);
	}
	$id = $album->id;
	if (!$album->isDynamic()) {
		$albums = $album->getAlbums();
		foreach ($albums as $albumname) {
			checkAlbumParentid($albumname, $id);
		}
	}
}

function getOptionTableName($albumname) {
	$pfxlen = strlen(prefix(''));
	if (strlen($albumname) > 54-$pfxlen) { // table names are limited to 62 characters
		return substr(substr($albumname, 0, max(0,min(24-$pfxlen, 20))).'_'.md5($albumname),0,54-$pfxlen).'_options';
	}
	return $albumname.'_options';
}

if (!file_exists(CONFIGFILE)) {
	if (!file_exists(dirname(dirname(__FILE__)).'/'.DATA_FOLDER)) {
		@mkdir(dirname(dirname(__FILE__)).'/'.DATA_FOLDER, $chmod);
	}
	if (file_exists(dirname(dirname(__FILE__)).'/'.ZENFOLDER.'/zp-config.php')) {
		@copy(dirname(dirname(__FILE__)).'/'.ZENFOLDER.'/zp-config.php', CONFIGFILE);
		@unlink(dirname(dirname(__FILE__)).'/'.ZENFOLDER.'/zp-config.php');
		$newconfig = false;
	} else {
		$newconfig = true;
		@copy('zp-config.php.source', CONFIGFILE);
	}
} else {
	$newconfig = false;
}

$zp_cfg = @file_get_contents(CONFIGFILE);
$updatezp_config = false;
if (isset($_GET['mod_rewrite'])) {
	$mod = '&mod_rewrite='.$_GET['mod_rewrite'];
} else {
	$mod = '';
}


$i = strpos($zp_cfg, 'define("DEBUG", false);');
if ($i !== false) {
	$updatezp_config = true;
	$j = strpos($zp_cfg, "\n", $i);
	$zp_cfg = substr($zp_cfg, 0, $i) . substr($zp_cfg, $j); // remove this so it won't be defined twice
}

if (isset($_POST['mysql'])) { //try to update the zp-config file
	setupLog(gettext("MySQL POST handling"));
	$updatezp_config = true;
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
}

if ($updatechmod = isset($_REQUEST['chmod_permissions'])) {
	$selected = round($_REQUEST['chmod_permissions']);
	$c = 0;
	if ($selected>=0 && $selected<count($permission_names)) {
		foreach ($permission_names as $key=>$name) {
			if ($c == $selected) {
				$chmod = $key;
				break; 
			}
			$c++;
		}
	} else {
		$updatechmod = false;
	}
}
if ($updatechmod || $newconfig) {
	$i = strpos($zp_cfg, "define('CHMOD_VALUE',");
	if ($i === false) {
		$i = strpos($zp_cfg, "define('SERVERPATH',");
		$i = strpos($zp_cfg, ';', $i);
		$i = strpos($zp_cfg, '/**', $i);
		$zp_cfg = substr($zp_cfg, 0, $i)."if (!defined('CHMOD_VALUE')) { define('CHMOD_VALUE', ".sprintf('0%o', $chmod)."); }\n".substr($zp_cfg, $i);
	} else {
		$i = $i +21;
		$j = strpos($zp_cfg, ")", $i);
		$zp_cfg = substr($zp_cfg, 0, $i) . sprintf('0%o',$chmod) . substr($zp_cfg, $j);
	}
	$updatezp_config = true;
}

if ($updatezp_config) {
	@chmod(CONFIGFILE, 0666 & $chmod);
	if (is_writeable(CONFIGFILE)) {
		if ($handle = fopen(CONFIGFILE, 'w')) {
			if (fwrite($handle, $zp_cfg)) {
				setupLog(gettext("Updated zp-config.php"));
				$base = true;
			}
		}
		fclose($handle);
	}
}

$result = true;
$environ = false;
$DBcreated = false;
$oktocreate = false;
$connectDBErr = '';
if (file_exists(CONFIGFILE)) {
	require(CONFIGFILE);
	if (defined('CHMOD_VALUE')) {
		$chmod = CHMOD_VALUE;
	}
	if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])){
		if (substr(trim(mysql_get_server_info()), 0, 1) > '4') {
			$collation = ' CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		} else {
			$collation = '';
		}
		if (@mysql_select_db($_zp_conf_vars['mysql_database'])) {
			$result = @mysql_query("SELECT `id` FROM " . $_zp_conf_vars['mysql_prefix'].'options' . " LIMIT 1", $connection);
			if ($result) {
				if (@mysql_num_rows($result) > 0) $upgrade = true;
			}
			$environ = true;
			require_once(dirname(__FILE__).'/admin-functions.php');
		} else {
			if (!empty($_zp_conf_vars['mysql_database'])) {
				if (isset($_GET['Create_Database'])) {
					$sql = 'CREATE DATABASE IF NOT EXISTS '.'`'.$_zp_conf_vars['mysql_database'].'`'.$collation;
					$result = @mysql_query($sql);
					if ($result && @mysql_select_db($_zp_conf_vars['mysql_database'])) {
						$environ = true;
						require_once(dirname(__FILE__).'/admin-functions.php');
					} else {
						if ($result) {
							$DBcreated = true;
						} else {
							$connectDBErr = mysql_error();
						}
					}
				} else {
					$oktocreate = true;
				}
			}
		}
	} else {
		$connectDBErr = mysql_error();
	}
}

if (!function_exists('setOption')) { // setup a primitive environment
	$environ = false;
	require_once(dirname(__FILE__).'/setup-primitive.php');
	require_once(dirname(__FILE__).'/functions-i18n.php');
}
if ($newconfig || isset($_GET['copyhtaccess'])) {
	if ($newconfig && !file_exists(dirname(dirname(__FILE__)).'/.htaccess') || zp_loggedin(ADMIN_RIGHTS)) {
		copy('htaccess', dirname(dirname(__FILE__)).'/.htaccess');
	}
}

if ($setup_checked) {
	setupLog(gettext("Completed system check"), true);
	if (isset($_COOKIE['setup_test_cookie'])) {
		$setup_cookie = $_COOKIE['setup_test_cookie'];
	} else {
		$setup_cookie = '';
	}
	if ($setup_cookie == ZENPHOTO_RELEASE) {
		setupLog(gettext('Setup cookie test successful'), true);
		setcookie('setup_test_cookie', '', time()-368000, '/');
	} else {
		setupLog(gettext('Setup cookie test unsuccessful'), true); 
	}
} else {
	if (isset($_POST['mysql'])) {
		setupLog(gettext("Post of MySql credentials"), true);
	} else {
		setupLog("Zenphoto Setup v".ZENPHOTO_VERSION.'['.ZENPHOTO_RELEASE.'] '.date('r'), true, true);  // initialize the log file
	}
	if ($environ) {
		setupLog(gettext("Full environment"));
	} else {
		setupLog(gettext("Primitive environment"));
		if ($result) {
			setupLog(sprintf(gettext("Query error: %s"),mysql_error()), true);
		}
	}
	setcookie('setup_test_cookie', ZENPHOTO_RELEASE, time()+3600, '/');
}

if (!isset($_zp_setupCurrentLocale_result) || empty($_zp_setupCurrentLocale_result)) {
	if (DEBUG_LOCALE) debugLog('Setup checking locale');
	$_zp_setupCurrentLocale_result = setMainDomain();
	if (DEBUG_LOCALE) debugLog('$_zp_setupCurrentLocale_result = '.$_zp_setupCurrentLocale_result);
}

$taskDisplay = array('create' => gettext("create"), 'update' => gettext("update"));
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo $upgrade ? gettext("Zenphoto upgrade") : gettext("Zenphoto setup") ; ?></title>
<link rel="stylesheet" href="admin.css" type="text/css" />

<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/zenphoto.js" type="text/javascript" ></script>

<style type="text/css">
body {
	margin: 0px 20% 0px;
	background: none;
	background-color: #f4f4f8;
	font-family: Arial, Helvetica, Verdana, sans-serif;
	font-size: 12px;
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
	font-size: 24px;
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

a:hover, a:active {
	text-decoration: none;
	color: #F60;
	background-color: #FFFCF4;
}

code {
	color: #090;
	font-size: 14px;
}

cite {
	color: #09C;
	font-style: normal;
	font-size: 8px;
}

.bug,a.bug {
	color: #D60 !important;
	font-family: monospace;
}

.pass {
	background: url(images/pass.png) top left no-repeat;
	padding-left: 20px;
	line-height: normal;
}

.fail {
	background: url(images/fail.png) top left no-repeat;
	padding-left: 20px;
	line-height: normal;
}

.warn {
	background: url(images/warn.png) top left no-repeat;
	padding-left: 20px;
	line-height: normal;
}

.createdb {
	background: url(images/add.png) top left no-repeat;
	padding-left: 20px;
	line-height: normal;
}

.updatedb {
	background: url(images/burst.png) top left no-repeat;
	padding-left: 20px;
	line-height: normal;
}


.sqlform {
	line-height: 1;
	text-align: center;
	border-top: 1px solid #FF9595;
	border-bottom: 1px solid #FF9595;
	background-color: #FFEAEA;
	padding: 2px 8px 0px 8px;
	margin-left: 20px;
	color: red;
	font-weight : bold;
}

.sqlform .inputform {
	text-align: left;
	color: black;
	font-weight : normal;
}

.error {
	line-height: 1;
	text-align: center;
	border-top: 1px solid #FF9595;
	border-bottom: 1px solid #FF9595;
	background-color: #FFEAEA;
	padding: 2px 8px 0px 8px;
	color: red;
	font-weight : bold;
}

.warning {
	line-height: 1;
	text-align: center;
	border-top: 1px solid #FF6600;
	border-bottom: 1px solid #FF6600;
	background-color: #FFDDAA;
	padding: 2px 8px 0px 8px;
	color: #FF6600;
	font-weight : bold;
}

.notice {
	line-height: 1;
	text-align: center;
	border-top: 1px solid #009966;
	border-bottom: 1px solid #009966;
	background-color: #B1F7B6;
	padding: 2px 8px 0px 8px;
	color: #009966;
	font-weight : bold;
}

.sqlform p,
.warning p,
.error p,
.notice p {
	text-align: left;
	color: black;
	font-weight : normal;
	line-height: 18px;
}

h4 {
	font-weight: normal;
	font-size: 10pt;
	margin-left: 2em;
	margin-bottom: .15em;
	margin-top: .35em;
}
ul {
	margin-left:0;
	padding-left: 0;
	padding-top:0;
	padding-bottom:0;
}
li {
	list-style-type:none;
	margin-top:0;
	margin-bottom: 5px;
}
.dbwindow {
	padding: 5px;
	margin-right: -5px;
	margin-left: -5px;
	border:1px solid #009966;
}
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
</style>

</head>

<body>

<div id="main">

<h1><img src="images/zen-logo.gif" title="<?php echo gettext('ZenPhoto Setup'); ?>" alt="<?php echo gettext('ZenPhoto Setup'); ?>" align="bottom" />
<?php echo $upgrade ? gettext("Upgrade") : gettext("Setup") ; ?>
</h1>

<div id="content">
<?php
$warn = false;
if (!$setup_checked) {
	?>
	<p>
		<?php
		// Some descriptions about setup/upgrade.
	  if ($upgrade) {
	    echo gettext("Zenphoto has detected that you're upgrading to a new version.");
		} else {
			echo gettext("Welcome to Zenphoto! This page will set up Zenphoto on your web server.");
		}
	?>
	</p>
	<h2><?php echo gettext("Systems Check:"); ?></h2>
	<?php

	/*****************************************************************************
	 *                                                                           *
	 *                             SYSTEMS CHECK                                 *
	 *                                                                           *
	 *****************************************************************************/

	global $_zp_conf_vars;

	function checkMark($check, $text, $text2, $msg) {
		global $warn, $moreid;
		$dsp = '';
		if ($check > 0) {$check = 1; }
		switch ($check) {
			case 0:
				$dsp = "fail";
				break;
			case -1:
				$dsp = "warn";
				$warn = true;
				break;
			case 1:
			case -2:
				$dsp = "pass";
				break;
		}
		if ($check <= 0) {
			?>
			<li class="<?php echo $dsp; ?>">
			<?php
			if (!empty($text2)) {
				echo  $text2;
				$dsp .= ': '.trim($text2);
			} else {
				echo  $text;
				$dsp .= ': '.trim($text);
			}
			if (!empty($msg)) {
				if ($check == 0) {
					echo '<div class="error">';
					echo gettext('Error!');
					echo "<p>".$msg."</p>";
					echo "</div>";
				} else if ($check == -1) {
					$moreid++;
					// should warnings start out hidden?
					//					echo ' <a href="javascript:toggle('. "'more" .$moreid."'".');">'.gettext('<strong>Warning!</strong> cick for details').'</a>';
					echo "<div class='warning' id='more".$moreid."' style='display:block'>";
					echo gettext('Warning!');
					echo "<br />".$msg;
					echo "</div>";
				} else {
					$moreid++;
					echo ' <a href="javascript:toggle('. "'more" .$moreid."'".');">'.gettext('<strong>Notice!</strong> click for details').'</a>';
					echo "<div class='notice' id='more".$moreid."' style='display:none'>";
					echo gettext('Notice!');
					echo "<br />".$msg;
					echo "</div>";
				}
				$dsp .= ' '.trim($msg);
			}
			?>
			</li>
			<?php
		} else {
			?> 
			<li class="<?php echo $dsp; ?>"><?php echo  $text; ?></li>
			<?php
			$dsp .= ': '.trim($text);
		}
		setupLog($dsp, $check>-2 && $check<=0);
		return $check;
	}
	
	function folderCheck($which, $path, $class, $relaxation=true, $subfolders=NULL) {
		global $const_webpath, $serverpath, $chmod, $permission_names;
		$path = str_replace('\\', '/', $path);
		if (!is_dir($path) && $class == 'std') {
			mkdir_recursive($path, $chmod);
		}
		$serverpath = str_replace('\\', '/', dirname(dirname(__FILE__)));
		switch ($class) {
			case 'std':
				$append = str_replace($serverpath, '', $path);
				if (substr($append,-1,1) == '/') $append = substr($append,0, -1);
				if (substr($append,0,1) == '/') $append = substr($append,1);
				if (($append != $which)) {
					$f = " (<em>$append</em>)";
				} else {
					$f = '';
				}
				if (!is_null($subfolders)) {
					$subfolderfailed = '';
					foreach ($subfolders as $subfolder) {
						if (!mkdir_recursive($path.$subfolder, $chmod)) {
							$subfolderfailed .= ', <code>'.$subfolder.'</code>';
						}
					}
					if (!empty($subfolderfailed)) {
						return checkMark(-1, '', sprintf(gettext('<em>%1$s</em> folder%2$s [subfolder creation failure]'),$which, $f), sprintf(gettext('Setup could not create the following subfolders:<br />%s'),substr($subfolderfailed,2)));
					}
				}
				$perms = fileperms($path)&0777;
				if (zp_loggedin(ADMIN_RIGHTS) && (($chmod<$perms) || ($relaxation && $chmod!=$perms))) {
					@chmod($path,$chmod);
					clearstatcache();
					if (($perms = fileperms($path)&0777)!=$chmod) {
						if (array_key_exists($perms, $permission_names)) {
							$perms_class = $permission_names[$perms];
						} else {
							$perms_class = gettext('unknown');
						}
						if (array_key_exists($chmod, $permission_names)) {
							$chmod_class = $permission_names[$chmod];
						} else {
							$chmod_class = gettext('unknown');
						}
						return checkMark(-1, '', sprintf(gettext('<em>%1$s</em> folder%2$s [permissions failure]'),$which, $f), sprintf(gettext('Setup could not change the folder permissions from <em>%1$s</em> (<code>0%2$o</code>) to <em>%3$s</em> (<code>0%4$o</code>). You will have to set the permissions manually. See the <a href="//www.zenphoto.org/2009/03/troubleshooting-zenphoto/#29">Troubleshooting guide</a> for details on Zenphoto permissions requirements.'),$perms_class,$perms,$chmod_class,$chmod));
					} else {
						?>
						<script type="text/javascript">
						// <![CDATA[
							$.ajax({   
								type: 'POST',   
								url: '<?php echo WEBPATH.'/'.ZENFOLDER; ?>/setup_permissions_changer.php',
								data: 'folder=<?php echo $path; ?>&key=<?php echo md5(filemtime(CONFIGFILE).file_get_contents(CONFIGFILE)); ?>'
							});
						// ]]>
						</script>
						<?php
					}
				}
				break;
			case 'in_webpath':
				if (empty($const_webpath)) {
					$serverroot = $serverpath;
				} else {
					$serverroot = substr($serverpath, 0, strpos($serverpath, $const_webpath));
				}
				$append = substr($path, strlen($serverroot));
				$f = " (<em>$append</em>)";
				break;
			case 'external':
				$append = $path;
				$f = " (<em>$append</em>)";
				break;
		}
		if (!is_dir($path)) {
			$msg = " ".sprintf(gettext('You must create the folder <em>%1$s</em><br /><code>mkdir(%2$s, 0777)</code>.'),$append,$path);
			if ($class != 'std') {
				return checkMark(false, '', sprintf(gettext('<em>%1$s</em> folder [<em>%2$s</em> does not exist]'),$which, $append), $msg);
			} else {
				return checkMark(false, '', sprintf(gettext('<em>%1$s</em> folder [<em>%2$s</em> does not exist and <strong>setup</strong> could not create it]'),$which, $append), $msg);
			}
		} else if (!is_writable($path)) {
			$msg =  sprintf(gettext('Change the permissions on the <code>%1$s</code> folder to be writable by the server (<code>chmod 777 %2$s</code>)'),$which,$append);
			return checkMark(false, '', sprintf(gettext('<em>%1$s</em> folder [<em>%2$s</em> is not writeable and <strong>setup</strong> could not make it so]'),$which, $append), $msg);
		} else {
			return checkMark(true, sprintf(gettext('<em>%1$s</em> folder%2$s'),$which, $f), '', '');
		}
	}
	
	
	function versionCheck($required, $desired, $found) {
		$nr = explode(".", $required . '.0.0.0');
		$vr = $nr[0]*10000 + $nr[1]*100 + $nr[2];
		$nf = explode(".", $found . '.0.0.0');
		$vf = $nf[0]*10000 + $nf[1]*100 + $nf[2]; 
		$nd = explode(".", $desired . '.0.0.0');
		$vd = $nd[0]*10000 + $nd[1]*100 + $nd[2];
		if ($vf < $vr) return 0;
		if ($vf < $vd) return -1;
		return 1;
	}

	function setup_glob($pattern, $flags=0) {
		$split=explode('/',$pattern);
		$match=array_pop($split);
		$path_return = $path = implode('/',$split);
		if (empty($path)) {
			$path = '.';
		} else {
			$path_return = $path_return . '/';
		}

		if (($dir=opendir($path))!==false) {
			$glob=array();
			while(($file=readdir($dir))!==false) {
				if (fnmatch($match,$file)) {
					if ((is_dir("$path/$file"))||(!($flags&GLOB_ONLYDIR))) {
						if ($flags&GLOB_MARK) $file.='/';
						$glob[] = $path_return.$file;
					}
				}
			}
			closedir($dir);
			if (!($flags&GLOB_NOSORT)) sort($glob);
			return $glob;
		} else {
			return array();
		}
	}
	if (!function_exists('fnmatch')) {
		/**
		 * pattern match function in case it is not included in PHP
		 *
		 * @param string $pattern pattern
		 * @param string $string haystack
		 * @return bool
		 */
		function fnmatch($pattern, $string) {
			return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|'), array('*' => '.*', '?' => '.?')) . '$/i', $string);
		}
	}

	$good = true;
	
	?>
	<ul>
	<?php
	
	$required = '4.4.8';
	$desired = '5.2';
	$err = versionCheck($required, $desired, PHP_VERSION);
	if ($err < 0) {
		$good = checkMark($err, sprintf(gettext("PHP version %s"), PHP_VERSION), "", sprintf(gettext('Version %1$s or greater is strongly recommended.'),$desired)) && $good;
	} else {
		if ($err == 0) $err = -1; // make it non-fatal
		$good = checkMark($err, sprintf(gettext("PHP version %s"), PHP_VERSION), "", sprintf(gettext('Version %1$s or greater is required. Use earlier versions at your own risk. Version %2$s or greater is strongly recommended.'),$required, $desired)) && $good;
	}
	
	if (ini_get('safe_mode')) {
		$safe = -1;
	} else {
		$safe = true;
	}
	checkMark($safe, gettext("PHP <code>Safe Mode</code>"), gettext("PHP <code>Safe Mode</code> [is set]"), gettext("Zenphoto functionality is reduced when PHP <code>safe mode</code> restrictions are in effect."));

	if (get_magic_quotes_gpc()) {
		$magic_quotes_disabled = -1;
	} else {
		$magic_quotes_disabled = true;
	}
	checkMark($magic_quotes_disabled, gettext("PHP <code>magic_quotes_gpc</code>"), gettext("PHP <code>magic_quotes_gpc</code> [is enabled]"), gettext("You should consider disabling <code>magic_quotes_gpc</code>. For more information See <em>What is magic_quotes_gpc and why should it be disabled?</em> in the Zenphoto troubleshooting guide."));

	if ($environ) {
		/* Check for graphic library and image type support. */
		if (function_exists('zp_graphicsLibInfo')) {
			$graphics_lib = zp_graphicsLibInfo();
			if (array_key_exists('Library',$graphics_lib)) {
				$library = $graphics_lib['Library'];
			} else {
				$library = '';
			}
			$good = checkMark(!empty($library), sprintf(gettext("Graphics support: <code>%s</code>"),$library), gettext('Graphics support [is not installed]'), gettext('You need to install a graphics support library such as the <em>GD library</em> in your PHP')) && $good;
			if (!empty($library)) {
				$missing = array();
				if (!isset($graphics_lib['JPG'])) { $missing[] = 'JPEG'; }
				if (!(isset($graphics_lib['GIF']))) { $missing[] = 'GIF'; }
				if (!(isset($graphics_lib['PNG']))) { $missing[] = 'PNG'; }
				if (count($missing) > 0) {
					if (count($missing) < 3) {
						if (count($missing) == 2) {
							$imgmissing =sprintf(gettext('Your PHP graphics library does not support %1$s or %2$s'),$missing[0],$missing[1]);
						} else {
							$imgmissing = sprintf(gettext('Your PHP graphics library does not support %1$s'),$missing[0]);
						}
						$err = -1;
						$mandate = gettext("To correct this you should install a graphics library with appropriate image support in your PHP");
					} else {
						$imgmissing = sprintf(gettext('Your PHP graphics library does not support %1$s, %2$s, or %3$s'),$missing[0],$missing[1],$missing[2]);
						$err = 0;
						$good = false;
						$mandate = gettext("To correct this you need to a install GD with appropriate image support in your PHP");
					}
					checkMark($err, gettext("PHP graphics image support"), '', $imgmissing.
	                    "<br />".gettext("The unsupported image types will not be viewable in your albums.").
	                    "<br />".$mandate);
				}
				if (!zp_imageCanRotate()) {
					checkMark(-1, '', gettext('Graphics Library rotation support [is not present]'), gettext('The graphics support library does not provide support for image rotation.'));
				}
			}
		} else {
			$graphicsmsg = '';
			foreach ($_zp_graphics_optionhandlers as $handler) {
				$graphicsmsg .= $handler->canLoadMsg($handler); 
			}
			checkmark(0, '', gettext('Graphics support [configuration error]'), gettext('No Zenphoto image handling library was loaded. Be sure that your PHP has a graphics support.').' '.trim($graphicsmsg));
		}
	}
	checkMark($noxlate, gettext('PHP <code>gettext()</code> support'), gettext('PHP <code>gettext()</code> support [is not present]'), gettext("Localization of Zenphoto currently requires native PHP <code>gettext()</code> support"));
	if ($_zp_setupCurrentLocale_result === false) {
		checkMark(-1, gettext('PHP <code>setlocale()</code>'), ' '.gettext('PHP <code>setlocale()</code> failed'), gettext("Locale functionality is not implemented on your platform or the specified locale does not exist. Language translation may not work.").'<br />'.gettext('See the troubleshooting guide on zenphoto.org for details.'));
	}
	if (function_exists('mb_internal_encoding')) {
		if (($charset = mb_internal_encoding()) == 'UTF-8') {
			$mb = 1;
		} else {
			$mb = -1;
		}
		$m2 = gettext('Setting <em>mbstring.internal_encoding</em> to <strong>UTF-8</strong> in your <em>php.ini</em> file is recommended to insure accented and multi-byte characters function properly.');
		checkMark($mb, gettext("PHP <code>mbstring</code> package"), sprintf(gettext('PHP <code>mbstring</code> package [Your internal characater set is <strong>%s</strong>]'), $charset), $m2);
	} else {
		$test = $_zp_UTF8->convert('test', FILESYSTEM_CHARSET, 'UTF-8');
		if (empty($test)) {
			$m2 = gettext("You need to install the <code>mbstring</code> package or correct the issue with <code>iconv(()</code>");
			checkMark(0, '', gettext("PHP <code>mbstring</code> package [is not present and <code>iconv()</code> is not working]"), $m2);
		} else {
			$m2 = gettext("Strings generated internally by PHP may not display correctly. (e.g. dates)");
			checkMark(-1, '', gettext("PHP <code>mbstring</code> package [is not present]"), $m2);
		}
	}

	$sql = extension_loaded('mysql');
	$good = checkMark($sql, gettext("PHP <code>MySQL support</code>"), gettext("PHP <code>MySQL support</code> [is not installed]"), gettext('You need to install MySQL support in your PHP')) && $good;
	if (file_exists(CONFIGFILE)) {
		require(CONFIGFILE);
		$cfg = true;
	} else {
		$cfg = false;
	}
	$good = checkMark($cfg, gettext('<em>zp-config.php</em> file'), gettext('<em>zp-config.php</em> file [does not exist]'),
 							sprintf(gettext('Setup was not able to create this file. You will need to edit the <code>zp-config.php.source</code> file as indicated in the file\'s comments and rename it to <code>zp-config.php</code>.'.
 							' Place the file in the %s folder.'),DATA_FOLDER).
 							sprintf(gettext('<br /><br />You can find the file in the "%s" directory.'),ZENFOLDER)) && $good;
 	if ($cfg) {
  	if (zp_loggedin(ADMIN_RIGHTS)) {
  		$selector =	'<select id="chmod_permissions" name="chmod_permissions" onchange="this.form.submit()">';
  		$c = 0;
  		foreach ($permission_names as $key=>$permission) {
  			$selector .= '	<option value="'.$c.'"'.($chmod==$key?' selected="selected"':'').'>'.sprintf(gettext('%1$s (0%2$o)'),$permission_names[$key],$key).'</option>';
				$c++;
  		}
  		$selector .= '</select>';
  		$chmodselector =	'<form action="#">'.
												sprintf(gettext('Change file/folder permissions mask: %s'),$selector).
												'</form><br />';
 		} else {
 			$chmodselector = '<p>'.gettext('You must be logged in to change permissions.').'</p>';
 		}
		if (array_key_exists($chmod, $permission_names)) {
			$value = sprintf(gettext('<em>%1$s</em> (<code>0%2$o</code>)'),$permission_names[$chmod],$chmod);
		} else {
			$value = sprintf(gettext('<em>unknown</em> (<code>%o</code>)'),$chmod);
		}
		if ($chmod>0755) {
			$severity = -1;
		} else {
			$severity = -2;
		}
 		$msg = sprintf(gettext('File/Folder Permissions [are %s]'),$value);
 		checkMark($severity, $msg, $msg,
	 							'<p>'.gettext('If file and folder permissions are not set to <em>strict</em> or tighter there could be a security risk. However, on some servers Zenphoto does not function correctly with tight file/folder permissions. If Zenphoto has permission errors, run setup again and select a more relaxed permission.').'</p>'.
	 							$chmodselector);
 	}
	if ($sql) {
		if($connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass'])) {
			$db = $_zp_conf_vars['mysql_database'];
			$db = @mysql_select_db($db);
		} else {
			if (empty($connectDBErr)) $connectDBErr = mysql_error();
		}
	}
	if ($connection) {
		$mysqlv = trim(@mysql_get_server_info());
		$i = strpos($mysqlv, "-");
		if ($i !== false) {
			$mysqlv = substr($mysqlv, 0, $i);
		}
		$required = '4.1';
		$desired = '5.0';
		$sqlv = versionCheck($required, $desired, $mysqlv);;
	}
	if ($cfg) {
		@chmod(CONFIGFILE, 0666 & $chmod);
		if (($adminstuff = !$sql || !$connection  || !$db) && is_writable(CONFIGFILE)) {
			$good = checkMark(false, '', gettext("MySQL setup in <em>zp-config.php</em>"), $connectDBErr) && $good;
			// input form for the information
			?>
<li>
<div class="sqlform">
<p>
<?php echo gettext("Fill in the information below and <strong>setup</strong> will attempt to update your <code>zp-config.php</code> file."); ?><br />
</p>
<form action="setup.php" method="post">
<input type="hidden" name="mysql"	value="yes" />
<?php
if ($debug) {
	echo '<input type="hidden" name="debug" />';
}
?>
<table class="inputform">
	<tr>
		<td><?php echo gettext("MySQL admin user") ?></td>
		<td>
			<input type="text" size="40" name="mysql_user" value="<?php echo $_zp_conf_vars['mysql_user']?>" />&nbsp;*
		</td>
	</tr>
	<tr>
		<td><?php echo gettext("MySQL admin password") ?></td>
		<td>
			<input type="password" size="40" name="mysql_pass" value="<?php echo $_zp_conf_vars['mysql_pass']?>" />&nbsp;*
		</td>
	</tr>
	<tr>
		<td><?php echo gettext("MySQL host") ?></td>
		<td>
			<input type="text" size="40" name="mysql_host" value="<?php echo $_zp_conf_vars['mysql_host']?>" />
		</td>
	</tr>
	<tr>
		<td><?php echo gettext("MySQL database") ?></td>
		<td>
			<input type="text" size="40" name="mysql_database" value="<?php echo $_zp_conf_vars['mysql_database']?>" />&nbsp;*
		</td>
	</tr>
	<tr>
		<td><?php echo gettext("Database table prefix") ?></td>
		<td>
			<input type="text" size="40" name="mysql_prefix" value="<?php echo $_zp_conf_vars['mysql_prefix']?>" />
		</td>
	</tr>
	<tr>
		<td></td>
		<td align="right">* <?php echo gettext("required") ?></td>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td>
			<input type="submit" value="<?php echo gettext('save'); ?>" />
		</td>
	</tr>
</table>
</form>
</div>
</li>
<?php
		} else {
			if ($connectDBErr) {
				$msg = $connectDBErr;
			} else {
				$msg = gettext("You have not correctly set your <strong>MySQL</strong> <code>user</code>, <code>password</code>, etc. in your <code>zp-config.php</code> file and <strong>setup</strong> is not able to write to the file.");
			}
			$good = checkMark(!$adminstuff, gettext("MySQL setup in <em>zp-config.php</em>"), '',$msg) && $good;
		}
	} else {
		$good = checkMark($connection, gettext("Connect to MySQL"), gettext("Connect to MySQL [<code>CONNECT</code> query failed]"), $connectDBErr) && $good;
	}
	if (!$newconfig && !$connection) {
		if (empty($_zp_conf_vars['mysql_host']) || empty($_zp_conf_vars['mysql_user']) || empty($_zp_conf_vars['mysql_pass'])) {
			$connectDBErr = gettext('Check the <code>user</code>, <code>password</code>, and <code>database host</code> and try again.'); // of course we can't connect!
		}
	}
	if ($connection) {
		$good = checkMark($sqlv, sprintf(gettext("MySQL version %s"),$mysqlv), "", sprintf(gettext('Version %1$s or greater is required. Use a lower version at your own risk.<br />Version %2$s or greater is prefered.'),$required,$desired)) && $good;
		if ($DBcreated || !empty($connectDBErr)) {
			if (empty($connectDBErr)) {
				$severity = 1;
			} else {
				$severity = 0;
			}
			checkMark($severity, sprintf(gettext('Database <code>%s</code> created'), $_zp_conf_vars['mysql_database']),sprintf(gettext('Database <code>%s</code> not created [<code>CREATE DATABASE</code> query failed]'), $_zp_conf_vars['mysql_database']),$connectDBErr);
		}
		if (empty($_zp_conf_vars['mysql_database'])) {
			$good = checkmark(0, '', gettext('Connect to the database [You have not provided a database name]'),gettext('Provide the name of your database in the form above.'));
		} else {
			if ($oktocreate) {
				$good = checkmark(0, '', gettext('Connect to the database [Database does not exist]'),sprintf(gettext('Click here to attempt to create <a href="?Create_Database" >%s</a>.'),$_zp_conf_vars['mysql_database']));
			}
		}
		if ($environ) {
			if ($sqlv == 0) $sqlv = -1; // make it non-fatal
			$good = checkMark($db, gettext("Connect to the database"), '',
								sprintf(gettext("Could not access the <strong>MySQL</strong> database (<code>%s</code>)."), $_zp_conf_vars['mysql_database']).' '.
								gettext("Check the <code>user</code>, <code>password</code>, <code>database name</code>, and <code>MySQL host</code>.").'<br />' .
								sprintf(gettext("Make sure the database has been created and that <code>%s</code> has access to it."),$_zp_conf_vars['mysql_user'])) && $good;

			$result = @mysql_query('SELECT @@SESSION.sql_mode;', $mysql_connection);
			if ($result) {
				$row = @mysql_fetch_row($result);
				$oldmode = $row[0];
			}
			$result = @mysql_query('SET SESSION sql_mode="";', $mysql_connection);
			$msg = gettext('You may need to set <code>SQL mode</code> <em>empty</em> in your MySQL configuration.');
			if ($result) {
				$result = @mysql_query('SELECT @@SESSION.sql_mode;', $mysql_connection);
				if ($result) {
					$row = @mysql_fetch_row($result);
					$mode = $row[0];
					if ($oldmode != $mode) {
						checkMark(-1, sprintf(gettext('MySQL <code>SQL mode</code> [<em>%s</em> overridden]'), $oldmode), '', gettext('Consider setting it <em>empty</em> in your MySQL configuration.'));
					} else {
						if (!empty($mode)) {
							$err = -1;
						} else {
							$err = 1;
						}
						checkMark($err, gettext('MySQL <code>SQL mode</code>'), sprintf(gettext('MySQL <code>SQL mode</code> [is set to <em>%s</em>]'),$mode), gettext('Consider setting it <em>empty</em> if you get MySql errors.'));
					}
				} else {
					checkMark(-1, '', sprintf(gettext('MySQL <code>SQL mode</code> [query failed]'), $oldmode), $msg);
				}
			} else {
				checkMark(-1, '', gettext('MySQL <code>SQL mode</code> [SET SESSION failed]'), $msg);
			}
			$dbn = "`".$_zp_conf_vars['mysql_database']. "`.*";
			if (versioncheck('4.2.1', '4.2.1', $mysqlv)) {
				$sql = "SHOW GRANTS FOR CURRENT_USER;";
			} else {
				$sql = "SHOW GRANTS FOR " . $_zp_conf_vars['mysql_user'].";";
			}
			$result = @mysql_query($sql, $mysql_connection);
			if (!$result) {
				$result = @mysql_query("SHOW GRANTS;", $mysql_connection);
			}
			$MySQL_results = array();
			while ($onerow = @mysql_fetch_row($result)) {
				$MySQL_results[] = $onerow[0];
			}
			$access = -1;
			$rightsfound = 'unknown';
			$rightsneeded = array(gettext('Select')=>'SELECT',gettext('Create')=>'CREATE',gettext('Drop')=>'DROP',gettext('Insert')=>'INSERT',
			gettext('Update')=>'UPDATE',gettext('Alter')=>'ALTER',gettext('Delete')=>'DELETE',gettext('Index')=>'INDEX');
			ksort($rightsneeded);
			$neededlist = '';
			foreach ($rightsneeded as $right=>$value) {
				$neededlist .= '<code>'.$right.'</code>, ';
			}
			$neededlist = substr($neededlist, 0, -2).' ';
			$i = strrpos($neededlist, ',');
			$neededlist = substr($neededlist, 0, $i).' '.gettext('and').substr($neededlist, $i+1);
			if ($result) {
				$report = "<br /><br /><em>".gettext("Grants found:")."</em> ";
				foreach ($MySQL_results as $row) {
					$row_report = "<br /><br />".$row;
					$r = str_replace(',', '', $row);
					$i = strpos($r, "ON");
					$j = strpos($r, "TO", $i);
					$found = stripslashes(trim(substr($r, $i+2, $j-$i-2)));
					if ($partial = (($i = strpos($found, '%')) !== false)) {
						$found = substr($found, 0, $i);
					}
					$rights = array_flip(explode(' ', $r));
					$rightsfound = 'insufficient';
					if (($found == $dbn) || ($found == "*.*") || $partial && preg_match('/^'.$found.'/', $dbn)) {
						$allow = true;
						foreach ($rightsneeded as $key=>$right) {
							if (!isset($rights[$right])) {
								$allow = false;
							}
						}
						if (isset($rights['ALL']) || $allow) {
							$access = 1;
						}
						$report .= '<strong>'.$row_report.'</strong>';
					} else {
						$report .= $row_report;
					}
				}
			} else {
				$report = "<br /><br />".gettext("The <em>SHOW GRANTS</em> query failed.");
			}
			checkMark($access, gettext("MySQL <code>access rights</code>"), sprintf(gettext("MySQL <code>access rights</code> [%s]"),$rightsfound),
			sprintf(gettext("Your MySQL user must have %s rights."),$neededlist) . $report);


			$sql = "SHOW TABLES FROM `".$_zp_conf_vars['mysql_database']."` LIKE '".$_zp_conf_vars['mysql_prefix']."%';";
			$result = @mysql_query($sql, $mysql_connection);
			$tableslist = '';
			if ($result) {
				while ($row = @mysql_fetch_row($result)) {
					$tableslist .= "<code>" . $row[0] . "</code>, ";
				}
			}
			if (empty($tableslist)) {
				$msg = gettext('MySQL <em>show tables</em> [found no tables]');
				$msg2 = '';
			} else {
				$msg = sprintf(gettext("MySQL <em>show tables</em> found: %s"),substr($tableslist, 0, -2));
				$msg2 = '';
			}
			if (!$result) { $result = -1; }
			$dbn = $_zp_conf_vars['mysql_database'];
			checkMark($result, $msg, gettext("MySQL <em>show tables</em> [Failed]"), sprintf(gettext("MySQL did not return a list of the database tables for <code>%s</code>."),$dbn) .
 											"<br />".gettext("<strong>Setup</strong> will attempt to create all tables. This will not over write any existing tables."));

			if (isset($_zp_conf_vars['UTF-8']) && $_zp_conf_vars['UTF-8']) {
				$fields = 0;
				$fieldlist = array();
				if (strpos($tableslist,$_zp_conf_vars['mysql_prefix'].'images') !== false) {
					$sql = 'SHOW FULL COLUMNS FROM `'.$_zp_conf_vars['mysql_prefix'].'images`';
					$result1 = @mysql_query($sql, $mysql_connection);
					if ($result1) {
						while ($row = @mysql_fetch_row($result1)) {
							if (!is_null($row[2]) && $row[2] != 'utf8_unicode_ci') {
								$fields = $fields | 1;
								$fieldlist[] = '<code>images->'.$row[0].'</code>';
							}
						}
					} else {
						$fields = 4;
					}
				}
				if (strpos($tableslist,$_zp_conf_vars['mysql_prefix'].'albums') !== false) {
					$sql = 'SHOW FULL COLUMNS FROM `'.$_zp_conf_vars['mysql_prefix'].'albums`';
					$result2 = @mysql_query($sql, $mysql_connection);
					if ($result2) {
						while ($row = @mysql_fetch_row($result2)) {
							if (!is_null($row[2]) && $row[2] != 'utf8_unicode_ci') {
								$fields = $fields | 2;
								$fieldlist[] = '<code>albums->'.$row[0].'</code>';
							}
						}
					} else {
						$fields = 4;
					}
				}
				$err = -1;
				switch ($fields) {
					case 0: // all is well
						$msg2 = '';
						$err = 1;
						break;
					case 1:
						$msg2 = gettext('MySQL <code>field collations</code> [Image table]');
						break;
					case 2:
						$msg2 = gettext('MySQL <code>field collations</code> [Album table]');
						break;
					case 3:
						$msg2 = gettext('MySQL <code>field collations</code> [Image and Album tables]');
						break;
					default:
						$msg2 = gettext('MySQL <code>field collations</code> [SHOW COLUMNS query failed]');
						break;
				}
				checkmark($err, gettext('MySQL <code>field collations</code>'), $msg2, sprintf(ngettext('%s is not UTF-8. You should consider porting your data to UTF-8 and changing the collation of the database fields to <code>utf8_unicode_ci</code>','%s are not UTF-8. You should consider porting your data to UTF-8 and changing the collation of the database fields to <code>utf8_unicode_ci</code>',count($fieldlist)),implode(', ',$fieldlist)));
			} else {
				checkmark(-1, '', gettext('MySQL <code>$conf["UTF-8"]</code> [is not set <em>true</em>]'), gettext('You should consider porting your data to UTF-8 and changing the collation of the database fields fields to <code>utf8_unicode_ci</code> and setting this <em>true</em>. Zenphoto works best with pure UTF-8 encodings.'));
			}
		}
	}

	$permissions = 1;
	$cum_mean = filemtime(SERVERPATH.'/'.ZENFOLDER.'/version.php');
	$hours = 3600;
	$lowset = $cum_mean - $hours;
	$highset = $cum_mean + $hours;

	$package = file_get_contents(SERVERPATH.'/'.ZENFOLDER.'/Zenphoto.package');
	$installed_files = explode("\n", trim($package));
	$base = dirname(dirname(__FILE__)).'/';
	$folders = array();
	$zenphoto_themes = array();
	foreach ($installed_files as $key=>$value) {
		$component_data = explode(':',$value);
		$value = trim($component_data[0]);
		$component = $base.$value;
		if (file_exists($component)) {
			if (is_dir($component)) {
				$folders[$component] = $component;
				unset($installed_files[$key]);
				if (dirname($value) == THEMEFOLDER) {
					$zenphoto_themes[] = basename($value);
				}
			} else {
				if (zp_loggedin(ADMIN_RIGHTS)) {
					@chmod($component,0666&$chmod);
					clearstatcache();
					if ($permissions==1 && ($perms = fileperms($component)&0777)!=($chmod & 0666)) {
						if (($perms&0644) == 0644) { // could not set them, but they will work.
							$permissions = -1;
						} else {
							$permissions = 0;
						}
					}
				}
				$t = filemtime($component);

				if ((defined('RELEASE') && ($t < $lowset || $t > $highset))) {
					$installed_files[$key] = $value;
				} else {
					unset($installed_files[$key]);
				}
			}
		}
	}
	if (count($folders)>0) {
		foreach ($folders as $key=>$folder) {
			if ((fileperms($folder)&0777) != 0755) { // need to set them?.
				@chmod($folder, $chmod);
				clearstatcache();
				if ($permissions==1 && ($perms = fileperms($folder)&0777)!=$chmod) {
					if (($perms&0755) != 0755) { // could not set them, but they will work.
						$permissions = 0;
					} else {
						$permissions = -1;
					}
				}
			}
		}
	}
	$plugin_subfolders = array();
	$Cache_html_subfolders = array();
	foreach ($installed_files as $key=>$component) {
		$folders = explode('/',$component);
		$folder = array_shift($folders);
		switch ($folder) {
			case 'albums':
			case 'cache':
			case 'zp-data':
			case 'uploaded':
				unset($installed_files[$key]);
				break;
			case 'plugins':
				$plugin_subfolders[] = implode('/',$folders);
				unset($installed_files[$key]); // this will be taken care of later
				break;
			case 'cache_html':
				$Cache_html_subfolders[] = implode('/',$folders);
				unset($installed_files[$key]);
				break;
		}
	}
	$filelist = implode("<br />", $installed_files);
	if (count($installed_files) > 0) {
		if (!defined("RELEASE")) {
			$msg1 = gettext("Zenphoto core files [This is not an official build. Some files are missing or seem out of variance]");
		} else {
			$msg1 = gettext("Zenphoto core files [Some files are missing or seem out of variance]");
		}
		$msg2 = gettext('Perhaps there was a problem with the upload. You should check the following files: ').'<br /><code>'.$filelist.'</code>';
		$mark = -1;
	} else {
		if (!defined("RELEASE")) {
			$mark = -1;
			$msg1 = gettext("Zenphoto core files [This is not an official build]");
		} else {
			$msg1 = '';
			$mark = 1;
		}
		$msg2 = '';
	}
	
	checkMark($mark, gettext("Zenphoto core files"), $msg1, $msg2);
	if (zp_loggedin(ADMIN_RIGHTS)) checkMark($permissions, gettext("Zenphoto core file permissions"), gettext("Zenphoto core file permissions [not correct]"), gettext('Setup could not set the one or more components to the selected permissions level. You will have to set the permissions manually. See the <a href="//www.zenphoto.org/2009/03/troubleshooting-zenphoto/#29">Troubleshooting guide</a> for details on Zenphoto permissions requirements.'));
	$msg = gettext("<em>.htaccess</em> file");
	if (!stristr($_SERVER['SERVER_SOFTWARE'], "apache") && !stristr($_SERVER['SERVER_SOFTWARE'], "litespeed")) {
		checkMark(-1, gettext("Server seems not to be Apache or Apache-compatible, <code>.htaccess</code> not required."), "", "");
		$Apache = false;
	}	else {
		$Apache = true;
	}
	$htfile = '../.htaccess';
	$ht = @file_get_contents($htfile);
	$htu = strtoupper($ht);
	$vr = "";
	$ch = 1;
	$j = 0;
	$err = '';
	$desc = '';
	if (empty($htu)) {
		if ($Apache) {
			$ch = -1;
			$err = gettext("<em>.htaccess</em> file [is empty or does not exist]");
			$desc = gettext('If you have the mod_rewrite module enabled an <em>.htaccess</em> file is required the root zenphoto folder to create cruft-free URLs.').
						'<br /><br />'.gettext('You can ignore this warning if you do not intend to set the <code>mod_rewrite</code> option.');
			if (zp_loggedin(ADMIN_RIGHTS)) $desc .= ' '.gettext('Click <a href="?copyhtaccess" >here</a> to have setup create the file.');
		} else {
			$ch = -2;
		}
	} else {
		$i = strpos($htu, 'VERSION');
		if ($i !== false) {
			$j = strpos($htu, ";");
			$vr = trim(substr($htu, $i+7, $j-$i-7));
		}
		$ch = !empty($vr) && ($vr == HTACCESS_VERSION);
		if (!$ch) {	// wrong version
			$oht = @file_get_contents('oldhtaccess');
			if ($oht == $ht) {	// an unmodified .htaccess file, we can just replace it
				@unlink($htfile);
				$ch = @copy('htaccess', dirname(dirname(__FILE__)).'/.htaccess');
			}
		}
		if (!$ch) {
			if (!$Apache) $ch = -1;
			$err = gettext("<em>.htaccess</em> file [wrong version]");
			$desc = sprintf(gettext("The <em>.htaccess</em> file in your root folder is not the same version as the one distributed with this version of Zenphoto. If you have made changes to <em>.htaccess</em>, merge those changs with the <em>%s/htaccess</em> file to produce a new <em>.htaccess</em> file."),ZENFOLDER);
			if (zp_loggedin(ADMIN_RIGHTS)) {
				$desc .= ' '.gettext('Click <a href="?copyhtaccess" >here</a> to have setup replace your <em>.htaccess</em> file with the current version.');
			}
		}
	}

	$mod = '';
	$rw = '';
	if ($ch) {
		$i = strpos($htu, 'REWRITEENGINE');
		if ($i === false) {
			$rw = '';
		} else {
			$j = strpos($htu, "\n", $i+13);
			$rw = trim(substr($htu, $i+13, $j-$i-13));
		}
		if (!empty($rw)) {
			$msg = sprintf(gettext("<em>.htaccess</em> file (<em>RewriteEngine</em> is <strong>%s</strong>)"), $rw);
			$mod = "&amp;mod_rewrite=$rw";
		}
	}
	if ($Apache || $ch != -2) {
		$good = checkMark($ch, $msg, $err, $desc) && $good;			
	}
	$base = true;
	$f = '';
	if ($rw == 'ON') {
		$d = str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME'])));
		$i = strpos($htu, 'REWRITEBASE', $j);
		if ($i === false) {
			$base = false;
			$b = '';
			$err = gettext("<em>.htaccess</em> RewriteBase [is <em>missing</em>]");
			$i = $j+1;
		} else {
			$j = strpos($htu, "\n", $i+11);
			$bs = trim(substr($ht, $i+11, $j-$i-11));
			$base = ($bs == $d);
			$b = sprintf(gettext("<em>.htaccess</em> RewriteBase is <code>%s</code>"), $bs);
			$err = sprintf(gettext("<em>.htaccess</em> RewriteBase is <code>%s</code> [Does not match install folder]"), $bs);
		}
		$f = '';
		if (!$base) { // try and fix it
			@chmod($htfile, 0666&$chmod);
			if (is_writeable($htfile)) {
				$ht = substr($ht, 0, $i) . "RewriteBase $d\n" . substr($ht, $j+1);
				if ($handle = fopen($htfile, 'w')) {
					if (fwrite($handle, $ht)) {
						$base = true;
						$b =  sprintf(gettext("<em>.htaccess</em> RewriteBase is <code>%s</code> (fixed)"), $d);
						$err =  '';
					}
					fclose($handle);
				}
			}
		}
		$good = checkMark($base, $b, $err,
											gettext("Setup was not able to write to the file change RewriteBase match the install folder.") .
											"<br />".sprintf(gettext("Either make the file writeable or set <code>RewriteBase</code> in your <code>.htaccess</code> file to <code>%s</code>."),$d)) && $good;
	}
	//robots.txt file
	$robots = file_get_contents(dirname(__FILE__).'/example_robots.txt');
	if ($robots === false) {
		checkmark(-1, gettext('<em>robots.txt</em> file'), gettext('<em>robots.txt</em> file [Not created]'), gettext('Setup could not find the  <em>example_robots.txt</em> file.'));
	} else {
		if (file_exists(dirname(dirname(__FILE__)).'/robots.txt')) {
			checkmark(-2, gettext('<em>robots.txt</em> file'), gettext('<em>robots.txt</em> file [Not created]'), '<p>'.gettext('Setup did not create a <em>robots.txt</em> file because one already exists.').'</p>');
		} else {
			$text = explode('****delete all lines above and including this one *******'."\n", $robots);
			$d = dirname(dirname($_SERVER['SCRIPT_NAME']));
			if ($d == '/') $d = '';
			$robots = str_replace('/zenphoto', $d, $text[1]);
			$rslt = file_put_contents(dirname(dirname(__FILE__)).'/robots.txt', $robots);
			if ($rslt === false) {
				$rslt = -1;
			} else {
				$rslt = 1;
			}
			checkmark($rslt, gettext('<em>robots.txt</em> file'), gettext('<em>robots.txt</em> file [Not created]'), gettext('Setup could not create a <em>robots.txt</em> file.'));
		}
	}

	if (isset($_zp_conf_vars['external_album_folder']) && !is_null($_zp_conf_vars['external_album_folder'])) {
		checkmark(-1, 'albums', gettext("albums [<code>\$conf['external_album_folder']</code> is deprecated]"), gettext('You should update your zp-config.php file to conform to the current zp-config.php.example file.'));
		$_zp_conf_vars['album_folder_class'] = 'external';
		$albumfolder = $_zp_conf_vars['external_album_folder'];
	}
	if (!isset($_zp_conf_vars['album_folder_class'])) {
		$_zp_conf_vars['album_folder_class'] = 'std';
	}
	if (isset($_zp_conf_vars['album_folder'])) {
		$albumfolder = str_replace('\\', '/', $_zp_conf_vars['album_folder']);
		switch ($_zp_conf_vars['album_folder_class']) {
			case 'std':
				$albumfolder = str_replace('\\', '/', dirname(dirname(__FILE__))) . $albumfolder;
				break;
			case 'in_webpath':
				$root = str_replace('\\', '/', dirname(dirname(__FILE__)));
				if (!empty($const_webpath)) {
					$root = str_replace('\\', '/', dirname($root));
				}
				$albumfolder = $root . $albumfolder;
				break;
		}
		$good = folderCheck('albums', $albumfolder, $_zp_conf_vars['album_folder_class']) && $good;
	} else {
		checkmark(-1, gettext('<em>albums</em> folder'), gettext("<em>albums</em> folder [The line <code>\$conf['album_folder']</code> is missing from your zp-config.php file]"), gettext('You should update your zp-config.php file to conform to the current zp-config.php.example file.'));
	}

	$good = folderCheck('cache', dirname(dirname(__FILE__)) . "/cache/", 'std') && $good;
	$good = checkmark(file_exists($en_US), gettext('<em>locale</em> folders'), gettext('<em>locale</em> folders [Are not complete]'), gettext('Be sure you have uploaded the complete Zenphoto package. You must have at least the <em>en_US</em> folder.')) && $good;
	$good = folderCheck(gettext('uploaded'), dirname(dirname(__FILE__)) . "/uploaded/", 'std') && $good;
	$good = folderCheck(DATA_FOLDER, dirname(dirname(__FILE__)) . '/'.DATA_FOLDER.'/', 'std') && $good;
	$good = folderCheck(gettext('HTML cache'), dirname(dirname(__FILE__)) . '/cache_html/', 'std', true, $Cache_html_subfolders) && $good;
	$good = folderCheck(gettext('Third party plugins'), dirname(dirname(__FILE__)) . '/'.USER_PLUGIN_FOLDER.'/', 'std', false, $plugin_subfolders) && $good;
	
	?>
	</ul>
	<?php
	
	if ($connection) { @mysql_close($connection); }
	if ($good) {
		$dbmsg = "";
	} else {
		if (zp_loggedin(ADMIN_RIGHTS)) {
			echo "<p>".gettext("You need to address the problems indicated above then run <code>setup.php</code> again.")."</p>";
		} else {
			if (zp_loggedin()) {
				echo "<p>".gettext("You need <em>USER ADMIN</em> rights to run setup.").'</p>';
				printLoginForm('', false);
			}
		}
		if ($noxlate > 0) {
			require_once(dirname(__FILE__).'/'.PLUGIN_FOLDER.'/dynamic-locale.php');
			printLanguageSelector();
		}
		echo "\n</div><!-- content -->";
		echo "\n</div><!-- main -->";
		printadminfooter();
		echo "</body>";
		echo "</html>";
		exit();
	}
} else {
	$dbmsg = gettext("database connected");
} // system check
if (file_exists(CONFIGFILE)) {

	require(CONFIGFILE);
	require_once(dirname(__FILE__).'/functions.php');
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
			$_zp_conf_vars['mysql_prefix'].'tags', $_zp_conf_vars['mysql_prefix'].'obj_to_tag',
			$_zp_conf_vars['mysql_prefix'].'captcha');
		
		$expected_tables = array_merge($expected_tables, array($_zp_conf_vars['mysql_prefix'].'zenpage_pages',
			$_zp_conf_vars['mysql_prefix'].'zenpage_news2cat', $_zp_conf_vars['mysql_prefix'].'zenpage_news_categories',
			$_zp_conf_vars['mysql_prefix'].'zenpage_news'));
		
		foreach ($expected_tables as $needed) {
			if (!isset($tables[$needed])) {
				$tables[$needed] = 'create';
			}
		}

		if (!($tables[$_zp_conf_vars['mysql_prefix'].'administrators'] == 'create')) {
			if (!zp_loggedin(ADMIN_RIGHTS) && (!isset($_GET['create']) && !isset($_GET['update']))) {  // Display the login form and exit.
				if (zp_loggedin()) { echo "<p>".gettext("You need <em>USER ADMIN</em> rights to run setup.").'</p>'; }
				printLoginForm('', false);
				if ($noxlate > 0) {
					require_once(dirname(__FILE__).'/'.PLUGIN_FOLDER.'/dynamic-locale.php');
					printLanguageSelector();
				}
				echo "\n</div><!-- content -->";
				echo "\n</div><!-- main -->";
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
	$tbl_captcha = prefix('captcha');
	$tbl_zenpage_news = prefix('zenpage_news');
	$tbl_zenpage_pages = prefix('zenpage_pages');
	$tbl_zenpage_news_categories = prefix('zenpage_news_categories');
	$tbl_zenpage_news2cat = prefix('zenpage_news2cat');
	// Prefix the constraint names:
	$cst_images = prefix('images_ibfk1');

	$db_schema = array();
	$sql_statements = array();
	
	/***********************************************************************************
	 Add new fields in the upgrade section. This section should remain static except for new
	 tables. This tactic keeps all changes in one place so that noting gets accidentaly omitted.
	************************************************************************************/

	//v1.2
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'captcha'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_captcha (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`ptime` int(32) UNSIGNED NOT NULL,
		`hash` varchar(255) NOT NULL,
		PRIMARY KEY  (`id`)
		)	$collation;";
	}
	//v1.1.7
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'options'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_options (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`ownerid` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`name` varchar(255) NOT NULL,
		`value` text,
		PRIMARY KEY  (`id`),
		UNIQUE (`name`, `ownerid`)
		)	$collation;";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'tags'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_tags (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`name`)
		)	$collation;";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'obj_to_tag'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_obj_to_tag (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`tagid` int(11) UNSIGNED NOT NULL,
		`type` tinytext,
		`objectid` int(11) UNSIGNED NOT NULL,
		PRIMARY KEY  (`id`)
		)	$collation;";
	}

	// v. 1.1.5
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'administrators'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_administrators (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`user` varchar(64) NOT NULL,
		`pass` text,
		`name` text,
		`email` text,
		`rights` int,
		PRIMARY KEY  (`id`),
		UNIQUE (`user`)
		)	$collation;";
	}
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'admintoalbum'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_admintoalbum (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`adminid` int(11) UNSIGNED NOT NULL,
		`albumid` int(11) UNSIGNED NOT NULL,
		PRIMARY KEY  (`id`)
		)	$collation;";
	}

	// base implementation
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'albums'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_albums (
		`id` int(11) UNSIGNED NOT NULL auto_increment,
		`parentid` int(11) unsigned default NULL,
		`folder` varchar(255) NOT NULL default '',
		`title` text,
		`desc` text,
		`date` datetime default NULL,
		`location` text,
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
		)	$collation;";
	}

	if (isset($create[$_zp_conf_vars['mysql_prefix'].'comments'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_comments (
		`id` int(11) unsigned NOT NULL auto_increment,
		`ownerid` int(11) unsigned NOT NULL default '0',
		`name` varchar(255) NOT NULL default '',
		`email` varchar(255) NOT NULL default '',
		`website` varchar(255) default NULL,
		`date` datetime default NULL,
		`comment` text,
		`inmoderation` int(1) unsigned NOT NULL default '0',
		PRIMARY KEY  (`id`),
		KEY `ownerid` (`ownerid`)
		)	$collation;";
	}

	if (isset($create[$_zp_conf_vars['mysql_prefix'].'images'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS $tbl_images (
		`id` int(11) unsigned NOT NULL auto_increment,
		`albumid` int(11) unsigned NOT NULL default '0',
		`filename` varchar(255) NOT NULL default '',
		`title` text,
		`desc` text,
		`location` text,
		`city` tinytext,
		`state` tinytext,
		`country` tinytext,
		`credit` text,
		`copyright` text,
		`commentson` int(1) UNSIGNED NOT NULL default '1',
		`show` int(1) NOT NULL default '1',
		`date` datetime default NULL,
		`sort_order` int(11) unsigned default NULL,
		`height` int(10) unsigned default NULL,
		`width` int(10) unsigned default NULL,
		`thumbX` int(10) unsigned default NULL,
		`thumbY` int(10) unsigned default NULL,
		`thumbW` int(10) unsigned default NULL,
		`thumbH` int(10) unsigned default NULL,
		`mtime` int(32) default NULL,
		`hitcounter` int(11) unsigned default 0,
		`total_value` int(11) unsigned default '0',
		`total_votes` int(11) unsigned default '0',
		`used_ips` longtext,
		PRIMARY KEY  (`id`),
		KEY `filename` (`filename`,`albumid`)
		)	$collation;";
		$db_schema[] = "ALTER TABLE $tbl_images ".
			"ADD CONSTRAINT $cst_images FOREIGN KEY (`albumid`) REFERENCES $tbl_albums (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
	}
	
	//v1.2.4
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'zenpage_news'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news')." (
		`id` int(11) unsigned NOT NULL auto_increment,
		`title` text,
		`content` text,
		`extracontent` text,
		`show` int(1) unsigned NOT NULL default '1',
		`date` datetime,
		`titlelink` varchar(255) NOT NULL,
		`commentson` int(1) UNSIGNED NOT NULL,
		`codeblock` text,
		`author` varchar(64) NOT NULL,
		`lastchange` datetime default NULL,
		`lastchangeauthor` varchar(64) NOT NULL,
		`hitcounter` int(11) unsigned default 0,
		`permalink` int(1) unsigned NOT NULL default 0,
		`locked` int(1) unsigned NOT NULL default 0,
		`expiredate` datetime default NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`titlelink`)
		) $collation;";
	}

	if (isset($create[$_zp_conf_vars['mysql_prefix'].'zenpage_news_categories'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news_categories')." (
		`id` int(11) unsigned NOT NULL auto_increment,
		`cat_name` text,
		`cat_link` varchar(255) NOT NULL default '',
		`permalink` int(1) UNSIGNED NOT NULL default 0,
		`hitcounter` int(11) unsigned default 0,
		PRIMARY KEY  (`id`),
		UNIQUE (`cat_link`)
		) $collation;";
	}

	if (isset($create[$_zp_conf_vars['mysql_prefix'].'zenpage_news2cat'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_news2cat')." (
		`id` int(11) unsigned NOT NULL auto_increment,
		`cat_id` int(11) unsigned NOT NULL,
		`news_id` int(11) unsigned NOT NULL,
		PRIMARY KEY  (`id`)
		) $collation;";
	}
	
	if (isset($create[$_zp_conf_vars['mysql_prefix'].'zenpage_pages'])) {
		$db_schema[] = "CREATE TABLE IF NOT EXISTS ".prefix('zenpage_pages')." (
		`id` int(11) unsigned NOT NULL auto_increment,
		`parentid` int(11) unsigned default NULL,
		`title` text,
		`content` text,
		`extracontent` text,
		`sort_order`varchar(48) NOT NULL default '',
		`show` int(1) unsigned NOT NULL default '1',
		`titlelink` varchar(255) NOT NULL,
		`commentson` int(1) unsigned NOT NULL,
		`codeblock` text,
		`author` varchar(64) NOT NULL,
		`date` datetime default NULL,
		`lastchange` datetime default NULL,
		`lastchangeauthor` varchar(64) NOT NULL,
		`hitcounter` int(11) unsigned default 0,
		`permalink` int(1) unsigned NOT NULL default 0,
		`locked` int(1) unsigned NOT NULL default 0,
		`expiredate` datetime default NULL,
		PRIMARY KEY  (`id`),
		UNIQUE (`titlelink`)
		) $collation;";
	}

	/****************************************************************************************
	 ******                             UPGRADE SECTION                                ******
	 ******                                                                            ******
	 ******                          Add all new fields below                          ******
	 ******                                                                            ******
	 ****************************************************************************************/

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
//v1.2.7 omits	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `EXIFValid` int(1) UNSIGNED default NULL;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `hitcounter` int(11) UNSIGNED default 0;";

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
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `custom_data` text";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `custom_data` text";
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `password` `password` varchar(255) NOT NULL DEFAULT ''";

	//v1.1.5
	$sql_statements[] = " ALTER TABLE $tbl_comments DROP FOREIGN KEY `comments_ibfk1`";
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
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `search_params` text";

	//v1.1.6
	$sql_statements[] = "ALTER TABLE $tbl_albums ADD COLUMN `album_theme` text";
	$sql_statements[] = "ALTER TABLE $tbl_comments ADD COLUMN `IP` text";

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

	//v1.2
	$sql_statements[] = "ALTER TABLE $tbl_options CHANGE `ownerid` `ownerid` int(11) UNSIGNED NOT NULL DEFAULT 0";
	$sql_statements[] = "ALTER TABLE $tbl_admintoalbum CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$sql_statements[] = "ALTER TABLE $tbl_obj_to_tag CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$sql_statements[] = "ALTER TABLE $tbl_options CHANGE `name` `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$hastagidindex = false;
	$sql = "SHOW INDEX FROM `".$_zp_conf_vars['mysql_prefix']."obj_to_tag`";
	$result = mysql_query($sql, $mysql_connection);
	if ($result) {
		while ($row = mysql_fetch_row($result)) {
			if ($row[2] == 'tagid') {
				$hastagidindex = true;
			}
		}
	}
	if (!$hastagidindex) {
		$sql_statements[] = "ALTER TABLE $tbl_obj_to_tag ADD INDEX (`tagid`)";
		$sql_statements[] = "ALTER TABLE $tbl_obj_to_tag ADD INDEX (`objectid`)";
	}
	
	//v1.2.1
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `title` `title` TEXT";
	$sql_statements[] = "ALTER TABLE $tbl_images CHANGE `title` `title` TEXT";
	$sql_statements[] = "ALTER TABLE $tbl_images CHANGE `location` `location` TEXT";
	$sql_statements[] = "ALTER TABLE $tbl_images CHANGE `credit` `credit` TEXT";
	$sql_statements[] = "ALTER TABLE $tbl_images CHANGE `copyright` `copyright` TEXT";
	//v1.2.2
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `thumbX` int(10) UNSIGNED default NULL;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `thumbY` int(10) UNSIGNED default NULL;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `thumbW` int(10) UNSIGNED default NULL;";
	$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `thumbH` int(10) UNSIGNED default NULL;";
	
	//v1.2.4
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news_categories.' DROP INDEX `cat_link`;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news_categories.' ADD UNIQUE (`cat_link`);';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' DROP INDEX `titlelink`;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD UNIQUE (`titlelink`);';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' DROP INDEX `titlelink`;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD UNIQUE (`titlelink`);';
	// Some versions of MySQL won't allow defaults on TEXT fields. Also can't make them NOT NULL because they don't have a default (catch 22) 
	$sql_statements[] = 'ALTER TABLE '.$tbl_comments.' CHANGE `comment` `comment` TEXT;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' CHANGE `title` `title` TEXT;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news_categories.' CHANGE `cat_name` `cat_name` TEXT;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' CHANGE `title` `title` TEXT;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_comments.' ADD COLUMN `custom_data` TEXT;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD COLUMN `expiredate` datetime default NULL';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD COLUMN `expiredate` datetime default NULL';
	$sql_statements[] = 'UPDATE '.$tbl_zenpage_pages.' SET `parentid`=NULL WHERE `parentid`=0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' CHANGE `sort_order` `sort_order` VARCHAR(48) NOT NULL default ""';
	//v1.2.5
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `parentid` `parentid` int(11) unsigned default NULL;";
	$sql_statements[] = "ALTER TABLE $tbl_images CHANGE `albumid` `albumid` int(11) unsigned default NULL;";
	$sql_statements[] = 'UPDATE '.$tbl_albums.' SET `parentid`=NULL WHERE `parentid`=0';
	$sql_statements[] = 'UPDATE '.$tbl_images.' SET `albumid`=NULL WHERE `albumid`=0';
	$sql_statements[] = 'DELETE FROM '.$tbl_zenpage_pages.' WHERE `titlelink`=""'; // cleanup for bad records
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' DROP INDEX `titlelink`;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD UNIQUE (`titlelink`);';
	
	$sql_statements[] = 'ALTER TABLE '.$tbl_albums.' ADD COLUMN `rating` FLOAT  NOT NULL DEFAULT 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_albums.' ADD COLUMN `rating_status` int(1) UNSIGNED default 3';
	$sql_statements[] = 'UPDATE '.$tbl_albums.' SET rating=total_value / total_votes WHERE total_votes > 0';
	
	$sql_statements[] = 'ALTER TABLE '.$tbl_images.' ADD COLUMN `rating` FLOAT  NOT NULL DEFAULT 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_images.' ADD COLUMN `rating_status` int(1) UNSIGNED default 3';
	$sql_statements[] = 'UPDATE '.$tbl_images.' SET rating=total_value / total_votes WHERE total_votes > 0';
	
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD COLUMN `total_votes` int(11) UNSIGNED default 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD COLUMN `total_value` int(11) UNSIGNED default 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD COLUMN `rating` FLOAT  NOT NULL DEFAULT 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD COLUMN `used_ips` longtext;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' ADD COLUMN `rating_status` int(1) UNSIGNED default 3';
	
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD COLUMN `total_votes` int(11) UNSIGNED default 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD COLUMN `total_value` int(11) UNSIGNED default 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD COLUMN `rating` FLOAT  NOT NULL DEFAULT 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD COLUMN `used_ips` longtext;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' ADD COLUMN `rating_status` int(1) UNSIGNED default 3';
	//v1.2.6
	$sql_statements[] = 'ALTER TABLE '.$tbl_administrators.' ADD COLUMN `custom_data` TEXT;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_administrators.' CHANGE `password` `pass` varchar(64)';
	$sql_statements[] = 'ALTER TABLE '.$tbl_administrators.' ADD COLUMN `valid` int(1) default 1;';
	$sql_statements[] = 'ALTER TABLE '.$tbl_administrators.' ADD COLUMN `group` varchar(64);';
	$sql = 'SHOW INDEX FROM '.$tbl_administrators;
	$result = mysql_query($sql, $mysql_connection);
	if ($result) {
		while ($row = mysql_fetch_row($result)) {
			if ($row[2] == 'user') {
				$sql_statements[] = "ALTER TABLE $tbl_administrators DROP INDEX `user`";
				$sql_statements[] = "ALTER TABLE $tbl_administrators ADD UNIQUE (`valid`, `user`)";
				break;
			}
		}
	}
	$sql_statements[] = 'ALTER TABLE '.$tbl_albums.' ADD COLUMN `watermark` varchar(255)';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_pages.' CHANGE `commentson` `commentson` int(1) UNSIGNED default 0';
	$sql_statements[] = 'ALTER TABLE '.$tbl_zenpage_news.' CHANGE `commentson` `commentson` int(1) UNSIGNED default 0';
	//v1.2.7
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `album_theme` `album_theme` varchar(127) DEFAULT NULL";
	$sql_statements[] = "ALTER TABLE $tbl_options DROP INDEX `unique_option`";
	$sql_statements[] = "ALTER TABLE $tbl_options ADD COLUMN `theme` varchar(127) DEFAULT NULL";
	$sql_statements[] = "ALTER TABLE $tbl_options CHANGE `name` `name` varchar(191) DEFAULT NULL";
	$sql_statements[] = "ALTER TABLE $tbl_options ADD UNIQUE `unique_option` (`name`, `ownerid`, `theme`)";
	$sql_statements[] = 'ALTER TABLE '.$tbl_images.' DROP COLUMN `EXIFValid`';
	$sql_statements[] = 'ALTER TABLE '.$tbl_images.' ADD COLUMN `hasMetadata` int(1) default 0';
	$sql_statements[] = 'UPDATE '.$tbl_images.' SET `mtime`=0'; // force metadata refresh
	$sql_statements[] = 'UPDATE '.$tbl_images.' SET `date`=NULL WHERE `date`="0000-00-00 00:00:00"'; // empty dates should be NULL
	$sql_statements[] = 'UPDATE '.$tbl_albums.' SET `date`=NULL WHERE `date`="0000-00-00 00:00:00"'; // force metadata refresh
	//v1.2.8
	$sql_statements[] = "ALTER TABLE $tbl_albums CHANGE `place` `location` TEXT";
	
	
	// do this last incase there are any field changes of like names!
	foreach ($_zp_exifvars as $key=>$exifvar) {
		$s = $exifvar[4];
		if ($s<255) {
			$size = "varchar($s)";
		} else {
			$size = 'MEDIUMTEXT';
		}
		$sql_statements[] = "ALTER TABLE $tbl_images ADD COLUMN `$key` $size default NULL";
		$sql_statements[] = "ALTER TABLE $tbl_images CHANGE `$key` `$key` $size default NULL";
	}
	
	
	/**************************************************************************************
	 ******                            END of UPGRADE SECTION     
	 ******                                                              
	 ******                           Add all new fields above      
	 ******                                                                         
	 ***************************************************************************************/

	$createTables = true;
	if (isset($_GET['create']) || isset($_GET['update']) && db_connect()) {
		if ($taskDisplay[substr($task,0,8)] == 'create') {
			echo "<h3>".gettext("About to create tables")."...</h3>";
		} else {
			echo "<h3>".gettext("About to update tables")."...</h3>";
		}
		setupLog(gettext("Begin table creation"));
		foreach($db_schema as $sql) {
			$result = mysql_query($sql);
			if (!$result) {
				$createTables = false;
				setupLog(sprintf(gettext('MySQL Query %1$s Failed. Error: %2$s'),$sql,mysql_error()));
				echo '<div class="error">';
				echo sprintf(gettext('Table creation failure: %s'),mysql_error());
				echo '</div>';
			} else {
				setupLog(sprintf(gettext('MySQL Query ( %s ) Success.'),$sql));
			}
		}
		// always run the update queries to insure the tables are up to current level
		setupLog(gettext("Begin table updates"));
		foreach($sql_statements as $sql) {
			$result = mysql_query($sql);
			if (!$result) {
				setupLog(sprintf(gettext('MySQL Query %1$s Failed. Error: %2$s'),$sql,mysql_error()));
			} else {
				setupLog(sprintf(gettext('MySQL Query ( %s ) Success.'),$sql));
			}
		}

		// set defaults on any options that need it
		setupLog(gettext("Done with database creation and update"));

		$prevRel = getOption('zenphoto_release');

		setupLog(sprintf(gettext("Previous Release was %s"),$prevRel));

		$_zp_gallery = new Gallery();
		require(dirname(__FILE__).'/setup-option-defaults.php');

		// 1.1.6 special cleanup section for plugins
		$badplugs = array ('exifimagerotate.php', 'flip_image.php', 'image_mirror.php', 'image_rotate.php', 'supergallery-functions.php');
		foreach ($badplugs as $plug) {
			$path = SERVERPATH . '/' . ZENFOLDER .'/'.PLUGIN_FOLDER.'/' . $plug;
			@unlink($path);
		}

		if ($prevRel < 1690) {  // cleanup root album DB records
			$_zp_gallery->garbageCollect(true, true);
		}

		// 1.1.7 conversion to the theme option tables
		$albums = $_zp_gallery->getAlbums();
		foreach ($albums as $albumname) {
			$album = new Album($_zp_gallery, $albumname);
			$theme = $album->getAlbumTheme();
			if (!empty($theme)) {
				$tbl = prefix(getOptionTableName($album->name));
				$sql = "SELECT `name`,`value` FROM " . $tbl;
				$result = query_full_array($sql, true);
				if (is_array($result)) {
					foreach ($result as $row) {
						setThemeOption($row['name'], $row['value'], $album);
					}
				}
				query('DROP TABLE '.$tbl, true);
			}
		}

		// 1.2 force up-convert to tag tables
		$convert = false;
		$result = query_full_array("SHOW COLUMNS FROM ".prefix('images').' LIKE "%tags%"');
		if (is_array($result)) {
			foreach ($result as $row) {
				if ($row['Field'] == 'tags') {
					$convert = true;
					break;
				}
			}
		}
		if ($convert) {
			// convert the tags to a table
			$result = query_full_array("SELECT `tags` FROM ". prefix('images'));
			$alltags = '';
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
					$tagLC = $_zp_UTF8->strtolower($clean);
					if (!in_array($tagLC, $seen)) {
						$seen[] = $tagLC;
						$taglist[] = $clean;
					}
				}
			}
			$alltags = array_merge($taglist);
			foreach ($alltags as $tag) {
				query("INSERT INTO " . prefix('tags') . " (name) VALUES ('" . zp_escape_string($tag) . "')", true);
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
		
		// update zenpage codeblocks--remove the base64 encoding
		$sql = 'SELECT `id`, `codeblock` FROM '.prefix('zenpage_news').' WHERE `codeblock` NOT REGEXP "^a:[0-9]+:{"';
		$result = query_full_array($sql);
		if (is_array($result)) {
			foreach ($result as $row) {
				$codeblock = base64_decode($row['codeblock']);
				$sql = 'UPDATE '.prefix('zenpage_news').' SET `codeblock`="'.zp_escape_string($codeblock).'" WHERE `id`='.$row['id'];
				query($sql);
			}
		}
		$sql = 'SELECT `id`, `codeblock` FROM '.prefix('zenpage_pages').' WHERE `codeblock` NOT REGEXP "^a:[0-9]+:{"';
		$result = query_full_array($sql);
		if (is_array($result)) {
			foreach ($result as $row) {
				$codeblock = base64_decode($row['codeblock']);
				$sql = 'UPDATE '.prefix('zenpage_pages').' SET `codeblock`="'.zp_escape_string($codeblock).'" WHERE `id`='.$row['id'];
				query($sql);
			}
		}
		// cache file names changed in Changeset 4455
		if ($prevRel < 4455) {
			$gallery = new Gallery();
			$gallery->clearCache();
		}
		
		echo "<h3>";
		if ($taskDisplay[substr($task,0,8)] == 'create') {
			if ($createTables) {
				echo gettext('Done with table create!');
			} else {
				echo gettext('Done with table create with errors!');
			}
		} else {
			if ($createTables) {
				echo gettext('Done with table update');
			} else {
				echo gettext('Done with table update with errors');
			}
		}
		echo "</h3>";

		// fixes 1.2 move/copy albums with wrong ids
		$albums = $_zp_gallery->getAlbums();
		foreach ($albums as $album) {
			checkAlbumParentid($album, NULL);
		}
		
		if ($createTables) {
			if ($_zp_loggedin == ADMIN_RIGHTS) {
				$filelist = safe_glob(SERVERPATH . "/" . BACKUPFOLDER . '/*.zdb');
				if (count($filelist) > 0) {
					echo "<p>".sprintf(gettext("You may <a href=\"admin-users.php?page=users\">set your admin user and password</a> or <a href=\"%s/backup_restore.php\">run backup-restore</a>"),UTILITIES_FOLDER)."</p>";
				} else {
					echo "<p>".gettext("You need to <a href=\"admin-users.php\">set your admin user and password</a>")."</p>";
				}				
			} else {
				echo "<p>".gettext("You can now  <a href=\"../\">View your gallery</a> or <a href=\"admin.php\">administer.</a>")."</p>";
			}
		}

	} else if (db_connect()) {
		if (!empty($dbmsg)) {
			?>
			<h2><?php echo $dbmsg; ?></h2>
			<?php		
		}
		?>
		<div class="dbwindow">
			<ul>
			<?php
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
			?>
				<li class="createdb">
					<?php
					printf(gettext("Database tables to create: %s"), $db_list);
					?>
				</li>
				<?php
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
				?>
				<li class="updatedb">
					<?php
					printf(gettext("Database tables to update: %s"), $db_list);
					?>
				</li>
				<?php
			}
			?>
			</ul>
		</div>
		<?php
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
		?>
		<p class='buttons'>
		<?php
		if ($warn) $img = 'warn.png'; else $img = 'pass.png';
		if (isset($zenphoto_themes)) {
			$th = '&amp;themelist='.rawurlencode(serialize($zenphoto_themes));
		} else {
			$th = '';
		}
		?>
		<a href="?checked&amp;<?php echo $task.$mod.$th; ?>" title="<?php echo gettext("create and or update the database tables."); ?>" style="font-size: 15pt; font-weight: bold;">
		<img src="images/<?php echo $img; ?>" alt=""/>
		<?php echo gettext("Go"); ?></a>
		</p>
		<br clear="all" /><br clear="all" />
		<?php
	} else {
		?>
		<div class="error">
			<h3><?php echo gettext("database did not connect"); ?></h3>
			<p>
			<?php echo gettext("If you haven't created the database yet, now would be a good time."); ?>
			</p>
		</div>
		<?php
	}
} else {
	// The config file hasn't been created yet. Show the steps.
	?>
	<div class="error">
		<?php echo gettext("The zp-config.php file does not exist. You should run setup.php to check your configuration and create this file."); ?>
	</div>
<?php
}

?>
</div><!-- content -->
</div><!-- main -->

<?php
if ($noxlate > 0) {
	require_once(dirname(__FILE__).'/'.PLUGIN_FOLDER.'/dynamic-locale.php');
	printLanguageSelector();
}
printAdminFooter();
?>

</body>
</html>

