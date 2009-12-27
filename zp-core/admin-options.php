<?php
/**
 * provides the Options tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

$gallery = new Gallery();
if (!isset($_GET['page'])) {
	if (array_key_exists('options', $zenphoto_tabs)) {
		$_GET['page'] = 'options';
	} else {
		$_GET['page'] = 'users'; // must be a user with no options rights
	}
}
$_current_tab = sanitize($_GET['page'],3);

/* handle posts */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	$themeswitch = false;
	if ($action == 'saveoptions') {
		$table = NULL;

		$woh = getOption('watermark_h_offset');
		$wow = getOption('watermark_w_offset');
		$ws = getOption('watermark_scale');
		$wus = getOption('watermark_allow_upscale');
		$wmchange = false;
		$notify = '';
		$returntab = '';
		$themeoptions = false;
		
		/*** General options ***/
		if (isset($_POST['savegeneraloptions'])) {
			
			if (isset($_POST['allowed_tags_reset'])) {
				setOption('allowed_tags', getOption('allowed_tags_default'));
			} else {
				$tags = $_POST['allowed_tags'];
				$test = "(".$tags.")";
				$a = parseAllowedTags($test);
				if ($a !== false) {
					setOption('allowed_tags', $tags);
					$notify = '';
				} else {
					$notify = '?tag_parse_error';
				}
			}
			setBoolOption('mod_rewrite', isset($_POST['mod_rewrite']));
			setOption('mod_rewrite_image_suffix', sanitize($_POST['mod_rewrite_image_suffix'],3));
			if (isset($_POST['time_zone'])) {
				setOption('time_zone', $tz = sanitize($_POST['time_zone'], 3));
				$offset = 0;
			} else {
				$offset = sanitize($_POST['time_offset'],3);
			}
			setOption('time_offset', $offset);
			setOption('server_protocol', $protocol = sanitize($_POST['server_protocol'],3));
			if ($protocol == 'http') {
				zp_setcookie("zenphoto_ssl", "", time()-368000, $cookiepath);
			}
			setOption('charset', sanitize($_POST['charset']),3);
			setOption('site_email', sanitize($_POST['site_email']),3);
			setBoolOption('tinyMCEPresent', isset($_POST['tinyMCEPresent']));
			$oldloc = getOption('locale', true); // get the option as stored in the database, not what might have been set by a cookie
			$newloc = sanitize($_POST['locale'],3);
			if ($newloc != $oldloc) {
				$cookiepath = WEBPATH;
				if (WEBPATH == '') { $cookiepath = '/'; }
				zp_setCookie('dynamic_locale', $newloc, time()-368000, $cookiepath);  // clear the language cookie
				$encoding = getOption('charset');
				if (empty($encoding)) $encoding = 'UTF-8';
				$result = setlocale(LC_ALL, $newloc.'.'.$encoding, $newloc);
				if (!empty($newloc) && ($result === false)) {
					$notify = '?local_failed='.$newloc;
				}
				setOption('locale', $newloc);
			}
			setBoolOption('multi_lingual', isset($_POST['multi_lingual']));
			$f = sanitize($_POST['date_format_list'],3);
			if ($f == 'custom') $f = sanitize($_POST['date_format'],3);
			setOption('date_format', $f);
			setBoolOption('UTF8_image_URI', isset($_POST['UTF8_image_URI']));
			setOption('captcha', sanitize($_POST['captcha']));
			setBoolOption('use_Imagick', isset($_POST['use_Imagick']));
			$msg = zp_apply_filter('save_admin_general_data', '');
			
			$returntab = "&tab=general";
		}
			
		/*** Gallery options ***/
		if (isset($_POST['savegalleryoptions'])) {
			
			setBoolOption('persistent_archive', isset($_POST['persistent_archive']));
			setBoolOption('album_session', isset($_POST['album_session']));
			setBoolOption('thumb_select_images', isset($_POST['thumb_select_images']));
			setOption('login_user_field', isset($_POST['login_user_field']));
			setOption('gallery_title', process_language_string_save('gallery_title', 2));
			setoption('Gallery_description', process_language_string_save('Gallery_description', 1));
			setOption('website_title', process_language_string_save('website_title', 2));
			$web = sanitize($_POST['website_url'],3);
			setOption('website_url', $web);
			setBoolOption('album_use_new_image_date', isset($_POST['album_use_new_image_date']));
			$st = strtolower(sanitize($_POST['gallery_sorttype'],3));
			if ($st == 'custom') $st = strtolower(sanitize($_POST['customalbumsort'],3));
			setOption('gallery_sorttype', $st);
			if ($st == 'manual') {
				setBoolOption('gallery_sortdirection', 0);
			} else {
				setBoolOption('gallery_sortdirection', isset($_POST['gallery_sortdirection']));
			}
			foreach ($_POST as $item=>$value) {
				if (strpos($item, 'gallery-page_')===0) {
					$item = 'gallery_page_unprotected_'.sanitize(substr($item, 13));
					if (isset($_POST[$item])) {
						$v = 1;
					} else {
						$v = 0;
					}
					setOption($item, $v);
				}
			}
			$olduser = getOption('gallery_user');
			$newuser = sanitize($_POST['gallery_user'],3);
			if (!empty($newuser)) setOption('login_user_field', 1);
			$pwd = trim($_POST['gallerypass']);
			$fail = '';
			if (sanitize($_POST['password_enabled'])) {
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
						setOption('gallery_password', passwordHash($newuser, $pwd));
					}
				} else {
					if (empty($fail)) {
						$notify = '?mismatch=gallery';
					} else {
						$notify = $fail;
					}
				}
				setOption('gallery_hint', process_language_string_save('gallery_hint', 3));
			}
			$returntab = "&tab=gallery";
		}

		/*** Search options ***/
		if (isset($_POST['savesearchoptions'])) {
			$search = new SearchEngine();
			$searchfields = array();
			foreach ($_POST as $key=>$value) {
				if (strpos($key, '_SEARCH_') !== false) {
					$searchfields[] = $value;
				}
			}
			setOption('search_fields', implode(',',$searchfields));
			setOption('exact_tag_match', sanitize($_POST['tag_match']));
			$olduser = getOption('search_user');
			$newuser = sanitize($_POST['search_user'],3);
			if (sanitize($_POST['password_enabled'], 3)) {
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
						setOption('search_password', passwordHash($newuser, $pwd));
					}
				} else {
					if (empty($notify)) {
						$notify = '?mismatch=search';
					} else {
						$notify = $fail;
					}
				}
				setOption('search_hint', process_language_string_save('search_hint', 3));
			}
			setBoolOption('search_space_is_or', isset($_POST['search_space_is_or']));
			setBoolOption('search_no_albums', isset($_POST['search_no_albums']));
			setBoolOption('search_no_images', isset($_POST['search_no_images']));
			setBoolOption('search_no_pages', isset($_POST['search_no_pages']));
			setBoolOption('search_no_news', isset($_POST['search_no_news']));
			$returntab = "&tab=search";
		}
		
		/*** RSS options ***/
		if (isset($_POST['saverssoptions'])) {
			setOption('feed_items', sanitize($_POST['feed_items'],3));
			setOption('feed_imagesize', sanitize($_POST['feed_imagesize'],3));
			setOption('feed_sortorder', sanitize($_POST['feed_sortorder'],3));
			setOption('feed_items_albums', sanitize($_POST['feed_items_albums'],3));
			setOption('feed_imagesize_albums', sanitize($_POST['feed_imagesize_albums'],3));
			setOption('feed_sortorder_albums', sanitize($_POST['feed_sortorder_albums'],3));
			setOption('feed_cache_expire', sanitize($_POST['feed_cache_expire'],3));
			setBoolOption('feed_enclosure', isset($_POST['feed_enclosure']));
			setBoolOption('feed_mediarss', isset($_POST['feed_mediarss']));
			setBoolOption('feed_cache', isset($_POST['feed_cache']));
			setBoolOption('RSS_album_image', isset($_POST['RSS_album_image']));
			setBoolOption('RSS_comments', isset($_POST['RSS_comments']));
			setBoolOption('RSS_articles', isset($_POST['RSS_articles']));
			setBoolOption('RSS_article_comments', isset($_POST['RSS_article_comments']));
			$returntab = "&tab=rss";
		}
		
		/*** Image options ***/
		if (isset($_POST['saveimageoptions'])) {
			setOption('image_quality', sanitize($_POST['image_quality'],3));
			setOption('thumb_quality', sanitize($_POST['thumb_quality'],3));
			setBoolOption('image_allow_upscale', isset($_POST['image_allow_upscale']));
			setBoolOption('thumb_sharpen', isset($_POST['thumb_sharpen']));
			setBoolOption('image_sharpen', isset($_POST['image_sharpen']));
			setOption('sharpen_amount', sanitize_numeric($_POST['sharpen_amount']));
			$num = str_replace(',', '.', sanitize($_POST['sharpen_radius']));
			if (is_numeric($num)) setOption('sharpen_radius', $num);
			setOption('sharpen_threshold', sanitize_numeric($_POST['sharpen_threshold']));
			
			$old = getOption('fullimage_watermark');
			if (isset($_POST['fullimage_watermark'])) {
				$new = sanitize($_POST['fullimage_watermark'], 3);
				setOption('fullimage_watermark', $new);
				$wmchange = $wmchange || $old != $new;
			}
			
			setOption('watermark_scale', sanitize($_POST['watermark_scale'],3));
			setBoolOption('watermark_allow_upscale', isset($_POST['watermark_allow_upscale']));
			setOption('watermark_h_offset', sanitize($_POST['watermark_h_offset'],3));
			setOption('watermark_w_offset', sanitize($_POST['watermark_w_offset'],3));
			
			$imageplugins = array_unique($_zp_extra_filetypes);
			$imageplugins[] = 'Image';
			foreach ($imageplugins as $plugin) {
				$opt = $plugin.'_watermark';
				$old = getOption($opt);
				if (isset($_POST[$opt])) {
					$new = sanitize($_POST[$opt], 3);
					setOption($opt, $new);
					$wmchange = $wmchange || $old != $new;
				}
			}
			
			setOption('full_image_quality', sanitize($_POST['full_image_quality'],3));
			setBoolOption('cache_full_image', isset($_POST['cache_full_image']));
			setOption('protect_full_image', sanitize($_POST['protect_full_image'],3));
			
			$olduser = getOption('protected_image_user');
			$newuser = sanitize($_POST['protected_image_user'],3);
			if (!empty($newuser)) setOption('login_user_field', 1);
			$pwd = trim($_POST['imagepass']);
			if ($olduser != $newuser) {
				if ($pwd != $_POST['imagepass_2']) {
					$pwd2 = trim($_POST['imagepass_2']);
					$_POST['imagepass'] = $pwd;  // invalidate, user changed but password not set
					if (!empty($newuser) && empty($pwd) && empty($pwd2)) $fail = '?mismatch=image_user';
				}
			}
			if ($_POST['imagepass'] == $_POST['imagepass_2']) {
				setOption('protected_image_user',$newuser);
				if (empty($pwd)) {
					if (empty($_POST['imagepass'])) {
						setOption('protected_image_password', NULL);  // clear the gallery password
					}
				} else {
					setOption('protected_image_password', passwordHash($newuser, $pwd));
				}
			} else {
				if (empty($notify)) {
					$notify = '?mismatch=image';
				} else {
					$notify = $fail;
				}
			}
			setOption('protected_image_hint', process_language_string_save('protected_image_hint', 3));
			
			setBoolOption('hotlink_protection', isset($_POST['hotlink_protection']));
			setBoolOption('use_lock_image', isset($_POST['use_lock_image']));
			$st = sanitize($_POST['image_sorttype'],3);
			if ($st == 'custom') $st = strtolower(sanitize($_POST['customimagesort'], 3));
			setOption('image_sorttype', $st);
			setBoolOption('image_sortdirection', isset($_POST['image_sortdirection']));
			setBoolOption('auto_rotate', isset($_POST['auto_rotate']));
			setOption('IPTC_encoding', $_POST['IPTC_encoding']);
			foreach ($_zp_exifvars as $key=>$item) {
				setBoolOption($key, array_key_exists($key, $_POST));
			}
			$returntab = "&tab=image";
		}
		/*** Comment options ***/

		if (isset($_POST['savecommentoptions'])) {
			setOption('spam_filter', sanitize($_POST['spam_filter'],3));
			setBoolOption('email_new_comments', isset($_POST['email_new_comments']));
			setBoolOption('comment_name_required', isset($_POST['comment_name_required']));
			setBoolOption('comment_email_required',isset( $_POST['comment_email_required']));
			setBoolOption('comment_web_required', isset($_POST['comment_web_required']));
			setBoolOption('Use_Captcha', isset($_POST['Use_Captcha']));
			$returntab = "&tab=comments";

		}
		/*** Theme options ***/
		if (isset($_POST['savethemeoptions'])) {
			$themeoptions = true;
			$themename = sanitize($_POST['optiontheme'],3);
			$returntab = "&tab=theme";
			if ($themename) $returntab .= '&optiontheme='.$themename;
			// all theme specific options are custom options, handled below
			if (isset($_POST['themealbum']) && !empty($_POST['themealbum'])) {
				$alb = urldecode(sanitize_path($_POST['themealbum']));
				$table = new Album(new Gallery(), $alb);
				$returntab = '&themealbum='.urlencode($alb).'&tab=theme';
				$themeswitch = $alb != urldecode(sanitize_path($_POST['old_themealbum']));
			} else {
				$table = NULL;
				$themeswitch = urldecode(sanitize_path($_POST['old_themealbum'])) != '';
			}
			if ($themeswitch) {
				$notify = '?switched';
			} else {
				$cw = getThemeOption('thumb_crop_width', $table, $themename);
				$ch = getThemeOption('thumb_crop_height', $table, $themename);
				if (isset($_POST['image_size'])) setThemeOption('image_size', sanitize($_POST['image_size'],3), $table, $themename);
				if (isset($_POST['image_use_side'])) setThemeOption('image_use_side', sanitize($_POST['image_use_side']), $table, $themename);
				if (isset($_POST['thumb_size'])) setThemeOption('thumb_size', sanitize($_POST['thumb_size'],3), $table, $themename);
				setBoolThemeOption('thumb_crop', isset($_POST['thumb_crop']), $table, $themename);
				if (isset($_POST['thumb_crop_width'])) setThemeOption('thumb_crop_width', $ncw = sanitize($_POST['thumb_crop_width'],3), $table, $themename);
				if (isset($_POST['thumb_crop_height'])) setThemeOption('thumb_crop_height', $nch = sanitize($_POST['thumb_crop_height'],3), $table, $themename);
				if (isset($_POST['albums_per_page'])) setThemeOption('albums_per_page', sanitize($_POST['albums_per_page'],3), $table, $themename);
				if (isset($_POST['images_per_page'])) setThemeOption('images_per_page', sanitize($_POST['images_per_page'],3), $table, $themename);
				if (isset($_POST['custom_index_page'])) setThemeOption('custom_index_page', sanitize($_POST['custom_index_page'], 3), $table, $themename);
				if (isset($_POST['user_registration_page'])) setThemeOption('user_registration_page', sanitize($_POST['user_registration_page'], 3), $table, $themename);
				if (isset($_POST['user_registration_text'])) setThemeOption('user_registration_text', process_language_string_save('user_registration_text', 3), $table, $themename);
				if (isset($_POST['user_registration_tip'])) setThemeOption('user_registration_tip', process_language_string_save('user_registration_tip', 3), $table, $themename);
				$otg = getThemeOption('thumb_gray', $table, $themename);
				setBoolThemeOption('thumb_gray', isset($_POST['thumb_gray']), $table, $themename);
				if ($otg = getThemeOption('thumb_gray', $table, $themename)) $wmo = 99; // force cache clear
				$oig = getThemeOption('image_gray', $table, $themename);
				setBoolThemeOption('image_gray', isset($_POST['image_gray']), $table, $themename);
				if ($oig = getThemeOption('image_gray',$table, $themename)) $wmo = 99; // force cache clear
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
			$returntab = "&tab=plugin";
		}
		/*** custom options ***/
		if (!$themeswitch) { // was really a save.
			$returntab = processCustomOptionSave($returntab);
		}
		
		if (($woh != getOption('watermark_h_offset')) ||
					($wow != getOption('watermark_w_offset'))  ||
					($ws != getOption('watermark_scale')) ||
					($wus != getOption('watermark_allow_upscale')) ||
					$wmchange) {
			$gallery->clearCache(); // watermarks (or lack there of) are cached, need to start fresh if the options haave changed
		}
		if (empty($notify)) $notify = '?saved';
		header("Location: " . $notify . $returntab);
		exit();

	}

}
printAdminHeader();
?>
<script type="text/javascript" src="js/farbtastic.js"></script>
<link rel="stylesheet" href="js/farbtastic.css" type="text/css" />
<?php
$subtab = getSubtabs($_current_tab, 'general');
if ($subtab == 'gallery' || $subtab == 'image') {
	$sql = 'SHOW COLUMNS FROM ';
	if ($subtab == 'image') {
		$sql .= prefix('images');
		$targetid = 'customimagesort';
	} else {
		$sql .= prefix('albums');
		$targetid = 'customalbumsort';
	}
	$result = mysql_query($sql);
	$dbfields = array();
	while ($row = mysql_fetch_row($result)) {
		$dbfields[] = "'".$row[0]."'";
	}
	sort($dbfields);
	?>
	<script type="text/javascript" src="js/tag.js"></script>
	<script type="text/javascript">
		$(function () {
			$('#<?php echo $targetid; ?>').tagSuggest({
				tags: [
				<?php echo implode(',', $dbfields);  ?>
				]
			});
		});
	</script>
	<?php
}

echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs($_current_tab);
echo "\n" . '<div id="content">';

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
<?php
printSubtabs($_current_tab, 'general');

if ($subtab == 'general' && $_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	?>
	<div id="tab_gallery" class="tabbox">
		<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
			<input	type="hidden" name="savegeneraloptions" value="yes" />
			<table class="bordered">
				<tr>
				 <td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
				<tr>
					<td width="175"><?php echo gettext("Server protocol:"); ?></td>
					<td width="350">
						<select id="server_protocol" name="server_protocol">
							<?php $protocol = getOption('server_protocol'); ?>
							<option value="http" <?php if ($protocol == 'http') echo 'SELECTED'; ?>>http</option>
							<option value="https" <?php if ($protocol == 'https') echo 'SELECTED'; ?>>https</option>
							<option value="https_admin" <?php if ($protocol == 'https_admin') echo 'SELECTED'; ?>><?php echo gettext('secure admin'); ?></option>
						</select>
					</td>
					<td>
						<p><?php echo gettext("Normally this option should be set to <em>http</em>. If you're running a secure server, change this to <em>https</em>. Select <em>secure admin</em> to insure secure access to <code>admin</code> pages."); ?>
						<p><?php echo gettext("<strong>Note:</strong> Login from the front-end user login form is secure only if <em>https</em> is selected.");?></p>

					</td>
				</tr>
				<tr>
					<?php
					if (function_exists('date_default_timezone_get')) {
						$offset = timezoneDiff($_zp_server_timezone, $tz);
						?>
						<td>
						<?php echo gettext("Timezone:"); ?>
						</td>
						<td>
						<?php
							$zones = getTimezones();
							?>
							<select id="time_zone" name="time_zone">
							<option value=""><?php echo gettext('*not specified'); ?></option>
							<?php generateListFromArray(array($tz = getOption('time_zone')), $zones, false, false); ?>
							</select>
						</td>
						<td>
							<p><?php printf(gettext('Your server reports its timezone as: <code>%s</code>.'), $_zp_server_timezone); ?></p>
							<p><?php printf(ngettext('Your timezone offset is %d hour. If your timezone is different from the servers, select the correct timezone here.', 'Your timezone offset is: %d hours. If your timezone is different from the servers, select the correct timezone here.', $offset), $offset); ?></p>
						</td>
						<?php
					} else {
						$offset = getOption('time_offset');
						?>
						<td><?php echo gettext("Time offset (hours):"); ?></td>
						<td>
							<input type="text" size="3" name="time_offset" value="<?php echo htmlspecialchars($offset);?>" />
						</td>
						<td>
						<p><?php echo gettext("If you're in a different time zone from your server, set the	offset in hours of your timezone from that of the server. For instance if your server is on the US East Coast (<em>GMT</em> - 5) and you are on the Pacific Coast (<em>GMT</em> - 8), set the offset to 3 (-5 - (-8))."); ?></p>
						<?php
					}
					?>
					</td>
				</tr>
				<tr>
					<td><?php echo gettext("URL options:"); ?></td>
					<td>
						<p>
							<label>
								<?php
								$mod_rewrite = getOption('mod_rewrite');
								if (is_null($mod_rewrite)) {
									$state = ' DISABLED';
								} else if ($mod_rewrite) {
									$state = ' CHECKED';
								} else {
									$state = '';
								}
								?>
								<input type="checkbox" name="mod_rewrite" value="1"<?php echo $state; ?> />
								<?php echo gettext('mod rewrite'); ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="UTF8_image_URI" value="1"<?php echo checked('1', getOption('UTF8_image_URI')); ?> />
								<?php echo gettext('UTF8 image URIs'); ?>
							</label>
						</p>
						<p><?php echo gettext("mod_rewrite suffix:"); ?> <input type="text" size="10" name="mod_rewrite_image_suffix" value="<?php echo htmlspecialchars(getOption('mod_rewrite_image_suffix'));?>" /></p>
					</td>
					<td>
						<p>
							<?php
							echo gettext("If you have Apache <em>mod rewrite</em>, put a checkmark on the <em>mod rewrite</em> option, and	you'll get nice cruft-free URLs."); 
							if (is_null($mod_rewrite)) echo ' '.gettext('If the checkbox is disabled, setup did not detect a working Apache <em>mod rewrite</em> facility and proper <em>.htaccess</em> file.');
							?>
						</p>
						<p><?php echo gettext("If you are having problems with images who's names with contain accented characters try changing the <em>UTF8 image URIs</em> setting."); ?></p>
						<p><?php echo gettext("If <em>mod_rewrite</em> is checked above, zenphoto will appended	the <em>mod_rewrite suffix</em> to the end of image URLs. (This helps search engines.) Examples: <em>.html, .php,	/view</em>, etc."); ?></p>
					</td>
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
						<p><?php echo gettext("The language to display text in. (Set to <em>HTTP Accept Language</em> to use the language preference specified by the viewer's browser.)"); ?></p>
						<p><?php echo gettext("Set <em>Multi-lingual</em> to enable multiple languages for database fields."); ?></p>
						<p><?php echo gettext("<strong>Note:</strong> if you have created multi-language strings, uncheck this option, then save anything, you will lose your strings."); ?></p>
					</td>
				</tr>
				<tr>
					<td><?php echo gettext("Date format:"); ?></td>
					<td>
						<select id="date_format_list" name="date_format_list" onchange="showfield(this, 'customTextBox')">
						<?php
						$formatlist = array(gettext('Custom')=>'custom',
								gettext('Preferred date representation') => '%x',
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
						generateListFromArray(array($cv), $formatlist, false, true);
						?>
						</select><br />
						<div id="customTextBox" class="customText" style="display:<?php echo $dsp; ?>">
						<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="date_format"
						value="<?php echo htmlspecialchars(getOption('date_format'));?>" />
						</div>
						</td>
					<td><?php echo gettext('Format for dates. Select from the list or set to <code>custom</code> and provide a <a href="http://us2.php.net/manual/en/function.strftime.php"><span style="white-space:nowrap"><code>strftime()</code></span></a> format string in the text box.'); ?></td>
				</tr>
				<tr>
					<td><?php echo gettext("Charset:"); ?></td>
					<td>
						<select id="charset" name="charset">
						<?php
						$sets = array_merge($_zp_UTF8->iconv_sets, $_zp_UTF8->mb_sets);
						$totalsets = $_zp_UTF8->charsets;
						asort($totalsets);
						foreach ($totalsets as $key=>$char) {
							?>
							<option value="<?php echo  $key; ?>" <?php if ($key == getOption('charset')) echo 'selected="SELECTED"'; if (!array_key_exists($key,$sets)) echo 'style="color: gray"'; ?>><?php echo $char; ?></option>
							<?php
						}
						?>
						</select>
					</td>
					<td>
					<?php
					echo gettext('The character encoding to use internally. Leave at <em>Unicode	(UTF-8)</em> if you are unsure.');
					if (!function_exists('mb_list_encodings')) {
						echo ' '.gettext('Character sets <span style="color:gray">shown in gray</span> have no character translation support.');
					}
					?>
					</td>
				</tr>
				<?php
				if (class_exists('lib_auth_options')) {
					customOptions(new lib_auth_options(), "");
				}
				?>
				<tr>
					<td><?php echo gettext('Captcha generator:'); ?></td>
					<td>
						<select id="captcha" name="captcha">
						<?php
						$captchas = getPluginFiles('*.php','captcha');
						generateListFromArray(array(getOption('captcha')), array_keys($captchas),false,false);
						?>
						</select>
					</td>
					<td><?php echo gettext('Select the <em>Captcha</em> generator to be used by Zenphoto.'); ?></td>
				</tr>
				<?php customOptions($_zp_captcha, "&nbsp;&nbsp;&nbsp;-&nbsp;"); ?>
				<tr>
					<td><?php echo gettext("Allowed tags:"); ?></td>
					<td>
						<p><textarea name="allowed_tags" style="width: 310px" rows="10"><?php echo htmlspecialchars(getOption('allowed_tags')); ?></textarea></p>
						<p>
							<label>
								<input type="checkbox" name="allowed_tags_reset" value="1" />
								<?php echo gettext('restore default allowed tags'); ?>
							</label>
						</p>
					</td>
					<td>
						<p><?php echo gettext("Tags and attributes allowed in comments, descriptions, and other fields."); ?></p>
						<p><?php echo gettext("Follow the form <em>tag</em> =&gt; (<em>attribute</em> =&gt; (<em>attribute</em>=&gt; (), <em>attribute</em> =&gt; ()...)))"); ?></p>
						<p><?php echo gettext('Check <em>restore default allowed tags</em> to reset allowed tags to the zenphoto default values.') ?></p>
					</td>
				</tr>			
				<tr>
					<td><?php echo gettext("TinyMCE editing:"); ?></td>
					<td>
						<label>
							<input type="checkbox" name="tinyMCEPresent" <?php if ($_tinyMCEPresent>=0) {echo 'value="1"'; if ($_tinyMCEPresent) echo ' CHECKED';} else { echo 'DISABLED value= "0"';} ?> />
							<?php echo gettext('enabled'); ?>
						</label>
					<td>
						<?php
						if ($_tinyMCEPresent>=0) {
							echo gettext('Enable TinyMCE for use in back-end editing.');
						} else {
							echo gettext('TinyMCE is not available.');
						}
						?>
					</td>
				</tr>
				<tr>
					<td width="175"><?php echo gettext("Site email:"); ?></td>
					<td width="350">
						<input type="text" size="40" id="site_email" name="site_email" style="width: 338px" value="<?php echo getOption('site_email'); ?>" />
					</td>
					<td><?php echo gettext("This email address will be used as the <em>From</em> address for all mails sent by Zenphoto."); ?></td>
				</tr>
				<?php
				if ($_zp_imagick_present) {
					?>
					<tr>
						<td><?php echo gettext("Imagick:"); ?></td>
						<td>
							<label>
								<input type="checkbox" name="use_Imagick" value="1" <?php if (getOption('use_Imagick')) echo ' CHECKED'; ?> />
								<?php echo gettext('enabled'); ?>
							</label>
						</td>
						<td>
							<?php echo gettext('Imagick is present on your server. Check this option to enalbe it.') ?>
						</td>
					</tr>
					<?php
				}
				?>
				<?php zp_apply_filter('admin_general_data'); ?>
				<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<!-- end of tab-general div -->
	<?php
}
if ($subtab == 'gallery' && $_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	?>
	<div id="tab_gallery" class="tabbox">
		<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
			<input	type="hidden" name="savegalleryoptions" value="yes" />
			<input	type="hidden" name="password_enabled" id="password_enabled" value=0 />
			<table class="bordered">
				<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
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
				<tr class="passwordextrashow">
					<td style="background-color: #ECF1F2;">
						<p>
							<a href="javascript:toggle_passwords('',true);">
								<?php echo gettext("Gallery password:"); ?>
							</a>
						</p>
					</td>
					<td style="background-color: #ECF1F2;">
					<?php
					$x = getOption('gallery_password');
					if (!empty($x)) echo "**********";
					?>
					</td>
					<td style="background-color: #ECF1F2;">
						<p><?php echo gettext("Master password for the gallery. Click on <em>Gallery password</em> to change."); ?></p>
					</td>
				</tr>
				<tr class="passwordextrahide" style="display:none" >
					<td>
						<p>
							<a href="javascript:toggle_passwords('',false);">
							<?php echo gettext("Gallery guest user:"); ?>
							</a>
						</p>
						<p>
							<?php echo gettext("Gallery password:"); ?><br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
						</p>
						<p><?php echo gettext("Gallery password hint:"); ?></p>
					</td>
					<td>
						<p><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="gallery_user" value="<?php echo htmlspecialchars(getOption('gallery_user')); ?>" /></p>
						<p>
							<?php $x = getOption('gallery_password'); if (!empty($x)) { $x = '          '; } ?>
							<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="gallerypass" value="<?php echo $x; ?>" />
							<br />
							<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="gallerypass_2" value="<?php echo $x; ?>" />
						</p>
						<p><?php print_language_string_list(getOption('gallery_hint'), 'gallery_hint', false) ?></p>
					</td>
					<td>
						<p><?php echo gettext("User ID for the gallery guest user") ?></p>
						<p><?php echo gettext("Master password for the gallery. If this is set, visitors must know this password to view the gallery."); ?></p>
						<p><?php echo gettext("A reminder hint for the password."); ?></p>
					</td>
				</tr>
				<tr>
					<td><?php echo gettext('Unprotected pages:'); ?></td>
					<td>
						<?php
						$curdir = getcwd();
						$root = SERVERPATH.'/'.THEMEFOLDER.'/'.$gallery->getCurrentTheme().'/';
						chdir($root);
						$filelist = safe_glob('*.php');
						$list = array();
						foreach($filelist as $file) {
							$list[] = str_replace('.php', '', filesystemToInternal($file));
						}
						chdir($curdir);
						$list = array_diff($list, standardScripts());
						$list[] = 'index';
						$current = array();
						foreach ($list as $page) {
							?>
							<input type="hidden" name="gallery-page_<?php echo $page; ?>" value="0" />
							<?php
							if (getOption('gallery_page_unprotected_'.$page)) {
								$current[] = $page;
							}
						}
						?>
						<ul class="customchecklist">
							<?php generateUnorderedListFromArray($current, $list, 'gallery_page_unprotected_', false, true, false); ?>
						</ul>
					</td>
					<td><?php echo gettext('Place a checkmark on any pages which should not be protected by the gallery password.'); ?></td>
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
					<td><?php echo gettext("Sort gallery by:"); ?></td>
					<td>
						<?php
						$sort = $sortby;
						$sort[gettext('Manual')] = 'manual';
						$sort[gettext('Custom')] = 'custom';
						$cvt = $cv = strtolower(getOption('gallery_sorttype'));
						ksort($sort);
						$flip = array_flip($sort);
						if (isset($flip[$cv])) {
							$dspc = 'none';
						} else {
							$dspc = 'block';
						}
						if (($cv == 'manual') || ($cv == '')) {
							$dspd = 'none';
						} else {
							$dspd = 'block';
						}
						?>
						<table>
							<tr>
								<td>
									<select id="sortselect" name="gallery_sorttype" onchange="update_direction(this,'gallery_sortdirection','customTextBox2')">
									<?php
									if (array_search($cv, $sort) === false) $cv = 'custom';
									generateListFromArray(array($cv), $sort, false, true);
									?>
									</select>
								</td>
								<td>
									<span id="gallery_sortdirection" style="display:<?php echo $dspd; ?>">
										<label>
											<input type="checkbox" name="gallery_sortdirection"	value="1" <?php echo checked('1', getOption('gallery_sortdirection')); ?> />
											<?php echo gettext("Descending"); ?>
										</label>
									</span>
									</td>
								</tr>
							<tr>
								<td colspan="2">
									<span id="customTextBox2" class="customText" style="display:<?php echo $dspc; ?>">
									<?php echo gettext('custom fields:') ?>
									<input id="customalbumsort" name="customalbumsort" type="text" value="<?php echo $cvt; ?>"></input>
									</span>
								</td>
							</tr>
						</table>
					</td>
					<td>
						<?php
						echo gettext('Sort order for the albums on the index of the gallery. Custom sort values must be database field names. You can have multiple fields separated by commas. This option is also the default sort for albums and subalbums.');
						?>
					</td>
				</tr>
				<tr>
					<td><?php echo gettext("Gallery behavior:"); ?></td>
					<td>
						<p>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="album_use_new_image_date" id="album_use_new_image_date"
											value="1" <?php echo checked('1', getOption('album_use_new_image_date')); ?> />
									<?php echo gettext("use latest image date as album date"); ?>
								</label>
							</span>
						</p>
						<p>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="login_user_field" id="login_user_field"
											value="1" <?php echo checked('1', getOption('login_user_field')); ?> />
									<?php echo gettext("enable user name login field"); ?>
								</label>
							</span>
						</p>
						<p>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="thumb_select_images" id="thumb_select_images"
											value="1" <?php echo checked('1', getOption('thumb_select_images')); ?> />
									<?php echo gettext("visual thumb selection"); ?>
								</label>
							</span>
						</p>
						<p>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="persistent_archive" id="persistent_archive"
											value="1" <?php echo checked('1', getOption('persistent_archive')); ?> />
									<?php echo gettext("enable persistent archives"); ?>
								</label>
							</span>
						</p>
						<p>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="album_session" id="album_session"
											value="1" <?php echo checked('1', getOption('album_session')); ?> />
									<?php echo gettext("enable gallery sessions"); ?>
								</label>
							</span>
						</p>					
					</td>
					<td>
						<p><?php  echo gettext("<a href=\"javascript:toggle('albumdate');\" >Details</a> for <em>use latest image date as album date</em>" ); ?></p>
						<div id="albumdate" style="display: none">
							<p><?php echo gettext("If you wish your album date to reflect the date of the latest image uploaded set set this option. Otherwise the date will be set initially to the date the album was created.") ?></p>
						</div>

						<p><?php  echo gettext("<a href=\"javascript:toggle('username');\" >Details</a> for <em>enable user name login field</em>" ); ?></p>
						<div id="username" style="display: none">
						<p><?php echo gettext("This option places a field on the gallery (search, album) login form for entering a user name. This is necessary if you have set guest login user names. It is also useful to allow Admin users to log in on these pages rather than at the Admin login."); ?></p>
						</div>

						<p><?php  echo gettext("<a href=\"javascript:toggle('visualthumb');\" >Details</a> for <em>visual thumb selection</em>" ); ?></p>
						<div id="visualthumb" style="display: none">
						<p><?php echo gettext("Setting this places thumbnails in the album thumbnail selection list (the dropdown list on each album's edit page). In Firefox the dropdown shows the thumbs, but in IE and Safari only the names are displayed (even if the thumbs are loaded!). In albums with many images loading these thumbs takes much time and is unnecessary when the browser won't display them. Uncheck this option and the images will not be loaded. "); ?></p>
						</div>

						<p><?php  echo gettext("<a href=\"javascript:toggle('persistentarchive');\" >Details</a> for <em>enable persistent archive</em>" ); ?></p>
						<div id="persistentarchive" style="display: none">
						<p><?php echo gettext("Put a checkmark here to re-serve Zip Archive files if you are using the optional template function <em>printAlbumZip()</em> to enable visitors of your site to download images of an album as .zip files. If not checked	that .zip file will be regenerated each time."); ?>
							<?php echo gettext("<strong>Note: </strong>Setting	this option may impact password protected albums!"); ?></p>
						</div>

						<p><?php  echo gettext("<a href=\"javascript:toggle('gallerysessions');\" >Details</a> for <em>enable gallery sessions</em>" ); ?></p>
						<div id="gallerysessions" style="display: none">
						<p><?php echo gettext("Check this option if you are having issues with album password cookies not being retained. Setting the option causes zenphoto to use sessions rather than cookies."); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<!-- end of tab-gallery div -->
	<?php
}
if ($subtab == 'search' && $_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	?>
	<div id="tab_search" class="tabbox">
		<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
			<input	type="hidden" name="savesearchoptions" value="yes" />
			<input	type="hidden" name="password_enabled" id="password_enabled" value=0 />
			<table class="bordered">
				<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
				<tr class="passwordextrashow">
					<td width="175" style="background-color: #ECF1F2;">
						<p>
							<a href="javascript:toggle_passwords('',true);">
								<?php echo gettext("Search password:"); ?>
							</a>
						</p>
					</td>
					<td style="background-color: #ECF1F2;">
						<?php
						$x = getOption('search_password');
						if (!empty($x)) echo "**********";
						?>
					</td>
					<td style="background-color: #ECF1F2;">
						<p><?php echo gettext("Password for the search guest user. click on <em>Search password</em> to change."); ?></p>
					</td>
				</tr>
				<tr class="passwordextrahide" style="display:none" >
					<td width="175">
						<p>
							<a href="javascript:toggle_passwords('',false);">
								<?php echo gettext("Search guest user:"); ?>
							</a>
						</p>
						<p>
							<?php echo gettext("Search password:"); ?><br />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
						</p>
						<p><?php echo gettext("Search password hint:"); ?></p>
					</td>
					<td>
						<p><input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="search_user" value="<?php echo htmlspecialchars(getOption('search_user')); ?>" /></p>
						<p>
							<?php $x = getOption('search_password'); if (!empty($x)) { $x = '          '; } ?>
							<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="searchpass" value="<?php echo $x; ?>" />
							<br />
							<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="searchpass_2" value="<?php echo $x; ?>" />
						</p>
						<p><?php print_language_string_list(getOption('search_hint'), 'search_hint', false) ?></p>
					</td>
					<td>
						<p><?php echo gettext("User ID for the search guest user") ?></p>
						<p><?php echo gettext("Password for the search guest user. If this is set, visitors must know this password to view search results."); ?></p>
						<p><?php echo gettext("A reminder hint for the password."); ?></p>
					</td>
				</tr>
				<tr>
					<td><?php echo gettext("Search behavior settings:"); ?></td>
					<?php 
					$exact = ' <input type="radio" id="exact_tags" name="tag_match" value="1" ';
					$partial = ' <input type="radio" id="exact_tags" name="tag_match" value="0" ';
					if (getOption('exact_tag_match')) {
						$exact .= ' CHECKED ';
					} else {
						$partial .= ' CHECKED ';
					}
					$exact .= '/>'. gettext('exact');
					$partial .= '/>'. gettext('partial');
					$engine = new SearchEngine();
					$fields = $engine->getSearchFieldList();
					$fields['tags'] .= $exact.$partial;
					$fields = array_flip($fields);
					$set_fields = array_flip($engine->allowedSearchFields());
					?>
					<td>
						<?php echo gettext('Fields list:'); ?>
						<ul class="searchchecklist">
							<?php
							generateUnorderedListFromArray($set_fields, $fields, '_SEARCH_', false, true, true);
							?>
						</ul>
						<br />
						<p>
							<label>
								<input type="checkbox" name="search_space_is_or" value="1" <?php echo checked('1', getOption('search_space_is_or')); ?> />
								<?php echo gettext('Treat spaces as <em>OR</em>') ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="search_no_albums" value="1" <?php echo checked('1', getOption('search_no_albums')); ?> />
								<?php echo gettext('Do not return <em>album</em> matches') ?>
							</label>
						</p>
						<p>
							<label>
								<input type="checkbox" name="search_no_images" value="1" <?php echo checked('1', getOption('search_no_images')); ?> />
								<?php echo gettext('Do not return <em>image</em> matches') ?>
							</label>
						</p>
						<?php
						if (getOption('zp_plugin_zenpage')) {
							?>
							<p>
								<label>
									<input type="checkbox" name="search_no_news" value="1" <?php echo checked('1', getOption('search_no_news')); ?> />
									<?php echo gettext('Do not return <em>news</em> matches') ?>
								</label>
							</p>
							<p>
								<label>
									<input type="checkbox" name="search_no_pages" value="1" <?php echo checked('1', getOption('search_no_pages')); ?> />
									<?php echo gettext('Do not return <em>page</em> matches') ?>
								</label>
							</p>
							<?php
						}
					?>
					</td>
					<td>
						<p><?php echo gettext('Search behavior settings.') ?></p>
						<p><?php echo gettext("<em>Field list</em> is the set of fields on which searches may be performed."); ?></p>
						<p><?php echo gettext("Search does partial matches on all fields selected with the possible exception of <em>Tags</em>. This means that if the field contains the search criteria anywhere within it a result will be returned. If <em>exact</em> is selected for <em>Tags</em> then the search criteria must exactly match the tag for a result to be returned.") ?></p>
						<p><?php echo gettext('Setting <code>Treat spaces as <em>OR</em></code> will cause search to trigger on any of the words in a string separated by spaces. Leaving the option unchecked will treat the whole string as a search target.') ?></p>
						<p><?php echo gettext('Setting <code>Do not return <em>{item}</em> matches</code> will cause search to ignore <em>{items}</em> when looking for matches. No albums will be returned from the <code>next_album()</code> loop.') ?></p>
					</td>
				</tr>
				<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<!-- end of tab-search div -->
 <?php
}
if ($subtab == 'rss' && $_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	?>
	<div id="tab_rss" class="tabbox">
		<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
		<input	type="hidden" name="saverssoptions" value="yes" />
	<table class="bordered">
		<tr>
			<td colspan="3">
			<p class="buttons">
			<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
			<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
			</p>
			</td>
		</tr>
		<tr>
			<td width="175"><?php echo gettext("RSS feeds enabled:"); ?></td>
			<td>
				<span style="white-space:nowrap">
					<label>
						<input type="checkbox" name="RSS_album_image" value=<?php if (getOption('RSS_album_image')) echo '1 CHECKED'; else echo '0'; ?> /> <?php echo gettext('Gallery'); ?>
					</label>
				</span>
				<span style="white-space:nowrap">
					<label>
						<input type="checkbox" name="RSS_comments" value=<?php if (getOption('RSS_comments')) echo '1 CHECKED'; else echo '0'; ?> /> <?php echo gettext('Comments'); ?>
					</label>
				</span>
				<span style="white-space:nowrap">
					<label>
						<input type="checkbox" name="RSS_articles" value=<?php if (getOption('RSS_articles')) echo '1 CHECKED'; else echo '0'; ?> /> <?php echo gettext('All news'); ?>
					</label>
				</span>
				<span style="white-space:nowrap">
					<label>
						<input type="checkbox" name="RSS_article_comments" value=<?php if (getOption('RSS_article_comments')) echo '1 CHECKED'; else echo '0'; ?> /> <?php echo gettext('News/Page comments'); ?>
					</label>
				</span>
			</td>
			<td><?php echo gettext("Check each RSS feed you wish to activate. Note: Theme support is required to display RSS links."); ?></td>
		</tr>
		<tr>
			<td width="175"><?php echo gettext("Number of RSS feed items:"); ?></td>
			<td width="350">
			<input type="text" size="15" id="feed_items" name="feed_items" value="<?php echo htmlspecialchars(getOption('feed_items'));?>" /> <label for="feed_items"><?php echo gettext("Images RSS"); ?></label><br />
			<input type="text" size="15" id="feed_items_albums" name="feed_items_albums" value="<?php echo htmlspecialchars(getOption('feed_items_albums'));?>" /> <label for="feed_items"><?php echo gettext("Albums RSS"); ?></label>
			</td>
			<td><?php echo gettext("The number of new items you want to appear in your site's RSS feed. The images and comments RSS share the value."); ?></td>
		</tr>
		<tr>
			<td><?php echo gettext("Size of RSS feed images:"); ?></td>
			<td>
			<input type="text" size="15" id="feed_imagesize" name="feed_imagesize"
				value="<?php echo htmlspecialchars(getOption('feed_imagesize'));?>" /> <label for="feed_imagesize"><?php echo gettext("Images RSS"); ?></label><br /> 
				<input type="text" size="15" id="feed_imagesize_albums" name="feed_imagesize_albums"
				value="<?php echo htmlspecialchars(getOption('feed_imagesize_albums'));?>" /> <label for="feed_imagesize_albums"><?php echo gettext("Albums RSS"); ?></label>
				</td>
			<td><?php echo gettext("The size you want your images to have in your site's RSS feed."); ?></td>
		</tr>
		<tr>
			<td><?php echo gettext("RSS feed sort order:"); ?></td>
			<td>
			<?php 
			$feedsortorder = array(gettext('latest by id')=>'latest',
						gettext('latest by date')=>'latest-date',
						gettext('latest by mtime')=>'latest-mtime'
						);
			$feedsortorder_albums = array(gettext('latest by id')=>'latest',
						gettext('latest updated')=>'latestupdated'
						);
			?>		
			<select id="feed_sortorder" name="feed_sortorder">
			<?php generateListFromArray(array(getOption("feed_sortorder")), $feedsortorder, false, true); ?>
			</select> <label for="feed_sortorder"><?php echo gettext("Images RSS"); ?></label><br /><br /> 
			<select id="feed_sortorder_albums" name="feed_sortorder_albums">
			<?php generateListFromArray(array(getOption("feed_sortorder_albums")), $feedsortorder_albums, false, true); ?>
			</select> <label for="feed_sortorder_albums"><?php echo gettext("Albums RSS"); ?></label>
			</td>
			<td><?php echo gettext("a) Images RSS: Choose between <em>latest by id</em> for the latest uploaded, <em>latest by date</em> for the latest uploaded fetched by date, or <em>latest by mtime</em> for the latest uploaded fetched by the file's last change timestamp.<br />b) Albums RSS: Choose between <em>latest by id</em> for the latest uploaded and <em>latest updated</em>"); ?></td>
		</tr>
		<tr>
			<td><?php echo gettext("RSS enclosure:"); ?></td>
			<td><input type="checkbox" name="feed_enclosure"
				value="1" <?php echo checked('1', getOption('feed_enclosure')); ?> /></td>
			<td><?php echo gettext("Check if you want to enable the <em>rss enclosure</em> feature which provides a direct download for full images, movies etc. from within certain rss reader clients <em>(only Images RSS)</em>."); ?></td>
		</tr>
			<tr>
			<td><?php echo gettext("Media RSS:"); ?></td>
			<td><input type="checkbox" name="feed_mediarss" value="1" <?php echo checked('1', getOption('feed_mediarss')); ?> /></td>
			<td><?php echo gettext("Check if <em>media rss</em> support is to be enabled. This support is used by some services and programs <em>(only Images RSS)</em>."); ?></td>
		</tr>
		<tr>
			<td><?php echo gettext("RSS cache"); ?></td>
			<td>
				<label><input type="checkbox" name="feed_cache" value="1" <?php echo checked('1', getOption('feed_cache')); ?> /> <?php echo gettext("Enabled"); ?></label><br /><br />
				<input type="text" size="15" id="feed_cache_expire" name="feed_cache_expire"
				value="<?php echo htmlspecialchars(getOption('feed_cache_expire'));?>" /> <label for="feed_cache_expire"><?php echo gettext("RSS cache expire"); ?></label><br /> 
				</td>
			<td><?php echo gettext("Check if you want to enable static RSS feed caching. The cached file will be placed within the <em>cache_html</em> folder.<br /> Cache expire default is 86400 seconds (1 day  = 24 hrs * 60 min * 60 sec)."); ?></td>
		</tr>
		<tr>
			<td colspan="3">
			<p class="buttons">
			<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
			<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
			</p>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<!-- end of tab-rss div -->
<?php
}
if ($subtab == 'image' && $_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	if (isset($_GET['mismatch'])) {
		echo '<div class="errorbox" id="fade-message">';
		switch ($_GET['mismatch']) {
			case 'image':
				echo  "<h2>". sprintf(gettext("Your %s passwords were empty or did not match"), $_GET['mismatch'])."</h2>";
				break;
			case 'image_user':
				echo  "<h2>". gettext("You must supply a password for the Protected image user")."</h2>";
				break;
		}
		echo '</div>';
	}
		?>
	<div id="tab_image" class="tabbox">
	<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
	<input type="hidden" name="saveimageoptions" value="yes" /> 
	<p align="center">
	<?php echo gettext('See also the <a href="?tab=theme">Theme Options</a> tab for theme specific image options.'); ?>
	</p>

	<table class="bordered">
		<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
		<tr>
			<td><?php echo gettext("Sort images by:"); ?></td>
			<td>
				<?php
				$sort = $sortby;
				$cvt = $cv = getOption('image_sorttype');
				$sort[gettext('Custom')] = 'custom';
				$flip = array_flip($sort);
				if (isset($flip[$cv])) {
					$dsp = 'none';
				} else {
					$dsp = 'block';
				}
				?>
				<select id="imagesortselect" name="image_sorttype"  onchange="showfield(this, 'customTextBox3')">
				<?php
				if (array_search($cv, $sort) === false) $cv = 'custom';
				generateListFromArray(array($cv), $sort, false, true);
				?>
				</select>
				<label>
					<input type="checkbox" name="image_sortdirection" value="1" <?php echo checked('1', getOption('image_sortdirection')); ?> />
					<?php echo gettext("Descending"); ?>
				</label>
				<div id="customTextBox3" class="customText" style="display:<?php echo $dsp; ?>">
				<?php echo gettext('custom fields:') ?>
				<input id="customimagesort" name="customimagesort" type="text" value="<?php echo $cvt; ?>"></input>
				</div>
				</td>
			<td>
				<p><?php	echo gettext("Default sort order for images."); ?></p>
				<p><?php echo gettext('Custom sort values must be database field names. You can have multiple fields separated by commas.') ?></p>
			</td>
		</tr>
		<tr>
			<td width="175"><?php echo gettext("Image quality:"); ?></td>
			<td width="350">
				<?php echo gettext('Normal Image'); ?>&nbsp;<input type="text" size="3" id="imagequality" name="image_quality" value="<?php echo getOption('image_quality');?>" />
				<script type="text/javascript">
				$(function() {
					$("#slider-imagequality").slider({
						<?php $v = getOption('image_quality'); ?>
						startValue: <?php echo $v; ?>,
						value: <?php echo $v; ?>,
						min: 0,
						max: 100,
						slide: function(event, ui) {
							$("#imagequality").val( ui.value);
						}
					});
					$("#imagequality").val($("#slider-imagequality").slider("value"));
				});
				</script>
				<div id="slider-imagequality"></div>
				<?php echo gettext('<em>full</em> Image'); ?>&nbsp;<input type="text" size="3" id="fullimagequality" name="full_image_quality" value="<?php echo getOption('full_image_quality');?>" />
				<script type="text/javascript">
				$(function() {
					$("#slider-fullimagequality").slider({
						<?php $v = getOption('full_image_quality'); ?>
						startValue: <?php echo $v; ?>,
						value: <?php echo $v; ?>,
						min: 0,
						max: 100,
						slide: function(event, ui) {
							$("#fullimagequality").val( ui.value);
						}
					});
					$("#fullimagequality").val($("#slider-fullimagequality").slider("value"));
				});
				</script>
				<div id="slider-fullimagequality"></div>
				<?php echo gettext('Thumbnail'); ?>&nbsp;<input type="text" size="3" id="thumbquality" name="thumb_quality" value="<?php echo getOption('thumb_quality');?>" />
				<script type="text/javascript">
				$(function() {
					$("#slider-thumbquality").slider({
						<?php $v = getOption('thumb_quality'); ?>
						startValue: <?php echo $v; ?>,
						value: <?php echo $v; ?>,
						min: 0,
						max: 100,
						slide: function(event, ui) {
							$("#thumbquality").val( ui.value);
						}
					});
					$("#thumbquality").val($("#slider-thumbquality").slider("value"));
				});
				</script>
				<div id="slider-thumbquality"></div>
			</td>
			<td>
				<p><?php echo gettext("Compression quality for images and thumbnails generated by Zenphoto.");?></p>
				<p><?php echo gettext("Quality ranges from 0 (worst quality, smallest file) to 100 (best quality, biggest file). "); ?></p>
			</td>
		</tr>
		<tr>
			<td width="175"><?php echo gettext("Auto rotate images:"); ?></td>
			<td><input type="checkbox" size="<?php echo TEXT_INPUT_SIZE; ?>" name="auto_rotate"	value="1"
				<?php echo checked('1', getOption('auto_rotate')); ?>
				<?php if (!zp_imageCanRotate()) echo ' DISABLED'; ?>	/></td>
			<td>
				<p><?php	echo gettext("Automatically rotate images based on the EXIF orientation setting."); ?></p>
				<?php
				if (!function_exists('imagerotate')) echo '<p>'.gettext("Image rotation requires the <em>imagerotate</em> function found in PHP version 4.3 or greater's bundled GD library.").'</p>';
				?>
			</td>
		</tr>
		<tr>
			<td><?php echo gettext("Allow upscale:"); ?></td>
			<td><input type="checkbox" size="<?php echo TEXT_INPUT_SIZE; ?>" name="image_allow_upscale" value="1" <?php echo checked('1', getOption('image_allow_upscale')); ?> /></td>
			<td><?php echo gettext("Allow images to be scaled up to the requested size. This could	result in loss of quality, so it's off by default."); ?></td>
		</tr>
		<tr>
			<td><?php echo gettext("Sharpen:"); ?></td>
			<td>
				<label>
					<input type="checkbox" name="image_sharpen" value="1" <?php echo checked('1', getOption('image_sharpen')); ?> />
					<?php echo gettext('Images'); ?>
				</label>
				<label>
					<input type="checkbox" name="thumb_sharpen" value="1" <?php echo checked('1', getOption('thumb_sharpen')); ?> />
					<?php echo gettext('Thumbs'); ?>
				</label>
				<br />
				<?php echo gettext('Amount'); ?>&nbsp;<input type="text" id="sharpenamount" name="sharpen_amount" size="3" value="<?php echo getOption('sharpen_amount'); ?>" />
				<script type="text/javascript">
				$(function() {
					$("#slider-sharpenamount").slider({
					<?php $v = getOption('sharpen_amount'); ?>
						<?php $v = getOption('sharpen_amount'); ?>
						startValue: <?php echo $v; ?>,
						value: <?php echo $v; ?>,
						min: 0,
						max: 100,
						slide: function(event, ui) {
							$("#sharpenamount").val( ui.value);
						}
					});
					$("#sharpenamount").val($("#slider-sharpenamount").slider("value"));
				});
				</script>
				<div id="slider-sharpenamount"></div>
				<table>
					<tr>
						<td style="margin:0; padding:0"><?php echo gettext('Radius'); ?>&nbsp;</td>
						<td style="margin:0; padding:0"><input type="text" name = "sharpen_radius" size="2" value="<?php echo getOption('sharpen_radius'); ?>" /></td>
					</tr>
					<tr>
						<td style="margin:0; padding:0"><?php echo gettext('Threshold'); ?>&nbsp;</td>
						<td style="margin:0; padding:0"><input type="text" name = "sharpen_threshold" size="3" value="<?php echo getOption('sharpen_threshold'); ?>" /></td>
					</tr>
					</table>
			</td>
			<td>
				<p><?php echo gettext("Add an unsharp mask to images and/or thumbnails. <strong>Warning</strong>: can overload slow servers."); ?></p>
				<p><?php echo gettext("<em>Amount</em>: the strength of the sharpening effect. Values are between 0 (least sharpening) and 100 (most sharpening)."); ?></p>
				<p><?php echo gettext("<em>Radius</em>: the pixel radius of the sharpening mask. A smaller radius sharpens smaller details, and a larger radius sharpens larger details."); ?></p>
				<p><?php echo gettext("<em>Threshold</em>: the color difference threshold required for sharpening. A low threshold sharpens all edges including faint ones, while a higher threshold only sharpens more distinct edges."); ?></p>
			</td>
		</tr>
		<tr>
			<td><?php echo gettext("Watermarks:"); ?></td>
			<td>
				<table>
				<?php
				$current = getOption('fullimage_watermark');
				?>
				<tr>
					<td style="margin:0; padding:0;"><?php echo gettext('Images'); ?> </td>
					<td style="margin:0; padding:0">
						<select id="fullimage_watermark" name="fullimage_watermark">
							<option value="" <?php if (empty($current)) echo ' selected="SELECTED"' ?> style="background-color:LightGray"><?php echo gettext('*none'); ?></option>
							<?php
							$watermarks = getWatermarks();
							generateListFromArray(array($current), $watermarks, false, false);
							?>
						</select>
					</td>
				</tr>
				<?php
				$imageplugins = array_unique($_zp_extra_filetypes);
				$imageplugins[] = 'Image';
				ksort($imageplugins);
				foreach ($imageplugins as $plugin) {
					$opt = $plugin.'_watermark';
					$current = getOption($opt);
					?>
					<tr>
						<td style="margin:0; padding:0;"><?php	echo $plugin;	?> <?php echo gettext('thumbnails'); ?> </td>
						<td style="margin:0; padding:0">
							<select id="<?php echo $opt; ?>" name="<?php echo $opt; ?>">
							<option value="" <?php if (empty($current)) echo ' selected="SELECTED"' ?> style="background-color:LightGray"><?php echo gettext('*none'); ?></option>
							<?php
							$watermarks = getWatermarks();
							generateListFromArray(array($current), $watermarks, false, false);
							?>
							</select>
						</td>
					</tr>
					<?php
					}
				?>
				</table>
				<br />
				<?php echo gettext('cover').' '; ?>
				<input type="text" size="2" name="watermark_scale"
						value="<?php echo htmlspecialchars(getOption('watermark_scale'));?>" /><?php /*xgettext:no-php-format*/ echo gettext('% of image') ?>
				<span style="white-space:nowrap">
					<label>
						<input type="checkbox" name="watermark_allow_upscale" value="1"
						<?php echo checked('1', getOption('watermark_allow_upscale')); ?> />
						<?php echo gettext("allow upscale"); ?>
					</label>
				</span>
				<br />
				<?php echo gettext("offset h"); ?>
				<input type="text" size="2" name="watermark_h_offset"
						value="<?php echo htmlspecialchars(getOption('watermark_h_offset'));?>" /><?php echo /*xgettext:no-php-format*/ gettext("% w, "); ?>
				<input type="text" size="2" name="watermark_w_offset"
					value="<?php echo htmlspecialchars(getOption('watermark_w_offset'));?>" /><?php /*xgettext:no-php-format*/ echo gettext("%"); ?>
			</td>
			<td>
				<p><?php echo gettext("The watermark image is scaled by to cover <em>cover percentage</em> of the image and placed relative to the upper left corner of the	image."); ?></p>
				<p><?php echo gettext("It is offset from there (moved toward the lower right corner) by the <em>offset</em> percentages of the height and width difference between the image and the watermark."); ?></p>
				<p><?php echo gettext("If <em>allow upscale</em> is not checked the watermark will not be made larger than the original watermark image."); ?></p>
				<p><?php printf(gettext('Images are in png-24 format and are located in the <code>/%s/watermarks/</code> folder.'), USER_PLUGIN_FOLDER); ?></p>
			</td>
												           
		</tr>
		<tr>
			<td><?php echo gettext("Full image protection:"); ?></td>
			<td style="margin:0">
				<p>
				<?php
				echo "<select id=\"protect_full_image\" name=\"protect_full_image\">\n";
				$protection = getOption('protect_full_image');
				generateListFromArray(array($protection), array(gettext('Unprotected') => 'Unprotected', gettext('Protected view') => 'Protected view', gettext('Download') => 'Download', gettext('No access') => 'No access'), false, true);
				echo "</select>\n";
				?>
				</p>
				<p>
					<label>
						<input type="checkbox" name="hotlink_protection" value="1" <?php echo checked('1', getOption('hotlink_protection')); ?> />
						<?php echo gettext('Disable hotlinking'); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="cache_full_image" value="1" <?php echo checked('1', getOption('cache_full_image')); ?> />
						<?php echo gettext('cache the full image'); ?>
					</label>
				</p>
				<p>
				<input	type="hidden" name="password_enabled" id="password_enabled" value=0 />
				<table class="compact">
					<tr class="passwordextrashow">
						<td style="margin:0; padding:0">
							<a href="javascript:toggle_passwords('',true);">
								<?php echo gettext("password:"); ?>
							</a>
						</td>
						<td style="margin:0; padding:0">
							<?php
							$x = getOption('protected_image_password');
							if (!empty($x)) echo "  **********";
							?>
						</td>
					</tr>
					<tr class="passwordextrashow">
						<td style="margin:0; padding:0">
						</td>
						<td style="margin:0; padding:0">
							<!-- password & repeat -->
							<br />
							<br />
						</td>
					</tr>
					<tr class="passwordextrashow">
						<td style="margin:0; padding:0">
						</td>
						<td style="margin:0; padding:0">
							<!-- hint -->
							<br />
							<br />
						</td>
					</tr>
					
					<tr class="passwordextrahide" style="display:none">
						<td style="margin:0; padding:0">
							<a href="javascript:toggle_passwords('',false);">
								<?php echo gettext("user:"); ?>
							</a>
						</td>
						<td style="margin:0; padding:0"><input type="text" size="<?php echo 30; ?>" name="protected_image_user" value="<?php echo htmlspecialchars(getOption('protected_image_user')); ?>" />		</td>
					</tr>
					<tr class="passwordextrahide" style="display:none">
						<td style="margin:0; padding:0">
							<?php echo gettext("password:"); ?>
						</td>
						<td style="margin:0; padding:0">
							<?php $x = getOption('protected_image_password'); if (!empty($x)) { $x = '          '; } ?>
							<input type="password" size="<?php echo 30; ?>" name="imagepass" value="<?php echo $x; ?>" />
						</td>
					</tr>
					<tr class="passwordextrahide" style="display:none">
						<td style="margin:0; padding:0 text-align:left">
							<?php echo gettext("(repeat)"); ?>
						</td>
						<td style="margin:0; padding:0">
							<input type="password" size="<?php echo 30; ?>" name="imagepass_2" value="<?php echo $x; ?>" />
						</td>
					</tr>
					<tr class="passwordextrahide" style="display:none">
						<td style="margin:0; padding:0"><?php echo gettext("hint:"); ?></td>
						<td style="margin:0; padding:0">
						<?php print_language_string_list(getOption('protected_image_hint'), 'protected_image_hint', false, NULL, '', true) ?>
						</td>
					</tr>
				</table>
			</td>
			<td>
				<p><?php echo gettext("Select the level of protection for full sized images. <em>Download</em> forces a download dialog rather than displaying the image. <em>No&nbsp;access</em> prevents a link to the image from being shown. <em>Protected&nbsp;view</em> forces image processing before the image is displayed, for instance to apply a watermark or to check passwords. <em>Unprotected</em> allows direct display of the image."); ?></p>
				<p><?php echo gettext("Disabling hotlinking prevents linking to the full image from other domains. If enabled, external links are redirect to the image page. If you are having problems with full images being displayed, try disabling this setting. Hotlinking is not prevented if <em>Full&nbsp;image&nbsp;protection</em> is <em>Unprotected</em> or if the image is cached."); ?></p>
				<p><?php echo gettext("If <em>Cache the full image</em> is checked the full image will be loaded to the cache and served from there after the first reference. <em>Full&nbsp;image&nbsp;protection</em> must be set to <em>Protected&nbsp;view</em> for the image to be cached. However, once cached, no protections are applied to the image."); ?></p>
				<p><?php echo gettext("The <em>user</em>, <em>password</em>, and <em>hint</em> apply to the <em>Download</em> and <em>Protected view</em> level of protection. If there is a password set, the viewer must supply this password to access the image."); ?></p>
			</td>
		</tr>
		<tr>
			<td><?php echo gettext("Use lock image"); ?></td>
			<td>
				<input type="checkbox" name="use_lock_image" value="1"
				<?php echo checked('1', getOption('use_lock_image')); ?> />&nbsp;<?php echo gettext("Enabled"); ?>
			</td>
			<td><?php echo gettext("Substitute a <em>lock</em> image for thumbnails of password protected albums when the viewer has not supplied the password. If your theme supplies an <code>images/err-passwordprotected.gif</code> image, it will be shown. Otherwise the zenphoto default lock image is displayed."); ?>
		</tr>
		<tr>
			<td><?php echo gettext("EXIF display"); ?></td>
			<td>
			<ul class="searchchecklist">
			<?php
			$exifstuff = sortMultiArray($_zp_exifvars,2,'asc',true);
			foreach ($exifstuff as $key=>$item) {
				echo '<li><label"><input id="'.$key.'" name="'.$key.'" type="checkbox"';		
				if ($item[3]) {
					echo ' checked="checked" ';
				}
				echo ' value="1"  /> ' . $item[2] . "</label></li>"."\n";
			}
			?>
			</ul>
			</td>
			<td><?php echo gettext("Check those EXIF fields you wish displayed in image EXIF information."); ?>
		</tr>
		<?php
		$sets = array_merge($_zp_UTF8->iconv_sets, $_zp_UTF8->mb_sets);
		ksort($sets);
		if (!empty($sets)) {
			?>
			<tr>
				<td><?php echo gettext("IPTC encoding:"); ?></td>
				<td>
					<select id="IPTC_encoding" name="IPTC_encoding">
						<?php generateListFromArray(array(getOption('IPTC_encoding')), array_flip($sets), false, true) ?>
					</select>
				</td>
				<td><?php echo gettext("The default character encoding of image IPTC metadata."); ?></td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td colspan="3">
			<p class="buttons">
			<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
			<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
			</p>
			</td>
		</tr>
	</table>
	</form>
	</div><!-- end of tab_image div -->
<?php
}
if ($subtab == 'comments' && $_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
	?>
	<div id="tab_comments" class="tabbox">
	<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
	<input 	type="hidden" name="savecommentoptions" value="yes" />
	<table class="bordered">
	<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
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
			$filters = getPluginFiles('*.php','spamfilters');
			generateListFromArray(array($currentValue), array_keys($filters),false,false);
			?>
			</select></td>
			<td><?php echo gettext("The SPAM filter plug-in you wish to use to check comments for SPAM"); ?></td>
		</tr>
		<?php
		/* procss filter based options here */
		if (!(false === ($requirePath = getPlugin('spamfilters/'.getOption('spam_filter').'.php')))) {
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
						<td>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="comment_name_required" id="comment_name_required"
											value=1 <?php checked('1', getOption('comment_name_required')); ?>>
									<?php echo gettext("Name"); ?>
								</label>
							</span>
						</td>
					</tr>
					<tr>
						<td>
							<span style="white-space:nowrap">
								<label>
								<input type="checkbox" name="comment_email_required" id="comment_email_required"
										value=1 <?php checked('1', getOption('comment_email_required')); ?>>
								<?php echo gettext("Email"); ?>
								</label>
							</span>
						</td>
					</tr>
					<tr>
						<td>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="comment_web_required" id="comment_web_required"
											value=1 <?php checked('1', getOption('comment_web_required')); ?>>
									<?php echo gettext("Website"); ?>
								</label>
							</span>
						</td>
						</
					</tr>
					<tr>
						<td>
							<span style="white-space:nowrap">
								<label>
									<input type="checkbox" name="Use_Captcha" id="Use_Captcha"
											value=1 <?php checked('1', getOption('Use_Captcha')); ?>>
									<?php echo gettext("Captcha"); ?>
								</label>
							</span>
						</td>
					</tr>
				</table>
			</td>
			<td><?php echo gettext("Checked fields must be valid in a comment posting."); ?></td>
		</tr>
		<tr>
			<td colspan="3">
			<p class="buttons">
			<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
			<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
			</p>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<!-- end of tab_comments div -->
<?php
}
if ($subtab=='theme' && $_zp_loggedin & (ADMIN_RIGHTS | THEMES_RIGHTS)) {
	?>
	<div id="tab_theme" class="tabbox">
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
	$optiontheme = '';
	if (!empty($_REQUEST['themealbum'])) {
		$alb = urldecode(sanitize_path($_REQUEST['themealbum']));
		$album = new Album($gallery, $alb);
		$albumtitle = $album->getTitle();
		$themename = $album->getAlbumTheme();
	} else if (!empty($_REQUEST['optiontheme'])) {
		$alb = $album = NULL;
		$albumtitle = '';
		$themename = $optiontheme = sanitize($_REQUEST['optiontheme']);
	} else {
		foreach ($themelist as $albumtitle=>$alb) break;
		if (empty($alb)) {
			$themename = $gallery->getCurrentTheme();
			$album = NULL;
		} else {
			$alb = sanitize_path($alb);
			$album = new Album($gallery, $alb);
			$albumtitle = $album->getTitle();
			$themename = $album->getAlbumTheme();
		}
	}
	?>
	<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
		<input type="hidden" name="savethemeoptions" value="yes" />
		<input type="hidden" name="optiontheme" value="<?php echo $optiontheme; ?>" />
		<input type="hidden" name="old_themealbum" value="<?php echo urlencode($alb); ?>" />
		<table class='bordered'>
		
		<?php
		if (count($themelist) == 0) {
			?>
			<th>
			<br />
			<div class="errorbox" id="no_themes">
			<h2><?php echo gettext("There are no themes for which you have rights to administer.");?></h2>
			</div>
			</th>
			
			<?php
		} else {
			/* handle theme options */
			$themes = $gallery->getThemes();
			$theme = $themes[$themename];
			?>
			<tr>
				<th colspan='2'>
					<h2 style='float: left'>
						<?php
						if ($albumtitle) {
							printf(gettext('Options for <code><strong>%1$s</strong></code>: <em>%2$s</em>'), $albumtitle,$theme['name']);
						} else {
							printf(gettext('Options for <em>%s</em>'), $theme['name']);
						}
						?>
					</h2>
				</th>
			<th colspan='1' style='text-align: right'>
			<?php
			if (count($themelist) > 1) {
				echo gettext("Show theme for:");
				echo '<select id="themealbum" name="themealbum" onchange="this.form.submit()">';
				generateListFromArray(array(urlencode($alb)), $themelist, false, true);
				echo '</select>';
			} else {
				echo '&nbsp;';
			}
			echo "</th></tr>\n";
			?>
			<tr>
					<td colspan="3">
					<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					</td>
				</tr>
			<tr class="alt1">
				<td align="left">
					<?php echo gettext('<strong>Standard options</strong>') ?>
				</td>
				<td colspan="2" ><em><?php echo gettext('These image and album presentation options provided by the Zenphoto core for all themes. However, please note that these are <em>recommendations</em> as themes may choose to override them for design reasons'); ?></em></td>
			</tr>
			<tr>
				<td style='width: 175px'><?php echo gettext("Albums per page:"); ?></td>
				<td><input type="text" size="3" name="albums_per_page"
					value="<?php echo getThemeOption('albums_per_page',$album,$themename);?>" /></td>
				<td><?php echo gettext("Recommended number of albums on a page. You might need to adjust this for a better page layout."); ?></td>
			</tr>
			<tr>
				<td><?php echo gettext("Thumbnails per page:"); ?></td>
				<td><input type="text" size="3" name="images_per_page"
					value="<?php echo getThemeOption('images_per_page',$album,$themename);?>" /></td>
				<td><?php echo gettext("Recommended number of thumbnails on a album page. You might need to adjust this for a better page layout."); ?></td>
			</tr>
			<tr>
				<td><?php echo gettext("Thumb size:"); ?></td>
				<td><input type="text" size="3" name="thumb_size"
					value="<?php echo getThemeOption('thumb_size',$album,$themename);?>" /></td>
				<td><?php echo gettext("Default thumbnail size and scale."); ?></td>
			</tr>
			<tr>
				<td><?php echo gettext("Crop thumbnails:"); ?></td>
				<td>
					<input type="checkbox" name="thumb_crop" value="1" <?php echo checked('1', getThemeOption('thumb_crop',$album,$themename)); ?> />
					&nbsp;&nbsp;
					<span style="white-space:nowrap">
						<?php echo gettext('Crop width'); ?>
						<input type="text" size="3" name="thumb_crop_width" id="thumb_crop_width"
								value="<?php echo getThemeOption('thumb_crop_width',$album,$themename);?>" />
					</span>
					<span style="white-space:nowrap">
						<?php echo gettext('Crop height'); ?>
						<input type="text" size="3" name="thumb_crop_height" id="thumb_crop_height"
								value="<?php echo getThemeOption('thumb_crop_height',$album,$themename);?>" />
					</span>
				</td>
				<td>
					<?php echo gettext("If checked the thumbnail cropped to the <em>width</em> and <em>height</em> indicated."); ?>
					<br />
					<?php echo gettext('<strong>Note</strong>: changing crop height or width will invalidate existing crops.'); ?>
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Grayscale conversion:"); ?></td>
				<td>
				<span style="white-space:nowrap">
					<label>
						<?php echo gettext('image') ?>
						<input type="Checkbox" size="3" name="image_gray" id="image_gray"
								value="1" <?php echo checked('1', getThemeOption('image_gray',$album,$themename)); ?>/>
					</label>
				</span>
				<span style="white-space:nowrap">
					<label>
						<?php echo gettext('thumbnail') ?>
						<input type="Checkbox" size="3" name="thumb_gray" id="thumb_gray"
								value="1" <?php echo checked('1', getThemeOption('thumb_gray',$album,$themename)); ?>/>
					</label>
				</span>
				</td>
				<td><?php echo gettext("If checked, images/thumbnails will be created in grayscale."); ?></td>
			</tr>
			<tr>
				<td><?php echo gettext("Image size:"); ?></td>
				<td>
					<?php $side = getOption('image_use_side'); ?>
					<table>
						<tr>
							<td rowspan="2" style="margin:0; padding:0">
								<input type="text" size="3" name="image_size" value="<?php echo getThemeOption('image_size',$album,$themename);?>" />
							</td>
							<td style="margin:0; padding:0">
								<label>
									<input type="radio" id="image_use_side1" name="image_use_side" id="image_use_side"
											value="height" <?php if ($side=='height') echo " CHECKED"?> />
										<?php echo gettext('height') ?>
								</label>
								<label>
									<input type="radio" id="image_use_side2" name="image_use_side" id="image_use_side"
												value="width" <?php if ($side=='width') echo " CHECKED"?> />
									<?php echo gettext('width') ?>
								</label>
							</td>
						</tr>
						<tr>
							<td style="margin:0; padding:0">
								<label>
									<input type="radio" id="image_use_side3" name="image_use_side" id="image_use_side"
											value="shortest" <?php if ($side=='shortest') echo " CHECKED"?> />
									<?php echo gettext('shortest side') ?>
								</label>
								<label>
									<input type="radio" id="image_use_side4" name="image_use_side" id="image_use_side"
											value="longest" <?php if ($side=='longest') echo " CHECKED"?> />
									<?php echo gettext('longest side') ?>
								</label>
							</td>
						</tr>
					</table>
				</td>
				<td>
					<?php echo gettext("Default image display size."); ?>
					<br />
					<?php echo gettext("The image will be sized so that the <em>height</em>, <em>width</em>, <em>shortest side</em>, or the <em>longest side</em> will be equal to <em>image size</em>."); ?>
				</td>
			</tr>
			<?php if (is_null($album)) {?>
			<tr>
				<td><?php echo gettext("Gallery index page link:"); ?></td>
				<td>
					<select id="custom_index_page" name="custom_index_page">
						<option value=""><?php echo gettext('*none'); ?></option>
						<?php
						$curdir = getcwd();
						$root = SERVERPATH.'/'.THEMEFOLDER.'/'.$themename.'/';
						chdir($root);
						$filelist = safe_glob('*.php');
						$list = array();
						foreach($filelist as $file) {
							$list[] = str_replace('.php', '', filesystemToInternal($file));
						}
						$list = array_diff($list, standardScripts());
						generateListFromArray(array(getOption('custom_index_page')), $list, false, false);
						chdir($curdir);
						?>
					</select>
				</td>
				<td><?php echo gettext("If this option is not empty, the Gallery Index URL that would normally link to the theme <code>index.php</code> script will instead link to this script. This frees up the <code>index.php</code> script so that you can create a customized <em>Home</em> page script. This option applies only to the main theme for the <em>Gallery</em>."); ?></td>
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
				<td align="left">
					<?php echo gettext('<strong>Custom theme options</strong>') ?>
				</td>
				<td colspan="2"><em><?php printf(gettext('The following are options specifically implemented by %s.'),$theme['name']); ?></em></td>
			</tr>
				<?php
					customOptions($optionHandler, '', $album, false, $supportedOptions, $themename);
				}
			}
		
			?>
			<tr>
			<td colspan="3">
			<p class="buttons">
			<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
			<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
			</p>
			</td>
			</tr>
			<?php
			}
		?>
			</table>
		</form>
	</div>
	<!-- end of tab_theme div -->
	<?php
}
?>
<?php
if ($subtab == 'plugin' && $_zp_loggedin & ADMIN_RIGHTS) {
	$_zp_plugin_count = 0;
	?>
	<div id="tab_plugin" class="tabbox">
		<form action="?action=saveoptions" method="post" AUTOCOMPLETE=OFF>
			<input type="hidden" name="savepluginoptions" value="yes" />
			<table class="bordered">
				<tr>
						<td colspan="3">
						<p class="buttons">
						<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
						<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
						</p>
						</td>
				</tr>
			<tr>
				<th colspan="3" style="text-align:center">
					<span style="font-weight: normal">
						<a href="javascript:setShow(1);toggleExtraInfo('','plugin',true);"><?php echo gettext('Expand plugin options');?></a>
						|
						<a href="javascript:setShow(0);toggleExtraInfo('','plugin',false);"><?php echo gettext('Collapse all plugin options');?></a>
					</span>
				</th>
			</tr>
			<tr>
			<td style="padding: 0;margin:0" colspan="3">
			<?php
			$showlist = array();
			$plugins = array_keys(getEnabledPlugins());
			natcasesort($plugins);
			foreach ($plugins as $extension) {
				$option_interface = NULL;
				if (array_key_exists($extension, $class_optionInterface)) {
					$option_interface = $class_optionInterface[$extension];
				}
				require_once(getPlugin($extension.'.php'));
				if (!is_null($option_interface)) {
					$showlist[] = '#_show-'.$extension;
					$_zp_plugin_count++;
					?>
					<!-- <?php echo $extension; ?> -->
					<table class="bordered" style="border: 0" id="plugin-<?php echo $extension; ?>">
						<tr>
						<input type="hidden" name="_show-<?php echo $extension;?>" id="_show-<?php echo $extension;?>" value="0" />
						<?php
						if (isset($_GET['_show-'.$extension])) {
							$show_show = 'none';
							$show_hide = 'block';
						} else {
							$show_show = 'block';
							$show_hide = 'none';
						}
						?>
						<th colspan="3" style="text-align:left">
							<span style="display:<?php echo $show_show; ?>;" class="pluginextrashow">
								<a href="javascript:$('#_show-<?php echo $extension;?>').val(1);toggleExtraInfo('<?php echo $extension;?>','plugin',true);"><?php echo $extension; ?></a>
							</span>
							<span style="display:<?php echo $show_hide; ?>;" class="pluginextrahide">
								<a href="javascript:$('#_show-<?php echo $extension;?>').val(0);toggleExtraInfo('<?php echo $extension;?>','plugin',false);"><?php echo $extension; ?></a>
							</span>
						</th>
					</tr>
					<?php
					$supportedOptions = $option_interface->getOptionsSupported();
					if (count($supportedOptions) > 0) {
						customOptions($option_interface, '', NULL, 'plugin', $supportedOptions, NULL, $show_hide);
					}
				?>
				</table>
				
				<?php
				}
			}
			if ($_zp_plugin_count == 0) {
				echo gettext("There are no plugin options to administer.");
			} else {
			?>
					<tr>
						<td colspan="3">
						<p class="buttons">
						<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
						<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
						</p>
						</td>
					</tr>
				</table> <!-- plugin page table -->
			<?php
			}
			?>
		</form>
		<script language="javascript" type="text/javascript">
			function setShow(v) {
				<?php
				foreach ($showlist as $show) {
					?>
					$('<?php echo $show; ?>').val(v);
					<?php
				}
				?>
			}
		</script>
	</div>
	<!-- end of tab_plugin div -->
<?php
}
?>

<!-- end of container -->
<?php
echo '</div>'; // content
printAdminFooter();
echo '</div>'; // main


echo "\n</body>";
echo "\n</html>";
?>



