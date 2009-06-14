<?php
/**
 * Provides an example of the use of the custom data filters
 * 
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("provides rudimentary user groups.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.1.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-user_groups.php.html";

zp_register_filter('admin_tabs', 'user_groups_admin_tabs');
zp_register_filter('admin_alterrights', 'user_groups_admin_alterrights');
zp_register_filter('save_admin_custom_data', 'user_groups_save_admin');
zp_register_filter('edit_admin_custom_data', 'user_groups_edit_admin');
require_once(dirname(dirname(__FILE__)).'/admin-functions.php');

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
	$group = $userobj->getGroup();
	$admins = getAdministrators();
	$ordered = array();
	$groups = array();
	$adminordered = array();
	foreach ($admins as $key=>$admin) {
		$ordered[$key] = $admin['user'];
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
		$grouppart = '<select name="'.$i.'group" onchange="javascript: $(\'#hint'.$i.'\').html(this.options[this.selectedIndex].title);">'."\n";
		$grouppart .= '<option title="'.gettext('no group affiliation').'"></option>'."\n";
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
			$grouppart .= '<option'.$selected.' title="'.$hint.'">'.$user['user'].'</option>'."\n";
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
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">'.gettext('User group membership.<br /><strong>NOTE:</strong> Rights and albums are determined by the group!').'</td>
		</tr>'."\n";
	return $html.$result;
}

function user_groups_admin_tabs($tabs, $current) {
	$subtabs = array(	gettext('users')=>'admin-options.php?page=users&tab=users',
										gettext('assignments')=>substr(PLUGIN_FOLDER,1).'user_groups/user_groups-tab.php?page=users&amp;tab=assignments',
										gettext('groups')=>substr(PLUGIN_FOLDER,1).'user_groups/user_groups-tab.php?page=users&amp;tab=groups');
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
	
echo "<br/>$group:$alterrights";	
	
	return ' DISABLED';
}

?>