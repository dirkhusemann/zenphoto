<?php
/**
 * Logs admin login attempts
 * This is an example filter for 'admin_login_attempt' 
 *  
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("Logs all attempts to login to the admin pages to <em>zenphoto_security_log.txt</em> in the root folder.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-admin_login.php.html";
$option_interface = new admin_login();

register_filter('admin_login_attempt', 'adminLoginLogger', 3);
if (getOption('logger_log_guests')) register_filter('guest_login_attempt', 'guestLoginLogger', 3);

/**
 * Option handler class
 *
 */
class admin_login {
	/**
	 * class instantiation function
	 *
	 * @return admin_login
	 */
	function admin_login() {
		setOptionDefault('logger_log_guests', 1);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Clear log') => array('key' => 'logger_clear_log', 'type' => OPTION_TYPE_CUSTOM,
										'desc' => gettext('Resets the log to <em>empty</em>.')),
									gettext('Log guest users') => array('key' => 'logger_log_guests', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext('If checked, guest user login attempts will be logged.'))
									);
	}

	/**
	 * Custom opton handler--creates the clear ratings button
	 *
	 * @param string $option
	 * @param string $currentValue
	 */
	function handleOption($option, $currentValue) {
		if($option=="logger_clear_log") {
			?>
			<div class='buttons'>
				<a href="?logger_clear_log=1&amp;tab=plugin" title="<?php echo gettext("Clear log"); ?>">
					<img src='images/edit-delete.png' alt='' />
					<?php echo gettext("Clear log"); ?>
				</a>
			</div>
			<?php
		}
	}

}

/**
 * Does the log handling
 *
 * @param int $success
 * @param string $user
 * @param string $pass
 * @param string $name
 * @param string $type
 */
function loginLogger($success, $user, $pass, $name, $type) {
	$f = fopen(dirname(dirname(dirname(__FILE__))) . '/zenphoto_security_log.txt', 'a');
	$message = date('Y-m-d H:i:s')."\t";
	$message .= $type."\t";
	$message .= $user."\t";
	$message .= $pass."\t";
	if ($success) {
		$message .= $name."\tSuccess";
	} else {
		$message .= "\tFailed";
	}
	fwrite($f, $message . "\n");
	fclose($f);
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
function adminLoginLogger($success, $user, $pass) {
	if ($success) {
		$admins = getAdministrators();
		foreach ($admins as $admin) {
			if ($admin['user'] == $user) {
				$name = $admin['name'];
				break;
			}
		}
	} else {
		$name = '';
	}
	loginLogger($success, $user, $pass, $name, '[admin]');
	return $success;
}

/**
 * Logs an attempt for a guest user to log onto the site
 * Returns the "success" parameter.
 *
 * @param bool $success
 * @param string $user
 * @param string $pass
 * @return bool
 */
function guestLoginLogger($success, $user, $pass) {
	loginLogger($success, $user, $pass, '', gettext('[guest]'));
	return $success;
}

if (isset($_GET['logger_clear_log']) && $_GET['logger_clear_log']) {
	require_once(dirname(dirname(__FILE__)).'/auth_zp.php');
	if (zp_loggedin(ADMIN_RIGHTS)) {
		@unlink(dirname(dirname(dirname(__FILE__))) . '/zenphoto_security_log.txt');
	}
}

?>