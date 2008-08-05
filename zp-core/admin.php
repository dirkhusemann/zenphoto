<?php
/**
 * admin.php is the main script for administrative functions.
 * @package admin
 */

/* Don't put anything before this line! */
define('OFFSET_PATH', 1);
require_once('admin-functions.php');
require_once("admin-sortable.php");
if (zp_loggedin()) { /* Display the admin pages. Do action handling first. */
	if (($_zp_null_account = ($_zp_loggedin == ADMIN_RIGHTS)) || ($_zp_loggedin == NO_RIGHTS)) { // user/password set required.
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-options.php");
	}

	$tagsort = getTagOrder();
	//check for security incursions
	if (isset($_GET['album'])) {
		if (!($_zp_loggedin & ADMIN_RIGHTS)) {
			if (!isMyAlbum(urldecode(strip($_GET['album'])), $_zp_loggedin)) {
				unset($_GET['album']);
				unset($_GET['page']);
				$page = '';
			}
		}
	}


	$gallery = new Gallery();
	if (isset($_GET['prune'])) {
		if ($_GET['prune'] != 'done') {
			if ($gallery->garbageCollect(true, true)) {
				$param = '?prune=continue';
			} else {
				$param = '?prune=done';
			}
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php" . $param);
		}
	} else {
		$gallery->garbageCollect();
	}
	if (isset($_GET['action'])) {
		$action = $_GET['action'];
		/** reorder the tag list ******************************************************/
		/******************************************************************************/
		if ($action == 'sorttags') {
			if (isset($_GET['subpage'])) {
				$pg = '&subpage='.$_GET['subpage'];
				$tab = '#tab_imageinfo';
			} else {
				$pg = '';
				$tab = '';
			}
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit&album='.$_GET['album'].$pg.'&tagsort='.$tagsort.$tab);
		}

		/** clear the cache ***********************************************************/
		/******************************************************************************/
		if ($action == "clear_cache") {
			$gallery->clearCache();
		}

		/** Publish album  ************************************************************/
		/******************************************************************************/
		if ($action == "publish") {
			$folder = urldecode(strip($_GET['album']));
			$album = new Album($gallery, $folder);
			$album->setShow($_GET['value']);
			$album->save();
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?page=edit');
			exit();

			/** Reset hitcounters ***********************************************************/
			/********************************************************************************/
		} else if ($action == "reset_hitcounters") {
			if (isset($_GET['albumid'])) $id = $_GET['albumid'];
			if (isset($_POST['albumid'])) $id = $_POST['albumid'];
			if(isset($id)) {
				$where = ' WHERE `id`='.$id;
				$imgwhere = ' WHERE `albumid`='.$id;
				$return = '?page=edit';
				if (isset($_GET['return'])) $rt = $_GET['return'];
				if (isset($_POST['return'])) $rt = $_POST['return'];
				if (isset($rt)) {
					$return .= '&album=' . $rt .'&counters_reset';
				}
			} else {
				$where = '';
				$imgwhere = '';
				$return = '?counters_reset';
			}
			query("UPDATE " . prefix('albums') . " SET `hitcounter`= 0" . $where);
			query("UPDATE " . prefix('images') . " SET `hitcounter`= 0" . $imgwhere);
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php' . $return);
			exit();

			/** SAVE **********************************************************************/
			/******************************************************************************/
		} else if ($action == "save") {
			$returntab = '';

			/** SAVE A SINGLE ALBUM *******************************************************/
			if ($_POST['album']) {

				$folder = urldecode(strip($_POST['album']));
				$album = new Album($gallery, $folder);
				$notify = '';
				if (isset($_POST['savealbuminfo'])) {
					$notify = processAlbumEdit(0, $album);
					$returntab = '&tagsort='.$tagsort.'#tab_albuminfo';
				}

				if (isset($_POST['totalimages'])) {
					$returntab = '&tagsort='.$tagsort.'#tab_imageinfo';
					if (isset($_POST['thumb'])) {
						$thumbnail = sanitize_numeric($_POST['thumb']);
					} else {
						$thumbnail = -1;
					}
					for ($i = 0; $i < $_POST['totalimages']; $i++) {
						$filename = strip($_POST["$i-filename"]);

						// The file might no longer exist
						$image = new Image($album, $filename);
						if ($image->exists) {
							if (isset($_POST[$i.'-Delete'])) {
								$image->deleteImage(true);								
							} else {
								if ($thumbnail == $i) { //selected as album thumb
									$album = $image->getAlbum();
									$album->setAlbumThumb($image->filename);
									$album->save();								
								}
								$image->setTitle(process_language_string_save("$i-title"));
								$image->setDesc(process_language_string_save("$i-desc"));
								$image->setLocation(process_language_string_save("$i-location"));
								$image->setCity(process_language_string_save("$i-city"));
								$image->setState(process_language_string_save("$i-state"));
								$image->setCountry(process_language_string_save("$i-country"));
								$image->setCredit(process_language_string_save("$i-credit"));
								$image->setCopyright(process_language_string_save("$i-copyright"));

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
								$image->setTags($tags);


								$image->setDateTime(strip($_POST["$i-date"]));
								$image->setShow(strip($_POST["$i-Visible"]));
								$image->setCommentsAllowed(strip($_POST["$i-allowcomments"]));
								if (isset($_POST["$i-reset_hitcounter"])) {
									$id = $image->id;
									query("UPDATE " . prefix('images') . " SET `hitcounter`= 0 WHERE `id` = $id");
								}
								$image->setCustomData(process_language_string_save("$i-custom_data"));
								$image->save();
							}
						}
					}
				}

				/** SAVE MULTIPLE ALBUMS ******************************************************/
			} else if ($_POST['totalalbums']) {
				for ($i = 1; $i <= $_POST['totalalbums']; $i++) {
					$folder = urldecode(strip($_POST["$i-folder"]));
					$album = new Album($gallery, $folder);
					$rslt = processAlbumEdit($i, $album);
					if (!empty($rslt)) { $notify = $rslt; }
				}
			}
			// Redirect to the same album we saved.
			$qs_albumsuffix = "&massedit";
			if ($_GET['album']) {
				$folder = urldecode(strip($_GET['album']));
				$qs_albumsuffix = '&album='.urlencode($folder);
			}
			if (isset($_POST['subpage'])) {
				$pg = '&subpage='.$_POST['subpage'];
			} else {
				$pg = '';
			}
			header('Location: '.FULLWEBPATH.'/'.ZENFOLDER.'/admin.php?page=edit'.$qs_albumsuffix.$notify.'&saved'.$pg.$returntab);
			exit();

			/** DELETION ******************************************************************/
			/*****************************************************************************/
		} else if ($action == "deletealbum") {
			$albumdir = "";
			if ($_GET['album']) {
				$folder = urldecode(strip($_GET['album']));
				$album = new Album($gallery, $folder);
				if ($album->deleteAlbum()) {
					$nd = 3;
				} else {
					$nd = 4;
				}
				$pieces = explode('/', $folder);
				if (($i = count($pieces)) > 1) {
					unset($pieces[$i-1]);
					$albumdir = "&album=" . urlencode(implode('/', $pieces));
				}
			}
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php?page=edit" . $albumdir . "&ndeleted=");
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

/** SINGLE ALBUM ********************************************************************/

define('IMAGES_PER_PAGE', 10);

if (isset($_GET['album']) && !isset($_GET['massedit'])) {
	$folder = strip($_GET['album']);
	$album = new Album($gallery, $folder);
	if ($album->isDynamic()) {
		$subalbums = array();
		$allimages = array();
	} else {
		$subalbums = $album->getSubAlbums();
		$allimages = $album->getImages(0, 0, 'ID', 'DESC');
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
		?>
	<?php
	}
	if (isset($_GET['uploaded'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Images uploaded")."</h2>";
		echo '</div>';
	}
	
//* TODO: 1.2 enable this
	echo '<div id="mainmenu">';
	echo '<ul>';
	echo '<li><a href="#tab_albuminfo"><span>'.gettext("Album").'</span></a></li>';
	if (count($subalbums) > 0) {
		echo '<li><a href="#tab_subalbuminfo"><span>'.gettext("Subalbums").'</span></a></li>';
	} if ($allimagecount) {
		echo '<li><a href="#tab_imageinfo"><span>'.gettext("Images").'</span></a></li>';
	}
	echo '</ul>';
	echo '</div>'."\n";
//*/
	?>
<!-- Album info box -->
<div id="tab_albuminfo">
<form name="albumedit1"
	action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>"	method="post">
	<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
	<input type="hidden"	name="savealbuminfo" value="1" />
	<?php printAlbumEditForm(0, $album); ?>
</form>
<br />
<?php printAlbumButtons($album) ?> <?php if (!$album->isDynamic())  {?>
<br />
</div>
<!-- Subalbum list goes here -->
<div id="tab_subalbuminfo">
<?php
if (count($subalbums) > 0) {
?>
<table class="bordered" width="100%">
	<input type="hidden" name="subalbumsortby" value="Manual" />
	<tr>
		<th colspan="8">
		<h2 class="subheadline"><?php echo gettext("Albums"); ?></h2>
		</th>
	</tr>
	<tr>
		<td colspan="8"><?php echo gettext("Drag the albums into the order you wish them displayed. Select an album to edit its description and data, or"); ?>
		 <a	href="?page=edit&album=<?php echo urlencode($album->name)?>&massedit"><?php echo gettext("mass-edit all album data"); ?></a>.</td>
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
			zenSortablesSaveButton("?page=edit&album=" . urlencode($album->name) . "&subalbumsaved#tab_subalbuminfo", gettext("Save Order"));
			?>

<br />
<?php
} ?>
</div>
<!-- Images List -->
<div id="tab_imageinfo">
<script type="text/javascript">
  function deletemsg(obj, element, msg) {
		if(obj.checked) {
			if (confirm(msg)) {
				document.getElementById(element).style.display = 'block';
			} else {
				obj.checked = false;
			}
		} else {
			document.getElementById(element).style.display = 'none';
 		}
	}
</script>
<?php
if ($allimagecount) {


	?>
<form name="albumedit2"	action="?page=edit&action=save<?php echo "&album=" . urlencode($album->name); ?>"	method="post">
	<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
	<input type="hidden" name="totalimages" value="<?php echo $totalimages; ?>" />
	<input type="hidden" name="subpage" value="<?php echo $pagenum; ?>" />
	<input type="hidden" name="tagsort" value=<?php echo $tagsort ?> />

<?php	$totalpages = ceil(($allimagecount / IMAGES_PER_PAGE));	?>
<table class="bordered">
	<tr>
		<th colspan="3">
		<h2 class="subheadline"><?php echo gettext("Images"); ?></h2>
		</th>
	</tr>
	<?php
	if ($allimagecount != $totalimages) { // need pagination links
	?>
	<tr><td colspan ="3" class="bordered" id="imagenav">
	<?php adminPageNav($pagenum,$totalpages,'admin.php?page=edit&amp;album='.urlencode($album->name),'#tab_imageinfo'); ?>
	</td></tr>
	<?php
	}
 ?>
	<tr>
		<td>
			<input type="submit" value="<?php echo gettext('save changes'); ?>" />
			<br/><?php echo gettext("Click the images for a larger version"); ?>
		</td>
	</tr>

	<?php
	$currentimage = 0;
	foreach ($images as $filename) {
		$image = new Image($album, $filename);
		?>

	<tr id=""	<?php echo ($currentimage % 2 == 0) ?  "class=\"alt\"" : ""; ?>>
		<td >
		<input type="hidden"
			name="<?php echo $currentimage; ?>-filename"
			value="<?php echo $image->filename; ?>" />
		<table border="0" class="formlayout">
			<tr>
				<td valign="top" width="100" rowspan=14>
					<img	id="thumb-<?php echo $currentimage; ?>"
						src="<?php echo $image->getThumb();?>"
						alt="<?php echo $image->filename;?>"
						onclick="toggleBigImage('thumb-<?php echo $currentimage; ?>', '<?php echo $image->getSizedImage(getOption('image_size')); ?>');" />
				</td>
				<td align="right" valign="top" width="100"><?php echo gettext("Filename:"); ?></td>
				<td>
				<?php echo $image->filename; ?>
				</td>
				<td style="padding-left: 1em;" rowspan="2">
					<input type="checkbox" id="<?php echo $currentimage; ?>-Delete"	
							name="<?php echo $currentimage; ?>-Delete" value="1" 
							onclick="deletemsg(this, 'deletemsg<?php echo $currentimage; ?>','<?php echo gettext("Are you sure you want to delete this image?"); ?>')" />
				 	<?php echo ' '.gettext("Delete this image.") ?> 
					<div id="deletemsg<?php echo $currentimage; ?>" style="color:red; display:none" >
				 	<?php echo gettext('Image will be deleted when changes are saved.'); ?>
				 	</div> 
				</td>
			</tr>
			<tr>
				<td align="right" valign="top" width="100"><?php echo gettext("Title:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('title'), $currentimage.'-title', false); ?>
				</td>
			</tr>
			<tr>
			<td></td>
			<td>
				<input type="radio" id="<?php echo $currentimage; ?>-thumb"	name="thumb" value="<?php echo $currentimage ?>" />
				 <?php echo ' '.gettext("Select as album thumbnail."); ?>
				</td>
			<td></td>
			</tr>
			<tr>
			<?php
			$id = $image->id;
			$result = query_single_row("SELECT `hitcounter` FROM " . prefix('images') . " WHERE `id` = $id");
			$hc = $result['hitcounter'];
			if (empty($hc)) { $hc = '0'; }
			echo "<td></td>";
			echo "<td>". gettext("Hit counter:").' '. $hc . " <input type=\"checkbox\" name=\"".gettext("reset_hitcounter")."\"> ".gettext("Reset")."</td>";
			?>
				<td rowspan=11 style="padding-left: 1em;">
				<?php
				tagSelector($image, 'tags_'.$currentimage.'-', false, $tagsort);
/*
				if ($tagsort == 1) {
					echo '<a class="tagsort" href="?action=sorttags&amp;album='.urlencode($album->name).'&amp;subpage='.$pagenum.'&amp;tagsort=0' .
 								'" title="'.gettext('Sort the tags alphabetically').'">';
					echo ' '.gettext('Order alphabetically').'</a>';
				} else{
					echo '<a class="tagsort" href="?action=sorttags&amp;album='.urlencode($album->name).'&amp;subpage='.$pagenum.'&amp;tagsort=1' .
 								'" title="'.gettext('Sort the tags by most used').'">';
					echo ' '.gettext('Order by most used').'</a>';
				}
				echo '<br /><strong>'.gettext("note:").'</strong> '.gettext('Selected tags are always placed at the front of the list.');
*/
				?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Description:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('desc'), $currentimage.'-desc', true); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Location:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('location'), $currentimage.'-location', false); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("City:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('city'), $currentimage.'-city', false); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("State:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('state'), $currentimage.'-state', false); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Country:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('country'), $currentimage.'-country', false); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Credit:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('credit'), $currentimage.'-credit', false); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Copyright:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('copyright'), $currentimage.'-copyright', false); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Date:"); ?></td>
				<td><input type="text" size="56" style="width: 360px"
					name="<?php echo $currentimage; ?>-date"
					value="<?php $d=$image->getDateTime(); if ($d!='0000-00-00 00:00:00') { echo $d; } ?>" /></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php echo gettext("Custom data:"); ?></td>
				<td>
				<?php print_language_string_list($image->get('custom_data'), $currentimage.'-custom_data', true); ?>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top" colspan="2">
				<label for="<?php echo $currentimage; ?>-allowcomments">
				<input type="checkbox" id="<?php echo $currentimage; ?>-allowcomments"
					name="<?php echo $currentimage; ?>-allowcomments" value="1"
					<?php if ($image->getCommentsAllowed()) { echo "checked=\"checked\""; } ?> />
				<?php echo gettext("Allow Comments"); ?></label> &nbsp; &nbsp; 
				<label for="<?php echo $currentimage; ?>-Visible">
				<input type="checkbox" id="<?php echo $currentimage; ?>-Visible"
					name="<?php echo $currentimage; ?>-Visible" value="1"
					<?php if ($image->getShow()) { echo "checked=\"checked\""; } ?> />
				<?php echo gettext("Visible"); ?></label></td>
			</tr>
		</table>
		</td>


	</tr>

	<?php
	$currentimage++;
}
	if ($allimagecount != $totalimages) { // need pagination links
	?>
	<tr><td colspan ="3" class="bordered" id="imagenav">
	<?php adminPageNav($pagenum,$totalpages,'admin.php?page=edit&amp;album='.urlencode($album->name),'#tab_imageinfo'); ?>
	</td></tr>
	<?php
	}
 ?>
	<tr>
		<td colspan="3"><input type="submit" value="<?php echo gettext('save changes'); ?>" /></td>
	</tr>

</table>


</form>

<?php
	}
}?>
</div>
<!-- page trailer -->
<p><a href="?page=edit<?php echo $albumdir ?>"
	title="<?php echo gettext('Back to the list of albums (go up one level)'); ?>">&laquo; <?php echo gettext("Back"); ?></a></p>


<?php

/*** MULTI-ALBUM ***************************************************************************/

} else if (isset($_GET['massedit'])) {
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
		$folder = strip($_GET['album']);
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

<form name="albumedit"
	action="?page=edit&action=save<?php echo $albumdir ?>" method="POST"><input
	type="hidden" name="totalalbums" value="<?php echo sizeof($albums); ?>" />
<?php
	$currentalbum = 0;
	foreach ($albums as $folder) {
		$currentalbum++;
		$album = new Album($gallery, $folder);
		$images = $album->getImages();
		echo "\n<!-- " . $album->name . " -->\n";
		printAlbumEditForm($currentalbum, $album);
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
			echo gettext('Drag the albums into the order you wish them displayed.');
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
  if ($_zp_loggedin & ADMIN_RIGHTS) {
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
				echo "\n<div style=\"font-size:150%;color:#ff0000;text-align:right;\">".gettext("Could not connect to")." <a href=\"http://www.zenphoto.org\">zenphoto.org</a>.</div>\n";
			} else {
				echo "\n<div style=\"font-size:150%;text-align:right;\"><a href=\"http://www.zenphoto.org\">". gettext("zenphoto version"). $v .gettext("is available.")."</a></div>\n";
			}
		} else {
			echo "\n<div style=\"font-size:150%;color:#33cc33;text-align:right;\">".gettext("You are running the latest zenphoto version.")."</div>\n";
		}
	} else {
		echo "\n<div style=\"text-align:right;color:#0000ff;\"><a href=\"?check_for_update\">".gettext("Check for zenphoto update.")."</a></div>\n";
	}
	$msg = '';
	if (isset($_GET['prune'])) {
		$msg = gettext("Database was refreshed");
	}
	if (isset($_GET['action']) && $_GET['action'] == 'clear_cache') {
		$msg = gettext("Cache has been purged");
	}
	if (isset($_GET['counters_reset'])) {
		$msg = gettext("Hitcounters have been reset");
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
			require_once("plugins/zenpage/zenpage-class.php");
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
	echo "<li><div class=\"commentmeta\">".$author." ".gettext("commented on")." ".$link.":</div><div class=\"commentbody\">$comment</div></li>";
}
?>
</ul>
</div>


<div class="box" id="overview-stats">
<h2 class="boxtitle"><?php echo gettext("Gallery Maintenance"); ?></h2>
<p><?php echo gettext("Your database is"); ?>: '<strong><?php echo getOption('mysql_database'); ?>'</strong><br />
<?php echo gettext("Tables are prefixed by"); ?> <strong>'<?php echo getOption('mysql_prefix'); ?>'</strong></p>
<?php if ($_zp_loggedin & ADMIN_RIGHTS) { ?>
<form name="prune_gallery" action="admin.php?prune=true"><input
		type="hidden" name="prune" value="true">
	<div class="buttons pad_button" id="home_dbrefresh">
	<button class="tooltip" type="submit" title="<?php echo gettext("Cleans the database and removes any orphan entries for comments, images, and albums."); ?>"><img src="images/refresh.png" alt="" /> <?php echo gettext("Refresh the Database"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>

<form name="clear_cache" action="admin.php?action=clear_cache=true"><input
		type="hidden" name="action" value="clear_cache">
	<div class="buttons" id="home_refresh">
	<button class="tooltip" type="submit" title="<?php echo gettext("Clears the image cache. Images will be re-cached as they are viewed. To clear the cache and renew it, use the <em>Pre-Cache Images</em> button below."); ?>"><img src="images/burst.png" alt="" /> <?php echo gettext("Purge Cache"); ?></button>
	</div>
	<br clear="all" />
	<br clear="all" />
</form>

<form name="cache_images" action="admin-cache-images.php">
	<div class="buttons" id="home_cache">
	<button class="tooltip" type="submit" title="<?php echo gettext("Finds newly uploaded images that have not been cached and creates the cached version. It also refreshes the numbers above. If you have a large number of images in your gallery you might consider using the <em>pre-cache image</em> link for each album to avoid swamping your browser."); ?>"><img src="images/cache.png" title="<?php echo gettext('Pre-Cache Images'); ?>" alt="" /> <?php echo gettext("Pre-Cache Images"); ?></button>
	</div>
	<input type="checkbox" name="clear" checked="checked" /> <?php echo gettext("Clear"); ?><br clear="all" />
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
?>
</div>


<div class="box" id="overview-suggest">
<h2 class="boxtitle"><?php echo gettext("Gallery Stats"); ?></h2>
<p>
<strong><?php echo $t = $gallery->getNumImages(); ?></strong> <?php echo gettext("images");
$c = $t-$gallery->getNumImages(true);
if ($c > 0) {
	echo ' ('.$c.' '.gettext("not visible").')';
}
?></p>
<p><strong><?php echo $t = $gallery->getNumAlbums(true); ?></strong> <?php echo gettext("albums");
$c = $t-$gallery->getNumAlbums(true,true);
if ($c > 0) {
	echo ' ('.$c.' '.gettext("unpublished").')';
}
?></p>

<p><strong><?php echo $t = $gallery->getNumComments(true); ?></strong>
<?php echo gettext("comments"); ?> <?php
$c = $t - $gallery->getNumComments(false);
if ($c > 0) {
	echo ' ('.$c.' '.gettext("in moderation").')';
}
?></p>
</div>
<p style="clear: both;"></p>
<?php
}
?>
</div>
<!-- content --> <?php
printAdminFooter();
if (issetPage('edit')) {
	zenSortablesFooter();
}
} /* No admin-only content allowed after this bracket! */ ?></div>
<!-- main -->
</body>
<?php // to fool the validator
echo "\n</html>";
?>
