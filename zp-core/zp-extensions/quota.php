<?php
/**
 * A quota management system to limit the sum of sizes of uploaded images.
 * 
 * Set the default quota on the plugin options page.
 * You can change the quota for individual users on the Admin tab. Users with ADMIN_RIGHTS
 * are not subject to quotas and will not be assigned ownership of an image.
 * 
 * Images uploaded by a user will be marked as his and will count toward his quota.
 * Images uploaded via FTP will not necessarily have an owner assigned. If there is
 * one assiged, it is the logged on user when the image is discovered by Zenphoto.
 * 
 * You may also assign the complete set of images in an albums to a user. (Just the images in the
 * album. If you want to assign images from subalbums, you need to do that for each 
 * subalbum.)
 * 
 * A user who exceeds his quota will not be allowed to upload files.
 * 
 * Because of the difficulty of policing quotas when ZIP files are uploaded, enabling
 * this plugin will diable ZIP file upload.
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
zp_register_filter('image_refresh', 'quota_image_refresh');
zp_register_filter('get_upload_quota', 'quota_getUploadQuota');
zp_register_filter('check_upload_quota', 'quota_checkQuota');
zp_register_filter('get_upload_limit', 'quota_getUploadLimit');
zp_register_filter('save_album_utilities_data', 'quota_save_album');
zp_register_filter('edit_album_custom_data', 'quota_edit_album');

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
				sprintf(gettext('(%s kb used)'),number_format($used)).
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
 * Returns an option list of administrators who do not have ADMIN_RIGHTS
 * @param string $owner
 * @return string
 */
function quota_admin_list($owner) {
	global $_zp_authority;
	$adminlist = '';
	$admins = $_zp_authority->getAdministrators();
	foreach ($admins as $user) {
		if ($user['valid'] && ($user['rights'] & (UPLOAD_RIGHTS)) && !($user['rights'] & (ADMIN_RIGHTS))) {
			$adminlist .= '<option value="'.$user['user'].'"';
			if ($owner == $user['user']) $adminlist .= ' SELECTED="SELECTED"';
			$adminlist .= '>'.$user['user']."</option>\n";
		}
	}
	return $adminlist;
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
	$owner = $image->getOwner();
	$html = 
		'<tr>
			<td valign="top">'.gettext("Owner:").'</td>
			<td>
				<select name="'.$currentimage.'-owner">
					<option value="">'.gettext('*no owner')."</option>\n".
					quota_admin_list($owner).'
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
	$image->set('filesize',filesize($image->localpath));
	return $image;
}

/**
 * Returns a table entry for image assignment
 * @param string $discard
 * @param object $album
 * @param string $prefix
 * @return string
 */
function quota_edit_album($discard, $album, $prefix) {
	$html = 
		'<tr>
			<td valign="top">'.gettext("Assign images to:").'</td>
			<td>
				<select name="'.$prefix.'assignee">
					<option value="">'."</option>\n".
					quota_admin_list('').'
				</select>
			</td>
		</tr>';
	return $html;
}

/**
 * Assigns images within the album to an owner.
 * @param object $album
 * @param string $prefix
 */
function quota_save_album($album, $prefix) {
	if (!empty($_POST[$prefix.'assignee'])) {
		$sql = 'UPDATE '.prefix('images').' SET `owner`="'.mysql_real_escape_string(sanitize($_POST[$prefix.'assignee'])).'" WHERE `albumid`='.$album->get('id');
		query($sql);
	}
	return $album;
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
 * @return object
 */
function quota_image_refresh($image) {
	$image->set('filesize',filesize($image->localpath));
	$image->save();
	return $image;
}

/**
 * Returns the user's quota
 * @param int $quota
 * @return int
 */
function quota_getUploadQuota($quota) {
	global $_zp_current_admin_obj, $quota_total, $quota_used;
	if (zp_loggedin(ADMIN_RIGHTS)) {
		$quota_used = 0;
		$quota_total = -1;
	} else {
		$quota_used = quota_getCurrentUse($_zp_current_admin_obj);
		$quota_total = $_zp_current_admin_obj->getQuota();
	}
	return $quota_total;
}

/**
 * Returns the upload limit
 * @param int $uploadlimit
 * @return int
 */
function quota_getUploadLimit() {
	global $quota_total, $quota_used;
	$uploadlimit = ($quota_total-$quota_used)*1024;	
	return $uploadlimit;
}

/**
 * Checks if upload should be allowed
 * @param int $error
 * @param object$image
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