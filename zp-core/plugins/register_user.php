<?php
/**
 * Provides a means where visitors can register and get limited site priviledges.
 *
 * Place a call on printReistrationForm() where you want the form to appear.
 *
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */

$plugin_description = gettext("Provides a means for placing a user logout link on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---register_user.php.html";
$option_interface = new register_user_options();

/**
 * Plugin option handling class
 *
 */
class register_user_options {

	function register_user_options() {
		setOptionDefault('register_user_rights', NO_RIGHTS);
	}

	function getOptionsSupported() {
		return array(	gettext('Default user rights') => array('key' => 'register_user_rights', 'type' => 4,
										'buttons' => array(gettext('No rights') => NO_RIGHTS, gettext('View Rights') => VIEWALL_RIGHTS | NO_RIGHTS),
										'desc' => gettext("Initial rights for the new user.<br />Set to <em>No rights</em> if you want to approve the user.<br />Set to <em>View Rights</em> to allow viewing the gallery.")),
		);
	}
	function handleOption($option, $currentValue) {
	}
}

if (!OFFSET_PATH) { // handle form post
	if (isset($_GET['userlog']) && $_GET['userlog']) {
		header("Location: " . FULLWEBPATH . "/index.php");
		exit();
	}
	if (isset($_POST['register_user'])) {
		$pass = trim($_POST['adminpass']);
		$user = trim($_POST['adminuser']);
		$admin_n = trim($_POST['admin_name']);
		$admin_e = trim($_POST['admin_email']);
		if (!empty($user) && !(empty($admin_n)) && !empty($admin_e)) {
			if ($pass == trim($_POST['adminpass_2'])) {

				if (empty($pass)) {
					$pwd = null;
				} else {
					$pwd = md5($_POST['adminuser'] . $pass);
				}
				$notify = '';
				$currentadmins = getAdministrators();
				foreach ($currentadmins as $admin) {
					if ($admin['user'] == $user) {
						$notify = 'exists';
						break;
					}
				}
				if (empty($notify)) {
					$rights = getOption('register_user_rights');
					saveAdmin($user, $pwd, $admin_n, $admin_e, $rights, NULL);
					zp_mail(gettext('Zenphoto allery registration'),
						sprintf(gettext('%1$s has registered for the zenphoto gallery providing an e-mail address of %2$s.'),$admin_n, $admin_e));
					$notify = 'success';
				}
			} else {
				$notify = 'mismatch';
			}
		} else {
			$notify = 'incomplete';
		}
	}
}

function printReistrationForm($thanks=NULL) {
	global $notify, $admin_e, $admin_n, $user;
	if (zp_loggedin()) {
		echo '<div class="errorbox" id="fade-message">';
		echo  '<h2>'.gettext("you are already logged in.").'</h2>';
		echo '</div>';
		return;
	}
	if (isset($notify)) {
		if ($notify == 'success') {
			if (is_null($thanks)) $thanks = gettext("Thank you for registering.");
			echo '<div class="Messagebox" id="fade-message">';
			echo  '<h2>'.$thanks.'</h2>';
			echo '</div>';
			if (function_exists('printUserLogout')) {
				?>
				<p><?php echo gettext('You may now log onto the site.'); ?></p>
				<?php
				printPasswordForm('', false, true);
			}
		} else {
			echo '<div class="errorbox" id="fade-message">';
			echo  '<h2>'.gettext("Registration failed.").'</h2>';
			echo '<p>';
			switch ($notify) {
				case 'exists':
					echo(gettext('The user ID you chose is already in use.'));
					break;
				case 'mismatch':
					echo(gettext('Your passwords did not match.'));
					break;
				case 'incomplete':
					echo(gettext('You have not filled in all the fields.'));
					break;
			}
			echo '</p>';
			echo '</div>';
		}
	}
	if ($notify != 'success') {
		require_once(dirname(__FILE__).'/'.substr(basename(__FILE__), 0, -4).'/'.'register_user_form.php');
	}
}
?>