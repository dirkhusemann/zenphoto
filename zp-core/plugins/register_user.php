<?php
/**
 * Provides a means where visitors can register and get limited site privileges.
 *
 * Place a call on printRegistrationForm() where you want the form to appear.
 * Probably the best use is to create a new 'custom page' script just for handling these
 * user registrations. Then put a link to that script on your index page so that people
 * who wish to register will click on the link and be taken to the registration page.
 * 
 * When successfully registered, a new admin user will be created with no logon rights. An e-mail
 * will be sent to the user with a link to activate the user ID. When he clicks on that link
 * he will be taken to the registration page and the verification process will be completed. 
 * At this point the user ID rights is set to the value of the plugin default user rights option 
 * and an email is sent to the Gallery admin announcing the new registration.
 * 
 * NOTE: If you change the rights on a user pending verification you have verified the user.
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_description = gettext("Provides a means for placing a user registration form on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.6';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---register_user.php.html";
$option_interface = new register_user_options();

/**
 * Plugin option handling class
 *
 */
class register_user_options {

	function register_user_options() {
		setOptionDefault('register_user_rights', NO_RIGHTS);
		setOptionDefault('register_user_notify', 1);
		setOptionDefault('register_user_captcha', 0);
	}

	function getOptionsSupported() {
		return array(	gettext('Default user rights') => array('key' => 'register_user_rights', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array(gettext('No rights') => NO_RIGHTS, gettext('View Rights') => VIEW_ALL_RIGHTS | NO_RIGHTS),
										'desc' => gettext("Initial rights for the new user.<br />Set to <em>No rights</em> if you want to approve the user.<br />Set to <em>View Rights</em> to allow viewing the gallery once the user is verified.")),
									gettext('Notify') => array('key' => 'register_user_notify', 'type' => OPTION_TYPE_CHECKBOX, 
										'desc' => gettext('If checked, an e-mail notification is sent on new user registration.')),
									gettext('User registration page') => array('key' => 'user_registration_page', 'type' => OPTION_TYPE_CUSTOM, 
										'desc' => gettext('If this option is not empty, the visitor login form will include a link to this page. The link text will be labeled with the text provided.')),
									gettext('Use Captcha') => array('key' => 'register_user_captcha', 'type' => OPTION_TYPE_CHECKBOX, 
										'desc' => gettext('If checked, captcha validation will be required for user registration.'))
									);
	}
	function handleOption($option, $currentValue) {
		global $gallery;
		?>
		<table>
			<tr>
				<td style="margin:0; padding:0"><?php echo gettext('script'); ?></td>
				<td style="margin:0; padding:0"> 
					<select id="user_registration_page" name="user_registration_page">
						<option value=""></option>
						<?php
						$curdir = getcwd();
						$root = SERVERPATH.'/'.THEMEFOLDER.'/'.$gallery->getCurrentTheme().'/';
						chdir($root);
						$filelist = safe_glob('*.php');
						$list = array();
						foreach($filelist as $file) {
							$list[] = str_replace('.php', '', filesystemToInternal($file));
						}
						$list = array_diff($list, standardScripts());
						generateListFromArray(array(getOption('user_registration_page')), $list, false, false);
						chdir($curdir);
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td style="margin:0; padding:0"><?php echo gettext('Link text'); ?></td>
				<td style="margin:0; padding:0"><?php print_language_string_list(getOption('user_registration_text'), 'user_registration_text', false, NULL, '', true); ?></td>
			</tr>
			<tr>
				<td style="margin:0; padding:0"><?php echo gettext('Hint text'); ?></td>
				<td style="margin:0; padding:0"><?php print_language_string_list(getOption('user_registration_tip'), 'user_registration_tip', false, NULL, '', true); ?></td>
			</tr>
		</table>
		<?php
	}
}

if (!OFFSET_PATH) { // handle form post
	if (isset($_GET['verify'])) {
		$notify = '';
		$currentadmins = getAdministrators();
		$params = unserialize(pack("H*", $_GET['verify']));
		$adminuser = NULL;
		foreach ($currentadmins as $admin) {
			if ($admin['user'] == $params['user'] && $admin['email'] == $params['email']) {
				$adminuser = $admin;
				break;
			}
		}
		if (!is_null($adminuser)) {
			$rights = getOption('register_user_rights');
			saveAdmin($adminuser['user'], NULL, $admin_n = $adminuser['name'], $admin_e = $adminuser['email'], $rights, NULL);
			if (getOption('register_user_notify')) {
				zp_mail(gettext('Zenphoto Gallery registration'),
									sprintf(gettext('%1$s (%2$s) has registered for the zenphoto gallery providing an e-mail address of %3$s.'),$admin_n, $adminuser['user'], $admin_e));
			}
			$notify = 'verified';
		} else {
			$notify = 'not_verified';
		}
	}
	if (isset($_POST['register_user'])) {
		$notify = '';
		if (getOption('register_user_captcha')) {
			if (isset($_POST['code'])) {
				$code = sanitize($_POST['code'], 3);
				$code_ok = sanitize($_POST['code_h'], 3);
			} else {
				$code = '';
				$code_ok = '';
			}
			if (!$_zp_captcha->checkCaptcha($code, $code_ok)) {
				$notify = 'invalidcaptcha';
			}
		}
		$admin_n = trim($_POST['admin_name']);
		if (empty($admin_n)) {
			$notify = 'incomplete';
		}
		$admin_e = trim($_POST['admin_email']);
		if (!is_valid_email_zp($admin_e)) {
			$notify = 'invalidemail';
		}
		$pass = trim($_POST['adminpass']);
		$user = trim($_POST['adminuser']);
		if (!empty($user) && !(empty($admin_n)) && !empty($admin_e)) {
			if ($pass == trim($_POST['adminpass_2'])) {
				$currentadmins = getAdministrators();
				foreach ($currentadmins as $admin) {
					if ($admin['user'] == $user) {
						$notify = 'exists';
						break;
					}
				}
				if (empty($notify)) {
					$notify = saveAdmin($user, $pass, $admin_n, $admin_e, 0, NULL, '', '');
					if (empty($notify)) {
						$link = FULLWEBPATH.'/index.php?p='.substr($_zp_gallery_page,0, -4).'&verify='.bin2hex(serialize(array('user'=>$user,'email'=>$admin_e)));
						$message = sprintf(gettext('You have received this email because you registered on the site. To complete your registration visit %s.'), $link);
						zp_mail(gettext('Registration confirmation'), $message, NULL, NULL, array($user=>$admin_e));
						$notify = 'accepted';
					}
				}
			} else {
				$notify = 'mismatch';
			}
		} else {
			$notify = 'incomplete';
		}
	}
}

/**
 * places the user registration form
 *
 * @param string $thanks the message shown on successful registration
 */
function printRegistrationForm($thanks=NULL) {
	global $notify, $admin_e, $admin_n, $user;
	if (zp_loggedin()) {
		if (isset($_GET['userlog']) && $_GET['userlog'] == 1) {
			echo '<meta HTTP-EQUIV="REFRESH" content="0; url=/">';
		} else {
			echo '<div class="errorbox" id="fade-message">';
			echo  '<h2>'.gettext("you are already logged in.").'</h2>';
			echo '</div>';
		}
		return;
	}
	if (isset($notify)) {
		if ($notify == 'verified' || $notify == 'accepted') {
			?>
			<div class="Messagebox" id="fade-message">
				<p>
				<?php
				if ($notify == 'verified') {
					if (is_null($thanks)) $thanks = gettext("Thank you for registering.");
					echo $thanks;
				} else {
					echo gettext('Your registration information has been accepted. An email has been sent to you to verify your email address.');
				}
				?>
				</p>
			</div>
			<?php
			if (function_exists('printUserLogout') && $notify == 'verified') {
				?>
				<p><?php echo gettext('You may now log onto the site.'); ?></p>
				<?php
				printPasswordForm('', false, true);
			}
			$notify = 'success';
		} else {
			echo '<div class="errorbox" id="fade-message">';
			echo  '<h2>'.gettext("Registration failed.").'</h2>';
			echo '<p>';
			switch ($notify) {
				case 'exists':
					echo gettext('The user ID you chose is already in use.');
					break;
				case 'mismatch':
					echo gettext('Your passwords did not match.');
					break;
				case 'incomplete':
					echo gettext('You have not filled in all the fields.');
					break;
				case 'notverified':
					echo gettext('Invalid verification link.');
					break;
				case 'invalidemail':
					echo gettext('Enter a valid email address.');
					break;
				case 'invalidcaptcha':
					echo gettext('The captcha you entered was not correct.');
					break;
				default:
					echo $notify;
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