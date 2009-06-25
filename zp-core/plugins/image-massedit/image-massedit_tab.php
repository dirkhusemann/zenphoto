<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */
define ('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
$gallery = new Gallery();
if (isset($_GET['album'])) {
	$folder = sanitize($_GET['album'],3);
} else {
	$folder = '';
}
if (!zp_loggedin() || empty($folder) || !isMyAlbum($folder, ALBUM_RIGHTS)) {
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__) );
	exit();
}
$album = new Album(new Gallery(), $folder);
if ($album->isDynamic()) {
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__) );
	exit();
}
if (isset($_GET['action'])) {
	$action = sanitize($_GET['action'],3);
	if ($action == "save") {
		if (isset($_POST['thumb'])) {
			$thumbnail = sanitize_numeric($_POST['thumb']);
		} else {
			$thumbnail = -1;
		}
		
		for ($i = 0; $i < $_POST['totalimages']; $i++) {
			$filename = strip($_POST["$i-filename"]);

			// The file might no longer exist
			$image = newImage($album, $filename);
			if ($image->exists) {
				if ($thumbnail == $i) { //selected as album thumb
					$album = $image->getAlbum();
					$album->setAlbumThumb($image->filename);
					$album->save();
				}
				if (isset($_POST[$i.'-reset_rating'])) {
					$image->set('total_value', 0);
					$image->set('total_votes', 0);
					$image->set('used_ips', 0);
				}
				$image->setTitle(process_language_string_save("$i-title", 2));
				$image->setDesc(process_language_string_save("$i-desc", 1));


				$image->setShow(isset($_POST["$i-Visible"]));
				$image->setCommentsAllowed(isset($_POST["$i-allowcomments"]));
				if (isset($_POST["$i-reset_hitcounter"])) {
					$image->set('hitcounter', 0);
				}
				$image->save();
			}
		}

	}
}
// Print our header
$page = 'edit';
printAdminHeader(WEBPATH.'/'.ZENFOLDER.'/', false); // no tinyMCE
echo "\n</head>";
?>

<body>

<?php	printLogoAndLinks(); ?>
<div id="main">
<?php
printTabs($page);
?>
<div id="content">
<?php

$subalbums = $album->getSubAlbums();
$images = $album->getImages(0);

$albumdir = dirname($folder);
if (($albumdir == '/') || ($albumdir == '.')) {
	$albumdir = '';
} else {
	$albumdir = "&album=" . urlencode($albumdir);
}
$allimagecount = count($images);
$albumlink = '?page=edit&album='.urlencode($album->name);
if (!is_array($zenphoto_tabs['edit']['subtabs'])) $zenphoto_tabs['edit']['subtabs'] = array();
if (count($subalbums) > 0) $zenphoto_tabs['edit']['subtabs'] = array_merge(array(gettext('Subalbums') => 'admin-edit.php'.$albumlink.'&page=edit&tab=subalbuminfo'), $zenphoto_tabs['edit']['subtabs']);
$zenphoto_tabs['edit']['subtabs'] = array_merge(array(gettext('Album') => 'admin-edit.php'.$albumlink.'&page=edit&tab=albuminfo'),$zenphoto_tabs['edit']['subtabs']);
?>
<h1><?php echo gettext("Edit Album:");?> <em><?php if($album->getParent()) { printAlbumBreadcrumbAdmin($album); } echo removeParentAlbumNames($album); ?></em></h1>
<p>
<?php printAlbumEditLinks($albumdir, "&laquo; ".gettext("Back"), gettext("Back to the list of albums (go up one level)"));?>
 | <?php if (!$album->isDynamic() && $album->getNumImages() > 1) {
   printSortLink($album, gettext("Sort Album"), gettext("Sort Album"));
   echo ' | '; }?>
<?php printViewLink($album, gettext("View Album"), gettext("View Album")); ?>
</p>

<?php
$subtab = printSubtabs('edit', 'mass_edit');
?>
		<!-- Images List -->
		<div id="tab_imageinfo" class="tabbox">
		<?php
		if ($allimagecount) {
		?>
		<form name="albumedit2"	action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>"	method="post" AUTOCOMPLETE=OFF>
			<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
			<input type="hidden" name="totalimages" value="<?php echo $allimagecount; ?>" />
		
		<table class="bordered">
			<tr>
				<th>
				</th>
			</tr>
			<tr>
				<td colspan="4">
					<p class="buttons">
						<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
						<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
				</td>
			</tr>
			<?php
			$bglevels = array('#fff','#f8f8f8','#efefef','#e8e8e8','#dfdfdf','#d8d8d8','#cfcfcf','#c8c8c8');
		
			$currentimage = 0;
			foreach ($images as $filename) {
				$image = newImage($album, $filename);
				?>
		
			<tr <?php echo ($currentimage % 2 == 0) ?  "class=\"alt\"" : ""; ?>>
				<td colspan="4">
				<input type="hidden" name="<?php echo $currentimage; ?>-filename"	value="<?php echo $image->filename; ?>" />
				<table border="0" class="formlayout" id="image-<?php echo $currentimage; ?>">
					<tr>
						<td valign="top" width="150" rowspan="14">
						
						<img
							id="thumb-<?php echo $currentimage; ?>"
							src="<?php echo $image->getThumb(); ?>"
							alt="<?php printf(gettext('crop %s'), $image->filename); ?>"
							title="<?php printf(gettext('crop %s'), $image->filename); ?>"
							/>
						<p><strong><?php echo $image->filename; ?></strong></p>					
						<p>
							<label>
								<input type="radio" id="<?php echo $currentimage; ?>-thumb" name="thumb" value="<?php echo $currentimage ?>" />
								<?php echo ' '.gettext("Select as album thumbnail."); ?>
							</label>
						</p>
						</td>
						<td align="left" valign="top" width="100"><?php echo gettext("Title:"); ?></td>
						<td><?php print_language_string_list($image->get('title'), $currentimage.'-title', false); ?>
						</td>
						<td style="padding-left: 1em; text-align: left;" rowspan="14" valign="top">
							<h2 class="h2_bordered_edit"><?php echo gettext("General"); ?></h2>
	     				<div class="box-edit">
								<label>
									<span style="white-space:nowrap">
										<input type="checkbox" id="<?php echo $currentimage; ?>-allowcomments" name="<?php echo $currentimage; ?>-allowcomments" value="1"
											<?php if ($image->getCommentsAllowed()) { echo "checked=\"checked\""; } ?> />
										<?php echo gettext("Allow Comments"); ?>
									</span>
								</label>
								&nbsp;&nbsp;
								<label>
									<span style="white-space:nowrap">
										<input type="checkbox" id="<?php echo $currentimage; ?>-Visible" name="<?php echo $currentimage; ?>-Visible" value="1"
											<?php if ($image->getShow()) { echo "checked=\"checked\""; } ?> />
										<?php echo gettext("Visible"); ?>
									</span>
								</label>
								<?php
								$hc = $image->get('hitcounter');
								?>
								<label>
									<span style="white-space:nowrap">
										<input name="<?php echo $currentimage; ?>-resethitcounter" type="checkbox" id="<?php echo $currentimage; ?>-resethitcounter" value="1" />
										<?php printf(gettext('Reset hitcounter (Hits: %1$s)'),$hc); ?> 
									</span> 
								</label>
								<label>
									<span style="white-space:nowrap">
									<?php
										$tv = $image->get('total_value');
										$tc = $image->get('total_votes');
										if ($tc > 0) {
											$hc = $tv/$tc;
										} else {
											$hc = 0;
										}
										?>
										<input type="checkbox" id="<?php echo $currentimage; ?>-reset_rating" name="<?php echo $currentimage; ?>-reset_rating" value=1>
										<?php printf(gettext('Reset rating (Rating: %1$s)'), $hc); ?>
									</span>
								</label>
							</div>
						</td>
					</tr>
					<tr>
						<td align="left" valign="top"><?php echo gettext("Description:"); ?></td>
						<td><?php print_language_string_list($image->get('desc'), $currentimage.'-desc', true, NULL, 'texteditor'); ?>
						</td>
					</tr>
				</table>
			<?php
			$currentimage++;
			if ($currentimage % 10 == 0) {
				?>							
				<tr>
					<td colspan="4">
						<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
				 		</p>
					</td>
				</tr>
				<?php
			}
		}
		if ($currentimage % 10 != 0) {
			?>
			<tr>
				<td colspan="4">
					<p class="buttons">
						<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
						<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
			 		</p>
				</td>
			</tr>
			<?php
		}
		?>
		</table>
		
		</form>
		
		<?php
			}
		?>
		</div>
<?php
?>
</div>

<?php printAdminFooter(); ?>
<?php // to fool the validator
echo "\n</html>";

?>