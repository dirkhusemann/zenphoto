<?php
/**
 * provides the Options tab of admin
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once("admin-functions.php");
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
						if (isset($_POST[$i.'-themes_rights'])) $themes_r = THEMES_RIGHTS; else $themes_r = 0;
						if (isset($_POST[$i.'-options_rights'])) $options_r = OPTIONS_RIGHTS; else $options_r = 0;
						if (isset($_POST[$i.'-admin_rights'])) $admin_r = ADMIN_RIGHTS; else $admin_r = 0;
						if (!isset($_POST['alter_enabled'])) {
							$rights = NO_RIGHTS + $main_r + $view_r + $upload_r + $comment_r + $edit_r + $themes_r + $options_r + $admin_r;
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
			setOption('gallery_title', $_POST['gallery_title']);
			setOption('website_title', $_POST['website_title']);
			$web = $_POST['website_url'];
			setOption('website_url', $web);
			setOption('time_offset', $_POST['time_offset']);
			setBoolOption('mod_rewrite', isset($_POST['mod_rewrite']));
			setOption('mod_rewrite_image_suffix', $_POST['mod_rewrite_image_suffix']);
			setOption('server_protocol', $_POST['server_protocol']);
			setOption('charset', $_POST['charset']);
			setBoolOption('album_use_new_image_date', isset($_POST['album_use_new_image_date']));
			setOption('gallery_sorttype', $_POST['gallery_sorttype']);
			if ($_POST['gallery_sorttype'] == 'Manual') {
				setBoolOption('gallery_sortdirection', 0);
			} else {
				setBoolOption('gallery_sortdirection', isset($_POST['gallery_sortdirection']));
			}
			setOption('feed_items', $_POST['feed_items']);
			setOption('feed_imagesize', $_POST['feed_imagesize']);
			$search = new SearchEngine();
			setOption('search_fields', 32767, false); // make SearchEngine allow all options so parseQueryFields() will gives back what was choosen this time
			setOption('search_fields', $search->parseQueryFields());
			$olduser = getOption('gallerly_user');
			setOption('gallery_user', $newuser = $_POST['gallery_user']);
			$pwd = trim($_POST['gallerypass']);
			if ($olduser != $newuser) {
				if (empty($pwd)) {
					$_POST['gallerypass'] = 'xxx';  // invalidate, user changed but password not set
				}
			}
			if ($_POST['gallerypass'] == $_POST['gallerypass_2']) {
				if (empty($pwd)) {
					if (empty($_POST['gallerypass'])) {
						setOption('gallery_password', NULL);  // clear the gallery password
					}
				} else {
					setOption('gallery_password', md5($newuser.$pwd));
				}
			} else {
				$notify = '?mismatch=gallery';
			}
			$olduser = getOption('search_user');
			setOption('search_user',$newuser = $_POST['search_user']);
			if ($olduser != $newuser) {
				if (empty($pwd)) {
					$_POST['searchpass'] = 'xxx';  // invalidate, user changed but password not set
				}
			}
			if ($_POST['searchpass'] == $_POST['searchpass_2']) {
				$pwd = trim($_POST['searchpass']);
				if (empty($pwd)) {
					if (empty($_POST['searchpass'])) {
						setOption('search_password', NULL);  // clear the gallery password
					}
				} else {
					setOption('search_password', md5($newuser.$pwd));
				}
			} else {
				$notify = '?mismatch=search';
			}
			setOption('gallery_hint', $_POST['gallery_hint']);
			setOption('search_hint', $_POST['search_hint']);
			setBoolOption('persistent_archive', isset($_POST['persistent_archive']));
			setBoolOption('album_session', isset($_POST['album_session']));
			setOption('locale', $newloc = $_POST['locale']);
			if ($newloc != '') { // only clear the cookie if the option is not the default!
				$cookiepath = WEBPATH;
				if (WEBPATH == '') { $cookiepath = '/'; }
				zp_setCookie('dynamic_locale', getOption('locale'), time()-368000, $cookiepath);  // clear the language cookie
			}
			$f = $_POST['date_format_list'];
			if ($f == 'custom') $f = $_POST['date_format'];
			setOption('date_format', $f);
			setBoolOption('thumb_select_images', isset($_POST['thumb_select_images']));
			$returntab = "#tab_gallery";
		}

		/*** Image options ***/
		if (isset($_POST['saveimageoptions'])) {
			setOption('image_quality', $_POST['image_quality']);
			setOption('thumb_quality', $_POST['thumb_quality']);
			setBoolOption('image_allow_upscale', isset($_POST['image_allow_upscale']));
			setBoolOption('thumb_sharpen', isset($_POST['thumb_sharpen']));
			setBoolOption('image_sharpen', isset($_POST['image_sharpen']));
			setBoolOption('perform_watermark', isset($_POST['perform_watermark']));
			setOption('watermark_image', 'watermarks/' . $_POST['watermark_image'] . '.png');
			setOption('watermark_scale', $_POST['watermark_scale']);
			setBoolOption('watermark_allow_upscale', isset($_POST['watermark_allow_upscale']));
			setOption('watermark_h_offset', $_POST['watermark_h_offset']);
			setOption('watermark_w_offset', $_POST['watermark_w_offset']);
			setBoolOption('perform_video_watermark', isset($_POST['perform_video_watermark']));
			setOption('video_watermark_image', 'watermarks/' . $_POST['video_watermark_image'] . '.png');
			setOption('full_image_quality', $_POST['full_image_quality']);
			setOption('protect_full_image', $_POST['protect_full_image']);
			setBoolOption('hotlink_protection', isset($_POST['hotlink_protection']));
			setBoolOption('use_lock_image', isset($_POST['use_lock_image']));
			setOption('image_sorttype', $_POST['image_sorttype']);
			setBoolOption('image_sortdirection', isset($_POST['image_sortdirection']));
			$returntab = "#tab_image";
		}
		/*** Comment options ***/
			
		if (isset($_POST['savecommentoptions'])) {
			setOption('spam_filter', $_POST['spam_filter']);
			setBoolOption('email_new_comments', isset($_POST['email_new_comments']));
			$tags = $_POST['allowed_tags'];
			$test = "(".$tags.")";
			$a = parseAllowedTags($test);
			if ($a !== false) {
				setOption('allowed_tags', $tags);
				$notify = '';
			} else {
				$notify = '?tag_parse_error';
			}
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
				$alb = urldecode($_POST['themealbum']);
				$table = new Album(new Gallery(), $alb);
				$returntab = '&themealbum='.urlencode($alb).'#tab_theme';
			} else {
				$table = NULL;
			}
			setThemeOption($table, 'image_size', $_POST['image_size']);
			setBoolThemeOption($table, 'image_use_longest_side', $_POST['image_use_longest_side']);
			setThemeOption($table, 'thumb_size', $_POST['thumb_size']);
			setBoolThemeOption($table, 'thumb_crop', $_POST['thumb_crop']);
			setThemeOption($table, 'thumb_crop_width', $_POST['thumb_crop_width']);
			setThemeOption($table, 'thumb_crop_height', $_POST['thumb_crop_height']);
			setThemeOption($table, 'albums_per_page', $_POST['albums_per_page']);
			setThemeOption($table, 'images_per_page', $_POST['images_per_page']);
		}
		/*** Plugin Options ***/
		if (isset($_POST['savepluginoptions'])) {
			// all plugin options are handled by the custom option code.
			$returntab = "#tab_plugin";
		}
		/*** custom options ***/
		$templateOptions = GetOptionList();

		foreach($standardOptions as $option) {
			unset($templateOptions[$option]);
		}
		unset($templateOptions['saveoptions']);
		$keys = array_keys($templateOptions);
		$i = 0;
		while ($i < count($keys)) {
			if (isset($_POST[$keys[$i]])) {
				setThemeOption($table, $keys[$i], $_POST[$keys[$i]]);
			} else {
				if (isset($_POST['chkbox-' . $keys[$i]])) {
					setThemeOption($table, $keys[$i], 0);
				}
			}
			$i++;
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
<form action="?action=saveoptions" method="post"><input
	type="hidden" name="saveadminoptions" value="yes" /> <?php
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		$alterrights = '';
		$admins = getAdministrators();
		$admins [''] = array('id' => -1, 'user' => '', 'pass' => '', 'name' => '', 'email' => '', 'rights' => ALL_RIGHTS);
	} else {
		$alterrights = ' DISABLED';
		global $_zp_current_admin;
		$admins = array($_zp_current_admin['user'] => $_zp_current_admin);
		echo "<input type=\"hidden\" name=\"alter_enabled\" value=\"no\" />";
	}
	if (isset($_GET['mismatch'])) {
		if ($_GET['mismatch'] == 'newuser') {
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
	?> <input type="hidden" name="totaladmins"
	value="<?php echo count($admins); ?>" />
<table class="bordered">
	<tr>
		<th colspan="3">
		<h2><?php echo gettext("Admin login information"); ?></h2> 
		</th>
	</tr>
	<?php
	$id = 0;
	$albumlist = $gallery->getAlbums();
	foreach($admins as $user) {
		$userid = $user['user'];
		$master = '';
		if ($id == 0) {
			if ($_zp_loggedin & ADMIN_RIGHTS) {
				$master = " (<em>".gettext("Master")."</em>)";
				$user['rights'] = $user['rights'] | ADMIN_RIGHTS;
			}
		}		
		if (count($admins) > 2) {
			$background = ($user['id'] == $_zp_current_admin['id']) ? " background-color: #ECF1F2;" : "";
		}
		?>
	<tr>
		<td style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" width="175"><strong><?php echo gettext("Username:"); ?></strong></td>
		<td style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" width="200"><?php if (empty($userid)) {?>
		<input type="text" size="40" name="<?php echo $id ?>-adminuser"
			value="" /> <?php  } else { echo $userid.$master; ?> 
			<input type="hidden" name="<?php echo $id ?>-adminuser"
			value="<?php echo $userid ?>" /> <?php } ?></td>
		<td style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>">
		<?php if(!empty($userid) && count($admins) > 2) { ?>
		<a href="javascript: if(confirm('Are you sure you want to delete this user?')) { window.location='?action=deleteadmin&adminuser=<?php echo $user['id']; ?>'; }"
			title="Delete this user." style="color: #c33;"> <img
			src="images/fail.png" style="border: 0px;" alt="Delete" /></a> <?php } ?>&nbsp;
		</td>
	</tr>
	<tr>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Password:"); ?><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?></td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><?php $x = $user['pass']; if (!empty($x)) { $x = '          '; } ?>
		<input type="password" size="40" name="<?php echo $id ?>-adminpass"
			value="<?php echo $x; ?>" /><br />
		<input type="password" size="40" name="<?php echo $id ?>-adminpass_2"
			value="<?php echo $x; ?>" /></td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		<table class="checkboxes" >
			<tr>
				<td style="padding-bottom: 3px;<?php echo $background; ?>"><strong><?php echo gettext("Rights"); ?></strong>:
			</tr>
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-admin_rights"
					value=<?php echo ADMIN_RIGHTS; if ($user['rights'] & ADMIN_RIGHTS) echo ' checked';echo $alterrights; ?>><?php echo gettext("User admin"); ?></td>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-options_rights"
					value=<?php echo OPTIONS_RIGHTS; if ($user['rights'] & OPTIONS_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("Options"); ?></td>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-themes_rights"
					value=<?php echo THEMES_RIGHTS; if ($user['rights'] & THEMES_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("Themes"); ?></td>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-edit_rights"
					value=<?php echo EDIT_RIGHTS; if ($user['rights'] & EDIT_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("Edit"); ?></td>
			</tr>
			<tr>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-comment_rights"
					value=<?php echo COMMENT_RIGHTS; if ($user['rights'] & COMMENT_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("Comment"); ?></td>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-upload_rights"
					value=<?php echo UPLOAD_RIGHTS; if ($user['rights'] & UPLOAD_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("Upload"); ?></td>
				<?php 
				if (NO_RIGHTS > 0) {
				?>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-view_rights"
					value=<?php echo VIEWALL_RIGHTS; if ($user['rights'] & VIEWALL_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("View all albums"); ?></td>
				<td <?php if (!empty($background)) echo "style=\"$background\""; ?>><input type="checkbox" name="<?php echo $id ?>-main_rights"
					value=<?php echo MAIN_RIGHTS; if ($user['rights'] & MAIN_RIGHTS) echo ' checked';echo$alterrights; ?>><?php echo gettext("Overview"); ?></td>
				<?php 
				} else{
					echo '<input type="hidden" name="'.$id.'-main_rights" value=1>';
				}
				?>
			</tr>
		</table>

		</td>
	</tr>
	<tr>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Full name:"); ?> <br />
		<br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("email:"); ?></td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
			<input type="text" size="40" name="<?php echo $id ?>-admin_name"
			value="<?php echo $user['name'];?>" /> <br />
		<br />
		<input type="text" size="40" name="<?php echo $id ?>-admin_email"
			value="<?php echo $user['email'];?>" /></td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		<table>
		<tr>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		<?php
		if (empty($master)) {
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
			echo '<br />'.gettext("This account's username and email are used as contact data in the RSS feeds.");	
		}
		?>
		</td>
		<td <?php if (!empty($background)) echo "style=\"$background\""; ?>>
		<?php
		if (empty($master)) {
			if (!empty($alterrights)) {
				echo gettext("You may manage these albums subject to the above rights.");
			} else {
				echo gettext("Select one or more albums for the administrator to manage.").' ';
				echo gettext("Administrators with <em>User admin</em> rights can manage all albums. All others may manage only those that are selected.");
			}
		}?>
		</td>
		</table>
		</td>
	
	<tr>
	</tr>
	<?php
	$id++;
}
?>
	<tr>
		<td></td>
		<td><input type="submit" value="<?php echo gettext('save'); ?>" /></td>
		<td></td>
	</tr>
</table>
</form>
</div>
<!-- end of tab_admin div -->
<?php
if (!$_zp_null_account) {
if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
?>
<div id="tab_gallery">
<form action="?action=saveoptions" method="post">
 <input	type="hidden" name="savegalleryoptions" value="yes" /> <?php
	if (isset($_GET['mismatch'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>". gettext("Your").' '. $_GET['mismatch'] . ' '.gettext("passwords were empty or did not match")."</h2>";
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
		<td width="175"><?php echo gettext("Gallery title:"); ?></td>
		<td width="200"><input type="text" size="40" name="gallery_title"
			value="<?php echo htmlspecialchars(getOption('gallery_title'));?>" /></td>
		<td><?php echo gettext("What you want to call your photo gallery."); ?></td>
	</tr>
	<tr>
    <td><?php echo gettext("Gallery guest user:"); ?>    </td>
    <td><input type="text" size="40" name="gallery_user" value="<?php echo htmlspecialchars(getOption('gallery_user')); ?>" />		</td>
		<td><?php echo gettext("User ID for the gallery guest user") ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Gallery password:"); ?><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php gettext("(repeat)"); ?>
		</td>
		<td>
		<?php $x = getOption('gallery_password'); if (!empty($x)) { $x = '          '; } ?>
		<input type="password" size="40" name="gallerypass"
			value="<?php echo $x; ?>" /><br />
		<input type="password" size="40" name="gallerypass_2"
			value="<?php echo $x; ?>" /></td>
		<td><?php echo gettext("Master password for the gallery. If this is set, visitors must know this password to view the gallery."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Gallery password hint:"); ?></td>
		<td><input type="text" size="40" name="gallery_hint"
			value="<?php echo htmlspecialchars(getOption('gallery_hint'));?>" /></td>
		<td><?php echo gettext("A reminder hint for the password."); ?></td>
	</tr>
	<tr>
    <td><?php echo gettext("Search guest user:"); ?>    </td>
    <td><input type="text" size="40" name="search_user" value="<?php echo htmlspecialchars(getOption('search_user')); ?>" />		</td>
		<td><?php echo gettext("User ID for the search guest user") ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Search password:"); ?><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
		</td>
		<td><?php $x = getOption('search_password'); if (!empty($x)) { $x = '          '; } ?>
		<input type="password" size="40" name="searchpass"
			value="<?php echo $x; ?>" /><br />
		<input type="password" size="40" name="searchpass_2"
			value="<?php echo $x; ?>" /></td>
		<td><?php echo gettext("Password for the the search guest user. If this is set, visitors must know this password to view search results."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Search password hint:"); ?></td>
		<td><input type="text" size="40" name="search_hint"
			value="<?php echo htmlspecialchars(getOption('search_hint'));?>" /></td>
		<td><?php echo gettext("A reminder hint for the password."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Website title:"); ?></td>
		<td><input type="text" size="40" name="website_title"
			value="<?php echo htmlspecialchars(getOption('website_title'));?>" /></td>
		<td><?php echo gettext("Your web site title."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Website url:"); ?></td>
		<td><input type="text" size="40" name="website_url"
			value="<?php echo htmlspecialchars(getOption('website_url'));?>" /></td>
		<td><?php echo gettext("This is used to link back to your main site, but your theme must	support it."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Server protocol:"); ?></td>
		<td><input type="text" size="40" name="server_protocol"
			value="<?php echo htmlspecialchars(getOption('server_protocol'));?>" /></td>
		<td><?php echo gettext("If you're running a secure server, change this to"); ?> <em>https</em>
		<?php echo gettext("(Most people will leave this alone.)"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Time offset (hours):"); ?></td>
		<td><input type="text" size="40" name="time_offset"
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
		<td><input type="text" size="40" name="mod_rewrite_image_suffix"
			value="<?php echo htmlspecialchars(getOption('mod_rewrite_image_suffix'));?>" /></td>
		<td><?php echo gettext("If <em>mod_rewrite</em> is checked above, zenphoto will appended	this to the end (helps search engines). Examples: <em>.html, .php,	/view</em>, etc."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Language:"); ?></td>
		<td><select id="locale" name="locale">
			<?php
			generateLanguageOptionList();
			?>
		</select></td>
		<td><?php echo gettext("The language to display text in. (Set to <em>HTTP Accept Language</em> to use the language preference specified by the viewer's browser.)"); ?></td>
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
			<div id="customTextBox" name="customText" style="display:<?php echo $dsp; ?>">
			<input type="text" size="40" name="date_format"
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
		<td><?php echo gettext("Number of RSS feed items:"); ?></td>
		<td><input type="text" size="40" name="feed_items"
			value="<?php echo htmlspecialchars(getOption('feed_items'));?>" /></td>
		<td><?php echo gettext("The number of new images/albums/comments you want to appear in your site's RSS feed."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Size of RSS feed images:"); ?></td>
		<td><input type="text" size="40" name="feed_imagesize"
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
		<td><?php $fields = getOption('search_fields'); ?>
		<table class="checkboxes">
			<tr>
				<td><input type="checkbox" name="sf_title" value=1
				<?php if ($fields & SEARCH_TITLE) echo ' checked'; ?>> <?php echo gettext("Title"); ?></td>
				<td><input type="checkbox" name="sf_desc" value=1
				<?php if ($fields & SEARCH_DESC) echo ' checked'; ?>> <?php echo gettext("Description"); ?></td>
				<td><input type="checkbox" name="sf_tags" value=1
				<?php if ($fields & SEARCH_TAGS) echo ' checked'; ?>> <?php echo gettext("Tags"); ?></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="sf_filename" value=1
				<?php if ($fields & SEARCH_FILENAME) echo ' checked'; ?>>
				<?php echo gettext("File/Folder name"); ?></td>
				<td><input type="checkbox" name="sf_location" value=1
				<?php if ($fields & SEARCH_LOCATION) echo ' checked'; ?>> <?php echo gettext("Location"); ?></td>
				<td><input type="checkbox" name="sf_city" value=1
				<?php if ($fields & SEARCH_CITY) echo ' checked'; ?>> <?php echo gettext("City"); ?></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="sf_state" value=1
				<?php if ($fields & SEARCH_STATE) echo ' checked'; ?>> <?php echo gettext("State"); ?></td>
				<td><input type="checkbox" name="sf_country" value=1
				<?php if ($fields & SEARCH_COUNTRY) echo ' checked'; ?>> <?php echo gettext("Country"); ?></td>
			</tr>
		</table>
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
		<td><?php echo gettext("Setting this option places thumbnails in the album thumbnail selection list. This does not work on all browsers (Internet Explorer does not show the images) and may slow down loading the edit page if you have a lot images."); ?></td>
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
<form action="?action=saveoptions" method="post"><input
	type="hidden" name="saveimageoptions" value="yes" /> <?php
	if (isset($_GET['mismatch'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your").' ' . $_GET['mismatch'] . ' '.gettext("passwords did not match")."</h2>";
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
		<td width="200"><input type="text" size="40" name="image_quality"
			value="<?php echo htmlspecialchars(getOption('image_quality'));?>" /></td>
		<td><?php echo gettext("JPEG Compression quality for all images."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Thumb quality:"); ?></td>
		<td><input type="text" size="40" name="thumb_quality"
			value="<?php echo htmlspecialchars(getOption('thumb_quality'));?>" /></td>
		<td><?php echo gettext("JPEG Compression quality for all thumbnails."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Allow upscale:"); ?></td>
		<td><input type="checkbox" size="40" name="image_allow_upscale"
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
		$v = explode("/", getOption('watermark_image'));
		$v = str_replace('.png', "", $v[count($v)-1]);
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
		<td><?php echo gettext("The watermark image (png-24). (Place the image in the"); ?> "<?php echo ZENFOLDER; ?>/watermarks/
		<?php echo gettext("directory"); ?>".)<br />
		<?php echo gettext("The watermark image is scaled by to cover <em>cover percentage</em> of the image and placed relative to the upper left corner of the	image.").' '.
		           gettext("It is offset from there (moved toward the lower right corner) by the <em>offset</em> percentages of the height and width difference between the image and the watermark.").' '.
		           gettext("If <em>allow upscale</em> is not checked the watermark will not be made larger than the original watermark image."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Watermark video thumbs:"); ?></td>
		<td><?php
		$v = explode("/", getOption('video_watermark_image'));
		$v = str_replace('.png', "", $v[count($v)-1]);
		echo "<select id=\"videowatermarkimage\" name=\"video_watermark_image\">\n";
		generateListFromFiles($v, SERVERPATH . "/" . ZENFOLDER . '/watermarks' , '.png');
		echo "</select>\n";
		?> <input type="checkbox" name="perform_video_watermark" value="1"
		<?php echo checked('1', getOption('perform_video_watermark')); ?> />&nbsp;<?php echo gettext("Enabled"); ?>
		</td>
		<td><?php echo gettext("The watermark image (png-24) that will be overlayed on the video thumbnail (if one exists). (Place the image in the"); ?> "<?php echo ZENFOLDER; ?>/watermarks/ <?php echo gettext("directory"); ?>".)</td>
	</tr>
	<tr>
		<td><?php echo gettext("Full image quality:"); ?></td>
		<td><input type="text" size="40" name="full_image_quality"
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
		echo '<input type="checkbox" name="hotlink_protection" value="1"';
		echo checked('1', getOption('hotlink_protection')). ' /> '.gettext('Disable hotlinking');
		?>
		</td>
		<td><?php echo gettext("Select the level of protection for full sized images."); 
		echo ' '.gettext("Disabling hotlinking prevents linking to the full image from other domains. If enabled, external links are redirect to the image page. If you are having problems with full images being displayed, try disabling this setting. Hotlinking is not prevented if <em>Full image protection</em> is <em>Unprotected</em>."); ?></td>
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
<form action="?action=saveoptions" method="post"><input
	type="hidden" name="savecommentoptions" value="yes" /> <?php
	if (isset($_GET['tag_parse_error'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your Allowed tags change did not parse successfully.")."</h2>";
		echo '</div>';
	}
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
	<tr>
		<td><?php echo gettext("Allowed tags:"); ?></td>
		<td><textarea name="allowed_tags" cols="40" rows="10"><?php echo htmlspecialchars(getOption('allowed_tags')); ?></textarea>
		</td>
		<td><?php echo gettext("Tags and attributes allowed in comments"); ?><br />
		<?php echo gettext("Follow the form <em>tag</em> =&gt; (<em>attribute</em> =&gt; (<em>attribute</em>=&gt; (), <em>attribute</em> =&gt; ()...)))"); ?></td>
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
		<td><input type="checkbox" name="comment_name_required" value=1
		<?php checked('1', getOption('comment_name_required')); ?>>&nbsp;<?php echo gettext("Name"); ?>
		<input type="checkbox" name="comment_email_required" value=1
		<?php checked('1', getOption('comment_email_required')); ?>>&nbsp;<?php echo gettext("Email"); ?>
		<input type="checkbox" name="comment_web_required" value=1
		<?php checked('1', getOption('comment_web_required')); ?>>&nbsp;<?php echo gettext("Website"); ?>
		<input type="checkbox" name="Use_Captcha" value=1
		<?php checked('1', getOption('Use_Captcha')); ?>>&nbsp;<?php echo gettext("Captcha"); ?></td>
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
	$gallery_title = htmlspecialchars(getOption('gallery_title'));
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
	$alb = urldecode($_REQUEST['themealbum']);
		$album = new Album($gallery, $alb);
		$albumtitle = $album->getTitle();
		$themename = $album->getAlbumTheme();
	} else {
		foreach ($themelist as $albumtitle=>$alb) break;
		if (empty($alb)) {
			$themename = $gallery->getCurrentTheme();
			$album = NULL;
		} else {
			$alb = urldecode($alb);
			$album = new Album($gallery, $alb);
			$albumtitle = $album->getTitle();
			$themename = $album->getAlbumTheme();
		}
	}
	$themes = $gallery->getThemes();
	$theme = $themes[$themename];
	if (count($themelist) > 1) {
		echo '<form action="#tab_theme" method="post">';
		echo gettext("Show theme for"). ': ';
		echo '<select id="themealbum" name="themealbum" onchange="this.form.submit()">';
		generateListFromArray(array(urlencode($alb)), $themelist);
		echo '</select>';
		echo '</form>';
	}	
	if (count($themelist) == 0) {
		echo '<div class="errorbox" id="no_themes">';
		echo  "<h2>".gettext("There are no themes for which you have rights to administer.")."</h2>";
		echo '</div>';
	} else {
?>
<form action="?action=saveoptions" method="post">
	<input type="hidden" name="savethemeoptions" value="yes" /> 
	<table class='bordered'>
<?php
	/* handle theme options */
	echo "<input type=\"hidden\" name=\"themealbum\" value=\"".urlencode($alb)."\" />";
	echo "<tr><th colspan='3'><h2>".gettext("Theme for")." <code><strong>$albumtitle</strong></code>: <em>".$theme['name']."</em></h2></th></tr>\n";
	?>
	<tr>
		<td><?php echo gettext("Albums per page:"); ?></td>
		<td><input type="text" size="40" name="albums_per_page"
			value="<?php echo getThemeOption($album, 'albums_per_page');?>" /></td>
		<td><?php echo gettext("Controls the number of albums on a page. You might need to change	this after switching themes to make it look better."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Images per page:"); ?></td>
		<td><input type="text" size="40" name="images_per_page"
			value="<?php echo getThemeOption($album, 'images_per_page');?>" /></td>
		<td><?php echo gettext("Controls the number of images on a page. You might need to change	this after switching themes to make it look better."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Thumb size:"); ?></td>
		<td><input type="text" size="40" name="thumb_size"
			value="<?php echo getThemeOption($album, 'thumb_size');?>" /></td>
		<td><?php echo gettext("Default thumbnail size and scale."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Crop thumbnails:"); ?></td>
		<td><input type="checkbox" size="40" name="thumb_crop" value="1"
		<?php echo checked('1', getThemeOption($album, 'thumb_crop')); ?> /></td>
		<td><?php echo gettext("If checked the thumbnail will be a centered portion of the	image with the given width and height after being resized to <em>thumb	size</em> (by shortest side).").' '; 
		echo gettext("Otherwise, it will be the full image resized to <em>thumb size</em> (by shortest side)."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Crop thumbnail width:"); ?></td>
		<td><input type="text" size="40" name="thumb_crop_width"
			value="<?php echo getThemeOption($album, 'thumb_crop_width');?>" /></td>
		<td><?php echo gettext("The <em>thumb crop width</em> is the maximum width when height is the shortest side"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Crop thumbnail height:"); ?></td>
		<td><input type="text" size="40" name="thumb_crop_height"
			value="<?php echo getThemeOption($album, 'thumb_crop_height');?>" /></td>
		<td><?php echo gettext("The <em>thumb crop height</em> is the maximum height when width is the shortest side"); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Image size:"); ?></td>
		<td><input type="text" size="40" name="image_size"
			value="<?php echo getThemeOption($album, 'image_size');?>" /></td>
		<td><?php echo gettext("Default image display width."); ?></td>
	</tr>
	<tr>
		<td><?php echo gettext("Images size is longest size:"); ?></td>
		<td><input type="checkbox" size="40" name="image_use_longest_side"
			value="1"
			<?php echo checked('1', getThemeOption($album, 'image_use_longest_side')); ?> /></td>
		<td><?php echo gettext("If this is checked the longest side of the image will be <em>image size</em>.").' ';  
		echo gettext("Otherwise, the <em>width</em> of the image will	be <em>image size</em>."); ?></td>
	</tr>
	<?php
	if (!(false === ($requirePath = getPlugin('themeoptions.php', $themename)))) {
		require_once($requirePath);
		$optionHandler = new ThemeOptions();
		$supportedOptions = $optionHandler->getOptionsSupported();
		if (count($supportedOptions) > 0) {
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
	$curdir = getcwd();
	chdir(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER);
	$filelist = safe_glob('*'.'php');
	$c = 0;
?>
<script type="text/javascript">
	$(document).ready(function(){
		$('div.toggler-c').toggleElements( );
	});
	</script>
	<div id="tab_plugin">
	<form action="?action=saveoptions" method="post">
	<input type="hidden" name="savepluginoptions" value="yes" /> 
	
 		<?php
		foreach ($filelist as $extension) {
			$ext = substr($extension, 0, strlen($extension)-4);
			$opt = 'zp_plugin_'.$ext;
			if (getOption($opt)) {
				$option_interface = null;
				require_once($extension);
				if (!is_null($option_interface)) {
					$c++;
					echo '<div class="toggler-c" title="'.$ext.' ">';
					echo "\n<table class=\"bordered\">\n";
					$supportedOptions = $option_interface->getOptionsSupported();
					if (count($supportedOptions) > 0) {
						customOptions($option_interface);
					}
					echo "</table>\n</div>\n";
				}
			}
		}
	?>
<?php
	if ($c == 0) {
		echo gettext("There are no plugin options to adminsiter.");
	} else {
	?>
		<p style="float:right"><?php echo gettext("Click the plugin bar to open/close the options.") ?></p>
		<input type="submit" value= <?php echo gettext('save') ?> />
	<?php 
	}
	?>
	</form>
</div>
<?php
	chdir($curdir);
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



