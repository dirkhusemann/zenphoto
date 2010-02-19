<?php
/**
 * Provides a unified comment handling facility
 * 
 * Place a call on the function printCommentForm() in your script where you
 * wish the comment items to appear.
 * 
 * Normally the plugin uses the form plugins/comment_form/comment_form.php.
 * However, you may override this form by placing a script of the same name in your theme folder.
 * This will allow you to customize the appearance of the comments on your site.
 * 
 * There are several options to tune what the plugin will do. 
 * 
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Provides a unified comment handling facility.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---comment_form.php.html";
$option_interface = new comment_form();

zp_register_filter('comment_post', 'comment_form_comment_post');
zp_register_filter('save_comment_custom_data', 'comment_form_save_comment');
zp_register_filter('edit_comment_custom_data', 'comment_form_edit_comment');
zp_register_filter('save_admin_custom_data', 'comment_form_save_admin');
zp_register_filter('edit_admin_custom_data', 'comment_form_edit_admin', 1);

class comment_form {
	/**
	 * class instantiation function
	 *
	 * @return admin_login
	 */
	function comment_form() {
		setOptionDefault('comment_form_addresses', 0);
		setOptionDefault('comment_form_require_addresses', 0);
		setOptionDefault('comment_form_members_only', 0);
		setOptionDefault('comment_form_albums', 1);
		setOptionDefault('comment_form_images', 1);
		setOptionDefault('comment_form_articles', 1);
		setOptionDefault('comment_form_pages', 1);
		setOptionDefault('comment_form_rss', 1);
		setOptionDefault('comment_form_private', 1);
		setOptionDefault('comment_form_anon', 1);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$checkboxes = array(gettext('Albums') => 'comment_form_albums', gettext('Images') => 'comment_form_images');	
		if (getOption('zp_plugin_zenpage')) {					
			$checkboxes = array_merge($checkboxes, array(gettext('Pages') => 'comment_form_pages', gettext('News') => 'comment_form_articles'));
		}
		
		return array(	gettext('Address fields') => array('key' => 'comment_form_addresses', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array(gettext('Omit')=>0, gettext('Show')=>1, gettext('Require')=>'required'),
										'desc' => gettext('If <em>Address fields</em> are shown or required, the form will include positions for address information. If required, the poster must supply data in each address field.')),
									gettext('Allow comments on') => array('key' => 'comment_form_allowed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
										'checkboxes' => $checkboxes,
									'desc' => gettext('Comment forms will be presented on the checked pages.')),
									gettext('Only members can comment') => array('key' => 'comment_form_members_only', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, only logged in users will be allowed to post comments.')),
									gettext('Allow private postings') => array('key' => 'comment_form_private', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, posters may mark their comments as private (not for publishing).')),
									gettext('Allow anonomous posting') => array('key' => 'comment_form_anon', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, posters may exclude their personal information from the published post.')),
									gettext('Include RSS link') => array('key' => 'comment_form_rss', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, an RSS link will be included at the bottom of the comment section.'))
									);
	}

	function handleOption($option, $currentValue) {
	}

}

/**
 * Returns a processed comment custom data item
 * Called when a comment edit is saved
 *
 * @param string $discard always empty
 * @return string
 */
function comment_form_save_comment($discard) {
	return serialize(getUserInfo(0));
}

/**
 * Returns table row(s) for edit of a comment's custom data
 *
 * @param string $discard always empty
 * @return string
 */
function comment_form_edit_comment($discard, $raw) {
	if (!preg_match('/^a:[0-9]+:{/', $raw)) {
		$address = array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>'', 'website'=>'');
	} else {
		$address = unserialize($raw);
	}
	return
			 '<tr> 
					<td>'.
						gettext('street:').
				 '</td>
			 		<td>
						<input type="text" name="0-comment_form_street" id="comment_form_street" class="inputbox" size="40" value="'.$address['street'].'">
					</td>
				</tr>
				<tr>
					<td>'.
						gettext('city:').
			 		'</td>
					<td>
						<input type="text" name="0-comment_form_city" id="comment_form_city" class="inputbox" size="40" value="'.$address['city'].'">
					</td>
			 	</tr>
			 	<tr>
					<td>'.
						gettext('state:').
				 '</td>
			 		<td>
						<input type="text" name="0-comment_form_state" id="comment_form_state" class="inputbox" size="40" value="'.$address['state'].'">
					</td>
				</tr>
				<tr>
					<td>'.
						gettext('country:').
				 '</td>
					<td>
						<input type="text" name="0-comment_form_country" id="comment_form_country" class="inputbox" size="40" value="'.$address['country'].'">
					</td>
				</tr>
				<tr>
					<td>'.
						gettext('postal code:').
					'</td>
					<td>
						<input type="text" name="0-comment_form_postal" id="comment_form_postal" class="inputbox" size="40" value="'.$address['postal'].'">
					</td>
				</tr>'."\n";
}

/**
 * Saves admin custom data
 * Called when an admin is saved
 *
 * @param string $discard always empty
 * @param object $userobj admin user object
 * @param string $i prefix for the admin
 * @return string
 */
function comment_form_save_admin($discard, $userobj, $i) {
	$userobj->setCustomData(serialize(getUserInfo($i)));
}

/**
 * Processes the post of an address
 *
 * @param int $i sequence number of the comment
 * @return array
 */
function getUserInfo($i) {
	$result = array();
	if (isset($_POST[$i.'-comment_form_website'])) $result['website'] = sanitize($_POST[$i.'-comment_form_website'], 1);
	if (isset($_POST[$i.'-comment_form_street'])) $result['street'] = sanitize($_POST[$i.'-comment_form_street'], 1);
	if (isset($_POST[$i.'-comment_form_city'])) $result['city'] = sanitize($_POST[$i.'-comment_form_city'], 1);
	if (isset($_POST[$i.'-comment_form_state'])) $result['state'] = sanitize($_POST[$i.'-comment_form_state'], 1);
	if (isset($_POST[$i.'-comment_form_country'])) $result['country'] = sanitize($_POST[$i.'-comment_form_country'], 1);
	if (isset($_POST[$i.'-comment_form_postal'])) $result['postal'] = sanitize($_POST[$i.'-comment_form_postal'], 1);
	return $result;
}

/**
 * Processes the address parts of a comment posst
 *
 * @param object $commentobj the comment object
 * @param object $receiver the object receiving the comment
 * @return object
 */
function comment_form_comment_post($commentobj, $receiver) {
	if ($addresses = getOption('comment_form_addresses')) {
		$userinfo = getUserInfo(0);
		if ($addresses == 'required') {
			// Note: this error will be incremented by functions-controller
			if (!isset($userinfo['street']) || empty($userinfo['street'])) {
				$commentobj->setInModeration(-11);
			}
			if (!isset($userinfo['city']) || empty($userinfo['city'])) {
				$commentobj->setInModeration(-12);
			}
			if (!isset($userinfo['state']) || empty($userinfo['state'])) {
				$commentobj->setInModeration(-13);
			}
			if (!isset($userinfo['country']) || empty($userinfo['country'])) {
				$commentobj->setInModeration(-14);
			}
			if (!isset($userinfo['postal']) || empty($userinfo['postal'])) {
				$commentobj->setInModeration(-15);
			}
		}
		$commentobj->setCustomData(serialize($userinfo));
	}
	return $commentobj;
}

/**
 * Returns table row(s) for edit of an admin user's custom data
 *
 * @param string $html always empty
 * @param $userobj Admin user object
 * @param string $i prefix for the admin
 * @param string $background background color for the admin row
 * @param bool $current true if this admin row is the logged in admin
 * @return string
 */
function comment_form_edit_admin($html, $userobj, $i, $background, $current) {
	$raw = $userobj->getCustomData();
	$needs = array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>'', 'website'=>'');
	if (!preg_match('/^a:[0-9]+:{/', $raw)) {
		$address = $needs;
	} else {
		$address = unserialize($userobj->getCustomData());
		foreach ($needs as $needed=>$value) {
			if (!isset($address[$needed])) {
				$address[$needed] = '';
			}
		}
	}
		
	return $html.
	 '<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Website:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_website" value="'.$address['website'].'" /></td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" ></td>
		</tr>'.
	 '<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Street:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_street" value="'.$address['street'].'" /></td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" rowspan="5">'.gettext('Address information').'</td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("City:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_city" value="'.$address['city'].'" /></td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("State:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_state" value="'.$address['state'].'" /></td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Country:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_country" value="'.$address['country'].'" /></td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Postal code:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_postal" value="'.$address['postal'].'" /></td>
		</tr>';
}

/**
 * returns an error message if a comment possting was not accepted
 *
 */
function getCommentErrors() {
	global $_zp_comment_error;
	if (isset($_zp_comment_error)) {
		switch ($_zp_comment_error) {
			case   0: return false;
			case -10: return gettext('You must supply the street field'); 
			case -11: return gettext('You must supply the city field'); 
			case -12: return gettext('You must supply the state field'); 
			case -13: return gettext('You must supply the country field');
			case -14: return gettext('You must supply the postal code field'); 
			case  -1: return gettext("You must supply an e-mail address.");
			case  -2: return gettext("You must enter your name.");
			case  -3: return gettext("You must supply an WEB page URL.");
			case  -4: return gettext("Captcha verification failed.");
			case  -5: return gettext("You must enter something in the comment text.");
			case   2: return sprintf(gettext('Your comment has been marked for moderation by the <em>%s</em> SPAM filter.'),getOption('spam_filter'));
			case	 3: return sprintf(gettext('Your comment was rejected by the <em>%s</em> SPAM filter.'),getOption('spam_filter'));
			default: return sprintf(gettext('Comment error "%d" not defined.'), $_zp_comment_error);
		}
	}
	return false; 
}

/**
 * Tool to put an out error message if a comment possting was not accepted
 *
 * @param string $class optional division class for the message
 */
function printCommentErrors($class = 'error') {
	$s = getCommentErrors();
	if ($s) {	
		?>
		<div class="<?php echo $class ?>">
		<?php echo $s; ?>
		</div>
		<?php
	}
}

/**
 * prints a form for posting comments
 * @param bool $showcomments defaults to true for showing list of comments
 *
 */
function printCommentForm($showcomments=true, $addcommenttext=NULL) {
	global $_zp_gallery_page, $_zp_themeroot,	$_zp_current_admin, $_zp_current_comment;
	if (is_null($addcommenttext)) $addcommenttext = gettext('Add a comment:');
	switch ($_zp_gallery_page) {
		case 'album.php':
			if (!getOption('comment_form_albums')) return;
			$comments_open = OpenedForComments(ALBUM);
			$formname = '/comment_form.php';
			break;
		case 'image.php':
			if (!getOption('comment_form_images')) return;
			$comments_open = OpenedForComments(IMAGE);
			$formname = '/comment_form.php';
			break;
		case ZENPAGE_PAGES.'.php':
			if (!getOption('comment_form_pages')) return;
			$comments_open = zenpageOpenedForComments();
			$formname = '/comment_form.php';
			break;
		case ZENPAGE_NEWS.'.php':
			if (!getOption('comment_form_articles')) return;
			$comments_open = zenpageOpenedForComments();
			$formname = '/comment_form.php';
			break;
		default:
			return;
			break;
	}
	$arraytest = '/^a:[0-9]+:{/'; // this screws up Eclipse's brace count!!!
	?>
<!-- printCommentForm -->
	<!-- Wrap Comments -->
	<div id="commentcontent">
		<?php
		if ($showcomments) {
			$num = getCommentCount(); 
			switch ($num) {
				case 0:
					echo '<h3>'.gettext('No Comments').'</h3><br />';
					break;
				default:
					echo '<h3>'.sprintf(ngettext('%u Comment','%u Comments',$num), $num).'</h3>';
			}
			?>
			<div id="comments">
				<?php
				while (next_comment()) {
					?>
					<div class="comment"><a name="<?php echo $_zp_current_comment['id']; ?>"></a>
						<div class="commentinfo">
							<h4><?php printCommentAuthorLink(); ?>: on <?php echo getCommentDateTime(); printEditCommentLink('Edit', ', ', ''); ?></h4>
						</div>
						<div class="commenttext">
							<?php echo getCommentBody();?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>

		<!-- Comment Box -->
		<?php
		if ($comments_open) {
			$stored = array_merge(getCommentStored(),array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>''));
			$raw = $stored['custom'];
			if (preg_match($arraytest, $raw)) {
				$custom = unserialize($raw);
				foreach ($custom as $key=>$value) {
					if (!empty($value)) $stored[$key] = $value;
				}
			}
			$disabled = array();
			foreach ($stored as $key=>$value) {
				$disabled[$key] = false;
			}

			if (zp_loggedin()) {
				$raw = $_zp_current_admin['custom_data'];
				if (preg_match($arraytest, $raw)) {
					$address = unserialize($raw);
					foreach ($address as $key=>$value) {
						if (!empty($value)) {
							$disabled[$key] = true;
							$stored[$key] = $value;
						}
					}
				}
			}

			if (zp_loggedin()) {
				if (!empty($_zp_current_admin['name'])) {
					$stored['name'] = $_zp_current_admin['name'];
					$disabled['name'] = ' disabled="disabled"';
				}
				if (!empty($_zp_current_admin['email'])) {
					$stored['email'] = $_zp_current_admin['email'];
					$disabled['email'] = ' disabled="disabled"';
				}
				if (!empty($address['website'])) {
					$stored['website'] = $address['website'];
					$disabled['website'] = ' disabled="disabled"';
				}
			}
			
			$theme = getCurrentTheme();
			$form = SERVERPATH.'/'.THEMEFOLDER.'/'.internalToFilesystem($theme).$formname;
			if (file_exists($form)) {
				$form = SERVERPATH.'/'.THEMEFOLDER.'/'.$theme.$formname;
			} else {
				$form = SERVERPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/comment_form'.$formname;
			}
			if (getOption('comment_form_members_only') && !zp_loggedin(ADMIN_RIGHTS | POST_COMMENT_RIGHTS)) {
				echo gettext('Only registered users may post comments.');
			} else {
				if (!empty($addcommenttext)) {
					?>
					<h3><?php echo $addcommenttext; ?></h3>
					<?php
				}
				?>
				<div id="commententry">
					<?php
					require_once($form);
					?>
				</div>
				<?php
			}
		} else {
			?>
			<div id="commententry">
				<h3><?php echo gettext('Closed for comments.');?></h3>
			</div>
			<?php
		}
	?>
</div>
<?php 
if (getOption('comment_form_rss')) {
	switch($_zp_gallery_page) {
		case "image.php":
			printRSSLink("Comments-image","",gettext("Subscribe to comments"),"");
			break;
		case "album.php":
			printRSSLink("Comments-album","",gettext("Subscribe to comments"),"");
			break;
		case ZENPAGE_NEWS.".php":
			printZenpageRSSLink("Comments-news", "", "", gettext("Subscribe to comments"), "");
			break;
		case ZENPAGE_PAGES.".php":
			printZenpageRSSLink("Comments-page", "", "", gettext("Subscribe to comments"), "");
			break;
	}
}
?>
<!-- end printCommentForm -->
<?php
}
?>