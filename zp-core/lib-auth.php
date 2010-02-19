<?php
/**
 * functions used in password hashing for zenphoto
 * 
 * @package functions
 * 
 * At least in theory one should be able to replace this script with
 * an alternate to change how Admin users are validated and stored.
 * 
 * Place the new script in the <ZENFOLDER>/plugins/alt/ folder. It then will be automatically loaded 
 * in place of this script.
 * 
 * The global $_zp_current_admin is referenced throuought Zenphoto, so the 
 * elements of the array need to be present in any alternate implementation.
 * in particular, there should be array elements for:
 * 		'id' (unique), 'user' (unique),	'pass',	'name', 'email', 'rights', 'valid', 'group', and 'custom_data'
 * 
 * Admin and the filters 'save_admin_custom_data' and 'edit_admin_custom_data' use the Administrator object 
 * defined below. Slowly other uses of the array may be changed over to use the object but this will probably
 * change the functions below as well.
 * 
 * So long as all these indices are populated it should not matter when and where
 * the data is stored.
 */

/*
 * admin rights [the values for the 'rights' element of $_zp_current_admin
 * at least these definitions are required. Their values should indicate the 
 * hierarchy of privileges as the checkAuthorization function will promote the 
 * "most privileged" Admin to ADMIN_RIGHTS
 * 
 */

require_once(dirname(__FILE__).'/classes.php');

define('LIBAUTH_VERSION', 1);
$_admin_rights = array(	'OVERVIEW_RIGHTS' => 4,
												'VIEW_ALL_RIGHTS' => 8,
												'UPLOAD_RIGHTS' => 16,
												'POST_COMMENT_RIGHTS'=>32,
												'COMMENT_RIGHTS' => 64,
												'ALBUM_RIGHTS' => 256,
												'MANAGE_ALL_ALBUM_RIGHTS' => 512,
												'THEMES_RIGHTS' => 1024,
												'ZENPAGE_RIGHTS' => 2048,
												'TAGS_RIGHTS' => 4096,
												'OPTIONS_RIGHTS' => 8192,
												'ADMIN_RIGHTS' => 65536);
arsort($_admin_rights);
$allrights = 0;
foreach ($_admin_rights as $right=>$value) {
	$allrights = $allrights | $value;
	define($right, $value);
}
define('NO_RIGHTS', 2);
define('ALL_RIGHTS', $allrights | NO_RIGHTS);
unset($allrights);
define('DEFAULT_RIGHTS', OVERVIEW_RIGHTS | VIEW_ALL_RIGHTS | POST_COMMENT_RIGHTS);
$_admin_rights_names = array(	OVERVIEW_RIGHTS => gettext('Overview'),
															VIEW_ALL_RIGHTS => gettext('View all'),
															UPLOAD_RIGHTS => gettext('Upload'),
															POST_COMMENT_RIGHTS => gettext('Post comments'),
															COMMENT_RIGHTS => gettext('Comments'),
															ALBUM_RIGHTS => gettext('Album'),
															MANAGE_ALL_ALBUM_RIGHTS => gettext('Manage all albums'),
															THEMES_RIGHTS => gettext('Themes'),
															ZENPAGE_RIGHTS => gettext('Zenpage'),
															TAGS_RIGHTS => gettext('Tags'),
															OPTIONS_RIGHTS => gettext('Options'),
															ADMIN_RIGHTS => gettext('Admin'));

//admin user handling
$_zp_current_admin = null;
$_zp_admin_users = null;


$_lib_auth_extratext = getOption('extra_auth_hash_text');
/**
 * Returns the hash of the zenphoto password
 *
 * @param string $user
 * @param string $pass
 * @return string
 */
function passwordHash($user, $pass) {
	global $_lib_auth_extratext;
	$md5 = md5($user . $pass . $_lib_auth_extratext);
	return $md5;	
}

/**
 * Checks to see if password follows rules
 * Returns error message if not.
 *
 * @param string $pass
 * @return string
 */
function validatePassword($pass) {
	$l = getOption('min_password_lenght');
	if ($l > 0) {
		if (strlen($pass) < $l) return sprintf(gettext('Password must be at least %u characters'), $l);
	}
	$p = getOption('password_pattern');
	if (!empty($p)) {
		$strong = false;
		$p = str_replace('\|', "\t", $p);	
		$patterns = explode('|', $p);
		$p2 = '';
		foreach ($patterns as $pat) {
			$pat = trim(str_replace("\t", '|', $pat));
			if (!empty($pat)) {
				$p2 .= '{<em>'.$pat.'</em>}, ';
				
				$patrn = '';
				foreach (array('0-9','a-z','A-Z') as $try) {
					if (preg_match('/['.$try.']-['.$try.']/', $pat, $r)) {
						$patrn .= $r[0];
						$pat = str_replace($r[0],'',$pat);
					}
				}
				$patrn .= addcslashes($pat,'\\/.()[]^-');
				if (preg_match('/(['.$patrn.'])/', $pass)) {
					$strong = true;
				}
			}
		}
		if (!$strong)	return sprintf(gettext('Password must contain at least one of %s'), substr($p2,0,-2));
	}
	return false;
}

/**
 * Returns text describing password constraints
 *
 * @return string
 */
function passwordNote() {
	$l = getOption('min_password_lenght');
	$p = getOption('password_pattern');
	$p = str_replace('\|', "\t", $p);	
	$c = 0;
	if (!empty($p)) {
		$patterns = explode('|', $p);
		$text = '';
		foreach ($patterns as $pat) {
			$pat = trim(str_replace("\t", '|', $pat));
			if (!empty($pat)) {
				$c++;
				$text .= ', <span style="white-space:nowrap;"><strong>{</strong><em>'.htmlspecialchars($pat).'</em><strong>}</strong></span>';
			}		
		}
		$text = substr($text, 2);
	}
	if ($c > 0) {
		if ($l > 0) {
			$msg = sprintf(ngettext('<strong>Note</strong>: passwords must be at least %1$u characters long and contain at least one character from %2$s.',
															'<strong>Note</strong>: passwords must be at least %1$u characters long and contain at least one character from each of the following groups: %2$s.', $c), $l, $text);
		} else {
			$msg = sprintf(ngettext('<strong>Note</strong>: passwords must contain at least one character from %s.',
															'<strong>Note</strong>: passwords must contain at least one character from each of the following groups: %s.', $c), $text);
		}
	} else {
		if ($l > 0) {
			$msg = sprintf(gettext('<strong>Note</strong>: passwords must be at least %u characters long.'), $l);
		} else {
			$msg = '';
		}
	}
	return $msg;
}

/**
 * Saves an admin user's settings
 *
 * @param string $user The username of the admin
 * @param string $pass The password associated with the user name
 * @param string $name The display name of the admin
 * @param string $email The email address of the admin
 * @param bit $rights The administrating rites for the admin
 * @param string $custom custom data for the administrator
 * @param array $albums an array of albums that the admin can access. (If empty, access is to all albums)
 * @return string error message if any errors
 */
function saveAdmin($user, $pass, $name, $email, $rights, $albums, $custom='', $group='', $valid=1) {
	if (DEBUG_LOGIN) { debugLog("saveAdmin($user, $pass, $name, $email, $rights, $albums, $custom, $group, $valid)"); }
	$sql = "SELECT `name`, `id` FROM " . prefix('administrators') . " WHERE `user` = '".zp_escape_string($user)."' AND `valid`=$valid";
	$result = query_single_row($sql);
	if (!is_null($pass)) {
		// validate the password.
		$msg = validatePassword($pass);
		if (!empty($msg)) return $msg;
	}
	if ($result) {
		$id = $result['id'];
		if (is_null($pass)) {
			$password = '';
		} else {
			$password = "`pass`='" . zp_escape_string(passwordHash($user, $pass))."', ";
		}
		if (is_null($rights)) {
			$rightsset = '';
		} else {
			$rightsset = "`rights`='" . zp_escape_string($rights)."', ";
		}
		$sql = "UPDATE " . prefix('administrators') . "SET `name`='" . zp_escape_string($name)."', " . $password .
 					"`email`='" . zp_escape_string($email)."', " . $rightsset . "`custom_data`='".zp_escape_string($custom)."', `valid`=".$valid.", `group`='".
					zp_escape_string($group)."' WHERE `id`='" . $id ."'";
		$result = query($sql);
		if (DEBUG_LOGIN) { debugLog("saveAdmin: updating[$id]:$result");	}
	} else {
		$passupdate = 'NULL';
		if (!is_null($pass)) {
			$passupdate = "'".zp_escape_string(passwordHash($user, $pass))."'";
		}
		$sql = "INSERT INTO " . prefix('administrators') . " (`user`, `pass`, `name`, `email`, `rights`, `custom_data`, `valid`, `group`) VALUES ('" .
				zp_escape_string($user) . "'," . $passupdate . ",'" . zp_escape_string($name) . "','" . 
				zp_escape_string($email) . "'," . $rights . ", '".zp_escape_string($custom)."', ".$valid.", '".
				zp_escape_string($group)."')";
		$result = query($sql);
		$id = mysql_insert_id();
		if (DEBUG_LOGIN) { debugLog("saveAdmin: inserting[$id]:$result"); }
	}
	$gallery = new Gallery();
	if (is_array($albums)) {
		$sql = "DELETE FROM ".prefix('admintoalbum')." WHERE `adminid`=$id";
		$result = query($sql);
		foreach ($albums as $albumname) {
			$album = new Album($gallery, $albumname);
			$albumid = $album->getAlbumID();
			$sql = "INSERT INTO ".prefix('admintoalbum')." (adminid, albumid) VALUES ($id, $albumid)";
			$result = query($sql);
		}
	}
	return '';
}

/**
 * Returns an array of admin users, indexed by the userid and ordered by "privileges"
 *
 * The array contains the id, hashed password, user's name, email, and admin priviledges
 *
 * @return array
 */
function getAdministrators() {
	global $_zp_admin_users;
	if (is_null($_zp_admin_users)) {
		$_zp_admin_users = array();
		$sql = 'SELECT * FROM '.prefix('administrators').' ORDER BY `rights` DESC, `id`';
		$admins = query_full_array($sql, true);
		if ($admins !== false) {
			foreach($admins as $user) {
				if (array_key_exists('password', $user)) { // transition code!
					$user['pass'] = $user['password'];
					unset($user['password']);
				}
				if (!array_key_exists('valid', $user)) { // transition code!
					$user['valid'] = 1;
				}
				$_zp_admin_users[$user['id']] = $user;
			}
		}
	}
	return $_zp_admin_users;
}

/**
 * Retuns the administration rights of a saved authorization code
 * Will promote an admin to ADMIN_RIGHTS if he is the most privileged admin
 *
 * @param string $authCode the md5 code to check
 *
 * @return bit
 */
function checkAuthorization($authCode) {
	global $_zp_current_admin;
	if (DEBUG_LOGIN) { debugLogBacktrace("checkAuthorization($authCode)");	}
	$admins = getAdministrators();
	
/** uncomment to auto-login for backend HTML validation	
	$_zp_current_admin = array_shift($admins);
	return $_zp_current_admin['rights'] | ADMIN_RIGHTS;
*/
	
	foreach ($admins as $key=>$user) {
		if (!$user['valid']) {	// no groups!
			unset($admins[$key]);
		}
	}
	if (DEBUG_LOGIN) { debugLogArray("checkAuthorization: admins",$admins);	}
	$reset_date = getOption('admin_reset_date');
	if ((count($admins) == 0) || empty($reset_date)) {
		$_zp_current_admin = null;
		if (DEBUG_LOGIN) { debugLog("checkAuthorization: no admin or reset request"); }
		return ADMIN_RIGHTS; //no admins or reset request
	}
	if (empty($authCode)) return 0; //  so we don't "match" with an empty password
	$i = 0;
	foreach($admins as $key=>$user) {
		if (DEBUG_LOGIN) { debugLog("checkAuthorization: checking: $key");	}
		if ($user['pass'] == $authCode) {
			$_zp_current_admin = $user;
			$result = $user['rights'];
			if ($i == 0) { // the first admin is the master.
				$result = $result | ADMIN_RIGHTS;
			}
			if (DEBUG_LOGIN) { debugLog("checkAuthorization: match");	}
			return $result;
		}
		$i++;
	}
	$_zp_current_admin = null;
	if (DEBUG_LOGIN) { debugLog("checkAuthorization: no match");	}
	return 0; // no rights
}

/**
 * Checks a logon user/password against the list of admins
 *
 * Returns true if there is a match
 *
 * @param string $user
 * @param string $pass
 * @param bool $admin_login will be true if the login for the backend. If false, it is a guest login beging checked for admin credentials
 * @return bool
 */
function checkLogon($user, $pass, $admin_login) {
	$admins = getAdministrators();
	$success = false;
	$md5 = passwordHash($user, $pass);
	foreach ($admins as $admin) {
		if ($admin['user'] == $user) {
			if ($admin['pass'] == $md5) {
				$success = checkAuthorization($md5);
				break;
			}
		}
	}
	return $success;
}

/**
 * Returns the email addresses of the Admin with ADMIN_USERS rights
 *
 * @param bit $rights what kind of admins to retrieve
 * @return array
 */
function getAdminEmail($rights=ADMIN_RIGHTS) {
	$emails = array();
	$admins = getAdministrators();
	$user = array_shift($admins);
	if (!empty($user['email'])) {
		$name = $user['name'];
		if (empty($name)) {
			$name = $user['user'];
		}
		$emails[$name] = $user['email'].' ('.$user['user'].')';
	}
	foreach ($admins as $user) {
		if (($user['rights'] & $rights)  && !empty($user['email'])) {
			$emails[] = $user['email'];
		}
	}
	return $emails;
}

/**
 * Migrates credentials
 *
 * @param int $oldversion
 */
function migrateAuth($oldversion) {
	global $_admin_rights;
	$_zp_admin_users = array();
	$sql = "SELECT * FROM ".prefix('administrators')."ORDER BY `rights` DESC, `id`";
	$admins = query_full_array($sql, true);
	if (count($admins)>0) { // something to migrate
		printf(gettext('Migrating lib-auth data version %1$s => version %2$s'), $oldversion, LIBAUTH_VERSION);	
		switch ($oldversion) {
			case 1:
				$oldrights = array(	'NO_RIGHTS' => 2, // only in migration array
														'OVERVIEW_RIGHTS' => 4,
														'VIEW_ALL_RIGHTS' => 8,
														'UPLOAD_RIGHTS' => 16,
														'POST_COMMENT_RIGHTS'=>32,
														'COMMENT_RIGHTS' => 64,
														'ALBUM_RIGHTS' => 256,
														'MANAGE_ALL_ALBUM_RIGHTS' => 512,
														'THEMES_RIGHTS' => 1024,
														'ZENPAGE_RIGHTS' => 2048,
														'TAGS_RIGHTS' => 4096,
														'OPTIONS_RIGHTS' => 8192,
														'ADMIN_RIGHTS' => 65536);
				break;
			case 2:
				$oldrights = array(	'NO_RIGHTS' => 1, // this should only be in the migration array
														'OVERVIEW_RIGHTS' => pow(2,2),
														'VIEW_ALL_RIGHTS' => pow(2,4),
														'POST_COMMENT_RIGHTS' => pow(2,6),
														'UPLOAD_RIGHTS' => pow(2,8),
														'COMMENT_RIGHTS' => pow(2,10),
														'ALBUM_RIGHTS' => pow(2,12),
														'MANAGE_ALL_ALBUM_RIGHTS' => pow(2,14),
														'THEMES_RIGHTS' => pow(2,16),
														'ZENPAGE_RIGHTS' => pow(2,18),
														'TAGS_RIGHTS' => pow(2,20),
														'OPTIONS_RIGHTS' => pow(2,22),
														'ADMIN_RIGHTS' => pow(2,24));
				break;
			default: // anything before the rights version was created
				$oldrights = NULL;
				break;
		}
		foreach($admins as $user) {
			$update = false;
			if (is_array($oldrights)) {
				$rights = $user['rights'];
				$newrights = 0;
				foreach ($_admin_rights as $key=>$right) {
					if (array_key_exists($key, $oldrights) && $rights & $oldrights[$key]) {
						$newrights = $newrights | $right;
					}
				}
			} else {
				$newrights = $user['rights'];
				if (NO_RIGHTS == 2) {
					if (($rights = $user['rights']) & 1) { // old compressed rights
						$newrights = OVERVIEW_RIGHTS;
						if ($rights & 2) $newrights = $newrights | UPLOAD_RIGHTS;
						if ($rights & 4) $newrights = $newrights | COMMENT_RIGHTS;
						if ($rights & 8) $newrights = $newrights | ALBUM_RIGHTS;
						if ($rights & 16) $newrights = $newrights | THEMES_RIGHTS;
						if ($rights & 32) $newrights = $newrights | OPTIONS_RIGHTS;
						if ($rights & 16384) $newrights = $newrights | ADMIN_RIGHTS;
					}
				} else {
					if (!(($rights = $user['rights']) & 1)) { // new expanded rights
						$newrights = OVERVIEW_RIGHTS;
						if ($rights & 16) $newrights = $newrights | UPLOAD_RIGHTS;
						if ($rights & 64) $newrights = $newrights | COMMENT_RIGHTS;
						if ($rights & 256) $newrights = $newrights | ALBUM_RIGHTS;
						if ($rights & 1024) $newrights = $newrights | THEMES_RIGHTS;
						if ($rights & 8192) $newrights = $newrights | OPTIONS_RIGHTS;
						if ($rights & 65536) $newrights = $newrights | ADMIN_RIGHTS;
					}
				}
			}
			$sql = 'UPDATE '.prefix('administrators').' SET `rights`='.$newrights.' WHERE `id`='.$user['id'];
			query($sql);
		} // end loop
	} else {
		$_lib_auth_extratext = "";
		$salt = 'abcdefghijklmnopqursuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789~!@#$%^&*()_+-={}[]|\:;<>,.?/';
		$list = range(0, strlen($salt));
		shuffle($list);
		for ($i=0; $i < 30; $i++) {
			$_lib_auth_extratext = $_lib_auth_extratext . substr($salt, $list[$i], 1);
		}
		setOption('extra_auth_hash_text', $_lib_auth_extratext);
	}
}

/**
 * Deletes admin record(s)
 *
 * @param array $constraints field value pairs for constraining the delete
 * @return mixed Query result
 */
function deleteAdmin($constraints) {
	$where = '';
	foreach ($constraints as $field=>$clause) {
		$where .= '`'.$field.'`="'.$clause.'" ';
	}
	$sql = "DELETE FROM ".prefix('administrators')." WHERE $where";
	return query($sql);
}

/**
 * Updates a field in admin record(s)
 *
 * @param string $field name of the field
 * @param mixed $value what to store
 * @param array $constraints field value pairs for constraining the update
 * @return mixed Query result
 */
function updateAdminField($field, $value, $constraints) {
	$where = '';
	foreach ($constraints as $field=>$clause) {
		if (!empty($where)) $where .= ' AND ';
		$where .= '`'.$field.'`="'.zp_escape_string($clause).'" ';
	}
	if (is_null($value)) {
		$value = 'NULL';
	} else {
		$value = '"'.$value.'"';
	}
	$sql = 'UPDATE '.prefix('administrators').' SET `'.$field.'`='.$value.' WHERE '.$where;
	return query($sql);
}

/**
 * Option handler class
 *
 */
class lib_auth_options {
	
	/**
	 * class instantiatio function
	 *
	 * @return lib_auth_options
	 */
	function lib_auth_options() {
		setOptionDefault('extra_auth_hash_text', '');
		setOptionDefault('min_password_lenght', 6);
		setOptionDefault('password_pattern', 'A-Za-z0-9   |   ~!@#$%&*_+`-(),.\^\'"/[]{}=:;?\|');
	}

	/**
	 * Declares options used by lib-auth
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Augment password hash:') => array('key' => 'extra_auth_hash_text', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('Extra text appended when hashing password to strengthen Zenphoto authentication. <strong>NOTE:</strong> Changing this will require all users to reset their passwords! You should change your password immediately if you change this text.')),
									gettext('Minimum password length:') => array('key' => 'min_password_lenght', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('Minimum number of characters a password must contain.')),
									gettext('Password characters:') => array('key' => 'password_pattern', 'type' => OPTION_TYPE_CLEARTEXT,
										'desc' => gettext('Passwords must contain at least one of the characters from each of the groups. Groups are separated by "|". (Use "\|" to represent the "|" character in the groups.)'))
		);
	}
}
				
class Administrator extends PersistentObject {
	
	/**
	 * This is a simple class so that we have a convienient "handle" for manipulating Administrators.
	 *
	 */
	
	/**
	 * Constructor for an Administrator
	 *
	 * @param string $userid.
	 * @return Administrator
	 */
	function Administrator($userid, $valid=1) {
		parent::PersistentObject('administrators',  array('user' => $userid, 'valid'=>$valid), 'user', true, empty($userid));
	}
	
	function setPass($pwd) {
		$this->set('pass', $pwd);
	}
	function getPass() {
		return $this->get('pass');
	}
	
	function setName($admin_n) {
		$this->set('name', $admin_n);
	}
	function getName() {
		return $this->get('name');
	}
	
	function setEmail($admin_e) {
		$this->set('email', $admin_e);
	}
	function getEmail() {
		return $this->get('email');
	}
	
	function setRights($rights) {
		$this->set('rights', $rights);
	}
	function getRights() {
		return $this->get('rights');
	}
	
	function setAlbums($albums) {
		$this->set('albums', $albums);
	}
	function getAlbums() {
		return $this->get('albums');
	}
	
	function setCustomData($custom_data) {
		$this->set('custom_data', $custom_data);
	}
	function getCustomData() {
		return $this->get('custom_data');
	}
	
	function setValid($valid) {
		$this->set('valid', $valid);
	}
	function getValid() {
		return $this->get('valid');
	}
	
	function setGroup($group) {
		$this->set('group', $group);
	}
	function getGroup() {
		return $this->get('group');
	}

	function setUser($user) {
		$this->set('user', $user);
	}
	function getUser() {
		return $this->get('user');
	}
	
}

?>