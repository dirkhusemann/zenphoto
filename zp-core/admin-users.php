<?php
/**
 * provides the Options tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!is_null(getOption('admin_reset_date'))) {
	if (!$_zp_loggedin) { // prevent nefarious access to this page.
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
		exit();
	}
}

$gallery = new Gallery();
$_GET['page'] = 'users'; // must be a user with no options rights
$_current_tab = sanitize($_GET['page'],3);

/* handle posts */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	$themeswitch = false;
	if ($action == 'deleteadmin') {
		$id = sanitize_numeric($_GET['adminuser']);
		deleteAdmin(array('id'=>$id));
		$sql = "DELETE FROM ".prefix('admintoalbum')." WHERE `adminid`=$id";
		query($sql);
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-users.php?page=users&deleted");
		exit();
	} else if ($action == 'saveoptions') {
		$notify = '';
		$returntab = '';

		/*** admin options ***/
		if (isset($_POST['saveadminoptions'])) {
			$nouser = true;
			$newuser = false;
			for ($i = 0; $i < $_POST['totaladmins']; $i++) {
				$pass = trim($_POST[$i.'-adminpass']);
				$user = trim($_POST[$i.'-adminuser']);
				if (empty($user) && !empty($pass)) {
					$notify = '?mismatch=nothing';
				}
				if (!empty($user)) {
					$nouser = false;
					if ($pass == trim($_POST[$i.'-adminpass_2'])) {
						$admin_n = trim($_POST[$i.'-admin_name']);
						$admin_e = trim($_POST[$i.'-admin_email']);
						$rights = processRights($i);
						if (isset($_POST['alter_enabled'])) {
							$albums = processManagedAlbums($i);
						} else {
							$rights = NULL;
							$albums = NULL;
						}
						if (empty($pass)) {
							$pass = NULL;
						}
						$userobj = new Administrator(''); // get a transient object
						$userobj->setuser($user);
						$userobj->setPass(NULL);
						$userobj->setName($admin_n);
						$userobj->setEmail($admin_e);
						$userobj->setRights($rights);
						$userobj->setAlbums($albums);
						zp_apply_filter('save_admin_custom_data', '', $userobj, $i);
						$msg = saveAdmin($user, $pass, $userobj->getName(), $userobj->getEmail(), $userobj->getRights(), $userobj->getAlbums(), $userobj->getCustomData(), $userobj->getGroup());		
						if (empty($msg)) {
							if (isset($_POST[$i.'-newuser'])) {
								$newuser = $user;
							}
							if ($i == 0) {
								setOption('admin_reset_date', '1');
							}
						} else {
							$notify = '?mismatch=format&error='.urlencode($msg);
						}
					} else {
						$notify = '?mismatch=password';
					}
				}
			}
			if ($nouser) {
				$notify = '?mismatch=nothing';
			}
			$returntab = "&page=users";
			if (!empty($newuser)) {
				$returntab .= '&_show-'.$newuser;
				unset($_POST['_show-']);
			}
		}
		
		/*** custom options ***/
		$returntab = processCustomOptionSave($returntab);

		if (empty($notify)) $notify = '?saved';
		header("Location: " . $notify . $returntab);
		exit();

	}

}
printAdminHeader();
?>
<script type="text/javascript" src="js/farbtastic.js"></script>
<link rel="stylesheet" href="js/farbtastic.css" type="text/css" />
<?php
$_zp_null_account = (($_zp_loggedin == ADMIN_RIGHTS) || $_zp_reset_admin);
$subtab = getSubtabs($_current_tab, 'users');
?>
</head>
<body>
<?php printLogoAndLinks(); ?>
<div id="main">
<?php printTabs($_current_tab); ?>
<div id="content">
<?php 
if ($_zp_null_account) {
	echo "<div class=\"errorbox space\">";
	echo "<h2>".gettext("Password reset request.<br />You may now set admin usernames and passwords.")."</h2>";
	echo "</div>";
}

/* Page code */
?>
<div id="container">
<?php
	if (isset($_GET['saved'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>".gettext("Saved")."</h2>";
		echo '</div>';
	}
?>
<?php
printSubtabs($_current_tab, 'users');
?>
<div id="tab_admin" class="tabbox">
<?php
	if ($_zp_loggedin & ADMIN_RIGHTS) {
		if ($_zp_null_account && isset($_zp_reset_admin)) {
			$admins = array($_zp_reset_admin['user'] => $_zp_reset_admin);
			$alterrights = ' disabled="disabled"';
			setOption('admin_reset_date', $_zp_request_date); // reset the date in case of no save
		} else {
			$admins = getAdministrators();
			if (empty($admins) || $_zp_null_account) {
				$rights = ALL_RIGHTS;
				$groupname = 'administrators';
			} else {
				$rights = DEFAULT_RIGHTS;
				$groupname = 'default';
			}
			$admins [''] = array('id' => -1, 'user' => '', 'pass' => '', 'name' => '', 'email' => '', 'rights' => $rights, 'custom_data' => NULL, 'valid'=>1, 'group' => $groupname);
			$alterrights = '';
		}
	} else {
		$alterrights = ' disabled="disabled"';
		$admins = array($_zp_current_admin['user'] => $_zp_current_admin);
	}
	if (isset($_GET['deleted'])) {
		echo '<div class="messagebox" id="fade-message">';
		echo  "<h2>Deleted</h2>";
		echo '</div>';
	}
	if (isset($_GET['tag_parse_error'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your Allowed tags change did not parse successfully.")."</h2>";
		echo '</div>';
	}
	if (isset($_GET['mismatch'])) {
		echo '<div class="errorbox" id="fade-message">';
		switch ($_GET['mismatch']) {
			case 'gallery':
			case 'search':
				echo  "<h2>".sprintf(gettext("Your %s passwords were empty or did not match"), $_GET['mismatch'])."</h2>";
				break;
			case 'user_gallery':
				echo  "<h2>".gettext("You must supply a password for the Gallery guest user")."</h2>";
				break;
			case 'user_search':
				echo  "<h2>".gettext("You must supply a password for the Search guest user")."</h2>";
				break;
			case 'mismatch':
				echo  "<h2>".gettext('You must supply a password')."</h2>";
				break;
			case 'nothing':
				echo  "<h2>".gettext('User name not provided')."</h2>";
				break;
			case 'format':
				echo '<h2>'.urldecode(sanitize($_GET['error'],2)).'</h2>';
				break;
			default:
				echo  "<h2>".gettext('Your passwords did not match')."</h2>";
				break;
		}
		echo '</div>';
	}
	if (isset($_GET['local_failed'])) {
		$locale = sanitize($_GET['local_failed']);
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".
					sprintf(gettext("<em>%s</em> is not available."),$_zp_languages[$locale]).
					' '.sprintf(gettext("The locale %s is not supported on your server."),$locale).
					'<br />'.gettext('See the troubleshooting guide on zenphoto.org for details.').
					"</h2>";
		echo '</div>';
	}
	if (isset($_GET['badurl'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your Website URL is not valid")."</h2>";
		echo '</div>';
	}
	
	
	
?> 
<form action="?action=saveoptions<?php if (isset($_zp_ticket)) echo '&amp;ticket='.$_zp_ticket.'&amp;user='.$post_user; ?>" method="post" autocomplete="off">
<input type="hidden" name="saveadminoptions" value="yes" />
<?php			
if (empty($alterrights)) {
	?>
	<input type="hidden" name="alter_enabled" value="1" />
	<?php 
}
?>
<p class="buttons">
					<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
					<button type="reset" value="<?php echo gettext('reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</p>
					<br clear="all" /><br />
<table class="bordered"> <!-- main table -->

	<tr>
		<th>
			<span style="font-weight: normal">
			<a href="javascript:setShow(1);toggleExtraInfo('','user',true);"><?php echo gettext('Expand all');?></a>
			| 
			<a href="javascript:setShow(0);toggleExtraInfo('','user',false);"><?php echo gettext('Collapse all');?></a>
			</span>
		</th>
	</tr>
	<?php
	$id = 0;
	$albumlist = array();
	foreach ($gallery->getAlbums() as $folder) {
		if (hasDynamicAlbumSuffix($folder)) {
			$name = substr($folder, 0, -4); // Strip the .'.alb' suffix
		} else {
			$name = $folder;
		}
		$albumlist[$name] = $folder;
	}
	if ($_zp_null_account) {
		$current = true;
	} else {
		foreach ($_GET as $param=>$value) {
			if (strpos($param, '_show-') === 0) {
				$current = false;
				break;
			}
			$current = true;
		}
	}
	$background = '';
	$showlist = array();
	foreach($admins as $user) {
		if ($user['valid']) {
			$local_alterrights = $alterrights;
			$userid = $user['user'];
			$showlist[] = '#_show-'.$userid;
			$userobj = new Administrator($userid);
			if (empty($userid)) {
				$userobj->setGroup($user['group']);
				$userobj->setRights($user['rights']);
				$userobj->setValid(1);
			}
			$groupname = $userobj->getGroup();
			if ($pending = $userobj->getRights() == 0) {
				$master = '(<em>'.gettext('pending verification').'</em>)';
			} else {
				$master = '&nbsp;';
			}
			$ismaster = false;
			if ($id == 0 && !$_zp_null_account) {
				if ($_zp_loggedin & ADMIN_RIGHTS) {
					$master = "(<em>".gettext("Master")."</em>)";
					$userobj->setRights($userobj->getRights() | ADMIN_RIGHTS);
					$ismaster = true;
				}
			}
			if (isset($_GET['_show-'.$userid])) {
				$current = true;
			}
			if ($background) {
				$background = "";
			} else {
				$background = "background-color:#ECF1F2;";
			}
			
			?>
			<!-- apply alterrights filter -->
			<?php $local_alterrights = zp_apply_filter('admin_alterrights', $local_alterrights, $userobj); ?>
			<!-- apply admin_custom_data filter -->
			<?php $custom_row = zp_apply_filter('edit_admin_custom_data', '', $userobj, $id, $background, $current); ?>
			<!-- finished with filters -->
			<tr>
				<td colspan="2" style="margin: 0pt; padding: 0pt;">
				<!-- individual admin table -->
				<input type="hidden" name="_show-<?php echo $userid; ?>" id="_show-<?php echo $userid; ?>" value="<?php echo ($current);?>" /> 
				<table class="bordered" style="border: 0" id='user-<?php echo $id;?>'>
				<tr>
					<td width="20%" style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" valign="top">
					<?php
					if (empty($userid)) {
						$displaytitle = gettext("Show details");
						$hidetitle = gettext("Hide details");
					} else {
						$displaytitle = sprintf(gettext('Show details for user %s'),$userid); 
						$hidetitle = sprintf(gettext('Hide details for user %s'),$userid); 
					}
					?>
						<span <?php if ($current) echo 'style="display:none;"'; ?> class="userextrashow">
							<a href="javascript:$('#_show-<?php echo $userid; ?>').val(1);toggleExtraInfo('<?php echo $id;?>','user',true);" title="<?php echo $displaytitle; ?>" >
								<?php
								if (empty($userid)) {
									?>
									<input type="hidden" name="<?php echo $id ?>-newuser" value="1" />
									<em><?php echo gettext("Add New User"); ?></em>
									<?php
								} else {
									?>
									<input type="hidden" name="<?php echo $id ?>-adminuser" value="<?php echo $userid ?>" />
									<?php
									echo '<strong>'.$userid.'</strong>'; 
								}
								?>
							</a>
						</span>
						<span <?php if ($current) echo 'style="display:block;"'; else echo 'style="display:none;"'; ?> class="userextrahide">
							<a href="javascript:$('#_show-<?php echo $userid; ?>').val(0);toggleExtraInfo('<?php echo $id;?>','user',false);" title="<?php echo $hidetitle; ?>">
								<?php 
								if (empty($userid)) {
									echo '<em>'.gettext("Add New User").'</em>';
								} else {
									echo '<strong>'.$userid.'</strong>';
								}
								?>
							</a>
						</span>
					</td>
					<td width="345" style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" valign="top" >
					<?php 
					if (empty($userid)) {
							?>
							<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminuser" value=""
								onclick="toggleExtraInfo('<?php echo $id;?>','user',true);" />
							<?php
						} else {
							echo $master;
						}
						if ($pending) {
						?>
							<input type="checkbox" name="<?php echo $id ?>-confirmed" value="<?php echo NO_RIGHTS; echo $alterrights; ?>" />
							<?php echo gettext("Authenticate user"); ?>
							<?php
						} else {
							?>
							<input type = "hidden" name="<?php echo $id ?>-confirmed"	value="<?php echo NO_RIGHTS; ?>" />
							<?php 
						}
			 			?>
		 			</td>
					<td style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" valign="top" >
						<?php 
						if(!empty($userid) && count($admins) > 2) { 
							$msg = gettext('Are you sure you want to delete this user?');
							if ($id == 0) {
								$msg .= ' '.gettext('This is the master user account. If you delete it another user will be promoted to master user.');
							}
						?>
						<a href="javascript:if(confirm(<?php echo "'".$msg."'"; ?>)) { window.location='?action=deleteadmin&adminuser=<?php echo $user['id']; ?>'; }"
							title="<?php echo gettext('Delete this user.'); ?>" style="color: #c33;"> <img
							src="images/fail.png" style="border: 0px;" alt="Delete" /></a> 
						<?php
						}
						?>
						&nbsp;
						</td>
					</tr>
			<tr <?php if (!$current) echo 'style="display:none;"'; ?> class="userextrainfo">
				<td width="20%" <?php if (!empty($background)) echo " style=\"$background\""; ?>>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Password:"); ?>
					<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
				</td>
				<td  width="320em" <?php if (!empty($background)) echo " style=\"$background\""; ?>><?php $x = $userobj->getPass(); if (!empty($x)) { $x = '          '; } ?>
					<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminpass"
						value="<?php echo $x; ?>" />
					<br />
					<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminpass_2"
						value="<?php echo $x; ?>" />
					<?php
					$msg = passwordNote();
					if (!empty($msg)) {
						?>
						<p>
						<?php echo $msg; ?>
						</p>
						<?php
					}
					?>
				</td>
				<td <?php if (!empty($background)) echo " style=\"$background\""; ?>>
					<?php printAdminRightsTable($id, $background, $local_alterrights, $userobj->getRights()); ?>	
				</td>
			</tr>
			<tr <?php if (!$current) echo 'style="display:none;"'; ?> class="userextrainfo">
				<td width="20%" <?php if (!empty($background)) echo " style=\"$background\""; ?> valign="top">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Full name:"); ?> <br />
					<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("email:"); ?>
				</td>
				<td  width="320" <?php if (!empty($background)) echo " style=\"$background\""; ?>  valign="top">
					<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-admin_name"
						value="<?php echo $userobj->getName();?>" />
					<br />
					<br />
					<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-admin_email"
						value="<?php echo $userobj->getEmail();?>" />
				</td>
				<td <?php if (!empty($background)) echo " style=\"$background\""; ?>>
						<?php
						if ($_zp_loggedin & (MANAGE_ALL_ALBUM_RIGHTS | ADMIN_RIGHTS)) {
							$album_alter_rights = $local_alterrights;
						} else {
							$album_alter_rights = ' disabled="disabled"';
						}
						if ($current && $ismaster) {
							echo '<p>'.gettext("The <em>master</em> account has full rights to all albums.").'</p>';
						} else {
							printManagedAlbums($albumlist, $album_alter_rights, $user['id'], $id);
						}
						?>
					<p>
						<?php
							if (!$ismaster) {
								if (empty($album_alter_rights)) {
									echo gettext("Select one or more albums for the administrator to manage.").' ';
									echo gettext("Administrators with <em>User admin</em> or <em>Manage all albums</em> rights can manage all albums. All others may manage only those that are selected.");
								} else {
									echo gettext("You may manage these albums subject to the above rights.");
								}
							}
						?>
					</p>
				</td>
			</tr>
			<?php echo $custom_row; ?>
		
		</table> <!-- end individual admin table -->
		</td>
		</tr>
		<?php
		$current = false;
		$id++;
	}
}
?>
</table> <!-- main admin table end -->
<input type="hidden" name="totaladmins" value="<?php echo $id; ?>" />
<br />
<p class="buttons">
<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p>
</form>
<script language="javascript" type="text/javascript">
	function setShow(v) {
		<?php
		foreach ($showlist as $show) {
			?>
			$('<?php echo $show; ?>').val(v);
			<?php
		}
		?>
	}
</script>

<br clear="all" />
<br />
</div><!-- end of tab_admin div -->

</div><!-- end of container -->
</div><!-- end of content -->
</div><!-- end of main -->
<?php
printAdminFooter();
?>
</body>
</html>



