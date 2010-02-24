<?php
/**
 * provides the TAGS tab of admin
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');
require_once(dirname(__FILE__).'/template-functions.php');

if (!($_zp_loggedin & (TAGS_RIGHTS | ADMIN_RIGHTS))) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

$gallery = new Gallery();
$_GET['page'] = 'tags';

if (isset($_REQUEST['tagsort'])) {
	$tagsort = sanitize($_REQUEST['tagsort'], 0);
	setBoolOption('tagsort', $tagsort);
} else {
	$tagsort = getOption('tagsort');
}

printAdminHeader();
?>
</head>
<body>
<?php
printLogoAndLinks();
?>
<div id="main">
	<?php
	printTabs('tags');
	?>
	<div id="content">
		<?php
		if (count($_POST) > 0) {
			if (isset($_GET['newtags'])) {
				foreach ($_POST as $value) {
					if (!empty($value)) {
						$value = zp_escape_string(sanitize($value, 3));
						$result = query_single_row('SELECT `id` FROM '.prefix('tags').' WHERE `name`="'.$value.'"');
						if (!is_array($result)) { // it really is a new tag
							query('INSERT INTO '.prefix('tags').' (`name`) VALUES ("' . $value . '")');
						}
					}
				}
			} // newtags
			if (isset($_GET['delete'])) {
				$kill = array();
				foreach ($_POST as $key => $value) {
					$key = postIndexDecode($key);
					$kill[] = $_zp_UTF8->strtolower($key);
				}
				if (count($kill) > 0) {
					$sql = "SELECT `id` FROM ".prefix('tags')." WHERE ";
					foreach ($kill as $tag) {
						$sql .= "`name`='".(zp_escape_string($tag))."' OR ";
					}
					$sql = substr($sql, 0, strlen($sql)-4);
					$dbtags = query_full_array($sql);
					if (is_array($dbtags)) {
						$sqltags = "DELETE FROM ".prefix('tags')." WHERE ";
						$sqlobjects = "DELETE FROM ".prefix('obj_to_tag')." WHERE ";
						foreach ($dbtags as $tag) {
							$sqltags .= "`id`='".$tag['id']."' OR ";
							$sqlobjects .= "`tagid`='".$tag['id']."' OR ";
						}
						$sqltags = substr($sqltags, 0, strlen($sqltags)-4);
						query($sqltags);
						$sqlobjects = substr($sqlobjects, 0, strlen($sqlobjects)-4);
						query($sqlobjects);
					}
				}
			} // delete
			if (isset($_GET['rename'])) {
				foreach($_POST as $key=>$newName) {
					if (!empty($newName)) {
						$newName = sanitize($newName, 3);
						$key = postIndexDecode($key);
						$key = substr($key, 2); // strip off the 'R_'
						$newtag = query_single_row('SELECT `id` FROM '.prefix('tags').' WHERE `name`="'.zp_escape_string($newName).'"');
						$oldtag = query_single_row('SELECT `id` FROM '.prefix('tags').' WHERE `name`="'.zp_escape_string($key).'"');
						if (is_array($newtag)) { // there is an existing tag of the same name
							$existing = $newtag['id'] != $oldtag['id']; // but maybe it is actually the original in a different case.
						} else {
							$existing = false;
						}
						if ($existing) {
							query('DELETE FROM '.prefix('tags').' WHERE `id`='.$oldtag['id']);
							query('UPDATE '.prefix('obj_to_tag').' SET `tagid`='.$newtag['id'].' WHERE `tagid`='.$oldtag['id']);
						} else {
							query('UPDATE '.prefix('tags').' SET `name`="'.zp_escape_string($newName).'" WHERE `id`='.$oldtag['id']);
						}
					}
				}
			} // rename
		}
		
		echo "<h1>".gettext("Tag Management")."</h1>";
		if ($tagsort == 1) {
			?>
			<p class="buttons">
				<a class="tagsort" href="?tagsort=0" title="<?php echo gettext('Sort the tags alphabetically'); ?>">
					<img src="images/sortorder.png" alt="" /> <?php echo gettext('Order alphabetically'); ?>
				</a>
			</p>
			<br />
			<br />
			<br clear="all" />
			<?php
		} else{
			?>
			<p class="buttons">
				<a class="tagsort" href="?tagsort=1" title="<?php echo gettext('Sort the tags by most used'); ?>">
					<img src="images/sortorder.png" alt="" /> <?php echo gettext('Order by most used'); ?>
				</a>
			</p>
			<br />
			<br />
			<br clear="all" />
			<?php
		}
		?>
		<table class="bordered">
			<tr>
			<td valign='top'>
				<h2 class="h2_bordered_edit"><?php echo gettext("Delete tags from the gallery"); ?></h2>
				<form name="tag_delete" action="?delete=true&amp;tagsort=<?php echo $tagsort; ?>" method="post">
					<div class="box-tags-unpadded">
						<?php
						tagSelector(NULL, '', true, $tagsort);
						?>
					</div>
						
					<p class="buttons">
						<button type="submit" id='delete_tags' value="<?php echo gettext("Delete checked tags"); ?>" title="<?php echo gettext("Delete all the tags checked above."); ?>" >
						<img src="images/fail.png" alt="" /><?php echo gettext("Delete checked tags"); ?>
						</button>
					</p>
<?php
/* TODO: enable 					
					<label id="autocheck">
						<input type="checkbox" name="checkAllAuto" id="checkAllAuto" />
						<span id="autotext"><?php echo gettext('all')?></span>
					</label>
					<script type="text/javascript">
					var checked = false;
					$('#autocheck').click(
					   function() {
					      if (checked) {
						      checked = false;
					      } else {
						      checked = true;
					      }
					      $("INPUT[type='checkbox']").attr('checked', checked);
					   }
					)			
					</script>	
*/		
?>
					<br clear="all" />
					<br />
					<br />
				
				</form>
				<div class="tagtext">
					<p><?php echo gettext('To delete tags from the gallery, place a checkmark in the box for each tag you wish to delete then press the <em>Delete checked tags</em> button. The brackets contain the number of times the tag appears.'); ?></p>
				</div>
			</td>
			
			<td valign='top'>
				<h2 class="h2_bordered_edit"><?php echo gettext("Rename tags"); ?></h2>
				<form name="tag_rename" action="?rename=true&amp;tagsort=<?php echo $tagsort; ?>" method="post">
					<div class="box-tags-unpadded">
						<ul class="tagrenamelist">
							<?php
							$list = $_zp_admin_ordered_taglist;
							foreach($list as $item) {
								$listitem = 'R_'.postIndexEncode($item);
								?>
								<li>
									<label>
										<?php echo $item; ?>
										<input id="<?php echo $listitem; ?>" name="<?php echo $listitem; ?>" type="text" size='33' />
									</label>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
					<p class="buttons">
						<button type="submit" id='rename_tags' value="<?php echo gettext("Rename tags"); ?>" title="<?php echo gettext("Save all the changes entered above."); ?>" >
							<img src="images/pass.png" alt="" /><?php echo gettext("Rename tags"); ?>
						</button>
					</p>
					<br clear="all" />
					<br />
					<br />
				</form>
				<div class="tagtext">
					<p><?php echo gettext('To change the value of a tag enter a new value in the text box below the tag. Then press the <em>Rename tags</em> button'); ?></p>
				</div>
			</td>
			
			<td valign='top'>
				<h2 class="h2_bordered_edit"><?php echo gettext("New tags"); ?></h2>
				<form name="new_tags" action="?newtags=true&amp;tagsort=<?php echo $tagsort; ?>" method="post">
					<div class="box-tags-unpadded">
						<ul class="tagnewlist">
							<?php
							for ($i=0; $i<40; $i++) {
								?>
								<li>
									<input id="new_tag_<?php echo $i; ?>" name="new_tag_<?php echo $i; ?>" type="text" size='33'/>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
					<p class="buttons">
						<button type="submit" id='save_tags' value="<?php echo gettext("Add tags"); ?>" title="<?php echo gettext("Add all the tags entered above."); ?>" >
							<img src="images/add.png" alt="" /><?php echo gettext("Add tags"); ?>
						</button>
					</p>
					<br clear="all" />
					<br />
					<br />
				
				</form>
				<div class="tagtext">				
					<p><?php echo gettext("Add tags to the list by entering their names in the input fields of the <em>New tags</em> list. Then press the <em>Add tags</em> button"); ?></p>
				</div>
			</td>
			</tr>
		</table>
	
	</div>
	<?php
	printAdminFooter();
	?>
</div>
</body>
</html>




