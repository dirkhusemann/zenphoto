<?php
/**
 * Provides rudimentary user groups
 * 
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Provides rudimentary user groups.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.6';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---user_groups.php.html";

zp_register_filter('admin_tabs', 'user_groups_admin_tabs');
zp_register_filter('admin_alterrights', 'user_groups_admin_alterrights');
zp_register_filter('save_admin_custom_data', 'user_groups_save_admin');
zp_register_filter('edit_admin_custom_data', 'user_groups_edit_admin');

/**
 * Saves admin custom data
 * Called when an admin is saved
 *
 * @param string $discard always empty
 * @param object $userobj admin user object
 * @param string $i prefix for the admin
 * @return string
 */
function user_groups_save_admin($discard, $userobj, $i) {
	$administrators = getAdministrators();
	if (isset($_POST[$i.'group'])) {
		$groupname = sanitize($_POST[$i.'group']);
		if (empty($groupname)) {
			$oldgroup = $userobj->getGroup();
			if (!empty($oldgroup)) {
				$group = new Administrator($oldgroup, 0);
				$userobj->setRights($group->getRights());
			}
		} else {
			$group = new Administrator($groupname, 0);
			$userobj->setRights($group->getRights());
			$userobj->setAlbums(populateManagedAlbumList($group->get('id')));
			if ($group->getName() == 'template') $groupname = '';
		}
		$userobj->setGroup($groupname);
	}
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
function user_groups_edit_admin($html, $userobj, $i, $background, $current) {
	global $_admin_rights, $gallery;
	$group = $userobj->getGroup();
	$admins = getAdministrators();
	$ordered = array();
	$groups = array();
	$hisgroup = NULL;
	$adminordered = array();
	foreach ($admins as $key=>$admin) {
		$ordered[$key] = $admin['user'];
		if ($group == $admin['user']) $hisgroup = $admin;
	}
	asort($ordered);
	foreach ($ordered as $key=>$user) {
		$adminordered[] = $admins[$key];
		if (!$admins[$key]['valid']) {
			$groups[] = $admins[$key];
		}
	}
	if (empty($groups)) return ''; // no groups setup yet
	if (zp_loggedin(ADMIN_RIGHTS)) {
		$albumlist = array();
		foreach ($gallery->getAlbums() as $folder) {
			if (hasDyanmicAlbumSuffix($folder)) {
				$name = substr($folder, 0, -4); // Strip the .'.alb' suffix
			} else {
				$name = $folder;
			}
			$albumlist[$name] = $folder;
		}
		$grouppart =	'
									<script type="text/javascript">
										function groupchange'.$i.'(obj) {
											var disable = obj.value != \'\';';
			foreach ($albumlist as $albumid) {
				$grouppart .= '
											$(\'#managed_albums_'.$i.'_'.postIndexEncode($albumid).'\').attr(\'disabled\',disable);';
			}
			$grouppart .= '
											if (disable) {';
			foreach ($albumlist as $albumid) {
				$grouppart .= '
												$(\'#managed_albums_'.$i.'_'.postIndexEncode($albumid).'\').attr(\'checked\',\'\');';
			}
			$grouppart .= '
												switch (obj.value) {';
			foreach ($groups as $user) {
				$cv = populateManagedAlbumList($user['id']);
				$grouppart .= '
													case \''.$user['user'].'\':
														target = '.$user['rights'].';';
				foreach ($albumlist as $albumid) {
					if (in_array($albumid,$cv)) {
						$grouppart .= '
														$(\'#managed_albums_'.$i.'_'.postIndexEncode($albumid).'\').attr(\'checked\',\'checked\');';
					} else {
						$grouppart .= '
														$(\'#managed_albums_'.$i.'_'.postIndexEncode($albumid).'\').attr(\'checked\',\'\');';
					}
				}
				$grouppart .= '
														break;';
		}
		$grouppart .= '
												}';
		foreach ($_admin_rights as $rightselement=>$rightsvalue) {
				$grouppart .= '
												if ($(\'#'.$i.'-'.$rightselement.'\').val()&target) {
													$(\'#'.$i.'-'.$rightselement.'\').attr(\'checked\',\'checked\');
												} else {
													$(\'#'.$i.'-'.$rightselement.'\').attr(\'checked\',\'\');
												}';		
		}
		$grouppart .= '
											}';
		foreach ($_admin_rights as $rightselement=>$rightsvalue) {
			$grouppart .= '	
											$(\'#'.$i.'-'.$rightselement.'\').attr(\'disabled\',disable);';		
		}
		
		$grouppart .= '
											$(\'#hint'.$i.'\').html(obj.options[obj.selectedIndex].title);
										}';
		if (is_array($hisgroup)) {
			$grouppart .= '
										window.onload = function() {';
			$cv = populateManagedAlbumList($hisgroup['id']);
			foreach ($albumlist as $albumid) {
				if (in_array($albumid,$cv)) {
					$grouppart .= '
											$(\'#managed_albums_'.$i.'_'.postIndexEncode($albumid).'\').attr(\'checked\',\'checked\');';
				} else {
					$grouppart .= '
											$(\'#managed_albums_'.$i.'_'.postIndexEncode($albumid).'\').attr(\'checked\',\'\');';
				}
			}
			$grouppart .= '
										}';
		}
		
		$grouppart .= '
									</script>';
		
		$grouppart .= '<select name="'.$i.'group" onchange="javascript: groupchange'.$i.'(this);"'.'>'."\n";
		$grouppart .= '<option value="" title="'.gettext('no group affiliation').'"></option>'."\n";
		$selected_hint = gettext('no group affiliation');
		foreach ($groups as $user) {
			if ($user['name']=='template') {
				$type = '<strong>'.gettext('Template:').'</strong> ';
			} else {
				$type = '';
			}
			$hint = $type.'<em>'.htmlentities($user['custom_data'],ENT_COMPAT,getOption("charset")).'</em>';
			if ($group == $user['user']) {
				$selected = ' SELECTED="SELECTED"';
				$selected_hint = $hint;
				} else {
				$selected = '';
			}
			$grouppart .= '<option'.$selected.' value="'.$user['user'].'" title="'.$hint.'">'.$user['user'].'</option>'."\n";
		}
		$grouppart .= '</select>'."\n";
		$grouppart .= '<span class="hint'.$i.'" id="hint'.$i.'" style="width:15em;">'.$selected_hint."</span>\n";
	} else {
		$grouppart = $group.'<input type="hidden" name="'.$i.'group" value="'.$group.'" />'."\n";
	}
	$result = 
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Group:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" width="345">'.
				$grouppart.
			'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">'.gettext('User group membership.<br /><strong>NOTE:</strong> When a group is assigned <em>rights</em> and <em>managed albums</em> are determined by the group!').'</td>
		</tr>'."\n";
	return $html.$result;
}

function user_groups_admin_tabs($tabs, $current) {
	$subtabs = array(	gettext('users')=>'admin-options.php?page=users&tab=users',
										gettext('assignments')=>PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&amp;tab=assignments',
										gettext('groups')=>PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&amp;tab=groups');
	if ((zp_loggedin(ADMIN_RIGHTS))) {
		$tabs['users'] = array(	'text'=>gettext("admin"),
														'link'=>WEBPATH."/".ZENFOLDER.'/admin-options.php?page=users&tab=users',
														'subtabs'=>$subtabs,
														'default'=>'users');
	}
	return $tabs;
}

function user_groups_admin_alterrights($alterrights, $userobj) {
	$group = $userobj->getGroup();
	if (empty($group)) return $alterrights;
	return ' DISABLED';
}

?>