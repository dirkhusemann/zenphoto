<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
  * @package plugins
 */
define ('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/admin-globals.php');

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
		$id = sanitize_numeric($_GET['groupid']);
		deleteAdmin(array('id'=>$id));
		$sql = "DELETE FROM ".prefix('admintoalbum')." WHERE `adminid`=$id";
		query($sql);
		//first clear out existing user assignments
		$groupname = sanitize($_GET['group'],3);
		updateAdminField('group', NULL, array('valid'=>1, 'group'=>$groupname));
		header("Location: ".FULLWEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&tab=groups&deleted');
		exit();
	} else if ($action == 'savegroups') {
		for ($i = 0; $i < $_POST['totalgroups']; $i++) {
			$groupname = trim(sanitize($_POST[$i.'-group'],3));
			$groupname = str_replace('"','',$groupname);
			if (!empty($groupname)) {
				$group = new Administrator($groupname, 0);
				if (isset($_POST[$i.'-initgroup']) && !empty($_POST[$i.'-initgroup'])) {
					$initgroupname = trim(sanitize($_POST[$i.'-initgroup'],3));
					$initgroup = new Administrator($initgroupname, 0);
					$group->setRights($initgroup->getRights());
					$group->setAlbums(populateManagedAlbumList($initgroup->get('id')));
				} else {
					$group->setRights(processRights($i) | NO_RIGHTS);
					$group->setAlbums(processManagedAlbums($i));
				}
				$groupdesc = trim(sanitize($_POST[$i.'-desc'], 3));
				$grouptype = trim(sanitize($_POST[$i.'-type'], 3));
								
				saveAdmin($groupname, NULL, $grouptype, NULL, $group->getRights(), $group->getAlbums(), $groupdesc, NULL, 0);
				if ($group->getName()=='group') {
					//have to update any users who have this group designate.
					foreach ($admins as $admin) {
						if ($admin['valid'] && $admin['group']===$groupname) {
							$user = new Administrator($admin['user'], 1);
							saveAdmin($admin['user'], NULL, $user->getName(), $user->getEmail(), $group->getRights(), $group->getAlbums(), $user->getCustomData(), $groupname);
						}
					}
					//user assignments: first clear out existing ones
					updateAdminField('group', NULL, array('valid'=>1, 'group'=>$groupname));
					//then add the ones marked
					$target = 'user_'.$i.'-';
					foreach ($_POST as $item=>$username) {
						if (strpos($item, $target)!==false) {
							$user = new Administrator($username, 1);
							saveAdmin($username, NULL, $user->getName(), $user->getEmail(), $group->getRights(), $group->getAlbums(), $user->getCustomData(), $groupname);
						}
					}
				}
			}
		}
		header("Location: ".FULLWEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&tab=groups&saved');
		exit();
	} else if ($action == 'saveauserassignments') {
		for ($i = 0; $i < $_POST['totalusers']; $i++) {
			$username = trim(sanitize($_POST[$i.'-user'],3));
			$user = new Administrator($username, 1);
			$groupname = trim(sanitize($_POST[$i.'-group'],3));
			$group = new Administrator($groupname, 0);
			if (empty($groupname)) {
				updateAdminField('group', NULL, array('id'=>$user->get('id')));
			} else {
				saveAdmin($username, NULL, $user->getName(), $user->getEmail(), $group->getRights(), populateManagedAlbumList($group->get('id')), $user->getCustomData(), $groupname);
			}
		}
		header("Location: ".FULLWEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/user_groups/user_groups-tab.php?page=users&tab=assignments&saved');
		exit();
	}
}
$page = 'users';

printAdminHeader();
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
			$subtab = printSubtabs('users');
			?>
			<div id="tab_users" class="tabbox">
				<?php
				switch ($subtab) {
					case 'groups':
						$adminlist = $adminordered;
						$users = array();
						$groups = array();
						foreach ($adminlist as $user) {
							if ($user['valid']) {
								$users[] = $user['user'];
							} else {
								$groups[] = $user;
							}
						}
						$gallery = new Gallery();
						$albumlist = array();
						foreach ($gallery->getAlbums() as $folder) {
							if (hasDynamicAlbumSuffix($folder)) {
								$name = substr($folder, 0, -4); // Strip the .'.alb' suffix
							} else {
								$name = $folder;
							}
							$albumlist[$name] = $folder;
						}
						?>
						<p>
							<?php
							echo gettext("Set group rights and select one or more albums for the users in the group to manage. Users with <em>User admin</em> or <em>Manage all albums</em> rights can manage all albums. All others may manage only those that are selected.");
							?>
						</p>
						<form action="?action=savegroups&amp;tab=groups" method="post" autocomplete="off">
							<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
							</p>
							<br clear="all" /><br /><br />
							<input type="hidden" name="savegroups" value="yes" /> 
							<table class="bordered">
								<?php
								$id = 0;
								$groupselector = $groups;
								$groupselector[''] = array('id' => -1,  'user' => '', 'name'=>'group', 'rights' => ALL_RIGHTS ^ MANAGE_ALL_ALBUM_RIGHTS, 'valid' => 0, 'custom_data'=>'');
								foreach($groupselector as $key=>$user) {
									$groupname = $user['user'];
									$groupid = $user['id'];
									$rights = $user['rights'];
									$grouptype = $user['name'];
									?>
									<tr>
										<td style="border-top:4px solid #D1DBDF;width:20em;" valign="top" >
											<?php
											if (empty($groupname)) {
												?>
												<em>
													<label><input type="radio" name="<?php echo $id; ?>-type" value="group" checked="checked" onclick="javascrpt:toggle('users<?php echo $id; ?>');" /><?php echo gettext('group'); ?></label>
													<label><input type="radio" name="<?php echo $id; ?>-type" value="template" onclick="javascrpt:toggle('users<?php echo $id; ?>');" /><?php echo gettext('template'); ?></label>
												</em>
												<br />
												<input type="text" size="35" name="<?php echo $id ?>-group" value="" />
												<?php
											} else {
												?>
												<em><?php if ($grouptype == 'group') echo gettext('group'); else echo gettext('template'); ?></em>
												<br />
												<strong><?php echo $groupname; ?></strong>
												<input type="hidden" name="<?php echo $id ?>-group" value="<?php echo htmlspecialchars($groupname); ?>" />
												<input type="hidden" name="<?php echo $id ?>-type" value="<?php echo htmlspecialchars($grouptype); ?>" />
												<?php
											}
											?>
											<br /><br />
											<?php echo gettext('description:'); ?>
											<br />
											<textarea name="<?php echo $id; ?>-desc" cols="40" rows="4"><?php echo htmlentities($user['custom_data'],ENT_COMPAT,getOption("charset")); ?></textarea>
											<?php
											if (empty($groupname) && !empty($groups)) {
												?>
												<br />
												<?php echo gettext('clone:'); ?>
												<br />
												<select name="<?php echo $id; ?>-initgroup" onchange="javascript:$('#hint<?php echo $id; ?>').html(this.options[this.selectedIndex].title);">
													<option title=""></option>
													<?php
													foreach ($groups as $user) {
														$hint = '<em>'.htmlentities($user['custom_data'],ENT_COMPAT,getOption("charset")).'</em>';
														if ($groupname == $user['user']) {
															$selected = ' selected="selected"';
															} else {
															$selected = '';
														}
														?>
														<option<?php echo $selected; ?> title="<?php echo $hint; ?>"><?php echo $user['user']; ?></option>
														<?php
													}
													?>
												</select>
												<span class="hint<?php echo $id; ?>" id="hint<?php echo $id; ?>"></span>
												<?php
											}
											?>
										</td>
										<td style="border-top: 4px solid #D1DBDF;?>" valign="top">
											<input type="hidden" name="<?php echo $id ?>-confirmed" value="1" />
											<?php				
											printAdminRightsTable($id, '', '', $rights);
											printManagedAlbums($albumlist, '', $groupid, $id);
											?>
											
										</td>
										<td style="border-top: 4px solid #D1DBDF;?>" valign="top">
											<div id="users<?php echo $id; ?>" <?php if ($grouptype=='template') echo ' style="display:none"' ?>>
												<h2 class="h2_bordered_edit"><?php echo gettext("Assign users"); ?></h2>
												<div class="box-tags-unpadded">
													<?php											
													$members = array();
													if (!empty($groupname)) {
														foreach ($adminlist as $user) {
															if ($user['valid'] && $user['group']==$groupname) {
																$members[] = $user['user'];
															}
														}
													}
													?>
													<ul class="shortchecklist">
													<?php generateUnorderedListFromArray($members, $users, 'user_'.$id.'-', false, true, false); ?>
													</ul>
												</div>
											</div>
										</td>
										<td style="border-top: 4px solid #D1DBDF;" valign="top">
										<?php
										if (!empty($groupname)) {
											$msg = gettext('Are you sure you want to delete this group?');
											?>
											<a href="javascript:if(confirm(<?php echo "'".$msg."'"; ?>)) { window.location='?action=deletegroup&groupid=<?php echo $groupid; ?>&group=<?php echo addslashes($groupname); ?>'; }"
																title="<?php echo gettext('Delete this group.'); ?>" style="color: #c33;">
												<img src="../../images/fail.png" style="border: 0px;" alt="Delete" />
											</a> 
											<?php
										}
										?>	
										</td>
									</tr>
									<?php
									$id++;
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
						<?php
						break;
					case 'assignments':
						$groups = array();
						foreach ($adminordered as $user) {
							if (!$user['valid'] && $user['name'] == 'group') {
								$groups[] = $user;
							}
						}
						?>
						<p>
							<?php
							echo gettext("Assign users to groups.");
							?>
						</p>
						<form action="?action=saveauserassignments" method="post" autocomplete="off">
							<p class="buttons">
							<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
							</p>
							<br clear="all" /><br /><br />
							<input type="hidden" name="saveauserassignments" value="yes" /> 
							<table class="bordered">
								<?php
								$id = 0;
								foreach ($adminordered as $user) {
									if ($user['valid']) {
										$group = $user['group'];
										?>
										<tr>
											<td width="20%" style="border-top: 1px solid #D1DBDF;" valign="top">
												<input type="hidden" name="<?php echo $id; ?>-user" value="<?php echo $user['user']; ?>" />
												<?php echo $user['user']; ?>
											</td>
											<td style="border-top: 1px solid #D1DBDF;" valign="top" >
												<select name="<?php echo $id; ?>-group" onchange="javascript:$('#hint<?php echo $id; ?>').html(this.options[this.selectedIndex].title);">
													<option title="<?php echo gettext('no group affiliation'); ?>"></option>
													<?php
													$selected_hint = gettext('no group affiliation');
													foreach ($groups as $user) {
														$hint = '<em>'.htmlentities($user['custom_data'],ENT_COMPAT,getOption("charset")).'</em>';
														if ($group == $user['user']) {
															$selected = ' selected="selected"';
															$selected_hint = $hint;
															} else {
															$selected = '';
														}
														?>
														<option<?php echo $selected; ?> title="<?php echo $hint; ?>"><?php echo $user['user']; ?></option>
														<?php
													}
													?>
												</select>
												<span class="hint<?php echo $id; ?>" id="hint<?php echo $id; ?>" style="width:15em;"><?php echo $selected_hint; ?></span>
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
						<?php
						break;
				}
				?>				
			</div>
		
		</div>
	</div>
</body>
</html>