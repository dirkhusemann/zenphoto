<?php
/**
 * Use this utility to reset your album thumbnails to either "random" or from an ordered field query
 * 
 * @package admin
 */

define('OFFSET_PATH', 3);
define('RECORD_SEPARATOR', ':****:');
define('TABLE_SEPARATOR', '::');
define('RESPOND_COUNTER', 1000);
chdir(dirname(dirname(__FILE__)));

require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
require_once(dirname(dirname(__FILE__)).'/admin-globals.php');
require_once(dirname(dirname(__FILE__)).'/template-functions.php');

$current = getOption('AlbumThumbSelecorText');
$button_text = gettext('Reset album thumbs');
$button_hint = sprintf(gettext('Reset album thumbnails to either random or %s'),getOption('AlbumThumbSelecorText'));
$button_icon = 'images/reset1.png';
$button_rights = MANAGE_ALL_ALBUM_RIGHTS;


if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!is_null(getOption('admin_reset_date'))) {
	if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
	}
}

$buffer = '';
$gallery = new Gallery();
$webpath = WEBPATH.'/'.ZENFOLDER.'/';
$selector = array(array('field'=>'ID', 'direction'=>'DESC', 'desc'=>gettext('most recent')),
									array('field'=>'mtime', 'direction'=>'', 'desc'=>gettext('oldest')),
									array('field'=>'title', 'direction'=>'', 'desc'=>gettext('first alphabetically')),
									array('field'=>'hitcounter', 'direction'=>'DESC', 'desc'=>gettext('most viewed'))
									);

printAdminHeader();
echo '</head>';
?>

<body>
<?php printLogoAndLinks(); ?>
<div id="main">
<?php printTabs('database'); ?>
<div id="content">
<h1><?php echo (gettext('Reset your album thumbnails')); ?></h1>
<?php
if (isset($_REQUEST['thumbtype']) && db_connect()) {
	$value = sanitize($_REQUEST['thumbtype'], 3);
	$sql = 'UPDATE '.prefix('albums').' SET `thumb`="'.$value.'"';
	if (query($sql)) {
		if ($value == '') {
			$reset = 'Random';
		} else {
			$reset = $current;
		}
		?>
		<div class="messagebox" id="fade-message">
		<h2><?php printf(gettext("Thumbnails all set to <em>%s</em>."), $reset); ?></h2>
		</div>
		<?php
	} else {
		?>
		<div class="errorbox" id="fade-message">
		<h2><?php echo gettext("Thumbnail reset query failed"); ?></h2>
		</div>
		<?php
	}
} else if (isset($_REQUEST['thumbselector'])) {
	$key = sanitize_numeric($_REQUEST['thumbselector']);
	$current=$selector[$key]['desc'];
	setOption('AlbumThumbSelectField',$selector[$key]['field']);
	setOption('AlbumThumbSelectDirection',$selector[$key]['direction']);
	setOption('AlbumThumbSelecorText',$current);	
}
if (db_connect()) {
	?>
	<form name="set_random" action="">
		<input type="hidden" name="thumbtype" value="" />
		<div class="buttons pad_button" id="set_random">
		<button class="tooltip" type="submit" title="<?php echo gettext("Sets all album thumbs to random."); ?>">
			<img src="<?php echo $webpath; ?>images/burst1.png" alt="" /> <?php echo gettext("Set to <em>random</em>"); ?>
		</button>
		</div>
		<br clear="all" />
		<br clear="all" />
	</form>
	<br />
	<br />
	<table>
		<tr>
			<td>
				<form name="set_first" action="">
					<input type="hidden" name="thumbtype" value="1" />
					<div class="buttons pad_button" id="set_first">
					<button class="tooltip" type="submit" title="<?php printf(gettext("Set all album thumbs to use the %s image."),$current); ?>">
						<img src="<?php echo $webpath; ?>images/burst1.png" alt="" /> <?php printf(gettext("Set to <em>%s</em>"),$current); ?>
					</button>
					</div>
				</form>
			</td>
			<td>
				<?php echo gettext('Change button to') ?>
				<form name="setselector" action="">
					<select id="thumbselector" name="thumbselector" onchange="this.form.submit()">
					<?php
					$selections = array();
					$currentkey = '';
					foreach ($selector as $key=>$selection) {
						$selections[$selection['desc']] = $key;
						if ($selection['desc'] == $current) $currentkey=$key;
					}
					generateListFromArray(array($currentkey),$selections,false,true);
					?>
					</select>
				</form>
			</td>
		</tr>
	</table>
	<br clear="all" />
	<br clear="all" />
	<p>
	<?php printf(gettext('These buttons allow you to set all of your album thumbs to either a <em>random</em> image or to the <em>%s</em> image. This will override any album thumb selections you have made on individual albums.'),$current); ?> 
	</p>
	<?php
} else {
	echo "<h3>".gettext("database not connected")."</h3>";
	echo "<p>".gettext("Check the zp-config.php file to make sure you've got the right username, password, host, and database. If you haven't created the database yet, now would be a good time.");
}

?>
	

</div>
<!-- content --></div>
<!-- main -->
<?php printAdminFooter(); ?>
</body>
<?php echo "</html>"; ?>




