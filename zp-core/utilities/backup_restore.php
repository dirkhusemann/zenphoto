<?php
/**
 * Backup and restore of the zenphoto database tables
 * 
 * This plugin provides a means to make backups of your zenphoto database and
 * at a later time restore the the database to the contents of one of these backups.
 * 
 * @package admin
 */

$button_text = gettext('Backup/Restore');
$button_hint = gettext('Backup and restore your gallery database.');
$button_icon = 'images/folder.png';
$button_rights = ADMIN_RIGHTS;

define('OFFSET_PATH', 3);
define('RECORD_SEPARATOR', ':****:');
define('TABLE_SEPARATOR', '::');
define('RESPOND_COUNTER', 1000);
chdir(dirname(dirname(__FILE__)));

require_once(dirname(dirname(__FILE__)).'/template-functions.php');
require_once(dirname(dirname(__FILE__)).'/admin-functions.php');


if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!is_null(getOption('admin_reset_date'))) {
	if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
		exit();
	}
}

$buffer = '';
function fillbuffer($handle) {
	global $buffer;
	$record = fread($handle, 8192);
	if ($record === false || empty($record)) {
		return false;
	}
	$buffer .= $record;
	return true;
}
function getrow($handle) {
	global $buffer;
	global $counter;
	$end = strpos($buffer, RECORD_SEPARATOR);
	while ($end === false) {
		if ($end = fillbuffer($handle)) {
			$end = strpos($buffer, RECORD_SEPARATOR);
		} else {
			return false;
		}
	}
	$result = substr($buffer, 0, $end);
	$buffer = substr($buffer, $end+strlen(RECORD_SEPARATOR));
	return $result;
}

$gallery = new Gallery();
$webpath = WEBPATH.'/'.ZENFOLDER.'/';

printAdminHeader($webpath);
echo '</head>';
?>

<body>
<?php printLogoAndLinks(); ?>
<div id="main">
<?php printTabs('database'); ?>
<div id="content">
<h1><?php echo (gettext('Backup and Restore your Database')); ?></h1>
<?php
if (isset($_REQUEST['backup']) && db_connect()) {
	$prefix = substr(prefix(''), 1, -1);
	$sql = "SHOW TABLES FROM `".$_zp_conf_vars['mysql_database']."` LIKE '".$prefix."%';";
	$result = query_full_array($sql);
	if (is_array($result)) {
		$folder = SERVERPATH . "/" . BACKUPFOLDER;
		$filename = $folder . '/backup-' . date('Y_m_d-H_i_s').'.zdb';
		if (!is_dir($folder)) {
			mkdir ($folder, CHMOD_VALUE);
		}
		@chmod($folder, CHMOD_VALUE);
		$handle = fopen($filename, 'w');
		if ($handle === false) {
			printf(gettext('Failed to open %s for writing.'), $filename);
		} else {
			$counter = 0;
			$writeresult = true;
			foreach ($result as $row) {
				$table = array_shift($row);
				$unprefixed_table = substr($table, strlen($prefix));
				$sql = 'SELECT * from `'.$table.'` ORDER BY ID';
				$result = query($sql);
				if ($result) {
					while ($tablerow = mysql_fetch_assoc($result)) {
						foreach ($tablerow as $key=>$element) {
							if (!empty($element)) {
								$tablerow[$key] = gzcompress($element, 4);
							}
						}
						$storestring = $unprefixed_table.TABLE_SEPARATOR.serialize($tablerow).RECORD_SEPARATOR;
						$writeresult = fwrite($handle, $storestring);
						if ($writeresult === false) {
							echo gettext('failed writing to backup!');
							break;
						}
						$counter ++;
						if ($counter >= RESPOND_COUNTER) {
							echo ' ';
							$counter = 0;
						}
					}
				}
				if ($writeresult === false) break;
			}
			fclose($handle);
		}
	} else {
		echo gettext('MySQL SHOW TABLES failed!');
		$writeresult = false;
	}
	if ($writeresult) {
		?>
		<div class="messagebox" id="fade-message">
		<h2><?php echo gettext("backup complete"); ?></h2>
		</div>
		<?php
	} else {
		?>
		<div class="errorbox" id="fade-message">
		<h2><?php echo gettext("backup failed"); ?></h2>
		</div>
		<?php
	}
} else if (isset($_REQUEST['restore']) && db_connect()) {
	$success = false;
	if (isset($_REQUEST['backupfile'])) {
		$folder = SERVERPATH . '/' . BACKUPFOLDER .'/';
		$filename = $folder . UTF8ToFileSystem(sanitize($_REQUEST['backupfile'], 3)).'.zdb';
		if (file_exists($filename)) {
			$handle = fopen($filename, 'r');
			if ($handle !== false) {
				$success = true;
				$string = getrow($handle);
				$counter = 0;
				while (!empty($string)) {
					$sep = strpos($string, TABLE_SEPARATOR);
					$table = substr($string, 0, $sep);
					$row = unserialize(substr($string, $sep+strlen(TABLE_SEPARATOR)));
					$items = '';
					$values = '';
					$updates = '';
					$special_keys = '';
					$special_values = '';
					foreach($row as $key=>$element) {
						if (!empty($element)) {
							$element = gzuncompress($element);
						}
						$items .= '`'.$key.'`,';
						if (is_null($element)) {
							$values .= 'NULL,';
							$updates .= '`'.$key.'`=NULL,';
						} else {
							$values .= '"'.mysql_real_escape_string($element).'",';
							$updates .= '`'.$key.'`="'.mysql_real_escape_string($element).'",';
						}
					}

					$items = substr($items,0,-1);
					$values = substr($values,0,-1);
					$updates = substr($updates,0,-1);

					$sql = 'REPLACE INTO '.prefix($table).' ('.$items.') VALUES ('.$values.')';
					$success = query($sql);
					if (!$success) break;
					$counter ++;
					if ($counter >= RESPOND_COUNTER) {
						echo ' ';
						$counter = 0;
					}
					$string = getrow($handle);
				}
				fclose($handle);
			}
		}
	}
	if ($success) {
		?>
		<div class="messagebox" id="fade-message">
		<h2><?php echo gettext("restore complete"); ?></h2>
		</div>
		<?php
	} else {
		?>
		<div class="errorbox" id="fade-message">
		<h2><?php echo gettext("restore failed"); ?></h2>
		</div>
		<?php
	}
}
if (db_connect()) {
	?>
	<h3><?php gettext("database connected"); ?></h3>
	<p><?php printf(gettext("Your database is <strong>%s</strong>"),getOption('mysql_database')); ?><br />
	<?php printf(gettext("Tables are prefixed by <strong>%s</strong>"), getOption('mysql_prefix')); ?>
	</p>
	<br />
	<br />
	<form name="backup_gallery" action=""><input type="hidden" name="backup"
		value="true">
	<div class="buttons pad_button" id="dbbackup">
	<button class="tooltip" type="submit" title="<?php echo gettext("Backup the tables in your database."); ?>">
		<img src="<?php echo $webpath; ?>images/burst1.png" alt="" /> <?php echo gettext("Backup the Database"); ?>
	</button>
	</div>
	<br clear="all" />
	<br clear="all" />
	</form>
	<br />
	<br />
	<?php
	$filelist = safe_glob(SERVERPATH . "/" . BACKUPFOLDER . '/*.zdb');
	if (count($filelist) <= 0) {
		echo gettext('You have not yet created a backup set.');
	} else {
	?>
		<form name="restore_gallery" action=""><?php echo gettext('Select the database restore file:'); ?>
		<br />
		<select id="backupfile" name="backupfile">
		<?php	generateListFromFiles('', SERVERPATH . "/" . BACKUPFOLDER, '.zdb', true);	?>
		</select> <input type="hidden" name="restore" value="true">
		<div class="buttons pad_button" id="dbrestore">
		<button class="tooltip" type="submit" title="<?php echo gettext("Restore the tables in your database from a previous backup."); ?>">
			<img src="<?php echo $webpath; ?>images/redo.png" alt="" /> <?php echo gettext("Restore the Database"); ?>
		</button>
		</div>
		<br clear="all" />
		<br clear="all" />
		</form>
	<?php
	}
} else {
	echo "<h3>".gettext("database not connected")."</h3>";
	echo "<p>".gettext("Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.");
}
echo	'<p>';
echo gettext('The backup facility creates database snapshots in the <code>backup</code> folder of your installation.').' '; 
echo gettext('These backups are named in according to the date and time the backup was taken.').' ';
echo gettext('You restore your database by selecting a backup and pressing the <em>Restore the Database</em> button').' ';
echo gettext('The restore is “additive”. That is the database is not emptied before the restore is attempted.');
echo '</p>'
?>
</div>
<!-- content --></div>
<!-- main -->
<?php printAdminFooter(); ?>
</body>
<?php echo "</html>"; ?>




