<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
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
			$filename = sanitize($_POST["$i-filename"]);

			// The file might no longer exist
			$image = newImage($album, $filename);
			if ($image->exists) {
				if (isset($_POST[$i.'-MoveCopyRename'])) {
					$movecopyrename_action = sanitize($_POST[$i.'-MoveCopyRename'],3);
				} else {
					$movecopyrename_action = '';
				}
				if ($movecopyrename_action == 'delete') {
					$image->deleteImage(true);
				} else {
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

					if (isset($_POST[$i.'-oldrotation'])) {
						$oldrotation = sanitize_numeric($_POST[$i.'-oldrotation']);
					} else {
						$oldrotation = 0;
					}
					if (isset($_POST[$i.'-rotation'])) {
						$rotation = sanitize_numeric($_POST[$i.'-rotation']);
					} else {
						$rotation = 0;
					}
					if ($rotation != $oldrotation) {
						$image->set('EXIFOrientation', $rotation);
						$image->updateDimensions();
						$album = $image->getAlbum();
						$gallery->clearCache(SERVERCACHE . '/' . $album->name);
					}
					$image->save();
					// Process move/copy/rename
					if ($movecopyrename_action == 'move') {
						$dest = sanitize_path($_POST[$i.'-albumselect'], 3);
						if ($dest && $dest != $folder) {
							if ($e = $image->moveImage($dest)) {
								$notify = "&mcrerr=".$e;
							}
						} else {
							// Cannot move image to same album.
							$notify = "&mcrerr=2";
						}
					} else if ($movecopyrename_action == 'copy') {
						$dest = sanitize_path($_POST[$i.'-albumselect'],2);
						if ($dest && $dest != $folder) {
							if($e = $image->copyImage($dest)) {
								$notify = "&mcrerr=".$e;
							}
						} else {
							// Cannot copy image to existing album.
							// Or, copy with rename?
							$notify = "&mcrerr=2";
						}
					} else if ($movecopyrename_action == 'rename') {
						$renameto = sanitize_path($_POST[$i.'-renameto'],3);
						if ($e = $image->renameImage($renameto)) {
							$notify = "&mcrerr=".$e;
						}
					}
				}
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
setAlbumSubtabs($album);
?>
<h1><?php echo gettext("Edit Album:");?> <em><?php if($album->getParent()) { printAlbumBreadcrumbAdmin($album); } echo removeParentAlbumNames($album); ?></em></h1>

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
				<td colspan="4">
					<p class="buttons">
						<?php
						$parent = dirname($album->name);
						if ($parent == '/' || $parent == '.' || empty($parent)) {
							$parent = '';
						} else {
							$parent = '&album='.$parent.'&tab=subalbuminfo';
						}
						?>
						<a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>" title="<?php echo gettext('Back to the album list'); ?>" ><img	src="../../images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
						<button type="submit" title="<?php echo gettext("Save"); ?>"><img	src="../../images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
						<button type="reset" title="<?php echo gettext("Reset"); ?>"><img	src="../../images/fail.png" alt="" /> <strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
				</td>
			</tr>
			<?php
			$bglevels = array('#fff','#f8f8f8','#efefef','#e8e8e8','#dfdfdf','#d8d8d8','#cfcfcf','#c8c8c8');
		
			$currentimage = 0;
			$classalt = '';
			if (getOption('auto_rotate')) {
				$disablerotate = '';
			} else {
				$disablerotate = ' DISABLED';
			}
			foreach ($images as $filename) {
				$image = newImage($album, $filename);
				if (empty($classalt)) {
					$classalt = 'class="alt"';
				} else { 
					$classalt = '';
				}
				?>
		
			<tr <?php echo $classalt; ?>>
				<td colspan="4">
				<input type="hidden" name="<?php echo $currentimage; ?>-filename"	value="<?php echo $image->filename; ?>" />
				<table border="0" class="formlayout" id="image-<?php echo $currentimage; ?>">
					<tr>
						<td valign="top" width="150" rowspan="14">
						
						<a href="<?php echo WEBPATH.'/'.ZENFOLDER; ?>/admin-edit.php?album=<?php echo urlencode($album->name); ?>
										&amp;image=<?php echo urlencode($image->filename); ?>&amp;tab=imageinfo#IT"
										title="<?php printf(gettext('full edit %s'), $image->filename); ?>"  >
						<img
								id="thumb-<?php echo $currentimage; ?>"
								src="<?php echo $image->getThumb(); ?>"
								alt="<?php printf(gettext('full edit %s'), $image->filename); ?>"
								title="<?php printf(gettext('full edit %s'), $image->filename); ?>"
								/>
							</a>
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
								<hr />
								<!-- Move/Copy/Rename this image -->
								<label style="padding-right: .5em">
									<span style="white-space:nowrap">
										<input type="radio" id="<?php echo $currentimage; ?>-rename" name="<?php echo $currentimage; ?>-MoveCopyRename" value="rename"
											onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'rename');" style="display:inline" /> <?php echo gettext("Rename File");?>
									</span>
								</label>
								<label style="padding-right: .5em">
									<span style="white-space:nowrap">
										<input type="radio" id="<?php echo $currentimage; ?>-Delete" name="<?php echo $currentimage; ?>-MoveCopyRename" value="delete"
											onclick="image_deleteconfirm(this, '<?php echo $currentimage; ?>','<?php echo gettext("Are you sure you want to select this image for deletion?"); ?>')" style="display:inline" /> <?php echo gettext("Delete image") ?>
									</span>
								</label>
								<div id="<?php echo $currentimage; ?>-renamediv" style="padding-top: .5em; padding-left: .5em; display: none;"><?php echo gettext("to"); ?>:
								<input name="<?php echo $currentimage; ?>-renameto" type="text" value="<?php echo $image->filename;?>" /><br />
								<br /><p class="buttons"><a	href="javascript:toggleMoveCopyRename('<?php echo $currentimage; ?>', '');"><img src="../../images/reset.png" alt="" /><?php echo gettext("Cancel");?></a>
								</p>
								</div>
								<div id="deletemsg<?php echo $currentimage; ?>"	style="padding-top: .5em; padding-left: .5em; color: red; display: none">
								<?php echo gettext('Image will be deleted when changes are saved.'); ?>
								<p class="buttons"><a	href="javascript:toggleMoveCopyRename('<?php echo $currentimage; ?>', '');"><img src="../../images/reset.png" alt="" /><?php echo gettext("Cancel");?></a>
								</p>
								</div>
								<span style="line-height: 0em;"><br clear=all /></span>						
								<?php
								if (isImagePhoto($image)) {
									?>
									<hr />
									<?php echo gettext("Rotation:"); ?>
									<br />
									<?php
									$splits = preg_split('/!([(0-9)])/', $image->get('EXIFOrientation'));
									$rotation = $splits[0];
									if (!in_array($rotation,array(3, 6, 8))) $rotation = 0;
									?>
									<input type="hidden" name="<?php echo $currentimage; ?>-oldrotation" value="<?php echo $rotation; ?>" />
									<label style="padding-right: .5em">
										<span style="white-space:nowrap">
											<input type="radio" id="<?php echo $currentimage; ?>-rotation"	name="<?php echo $currentimage; ?>-rotation" value="0" <?php checked(0, $rotation); echo $disablerotate ?> />
											<?php echo gettext('none'); ?>
										</span>
									</label>
									<label style="padding-right: .5em">
										<span style="white-space:nowrap">
											<input type="radio" id="<?php echo $currentimage; ?>-rotation"	name="<?php echo $currentimage; ?>-rotation" value="8" <?php checked(8, $rotation); echo $disablerotate ?> />
											<?php echo gettext('90 degrees'); ?>
										</span>
									</label>
									<label style="padding-right: .5em">
										<span style="white-space:nowrap">
											<input type="radio" id="<?php echo $currentimage; ?>-rotation"	name="<?php echo $currentimage; ?>-rotation" value="3" <?php checked(3, $rotation); echo $disablerotate ?> />
											<?php echo gettext('180 degrees'); ?>
										</span>
									</label>
									<label style="padding-right: .5em">
										<span style="white-space:nowrap">
											<input type="radio" id="<?php echo $currentimage; ?>-rotation"	name="<?php echo $currentimage; ?>-rotation" value="6" <?php checked(6, $rotation); echo $disablerotate ?> />
											<?php echo gettext('270 degrees'); ?>
										</span>
									</label>
									<span style="line-height: 0em;"><br clear=all /></span>
									<?php
								}
								?>
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
				if (empty($classalt)) {
					$classalt = 'class="alt"';
				} else { 
					$classalt = '';
				}
				?>							
				<tr <?php echo $classalt; ?>>
					<td colspan="4">
						<p class="buttons">
							<?php
							$parent = dirname($album->name);
							if ($parent == '/' || $parent == '.' || empty($parent)) {
								$parent = '';
							} else {
								$parent = '&album='.$parent.'&tab=subalbuminfo';
							}
							?>
							<a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>" title="<?php echo gettext('Back to the album list'); ?>" ><img	src="../../images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img	src="../../images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img	src="../../images/fail.png" alt="" /> <strong><?php echo gettext("Reset"); ?></strong></button>
						</p>
					</td>
				</tr>
				<?php
			}
		}
		if ($currentimage % 10 != 0) {
			if (empty($classalt)) {
				$classalt = 'class="alt"';
			} else { 
				$classalt = '';
			}
			?>							
			<tr <?php echo $classalt; ?>>
				<td colspan="4">
					<p class="buttons">
						<?php
						$parent = dirname($album->name);
						if ($parent == '/' || $parent == '.' || empty($parent)) {
							$parent = '';
						} else {
							$parent = '&album='.$parent.'&tab=subalbuminfo';
						}
						?>
						<a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit'.$parent; ?>" title="<?php echo gettext('Back to the album list'); ?>" ><img	src="../../images/arrow_left_blue_round.png" alt="" /><strong><?php echo gettext("Back"); ?></strong></a>
						<button type="submit" title="<?php echo gettext("Save"); ?>"><img	src="../../images/pass.png" alt="" /> <strong><?php echo gettext("Save"); ?></strong></button>
						<button type="reset" title="<?php echo gettext("Reset"); ?>"><img	src="../../images/fail.png" alt="" /> <strong><?php echo gettext("Reset"); ?></strong></button>
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