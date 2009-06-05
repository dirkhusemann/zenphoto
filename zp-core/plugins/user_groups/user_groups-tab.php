<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */
$zp = dirname(dirname(dirname(__FILE__)));
define ('OFFSET_PATH', 4);
require_once($zp.'/admin-functions.php');

$admins = getAdministrators();
$ordered = array();
foreach ($admins as $key=>$admin) {
	$ordered[$key] = $admin['user'];
}
asort($ordered);
$adminordered = array();
foreach ($ordered as $key=>$user) $adminordered[] = $admins[$key];
						
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	$themeswitch = false;
	if ($action == 'deletegroup') {
		$id = $_GET['groupid'];
		$sql = "DELETE FROM ".prefix('administrators')." WHERE `id`=$id";
		query($sql);
		$sql = "DELETE FROM ".prefix('admintoalbum')." WHERE `adminid`=$id";
		query($sql);
		// remover user group association
		$groupname = sanitize($_GET['group'],3);
		foreach ($admins as $admin) {
			if ($admin['valid'] && $admin['group']===$groupname) {
				$user = new Administrator($groupname);
				saveAdmin($username, NULL, $user->getName(), $user->getEmail(), $user->getRights(), $user->getAlbums(), $user->getCustomData(), NULL);
			}
		}
		header("Location: ".FULLWEBPATH."/".ZENFOLDER.PLUGIN_FOLDER.'user_groups/user_groups-tab.php?page=users&tab=groups&deleted');
		exit();
	} else if ($action == 'savegroups') {
		for ($i = 0; $i < $_POST['totalgroups']; $i++) {
			$groupname = trim(sanitize($_POST[$i.'-group'],3));
			if (!empty($groupname)) {
				$group = new Administrator($groupname);
				$group->setRights(processRights($i));
				$group->setAlbums(processManagedAlbums($i));
				saveAdmin($groupname, NULL, NULL, NULL, $group->getRights(), $group->getAlbums(), NULL, NULL, 0);
				//have to update any users who have this group designate.
				foreach ($admins as $admin) {
					if ($admin['valid'] && $admin['group']===$groupname) {
						$user = new Administrator($admin['user']);
						saveAdmin($admin['user'], NULL, $user->getName(), $user->getEmail(), $group->getRights(), $group->getAlbums(), $user->getCustomData(), $groupname);
					}
				}
			}
		}
		header("Location: ".FULLWEBPATH."/".ZENFOLDER.PLUGIN_FOLDER.'user_groups/user_groups-tab.php?page=users&tab=groups&saved');
		exit();
	} else if ($action == 'saveauserassignments') {
		for ($i = 0; $i < $_POST['totalusers']; $i++) {
			$username = trim(sanitize($_POST[$i.'-user'],3));
			$group = trim(sanitize($_POST[$i.'-group'],3));
			$user = new Administrator($username);
			saveAdmin($username, NULL, $user->getName(), $user->getEmail(), $user->getRights(), $user->getAlbums(), $user->getCustomData(), $group);
		}
		header("Location: ".FULLWEBPATH."/".ZENFOLDER.PLUGIN_FOLDER.'user_groups/user_groups-tab.php?page=users&tab=users&saved');
		exit();
	}
}
$page = 'users';

printAdminHeader(WEBPATH.'/'.ZENFOLDER.'/');
echo '</head>'."\n";
?>

<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs('users'); ?>
		<div id="content">
			<?php
			if (isset($_GET['deleted'])) {
				echo '<div class="messagebox" id="fade-message">';
				echo  "<h2>Deleted</h2>";
				echo '</div>';
			}
			if (isset($_GET['saved'])) {
				echo '<div class="messagebox" id="fade-message">';
				echo  "<h2>Saved</h2>";
				echo '</div>';
			}
			$subtab = printSubtabs($subtabs['usertabs']);
			?>
			<div id="tab_users" class="tabbox">
				<?php
				switch ($subtab) {
					case 'groups':
						$gallery = new Gallery();
						$albumlist = $gallery->getAlbums();
						
						$adminordered [''] = array('id' => -1,  'user' => '', 'rights' => ALL_RIGHTS ^ ALL_ALBUMS_RIGHTS, 'valid' => 0);
						?>
						<form action="?action=savegroups&tab=groups" method="post" AUTOCOMPLETE=OFF>
							<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
							</p>
							<br clear="al" /><br /><br />
							<input type="hidden" name="savegroups" value="yes" /> 
							<table class="bordered">
								<?php
								$id = 0;
								foreach($adminordered as $user) {
									if (!$user['valid']) { // then it is a group
										$userid = $user['user'];
										?>
										<tr>
											<td width="20%" style="border-top: 4px solid #D1DBDF;" valign="top">
												<?php
												if (empty($user['user'])) {
													?>
													<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="<?php echo $id ?>-group" value="" />
													<?php
												} else {
													echo $userid;
													$msg = gettext('Are you sure you want to delete this group?');
													?>
													&nbsp;&nbsp;
													<a href="javascript: if(confirm(<?php echo "'".$msg."'"; ?>)) { window.location='?action=deletegroup&groupid=<?php echo $user['id']; ?>&group=<?php echo $userid; ?>'; }"
														title="<?php echo gettext('Delete this group.'); ?>" style="color: #c33;"> <img
														src="../../images/fail.png" style="border: 0px;" alt="Delete" /></a> 
	
													<input type="hidden" name="<?php echo $id ?>-group" value="<?php echo $userid ?>" />
													<?php
												}
												?>
											</td>
											<td style="border-top: 4px solid #D1DBDF;?>" valign="top">
												<?php
												printAdminRightsTable($id, '', '', $user['rights']);
												?>
												<p>
													<?php
													printManagedAlbums($albumlist, '', $user['id'], $id);
													?>
												</p>
											</td>
										</tr>
										<?php
										$id++;
									}
								}
								?>
							</table>
							<br />
							<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
							</p>
							<input type="hidden" name="totalgroups" value="<?php echo $id; ?>" />
						</form>
						<br clear="all" /><br />
						<p>
							<?php
							echo gettext("Set group rights and select one or more albums for the users in the group to manage. Users with <em>User admin</em> or <em>Manage all albums</em> rights can manage all albums. All others may manage only those that are selected.");
							?>
						</p>
						<?php
						break;
					case 'users':
						$groups = array('');
						foreach ($adminordered as $user) {
							if (!$user['valid']) {
								$groups[] = $user['user'];
							}
						}
						?>
						<form action="?action=saveauserassignments&tab=users" method="post" AUTOCOMPLETE=OFF>
							<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
							</p>
							<br clear="al"><br /><br />
							<input type="hidden" name="saveauserassignments" value="yes" /> 
							<table class="bordered">
								<?php
								$id = 0;
								foreach ($adminordered as $user) {
									if ($user['valid']) {
										?>
										<input type="hidden" name="<?php echo $id; ?>-user" value="<?php echo $user['user']; ?>" />
										<tr>
											<td width="20%" style="border-top: 1px solid #D1DBDF;" valign="top">
												<?php echo $user['user']; ?>
											</td>
											<td style="border-top: 1px solid #D1DBDF;" valign="top">
												<select name="<?php echo $id; ?>-group" >
													<?php generateListFromArray(array($user['group']), $groups,false, false); ?>
												</select>
											</td>
										</tr>
										<?php
										$id++;
									}
								}
								?>
							</table>
							<br />
							<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
							</p>
						<input type="hidden" name="totalusers" value="<?php echo $id; ?>" />
						</form>
						<br clear="all" /><br />
						<p>
							<?php
							echo gettext("Assign users to groups.");
							?>
						</p>
						<?php
						break;
				}
				?>				
			</div>
		
		</div>
	</div>
</body>