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
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---comment_form.php.html";
$option_interface = new comment_form();

register_filter('comment_post', 'comment_form_comment_post', 2);
register_filter('save_comment_custom_data', 'comment_form_save_comment');
register_filter('edit_comment_custom_data', 'comment_form_edit_comment', 2);
register_filter('save_comment_form_data', 'comment_form_save_admin', 3);
register_filter('edit_comment_form_data', 'comment_form_edit_admin', 5);

class comment_form {
	/**
	 * class instantiation function
	 *
	 * @return admin_login
	 */
	function comment_form() {
		setOptionDefault('comment_form_addresses', 0);
		setOptionDefault('comment_form_members_only', 0);
		$default = getOption('Allow_comments');
		if (is_null($default)) $default = 1;
		setOptionDefault('comment_form_albums', $default);
		setOptionDefault('comment_form_images', $default);
		$default = getOption('zenpage_comments_allowed');
		if (is_null($default)) $default = 1;
		setOptionDefault('comment_form_articles', $default);
		setOptionDefault('comment_form_pages', $default);
		setOptionDefault('comment_form_rss', 1);
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
		
		return array(	gettext('Show address form') => array('key' => 'comment_form_addresses', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, the form will include positions for address information.')),
									gettext('Allow comments on') => array('key' => 'comment_form_allowed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
										'checkboxes' => $checkboxes,
									'desc' => gettext('Comment forms will be presented on the checked pages.')),
									gettext('Only members can comment') => array('key' => 'comment_form_members_only', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, only logged in users will be allowed to post comments.')),
									gettext('Include RSS link') => array('key' => 'comment_form_rss', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, an RSS link will be included at the bottom of the comment section.'))
									);
	}

	/**
	 * Custom opton handler--creates the clear ratings button
	 *
	 * @param string $option
	 * @param string $currentValue
	 */
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
	return serialize(getStreetInfo());
}

/**
 * Returns table row(s) for edit of a comment's custom data
 *
 * @param string $discard always empty
 * @return string
 */
function comment_form_edit_comment($discard, $raw) {
	if (!preg_match('/^a:[0-9]+:{/', $raw)) {
		$address = array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>'');
	} else {
		$address = unserialize($raw);
	}
	return
			 '<tr> 
					<td>'.
						gettext('street:').
				 '</td>
			 		<td>
						<input type="text" name="0-comment_form_street" id="comment_form_street" class="inputbox" size="22" value="'.$address['street'].'">
					</td>
				</tr>
				<tr>
					<td>'.
						gettext('city:').
			 		'</td>
					<td>
						<input type="text" name="0-comment_form_city" id="comment_form_city" class="inputbox" size="22" value="'.$address['city'].'">
					</td>
			 	</tr>
			 	<tr>
					<td>'.
						gettext('state:').
				 '</td>
			 		<td>
						<input type="text" name="0-comment_form_state" id="comment_form_state" class="inputbox" size="22" value="'.$address['state'].'">
					</td>
				</tr>
				<tr>
					<td>'.
						gettext('country:').
				 '</td>
					<td>
						<input type="text" name="0-comment_form_country" id="comment_form_country" class="inputbox" size="22" value="'.$address['country'].'">
					</td>
				</tr>
				<tr>
					<td>'.
						gettext('postal code:').
					'</td>
					<td>
						<input type="text" name="0-comment_form_postal" id="comment_form_postal" class="inputbox" size="22" value="'.$address['postal'].'">
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
	$userobj->setCustomData(serialize(getStreetInfo($i)));
}

/**
 * Processes the post of an address
 *
 * @param int $i sequence number of the comment
 * @return array
 */
function getStreetInfo($i) {
	return array(	'street'=>sanitize($_POST[$i.'-comment_form_street'], 1),
								'city'=>sanitize($_POST[$i.'-comment_form_city'], 1),
								'state'=>sanitize($_POST[$i.'-comment_form_state'], 1),
								'country'=>sanitize($_POST[$i.'-comment_form_country'], 1),
								'postal'=>sanitize($_POST[$i.'-comment_form_postal'], 1)
								);
}

function comment_form_comment_post($commentobj, $receiver) {
	if (getOption('comment_form_addresses')) $commentobj->setCustomData(serialize(getStreetInfo(0)));
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
	if (!preg_match('/^a:[0-9]+:{/', $raw)) {
		$address = array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>'');
	} else {
		$address = unserialize($raw);
	}
	
	return $html.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Street:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_street" value="'.$address['street'].'"></td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" rowspan="5">'.gettext('Address information.').'</td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("City:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_city" value="'.$address['city'].'"></td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("State:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_state" value="'.$address['state'].'"></td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Country:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_country" value="'.$address['country'].'"></td>
		</tr>'.
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Postal code:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top"><input type="text" name="'.$i.'-comment_form_postal" value="'.$address['postal'].'"></td>
		</tr>';
}

/**
 * prints a form for posting comments
 *
 */
function printCommentForm() {
	global $_zp_gallery_page, $_zp_themeroot;
	switch ($_zp_gallery_page) {
		case 'album.php':
			if (!getOption('comment_form_albums')) return;
			$comments_open = OpenedForComments(ALBUM);
			$formname = '/comment_form.php';
			break;
		case 'image.php':
			if (!getOption('comment_form_images')) return;
			$comments_open = OpenedForComments('IMAGE');
			$formname = '/comment_form.php';
			break;
		case 'pages.php':
			if (!getOption('comment_form_pages')) return;
			$comments_open = zenpageOpenedForComments();
			$formname = '/comment_form.php';
			break;
		case 'news.php':
			if (!getOption('comment_form_articles')) return;
			$comments_open = zenpageOpenedForComments();
			$formname = '/comment_form.php';
			break;
		default:
			return;
			break;
	}
	?>
<!-- printCommentForm -->
	<!-- Headings -->
	<div id="bottomheadings">
		<div class="bottomfull">
			<?php 
			$num = getCommentCount(); 
			switch ($num) {
				case 0:
					echo gettext('<h3>No Comments</h3>');
					break;
				case 1:
					echo gettext('<h3>1 Comment</h3>');
					break;
				default:
					printf(gettext('<h3>%u Comments</h3>'), $num);
			}
			?>
		</div>
	</div>

	<!-- Wrap Comments -->
	<div id="main3">
		<div id="comments">
			<?php while (next_comment()):  ?>
				<div class="comment">
					<div class="commentinfo">
						<h4><?php printCommentAuthorLink(); ?>: on <?php echo getCommentDateTime(); printEditCommentLink('Edit', ', ', ''); ?></h4>
					</div>
					<div class="commenttext">
						<?php echo getCommentBody();?>
					</div>
				</div>
			<?php endwhile; ?>
		</div>

		<!-- Comment Box -->
		<?php
		if ($comments_open) {
			$stored = getCommentStored();
			$disabled = array();
			foreach ($stored as $key=>$value) {
				$disabled[$key] = '';
			}
			if ($comments_open) {
				$address = array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>'');
				$disabled = array_merge($disabled, $address);
				if (zp_loggedin()) {
					global $_zp_current_admin;
					$raw = $_zp_current_admin['custom_data'];
					if (preg_match('/^a:[0-9]+:{/', $raw)) {
						$address = unserialize($raw);
						foreach ($address as $key=>$value) {
							if (!empty($value)) $disabled[$key] = ' DISABLED';
						}
					}
				} else {
					$address = array('street'=>'', 'city'=>'', 'state'=>'', 'country'=>'', 'postal'=>'');
				}
			}
			if (zp_loggedin()) {
				if (!empty($_zp_current_admin['name'])) {
					$stored['name'] = $_zp_current_admin['name'];
					$disabled['name'] = ' DISABLED';
				}
				if (!empty($_zp_current_admin['email'])) {
					$stored['email'] = $_zp_current_admin['email'];
					$disabled['email'] = ' DISABLED';
				}
			}
				
			$theme = getCurrentTheme();
			$form = SERVERPATH.'/'.THEMEFOLDER.'/'.internalToFilesystem($theme).$formname;
			if (file_exists($form)) {
				$form = SERVERPATH.'/'.THEMEFOLDER.'/'.$theme.$formname;
			} else {
				$form = SERVERPATH.'/'.ZENFOLDER.PLUGIN_FOLDER.'comment_form'.$formname;
			}
			if (getOption('comment_form_members_only') && !zp_loggedin(ADMIN_RIGHTS | POST_COMMENT_RIGHTS)) {
				echo gettext('Only registered users may post comments.');
			} else {
				require_once($form);
			}
		} else {
		?>
			<div id="commentbox">
				<h3><?php echo gettext('Closed for comments.');?></h3>
			</div>
		<?php
		}
		?>
	</div>
<?php 
if (getOption('comment_form_rss')) printRSSLink("Comments-image","",gettext("Subscribe to comments"),"");
?>
<!-- end printCommentForm -->
<?php
}
?>