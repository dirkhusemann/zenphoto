<?php
/**
 * A quota management system to limit the sum of sizes of uploaded images.
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_is_filter = 5;
$plugin_description = gettext("Provides a quota management system to limit the sum of sizes of images a user uploads. <strong>NOTE</strong> if FTP is used to upload images, manual user assignment is necessary. ZIP file upload is disabled as as quotas are not applied to the files contained therein.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.3.0'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---filter-quota.php.html";

$option_interface = new Quota_management();

zp_register_filter('save_admin_custom_data', 'quota_save_admin');
zp_register_filter('edit_admin_custom_data', 'quota_edit_admin');
zp_register_filter('save_image_utilities_data', 'quota_save_image');
zp_register_filter('edit_image_custom_data', 'quota_edit_image');
zp_register_filter('new_image', 'quota_new_image');
zp_register_filter('image_instantiate', 'quota_image_instantiate');
zp_register_filter('get_upload_quota', 'quota_getUploadQuota');
zp_register_filter('check_upload_quota', 'quota_checkQuota');
zp_register_filter('get_upload_limit', 'quota_getUploadLimit');

/**
 * Option handler class
 *
 */
class Quota_management {
	/**
	 * class instantiation function
	 *
	 * @return filter_zenphoto_seo
	 */
	function Quota_management() {
		setOptionDefault('quota_default', 250000);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Default quota') => array('key' => 'quota_default', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('Default size limit in kilobytes.')));
	}

	function handleOption($option, $currentValue) {
	}

}

$quota_used = NULL;
$quota_total = NULL;

/**
 * Saves admin custom data
 * Called when an admin is saved
 *
 * @param string $discard always empty
 * @param object $userobj admin user object
 * @param string $i prefix for the admin
 * @return string
 */
function quota_save_admin($discard, $userobj, $i) {
	if (isset($_POST[$i.'quota'])) {
		$userobj->setQuota(sanitize_numeric($_POST[$i.'quota']));
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
function quota_edit_admin($html, $userobj, $i, $background, $current) {
	if ($userobj->getRights() & ADMIN_RIGHTS) return $html;
	if (!($userobj->getRights() & UPLOAD_RIGHTS)) return $html;
	$quota = $userobj->getQuota();
	$used = quota_getCurrentUse($userobj);
	if ($quota == NULL) $quota = getOption('quota_default');
	$result = 
		'<tr'.((!$current)? ' style="display:none;"':'').' class="userextrainfo">
			<td width="20%"'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.gettext("Quota:").'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top" width="345">'.
				sprintf(gettext('Allowed: %s kb'),'<input type="text" size="10" name="'.$i.'quota" value="'.$quota.'" />').' '.
				sprintf(gettext('(%u kb used)'),$used).' '.
				"\n".
			'</td>
			<td'.((!empty($background)) ? ' style="'.$background.'"':'').' valign="top">'.gettext('Image quota information.').'</td>
		</tr>'."\n";
	return $html.$result;
}

/**
 * Returns curren image useage
 * @param $userobj Admin user object
 * @return int
 */
function quota_getCurrentUse($userobj) {
	$sql = 'SELECT sum(`filesize`) FROM '.prefix('images').' WHERE `owner`="'.$userobj->getUser().'"';
	$result = query_single_row($sql);
	return array_shift($result)/1024;
}

/**
 * Returns table row(s) for the edit of an image custom data field
 *
 * @param string $discard always empty
 * @param int $currentimage prefix for the image being edited
 * @param object $image the image object
 * @return string
 */
function quota_edit_image($discard, $image, $currentimage) {
	global $_zp_authority;
	$adminlist = '';
	$admins = $_zp_authority->getAdministrators();
	$owner = $image->getOwner();
	foreach ($admins as $user) {
		if ($user['valid'] && ($user['rights'] & (ADMIN_RIGHTS | UPLOAD_RIGHTS))) {
			$adminlist .= '<option value="'.$user['user'].'"';
			if ($owner == $user['user']) $adminlist .= ' SELECTED="SELECTED"';
			$adminlist .= '>'.$user['user']."</option>\n";
		}
	}
	$html = 
		'<tr>
			<td valign="top">'.gettext("Owner:").'</td>
			<td>
				<select name="'.$currentimage.'-owner">
					<option value="">'.gettext('*no owner')."</option>\n".
					$adminlist.'
				</select>
			</td>
		</tr>';
	return $html;
}

/**
 * Option save handler for the filter
 *
 * @param object $object object being rated
 * @param string $prefix indicator if admin is processing multiple objects
 * @rerun object
 */
function quota_save_image($image, $prefix) {
	$image->setOwner(sanitize($_POST[$prefix.'-owner']));
	return $image;
}

/**
 * Assigns owner to new image
 * @param string $image
 * @return object
 */
function quota_new_image($image) {
	global $_zp_current_admin_obj;
	if (is_object($_zp_current_admin_obj)) {
		$image->set('owner',$_zp_current_admin_obj->getUser());
	}
	$image->set('filesize',filesize($image->localpath));
	$image->save();
	return $image;
}

/**
 * checks to see if the filesize is set and sets it if not
 * @param unknown_type $image
 */
function quota_image_instantiate($image) {
	if ($image->get('filesize') == 0) {
		$image->set('filesize',filesize($image->localpath));
	}
	return $image;
}

/**
 * Returns the user's quota
 * @param $quota
 */
function quota_getUploadQuota($quota) {
	global $_zp_current_admin_obj, $quota_total, $quota_used;
	if (is_object($_zp_current_admin_obj) && !($_zp_current_admin_obj->getRights() & ADMIN_RIGHTS)) {
		$quota_used = quota_getCurrentUse($_zp_current_admin_obj);
		$quota_total = $_zp_current_admin_obj->getQuota();
	} else {
		$quota_used = 0;
		$quota_total = -1;
	}
	return $quota_total;
}

/**
 * Returns the upload limit
 * @param $uploadlimit
 */
function quota_getUploadLimit() {
	global $quota_total, $quota_used;
	$uploadlimit = ($quota_total-$quota_used)*1024;	
	return $uploadlimit;
}

/**
 * Checks if upload should be allowed
 * @param $error
 * @param $image
 * @return int
 */
function quota_checkQuota($error, $image) {
	global $quota_total, $quota_used;
	$size = round(filesize($image)/1024);
	if ($quota_total > 0) {
		if ($quota_used + $size > $quota_total) {
			$error = UPLOAD_ERR_QUOTA;
			break;
		} else {
			$quota_used = $quota_used + $size;
		}
	}
	return $error;
}
?>