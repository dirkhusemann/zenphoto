<?php
/**
 * Places login attempts into a security log
 * The logged data includes the ip address of the site attempting the login, the type of login, the user/user name,
 * and the success/failure. On failure, the password used in the attempt is also shown.
 * 
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = sprintf(gettext("Logs all attempts to login to the admin pages to <em>security_log.txt</em> in the %s folder."),DATA_FOLDER);
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.3.1'; 
$option_interface = new security_logger();

if (getOption('logger_log_admin')) zp_register_filter('admin_login_attempt', 'security_logger_adminLoginLogger');
if (getOption('logger_log_guests')) zp_register_filter('guest_login_attempt', 'security_logger_guestLoginLogger');

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
 * @param bool $authority kind of login
 */
function security_logger_loginLogger($success, $user, $pass, $name, $ip, $type, $authority) {
	switch (getOption('logger_log_type')) {
		case 'all': 
			break;
		case 'success':
			if (!$success) return;
			break;
		case 'fail':
			if ($success) return;
			break;
	}
	$file = dirname(dirname(dirname(__FILE__))).'/'.DATA_FOLDER . '/security_log.txt';
	$preexists = file_exists($file) && filesize($file) > 0;
	$f = fopen($file, 'a');
	if (!$preexists) { // add a header
		fwrite($f, gettext('date'."\t".'requestor\'s IP'."\t".'type'."\t".'user ID'."\t".'password'."\t".'user name'."\t".'outcome'."\t".'athority'."\n"));
	}
	$message = date('Y-m-d H:i:s')."\t";
	$message .= $ip."\t";
	if ($type == 'frontend') {
		$message .= gettext('Front-end')."\t";
	} else {
		$message .= gettext('Back-end')."\t";
	}
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
	security_logger_loginLogger($success, $user, $pass, $name, getUserIP(), 'backend', 'zp_admin_auth');
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
	security_logger_loginLogger($success, $user, $pass, '', getUserIP(), 'frontend', $athority);
	return $success;
}

?>