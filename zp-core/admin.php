<?php
/**
 * admin.php is the main script for administrative functions.
 * @package admin
 */

// force UTF-8 Ø

/* Don't put anything before this line! */
define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-sortable.php');

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (zp_loggedin()) { /* Display the admin pages. Do action handling first. */
	if (($_zp_null_account = ($_zp_loggedin == ADMIN_RIGHTS)) || ($_zp_loggedin == NO_RIGHTS)) { // user/password set required.
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-options.php");
	}

	$tagsort = getTagOrder();
	//check for security incursions
	if (isset($_GET['album'])) {
		if (!($_zp_loggedin & ADMIN_RIGHTS)) {
			if (!isMyAlbum(sanitize_path($_GET['album']), $_zp_loggedin)) {
				unset($_GET['album']);
				unset($_GET['page']);
				$page = '';
			}
		}
	}

	$mcr_errors = array();


	$gallery = new Gallery();
	$gallery->garbageCollect();
	if (isset($_GET['action'])) {
		$action = $_GET['action'];
		/** reorder the tag list ******************************************************/
		/******************************************************************************/
		if ($action == 'sorttags') {
			if (isset($_GET['subpage'])) {
				$pg = '&subpage='.$_GET['subpage'];
				$tab = '&tab=imageinfo';
			} else {
				$pg = '';
				$tab = '';
			}
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit&album='.$_GET['album'].$pg.'&tagsort='.$tagsort.$tab);
		}

		/** clear the cache ***********************************************************/
		/******************************************************************************/
		if ($action == "clear_cache") {
			if (isset($_POST['album'])) {
				$gallery->clearCache(SERVERCACHE . '/' . sanitize_path($_POST['album']));
				header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit&cleared&album='.$_POST['album']);
				exit();
			} else {
				$gallery->clearCache();
			}
		}

		/** Publish album  ************************************************************/
		/******************************************************************************/
		if ($action == "publish") {
			$folder = sanitize_path($_GET['album']);
			$album = new Album($gallery, $folder);
			$album->setShow($_GET['value']);
			$album->save();
			$return = urlencode(dirname($folder));
			if (!empty($return)) {
				if ($return == '.' || $return == '/') {
					$return = '';
				} else {
					$return = '&album='.$return.'&tab=subalbuminfo';
				}
			}
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit'.$return);
			exit();

			/** Reset hitcounters ***********************************************************/
			/********************************************************************************/
		} else if ($action == "reset_hitcounters") {
			if (isset($_REQUEST['albumid'])) {
				$id = sanitize_numeric($_REQUEST['albumid']);
				$where = ' WHERE `id`='.$id;
				$imgwhere = ' WHERE `albumid`='.$id;
				$return = '?counters_reset';
				if (isset($_REQUEST['album'])) {
					if (isset($_GET['album'])) {
						$return = urlencode(dirname(sanitize_path($_REQUEST['album'])));
					} else {
						$return = urlencode(sanitize_path($_REQUEST['album']));	
					}
					if (empty($return) || $return == '.' || $return == '/') {
						$return = '?page=edit&counters_reset';
					} else {
						$return = '?page=edit&album='.$return.'&counters_reset&tab=subalbuminfo';
					}
				}
			} else {
				$where = '';
				$imgwhere = '';
				$return = '';
			}
			query("UPDATE " . prefix('albums') . " SET `hitcounter`= 0" . $where);
			query("UPDATE " . prefix('images') . " SET `hitcounter`= 0" . $imgwhere);
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php' . $return);
			exit();
			
			//** DELETEIMAGE **************************************************************/
			/******************************************************************************/
		} else if ($action == 'deleteimage') {
			$albumname = sanitize_path($_REQUEST['album']);
			$imagename = sanitize_path($_REQUEST['image']);
			$album = new Album($gallery, $albumname);
			$image = newImage($album, $imagename);
			if ($image->deleteImage(true)) {
				$nd = 1;
			} else {
				$nd = 2;
			}

			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit&album='.pathurlencode($albumname).'&ndeleted='.$nd);
			exit();
			
			/** SAVE **********************************************************************/
			/******************************************************************************/
		} else if ($action == "save") {
			$returntab = '';

			/** SAVE A SINGLE ALBUM *******************************************************/
			if (isset($_POST['album'])) {

				$folder = sanitize_path($_POST['album']);
				$album = new Album($gallery, $folder);
				$notify = '';
				if (isset($_POST['savealbuminfo'])) {
					$notify = processAlbumEdit(0, $album);
					$returntab = '&tagsort='.$tagsort.'&tab=albuminfo';
				}

				if (isset($_POST['totalimages'])) {
					$returntab = '&tagsort='.$tagsort.'&tab=imageinfo';
					if (isset($_POST['thumb'])) {
						$thumbnail = sanitize_numeric($_POST['thumb']);
					} else {
						$thumbnail = -1;
					}
					$oldsort = sanitize($_POST['oldalbumimagesort'], 3);
					if (getOption('albumimagedirection')) $oldsort = $oldsort.'_desc';
					$newsort = sanitize($_POST['albumimagesort'],3);
					if ($oldsort == $newsort) {
						for ($i = 0; $i < $_POST['totalimages']; $i++) {
							$filename = strip($_POST["$i-filename"]);

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
									$image->setLocation(process_language_string_save("$i-location", 3));
									$image->setCity(process_language_string_save("$i-city", 3));
									$image->setState(process_language_string_save("$i-state", 3));
									$image->setCountry(process_language_string_save("$i-country", 3));
									$image->setCredit(process_language_string_save("$i-credit", 1));
									$image->setCopyright(process_language_string_save("$i-copyright", 1));

									$tagsprefix = 'tags_'.$i.'-';
									$tags = array();
									for ($j=0; $j<4; $j++) {
										if (isset($_POST[$tagsprefix.'new_tag_value_'.$j])) {
											$tag = trim(strip($_POST[$tagsprefix.'new_tag_value_'.$j]));
											unset($_POST[$tagsprefix.'new_tag_value_'.$j]);
											if (!empty($tag)) {
												$tags[] = $tag;
											}
										}
									}
									$l = strlen($tagsprefix);
									foreach ($_POST as $key => $value) {
										$key = postIndexDecode($key);
										if (substr($key, 0, $l) == $tagsprefix) {
											if ($value) {
												$tags[] = substr($key, $l);
											}
										}
									}
									$tags = array_unique($tags);
									$image->setTags(sanitize($tags, 3));


									$image->setDateTime(strip($_POST["$i-date"]));
									$image->setShow(isset($_POST["$i-Visible"]));
									$image->setCommentsAllowed(strip($_POST["$i-allowcomments"]));
									if (isset($_POST["$i-reset_hitcounter"])) {
										$image->set('hitcounter', 0);
									}
									$image->setCustomData(process_language_string_save("$i-custom_data", 1));
									$image->save();

									// Process move/copy/rename
									if ($movecopyrename_action == 'move') {
										$dest = sanitize_path($_POST[$i.'-albumselect'], 3);
										if ($dest && $dest != $folder) {
											if (!$image->moveImage($dest)) {
												$notify = "&mcrerr=1";
											}
										} else {
											// Cannot move image to same album.
										}
									} else if ($movecopyrename_action == 'copy') {
										$dest = sanitize_path($_POST[$i.'-albumselect'],2);
										if ($dest && $dest != $folder) {
											if(!$image->copyImage($dest)) {
												$notify = "&mcrerr=1";
											}
										} else {
											// Cannot copy image to existing album.
											// Or, copy with rename?
										}
									} else if ($movecopyrename_action == 'rename') {
										$renameto = sanitize_path($_POST[$i.'-renameto'],3);
										$image->renameImage($renameto);
									}
								}
							}
						}
					} else {
						if (strpos($newsort, '_desc')) {
							setOption('albumimagesort', substr($newsort, 0, -5));
							setOption('albumimagedirection', 'DESC');
						} else {
							setOption('albumimagesort', $newsort);
							setOption('albumimagedirection', '');
						}
						$notify = '&';
					}
				}

				/** SAVE MULTIPLE ALBUMS ******************************************************/
			} else if ($_POST['totalalbums']) {
				for ($i = 1; $i <= $_POST['totalalbums']; $i++) {
					$folder = sanitize_path($_POST["$i-folder"]);
					$album = new Album($gallery, $folder);
					$rslt = processAlbumEdit($i, $album);
					if (!empty($rslt)) { $notify = $rslt; }
				}
				$notify = '';
			}
			// Redirect to the same album we saved.
			$qs_albumsuffix = "&massedit";
			if (isset($_GET['album'])) {
				$folder = sanitize_path($_GET['album']);
				$qs_albumsuffix = '&album='.urlencode($folder);
			}
			if (isset($_POST['subpage'])) {
				$pg = '&subpage='.$_POST['subpage'];
			} else {
				$pg = '';
			}
			if ($notify == '&') {
				$notify = '';
			} else {
				$notify .= '&saved';
			}
			header('Location: '.FULLWEBPATH.'/'.ZENFOLDER.'/admin.php?page=edit'.$qs_albumsuffix.$notify.$pg.$returntab);
			exit();

			/** DELETION ******************************************************************/
			/*****************************************************************************/
		} else if ($action == "deletealbum") {
			if ($_GET['album']) {
				$folder = sanitize_path($_GET['album']);
				$album = new Album($gallery, $folder);
				if ($album->deleteAlbum()) {
					$nd = 3;
				} else {
					$nd = 4;
				}
				$albumdir = dirname($folder);
				if ($albumdir != '/' && $albumdir != '.') {
					$albumdir = "&album=" . urlencode($albumdir);
				} else {
					$albumdir = '';
				}
			}
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit" . $albumdir . "&ndeleted=".$nd);
			exit();
		}
	}


	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else if (empty($page)) {
		$page = "home";
	}

	switch ($page) {
		case 'comments':
			if (!($_zp_loggedin & (ADMIN_RIGHTS | COMMENT_RIGHTS))) $page = '';
			break;
		case 'upload':
			if (!($_zp_loggedin & (ADMIN_RIGHTS | UPLOAD_RIGHTS))) $page = '';
			break;
		case 'edit':
			if (!($_zp_loggedin & (ADMIN_RIGHTS | EDIT_RIGHTS))) $page = '';
			break;
		case 'themes':
			if (!($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS))) $page = '';
			break;
		case 'plugins':
			if (!($_zp_loggedin & (ADMIN_RIGHTS | ADMIN_RIGHTS))) $page = '';
			break;
		case 'home':
			if (!($_zp_loggedin & (ADMIN_RIGHTS | MAIN_RIGHTS))) {
				$page='options';
			}
			break;
	}


	/* TODO: 	This should not be necessary if all the references really got changed on the restructure.
						Only the redirect to the options page should be required--for no-rights admin users as
						they can only view/change their credentials.
  */
	$q = '?page='.$page;
	foreach ($_GET as $opt=>$value) {
		if ($opt != 'page') {
			$q .= '&'.$opt.'='.$value;
		}
	}
	switch ($page) {
		case 'editcomment':
		case 'comments':
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-comments.php".$q);
			exit();
		case 'upload':
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-upload.php".$q);
			exit();
		case 'themes':
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-themes.php".$q);
			exit();
		case 'plugins':
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-plugins.php".$q);
			exit();
		case 'options':
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-options.php".$q);
			exit();
		default:
	}
}

/* NO Admin-only content between this and the next check. */

/************************************************************************************/
/** End Action Handling *************************************************************/
/************************************************************************************/

if (issetPage('edit')) {
	zenSortablesPostHandler('albumOrder', 'albumList', 'albums');
}

// Print our header
printAdminHeader();

if (issetPage('edit')) {
	zenSortablesHeader('albumList','albumOrder','div', "handle:'handle'");
}
echo "\n</head>";
?>

<body>

<?php
// If they are not logged in, display the login form and exit

if (!zp_loggedin()) {
	printLoginForm();
	echo "\n</body>";
	echo "\n</html>";
	exit();

} else { /* Admin-only content safe from here on. */
	printLogoAndLinks();
	?>
<div id="main">
<?php printTabs($page); ?>
<div id="content">
<?php

/** EDIT ****************************************************************************/
/************************************************************************************/

if ($page == "edit") {


if (isset($_GET['album']) && !isset($_GET['massedit'])) {
	/** SINGLE ALBUM ********************************************************************/	
	define('IMAGES_PER_PAGE', 10);
	// one time generation of this list.
	$mcr_albumlist = array();
	genAlbumUploadList($mcr_albumlist);
	
	$oldalbumimagesort = getOption('albumimagesort');
	$direction = getOption('albumimagedirection');
	$folder = sanitize_path($_GET['album']); 
	$album = new Album($gallery, $folder);
	if ($album->isDynamic()) {
		$subalbums = array();
		$allimages = array();
	} else {
		$subalbums = $album->getSubAlbums();
		$allimages = $album->getImages(0, 0, $oldalbumimagesort, $direction);
	}
	$allimagecount = count($allimages);
	if (isset($_GET['subpage'])) {
		$pagenum = max(intval($_GET['subpage']),1);
		if (($pagenum-1) * IMAGES_PER_PAGE >= $allimagecount) $pagenum --;
	} else {
		$pagenum = 1;
	}
	$images = array_slice($allimages, ($pagenum-1)*IMAGES_PER_PAGE, IMAGES_PER_PAGE);

	$totalimages = count($images);
	$albumdir = "";

	$albumdir = dirname($folder);
	if (($albumdir == '/') || ($albumdir == '.')) {
		$albumdir = '';
	} else {
		$albumdir = "&album=" . urlencode($albumdir);
	}
	if (isset($_GET['subalbumsaved'])) {
		$album->setSubalbumSortType('Manual');
		$album->setSortDirection('album', 0);
		$album->save();
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Subalbum order saved")."</h2>";
		echo '</div>';
	}
	if (isset($_GET['counters_reset'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Hitcounters have been reset")."</h2>";
		echo '</div>';
	}
	
	?>
<h1><?php echo gettext("Edit Album:");?> <em><?php echo $album->name; ?></em></h1>
<p><?php printAdminLinks('edit' . $albumdir, "&laquo; ".gettext("Back"), gettext("Back to the list of albums (go up one level)"));?>
 | <?php if (!$album->isDynamic() && $album->getNumImages() > 1) {
   printSortLink($album, gettext("Sort Album"), gettext("Sort Album"));
   echo ' | '; }?>
<?php printViewLink($album, gettext("View Album"), gettext("View Album")); ?>
</p>


	<?php displayDeleted(); /* Display a message if needed. Fade out and hide after 2 seconds. */ ?>
	<?php
	if (isset($_GET['saved'])) {
		if (isset($_GET['mismatch'])) {
			?>
			<div class="errorbox" id="fade-message">
			<?php if ($_GET['mismatch'] == 'user') {
				echo '<h2>'.gettext("You must supply a  password.").'</h2>';
			} else {
				echo '<h2>'.gettext("Your passwords did not match.").'</h2>';
			}
			?>

			</div>
		<?php
		} else {
		?>
			<div class="messagebox" id="fade-message">
			<h2><?php echo gettext("Changes saved"); ?></h2>
			</div>
		<?php
		}
		if (isset($_GET['mcrerr'])) {
			echo '<div class="errorbox" id="fade-message2">';
			echo  "<h2>".gettext("There was an error with a move, copy, or rename operation.")."</h2>";
			echo '</div>';
		}
		?>
	<?php
	}
	if (isset($_GET['uploaded'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Images uploaded")."</h2>";
		echo '</div>';
	}
	if (isset($_GET['cleared'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Album cache purged")."</h2>";
		echo '</div>';
	}
	$albumlink = '?page=edit&album='.urlencode($album->name);
	$tabs = array(gettext('Album')=>'admin.php'.$albumlink.'&tab=albuminfo');
	if (count($subalbums) > 0) $tabs[gettext('Subalbums')] = 'admin.php'.$albumlink.'&tab=subalbuminfo';
	if ($allimagecount) $tabs[gettext('Images')] = 'admin.php'.$albumlink.'&tab=imageinfo';
	$subtab = printSubtabs($tabs);
	?>
	<?php
	if ($subtab == 'albuminfo') {
	?>
		<!-- Album info box -->
		<div id="tab_albuminfo" class="box" style="padding: 15px;">
		<div class="innerbox" style="padding: 15px;">
		<form name="albumedit1" AUTOCOMPLETE=OFF
			action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>"	method="post">
			<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
			<input type="hidden"	name="savealbuminfo" value="1" />
			<?php printAlbumEditForm(0, $album); ?>
		</form>
		<br />
		<?php printAlbumButtons($album) ?> 
		</div>
		</div>
		<?php
		} else if ($subtab == 'subalbuminfo' && !$album->isDynamic())  {
		?>
		<!-- Subalbum list goes here -->
		<?php
		if (count($subalbums) > 0) {
		?>
		<div id="tab_subalbuminfo" class="box" style="padding: 15px;">
			<table class="bordered" width="100%">
			<input type="hidden" name="subalbumsortby" value="Manual" />
			<tr>
				<td colspan="8">
				<?php
					$sorttype = $album->getSubalbumSortType();
					if ($sorttype != 'Manual') {
						if ($album->getSortDirection('album')) {
							$dir = gettext(' descending');
						} else {
							$dir = '';
						}
						$sortNames = array_flip($sortby);
						$sorttype = $sortNames[$sorttype];
					} else {
						$dir = '';
					}
					printf(gettext('Current sort: %1$s%2$s. '), $sorttype, $dir);
					echo gettext('Drag the albums into the order you wish them displayed.').' ';
					echo gettext("Select an album to edit its description and data, or");
				?>
				<a href="?page=edit&album=<?php echo urlencode($album->name)?>&massedit"><?php echo gettext("mass-edit all album data"); ?></a>.</td>
			</tr>
			<tr>
				<td style="padding: 0px 0px;" colspan="8">
				<div id="albumList" class="albumList"><?php
				foreach ($subalbums as $folder) {
					$subalbum = new Album($album, $folder);
					printAlbumEditRow($subalbum);
				}
				?></div>
			</tr>
		</table>
				<ul class="iconlegend">
				<li><img src="images/lock.png" alt="Protected" /><?php echo gettext("Has Password"); ?></li>
				<li><img src="images/pass.png" alt="Published" /><img src="images/action.png" alt="Unpublished" /><?php echo gettext("Published/Unpublished"); ?></li>
				<li><img src="images/cache.png" alt="Cache the album" /><?php echo gettext("Cache	the album"); ?></li>
				<li><img src="images/warn.png" alt="Refresh image metadata" /><?php echo gettext("Refresh image metadata"); ?></li>
				<li><img src="images/reset.png" alt="Reset hitcounters" /><?php echo gettext("Reset	hitcounters"); ?></li>
				<li><img src="images/fail.png" alt="Delete" /><?php echo gettext("Delete"); ?></li>
				</ul>
			<?php
					zenSortablesSaveButton("?page=edit&album=" . urlencode($album->name) . "&subalbumsaved&tab=subalbuminfo", gettext("Save Order"));
					?>
		
		</div>
		<?php
		} ?>
<?php 
	} else if ($subtab == 'imageinfo') {
?>
		<!-- Images List -->
		<div id="tab_imageinfo" class="box" style="padding: 15px;">
		<?php
		if ($allimagecount) {
			?>
		<form name="albumedit2"	action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>"	method="post" AUTOCOMPLETE=OFF>
			<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
			<input type="hidden" name="totalimages" value="<?php echo $totalimages; ?>" />
			<input type="hidden" name="subpage" value="<?php echo $pagenum; ?>" />
			<input type="hidden" name="tagsort" value=<?php echo $tagsort ?> />
			<input type="hidden" name="oldalbumimagesort" value=<?php echo $oldalbumimagesort; ?> />
		
		<?php	$totalpages = ceil(($allimagecount / IMAGES_PER_PAGE));	?>
		<table class="bordered">
			<tr>
				<th><?php echo gettext("Click on the image to change the thumbnail cropping."); ?>	</th>
				<th>
					<a href="javascript:toggleExtraInfo('','image',true);"><?php echo gettext('expand all fields');?></a>
					| <a href="javascript:toggleExtraInfo('','image',false);"><?php echo gettext('collapse all fields');?></a>
				</th>
				<th align="right">
				<?php
				$sort = $sortby;
				foreach ($sort as $key=>$value) {
					$sort[sprintf(gettext('%s (descending)'),$key)] = $value.'_desc';
				}
				$sort[gettext('Manual')] = 'Manual';
				ksort($sort);
				if ($direction) $oldalbumimagesort = $oldalbumimagesort.'_desc';
				echo gettext("Display images by:");
					echo '<select id="albumimagesort" name="albumimagesort" onchange="this.form.submit()">';
					generateListFromArray(array($oldalbumimagesort), $sort);
					echo '</select>';
					?>
				</th>
			</tr>
			<?php
			if ($allimagecount != $totalimages) { // need pagination links
			?>
			<tr>
				<td colspan="4" class="bordered" id="imagenav"><?php adminPageNav($pagenum,$totalpages,'admin.php','?page=edit&amp;tagsort='.$tagsort.'&amp;album='.urlencode($album->name),'&amp;tab=imageinfo'); ?>
				</td>
			</tr>
			<?php
			}
		 ?>
			<tr>
				<td colspan="4">
				<input type="submit" value="<?php echo gettext('save changes'); ?>" />
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
				<table border="0" class="formlayout"
					id="image-<?php echo $currentimage; ?>">
					<tr>
						<td valign="top" width="150" rowspan="14">
						
						<a href="admin-thumbcrop.php?a=<?php echo urlencode($album->name); ?>&amp;i=<?php echo urlencode($image->filename); ?>&amp;subpage=<?php echo $pagenum; ?>&amp;tagsort=<?php echo $tagsort; ?>"
										title=<?php printf(gettext('crop %s'), $image->filename); ?>  >
						<img
							id="thumb-<?php echo $currentimage; ?>"
							src="<?php echo $image->getThumb(); ?>"
							alt="<?php printf(gettext('crop %s'), $image->filename); ?>"
							title="<?php printf(gettext('crop %s'), $image->filename); ?>"
							/>
						</a>
						<p><strong><?php echo $image->filename; ?></strong></p>
						<p><label for="<?php echo $currentimage; ?>-thumb"><input
							type="radio" id="<?php echo $currentimage; ?>-thumb" name="thumb"
							value="<?php echo $currentimage ?>" /> <?php echo ' '.gettext("Select as album thumbnail."); ?>
						</label></p>
						</td>
						<td align="right" valign="top" width="100"><?php echo gettext("Title:"); ?></td>
						<td><?php print_language_string_list($image->get('title'), $currentimage.'-title', false); ?>
						</td>
						<td style="padding-left: 1em; text-align: left;" valign="top"
							colspan="1"><label for="<?php echo $currentimage; ?>-allowcomments">
						<input type="checkbox"
							id="<?php echo $currentimage; ?>-allowcomments"
							name="<?php echo $currentimage; ?>-allowcomments" value="1"
							<?php if ($image->getCommentsAllowed()) { echo "checked=\"checked\""; } ?> />
						<?php echo gettext("Allow Comments"); ?></label> &nbsp; &nbsp; <label
							for="<?php echo $currentimage; ?>-Visible"> <input type="checkbox"
							id="<?php echo $currentimage; ?>-Visible"
							name="<?php echo $currentimage; ?>-Visible" value="1"
							<?php if ($image->getShow()) { echo "checked=\"checked\""; } ?> />
						<?php echo gettext("Visible"); ?></label></td>
					</tr>
		
					<tr>
						<td align="right" valign="top"><?php echo gettext("Description:"); ?></td>
						<td><?php print_language_string_list($image->get('desc'), $currentimage.'-desc', true, NULL, 'texteditor'); ?>
						</td>
						<td style="padding-left: 1em;">
						<p style="margin-top: 0; margin-bottom: 1em;"><?php
									$hc = $image->get('hitcounter');
									if (empty($hc)) { $hc = '0'; }
									printf( gettext("Hit counter: <strong>%u</strong>"),$hc)." <label for=\"$currentimage-reset_hitcounter\"><input type=\"checkbox\" id=\"$currentimage-reset_hitcounter\" name=\"$currentimage-reset_hitcounter\" value=1> ".gettext("Reset")."</label> ";
									$tv = $image->get('total_value');
									$tc = $image->get('total_votes');
									echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
									if ($tc > 0) {
										$hc = $tv/$tc;
										printf(gettext('Rating: <strong>%u</strong>'),$hc)." <label for=\"$currentimage-reset_rating\"><input type=\"checkbox\" id=\"$currentimage-reset_rating\" name=\"$currentimage-reset_rating\" value=1> ".gettext("Reset")."</label> ";
									} else {
										echo ' '.gettext("Rating: Unrated");
									}
									?></p>
		
						<!-- Move/Copy/Rename this image --> <label
							for="<?php echo $currentimage; ?>-move" style="padding-right: .5em">
						<input type="radio" id="<?php echo $currentimage; ?>-move"
							name="<?php echo $currentimage; ?>-MoveCopyRename" value="move"
							onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'movecopy');" />
						<?php echo gettext("Move");?> </label> <label
							for="<?php echo $currentimage; ?>-copy" style="padding-right: .5em">
						<input type="radio" id="<?php echo $currentimage; ?>-copy"
							name="<?php echo $currentimage; ?>-MoveCopyRename" value="copy"
							onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'movecopy');" />
						<?php echo gettext("Copy");?> </label> <label
							for="<?php echo $currentimage; ?>-rename"
							style="padding-right: .5em"> <input type="radio"
							id="<?php echo $currentimage; ?>-rename"
							name="<?php echo $currentimage; ?>-MoveCopyRename" value="rename"
							onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'rename');" />
						<?php echo gettext("Rename File");?> </label> <label
							for="<?php echo $currentimage; ?>-Delete"> <input type="radio"
							id="<?php echo $currentimage; ?>-Delete"
							name="<?php echo $currentimage; ?>-MoveCopyRename" value="delete"
							onclick="image_deleteconfirm(this, '<?php echo $currentimage; ?>','<?php echo gettext("Are you sure you want to select this image for deletion?"); ?>')" />
						<?php echo ' '.gettext("Delete image.") ?> </label>
						<div id="<?php echo $currentimage; ?>-movecopydiv"
							style="padding-top: .5em; padding-left: .5em; display: none;"><?php echo gettext("to"); ?>:
						<select id="<?php echo $currentimage; ?>-albumselectmenu"
							name="<?php echo $currentimage; ?>-albumselect" onChange="">
							<?php
											foreach ($mcr_albumlist as $fullfolder => $albumtitle) {
												$singlefolder = $fullfolder;
												$saprefix = "";
												$salevel = 0;
												$selected = "";
												if ($album->name == $fullfolder) {
													$selected = " SELECTED=\"true\" ";
												}
												// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
												while (strstr($singlefolder, '/') !== false) {
													$singlefolder = substr(strstr($singlefolder, '/'), 1);
													$saprefix = "&nbsp; &nbsp;&nbsp;" . $saprefix;
													$salevel++;
												}
												echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: '.$bglevels[$salevel].';"' : '')
												. "$selected>". $saprefix . $singlefolder ."</option>\n";
											}
										?>
						</select>
						<p style="text-align: right;"><a
							href="javascript:toggleMoveCopyRename('<?php echo $currentimage; ?>', '');"><?php echo gettext("Cancel");?></a>
						</p>
						</div>
						<div id="<?php echo $currentimage; ?>-renamediv"
							style="padding-top: .5em; padding-left: .5em; display: none;"><?php echo gettext("to"); ?>:
						<input name="<?php echo $currentimage; ?>-renameto" type="text"
							size="35" value="<?php echo $image->filename;?>" /><br />
						<p style="text-align: right; padding: .25em 0px;"><a
							href="javascript:toggleMoveCopyRename('<?php echo $currentimage; ?>', '');"><?php echo gettext("Cancel");?></a>
						</p>
						</div>
						<div id="deletemsg<?php echo $currentimage; ?>"
							style="padding-top: .5em; padding-left: .5em; color: red; display: none">
						<?php echo gettext('Image will be deleted when changes are saved.'); ?>
						<p style="text-align: right; padding: .25em 0px;"><a
							href="javascript:toggleMoveCopyRename('<?php echo $currentimage; ?>', '');"><?php echo gettext("Cancel");?></a>
						</p>
						</div>
						</td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("Location:"); ?></td>
						<td><?php print_language_string_list($image->get('location'), $currentimage.'-location', false); ?>
						</td>
						<td rowspan="10" style="padding-left: 1em;">
						<p style="padding: 0px 0px .5em; margin: 0px;">Tags</p>
						<?php
									tagSelector($image, 'tags_'.$currentimage.'-', false, $tagsort);
								?></td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("City:"); ?></td>
						<td><?php print_language_string_list($image->get('city'), $currentimage.'-city', false); ?>
						</td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("State:"); ?></td>
						<td><?php print_language_string_list($image->get('state'), $currentimage.'-state', false); ?>
						</td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("Country:"); ?></td>
						<td><?php print_language_string_list($image->get('country'), $currentimage.'-country', false); ?>
						</td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("Credit:"); ?></td>
						<td><?php print_language_string_list($image->get('credit'), $currentimage.'-credit', false); ?>
						</td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("Copyright:"); ?></td>
						<td><?php print_language_string_list($image->get('copyright'), $currentimage.'-copyright', false); ?>
						</td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("Date:"); ?></td>
						<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $currentimage; ?>-date"
							value="<?php $d=$image->getDateTime(); if ($d!='0000-00-00 00:00:00') { echo $d; } ?>" /></td>
					</tr>
		
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("Custom data:"); ?></td>
						<td><?php print_language_string_list($image->get('custom_data'), $currentimage.'-custom_data', true); ?>
						</td>
					</tr>
					
					<tr class="imageextrainfo" style="display: none;">
						<td align="right" valign="top"><?php echo gettext("EXIF inforation:"); ?></td>
						<td>
						<?php
							$data = '';
							$exif = $image->getExifData();
							if (false !== $exif) {
								foreach ($exif as $field => $value) {
									if (!empty($value)) {
										$display = $_zp_exifvars[$field][3];
										if ($display) {
											$label = $_zp_exifvars[$field][2];
											$data .= "<tr><td align=\"right\">$label: </td> <td>$value</td></tr>\n";
										}
									}
								}
							}
							if (empty($data)) {
								echo gettext('None');
							} else {
								echo '<table>'.$data.'</table>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td colspan="4"><span class="imageextrashow"><a
							href="javascript:toggleExtraInfo('<?php echo $currentimage;?>', 'image', true);"><?php echo gettext('show more fields');?></a></span>
						<span style="display: none;" class="imageextrahide"><a
							href="javascript:toggleExtraInfo('<?php echo $currentimage;?>', 'image', false);"><?php echo gettext('show fewer fields');?></a></span>
						</td>
					</tr>
		
		
				</table>
				</td>
			</tr>
		
			<?php
			$currentimage++;
		}
		?>
			<tr>
				<td colspan="4"><input type="submit"
					value="<?php echo gettext('save changes'); ?>" /></td>
			</tr>
		<?php
		if ($allimagecount != $totalimages) { // need pagination links
			?>
			<tr>
				<td colspan="4" class="bordered" id="imagenavb"><?php adminPageNav($pagenum,$totalpages,'admin.php','?page=edit&amp;album='.urlencode($album->name),'&amp;tab=imageinfo'); ?>
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
	}
?>
<!-- page trailer -->
<p><a href="?page=edit<?php echo $albumdir ?>"
	title="<?php echo gettext('Back to the list of albums (go up one level)'); ?>">&laquo; <?php echo gettext("Back"); ?></a></p>


<?php

/*** MULTI-ALBUM ***************************************************************************/

} else if (isset($_GET['massedit'])) {
	// one time generation of this list.
	$mcr_albumlist = array();
	genAlbumUploadList($mcr_albumlist);
	
if (isset($_GET['saved'])) {
		if (isset($_GET['mismatch'])) {
			echo "\n<div class=\"errorbox\" id=\"fade-message\">";
			echo "\n<h2>".gettext("Your passwords did not match")."</h2>";
			echo "\n</div>";
		} else {
			echo "\n<div class=\"messagebox\" id=\"fade-message\">";
			echo "\n<h2>".gettext("Save Successful")."</h2>";
			echo "\n</div>";
		}
	}
	$albumdir = "";
	if (isset($_GET['album'])) {
		$folder = sanitize_path($_GET['album']);
		if (isMyAlbum($folder, EDIT_RIGHTS)) {
			$album = new Album($gallery, $folder);
			$albums = $album->getSubAlbums();
			$pieces = explode('/', $folder);
			if (($i = count($pieces)) > 1) {
				unset($pieces[$i-1]);
				$albumdir = "&album=" . urlencode(implode('/', $pieces));
			} else {
				$albumdir = "";
			}
		} else {
			$albums = array();
		}
	} else {
		$albumsprime = $gallery->getAlbums();
		$albums = array();
		foreach ($albumsprime as $album) { // check for rights
			if (isMyAlbum($album, EDIT_RIGHTS)) {
				$albums[] = $album;
			}
		}
	}
	?>
<h1><?php echo gettext("Edit All Albums in"); ?> <?php if (!isset($_GET['album'])) { echo gettext("Gallery");} else {echo "<em>" . $album->name . "</em>";}?></h1>
<p><a href="?page=edit<?php echo $albumdir ?>"
	title="<?php gettext('Back to the list of albums (go up a level)'); ?>">&laquo; <?php echo gettext("Back"); ?></a></p>
<div class="box" style="padding: 15px;">

<form name="albumedit" AUTOCOMPLETE=OFF
	action="?page=edit&action=save<?php echo $albumdir ?>" method="POST">
	<input type="hidden" name="totalalbums" value="<?php echo sizeof($albums); ?>" />
<?php
	$currentalbum = 0;
	foreach ($albums as $folder) {
		$currentalbum++;
		$album = new Album($gallery, $folder);
		$images = $album->getImages();
		echo "\n<!-- " . $album->name . " -->\n";
		?>
		<div class="innerbox" style="padding: 15px;">
		<?php
		printAlbumEditForm($currentalbum, $album);
		?>
		</div>
		<br />
		<?php
	}
	?></form>

</div>
<?php

/*** EDIT ALBUM SELECTION *********************************************************************/

} else { /* Display a list of albums to edit. */ ?>
<h1><?php echo gettext("Edit Gallery"); ?></h1>
<?php
	displayDeleted(); /* Display a message if needed. Fade out and hide after 2 seconds. */
	if (isset($_GET['saved'])) {
		setOption('gallery_sorttype', 'Manual');
		setOption('gallery_sortdirection', 0);
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Album order saved")."</h2>";
		echo '</div>';
	}
	if (isset($_GET['counters_reset'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Hitcounters have been reset.")."</h2>";
		echo '</div>';
	}
	if (isset($_GET['action']) && $_GET['action'] == 'clear_cache') {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Cache has been purged.")."</h2>";
		echo '</div>';
	}
	$albumsprime = $gallery->getAlbums();
	$albums = array();
	foreach ($albumsprime as $album) { // check for rights
		if (isMyAlbum($album, EDIT_RIGHTS)) {
			$albums[] = $album;
		}
	}
	?>
<p><?php
	if (count($albums) > 0) {
		if (($_zp_loggedin & ADMIN_RIGHTS) && (count($albums)) > 1) {
			$sorttype = getOption('gallery_sorttype');
			if ($sorttype != 'Manual') {
				if (getOption('gallery_sortdirection')) {
					$dir = gettext(' descending');
				} else {
					$dir = '';
				}
				$sortNames = array_flip($sortby);
				$sorttype = $sortNames[$sorttype];
			} else {
				$dir = '';
			}
			printf(gettext('Current sort: %1$s%2$s. '), $sorttype, $dir);
			echo gettext('Drag the albums into the order you wish them displayed.').' ';
		}
		echo gettext('Select an album to edit its description and data, or');
	?><a href="?page=edit&massedit"> <?php echo gettext('mass-edit all album data'); ?></a>.</p>

<table class="bordered" width="100%">
	<tr>
		<th style="text-align: left;"><?php echo gettext("Edit this album"); ?></th>
	</tr>
	<tr>
		<td style="padding: 0px 0px;" colspan="2">
		<div id="albumList" class="albumList"><?php
		if (count($albums) > 0) {
			foreach ($albums as $folder) {
				$album = new Album($gallery, $folder);
				printAlbumEditRow($album);
			}
		}
		?></div>
		</td>
	</tr>
</table>
<div>
<ul class="iconlegend">
		<li><img src="images/lock.png" alt="Protected" /><?php echo gettext("Has Password"); ?></li>
		<li><img src="images/pass.png" alt="Published" /><img src="images/action.png" alt="Unpublished" /><?php echo gettext("Published/Unpublished"); ?></li>
		<li><img src="images/cache.png" alt="Cache the album" /><?php echo gettext("Cache	the album"); ?></li>
		<li><img src="images/warn.png" alt="Refresh image metadata" /><?php echo gettext("Refresh image metadata"); ?></li>
		<li><img src="images/reset.png" alt="Reset hitcounters" /><?php echo gettext("Reset	hitcounters"); ?></li>
		<li><img src="images/fail.png" alt="Delete" /><?php echo gettext("Delete"); ?></li>
		</ul>
<?php
  if ($_zp_loggedin & (ADMIN_RIGHTS | ALL_ALBUMS_RIGHTS)) {
		zenSortablesSaveButton("?page=edit&saved", gettext("Save Order"));
  }
	?>
</div>
<?php
	} else {
		echo gettext("There are no albums for you to edit.");
	}
}
/*** HOME ***************************************************************************/
/************************************************************************************/
} else {
$page = "home"; ?>
<h1><?php echo gettext("zenphoto Administration"); ?></h1>
<?php
	if (isset($_GET['check_for_update'])) {
		$v = checkForUpdate();
		if (!empty($v)) {
			if ($v == 'X') {
				echo "\n<div style=\"font-size:150%;color:#ff0000;text-align:right;\">".gettext("Could not connect to  <a href=\"http://www.zenphoto.org\">zenphoto.org</a>")."</div>\n";
			} else {
				echo "\n<div style=\"font-size:150%;text-align:right;\"><a href=\"http://www.zenphoto.org\">". sprinf(gettext("zenphoto version %s is available."), $v)."</a></div>\n";
			}
		} else {
			echo "\n<div style=\"font-size:150%;color:#33cc33;text-align:right;\">".gettext("You are running the latest zenphoto version.")."</div>\n";
		}
	} else {
		echo "\n<div style=\"text-align:right;color:#0000ff;\"><a href=\"?check_for_update\">".gettext("Check for zenphoto update.")."</a></div>\n";
	}
	?>
<ul id="home-actions">
	<?php if ($_zp_loggedin & (ADMIN_RIGHTS | UPLOAD_RIGHTS))  { ?>
	<li><a href="admin-upload.php"> &raquo; <?php echo gettext("<strong>Upload</strong> pictures."); ?></a></li>
	<?php } if ($_zp_loggedin & (ADMIN_RIGHTS | EDIT_RIGHTS))  { ?>
	<li><a href="?page=edit"> &raquo; <?php echo gettext("<strong>Edit</strong> titles, descriptions, and other metadata."); ?></a></li>
	<?php } if ($_zp_loggedin & (ADMIN_RIGHTS | COMMENT_RIGHTS))  { ?>
	<li><a href="admin-comments.php"> &raquo; <?php echo gettext("Edit or delete <strong>comments</strong>."); ?></a></li>
	<?php } ?>
	<li><a href="../"> &raquo; <?php echo gettext("Browse your <strong>gallery</strong> and edit on the go."); ?></a></li>
</ul>
<?php
	if (!empty($msg)) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>$msg</h2>";
		echo '</div>';
	}
?>

<hr />

<div class="box" id="overview-comments">
<h2><?php echo gettext("10 Most Recent Comments"); ?></h2>
<ul>
	<?php
$comments = fetchComments(10);
foreach ($comments as $comment) {
	$id = $comment['id'];
	$author = $comment['name'];
	$email = $comment['email'];
		if(getOption("zp_plugin_zenpage")) {
			require_once(dirname(__FILE__).'/plugins/zenpage/zenpage-class.php');
			$zenpage = new Zenpage("","");
		}
		// ZENPAGE: switch added for zenpage comment support
		switch ($comment['type']) {
			case "images":
				$imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
 										" WHERE `id`=" . $comment['ownerid']);
				if ($imagedata) {
					$imgdata = $imagedata[0];
					$image = $imgdata['filename'];
					if ($imgdata['title'] == "") $title = $image; else $title = get_language_string($imgdata['title']);
					$title = '/ ' . $title;
					$albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
 											" WHERE `id`=" . $imgdata['albumid']);
					if ($albmdata) {
						$albumdata = $albmdata[0];
						$album = $albumdata['folder'];
						$albumtitle = get_language_string($albumdata['title']);
						$link = "<a href=\"".rewrite_path("/$album/$image","/index.php?album=".urlencode($album).	"&amp;image=".urlencode($image))."\">".$albumtitle.$title."</a>";
						if (empty($albumtitle)) $albumtitle = $album;
					} else {
						$title = gettext('database error');
					}
				} else {
					$title = gettext('database error');
				}
				break;
			case "albums":
				$image = '';
				$title = '';
				$albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
 										" WHERE `id`=" . $comment['ownerid']);
				if ($albmdata) {
					$albumdata = $albmdata[0];
					$album = $albumdata['folder'];
					$albumtitle = get_language_string($albumdata['title']);
					$link = "<a href=\"".rewrite_path("/$album","/index.php?album=".urlencode($album))."\">".$albumtitle.$title."</a>";
					if (empty($albumtitle)) $albumtitle = $album;
				} else {
					$title = gettext('database error');
				}
				break;
			case "news": // ZENPAGE: if plugin is installed
				if(getOption("zp_plugin_zenpage")) {
					$titlelink = '';
					$title = '';
					$newsdata = query_full_array("SELECT `title`, `titlelink` FROM ". prefix('zenpage_news') .
 										" WHERE `id`=" . $comment['ownerid']);
					if ($newsdata) {
						$newsdata = $newsdata[0];
						$titlelink = $newsdata['titlelink'];
						$title = get_language_string($newsdata['title']);
				  $link = "<a href=\"".rewrite_path("/".ZENPAGE_NEWS."/".$titlelink,"/index.php?p=".ZENPAGE_NEWS."&amp;title=".urlencode($titlelink))."\">".$title."</a> ".gettext("[news]");
					} else {
						$title = gettext('database error');
					}
				}
				break;
			case "pages": // ZENPAGE: if plugin is installed
				if(getOption("zp_plugin_zenpage")) {
					$image = '';
					$title = '';
					$pagesdata = query_full_array("SELECT `title`, `titlelink` FROM ". prefix('zenpage_pages') .
 										" WHERE `id`=" . $comment['ownerid']);
					if ($pagesdata) {
						$pagesdata = $pagesdata[0];
						$titlelink = $pagesdata['titlelink'];
						$title = get_language_string($pagesdata['title']);
						$link = "<a href=\"".rewrite_path("/".ZENPAGE_PAGES."/".$titlelink,"/index.php?p=".ZENPAGE_PAGES."&amp;title=".urlencode($titlelink))."\">".$title."</a> ".gettext("[page]");
					} else {
						$title = gettext('database error');
					}
				}
				break;
		}
		$date  = myts_date('%m/%d/%Y %I:%M %p', $comment['date']);
		$website = $comment['website'];
		$comment = truncate_string($comment['comment'], 123);
		$inmoderation = $comment['inmoderation'];
		$private = $comment['private'];
		$anon = $comment['anon'];
	echo "<li><div class=\"commentmeta\">".sprintf(gettext('%1$s commented on %2$s:'),$author,$link)."</div><div class=\"commentbody\">$comment</div></li>";
}
?>
</ul>
</div>


<div class="box" id="overview-maint">
<h2 class="boxtitle"><?php echo gettext("Gallery Maintenance"); ?></h2>
<p>
<?php echo gettext("Your database is"); ?>: '<strong><?php echo getOption('mysql_database'); ?>'</strong><br />
<?php echo gettext("Tables are prefixed by"); ?> <strong>'<?php echo getOption('mysql_prefix'); ?>'</strong>
</p>
<?php if ($_zp_loggedin & ADMIN_RIGHTS) { ?>
<form name="prune_gallery" action="admin-refresh-metadata.php?prune">
	<input type="hidden" name="prune" value="true">
	<div class="buttons pad_button" id="home_dbrefresh">
	<button class="tooltip" type="submit" title="<?php echo gettext("Cleans the database and removes any orphan entries for comments, images, and albums."); ?>"><img src="images/refresh.png" alt="" /> <?php echo gettext("Refresh the Database"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>

<form name="clear_cache" action="admin.php?action=clear_cache=true"><input
		type="hidden" name="action" value="clear_cache">
	<div class="buttons" id="home_refresh">
	<button class="tooltip" type="submit" title="<?php echo gettext("Clears the image cache. Images will be re-cached as they are viewed."); ?>"><img src="images/burst.png" alt="" /> <?php echo gettext("Purge Cache"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>

<form name="cache_images" action="admin-cache-images.php">
	<div class="buttons" id="home_cache">
	<button class="tooltip" type="submit" title="<?php echo gettext("Finds newly uploaded images that have not been cached and creates the cached version. It also refreshes the numbers above. If you have a large number of images in your gallery you might consider using the <em>pre-cache image</em> link for each album to avoid swamping your browser."); ?>"><img src="images/cache.png" title="<?php echo gettext('Pre-Cache Images'); ?>" alt="" /> <?php echo gettext("Pre-Cache Images"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>

<form name="refresh_metadata" action="admin-refresh-metadata.php">
	<div class="buttons" id="home_exif">
	<button class="tooltip" type="submit" title="<?php echo gettext("Forces a refresh of the EXIF and IPTC data for all images."); ?>"><img src="images/warn.png" alt="" /> <?php echo gettext("Refresh Metadata"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>

<form name="reset_hitcounters"
		action="admin.php?action=reset_hitcounters=true"><input type="hidden"
		name="action" value="reset_hitcounters">
	<div class="buttons" id="home_refresh">
	<button class="tooltip" type="submit" title="<?php echo gettext("Sets all album and image hitcounters to zero."); ?>"><img src="images/reset.png" alt="" /> <?php echo gettext("Reset hitcounters"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>
<?php
}
$curdir = getcwd();
chdir(SERVERPATH . "/" . ZENFOLDER . UTILITIES_FOLDER);
$filelist = safe_glob('*'.'php');
natcasesort($filelist);
foreach ($filelist as $utility) {
	$button_text = '';
	$button_hint = '';
	$button_icon = '';
	$button_rights = false;
	
	$utilityStream = file_get_contents($utility);
	eval(isolate('$button_text', $utilityStream));
	eval(isolate('$button_hint', $utilityStream));
	eval(isolate('$button_icon', $utilityStream));
	eval(isolate('$button_rights', $utilityStream));
	$button_rights = $button_rights | ADMIN_RIGHTS;

	if ($_zp_loggedin & $button_rights) {	
?>
	<form name="<?php echo $utility; ?>" action="utilities/<?php echo $utility; ?>">
		<div class="buttons" id="home_exif">
		<button class="tooltip" type="submit" title="<?php echo $button_hint; ?>"><?php if(!empty($button_icon)) echo '<img src="'.$button_icon.'" alt="" />'; echo $button_text; ?></button>
		</div>
		<br clear="all" />
		<br clear="all" />
	</form>

<?php
	}
}
?>
</div>


<div class="box" id="overview-stats">
<h2 class="boxtitle"><?php echo gettext("Gallery Stats"); ?></h2>
<p>
<?php
$t = $gallery->getNumImages();
$c = $t-$gallery->getNumImages(true);
if ($c > 0) {
	printf(gettext('<strong>%1$u</strong> images (%2$u not visible)'),$t, $c);
} else {
	printf(gettext('<strong>%u</strong> images'),$t);
}
?>
</p>
<p>
<?php
$t = $gallery->getNumAlbums(true);
$c = $t-$gallery->getNumAlbums(true,true);
if ($c > 0) {
	printf(gettext('<strong>%1$u</strong> albums (%2$u not published)'),$t, $c);
} else {
	printf(gettext('<strong>%u</strong> albums'),$t);
}
?>
</p>

<p>
<?php $t = $gallery->getNumComments(true);
$c = $t - $gallery->getNumComments(false);
if ($c > 0) {
	printf(gettext('<strong>%1$u</strong> comments (%2$u in moderation)'),$t, $c);
} else {
	printf(gettext('<strong>%u</strong> comments'),$t);
}
?>
</p>
</div>
<p style="clear: both;"></p>
<?php
}
?>
</div>
<!-- content --> <?php
printAdminFooter();
if (issetPage('edit') &&
	((isset($albums) && count($albums) > 0)
		|| (isset($subalbums) && count($subalbums) > 0))) {
	zenSortablesFooter();
}
} /* No admin-only content allowed after this bracket! */ ?></div>
<!-- main -->
</body>
<?php // to fool the validator
echo "\n</html>";
?>
