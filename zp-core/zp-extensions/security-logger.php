<?php
/**
 * Places security information in a security log
 * The logged data includes the ip address of the site attempting the login, the type of login, the user/user name,
 * and the success/failure. On failure, the password used in the attempt is also shown.
 * 
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = sprintf(gettext("Logs all attempts to login to or illegally access the admin pages. Log is kept in <em>security_log.txt</em> in the %s folder."),DATA_FOLDER);
$plugin_author = "Stephen Billard (sbillard)";
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/".PLUGIN_FOLDER."--security-logger.php.html";
$plugin_version = '1.3.1'; 
$option_interface = new security_logger();

if (getOption('logger_log_admin')) zp_register_filter('admin_login_attempt', 'security_logger_adminLoginLogger');
if (getOption('logger_log_guests')) zp_register_filter('guest_login_attempt', 'security_logger_guestLoginLogger');
zp_register_filter('admin_allow_access', 'security_logger_adminGate');
zp_register_filter('admin_managed_albums_access', 'security_logger_adminAlbumGate');

/**
 * Option handler class
 *
 */
class security_logger {
	/**
	 * class instantiation function
	 *
	 * @return security_logger
	 */
	function security_logger() {
		setOptionDefault('logger_log_guests', 1);
		setOptionDefault('logger_log_admin', 1);
		setOptionDefault('logger_log_type', 'all');
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Record logon attempts of') => array('key' => 'logger_log_allowed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
										'checkboxes' => array(gettext('Administrators') => 'logger_log_admin', gettext('Guests') => 'logger_log_guests'),
										'desc' => gettext('If checked login attempts will be logged.')),
									gettext('Record') =>array('key' => 'logger_log_type', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array(gettext('All attempts') => 'all', gettext('Successful attempts') => 'success', gettext('unsuccessful attempts') => 'fail'),
										'desc' => gettext('Record login failures, successes, or all attempts.'))
		);
	}

	function handleOption($option, $currentValue) {
	}

}

/**
 * Does the log handling
 *
 * @param int $success
 * @param string $user
 * @param string $pass
 * @param string $name
 * @param string $ip
 * @param string $type
 * @param string $authority kind of login
 * @param string $addl more info
 */
function security_logger_loginLogger($success, $user, $pass, $name, $ip, $type, $authority, $addl=NULL) {
	$file = dirname(dirname(dirname(__FILE__))).'/'.DATA_FOLDER . '/security_log.txt';
	$preexists = file_exists($file) && filesize($file) > 0;
	$f = fopen($file, 'a');
	if (!$preexists) { // add a header
		fwrite($f, gettext('date'."\t".'requestor\'s IP'."\t".'type'."\t".'user ID'."\t".'password'."\t".'user name'."\t".'outcome'."\t".'authority'."\t\n"));
	}
	$message = date('Y-m-d H:i:s')."\t";
	$message .= $ip."\t";
	$message .= $type."\t";
	$message .= $user."\t";
	if ($success) {
		$message .= "**********\t";
		$message .= $name."\tSuccess\t";
	} else {
		$message .= $pass."\t";
		$message .= "\tFailed\t";
	}
	if ($success) {
		$message .= substr($authority, 0, strrpos($authority,'_auth'));
	}
	if ($addl) {
		$message .= "\t".$addl;
	}
	fwrite($f, $message . "\n");
	fclose($f);
	chmod($file, 0600);
}

/**
 * Logs an attempt to log onto the back-end or as an admin user
 * Returns the rights to grant
 *
 * @param int $success the admin rights granted
 * @param string $user
 * @param string $pass
 * @return int
 */
function security_logger_adminLoginLogger($success, $user, $pass) {
	global $_zp_authority;
	switch (getOption('logger_log_type')) {
		case 'all': 
			break;
		case 'success':
			if (!$success) return false;
			break;
		case 'fail':
			if ($success) return true;
			break;
	}
	if ($success) {
		$admins = $_zp_authority->getAdministrators();
		foreach ($admins as $admin) {
			if ($admin['user'] == $user) {
				$name = $admin['name'];
				break;
			}
		}
	} else {
		$name = '';
	}
	security_logger_loginLogger($success, $user, $pass, $name, getUserIP(), gettext('Back-end'), 'zp_admin_auth');
	return $success;
}

/**
 * Logs an attempt for a guest user to log onto the site
 * Returns the "success" parameter.
 *
 * @param bool $success
 * @param string $user
 * @param string $pass
 * @param string $athority what kind of login
 * @return bool
 */
function security_logger_guestLoginLogger($success, $user, $pass, $athority) {
	switch (getOption('logger_log_type')) {
		case 'all': 
			break;
		case 'success':
			if (!$success) return false;
			break;
		case 'fail':
			if ($success) return true;
			break;
	}
	security_logger_loginLogger($success, $user, $pass, '', getUserIP(), gettext('Front-end'), $athority);
	return $success;
}

/**
 * Logs blocked accesses to Admin pages
 * @param bool $allow set to true to override the block
 * @param string $page the "return" link
 */
function security_logger_adminGate($allow, $page) {
	global $_zp_current_admin_obj;
	if (zp_loggedin()) {
		$user = $_zp_current_admin_obj->getUser();
		$name = $_zp_current_admin_obj->getName();
	} else {
		$user = $name = '';
	}
	security_logger_loginLogger(false, $user, '', $name, getUserIP(), gettext('Blocked access'), '', $page);
	return $allow;
}

/**
 * Logs blocked accesses to Managed albums
 * @param bool $allow set to true to override the block
 * @param string $page the "return" link
 */
function security_logger_adminAlbumGate($allow, $page) {
	global $_zp_current_admin_obj;
	if (zp_loggedin()) {
		$user = $_zp_current_admin_obj->getUser();
		$name = $_zp_current_admin_obj->getName();
	} else {
		$user = $name = '';
	}
	security_logger_loginLogger(false, $user, '', $name, getUserIP(), gettext('Blocked album'), '', $page);
	return $allow;
}

?>