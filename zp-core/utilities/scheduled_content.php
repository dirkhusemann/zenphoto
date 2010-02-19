<?php
/**
 * Manage the timing of publishing new content
 * 
 * This plugin allows you to change the default setting of the albums: published and 
 * the images: visible fields.
 * 
 * It also allows you to list unpublished albums and not visible images from before a
 * specific data and time. You can select albums and images from these lists to be published.
 * NOTE: currently there is no record of when albums were first encountered, so all unpublished 
 * albums are show.
 * 
 * So you can freely upload albums and images then on a periodic basis review which ones to make available
 * to visitors of your gallery.
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

$button_text = gettext('Publish content');
$button_hint = gettext('Manage unpublished content in your gallery.');
$button_icon = 'images/calendar.png';
$button_rights = ALBUM_RIGHTS;


if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!($_zp_loggedin & (ADMIN_RIGHTS | ALBUM_RIGHTS))) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

$gallery = new Gallery();
$webpath = WEBPATH.'/'.ZENFOLDER.'/';

printAdminHeader();
?>
<style type="text/css">
.schedulealbumchecklist li {
	background: none;
	padding-left: 0;
	text-align: left;
	
}

.schedulealbumchecklist {
	border: 1px solid #ccc;
	list-style: none;
	height: 8em;
	overflow: auto;
	width: 50em;
	background-color: white;
}

.schedulealbumchecklist,.schedulealbumchecklist li {
	margin: 0;
	padding: 0;
	border-bottom: 1px dotted #C6D880;
}

.schedulealbumchecklist label {
	display: block;
	padding: 0 0.2em 0 25px;
	text-indent: -25px;
}

* html .schedulealbumchecklist label {
	height: 1%;
}
.schedulealbumchecklist li {
	background: none;
	padding-left: 0;
	text-align: left;
	
}

.scheduleimagechecklist {
	border: 1px solid #ccc;
	list-style: none;
	height: 40em;
	overflow: auto;
	width: 50em;
	background-color: white;
}

.scheduleimagechecklist,.scheduleimagechecklist li {
	margin: 0;
	padding: 0;
	border-bottom: 1px dotted #C6D880;
}

.scheduleimagechecklist label {
	display: block;
	padding: 0 0.2em 0 25px;
	text-indent: -25px;
}

.scheduleimagechecklist li p {
	margin-top: 5px;
	text-align:center;
	background: #777;
	color: #fff;
}

* html .scheduleimagechecklist label {
	height: 1%;
}
</style>
<?php
function unpublishSubalbums($album) {
	global $gallery;
	$albums = $album->getAlbums();
	foreach ($albums as $albumname) {
		$subalbum = new Album($gallery, $albumname);
		$subalbum->setShow(false);
		$subalbum->save();
		unpublishSubalbums($subalbum);
	}
}

echo '</head>';
?>

<body>
<?php printLogoAndLinks(); ?>
<div id="main">
<?php printTabs('content'); ?>
<div id="content">
<h1><?php echo (gettext('Manage content publication')); ?></h1>
<?php
$publish_albums_list = array();
$publish_images_list = array();
if (db_connect()) {
	if (isset($_POST['set_defaults'])) {
		if (isset($_POST['album_default'])) {
			$albpublish = 1;
		} else {
			$albpublish = 0;
		}
		$sql = "ALTER TABLE ".prefix('albums').' CHANGE `show` `show` INT( 1 ) NOT NULL DEFAULT "'.$albpublish.'"';
		query($sql);
		if (isset($_POST['image_default'])) {
			$imgpublish = 1;
		} else {
			$imgpublish = 0;
		}
		$sql = "ALTER TABLE ".prefix('images').' CHANGE `show` `show` INT( 1 ) NOT NULL DEFAULT "'.$imgpublish.'"';
		query($sql);
	} else if (isset($_POST['publish_albums'])) {
		$sql = '';
		unset($_POST['publish_albums']);
		foreach ($_POST as $key=>$albumid) {
			$albumid = sanitize_numeric($albumid);
			if (is_numeric($key)) {
				$sql .= '`id`="'.sanitize_numeric($albumid).'" OR ';
			}
		}
		if (!empty($sql)) {
			$sql = substr($sql, 0, -4);
			$sql = 'UPDATE '.prefix('albums').' SET `show`="1" WHERE '.$sql;
			query($sql);
		}
	} else if (isset($_POST['publish_images'])) {
		unset($_POST['publish_images']);
		$sql = '';
		foreach ($_POST as $action) {
			$i = strrpos($action,'_');
			$imageid = sanitize_numeric(substr($action,$i+1));
			switch(substr($action,0,$i)) {
				case 'pub':
					if (is_numeric($imageid)) $sql .= '`id`="'.$imageid.'" OR ';
					break;
				case 'del':
					$rowi = query_single_row('SELECT * FROM '.prefix('images').' WHERE `id`='.$imageid);
					$rowa = query_single_row('SELECT * FROM '.prefix('albums').' WHERE `id`='.$rowi['albumid']);
					$album = new Album($gallery, $rowa['folder']);
					$image = newImage($album, $rowi['filename']);
					$image->deleteimage();
					break;
			}
		}
		if (!empty($sql)) {
			$sql = substr($sql, 0, -4);
			$sql = 'UPDATE '.prefix('images').' SET `show`="1" WHERE '.$sql;
			query($sql);
		}
	}
	?>
	<h3><?php gettext("database connected"); ?></h3>
	<br />
	<?php
	$sql = 'SHOW COLUMNS FROM '.prefix('albums');
	$result = query_full_array($sql);
	if (is_array($result)) {
		foreach ($result as $row) {
			if ($row['Field'] == 'show') {
				$albpublish = $row['Default'];
				break;	
			}
		}
	}
	$sql = 'SHOW COLUMNS FROM '.prefix('images');
	$result = query_full_array($sql);
	if (is_array($result)) {
		foreach ($result as $row) {
			if ($row['Field'] == 'show') {
				$imgpublish = $row['Default'];
				break;	
			}
		}
	}
	if (isset($_POST['publish_date']))	{
		$requestdate = dateTimeConvert(sanitize($_POST['publish_date']));
	} else {
		$requestdate = date('Y-m-d H:i:s');
	}
	
	$albumidlist = '';
	$albumids = '';
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		$albumlist = $gallery->getAlbums();
	} else {
		$albumlist = getManagedAlbumList();
		$albumIDs = array();
		foreach ($albumlist as $albumname) {
			$subalbums = getAllSubAlbumIDs($albumname);
			foreach($subalbums as $ID) {
				$albumIDs[] = $ID['id'];
			}
		}
		$i = 0;
		foreach ($albumIDs as $ID) {
			if ($i>0) {
				$albumidlist .= ' OR ';
				$albumids .= ' OR ';
			}
			$albumidlist .= prefix('images').'.albumid='.$ID;
			$albumids .= '`id`='.$ID;
			$i++;
		}
		if (!empty($albumlist)) {
			$albumids = ' AND ('.$albumids.')';
			$albumidlist = ' AND ('.$albumidlist.')';
		}
	}
	if (isset($_GET['propagate_unpublished'])) {
		foreach ($albumlist as $albumname) {
			$album = new Album($gallery, $albumname);
			if (!$album->getShow()) {
				unpublishSubalbums($album);
			}
		}
	}
	
	$mtime = dateTimeConvert(sanitize($requestdate), true);
	$sql = "SELECT `folder`, `id` FROM ".prefix('albums').' WHERE `show`="0"'.$albumids;
	$result = query_full_array($sql);
	if (is_array($result)) {
		foreach ($result as $row) {
			$publish_albums_list[$row['folder']] = $row['id'];
		}
	}
	$sql = 'SELECT `filename`, '.prefix('images').'.id as id, folder FROM '.prefix('images').','.prefix('albums').' WHERE '.prefix('images').'.show="0" AND '.
					prefix('images').'.mtime < "'.$mtime.'" AND '.prefix('albums').'.id='.prefix('images').'.albumid'.$albumidlist;
	$result = query_full_array($sql);
	if (is_array($result)) {
		foreach ($result as $row) {
			$publish_images_list[$row['folder']][$row['filename']] = $row['id'];
		}
	}
	?>
<?php if ($_zp_loggedin & ADMIN_RIGHTS) { ?>
<form name="set_publication" action="" method="post">
<input type="hidden" name="set_defaults" value="true" />
		<input type="checkbox" name="album_default"	value="1"<?php if ($albpublish) echo ' checked="checked"'; ?> /> <?php echo gettext("Publish albums by default"); ?>
		<br />
		<input type="checkbox" name="image_default"	value="1"<?php if ($imgpublish) echo ' checked="checked"'; ?> /> <?php echo gettext("Make images visible by default"); ?>
		<br />
<div class="buttons pad_button" id="setdefaults">
<button class="tooltip" type="submit" title="<?php echo gettext("Set defaults for album publishing and image visibility."); ?>">
	<img src="<?php echo $webpath; ?>images/burst1.png" alt="" /> <?php echo gettext("Set defaults"); ?>
</button>
</div>
<br clear="all" />
<br clear="all" />
</form>
<?php 
}
if (count($publish_albums_list) > 0) { 
?>
	<form name="publish" action="" method="post"><?php echo gettext('Unpublished albums:'); ?>
	<input type="hidden" name="publish_albums" value="true" />
	<ul class="schedulealbumchecklist">
	<?php	generateUnorderedListFromArray($publish_albums_list, $publish_albums_list, 'sched_', false, true, true); ?>
	</ul>
	<div class="buttons pad_button" id="publishalbums">
	<button class="tooltip" type="submit" title="<?php echo gettext("Publish waiting albums."); ?>">
		<img src="<?php echo $webpath; ?>images/cache1.png" alt="" /> <?php echo gettext("Publish albums"); ?>
	</button>
	</div>
	<br clear="all" />
	<br clear="all" />
	</form>
	<p class="buttons">
		<a href="?propagate_unpublished" title="<?php echo gettext('Set all subalbums of an unpublished album to unpublished.'); ?>">
		<img src="<?php echo $webpath; ?>images/redo.png" alt="" />
			<?php echo gettext('Propagate unpublished state'); ?>
		</a>
	</p>
	<br clear="all" />
	<br clear="all" />
	<?php
	} else {
		echo '<p>'.gettext('No albums are unpublished.').'</p>';
	}
?>

<script type="text/javascript">
	$(function() {
		$("#publish_date").datepicker({
							showOn: 'button',
							buttonImage: '../images/calendar.png',
							buttonText: '<?php echo gettext('calendar'); ?>',
							buttonImageOnly: true
							});
	});
</script>
<form name="review" action="" method="post">
<?php printf(gettext('Review images older than: %s'),'<input type="text" size="20" id="publish_date" name="publish_date" value="'.$requestdate.'" />'); ?>
<input type="hidden" name="review" value="true" />

<div class="buttons pad_button" id="reviewobjects">
<button class="tooltip" type="submit" title="<?php echo gettext("Review not visible images."); ?>">
	<img src="<?php echo $webpath; ?>images/quest.png" alt="" /> <?php echo gettext("Review images"); ?>
</button>
</div>
<br clear="all" />
<br clear="all" />
</form>

<?php
if (count($publish_images_list) > 0) { 
	?>
	<script type="text/javascript">
	function confirmdel(obj, id, msg) {
		if (msg) {
			if (confirm('<?php echo gettext("Are you sure you want to select this image for deletion?"); ?>')) {
				jQuery('#'+id).css({color:'red'}); 
				obj.checked = true;
			}
		} else {
			jQuery('#'+id).css({color:'black'}); 
			obj.checked = true;
		}
	}
	</script>
	<form name="publish" action="" method="post"><?php echo gettext('Not visible images:'); ?>
	<input type="hidden" name="publish_images" value="true" />
	<ul class="scheduleimagechecklist">
	<?php
	foreach ($publish_images_list as $key=>$imagelist) {
		$album = new Album($gallery,$key);
		$imagelist = array_flip($imagelist);
		natcasesort($imagelist);
		$imagelist = array_flip($imagelist);
		?>
		<li><p><strong><?php echo $key; ?></strong></p></li>
		<?php 
		foreach ($imagelist as $display=>$item) {
			$listitem = postIndexEncode($item);
			?>
			<li>
				<table>
					<tr>
						<td>
							<span style="white-space:nowrap">
								<label>
									<input id="pub_<?php echo $item; ?>" name="r_<?php echo $item; ?>" type="radio" value="pub_<?php echo $item; ?>" onclick="confirmdel(this, 'label_del_<?php echo $item; ?>',false)" />
									<?php echo gettext('Publish'); ?>
								</label>
							</span>
							<span style="white-space:nowrap">
								<label>
									<input id="notpub_<?php echo $item; ?>" name="r_<?php echo $item; ?>" type="radio"	value="notpub_<?php echo $item; ?>"	checked="checked" onclick="confirmdel(this, 'label_del_<?php echo $item; ?>',false)" />
									<?php echo gettext('Do not publish'); ?>
								</label>
							</span>
							<span style="white-space:nowrap">
								<label id="label_del_<?php echo $item; ?>">
									<input id="del_<?php echo $item; ?>" name="r_<?php echo $item; ?>" type="radio"	value="del_<?php echo $item; ?>" onclick="confirmdel(this, 'label_del_<?php echo $item; ?>',true)" />
									<?php echo gettext('Delete'); ?>
								</label>
							</span>
						</td>
						<td>
							<?php $image = newImage($album,$display); ?>
							<img src="<?php echo $image->getThumb()?>" alt="<?php echo $image->filename; ?>"/>
						</td>
						<td>
							<?php printf(gettext('<strong>%s</strong>'),$key); ?><br />
							<?php printf(gettext('%s'),$display); ?>
						</td>
					</tr>
				</table>
			</li>
			<?php
		} 
	}
	?>
	</ul>
	<div class="buttons pad_button" id="process">
	<button class="tooltip" type="submit" title="<?php echo gettext("Process the above changes."); ?>">
		<img src="<?php echo $webpath; ?>images/cache1.png" alt="" /> <?php echo gettext("Process changes"); ?>
	</button>
	</div>
	<br clear="all" />
	<br clear="all" />
	</form>
<?php
	} else {
		echo '<p>'.gettext('No images meet the criteria.').'</p>';
	}
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




