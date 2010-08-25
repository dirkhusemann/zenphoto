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
$plugin_version = '1.3.1';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---register_user.php.html";
$option_interface = new register_user_options();

require_once(dirname(dirname(__FILE__)).'/admin-functions.php');

/**
 * Plugin option handling class
 *
 */
class register_user_options {

	function register_user_options() {
		setOptionDefault('register_user_notify', 1);
		gettext($str = 'You have received this email because you registered on the site. To complete your registration visit %s.');
		setOptionDefault('register_user_text', getAllTranslations($str));
		gettext($str = 'Click here to register for this site.');
		setOptionDefault('register_user_page_tip', getAllTranslations($str));
		gettext($str = 'Register');
		setOptionDefault('register_user_page_link', getAllTranslations($str));
		setOptionDefault('register_user_captcha', 0);
		setOptionDefault('register_user_email_is_id', 1);
		setOptionDefault('register_user_page_page', 'register');
	}

	function getOptionsSupported() {
		global $_zp_authority;
		$options = array(	gettext('Notify') => array('key' => 'register_user_notify', 'type' => OPTION_TYPE_CHECKBOX,
												'desc' => gettext('If checked, an e-mail will be sent to the gallery admin when a new user has verified his registration.')),
											gettext('Email ID') => array('key' => 'register_user_email_is_id', 'type' => OPTION_TYPE_CHECKBOX,
												'desc' => gettext('If checked, The user\'s e-mail address will be used as his User ID.')),
											gettext('Email notification text') => array('key' => 'register_user_text', 'type' => OPTION_TYPE_TEXTAREA,
												'desc' => gettext('Text for the body of the email sent to the user. <p class="notebox"><strong>Note:</strong> You must include <code>%s</code> in your message where you wish the registration completion link to appear.</p>')),
											gettext('User registration page') => array('key' => 'register_user_page', 'type' => OPTION_TYPE_CUSTOM,
												'desc' => gettext('If this option is set, the visitor login form will include a link to this page. The link text will be labeled with the text provided.')),
											gettext('CAPTCHA') => array('key' => 'register_user_captcha', 'type' => OPTION_TYPE_CHECKBOX,
												'desc' => gettext('If checked, CAPTCHA validation will be required for user registration.'))
											);
		$admins = $_zp_authority->getAdministrators();
		$ordered = array();
		$groups = array();
		$adminordered = array();
		asort($ordered);
		if (function_exists('user_groups_admin_tabs')) {
			$nullselection = '';
			$defaultrights = ALL_RIGHTS;
			foreach ($admins as $key=>$admin) {
				if (!$admin['valid']) {
					$ordered[$admin['user']] = $admin['user'];
					if ($admin['rights'] < $defaultrights && $admin['rights'] >= NO_RIGHTS) {
						$nullselection = $admin['user'];
						$defaultrights = $admin['rights'];
					}
				}
			}
			if (!empty($nullselection)) {
				if (is_numeric(getOption('register_user_rights'))) {
					setOption('register_user_rights', $nullselection);
				} else {
					setOptionDefault('register_user_rights', $nullselection);
				}
			}
			$options[gettext('Default user group')] =  array('key' => 'register_user_rights', 'type' => OPTION_TYPE_SELECTOR,
										'selections' => $ordered,
										'desc' => gettext("Initial group assignment for the new user."));
		} else {
				if (is_numeric(getOption('register_user_rights'))) {
					setOptionDefault('register_user_rights', NO_RIGHTS);
				} else {
					setOption('register_user_rights', NO_RIGHTS);
				}
			$options[gettext('Default user rights')] = array('key' => 'register_user_rights', 'type' => OPTION_TYPE_RADIO,
										'buttons' => array(gettext('No rights') => NO_RIGHTS, gettext('View Rights') => VIEW_ALL_RIGHTS | NO_RIGHTS),
										'desc' => gettext("Initial rights for the new user.<br />Set to <em>No rights</em> if you want to approve the user.<br />Set to <em>View Rights</em> to allow viewing the gallery once the user is verified."));
		}
		return $options;
	}
	function handleOption($option, $currentValue) {
		global $gallery;
		?>
		<table>
			<tr>
				<td style="margin:0; padding:0"><?php echo gettext('script'); ?></td>
				<td style="margin:0; padding:0">
					<input type="hidden" name="_ZP_CUSTOM_selector-register_user_page_page" value="0" />
					<select id="register_user_page_page" name="register_user_page_page">
						<option value="" style="background-color:LightGray"><?php echo gettext('*no page selected'); ?></option>
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
						generateListFromArray(array(getOption('register_user_page_page')), $list, false, false);
						chdir($curdir);
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td style="margin:0; padding:0"><?php echo gettext('Link text'); ?></td>
				<td style="margin:0; padding:0">
					<input type="hidden" name="_ZP_CUSTOM_text-register_user_page_link" value="0" />
					<?php print_language_string_list(getOption('register_user_page_link'), 'register_user_page_link', false, NULL, '', true); ?>
				</td>
			</tr>
			<tr>
				<td style="margin:0; padding:0"><?php echo gettext('Hint text'); ?></td>
				<td style="margin:0; padding:0">
					<input type="hidden" name="_ZP_CUSTOM_text-register_user_page_tip" value="0" />
					<?php print_language_string_list(getOption('register_user_page_tip'), 'register_user_page_tip', false, NULL, '', true); ?>
				</td>
			</tr>
		</table>
		<?php
	}
}

if (!OFFSET_PATH) { // handle form post
	if (isset($_GET['verify'])) {
		$notify = '';
		$currentadmins = $_zp_authority->getAdministrators();
		$params = unserialize(pack("H*", $_GET['verify']));
		$adminuser = NULL;
		$rights = getOption('register_user_rights');
		$group = NULL;
		foreach ($currentadmins as $admin) {
			if ($admin['user'] == $params['user'] && $admin['email'] == $params['email']) {
				$adminuser = $admin;
			}
			if ($admin['user'] == $rights) {
				if ($admin['name'] != 'template') $group = $rights;;
				$rights = $admin['rights'];
			}
		}
		if (is_null($adminuser)) {
			$notify = 'not_verified';	// User ID no longer exists
		} else {
			$userobj = $_zp_authority->newAdministrator($adminuser['user']);
			$userobj->setName($admin_n = $adminuser['name']);
			$userobj->setEmail($admin_e = $adminuser['email']);
			$userobj->setRights($rights | NO_RIGHTS);
			$userobj->setGroup($group);
			if (!empty($group)) {
				$membergroup = $_zp_authority->newAdministrator($group, 0);
				$userobj->setObjects(populateManagedObjectsList(NULL,$membergroup->getID()));
			}
			zp_apply_filter('register_user_verified', $userobj);
			$userobj->save();
			$notify = false;
			if (getOption('register_user_notify')) {
				$notify = zp_mail(gettext('Zenphoto Gallery registration'),
									sprintf(gettext('%1$s (%2$s) has registered for the zenphoto gallery providing an e-mail address of %3$s.'),$userobj->getName(), $adminuser['user'], $admin_e));
			}
			if (empty($notify)) $notify = 'verified';
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
		if (isset($_POST['admin_email'])) {
			$admin_e = trim($_POST['admin_email']);
		} else {
			$admin_e = trim($_POST['adminuser']);
		}
		if (!is_valid_email_zp($admin_e)) {
			$notify = 'invalidemail';
		}

		$pass = trim($_POST['adminpass']);
		$user = trim($_POST['adminuser']);
		if (!empty($user) && !(empty($admin_n)) && !empty($admin_e)) {
			if ($pass == trim($_POST['adminpass_2'])) {
				$currentadmins = $_zp_authority->getAdministrators();
				foreach ($currentadmins as $admin) {
					if ($admin['user'] == $user) {
						$notify = 'exists';
					}
				}
				if (empty($notify)) {
					$notify = $_zp_authority->validatePassword($pass);	//	test for valid password
					if (empty($notify)) {
						$userobj = $_zp_authority->newAdministrator($user);
						$userobj->setPass($pass);
						$userobj->setName($admin_n);
						$userobj->setEmail($admin_e);
						$userobj->setRights(0);
						$userobj->setObjects(NULL);
						$userobj->setGroup('');
						$userobj->setCustomData('');
						zp_apply_filter('register_user_registered', $userobj);
						if ($userobj->transient) {
							if (empty($notify)) {
								$notify = 'filter';
							}
						} else {
							$userobj->save();
							$link = FULLWEBPATH.'/index.php?p='.substr($_zp_gallery_page,0, -4).'&verify='.bin2hex(serialize(array('user'=>$user,'email'=>$admin_e)));
							$message = sprintf(get_language_string(getOption('register_user_text')), $link);
							$notify = zp_mail(get_language_string(gettext('Registration confirmation')), $message, array($user=>$admin_e));
							if (empty($notify)) $notify = 'accepted';
						}
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
	global $notify, $admin_e, $admin_n, $user, $_zp_authority;;
	if (zp_loggedin()) {
		if (isset($_GET['userlog']) && $_GET['userlog'] == 1) {
			echo '<meta http-equiv="refresh" content="2; url='.WEBPATH.'/">';
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
			if (function_exists('printUserLogin_out') && $notify == 'verified') {
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
					echo gettext('The CAPTCHA you entered was not correct.');
					break;
				case 'not_verified':
					echo gettext('Your registration request could not be completed.');
					break;
				case 'filter':
					echo gettext('Your registration attempt failed a <code>register_user_registered</code> filter check.');
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