<?php
/**
 * Provides rudimentary user groups
 * 
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Provides rudimentary user groups.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.3.1'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---user_groups.php.html";

zp_register_filter('admin_tabs', 'user_groups_admin_tabs');
zp_register_filter('admin_alterrights', 'user_groups_admin_alterrights');
zp_register_filter('save_admin_custom_data', 'user_groups_save_admin');
zp_register_filter('edit_admin_custom_data', 'user_groups_edit_admin');

/**
 * Saves admin custom data
 * Called when an admin is saved
 *
 * @param string $updated true if there has been an update to the user
 * @param object $userobj admin user object
 * @param string $i prefix for the admin
 * @return string
 */
function user_groups_save_admin($updated, $userobj, $i) {
	global $_zp_authority;
	$administrators = $_zp_authority->getAdministrators();
	if (isset($_POST[$i.'group'])) {
		$groupname = sanitize($_POST[$i.'group']);
		$oldgroup = $userobj->getGroup();
		if (empty($groupname)) {
			if (!empty($oldgroup)) {
				$updated = $groupname != $oldgroup;
				$group = $_zp_authority->newAdministrator($oldgroup, 0);
				$userobj->setRights($group->getRights());
				$userobj->setObjects(populateManagedObjectsList(NULL,$group->getID()));
			}
		} else {
			$group = $_zp_authority->newAdministrator($groupname, 0);
			$userobj->setRights($group->getRights());
			$userobj->setObjects(populateManagedObjectsList(NULL,$group->getID()));
			if ($group->getName() == 'template') $groupname = '';
			$updated = true;
		}
		$userobj->setGroup($groupname);
	}
	return $updated;
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
	global $gallery, $_zp_authority;
	$group = $userobj->getGroup();
	$admins = $_zp_authority->getAdministrators();
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
		$allo = array();
		foreach ($gallery->getAlbums() as $folder) {
			if (hasDynamicAlbumSuffix($folder)) {
				$name = substr($folder, 0, -4); // Strip the .'.alb' suffix
			} else {
				$name = $folder;
			}
			$albumlist[$name] = $folder;
			$allo[] = "'#managed_albums_".$i.'_'.postIndexEncode($folder)."'";
		}
		$rights = array();
		foreach ($_zp_authority->getRights() as $rightselement=>$right) {
			if ($right['display']) {
				$rights[] = "'#".$rightselement.'-'.$i."'";
			}
		}
		$grouppart =	'
			<script type="text/javascript">
				// <!-- <![CDATA[
				function groupchange'.$i.'(obj) {
					var disable = obj.value != \'\';
					var albdisable = false;
					var checkedalbums = [];
					var checked = 0;
					var uncheckedalbums = [];
					var unchecked = 0;
					var allalbums = ['.implode(',', $allo).'];
					var allc = '.count($allo).';
					var rights = ['.implode(',',$rights).'];
					var rightsc = '.count($rights).';
					for (i=0;i<rightsc;i++) {
						$(rights[i]).attr(\'disabled\',disable);
					}
					for (i=0;i<allc;i++) {
						$(allalbums[i]).attr(\'disabled\',disable);
					}
					$(\'#hint'.$i.'\').html(obj.options[obj.selectedIndex].title);
					if (disable) {
						switch (obj.value) {';
		foreach ($groups as $user) {
			$cv = populateManagedObjectsList('album',$user['id']);
			$xv = array_diff($albumlist, $cv);
			$cvo = array();
			foreach ($cv as $albumid) {
				$cvo[] = "'#managed_albums_".$i.'_'.postIndexEncode($albumid)."'";
			}
			$xvo = array();
			foreach ($xv as $albumid) {
				$xvo[] = "'#managed_albums_".$i.'_'.postIndexEncode($albumid)."'";
			}
			if ($user['name']=='template') {
				$albdisable = 'false';
			} else {
				$albdisable = 'true';
			}
			$grouppart .= '
							case \''.$user['user'].'\':
								target = '.$user['rights'].';
								checkedalbums = ['.implode(',',$cvo).'];
								checked = '.count($cvo).';
								uncheckedalbums = ['.implode(',',$xvo).'];
								unchecked = '.count($xvo).';
								break;';
		}
		$grouppart .= '
							}
						for (i=0;i<checked;i++) {
							$(checkedalbums[i]).attr(\'checked\',\'checked\');
						}
						for (i=0;i<unchecked;i++) {
							$(uncheckedalbums[i]).attr(\'checked\',\'\');
						}
						for (i=0;i<rightsc;i++) {
							if ($(rights[i]).val()&target) {
								$(rights[i]).attr(\'checked\',\'checked\');
							} else {
								$(rights[i]).attr(\'checked\',\'\');
							}		
						}
					}
				}';
		if (is_array($hisgroup)) {
			$grouppart .= '
				window.onload = function() {';
			$cv = populateManagedObjectsList('album',$hisgroup['id']);
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
				//]]> -->
			</script>';
		$grouppart .= '<select name="'.$i.'group" onchange="javascript:groupchange'.$i.'(this);"'.'>'."\n";
		$grouppart .= '<option value="" title="'.gettext('*no group affiliation').'">'.gettext('*no group selected').'</option>'."\n";
		$selected_hint = gettext('no group affiliation');
		foreach ($groups as $user) {
			if ($user['name']=='template') {
				$type = '<strong>'.gettext('Template:').'</strong> ';
			} else {
				$type = '';
			}
			$hint = $type.'<em>'.htmlentities($user['custom_data'],ENT_QUOTES,getOption("charset")).'</em>';
			if ($group == $user['user']) {
				$selected = ' selected="selected"';
				$selected_hint = $hint;
				} else {
				$selected = '';
			}
			$grouppart .= '<option'.$selected.' value="'.$user['user'].'" title="'.sanitize($hint,3).'">'.$user['user'].'</option>'."\n";
		}
		$grouppart .= '</select>'."\n";
		$grouppart .= '<span class="hint'.$i.'" id="hint'.$i.'" style="width:15em;">'.$selected_hint."</span>\n";
	} else {
		$grouppart = $group.'<input type="hidden" name="'.$i.'group" value="'.$group.'" />'."\n";
	}
	$result = 
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Group:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" width="345">'.
				$grouppart.
			'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">'.gettext('User group membership.<br /><strong>Note:</strong> When a group is assigned <em>rights</em> and <em>managed albums</em> are determined by the group!').'</td>
		</tr>'."\n";
	return $html.$result;
}

function user_groups_admin_tabs($tabs, $current) {
	$subtabs = array(	gettext('users')=>'admin-users.php?page=users',
										gettext('assignments')=>PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&amp;tab=assignments',
										gettext('groups')=>PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&amp;tab=groups');
	if ((zp_loggedin(ADMIN_RIGHTS))) {
		$tabs['users'] = array(	'text'=>gettext("admin"),
														'link'=>WEBPATH."/".ZENFOLDER.'/admin-users.php?page=users&amp;tab=users',
														'subtabs'=>$subtabs,
														'default'=>'users');
	}
	return $tabs;
}

function user_groups_admin_alterrights($alterrights, $userobj) {
	global $_zp_authority;
	$group = $userobj->getGroup();
	$admins = $_zp_authority->getAdministrators();
	foreach ($admins as $admin) {
		if (!$admin['valid']) {	// is a group or template
			if ($group == $admin['user']) {
				return ' disabled="disabled"';				
			}
		}
	}
	return $alterrights;
}

?>