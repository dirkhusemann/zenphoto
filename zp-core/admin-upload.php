<?php
/**
 * provides the Upload tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (!($_zp_loggedin & (UPLOAD_RIGHTS | ADMIN_RIGHTS))) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}
$uploadtype = zp_getcookie('uploadtype');
if (isset($_GET['uploadtype'])) {
	$uploadtype = sanitize($_GET['uploadtype'])	;
	zp_setcookie('uploadtype', $uploadtype);
}
$gallery = new Gallery();

/* handle posts */
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'upload') {
		// Check for files.
		$error = false;
		$files_empty = true;
		if (isset($_FILES['files'])) {
			foreach($_FILES['files']['name'] as $name) {
				if (!empty($name)) $files_empty = false;
			}
		}
		$newAlbum = ((isset($_POST['existingfolder']) && $_POST['existingfolder'] == 'false') || isset($_POST['newalbum']));
		// Make sure the folder exists. If not, create it.
		if (isset($_POST['processed']) && !empty($_POST['folder']) && ($newAlbum || !$files_empty)) {
			$folder = trim(sanitize_path($_POST['folder']));
			// see if he has rights to the album.
			if (!isMyAlbum($folder, UPLOAD_RIGHTS)) {
				$error = UPLOAD_ERR_CANT_WRITE;
			}
			if (!$error) {

				$uploaddir = $gallery->albumdir . internalToFilesystem($folder);
				if (!is_dir($uploaddir)) {
					mkdir_recursive($uploaddir, CHMOD_VALUE);
				}
				@chmod($uploaddir, CHMOD_VALUE);

				$album = new Album($gallery, $folder);
				if ($album->exists) {
					if (!isset($_POST['publishalbum'])) {
						$album->setShow(false);
					}
					$title = sanitize($_POST['albumtitle'], 2);
					if (!empty($title) && $newAlbum) {
						$album->setTitle($title);
					}
					$album->save();
				} else {
					$AlbumDirName = str_replace(SERVERPATH, '', $gallery->albumdir);
					zp_error(gettext("The album couldn't be created in the 'albums' folder. This is usually a permissions problem. Try setting the permissions on the albums and cache folders to be world-writable using a shell:")." <code>chmod 777 " . $AlbumDirName . '/'.CACHEFOLDER.'/' ."</code>, "
					. gettext("or use your FTP program to give everyone write permissions to those folders."));
				}
				foreach ($_FILES['files']['error'] as $key => $error) {
					if ($_FILES['files']['name'][$key] == "") continue;
					if ($error == UPLOAD_ERR_OK) {
						$tmp_name = $_FILES['files']['tmp_name'][$key];
						$name = trim($_FILES['files']['name'][$key]);
						$soename = seoFriendly($name);
						if (is_valid_image($name) || is_valid_other_type($name)) {
							if (strrpos($soename,'.')===0) $soename = md5($name).$soename; // soe stripped out all the name.
							$uploadfile = $uploaddir . '/' . internalToFilesystem($soename);
							move_uploaded_file($tmp_name, $uploadfile);
							@chmod($uploadfile, 0666 & CHMOD_VALUE);
							$image = newImage($album, $soename);
							if ($name != $soename) {
								$image->setTitle($name);
								$image->save();
							}
						} else if (is_zip($name)) {
							unzip($tmp_name, $uploaddir);
						} else {
							$error = UPLOAD_ERR_EXTENSION;	// invalid file uploaded
							break;
						}
					}
				}
				if (!$error) {
					header('Location: '.FULLWEBPATH.'/'.ZENFOLDER.'/admin-edit.php?page=edit&album='.urlencode($folder).'&uploaded&subpage=1&tab=imageinfo');
					exit();
				}
			}
		}
		// Handle the error and return to the upload page.
		$page = "upload";
		$_GET['page'] = 'upload';
		if ($files_empty && !isset($_POST['newalbum'])) {
			$errormsg = gettext("You must upload at least one file.");
		} else if (empty($_POST['folder'])) {
			$errormsg = gettext("You must enter a folder name for your new album.");
		} else if (!isset($_POST['processed'])) {
			$errormsg = gettext("You've most likely exceeded the upload limits. Try uploading fewer files at a time, or use a ZIP file.");
		} else {
			switch ($error) {
				case UPLOAD_ERR_CANT_WRITE:
					$errormsg = gettext('You have attempted to upload to an album for which you do not have upload rights');
					break;
				case UPLOAD_ERR_EXTENSION:
					$errormsg = gettext('You have attempted to upload one or more files which are not Zenphoto supported file types');
					break;
				default:
					$errormsg = gettext("There was an error submitting the form. Please try again. If this keeps happening, check your server and PHP configuration (make sure file uploads are enabled, and upload_max_filesize is set high enough.) If you think this is a bug, file a bug report. Thanks!");
					break;
			}
		}
		$error = true;
	}
}

printAdminHeader();
/* MULTI FILE UPLOAD: Script additions */ ?>
<link rel="stylesheet" href="admin-uploadify/uploadify.css" type="text/css" />
<script type="text/javascript">
var uploadifier_replace_message =  "<?php echo gettext('Do you want to replace the file %s?'); ?>";
</script>

<script type="text/javascript" src="<?php echo WEBPATH.'/'.ZENFOLDER;?>/js/sprintf.js"></script>
<script type="text/javascript" src="<?php echo WEBPATH.'/'.ZENFOLDER;?>/js/upload.js"></script>
<script type="text/javascript" src="<?php echo WEBPATH.'/'.ZENFOLDER;?>/admin-uploadify/jquery.uploadify.js"></script>
<script type="text/javascript" src="<?php echo WEBPATH.'/'.ZENFOLDER;?>/admin-uploadify/swfobject.js"></script>
<?php
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
?>
<div id="main">
	<?php
	printTabs('upload');
	?>
		<div id="content">
		<?php
			$albumlist = array();
			genAlbumUploadList($albumlist);
			?>
			<script type="text/javascript">
				window.totalinputs = 5;
				// Array of album names for javascript functions.
				var albumArray = new Array (
					<?php
					$separator = '';
					foreach($albumlist as $key => $value) {
						echo $separator . "'" . addslashes($key) . "'";
						$separator = ", ";
					}
					?> );
			</script>

<h1><?php echo gettext("Upload Photos"); ?></h1>
<p>
<?php
natcasesort($_zp_supported_images);
$types = array_keys($_zp_extra_filetypes);
natcasesort($types);
$last = strtoupper(array_pop($types));
$s1 = strtoupper(implode(', ', $_zp_supported_images));
$s2 = strtoupper(implode(', ', $types));
printf(gettext('This web-based upload accepts the ZenPhoto supported file formats: %s, %s, and %s.'), $s1, $s2, $last);
echo '<br />'.gettext('You can also upload ZIP files containing files of these types.');
$maxupload = ini_get('upload_max_filesize');
?>
</p>
<p>
<?php echo sprintf(gettext("The maximum size for any one file is <strong>%sB</strong> which is set by your PHP configuration <code>upload_max_filesize</code>."), $maxupload); ?>
<?php echo gettext(' Don\'t forget, you can also use <acronym title="File Transfer Protocol">FTP</acronym> to upload folders of images into the albums directory!'); ?>
</p>

<?php if (isset($error) && $error) { ?>
<div class="errorbox" id="fade-message">
<h2><?php echo gettext("Something went wrong..."); ?></h2>
<?php echo (empty($errormsg) ? gettext("There was an error submitting the form. Please try again.") : $errormsg); ?>
</div>
<?php
}
if (ini_get('safe_mode')) { ?>
<div class="warningbox" id="fade-message">
<h2><?php echo gettext("PHP Safe Mode Restrictions in effect!"); ?></h2>
<p><?php echo gettext("Zenphoto may be unable to perform uploads when PHP Safe Mode restrictions are in effect"); ?></p>
</div>
<?php
}
?>

<form name="uploadform" enctype="multipart/form-data" action="?action=upload&amp;uploadtype=http" method="post"
												onsubmit="return validateFolder(document.uploadform.folder,'<?php echo gettext('That name is already used.'); ?>','<?php echo gettext('This upload has to have a folder. Type a title or folder name to continue...'); ?>');">
	<input type="hidden" name="processed" value="1" />
	<input type="hidden" name="existingfolder" value="false" />

	<div id="albumselect">
	<?php
	$rootrights = isMyAlbum('/', UPLOAD_RIGHTS);
	if ($rootrights || !empty($albumlist)) {
		echo gettext("Upload to:");
		if (isset($_GET['new'])) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}
		$defaultjs = "
			<script type=\"text/javascript\">
				//<![CDATA[
				function soejs(fname) {
					fname = fname.replace(/[\!@#$\%\^&*()\~`\'\"]/g, '');
					fname = fname.replace(/^\s+|\s+$/g, '');
					fname = fname.replace(/[^a-zA-Z0-9]/g, '-');
					fname = fname.replace(/--*/g, '-');
					return fname;
				}
				//]]>
			</script>
		";

		echo zp_apply_filter('seoFriendly_js', $defaultjs);	
		?>
		<script type="text/javascript">
			function buttonstate(good) {
				if (good) {
					$('#fileUploadbuttons').show();
				} else {
					$('#fileUploadbuttons').hide();
				}
			}

			function albumSelect() {
				var sel = document.getElementById('albumselectmenu');
				buttonstate(albumSwitch(sel, true, '<?php echo gettext('That name is already used.'); ?>','<?php echo gettext('This upload has to have a folder. Type a title or folder name to continue...'); ?>'));
			}
		</script>
		<select id="albumselectmenu" name="albumselect" onchange="albumSelect()">
			<?php
				if ($rootrights) {
				?>
				<option value="" selected="selected" style="font-weight: bold;">/</option>
				<?php
			}
			$bglevels = array('#fff','#f8f8f8','#efefef','#e8e8e8','#dfdfdf','#d8d8d8','#cfcfcf','#c8c8c8');
			if (isset($_GET['album'])) {
				$passedalbum = sanitize($_GET['album']);
			} else {
				$passedalbum = NULL;
			}
			foreach ($albumlist as $fullfolder => $albumtitle) {
				$singlefolder = $fullfolder;
				$saprefix = "";
				$salevel = 0;
				if (!is_null($passedalbum) && ($passedalbum == $fullfolder)) {
					$selected = " selected=\"selected\" ";
				} else {
					$selected = "";
				}
				// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
				while (strstr($singlefolder, '/') !== false) {
					$singlefolder = substr(strstr($singlefolder, '/'), 1);
					$saprefix = "&nbsp; &nbsp;&raquo;&nbsp;" . $saprefix;
					$salevel++;
				}
				echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: '.$bglevels[$salevel].'; border-bottom: 1px dotted #ccc;"' : '')
						. "$selected>" . $saprefix . $singlefolder . " (" . $albumtitle . ')' . "</option>\n";
			}
			if (isset($_GET['publishalbum'])) {
				if ($_GET['publishalbum']=='true') {
					$publishchecked = ' checked="checked"';
				} else {
					$publishchecked = '';
				}
			} else {
				// get default for publishing of albums
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
				if ($albpublish) {
					$publishchecked = ' checked="checked"';
				} else {
					$publishchecked = '';
				}
			}
			?>
		</select>

		<div id="newalbumbox" style="margin-top: 5px;">
			<div>
				<label>
					<input id="newalbumcheckbox" type="checkbox" name="newalbum"<?php echo $checked; ?> onclick="albumSwitch(this.form.albumselect,false,'<?php echo gettext('That name is already used.'); ?>','<?php echo gettext('This upload has to have a folder. Type a title or folder name to continue...'); ?>')" />
					<?php echo gettext("Make a new Album"); ?>
				</label>
			</div>
			<div id="publishtext"><?php echo gettext("and"); ?>
				<label>
					<input type="checkbox" name="publishalbum" id="publishalbum" value="1" <?php echo $publishchecked; ?> /> <?php echo gettext("Publish the album so everyone can see it."); ?>
				</label>
			</div>
		</div>
		<div id="albumtext" style="margin-top: 5px;"><?php echo gettext("titled:"); ?>
			<input id="albumtitle" size="42" type="text" name="albumtitle"
										onkeyup="buttonstate(updateFolder(this, 'folderdisplay', 'autogen','<?php echo gettext('That name is already used.'); ?>','<?php echo gettext('This upload has to have a folder. Type a title or folder name to continue...'); ?>'));" />

			<div style="position: relative; margin-top: 4px;"><?php echo gettext("with the folder name:"); ?>
				<div id="foldererror" style="display: none; color: #D66; position: absolute; z-index: 100; top: 2.5em; left: 0px;"></div>
				<input id="folderdisplay" size="18" type="text" name="folderdisplay" disabled="disabled"
											onkeyup="buttonstate(validateFolder(this,'<?php echo gettext('That name is already used.'); ?>','<?php echo gettext('This upload has to have a folder. Type a title or folder name to continue...'); ?>'));" />
				<label><input type="checkbox" name="autogenfolder" id="autogen" checked="checked"
											onclick="buttonstate(toggleAutogen('folderdisplay', 'albumtitle', this));" />
											<?php echo gettext("Auto-generate"); ?></label>
				<br />
				<br />
			</div>

			<input id="folderslot" type="hidden" name="folder" value="<?php echo $passedalbum; ?>" />
		</div>

		<hr />

		<?php
		$extensions = '*.zip';
		$types = array_merge($_zp_supported_images, array_keys($_zp_extra_filetypes)); // supported extensions
		foreach ($types as $ext) {
			$extensions .= ';*.'.$ext.';*.'.strtoupper($ext);
		}
		if($uploadtype != 'http') {
			?>
			<div id="uploadboxes" style="display: none;"></div> <!--  need this so that toggling it does not fail. -->
			<div>
			<!-- UPLOADIFY JQUERY/FLASH MULTIFILE UPLOAD TEST -->
				<script type="text/javascript">
					$(document).ready(function() {
						$('#fileUpload').uploadify({
							'uploader': 'admin-uploadify/uploadify.swf',
							'cancelImg': 'images/fail.png',
							'script': 'admin-uploadify/uploader.php',
							'scriptData': {	'auth': '<?php echo md5(serialize($_zp_current_admin)); ?>' },
							'folder': '/',
							'multi': true,
							<?php
							$uploadbutton = SERVERPATH.'/'.ZENFOLDER.'/locale/'.getOption('locale').'/select_files_button.png';
							if(!file_exists($uploadbutton)) {
								$uploadbutton = SERVERPATH.'/'.ZENFOLDER.'/images/select_files_button.png';
							}
							$discard = NULL;
							$info = zp_imageDims($uploadbutton, $discard);
							if ($info['height']>60) {
								$info['height'] = round($info['height']/3);
								$rollover = "'rollover': true,";
							} else {
								$rollover = "";
							}
							$uploadbutton = str_replace(SERVERPATH, WEBPATH, $uploadbutton);
							?>
							'buttonImg': '<?php echo $uploadbutton; ?>',
							'height': '<?php echo $info['height'] ?>',
							'width': '<?php echo $info['width'] ?>',
							<?php echo $rollover; ?>
							'checkScript': 'admin-uploadify/check.php',
<?php
/* Uploadify does not really support this onCheck facility (it is unusable as implemented, this gets called fore each element
										passing the whole queue each time!)
							'onCheck':	function(event, script, queue, folder, single) {

														alert('folder: '+folder);
														alert('single: '+single);
														for (var key in queue ) {
															if (queue[key] != folder) {
																var replaceFile = confirm("Do you want to replace the file " + queue[key] + "?");
																if (!replaceFile) {
																	document.getElementById(jQuery(event.target).attr('id') + 'Uploader').cancelFileUpload(key, true,true);
																}
															}
														}
														return false;
													},
*/
?>
							'displayData': 'speed',
							'simUploadLimit': 3,
							'sizeLimit': <?php echo parse_size($maxupload); ?>,
							<?php
							if (zp_loggedin(ALBUM_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS)) {
								?>
								'onAllComplete':	function(event, data) {
																		if (data.errors) {
																			return false;
																		} else {
																			launchScript('admin-edit.php',['page=edit','subpage=1','tab=imageinfo','album='+encodeURIComponent($('#folderdisplay').val())]);
																		}
																	},
								<?php
								}
							?>
							'fileDesc': '<?php echo gettext('Zenphoto supported file types | all files'); ?>',
							'fileExt': '<?php echo $extensions.'|*.*'; ?>'
						});
					buttonstate($('#folderdisplay').val() != "");
				});
				</script>
				<div id="fileUpload">
					<?php echo gettext("You have a problem with your javascript or your browser's flash plugin is not supported."); ?>
				</div>
				<p class="buttons" id="fileUploadbuttons" style="display: none;">
					<a href="javascript:$('#fileUpload').uploadifySettings('folder','/'+$('#publishalbum').attr('checked')+':'+$('#folderdisplay').val()+':'+$('#albumtitle').val());
															$('#fileUpload').uploadifyUpload()"><img src="images/pass.png" alt="" /><?php echo gettext("Upload"); ?></a>
					<a href="javascript:$('#fileUpload').uploadifyClearQueue()"><img src="images/fail.png" alt="" /><?php echo gettext("Cancel"); ?></a>
				<br clear="all" /><br />
				</p>
				<p><?php echo gettext('If your upload does not work try the <a href="javascript:switchUploader(\'admin-upload.php?uploadtype=http\');" >http-browser single file upload</a> or use FTP instead.'); ?></p>
			</div>
			<?php
		} else {
			?>
			<div id="uploadboxes" style="display: none;">
				<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
				<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
				<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
				<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
				<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>

				<div id="place" style="display: none;"></div>
				<!-- New boxes get inserted before this -->

				<div style="display:none">
				<!-- This is the template that others are copied from -->
				<div class="fileuploadbox" id="filetemplate" ><input type="file" size="40" name="files[]" value="x" /></div>
				</div>
				<p id="addUploadBoxes"><a href="javascript:addUploadBoxes('place','filetemplate',5)" title="<?php echo gettext("Doesn't reload!"); ?>">+ <?php echo gettext("Add more upload boxes"); ?></a> <small>
				<?php echo gettext("(won't reload the page, but remember your upload limits!)"); ?></small></p>


				<p id="fileUploadbuttons" class="buttons">
					<button type="submit" value="<?php echo gettext('Upload'); ?>"
						onclick="this.form.folder.value = this.form.folderdisplay.value;" class="button">
						<img src="images/pass.png" alt="" /><?php echo gettext('Upload'); ?>
					</button>
				</p>
				<br /><br clear="all" />
			</div>
			<p><?php echo gettext('Try the <a href="javascript:switchUploader(\'admin-upload.php?uploadtype=multifile\');" >multi file upload</a>'); ?></p>
			<?php
		}
	} else {
		echo gettext("There are no albums to which you can upload.");
	}
	?>
	</div>
</form>
<script type="text/javascript">
	albumSwitch(document.uploadform.albumselect,false,'<?php echo gettext('That name is already used.'); ?>','<?php echo gettext('This upload has to have a folder. Type a title or folder name to continue...'); ?>');
	<?php
		if (isset($_GET['folderdisplay'])) {
			?>
			$('#folderdisplay').val('<?php echo sanitize($_GET['folderdisplay']); ?>');
			<?php
		}
		if (isset($_GET['albumtitle'])) {
			?>
			$('#albumtitle').val('<?php echo sanitize($_GET['albumtitle']); ?>');
			<?php
		}
		if (isset($_GET['autogen'])) {
			if ($_GET['autogen'] == 'true') {
				?>
				$('#autogen').attr('checked', 'checked');
				$('#folderdisplay').attr('disabled', 'disabled');
				if ($('#albumtitle').val() != '') {
					$('#foldererror').hide();
					<?php
					if($uploadtype == 'http') {
						?>
						$('#uploadboxes').show();
						buttonstate(true);
						<?php
					}
					?>
				}
				<?php
			} else {
				?>
				$('#autogen').removeAttr('checked');
				$('#folderdisplay').removeAttr('disabled');
				if ($('#folderdisplay').val() != '') {
					<?php
					if($uploadtype == 'http') {
						?>
						$('#uploadboxes').show();
						buttonstate(true);
						<?php
					}
					?>
					$('#foldererror').hide();
					buttonstate(false);
				}
				<?php
			}
		}
	?>
</script>
</div>
</div>
<?php
printAdminFooter();
?>
</body>
</html>




