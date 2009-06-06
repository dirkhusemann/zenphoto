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

register_filter('admin_tabs', 'admin_tabs', 2);
register_filter('admin_alterrights', 'admin_alterrights', 2);
register_filter('save_admin_custom_data', 'save_admin', 3);
register_filter('edit_admin_custom_data', 'edit_admin', 5);
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
function save_admin($discard, $userobj, $i) {
	$administrators = getAdministrators();
	$groupname = sanitize($_POST[$i.'group']);
	$userobj->setGroup($groupname);
	foreach ($administrators as $group) {
		if (!$group['valid']) {
			if ($group['user'] == $groupname) { // matches up with group
				$userobj->setRights($group['rights']);
				$userobj->setAlbums(populateManagedAlbumList($group['id']));
				break;
			}
		}
	}	
}

/**
 * Returns table row(s) for edit of an admin user's custom data
 *
 * @param string $discard always empty
 * @param $userobj Admin user object
 * @param string $i prefix for the admin
 * @param string $background background color for the admin row
 * @param bool $current true if this admin row is the logged in admin
 * @return string
 */
function edit_admin($discard, $userobj, $i, $background, $current) {
	$group = $userobj->getGroup();
	$admins = getAdministrators();
	$ordered = array();
	$groups = array();
	foreach ($admins as $key=>$admin) {
		$ordered[$key] = $admin['user'];
	}
	asort($ordered);
	$adminordered = array();
	foreach ($ordered as $key=>$user) {
		$adminordered[] = $admins[$key];
		if (!$user['valid']) {
			$groups[] = $admins[$key];
		}
	}
	if (empty($groups)) return ''; // no groups setup yet
	if (zp_loggedin(ADMIN_RIGHTS)) {
		$grouppart = '<select name="'.$i.'group" >'."\n";
		$grouppart .= '<option></option>'."\n";
		foreach ($groups as $user) {
			if ($group == $user['user']) {
				$selected = ' SELECTED="SELECTED"';
			} else {
				$selected = '';
			}
			$grouppart .= '<option'.$selected.'>'.$user['user'].'</option>'."\n";
		}
		$grouppart .= '</select>'."\n";
	} else {
		$grouppart = $group.'<input type="hidden" name="'.$i.'group" value="'.$group.'" />'."\n";
	}
	$result = 
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? 'style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Group:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">'.
				$grouppart.
			'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" rowspan="5">'.gettext('User group membership.<br /><strong>NOTE:</strong> Rights and albums are determined by the group!').'</td>
		</tr>';
	return $result;
}

function admin_tabs($tabs, $current) {
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

function admin_alterrights($alterrights, $userobj) {
	$group = $userobj->getGroup();
	if (empty($group)) return $alterrights;
	return ' DISABLED';
}

?>