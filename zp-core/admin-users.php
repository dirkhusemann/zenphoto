<?php
/**
 * provides the Options tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

admin_securityChecks(NULL, currentRelativeURL(__FILE__));

$gallery = new Gallery();
$_GET['page'] = 'users'; // must be a user with no options rights
$_current_tab = sanitize($_GET['page'],3);

/* handle posts */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	$themeswitch = false;
	if ($action == 'deleteadmin') {
		XSRFdefender('deleteadmin');
		$adminobj = new Zenphoto_Administrator(sanitize($_GET['adminuser']),1);
		zp_apply_filter('save_user', '', $adminobj, 'delete');
		$adminobj->delete();
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-users.php?page=users&deleted");
		exit();
	} else if ($action == 'saveoptions') {
		if (!$_zp_null_account) XSRFdefender('saveadmin');
		$notify = '';
		$returntab = '';

		/*** admin options ***/
		if (isset($_POST['saveadminoptions'])) {
			$nouser = true;
			$newuser = false;
			for ($i = 0; $i < $_POST['totaladmins']; $i++) {
				$updated = false;
				$error = false;
				$userobj = NULL;
				$pass = trim($_POST[$i.'-adminpass']);
				$user = trim(sanitize($_POST[$i.'-adminuser'],0));
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
							$objects = processManagedObjects($i);
						} else {
							$rights = NULL;
							$objects = array();
						}
						$userobj = $_zp_authority->newAdministrator($user);
						if ($admin_n != $userobj->getName()) {
							$updated = true;
							$userobj->setName($admin_n);
						}
						if ($admin_e != $userobj->getEmail()) {
							$updated = true;
							$userobj->setEmail($admin_e);
						}
						if ($rights != $userobj->getRights()) {
							$updated = true;
							$userobj->setRights($rights);
						}
						$objects_match = true;
						$oldobjects = populateManagedObjectsList('', $userobj->getID());
						if (!is_array($oldobjects)) $oldobjects = array();
						foreach ($objects as $object) {
							$key = false;
							foreach($oldobjects as $objinx=>$oldobject) {
								if ($object['type']==$oldobject['type'] && $object['data']==$oldobject['data']) {
									$key = $objinx;
									break;
								}
							}
							if ($key === false) {
								$objects_match = false;
								break;
							} else {
								unset ($oldobjects[$key]);
							}
						}
						if (!$objects_match || count($oldobjects) > 0) {
							$updated = true;
							$userobj->setObjects($objects);
						}
						if (empty($pass)) {
							$msg = '';
						} else {
							$msg = $userobj->setPass($pass);
							$updated = true;
						}
						$updated = zp_apply_filter('save_admin_custom_data', $updated, $userobj, $i);
						$userobj->save();
							if (isset($_POST[$i.'-newuser'])) {
								$newuser = $user;
								$what = 'new';
							} else {
								$what = 'update';
							}
						zp_apply_filter('save_user', $msg, $userobj, $what);
						if (empty($msg)) {
							if ($i == 0) {
								setOption('admin_reset_date', '1');
							}
						} else {
							$notify = '?mismatch=format&error='.urlencode($msg);
							$error = true;
						}
					} else {
						$notify = '?mismatch=password';
						$error = true;
					}
				}
			}
			if ($nouser) {
				$notify = '?mismatch=nothing';
			}
			$returntab = "&page=users";
			if (!empty($newuser)) {
				$returntab .= '&show-'.$newuser;
				unset($_POST['show-']);
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
<script type="text/javascript" src="<?php echo WEBPATH.'/'.ZENFOLDER;?>/js/sprintf.js"></script>
<link rel="stylesheet" href="js/farbtastic.css" type="text/css" />

<?php
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
	global $_zp_authority;
?>
<div id="tab_admin" class="tabbox">
<?php
	if (zp_loggedin(ADMIN_RIGHTS)) {
		if ($_zp_null_account && isset($_zp_reset_admin)) {
			$admins = array($_zp_reset_admin['user'] => $_zp_reset_admin);
			$alterrights = ' disabled="disabled"';
			setOption('admin_reset_date', $_zp_request_date); // reset the date in case of no save
		} else {
			$admins = $_zp_authority->getAdministrators();
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
		$admins = array($_zp_current_admin_obj->getUser() => 
													array('id' => $_zp_current_admin_obj->getID(), 
																'user' => $_zp_current_admin_obj->getUser(),
																'pass' => $_zp_current_admin_obj->getPass(),
																'name' => $_zp_current_admin_obj->getName(),
																'email' => $_zp_current_admin_obj->getEmail(),
																'rights' => $_zp_current_admin_obj->getRights(),
																'custom_data' => $_zp_current_admin_obj->getCustomData(),
																'valid' => 1,
																'group' => $_zp_current_admin_obj->getGroup()));
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
	if (isset($_GET['badurl'])) {
		echo '<div class="errorbox" id="fade-message">';
		echo  "<h2>".gettext("Your Website URL is not valid")."</h2>";
		echo '</div>';
	}
	
	
	
?> 
<form action="?action=saveoptions<?php if (isset($_zp_ticket)) echo '&amp;ticket='.$_zp_ticket.'&amp;user='.$post_user; ?>" method="post" autocomplete="off" onsubmit="return checkNewuser();" >
	<?php XSRFToken('saveadmin');?>
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
			if (strpos($param, 'show-') === 0) {
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
			$showlist[] = '#show-'.$userid;
			$userobj = $_zp_authority->newAdministrator($userid);
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
				if (zp_loggedin(ADMIN_RIGHTS)) {
					$master = "(<em>".gettext("Master")."</em>)";
					$userobj->setRights($userobj->getRights() | ADMIN_RIGHTS);
					$ismaster = true;
				}
			}
			if (isset($_GET['show-'.$userid])) {
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
			<?php $custom_row = zp_apply_filter('edit_admin_custom_data', '', $userobj, $id, $background, $current, $local_alterrights); ?>
			<!-- finished with filters -->
			<tr>
				<td colspan="2" style="margin: 0pt; padding: 0pt;">
				<!-- individual admin table -->
				<input type="hidden" name="show-<?php echo $userid; ?>" id="show-<?php echo $userid; ?>" value="<?php echo ($current);?>" /> 
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
							<a href="javascript:$('#show-<?php echo $userid; ?>').val(1);toggleExtraInfo('<?php echo $id;?>','user',true);" title="<?php echo $displaytitle; ?>" >
								<?php
								if (empty($userid)) {
									?>
									<input type="hidden" name="<?php echo $id ?>-newuser" value="1" />
									<em><?php echo gettext("Add New User"); ?></em>
									<?php
								} else {
									?>
									<input type="hidden" id="adminuser-<?php echo $id; ?>" name="<?php echo $id ?>-adminuser" value="<?php echo $userid ?>" />
									<?php
									echo '<strong>'.$userid.'</strong>'; 
								}
								?>
							</a>
						</span>
						<span <?php if ($current) echo 'style="display:block;"'; else echo 'style="display:none;"'; ?> class="userextrahide">
							<a href="javascript:$('#show-<?php echo $userid; ?>').val(0);toggleExtraInfo('<?php echo $id;?>','user',false);" title="<?php echo $hidetitle; ?>">
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
					<?php
					if (!$alterrights) {
						?>
						<td width="345" style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" valign="top" >
						<?php 
						if (empty($userid)) {
								?>
								<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" id="adminuser-<?php echo $id; ?>" name="<?php echo $id; ?>-adminuser" value=""
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
							<a href="javascript:if(confirm(<?php echo "'".$msg."'"; ?>)) { window.location='?action=deleteadmin&adminuser=<?php echo addslashes($user['user']); ?>&amp;XSRFToken=<?php echo getXSRFToken('deleteadmin')?>'; }"
								title="<?php echo gettext('Delete this user.'); ?>" style="color: #c33;"> <img
								src="images/fail.png" style="border: 0px;" alt="Delete" /></a> 
							<?php
							}
							?>
							&nbsp;
							</td>
							<?php
					} else  {
						?>
						<td colspan="2" style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" valign="top" >
							<span class="notebox">
								<?php echo gettext('<strong>Note:</strong> You must have ADMIN rights to alter anything but your personal information.');?>
							</span>
						</td>
						<?php
					}
					?>
				</tr>
			<tr <?php if (!$current) echo 'style="display:none;"'; ?> class="userextrainfo">
				<td width="20%" <?php if (!empty($background)) echo " style=\"$background\""; ?>>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("Password:"); ?>
					<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
				</td>
				<td  width="320" <?php if (!empty($background)) echo " style=\"$background\""; ?>><?php $x = $userobj->getPass(); if (!empty($x)) { $x = '          '; } ?>
					<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminpass"
						value="<?php echo $x; ?>" />
					<br />
					<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-adminpass_2"
						value="<?php echo $x; ?>" />
					<?php
					$msg = $_zp_authority->passwordNote();
					if (!empty($msg)) {
						echo $msg; 
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
					if (zp_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
						$album_alter_rights = $local_alterrights;
					} else {
						$album_alter_rights = ' disabled="disabled"';
					}
					if ($current && $ismaster) {
						echo '<p>'.gettext("The <em>master</em> account has full rights to all albums.").'</p>';
					} else {
						printManagedObjects('albums',$albumlist, $album_alter_rights, $user['id'], $id);
						if (getOption('zp_plugin_zenpage')) {
							$pagelist = array();
							$pages = getPages(false);
							foreach ($pages as $page) {
								if (!$page['parentid']) {
									$pagelist[get_language_string($page['title'])] = $page['titlelink'];
								}
							}
							printManagedObjects('pages',$pagelist, $album_alter_rights, $user['id'], $id);
							$newslist = array();
							$categories = getAllCategories();
							foreach ($categories as $category) {
								$newslist[get_language_string($category['cat_name'])] = $category['cat_link'];
							}
							printManagedObjects('news',$newslist, $album_alter_rights, $user['id'], $id);
						}
					}
						if (!$ismaster) {
							?>
							<p>
								<?php
									if (empty($album_alter_rights)) {
									echo gettext("Select one or more objects for the administrator to manage.").' ';
									echo gettext("Administrators with <em>User admin</em> or <em>Manage all...</em> rights can manage all objects. All others may manage only those that are selected.");
								} else {
									echo gettext("You may manage these objects subject to the above rights.");
								}
								?>
							</p>
							<?php
						}
						?>
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
	//<!-- <![CDATA[
	function checkNewuser() {
		newuserid = <?php echo ($id-1); ?>;
		newuser = $('#adminuser-'+newuserid).val().replace(/^\s+|\s+$/g,"");;
		if (newuser=='') return true;
		if (newuser.indexOf('?')>=0 || newuser.indexOf('&')>=0 || newuser.indexOf('"')>=0 || newuser.indexOf('\'')>=0) {
			alert('<?php echo gettext('User names may not contain "?", "&", or quotation marks.'); ?>');
			return false;
		}
		for (i=newuserid-1;i>=0;i--) {
			if ($('#adminuser-'+i).val() == newuser) {
				alert(sprintf('<?php echo gettext('The user "%s" already exists.'); ?>',newuser));
				return false;
			}
		} 
		return true;
	}
	function setShow(v) {
		<?php
		foreach ($showlist as $show) {
			?>
			$('<?php echo $show; ?>').val(v);
			<?php
		}
		?>
	}
	// ]]> -->
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



