<?php
/**
 * Logs admin login attempts
 * This is an example filter for 'admin_login_attempt'
 * 
 * Note: Your server must be configured so that your php scripts run as the owner of the files 
 * they create otherwise this script will fail due when file security on the server blocks it
 * from writing to the log file.
 *  
 * @author Stephen Billard (sbillard)
 * @version 1.0.1
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = sprintf(gettext("Logs all attempts to login to the admin pages to <em>security_log.txt</em> in the %s folder."),DATA_FOLDER);
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.1';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---filter-admin_login.php.html";
$option_interface = new admin_login();

if (getOption('logger_log_admin')) zp_register_filter('admin_login_attempt', 'adminLoginLogger');
zp_register_filter('admin_tabs', 'filter_login_admin_tabs');
if (getOption('logger_log_guests')) zp_register_filter('guest_login_attempt', 'guestLoginLogger');

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
		setOptionDefault('logger_log_admin', 1);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Record logon attempts of') => array('key' => 'logger_log_allowed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
										'checkboxes' => array(gettext('Administrators') => 'logger_log_admin', gettext('Guests') => 'logger_log_guests'),
										'desc' => gettext('If checked login attempts will be logged.'))
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
 * @param string $ip
 * @param string $type
 */
function loginLogger($success, $user, $pass, $name, $ip, $type) {
	$file = dirname(dirname(dirname(__FILE__))).'/'.DATA_FOLDER . '/security_log.txt';
	$preexists = file_exists($file) && filesize($file) > 0;
	$f = fopen($file, 'a');
	if (!$preexists) { // add a header
		fwrite($f, 'date'."\t".'requestor\'s IP'."\t".'type'."\t".'user ID'."\t".'password'."\t".'user name'."\t".'outcome'."\n");
	}
	$message = date('Y-m-d H:i:s')."\t";
	$message .= $ip."\t";
	$message .= $type."\t";
	$message .= $user."\t";
	if ($success) {
		$message .= "**********\t";
		$message .= $name."\tSuccess";
	} else {
		$message .= $pass."\t";
		$message .= "\tFailed";
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
	loginLogger($success, $user, $pass, $name, sanitize($_SERVER['REMOTE_ADDR'], 0), gettext('admin'));
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
	loginLogger($success, $user, $pass, '', sanitize($_SERVER['REMOTE_ADDR'], 0), gettext('guest'));
	return $success;
}

function filter_login_admin_tabs($tabs, $current) {
	if ((zp_loggedin(ADMIN_RIGHTS))) {
		$tabs['logs'] = array(	'text'=>gettext("Logs"),
														'link'=>WEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/filter-login/view_log_tab.php?page=logs',
														'subtabs'=>NULL);
	}
	return $tabs;
}

?>