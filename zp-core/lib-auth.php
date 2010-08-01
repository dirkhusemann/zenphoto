<?php
/**
* functions used in password hashing for zenphoto
 *
 * @package functions
 *
 * An alternate authorization script may be provided to override this script. To do so, make a script that
 * implements the classes declared below. Place the new script inthe <ZENFOLDER>/plugins/alt/ folder. Zenphoto
 * will then will be automatically loaded the alternate script in place of this one.
 *
 * Replacement libraries must implement two classes:
 * 		"Authority" class: Provides the methods used for user authorization and management
 * 			store an instantiation of this class in $_zp_authority.
 *
 * 		Administrator: supports the basic Zenphoto needs for object manipulation of administrators.
 * (You can include this script and extend the classes if that suits your needs.)
 *
 * The global $_zp_current_admin_obj represents the current admin with.
 * The library must instantiate its authority class and store the object in the global $_zp_authority
 * (Note, this library does instantiate the object as described. This is so its classes can
 * be used as parent classes for lib-auth implementations. If auth_zp.php decides to use this
 * library it will instantiate the class and store it into $_zp_authority.
 *
 * The following elements need to be present in any alternate implementation in the
 * array returned by getAdministrators().
 *
 * 		In particular, there should be array elements for:
 * 				'id' (unique), 'user' (unique),	'pass',	'name', 'email', 'rights', 'valid',
 * 				'group', and 'custom_data'
 *
 * 		So long as all these indices are populated it should not matter when and where
 *		the data is stored.
 *
 *		Administrator class methods are required for these elements as well.
 *
 * 		The getRights() method must define at least the rights defined by the method in
 * 		this library.
 *
 * 		The checkAuthorization() method should promote the "most privileged" Admin to
 * 		ADMIN_RIGHTS to insure that there is some user capable of adding users or
 * 		modifying user rights.
 *
 *
 */

require_once(dirname(__FILE__).'/classes.php');

class Zenphoto_Authority {

	var $admin_users = NULL;
	var $rightsset = NULL;
	var $version = 2;


	/**
	 * class instantiator
	 */
	function Zenphoto_Authority() {
	}

	/**
	 * Returns the hash of the zenphoto password
	 *
	 * @param string $user
	 * @param string $pass
	 * @return string
	 */
	function passwordHash($user, $pass) {
		$hash = getOption('extra_auth_hash_text');
		$md5 = md5($user . $pass . $hash);
		if (DEBUG_LOGIN) { debugLog("passwordHash($user, $pass)[$hash]:$md5"); }
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
					$text .= ', <span style="white-space:nowrap;"><strong>{</strong><em>'.htmlspecialchars($pat,ENT_QUOTES).'</em><strong>}</strong></span>';
				}
			}
			$text = substr($text, 2);
		}
		if ($c > 0) {
			if ($l > 0) {
				$msg = '<p class="notebox">'.sprintf(ngettext('<strong>Note:</strong> passwords must be at least %1$u characters long and contain at least one character from %2$s.',
															'<strong>Note</strong>: passwords must be at least %1$u characters long and contain at least one character from each of the following groups: %2$s.', $c), $l, $text).'</p>';;
			} else {
				$msg = '<p class="notebox">'.sprintf(ngettext('<strong>Note</strong>: passwords must contain at least one character from %s.',
															'<strong>Note</strong>: passwords must contain at least one character from each of the following groups: %s.', $c), $text).'</p>';
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
	 * Returns an array of admin users, indexed by the userid and ordered by "privileges"
	 *
	 * The array contains the id, hashed password, user's name, email, and admin privileges
	 *
	 * @return array
	 */
	function getAdministrators() {
		if (is_null($this->admin_users)) {
			$this->admin_users = array();
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
					$this->admin_users[$user['id']] = $user;
				}
			}
		}
		return $this->admin_users;
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
		global $_zp_current_admin_obj;
		if (DEBUG_LOGIN) { debugLogBacktrace("checkAuthorization($authCode)");	}
		$admins = $this->getAdministrators();

		/** uncomment to auto-login for backend HTML validation
		 $user = array_shift($admins);
		 $_zp_current_admin_obj = $this->newAdministrator($user['user']);
		 return $user['rights'] | ADMIN_RIGHTS;
		 */

		foreach ($admins as $key=>$user) {
			if (!$user['valid']) {	// no groups!
				unset($admins[$key]);
			}
		}
		if (DEBUG_LOGIN) { debugLogArray("checkAuthorization: admins",$admins);	}
		$reset_date = getOption('admin_reset_date');
		if ((count($admins) == 0) || empty($reset_date)) {
			$_zp_current_admin_obj = NULL;
			if (DEBUG_LOGIN) { debugLog("checkAuthorization: no admin or reset request"); }
			return ADMIN_RIGHTS; //no admins or reset request
		}
		if (empty($authCode)) return 0; //  so we don't "match" with an empty password
		$i = 0;
		foreach($admins as $key=>$user) {
			if (DEBUG_LOGIN) { debugLog("checkAuthorization: checking: $key");	}
			if ($user['pass'] == $authCode) {
				$_zp_current_admin_obj = $this->newAdministrator($user['user']);
				$result = $user['rights'];
				if ($i == 0) { // the first admin is the master.
					$result = $result | ADMIN_RIGHTS;
				}
				if (DEBUG_LOGIN) { debugLog("checkAuthorization: match");	}
				return $result;
			}
			$i++;
		}
		$_zp_current_admin_obj = null;
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
		$admins = $this->getAdministrators();
		$success = false;
		$md5 = $this->passwordHash($user, $pass);
		foreach ($admins as $admin) {
			if ($admin['valid']) {
				if (DEBUG_LOGIN) { debugLogArray('checking:',$admin); }
				if ($admin['user'] == $user) {
					if ($admin['pass'] == $md5) {
						$success = $this->checkAuthorization($md5);
						break;
					}
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
	function getAdminEmail($rights=NULL) {
		if (is_null($rights)) $rights = ADMIN_RIGHTS;
		$emails = array();
		$admins = $this->getAdministrators();
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
		$this->admin_users = array();
		$sql = "SELECT * FROM ".prefix('administrators')."ORDER BY `rights` DESC, `id`";
		$admins = query_full_array($sql, true);
		if (count($admins)>0) { // something to migrate
			printf(gettext('Migrating lib-auth data version %1$s => version %2$s'), $oldversion, $this->version);
			$oldrights = array();
			foreach ($this->getRights($oldversion) as $key=>$right) {
				$oldrights[$key] = $right['value'];
			}

			foreach($admins as $user) {
				$update = false;
				$rights = $user['rights'];
				$newrights = 0;
				foreach ($this->getRights() as $key=>$right) {
					if ($right['display']) {
						if (array_key_exists($key, $oldrights) && $rights & $oldrights[$key]) {
							$newrights = $newrights | $right['value'];
						}
					}
				}
				switch ($oldversion) {	// need to migrate zenpage rights
					case '1':
						if ($rights & $oldrights['ZENPAGE_RIGHTS']) {
							$newrights = $newrights | ZENPAGE_PAGES_RIGHTS | ZENPAGE_NEWS_RIGHTS | FILES_RIGHTS;
						}
						break;
					default:
						if ($this->version == 1) {
							if ($rights & ($oldrights['ZENPAGE_PAGES_RIGHTS'] | $oldrights['ZENPAGE_NEWS_RIGHTS'] | $oldrights['FILES_RIGHTS'])) {
								$newrights = $newrights | ZENPAGE_RIGHTS;
							}
						}
						break;
				}
				$sql = 'UPDATE '.prefix('administrators').' SET `rights`='.$newrights.' WHERE `id`='.$user['id'];
				query($sql);
			} // end loop
		} else {
			$lib_auth_extratext = "";
			$salt = 'abcdefghijklmnopqursuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789~!@#$%^&*()_+-={}[]|\:;<>,.?/';
			$list = range(0, strlen($salt));
			shuffle($list);
			for ($i=0; $i < 30; $i++) {
				$lib_auth_extratext = $lib_auth_extratext . substr($salt, $list[$i], 1);
			}
			setOption('extra_auth_hash_text', $lib_auth_extratext);
		}
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
	 * Instantiates and returns administrator object
	 * @param $name
	 * @param $valid
	 * @return object
	 */
	function newAdministrator($name, $valid=1) {
		return new Zenphoto_Administrator($name, $valid);
	}

	/**
	 * Returns an array of the rights definitions for $version (default returns current version rights)
	 * 
	 * @param $version
	 */
	function getRights($version=NULL) {
		$rightsset = $this->rightsset;
		if (!empty($version) || is_null($rightsset)) {
			if (empty($version)) {
				$v = $this->version;
			} else {
				$v = $version;
			}
			switch ($v) {
				case 1:
					$rightsset = array(	'NO_RIGHTS' => array('value'=>2,'name'=>gettext('No rights'),'display'=>false),
															'OVERVIEW_RIGHTS' => array('value'=>4,'name'=>gettext('Overview'),'display'=>true),
															'VIEW_ALL_RIGHTS' => array('value'=>8,'name'=>gettext('View all'),'display'=>true),
															'UPLOAD_RIGHTS' => array('value'=>16,'name'=>gettext('Upload'),'display'=>true),
															'POST_COMMENT_RIGHTS'=> array('value'=>32,'name'=>gettext('Post comments'),'display'=>true),
															'COMMENT_RIGHTS' => array('value'=>64,'name'=>gettext('Comments'),'display'=>true),
															'ALBUM_RIGHTS' => array('value'=>256,'name'=>gettext('Album'),'display'=>true),
															'MANAGE_ALL_ALBUM_RIGHTS' => array('value'=>512,'name'=>gettext('Manage all albums'),'display'=>true),
															'THEMES_RIGHTS' => array('value'=>1024,'name'=>gettext('Themes'),'display'=>true),
															'ZENPAGE_RIGHTS' => array('value'=>2049,'name'=>gettext('Zenpage'),'display'=>true),
															'TAGS_RIGHTS' => array('value'=>4096,'name'=>gettext('Tags'),'display'=>true),
															'OPTIONS_RIGHTS' => array('value'=>8192,'name'=>gettext('Options'),'display'=>true),
															'ADMIN_RIGHTS' => array('value'=>65536,'name'=>gettext('Admin'),'display'=>true));
					break;
				case 2:
					$rightsset = array(	'NO_RIGHTS' => array('value'=>1,'name'=>gettext('No rights'),'display'=>false),
															'OVERVIEW_RIGHTS' => array('value'=>pow(2,2),'name'=>gettext('Overview'),'display'=>true),
															'VIEW_ALL_RIGHTS' => array('value'=>pow(2,4),'name'=>gettext('View all'),'display'=>true),
															'UPLOAD_RIGHTS' => array('value'=>pow(2,6),'name'=>gettext('Upload'),'display'=>true),
															'POST_COMMENT_RIGHTS'=> array('value'=>pow(2,8),'name'=>gettext('Post comments'),'display'=>true),
															'COMMENT_RIGHTS' => array('value'=>pow(2,10),'name'=>gettext('Comments'),'display'=>true),
															'ALBUM_RIGHTS' => array('value'=>pow(2,12),'name'=>gettext('Albums'),'display'=>true),
															'ZENPAGE_PAGES_RIGHTS' => array('value'=>pow(2,14),'name'=>gettext('Pages'),'display'=>true),
															'ZENPAGE_NEWS_RIGHTS' => array('value'=>pow(2,16),'name'=>gettext('News'),'display'=>true),
															'FILES_RIGHTS' => array('value'=>pow(2,18),'name'=>gettext('Files'),'display'=>true),
															'MANAGE_ALL_PAGES_RIGHTS' => array('value'=>pow(2,20),'name'=>gettext('Manage all pages'),'display'=>true),
															'MANAGE_ALL_NEWS_RIGHTS' => array('value'=>pow(2,22),'name'=>gettext('Manage all news'),'display'=>true),
															'MANAGE_ALL_ALBUM_RIGHTS' => array('value'=>pow(2,24),'name'=>gettext('Manage all albums'),'display'=>true),
															'THEMES_RIGHTS' => array('value'=>pow(2,26),'name'=>gettext('Themes'),'display'=>true),
															'TAGS_RIGHTS' => array('value'=>pow(2,28),'name'=>gettext('Tags'),'display'=>true),
															'OPTIONS_RIGHTS' => array('value'=>pow(2,29),'name'=>gettext('Options'),'display'=>true),
															'ADMIN_RIGHTS' => array('value'=>pow(2,30),'name'=>gettext('Admin'),'display'=>true));
					break;
			}
			$allrights = 0;
			foreach ($rightsset as $key=>$right) {
				$allrights = $allrights | $right['value'];
			}
			$rightsset['ALL_RIGHTS'] =	array('value'=>$allrights,'name'=>gettext('All rights'),'display'=>false);
			$rightsset['DEFAULT_RIGHTS'] =	array('value'=>$rightsset['OVERVIEW_RIGHTS']['value']+$rightsset['VIEW_ALL_RIGHTS']['value']+$rightsset['POST_COMMENT_RIGHTS']['value'],'name'=>gettext('Default rights'),'display'=>false);
			$rightsset = sortMultiArray($rightsset,'value',true,false,false);

			if (empty($version)) {
				$this->rightsset = $rightsset;
			}
		}
		return $rightsset;
	}

	function getVersion() {
		return $this->version;
	}

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
										'desc' => gettext('Extra text appended when hashing password to strengthen Zenphoto authentication.').'<p class="notebox">'.gettext('<strong>Note:</strong> Changing this will require all users to reset their passwords! You should change your password immediately if you change this text.').'</p>'),
		gettext('Minimum password length:') => array('key' => 'min_password_lenght', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('Minimum number of characters a password must contain.')),
		gettext('Password characters:') => array('key' => 'password_pattern', 'type' => OPTION_TYPE_CLEARTEXT,
										'desc' => gettext('Passwords must contain at least one of the characters from each of the groups. Groups are separated by "|". (Use "\|" to represent the "|" character in the groups.)'))
		);
	}
}

class Zenphoto_Administrator extends PersistentObject {

	/**
	 * This is a simple class so that we have a convienient "handle" for manipulating Administrators.
	 *
	 */
	var $objects = array();

	/**
	 * Constructor for an Administrator
	 *
	 * @param string $userid.
	 * @return Administrator
	 */
	function Zenphoto_Administrator($user, $valid) {
		parent::PersistentObject('administrators',  array('user' => $user, 'valid'=>$valid), NULL, false, empty($user));
	}

	function getID() {
		return $this->get('id');
	}

	function setPass($pwd) {
		global $_zp_authority;
		$msg = $_zp_authority->validatePassword($pwd);
		if (!empty($msg)) return $msg;	// password validation failure
		$pwd = $_zp_authority->passwordHash($this->getUser(),$pwd);
		$this->set('pass', $pwd);
		return false;
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

	function setObjects($objects) {
		$this->objects = $objects;
	}
	function getObjects() {
		return $this->objects;
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
	
	function setQuota($v) {
		$this->set('quota',$v);
	}
	function getQuota() {
		return $this->get('quota');
	}
	
	function save() {
		if (DEBUG_LOGIN) { debugLogVar("Zenphoto_Adminministratir->save()", $this); }
		$objects = $this->getObjects();
		$gallery = new Gallery();
		parent::save();
		$id = $this->getID();
		if (is_array($objects)) {
			$sql = "DELETE FROM ".prefix('admin_to_object').' WHERE `adminid`='.$id;
			$result = query($sql);
			foreach ($objects as $object) {
				if (array_key_exists('edit',$object)) {
					$edit = $object['edit'];
				} else {
					$edit = 32767;
				}
				switch ($object['type']) {
					case 'album':
						$album = new Album($gallery, $object['data']);
						$albumid = $album->getAlbumID();
						$sql = "INSERT INTO ".prefix('admin_to_object')." (adminid, objectid, type, edit) VALUES ($id, $albumid, 'album', $edit)";
						$result = query($sql);
						break;
					case 'pages':
						$sql = 'SELECT * FROM '.prefix('zenpage_pages').' WHERE `titlelink`="'.$object['data'].'"';
						$result = query_single_row($sql);
						if (is_array($result)) {
							$objectid = $result['id'];
							$sql = "INSERT INTO ".prefix('admin_to_object')." (adminid, objectid, type, edit) VALUES ($id, $objectid, 'pages', $edit)";
							$result = query($sql);
						}
						break;
					case 'news':
						$sql = 'SELECT * FROM '.prefix('zenpage_news_categories').' WHERE `cat_link`="'.$object['data'].'"';
						$result = query_single_row($sql);
						if (is_array($result)) {
							$objectid = $result['id'];
							$sql = "INSERT INTO ".prefix('admin_to_object')." (adminid, objectid, type, edit) VALUES ($id, $objectid, 'news', $edit)";
							$result = query($sql);
						}
						break;
				}
			}
		}
	}
	
	function delete() {
		$id = $this->getID();
		$sql = "DELETE FROM ".prefix('administrators')." WHERE `id`=".$id;
		query($sql);
		$sql = "DELETE FROM ".prefix('admin_to_object')." WHERE `adminid`=$id";
		query($sql);
	}

}

?>