<?php
/**
 * provides the Options tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
if (!is_null(getOption('admin_reset_date'))) {
	if (!$_zp_loggedin) { // prevent nefarious access to this page.
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
		exit();
	}
}
$gallery = new Gallery();
$_GET['page'] = 'options';

/* handle posts */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	$themeswitch = false;
	if ($action == 'deleteadmin') {
		$id = $_GET['adminuser'];
		$sql = "DELETE FROM ".prefix('administrators')." WHERE `id`=$id";
		query($sql);
		$sql = "DELETE FROM ".prefix('admintoalbum')." WHERE `adminid`=$id";
		query($sql);
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-options.php?deleted");
		exit();
	} else if ($action == 'saveoptions') {
		$table = NULL;
		$wm = getOption('watermark_image');
		$vwm = getOption('video_watermark_image');
		$wmo = getOption('perform_watermark');
		$vwmo = getOption('perform_video_watermark');
		$woh = getOption('watermark_h_offset');
		$wow = getOption('watermark_w_offset');
		$ws = getOption('watermark_scale');
		$wus = getOption('watermark_allow_upscale');
		$notify = '';
		$returntab = '';

		/*** admin options ***/
		if (isset($_POST['saveadminoptions'])) {
			for ($i = 0; $i < $_POST['totaladmins']; $i++) {
				$pass = trim($_POST[$i.'-adminpass']);
				$user = trim($_POST[$i.'-adminuser']);
				if (!empty($user)) {
					if ($pass == trim($_POST[$i.'-adminpass_2'])) {
						$admin_n = trim($_POST[$i.'-admin_name']);
						$admin_e = trim($_POST[$i.'-admin_email']);
						if (isset($_POST[$i.'-main_rights'])) $main_r = MAIN_RIGHTS; else $main_r = 0;
						if (isset($_POST[$i.'-view_rights'])) $view_r = VIEWALL_RIGHTS; else $view_r = 0;
						if (isset($_POST[$i.'-upload_rights'])) $upload_r = UPLOAD_RIGHTS; else $upload_r = 0;
						if (isset($_POST[$i.'-comment_rights'])) $comment_r = COMMENT_RIGHTS; else $comment_r = 0;
						if (isset($_POST[$i.'-edit_rights'])) $edit_r = EDIT_RIGHTS; else $edit_r = 0;
						if (isset($_POST[$i.'-all_album_rights'])) $all_album_r = ALL_ALBUMS_RIGHTS; else $all_album_r = 0;
						if (isset($_POST[$i.'-themes_rights'])) $themes_r = THEMES_RIGHTS; else $themes_r = 0;
						if (isset($_POST[$i.'-options_rights'])) $options_r = OPTIONS_RIGHTS; else $options_r = 0;
						if (isset($_POST[$i.'-zenpage_rights'])) $zenpage_r = ZENPAGE_RIGHTS; else $zenpage_r = 0;
						if (isset($_POST[$i.'-admin_rights'])) $admin_r = ADMIN_RIGHTS; else $admin_r = 0;
						if (!isset($_POST['alter_enabled'])) {
							$rights = NO_RIGHTS + $main_r + $view_r + $upload_r + $comment_r + $edit_r + $all_album_r + $themes_r + $options_r + $zenpage_r + $admin_r;
							if ($rights & ALL_ALBUMS_RIGHTS) $rights = $rights | EDIT_RIGHTS;
							$managedalbums = array();
							$l = strlen($albumsprefix = 'managed_albums_'.$i.'_');
							foreach ($_POST as $key => $value) {
								$key = postIndexDecode($key);
								if (substr($key, 0, $l) == $albumsprefix) {
									if ($value) {
										$managedalbums[] = substr($key, $l);
									}
								}
							}
							if (count($managedalbums > 0)) {
								$albums = array_unique($managedalbums);
							} else {
								$albums = NULL;
							}
						} else {
							$rights = null;
							$albums = NULL;
						}
						if (empty($pass)) {
							$pwd = null;
						} else {
							$pwd = md5($_POST[$i.'-adminuser'] . $pass);
						}
						saveAdmin($user, $pwd, $admin_n, $admin_e, $rights, $albums);
						if ($i == 0) {
							setOption('admin_reset_date', '1');
						}
					} else {
						$notify = '?mismatch=password';
					}
				}
			}
			$returntab = "#tab_admin";
		}

		/*** Gallery options ***/
		if (isset($_POST['savegalleryoptions'])) {
			$tags = $_POST['allowed_tags'];
			$test = "(".$tags.")";
			$a = parseAllowedTags($test);
			if ($a !== false) {
				setOption('allowed_tags', $tags);
				$notify = '';
			} else {
				$notify = '?tag_parse_error';
			}
			setOption('gallery_title', process_language_string_save('gallery_title', 2));
			setoption('Gallery_description', process_language_string_save('Gallery_description', 1));
			setOption('website_title', process_language_string_save('website_title', 2));
			$web = sanitize($_POST['website_url'],3);
			setOption('website_url', $web);
			setOption('time_offset', sanitize($_POST['time_offset']),3);
			setBoolOption('mod_rewrite', isset($_POST['mod_rewrite']));
			setOption('mod_rewrite_image_suffix', sanitize($_POST['mod_rewrite_image_suffix']),3);
			setOption('server_protocol', sanitize($_POST['server_protocol']),3);
			setOption('charset', sanitize($_POST['charset']),3);
			setBoolOption('album_use_new_image_date', isset($_POST['album_use_new_image_date']));
			setOption('gallery_sorttype', sanitize($_POST['gallery_sorttype']),3);
			if ($_POST['gallery_sorttype'] == 'Manual') {
				setBoolOption('gallery_sortdirection', 0);
			} else {
				setBoolOption('gallery_sortdirection', isset($_POST['gallery_sortdirection']));
			}
			setOption('feed_items', sanitize($_POST['feed_items']),3);
			setOption('feed_imagesize', sanitize($_POST['feed_imagesize']),3);
			setBoolOption('login_user_field', isset($_POST['login_user_field']));
			$serachfields = 0;
			foreach ($_POST as $key=>$value) {
				if (strpos($key, '_SEARCH_') !== false) {
					$serachfields = $serachfields | $value;
				}
			}
			setOption('search_fields', $serachfields);
			$olduser = getOption('gallery_user');
			$newuser = sanitize($_POST['gallery_user'],3);
			if (!empty($newuser)) setOption('login_user_field', 1);
			$pwd = trim($_POST['gallerypass']);
			$fail = '';
			if ($olduser != $newuser) {
				if ($pwd != $_POST['gallerypass_2']) {
					$_POST['gallerypass'] = $pwd;  // invalidate, user changed but password not set
					$pwd2 = trim($_POST['gallerypass_2']);
					if (!empty($newuser)  && empty($pwd) && empty($pwd2)) $fail = '?mismatch=user_gallery';
				}
			}
			if ($_POST['gallerypass'] == $_POST['gallerypass_2']) {
				setOption('gallery_user', $newuser);
				if (empty($pwd)) {
					if (empty($_POST['gallerypass'])) {
						setOption('gallery_password', NULL);  // clear the gallery password
					}
				} else {
					setOption('gallery_password', md5($newuser.$pwd));
				}
			} else {
				if (empty($fail)) {
					$notify = '?mismatch=gallery';
				} else {
					$notify = $fail;
				}
			}
			$olduser = getOption('search_user');
			$newuser = sanitize($_POST['search_user'],3);
			if (!empty($newuser)) setOption('login_user_field', 1);
			$pwd = trim($_POST['searchpass']);
			if ($olduser != $newuser) {
				if ($pwd != $_POST['searchpass_2']) {
					$pwd2 = trim($_POST['searchpass_2']);
					$_POST['searchpass'] = $pwd;  // invalidate, user changed but password not set
					if (!empty($newuser) && empty($pwd) && empty($pwd2)) $fail = '?mismatch=user_search';
				}
			}
			if ($_POST['searchpass'] == $_POST['searchpass_2']) {
				setOption('search_user',$newuser);
				if (empty($pwd)) {
					if (empty($_POST['searchpass'])) {
						setOption('search_password', NULL);  // clear the gallery password
					}
				} else {
					setOption('search_password', md5($newuser.$pwd));
				}
			} else {
				if (empty($notify)) {
					$notify = '?mismatch=search';
				} else {
					$notify = $fail;
				}
			}
			setOption('gallery_hint', process_language_string_save('gallery_hint', 3));
			setOption('search_hint', process_language_string_save('search_hint', 3));
			setBoolOption('persistent_archive', isset($_POST['persistent_archive']));
			setBoolOption('album_session', isset($_POST['album_session']));
			$oldloc = getOption('locale', true); // get the option as stored in the database, not what might have been set by a cookie
			$newloc = sanitize($_POST['locale'],3);
			if ($newloc != $oldloc) {
				$cookiepath = WEBPATH;
				if (WEBPATH == '') { $cookiepath = '/'; }
				zp_setCookie('dynamic_locale', $newloc, time()-368000, $cookiepath);  // clear the language cookie
				$encoding = getOption('charset');
				if (empty($encoding)) $encoding = 'UTF-8';
				$result = setlocale(LC_ALL, $newloc.'.'.$encoding);
				if ($result === false) {
					$result = setlocale(LC_ALL, $newloc);
				}
				if (!empty($newloc) && ($result === false)) {
					$notify = '?local_failed='.$newloc;
				}
				setOption('locale', $newloc);
			}
			setBoolOption('multi_lingual', isset($_POST['multi_lingual']));
			$f = sanitize($_POST['date_format_list'],3);
			if ($f == 'custom') $f = sanitize($_POST['date_format'],3);
			setOption('date_format', $f);
			setBoolOption('thumb_select_images', isset($_POST['thumb_select_images']));
			$returntab = "#tab_gallery";
		}

		/*** Image options ***/
		if (isset($_POST['saveimageoptions'])) {
			setOption('image_quality', sanitize($_POST['image_quality']),3);
			setOption('thumb_quality', sanitize($_POST['thumb_quality']),3);
			setBoolOption('image_allow_upscale', isset($_POST['image_allow_upscale']));
			setBoolOption('thumb_sharpen', isset($_POST['thumb_sharpen']));
			setBoolOption('image_sharpen', isset($_POST['image_sharpen']));
			setBoolOption('perform_watermark', isset($_POST['perform_watermark']));
			setOption('watermark_image', 'watermarks/' . sanitize($_POST['watermark_image'],3) . '.png');
			setOption('watermark_scale', sanitize($_POST['watermark_scale']),3);
			setBoolOption('watermark_allow_upscale', isset($_POST['watermark_allow_upscale']));
			setOption('watermark_h_offset', sanitize($_POST['watermark_h_offset']),3);
			setOption('watermark_w_offset', sanitize($_POST['watermark_w_offset']),3);
			setBoolOption('perform_video_watermark', isset($_POST['perform_video_watermark']));
			setOption('video_watermark_image', 'watermarks/' . sanitize($_POST['video_watermark_image'],3) . '.png');
			setOption('full_image_quality', sanitize($_POST['full_image_quality']),3);
			setBoolOption('cache_full_image', isset($_POST['cache_full_image']));
			setOption('protect_full_image', sanitize($_POST['protect_full_image']),3);
			setBoolOption('hotlink_protection', isset($_POST['hotlink_protection']));
			setBoolOption('use_lock_image', isset($_POST['use_lock_image']));
			setOption('image_sorttype', sanitize($_POST['image_sorttype'],3));
			setBoolOption('image_sortdirection', isset($_POST['image_sortdirection']));
			$returntab = "#tab_image";
		}
		/*** Comment options ***/

		if (isset($_POST['savecommentoptions'])) {
			setOption('spam_filter', sanitize($_POST['spam_filter'],3));
			setBoolOption('email_new_comments', isset($_POST['email_new_comments']));
			setBoolOption('comment_name_required', isset($_POST['comment_name_required']));
			setBoolOption('comment_email_required',isset( $_POST['comment_email_required']));
			setBoolOption('comment_web_required', isset($_POST['comment_web_required']));
			setBoolOption('Use_Captcha', isset($_POST['Use_Captcha']));
			$returntab = "#tab_comments";

		}
		/*** Theme options ***/
		if (isset($_POST['savethemeoptions'])) {
			$returntab = "#tab_theme";
			// all theme specific options are custom options, handled below
			if (!empty($_POST['themealbum'])) {
				$alb = sanitize($_POST['themealbum']);
				$table = new Album(new Gallery(), $alb);
				$returntab = '&themealbum='.urlencode($alb).'#tab_theme';
				$themeswitch = $alb != sanitize($_POST['old_themealbum']);
			} else {
				$table = NULL;
				$themeswitch = sanitize($_POST['old_themealbum']) != '';
			}
			if ($themeswitch) {
				$notify = '?switched';
			} else {
				
				$cw = getOption('thumb_crop_width');
				$ch = getOption('thumb_crop_height');
				if (isset($_POST['image_size'])) setThemeOption($table, 'image_size', sanitize($_POST['image_size'],3));
				setBoolThemeOption($table, 'image_use_longest_side', isset($_POST['image_use_longest_side']));
				if (isset($_POST['thumb_size'])) setThemeOption($table, 'thumb_size', sanitize($_POST['thumb_size'],3));
				setBoolThemeOption($table, 'thumb_crop', isset($_POST['thumb_crop']));
				if (isset($_POST['thumb_crop_width'])) setThemeOption($table, 'thumb_crop_width', $ncw = sanitize($_POST['thumb_crop_width'],3));
				if (isset($_POST['thumb_crop_height'])) setThemeOption($table, 'thumb_crop_height', $nch = sanitize($_POST['thumb_crop_height'],3));
				if (isset($_POST['albums_per_page'])) setThemeOption($table, 'albums_per_page', sanitize($_POST['albums_per_page'],3));
				if (isset($_POST['images_per_page'])) setThemeOption($table, 'images_per_page', sanitize($_POST['images_per_page'],3));
				if (isset($_POST['custom_index_page'])) setThemeOption($table, 'custom_index_page', sanitize($_POST['custom_index_page'], 3));
				
				if ($nch != $ch || $ncw != $cw) { // the crop height/width has been changed
					$sql = 'UPDATE '.prefix('images').' SET `thumbX`=NULL,`thumbY`=NULL,`thumbW`=NULL,`thumbH`=NULL WHERE `thumbY` IS NOT NULL';
					query($sql);
					$wmo = 99; // force cache clear as well.
				}
			}
}
		/*** Plugin Options ***/
		if (isset($_POST['savepluginoptions'])) {
			// all plugin options are handled by the custom option code.
			$returntab = "#tab_plugin";
		}
		/*** custom options ***/
		if (!$themeswitch) { // was really a save.
			foreach ($_POST as $postkey=>$value) {
				if (preg_match('/^'.CUSTOM_OPTION_PREFIX.'/', $postkey)) { // custom option!
					$key = substr($postkey, strpos($postkey, '-')+1);
					$switch = substr($postkey, strlen(CUSTOM_OPTION_PREFIX), -strlen($key)-1);
					switch ($switch) {
						case 'text':
							$value = process_language_string_save($key, 1);
							break;
						case 'chkbox':
							if (isset($_POST[$key])) {
								$value = 1;
							} else {
								$value = 0;
							}
							break;
						default:
							if (isset($_POST[$key])) {
								$value = sanitize($_POST[$key], 1);
							} else {
								$value = '';
							}
							break;
					}
					setThemeOption($table, $key, $value);
				}
			}
		}

		if (($wmo != getOption('perform_watermark')) ||
		($vwmo != getOption('perform_video_watermark')) ||
		($woh != getOption('watermark_h_offset')) ||
		($wow != getOption('watermark_w_offset'))  ||
		($wm != getOption('watermark_image')) ||
		($ws != getOption('watermark_scale')) ||
		($wus != getOption('watermark_allow_upscale')) ||
		($vwm != getOption('video_watermark_image'))) {
			$gallery->clearCache(); // watermarks (or lack there of) are cached, need to start fresh if the options haave changed
		}
		if (empty($notify)) $notify = '?saved';
		header("Location: " . $notify . $returntab);
		exit();

	}

}
printAdminHeader();
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs('options');
echo "\n" . '<div id="content">';
if ($_zp_null_account = ($_zp_loggedin == ADMIN_RIGHTS)) {
	echo "<div class=\"errorbox space\">";
	echo "<h2>".gettext("Password reset request.<br/>You may now set admin usernames and passwords.")."</h2>";
	echo "</div>";
}

/* Page code */
?>
<div id="container">
<?php
	if (isset($_GET['saved'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Saved")."</h2>";
		echo '</div>';
	}
?>
<div id="mainmenu">
<ul>
	<li><a href="#tab_admin"><span><?php echo gettext("admin information"); ?></span></a></li>
	<?php
	if (!$_zp_null_account) {
		if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	?>
			<li><a href="#tab_gallery"><span><?php echo gettext("gallery configuration"); ?></span></a></li>
			<li><a href="#tab_image"><span><?php echo gettext("image display"); ?></span></a></li>
			<li><a href="#tab_comments"><span><?php echo gettext("comment configuration"); ?></span></a></li>
		<?php
		}
		if ($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS)) {
		?>
			<li><a href="#tab_theme"><span><?php echo gettext("theme options"); ?></span></a></li>
		<?php
		}
		if ($_zp_loggedin & ADMIN_RIGHTS) {
		?>
		<li><a href="#tab_plugin"><span><?php echo gettext("plugin options"); ?></span></a></li>
	<?php
		}
	}
	?>
</ul>

</div>
<div id="tab_admin">
<?php
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		$alterrights = '';
		$admins = getAdministrators();
		if (!$_zp_null_account || count($admins) == 0) {
			$admins [''] = array('id' => -1, 'user' => '', 'pass' => '', 'name' => '', 'email' => '', 'rights' => ALL_RIGHTS ^ ALL_ALBUMS_RIGHTS);
		}
	} else {
		$alterrights = ' DISABLED';
		global $_zp_current_admin;
		$admins = array($_zp_current_admin['user'] => $_zp_current_admin);
		echo "<input type=\"hidden\" name=\"alter_enabled\" value=\"no\" />";
	}
	if (isset($_GET['mismatch'])) {
		if ($_GET['mismatch'] == 'mismatch') {
			$msg = gettext('You must supply a password');
		} else {
			$msg = gettext('Your passwords did not match');
		}
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>$msg</h2>";
		echo '</div>';
	}
	if (isset($_GET['deleted'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>Deleted</h2>";
		echo '</div>';
	}
	?> 
<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
<input type="hidden" name="saveadminoptions" value="yes" /> 
<input type="hidden" name="totaladmins" value="<?php echo count($admins); ?>" />
<table class="bordered"> <!-- main table -->
	<tr>
		<th width="20%">
		<h2><?php echo gettext("Admin login information"); ?></h2>
		</th>
		<th width="80%">
			<span style="font-weight: normal"> <a href="javascript:toggleExtraInfo('','user',true);"><?php echo gettext('Expand all');?></a>
		| <a href="javascript:toggleExtraInfo('','user',false);"><?php echo gettext('Collapse all');?></a></span>
		</th>
	</tr>
	<?php
	$id = 0;
	$albumlist = $gallery->getAlbums();
	foreach($admins as $user) {
		$userid = $user['user'];
		$master = '&nbsp;';
		if ($id == 0) {
			if ($_zp_loggedin & ADMIN_RIGHTS) {
				$master = " (<em>".gettext("Master")."</em>)";
				$user['rights'] = $user['rights'] | ADMIN_RIGHTS;
				if ($_zp_null_account) $user['rights'] = $user['rights'] | ALL_ALBUMS_RIGHTS;
			}
		}
		$current =  ($user['id'] == $_zp_current_admin['id']) || $_zp_null_account;
		if (count($admins) > 2) {
			$background = ($current) ? " background-color: #ECF1F2;" : "";
		} else {
			$background = '';
		}
		?>
	<tr>
		<td colspan="2" style="margin: 0pt; padding: 0pt;">
		<table class="bordered" style="border: 0" id='user-<?php echo $id;?>'> <!-- individual admin table -->
		<tr>
			<td width="150" style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>">
				<input type="hidden" name="<?php echo $id ?>-adminuser" value="<?php echo $userid ?>" />
				<span <?php if ($current) echo 'style="display:none;"'; ?> class="userextrashow">
				<a href="javascript:toggleExtraInfo('<?php echo $id;?>','user',true);"><?php
				if (empty($userid)) {
					echo gettext("Add New Admin");
				} else {
					echo $userid; 
				}
				?></a></span>
				<span <?php if ($current) echo 'style="display:block;"'; else echo 'style="display:none;"'; ?> class="userextrahide">
				<a href="javascript:toggleExtraInfo('<?php echo $id;?>','user',false);"><?php 
				if (empty($userid)) {
					echo gettext("Add New Admin");
				} else {
					echo $userid;
				}?></a></span>
			</td>
			<td width="330" style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>">
			<?php 
			if (empty($userid)) {
			?>
				<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminuser" value="" />
			<?php
			} else {
				echo $master;
			}
 			?>
 			</td>
			<td style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" >
				<?php 
				if(!empty($userid) && count($admins) > 2) { 
					$msg = gettext('Are you sure you want to delete this user?');
					if ($id == 0) {
						$msg .= ' '.gettext('This is the master user account. If you delete it another user will be promoted to master user.');
					}
				?>
				<a href="javascript: if(confirm(<?php echo "'".$msg."'"; ?>)) { window.location='?action=deleteadmin&adminuser=<?php echo $user['id']; ?>'; }"
					title="<?php echo gettext('Delete this user.'); ?>" style="color: #c33;"> <img
					src="images/fail.png" style="border: 0px;" alt="Delete" /></a> 
				<?php
				}
				?>
				&nbsp;
				</td>
			</tr>
	<tr <?php if (!$current) echo 'style="display:none;"'; ?> class="userextrainfo">
		<td width="150" <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Password:"); ?><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?></td>
		<td width="330" <?php if (!empty($background)) echo "style=\"$background\""; ?>><?php $x = $user['pass']; if (!empty($x)) { $x = '          '; } ?>
		<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminpass"
			value="<?php echo $x; ?>" /><br />
		<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminpass_2"
			value="<?php echo $x; ?>" /></td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		<table class="checkboxes" > <!-- checkbox table -->
			<tr>
				<td style="padding-bottom: 3px;<?php echo $background; ?>" colspan="5">
				<strong><?php echo gettext("Rights"); ?></strong>:
				</td>
			</tr>
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-admin_rights"
					value=<?php echo ADMIN_RIGHTS; if ($user['rights'] & ADMIN_RIGHTS) echo ' checked'; 
					echo $alterrights; ?>> <?php echo gettext("User admin"); ?></td>
					
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-options_rights"
					value=<?php echo OPTIONS_RIGHTS; if ($user['rights'] & OPTIONS_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("Options"); ?></td>
					
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-zenpage_rights"
					<?php if(!getOption('zp_plugin_zenpage')) echo "DISABLED ";?>value=<?php echo ZENPAGE_RIGHTS; if ($user['rights'] & ZENPAGE_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("Zenpage"); ?></td>
			</tr>
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-themes_rights"
					value=<?php echo THEMES_RIGHTS; if ($user['rights'] & THEMES_RIGHTS) echo ' checked';
					echo$alterrights; ?>> <?php echo gettext("Themes"); ?></td>

				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-all_album_rights"
					value=<?php echo ALL_ALBUMS_RIGHTS; if ($user['rights'] & ALL_ALBUMS_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("Manage all albums"); ?></td>

				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-edit_rights"
					value=<?php echo EDIT_RIGHTS; if ($user['rights'] & EDIT_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("Edit"); ?></td>
			</tr>
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-comment_rights"
					value=<?php echo COMMENT_RIGHTS; if ($user['rights'] & COMMENT_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("Comment"); ?></td>

				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-upload_rights"
					value=<?php echo UPLOAD_RIGHTS; if ($user['rights'] & UPLOAD_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("Upload"); ?></td>

				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<input type="checkbox" name="<?php echo $id ?>-view_rights"
					value=<?php echo VIEWALL_RIGHTS; if ($user['rights'] & VIEWALL_RIGHTS) echo ' checked'; 
					echo$alterrights; ?>> <?php echo gettext("View all albums"); ?></td>
			</tr>
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-main_rights"
					value=<?php echo MAIN_RIGHTS; if ($user['rights'] & MAIN_RIGHTS) echo ' checked';echo$alterrights; ?>> <?php echo gettext("Overview"); ?></td>
			</tr>
		</table> <!-- end checkbox table -->

		</td>
	</tr>
	<tr <?php if (!$current) echo 'style="display:none;"'; ?> class="userextrainfo">
		<td width="150" <?php if (!empty($background)) echo "style=\"$background\""; ?>>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Full name:"); ?> <br />
			<br />
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("email:"); ?>
		</td>
		<td width="330" <?php if (!empty($background)) echo "style=\"$background\""; ?>>
			<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-admin_name"
				value="<?php echo $user['name'];?>" /> <br />
			<br />
			<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-admin_email"
				value="<?php echo $user['email'];?>" />
		</td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		<table> <!-- album rights table -->
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?> >
				<?php
					if (!($user['rights'] & ALL_ALBUMS_RIGHTS) && !$current) {
						$cv = array();
						$sql = "SELECT ".prefix('albums').".`folder` FROM ".prefix('albums').", ".
						prefix('admintoalbum')." WHERE ".prefix('admintoalbum').".adminid=".
						$user['id']." AND ".prefix('albums').".id=".prefix('admintoalbum').".albumid";
						$currentvalues = query_full_array($sql);
						foreach($currentvalues as $albumitem) {
							$cv[] = $albumitem['folder'];
						}
						$rest = array_diff($albumlist, $cv);
						$prefix = 'managed_albums_'.$id.'_';
						echo gettext("Managed albums:");
						echo '<ul class="albumchecklist">'."\n";;
						generateUnorderedListFromArray($cv, $cv, $prefix, $alterrights);
						if (empty($alterrights)) {
							generateUnorderedListFromArray(array(), $rest, $prefix);
						}
						echo '</ul>';
					} else {
						if (!empty($master)) {
							echo gettext("This account's username and email are used as contact data in the RSS feeds.");
						}
					}
				?>
				</td>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
				<?php
					if (!($user['rights'] & ALL_ALBUMS_RIGHTS) && !$current) {
						if (!empty($alterrights)) {
							echo gettext("You may manage these albums subject to the above rights.");
						} else {
							echo gettext("Select one or more albums for the administrator to manage.").' ';
							echo gettext("Administrators with <em>User admin</em> or <em>Manage all albums</em> rights can manage all albums. All others may manage only those that are selected.");
						}
					}
				?>
			</td>
			</tr>
		</table> <!-- album rights table -->
		</td>
		</tr>

</table> <!-- end individual admin table -->
</td>
</tr>
<?php
	$id++;
}
?>
</table> <!-- main admin table end -->
<input type="submit" value= <?php echo gettext('save') ?> />
</form>
</div>
<!-- end of tab_admin div -->

<?php
if (!$_zp_null_account) {
if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
?>
<div id="tab_gallery">
<?php
	if (isset($_GET['tag_parse_error'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your Allowed tags change did not parse successfully.")."</h2>";
		echo '</div>';
	}
	?>
	<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
	<input	type="hidden" name="savegalleryoptions" value="yes" /> <?php
	if (isset($_GET['mismatch'])) {
		echo '<div class="errorbox" id="fade-message">';
		switch ($_GET['mismatch']) {
			case 'gallery':
			case 'search':
				echo  "<h2>". sprintf(gettext("Your %s passwords were empty or did not match"), $_GET['mismatch'])."</h2>";
				break;
			case 'user_gallery':
				echo  "<h2>". gettext("You must supply a password for the Gallery guest user")."</h2>";
				break;
			case 'user_search':
				echo  "<h2>". gettext("You must supply a password for the Search guest user")."</h2>";
				break;
		}
		echo '</div>';
	}
	if (isset($_GET['local_failed'])) {
		$locale = $_GET['local_failed'];
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".
					sprintf(gettext("<em>%s</em> is not available."),$_zp_languages[$locale]).
					' '.sprintf(gettext("The locale %s is not supported on your server."),$locale).
					'<br />'.gettext('See the troubleshooting guide on zenphoto.org for details.').
					"</h2>";
		echo '</div>';
	}
	if (isset($_GET['badurl'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your Website URL is not valid")."</h2>";
		echo '</div>';
	}
	?>
<table class="bordered">
	<tr>
		<th colspan="3">
		<h2><?php echo gettext("General Gallery Configuration"); ?></h2>
		</th>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="<?php echo gettext('save'); ?>" /></td>
		<td></td>
	</tr>
	<tr>
		<td width="175"><?php echo gettext("Gallery title:"); ?></td>
		<td width="350">
		<?php print_language_string_list(getOption('gallery_title'), 'gallery_title', false) ?>
		</td>
		<td><?php echo gettext("What you want to call your photo gallery."); ?></td>
	</tr>
	<tr>
		<td width="175"><?php echo gettext("Gallery description:"); ?></td>
		<td width="350">
		<?php print_language_string_list(getOption('Gallery_description'), 'Gallery_description', true, NULL, 'texteditor') ?>
		</td>
		<td><?php echo gettext("A brief description of your gallery. Some themes may display this text."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Enable user name login field:"); ?></td>
		<td><input type="checkbox" name="login_user_field" value="1"
		<?php echo checked('1', getOption('login_user_field')); ?> /></td>
		<td><?php echo gettext("This option places a field on the gallery (search, album) login form for entering a user name. This is necessary if you have set guest login user names. It is also useful to allow Admin users to log in on these pages rather than at the Admin login."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Gallery guest user:"); ?>    </td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="gallery_user" value="<?php echo htmlspecialchars(getOption('gallery_user')); ?>" />		</td>
		<td><?php echo gettext("User ID for the gallery guest user") ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Gallery password:"); ?><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php gettext("(repeat)"); ?>
		</td>
		<td>
		<?php $x = getOption('gallery_password'); if (!empty($x)) { $x = '          '; } ?>
		<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="gallerypass"
			value="<?php echo $x; ?>" /><br />
		<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="gallerypass_2"
			value="<?php echo $x; ?>" /></td>
		<td><?php echo gettext("Master password for the gallery. If this is set, visitors must know this password to view the gallery."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Gallery password hint:"); ?></td>
		<td>
		<?php print_language_string_list(getOption('gallery_hint'), 'gallery_hint', false) ?>
		</td>
		<td><?php echo gettext("A reminder hint for the password."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Search guest user:"); ?>    </td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="search_user" value="<?php echo htmlspecialchars(getOption('search_user')); ?>" />		</td>
		<td><?php echo gettext("User ID for the search guest user") ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Search password:"); ?><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
		</td>
		<td><?php $x = getOption('search_password'); if (!empty($x)) { $x = '          '; } ?>
		<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="searchpass"
			value="<?php echo $x; ?>" /><br />
		<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="searchpass_2"
			value="<?php echo $x; ?>" /></td>
		<td><?php echo gettext("Password for the the search guest user. If this is set, visitors must know this password to view search results."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Search password hint:"); ?></td>
		<td>
		<?php print_language_string_list(getOption('search_hint'), 'search_hint', false) ?>
		</td>
		<td><?php echo gettext("A reminder hint for the password."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Website title:"); ?></td>
		<td>
		<?php print_language_string_list(getOption('website_title'), 'website_title', false) ?>
		</td>
		<td><?php echo gettext("Your web site title."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Website url:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="website_url"
			value="<?php echo htmlspecialchars(getOption('website_url'));?>" /></td>
		<td><?php echo gettext("This is used to link back to your main site, but your theme must	support it."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Server protocol:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="server_protocol"
			value="<?php echo htmlspecialchars(getOption('server_protocol'));?>" /></td>
		<td><?php echo gettext("If you're running a secure server, change this to"); ?> <em>https</em>
		<?php echo gettext("(Most people will leave this alone.)"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Time offset (hours):"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="time_offset"
			value="<?php echo htmlspecialchars(getOption('time_offset'));?>" /></td>
		<td><?php echo gettext("If you're in a different time zone from your server, set the	offset in hours."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Enable mod_rewrite:"); ?></td>
		<td><input type="checkbox" name="mod_rewrite" value="1"
		<?php echo checked('1', getOption('mod_rewrite')); ?> /></td>
		<td><?php echo gettext("If you have Apache <em>mod_rewrite</em>, put a checkmark here, and	you'll get nice cruft-free URLs."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Mod_rewrite Image suffix:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="mod_rewrite_image_suffix"
			value="<?php echo htmlspecialchars(getOption('mod_rewrite_image_suffix'));?>" /></td>
		<td><?php echo gettext("If <em>mod_rewrite</em> is checked above, zenphoto will appended	this to the end (helps search engines). Examples: <em>.html, .php,	/view</em>, etc."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Language:"); ?></td>
		<td><select id="locale" name="locale">
			<?php	generateLanguageOptionList(true);	?>
		</select>
		<input type="checkbox" name="multi_lingual" value="1"	<?php echo checked('1', getOption('multi_lingual')); ?> />
		<?php echo gettext('Multi-lingual'); ?>
		</td>
		<td>
		<?php
		echo gettext("The language to display text in. (Set to <em>HTTP Accept Language</em> to use the language preference specified by the viewer's browser.)");
		echo ' '.gettext("Set <em>Multi-lingual</em> to enable multiple languages for database fields.");
		echo ' '.gettext("<strong>Note:</strong> if you have created multi-language strings, uncheck this option, then save anything, you will loose your strings.");
		?>
		</td>
	</tr>
	<tr>
		<td><?php echo gettext("Date format:"); ?></td>
		<td>
			<script type="text/javascript">
			function showfield(obj) {
				no = obj.options[obj.selectedIndex].value;
				document.getElementById('customTextBox').style.display = 'none';
				if(no=='custom')
					document.getElementById('customTextBox').style.display = 'block';
			}
			</script>
			<select id="date_format_list" name="date_format_list" onchange="showfield(this)">
			<?php
			$formatlist = array(gettext('Custom')=>'custom',
					gettext('02/25/08 15:30')=>'%d/%m/%y %H:%M',
					gettext('02/25/08')=>'%d/%m/%y',
					gettext('02/25/2008 15:30')=>'%d/%m/%Y %H:%M',
					gettext('02/25/2008')=>'%d/%m/%Y',
					gettext('02-25-08 15:30')=>'%d-%m-%y %H:%M',
					gettext('02-25-08')=>'%d-%m-%y',
					gettext('02-25-2008 15:30')=>'%d-%m-%Y %H:%M',
					gettext('02-25-2008')=>'%d-%m-%Y',
					gettext('2008. February 25. 15:30')=>'%Y. %B %d. %H:%M',
					gettext('2008. February 25.')=>'%Y. %B %d.',
					gettext('2008-02-25 15:30')=>'%Y-%m-%d %H:%M',
					gettext('2008-02-25')=>'%Y-%m-%d',
					gettext('25 Feb 2008 15:30')=>'%d %B %Y %H:%M',
					gettext('25 Feb 2008')=>'%d %B %Y',
					gettext('25 February 2008 15:30')=>'%d %B %Y %H:%M',
					gettext('25 February 2008')=>'%d %B %Y',
					gettext('25. Feb 2008 15:30')=>'%d. %B %Y %H:%M',
					gettext('25. Feb 2008')=>'%d. %B %Y',
					gettext('25. Feb. 08 15:30')=>'%d. %b %y %H:%M',
					gettext('25. Feb. 08')=>'%d. %b %y',
					gettext('25. February 2008 15:30')=>'%d. %B %Y %H:%M',
					gettext('25. February 2008')=>'%d. %B %Y',
					gettext('25.02.08 15:30')=>'%d.%m.%y %H:%M',
					gettext('25.02.08')=>'%d.%m.%y',
					gettext('25.02.2008 15:30')=>'%d.%m.%Y %H:%M',
					gettext('25.02.2008')=>'%d.%m.%Y',
					gettext('25-02-08 15:30')=>'%d-%m-%y %H:%M',
					gettext('25-02-08')=>'%d-%m-%y',
					gettext('25-02-2008 15:30')=>'%d-%m-%Y %H:%M',
					gettext('25-02-2008')=>'%d-%m-%Y',
					gettext('25-Feb-08 15:30')=>'%d-%b-%y %H:%M',
					gettext('25-Feb-08')=>'%d-%b-%y',
					gettext('25-Feb-2008 15:30')=>'%d-%b-%Y %H:%M',
					gettext('25-Feb-2008')=>'%d-%b-%Y',
					gettext('Feb 25, 2008 15:30')=>'%b %d, %Y %H:%M',
					gettext('Feb 25, 2008')=>'%b %d, %Y',
					gettext('February 25, 2008 15:30')=>'%B %d, %Y %H:%M',
					gettext('February 25, 2008')=>'%B %d, %Y');
			$cv = getOption("date_format");
			$flip = array_flip($formatlist);
			if (isset($flip[$cv])) {
				$dsp = 'none';
			} else {
				$dsp = 'block';
			}
			if (array_search($cv, $formatlist) === false) $cv = 'custom';
			generateListFromArray(array($cv), $formatlist);
			?>
			</select><br />
			<div id="customTextBox" class="customText" style="display:<?php echo $dsp; ?>">
			<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="date_format"
			value="<?php echo htmlspecialchars(getOption('date_format'));?>" />
			</div>
			</td>
		<td><?php echo gettext('Format for dates. Select from the list or set to <code>custom</code> and provide a <a href="http://us2.php.net/manual/en/function.strftime.php"><code>strftime()</code></a> format string in the text box.'); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Charset:"); ?></td>
		<td><select id="charset" name="charset">
			<?php generateListFromArray(array(getOption('charset')), array_flip($charsets)) ?>
		</select></td>
		<td><?php echo gettext("The character encoding to use internally. Leave at <em>Unicode	(UTF-8)</em> if you're unsure."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Allowed tags:"); ?></td>
		<td><textarea name="allowed_tags" style="width: 310px" rows="10"><?php echo htmlspecialchars(getOption('allowed_tags')); ?></textarea>
		</td>
		<td><?php echo gettext("Tags and attributes allowed in comments, descriptions, and other fields."); ?><br />
		<?php echo gettext("Follow the form <em>tag</em> =&gt; (<em>attribute</em> =&gt; (<em>attribute</em>=&gt; (), <em>attribute</em> =&gt; ()...)))"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Number of RSS feed items:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="feed_items"
			value="<?php echo htmlspecialchars(getOption('feed_items'));?>" /></td>
		<td><?php echo gettext("The number of new images/albums/comments you want to appear in your site's RSS feed."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Size of RSS feed images:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="feed_imagesize"
			value="<?php echo htmlspecialchars(getOption('feed_imagesize'));?>" /></td>
		<td><?php echo gettext("The size you want your images to have in your site's RSS feed."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Album date:"); ?></td>
		<td>
		<input type="checkbox" name="album_use_new_image_date" value="1"
			<?php echo checked('1', getOption('album_use_new_image_date')); ?> />
			<?php echo gettext("Use latest image date"); ?>
		</td>
		<td><?php echo gettext("Set this option if you wish your album date to reflect the date of the latest image uploaded. Otherwise it will initially be set to the date the album was created.") ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Sort gallery by:"); ?></td>
		<td><select id="sortselect" name="gallery_sorttype">
			<?php
		$sort = $sortby;
		$sort[gettext('Manual')] = 'Manual'; // allow manual sorttype
		generateListFromArray(array(getOption('gallery_sorttype')), $sort);
		?>
		</select>
		<input type="checkbox" name="gallery_sortdirection"
			value="1"
			<?php echo checked('1', getOption('gallery_sortdirection')); ?> />
		<?php echo gettext("Descending"); ?></td>
		<td><?php echo gettext("Sort order for the albums on the index of the gallery"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Search fields:"); ?></td>
		<td>
		<?php 
		echo '<ul class="searchchecklist">'."\n";
		$engine = new SearchEngine();
		generateUnorderedListFromArray($engine->allowedSearchFields(), $engine->zp_search_fields, '_SEARCH_', '');
		echo '</ul>';
		?>		
		</td>
		<td><?php echo gettext("The set of fields on which searches may be performed."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Enable Persistent Archives:"); ?></td>
		<td><input type="checkbox" name="persistent_archive" value="1"
		<?php echo checked('1', getOption('persistent_archive')); ?> /></td>
		<td><?php echo gettext("Put a checkmark here to re-serve Zip Archive files. If not checked	they will be regenerated each time."); ?>
		<?php echo gettext("<strong>Note: </strong>Setting	this option may impact password protected albums!"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Enable gallery sessions:"); ?></td>
		<td><input type="checkbox" name="album_session" value="1"
		<?php echo checked('1', getOption('album_session')); ?> /></td>
		<td><?php echo gettext("Put a checkmark here if you are having issues with with album password cookies not being retained. Setting the option causes zenphoto to use sessions rather than cookies."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Visual Thumb selection:"); ?></td>
		<td><input type="checkbox" name="thumb_select_images" value="1"
		<?php echo checked('1', getOption('thumb_select_images')); ?> /></td>
		<td><?php echo gettext("Setting this option places thumbnails in the album thumbnail selection list (the dropdown list on each album's edit page). In Firefox the dropdown shows the thumbs, but in IE and Safari only the names are displayed (even if the thumbs are loaded!). In albums with many images loading these thumbs takes much time and is unnecessary when the browser won't display them. Uncheck this option and the images will not be loaded. "); ?></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="<?php echo gettext('save'); ?>" /></td>
		<td></td>
	</tr>
</table>
</form>
</div>
<!-- end of tab-gallery div -->
<?php
}
if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
?>
<div id="tab_image">
<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
<input type="hidden" name="saveimageoptions" value="yes" /> <?php
	if (isset($_GET['mismatch'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".sprintf(gettext("Your %s passwords did not match"), $_GET['mismatch'])."</h2>";
		echo '</div>';
	}
	?>
<table class="bordered">
	<tr>
		<th colspan="3">
		<h2><?php echo gettext("Image Display"); ?></h2>
		</th>
	</tr>
	<tr>
		<td><?php echo gettext("Sort images by:"); ?></td>
		<td><select id="imagesortselect" name="image_sorttype">
			<?php generateListFromArray(array(getOption('image_sorttype')), $sortby); ?>
		</select> <input type="checkbox" name="image_sortdirection" value="1"
		<?php echo checked('1', getOption('image_sortdirection')); ?> />
		<?php echo gettext("Descending"); ?></td>
		<td><?php echo gettext("Default sort order for images"); ?></td>
	</tr>
	<tr>
		<td width="175"><?php echo gettext("Image quality:"); ?></td>
		<td width="350"><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="image_quality"
			value="<?php echo htmlspecialchars(getOption('image_quality'));?>" /></td>
		<td><?php echo gettext("JPEG Compression quality for all images."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Thumb quality:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="thumb_quality"
			value="<?php echo htmlspecialchars(getOption('thumb_quality'));?>" /></td>
		<td><?php echo gettext("JPEG Compression quality for all thumbnails."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Allow upscale:"); ?></td>
		<td><input type="checkbox" size="<?php echo TEXT_INPUT_SIZE; ?>" name="image_allow_upscale"
			value="1"
			<?php echo checked('1', getOption('image_allow_upscale')); ?> /></td>
		<td><?php echo gettext("Allow images to be scaled up to the requested size. This could	result in loss of quality, so it's off by default."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Sharpen:"); ?></td>
		<td><input type="checkbox" name="image_sharpen" value="1"
		<?php echo checked('1', getOption('image_sharpen')); ?> /> Images
		<input type="checkbox" name="thumb_sharpen" value="1"
		<?php echo checked('1', getOption('thumb_sharpen')); ?> /> Thumbs</td>
		<td><?php echo gettext("Add a small amount of unsharp mask to images and/or thumbnails. <strong>Warning</strong>: can overload slow servers."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Watermark images:"); ?></td>
		<td><?php
		$v = str_replace('.png', "", basename(getOption('watermark_image')));
		echo "<select id=\"watermark_image\" name=\"watermark_image\">\n";
		generateListFromFiles($v, SERVERPATH . "/" . ZENFOLDER . '/watermarks' , '.png');
		echo "</select>\n";
		?>
		<input type="checkbox" name="perform_watermark" value="1"
		<?php echo checked('1', getOption('perform_watermark')); ?> />&nbsp;<?php echo gettext("Enabled"); ?>
		<br />
		<?php echo gettext('cover').' '; ?>
		<input type="text" size="2" name="watermark_scale"
				value="<?php echo htmlspecialchars(getOption('watermark_scale'));?>" /><?php echo gettext('% of image') ?>
		<input type="checkbox" name="watermark_allow_upscale" value="1"
		<?php echo checked('1', getOption('watermark_allow_upscale')); ?> />&nbsp;<?php echo gettext("allow upscale"); ?>
		<br />
		<?php echo gettext("offset h"); ?>
		<input type="text" size="2" name="watermark_h_offset"
				value="<?php echo htmlspecialchars(getOption('watermark_h_offset'));?>" /><?php echo gettext("% w, "); ?>
		<input type="text" size="2" name="watermark_w_offset"
			value="<?php echo htmlspecialchars(getOption('watermark_w_offset'));?>" /><?php echo gettext("%"); ?>
		</td>
		<td><?php echo gettext("The watermark image (png-24).").sprintf(gettext('Place the image in the %s/watermarks/ folder.'),ZENFOLDER); ?>
		<br />
		<?php echo gettext("The watermark image is scaled by to cover <em>cover percentage</em> of the image and placed relative to the upper left corner of the	image.").' '.
		           gettext("It is offset from there (moved toward the lower right corner) by the <em>offset</em> percentages of the height and width difference between the image and the watermark.").' '.
		           gettext("If <em>allow upscale</em> is not checked the watermark will not be made larger than the original watermark image."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Watermark video thumbs:"); ?></td>
		<td><?php
		$v = str_replace('.png', "", basename(getOption('video_watermark_image')));
		echo "<select id=\"videowatermarkimage\" name=\"video_watermark_image\">\n";
		generateListFromFiles($v, SERVERPATH . "/" . ZENFOLDER . '/watermarks' , '.png');
		echo "</select>\n";
		?> <input type="checkbox" name="perform_video_watermark" value="1"
		<?php echo checked('1', getOption('perform_video_watermark')); ?> />&nbsp;<?php echo gettext("Enabled"); ?>
		</td>
		<td><?php echo gettext("The watermark image (png-24) that will be overlayed on the video thumbnail (if one exists).").' ('.sprintf(gettext('Place the image in the %s/watermarks/ folder'), ZENFOLDER).')'; ?> </td>
	</tr>
	<tr>
		<td><?php echo gettext("Full image quality:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="full_image_quality"
			value="<?php echo htmlspecialchars(getOption('full_image_quality'));?>" /></td>
		<td><?php echo gettext("Controls compression on full images."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Full image protection:"); ?></td>
		<td>
			<?php
			echo "<select id=\"protect_full_image\" name=\"protect_full_image\">\n";
			generateListFromArray(array(getOption('protect_full_image')), array(gettext('Unprotected') => 'Unprotected', gettext('Protected view') => 'Protected view', gettext('Download') => 'Download', gettext('No access') => 'No access'));
			echo "</select>\n";
			?>
			<p>
			<?php
			echo '<input type="checkbox" name="hotlink_protection" value="1"';
			echo checked('1', getOption('hotlink_protection')). ' /> '.gettext('Disable hotlinking');
			?>
			<br />
			<?php
			echo '<input type="checkbox" name="cache_full_image" value="1"';
			echo checked('1', getOption('cache_full_image')). ' /> '.gettext('cache the full image');
			?>
			</p>
		</td>
		<td><?php echo gettext("Select the level of protection for full sized images. <em>Download</em> forces a download dialog rather than displaying the image. <em>No&nbsp;access</em> prevents a link to the image from being shown. <em>Protected&nbsp;view</em> forces image processing before the image is diaplayed, for instance to apply a watermark or to check passwords. <em>Unprotected</em> allows direct display of the image."); ?>
		<br /><br />
		<?php echo gettext("Disabling hotlinking prevents linking to the full image from other domains. If enabled, external links are redirect to the image page. If you are having problems with full images being displayed, try disabling this setting. Hotlinking is not prevented if <em>Full&nbsp;image&nbsp;protection</em> is <em>Unprotected</em> or if the image is cached."); ?>
		<br /><br />
		<?php echo ' '.gettext("If <em>Cache the full image</em> is checked the full image will be loaded to the cache and served from there after the first reference. <em>Full&nbsp;image&nbsp;protection</em> must be set to <em>Protected&nbsp;view</em> for the image to be cached. However, once cached, no protections are applied to the image."); ?>
		</td>
	</tr>
		<td><?php echo gettext("Use lock image"); ?></td>
		<td>
			<input type="checkbox" name="use_lock_image" value="1"
			<?php echo checked('1', getOption('use_lock_image')); ?> />&nbsp;<?php echo gettext("Enabled"); ?>
		</td>
		<td><?php echo gettext("Substitute a <em>lock</em> image for thumbnails of password protected albums when the viewer has not supplied the password. If your theme supplies an <code>images/err-passwordprotected.gif</code> image, it will be shown. Otherwise the zenphoto default lock image is displayed."); ?>
	<tr>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="<?php echo gettext('save'); ?>" /></td>
		<td></td>
	</tr>
</table>
</form>
</div><!-- end of tab_image div -->
<?php
}
if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
?>
<div id="tab_comments">
<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
<input 	type="hidden" name="savecommentoptions" value="yes" /> <?php
	?>
<table class="bordered">
	<tr>
		<th colspan="3">
		<h2><?php echo gettext("Comments options"); ?></h2>
		</th>
	</tr>
	<tr>
		<td><?php echo gettext("Enable comment notification:"); ?></td>
		<td><input type="checkbox" name="email_new_comments" value="1"
		<?php echo checked('1', getOption('email_new_comments')); ?> /></td>
		<td><?php echo gettext("Email the Admin when new comments are posted"); ?></td>
	</tr>
	<!-- SPAM filter options -->
	<tr>
		<td><?php echo gettext("Spam filter:"); ?></td>
		<td><select id="spam_filter" name="spam_filter">
			<?php
		$currentValue = getOption('spam_filter');
		$pluginroot = SERVERPATH . "/" . ZENFOLDER . "/plugins/spamfilters";
		generateListFromFiles($currentValue, $pluginroot , '.php');
		?>
		</select></td>
		<td><?php echo gettext("The SPAM filter plug-in you wish to use to check comments for SPAM"); ?></td>
	</tr>
	<?php
	/* procss filter based options here */
	if (!(false === ($requirePath = getPlugin('spamfilters/'.getOption('spam_filter').'.php', false)))) {
		require_once($requirePath);
		$optionHandler = new SpamFilter();
		customOptions($optionHandler, "&nbsp;&nbsp;&nbsp;-&nbsp;");
	}
	?>
	<!-- end of SPAM filter options -->
	<tr>
		<td><?php echo gettext("Require fields:"); ?></td>
		<td>
			<table class="checkboxes">
				<tr>
					<td><input type="checkbox" name="comment_name_required" value=1
					<?php checked('1', getOption('comment_name_required')); ?>>&nbsp;<?php echo gettext("Name"); ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="comment_email_required" value=1
					<?php checked('1', getOption('comment_email_required')); ?>>&nbsp;<?php echo gettext("Email"); ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="comment_web_required" value=1
					<?php checked('1', getOption('comment_web_required')); ?>>&nbsp;<?php echo gettext("Website"); ?></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="Use_Captcha" value=1
					<?php checked('1', getOption('Use_Captcha')); ?>>&nbsp;<?php echo gettext("Captcha"); ?></td>
				</tr>
			</table>
		</td>
		<td><?php echo gettext("Checked fields must be valid in a comment posting."); ?></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="<?php echo gettext('save'); ?>" /></td>
		<td></td>
	</tr>
</table>
</form>
</div>
<?php } ?>
<!-- end of tab_comments div -->
<?php if ($_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS)) { ?>
<div id="tab_theme">
<?php
$themelist = array();
if (($_zp_loggedin & ADMIN_RIGHTS)) {
	$gallery_title = get_language_string(getOption('gallery_title'));
	if ($gallery_title != gettext("Gallery")) {
		$gallery_title .= ' ('.gettext("Gallery").')';
	}
	$themelist[$gallery_title] = '';
}
$albums = $gallery->getAlbums(0);
foreach ($albums as $alb) {
	if (isMyAlbum($alb, THEMES_RIGHTS)) {
		$album = new Album($gallery, $alb);
		$theme = $album->getAlbumTheme();
		if (!empty($theme)) {
			$key = $album->getTitle();
			if ($key != $alb) {
				$key .= " ($alb)";
			}
			$themelist[$key] = urlencode($alb);
		}
	}
}
if (!empty($_REQUEST['themealbum'])) {
	$alb = sanitize($_REQUEST['themealbum']);
		$album = new Album($gallery, $alb);
		$albumtitle = $album->getTitle();
		$themename = $album->getAlbumTheme();
	} else {
		foreach ($themelist as $albumtitle=>$alb) break;
		if (empty($alb)) {
			$themename = $gallery->getCurrentTheme();
			$album = NULL;
		} else {
			$alb = sanitize($alb);
			$album = new Album($gallery, $alb);
			$albumtitle = $album->getTitle();
			$themename = $album->getAlbumTheme();
		}
	}
	$themes = $gallery->getThemes();
	$theme = $themes[$themename];
	if (count($themelist) == 0) {
		echo '<div class="errorbox" id="no_themes">';
		echo  "<h2>".gettext("There are no themes for which you have rights to administer.")."</h2>";
		echo '</div>';
	} else {
?>
<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
	<input type="hidden" name="savethemeoptions" value="yes" />
	<table class='bordered'>
<?php
	/* handle theme options */
	echo "<input type=\"hidden\" name=\"old_themealbum\" value=\"".urlencode($alb)."\" />";
	echo "<tr><th colspan='2'><h2 style='float: left'>".sprintf(gettext('Theme for <code><strong>%1$s</strong></code>: <em>%2$s</em>'), $albumtitle,$theme['name'])."</h2></th>\n";
	echo "<th colspan='1' style='text-align: right'>";
	if (count($themelist) > 1) {
		echo gettext("Show theme for:");
		echo '<select id="themealbum" name="themealbum" onchange="this.form.submit()">';
		generateListFromArray(array(urlencode($alb)), $themelist);
		echo '</select>';
	} else {
		echo '&nbsp;';
	}
	echo "</th></tr>\n";
	?>

	<tr class="alt1">
		<td colspan="2" align="left">
			<?php echo gettext('<strong>Standard options</strong>') ?>
		</td>
		<td><em><?php echo gettext('These image and album presentation options are standard to all themes.'); ?></em></td>
	</tr>
	<tr>
		<td style='width: 175px'><?php echo gettext("Albums per page:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="albums_per_page"
			value="<?php echo getThemeOption($album, 'albums_per_page');?>" /></td>
		<td><?php echo gettext("Controls the number of albums on a page. You might need to change	this after switching themes to make it look better."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Images per page:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="images_per_page"
			value="<?php echo getThemeOption($album, 'images_per_page');?>" /></td>
		<td><?php echo gettext("Controls the number of images on a page. You might need to change	this after switching themes to make it look better."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Thumb size:"); ?></td>
		<td><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="thumb_size"
			value="<?php echo getThemeOption($album, 'thumb_size');?>" /></td>
		<td><?php echo gettext("Default thumbnail size and scale."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Crop thumbnails:"); ?></td>
		<td>
			<input type="checkbox" name="thumb_crop" value="1"
				<?php echo checked('1', getThemeOption($album, 'thumb_crop')); ?> />
			Crop width <input type="text" size="5" name="thumb_crop_width"
				value="<?php echo getThemeOption($album, 'thumb_crop_width');?>" />
			Crop height <input type="text" size="5" name="thumb_crop_height"
				value="<?php echo getThemeOption($album, 'thumb_crop_height');?>" />
		</td>
		<td>
			<?php echo gettext("If checked the thumbnail cropped to the <em>width</em> and <em>height</em> indicated."); ?>
			<br />
			<?php echo gettext('<strong>Note</strong>: changing crop height or width will invaliate existing crops.'); ?>
		</td>
	</tr>
	<tr>
		<td><?php echo gettext("Image size:"); ?></td>
		<td>
			<input type="text" size="<?php echo 5; ?>" name="image_size"
				value="<?php echo getThemeOption($album, 'image_size');?>" />
			<input type="checkbox" name="image_use_longest_side"
				value="1"	<?php echo checked('1', getThemeOption($album, 'image_use_longest_side')); ?> />
			<?php echo gettext("Use longest side"); ?>
		</td>
		<td>
			<?php echo gettext("Default image display width."); ?>
			<br />
			<?php echo gettext("If <em>Use longest side</em> is checked the longest side of the image will be <em>image size</em>.").' ';
						echo gettext("Otherwise, the <em>width</em> of the image will	be <em>image size</em>."); ?>
		</td>
	</tr>
	<?php if (is_null($album)) {?>
	<tr>
		<td><?php echo gettext("Custom index page:"); ?></td>
		<td>
			<select id="custom_index_page" name="custom_index_page">
				<option value=''>
				<?php
				$curdir = getcwd();
				$root = SERVERPATH.'/'.THEMEFOLDER.'/'.$themename.'/';
				chdir($root);
				$filelist = safe_glob('*.php');
				$list = array();
				foreach($filelist as $file) {
					$list[] = str_replace('.php', '', FilesystemToUTF8($file));
				}
				$list = array_diff($list, array('themeoptions', 'theme_description', '404', 'slideshow', 'search', 'image', 'index', 'album', 'customfunctions', 'news', 'pages'));
				generateListFromArray(array(getThemeOption($album, 'custom_index_page')), $list);
				chdir($curdir);
				?>
			</select>
		</td>
		<td><?php echo gettext("If this option is not empty, the Gallery Index URL that would normally link to the theme <code>index.php</code> script will instead link to this script. This option applies only to the main theme for the <em>Gallery</em>."); ?></td>
	</tr>
	<?php
	}
	if (!(false === ($requirePath = getPlugin('themeoptions.php', $themename)))) {
		require_once($requirePath);
		$optionHandler = new ThemeOptions();
		$supportedOptions = $optionHandler->getOptionsSupported();
		if (count($supportedOptions) > 0) {
		?>
	<tr class="alt1" >
		<td colspan="2" align="left">
			<?php echo gettext('<strong>Custom theme options</strong>') ?>
		</td>
		<td><em><?php echo gettext('The following are options specifically implemented by the theme.'); ?></em></td>
	</tr>
		<?php
			customOptions($optionHandler, '', $album);
		}
	}

	?>
	<tr>
	<td></td>
	<td><input type="submit" value= <?php echo gettext('save') ?> /></td>
	<td></td>
	</table>
	</form>
<?php } ?>
</div>
<?php } ?>
<!-- end of tab_theme div -->
<?php
if ($_zp_loggedin & ADMIN_RIGHTS) {
	$c = 0;
?>
	<div id="tab_plugin">
	<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
	<input type="hidden" name="savepluginoptions" value="yes" />
	<table class="bordered">
	<tr>
		<th>
			<h2><?php echo gettext("Plugin Options"); ?> 	</h2>
		</th>
		<th colspan="2">
			<span style="font-weight: normal"> <a href="javascript:toggleExtraInfo('','plugin',true);"><?php echo gettext('Expand plugin options');?></a>
		| <a href="javascript:toggleExtraInfo('','plugin',false);"><?php echo gettext('Collapse all plugin options');?></a></span>
		</th>
	</tr>
	<tr>
	<td style="padding: 0;margin:0" colspan="3">
	<?php
	foreach (getEnabledPlugins() as $extension) {
		$ext = substr($extension, 0, strlen($extension)-4);
		$option_interface = null;
		require_once(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER . $extension);
		if (!is_null($option_interface)) {
			$c++;
			echo '<table class="bordered" style="border: 0" id="plugin-'.$ext.'">';
			echo '<tr><th colspan="3">';
			?>
			<span class="pluginextrashow"><a href="javascript:toggleExtraInfo('<?php echo $ext;?>','plugin',true);"><?php echo $ext; ?></a></span>
			<span style="display:none;" class="pluginextrahide"><a href="javascript:toggleExtraInfo('<?php echo $ext;?>','plugin',false);"><?php echo $ext; ?></a></span>
			<?php
			echo '</th></tr>';
			$supportedOptions = $option_interface->getOptionsSupported();
			if (count($supportedOptions) > 0) {
				customOptions($option_interface, '', NULL, 'plugin');
			}
		}
	}
	if ($c == 0) {
		echo gettext("There are no plugin options to administer.");
	} else {
	?>
		<tr>
		<td colspan="3">
		<input type="submit" value= <?php echo gettext('save') ?> />
		</td>
		</tr></table>
	<?php
	}
	?>
	</form>
	<tr>
	</td>
	</table>
<?php
}
} // end of null account lockout
?>

<!-- end of tab_plugin div -->
</div>
<!-- end of container -->
<?php
echo '</div>'; // content
echo '</div>'; // main

printAdminFooter();
echo "\n</body>";
echo "\n</html>";
?>



