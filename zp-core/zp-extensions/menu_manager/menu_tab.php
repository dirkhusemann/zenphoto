<?php
define ('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/admin-globals.php');
require_once(dirname(dirname(dirname(__FILE__))).'/template-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/'.PLUGIN_FOLDER.'/zenpage/zenpage-admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/'.PLUGIN_FOLDER.'/menu_manager/menu_manager-admin-functions.php');

admin_securityChecks(NULL, currentRelativeURL(__FILE__));

$page = 'edit';

$menuset = checkChosenMenuset('');
if (empty($menuset)) {	//	setup default menuset
	$result = query_full_array("SELECT DISTINCT menuset FROM ".prefix('menu'));
	if (is_array($result)) {	// default to the first one
		$set = array_shift($result);
		$menuset = $set['menuset'];
	} else {
		$menuset = 'default';
	}
	$_GET['menuset'] = $menuset;
}

$reports = array();
if(isset($_POST['update'])) {
	XSRFdefender('update_menu');
	processMenuBulkActions($reports);
	updateItemsSortorder($reports);
}
if (isset($_GET['delete'])) {
	XSRFdefender('delete_menu');
	$sql = 'SELECT `sort_order` FROM '.prefix('menu').' WHERE `id`='.sanitize_numeric($_GET['id']);
	$result = query_single_row($sql);
	if (empty($result)) {
		$reports[] = "<p class='errorbox' >".gettext('Menu item deleted failed')."</p>";
	} else {
		$sql = 'DELETE FROM '.prefix('menu').' WHERE `sort_order` LIKE "'.$result['sort_order'].'%"';
		query($sql);
		$reports[] =  "<p class='messagebox' id='fade-message'>".gettext('Menu item deleted')."</p>";
	}
}
if (isset($_GET['deletemenuset'])) {
	XSRFdefender('delete_menu');
	$sql = 'DELETE FROM '.prefix('menu').' WHERE `menuset`="'.zp_escape_string(sanitize($_GET['deletemenuset'])).'"';
	query($sql);
	$_menu_manager_items = array();
	$delmsg =  "<p class='messagebox' id='fade-message'>".sprintf(gettext("Menu set '%s' deleted"),html_encode($_GET['deletemenuset']))."</p>";
}
// publish or un-publish page by click
if(isset($_GET['publish'])) {
	XSRFdefender('update_menu');
	publishItem($_GET['id'],$_GET['show'],$menuset);
}

printAdminHeader();
printSortableHead();
?>
<link rel="stylesheet" href="../zenpage/zenpage.css" type="text/css" />
</head>
<body>
<?php	printLogoAndLinks(); ?>
<div id="main">
<?php
printTabs("menu");
?>
<div id="content">
<?php
foreach ($reports as $report) {
	echo $report;
}

$sql = 'SELECT COUNT(DISTINCT `menuset`) FROM '.prefix('menu');
$result = query($sql);
$count = db_result($result, 0);
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	 function newMenuSet() {
		var new_menuset = prompt("<?php echo gettext('Menuset id'); ?>","<?php echo 'menu_'.$count; ?>");
		if (new_menuset) {
			window.location = '?menuset='+encodeURIComponent(new_menuset);
		}
	};
	function deleteMenuSet() {
		if (confirm('<?php printf(gettext('Ok to delete menu set %s? This cannot be undone!'),html_encode($menuset)); ?>')) {
			window.location = '?deletemenuset=<?php echo html_encode($menuset); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('delete_menu')?>';
		}
	};
	function deleteMenuItem(location,warn) {
		if (confirm(warn)) {
			window.location = location;
		}
	}
	function confirmAction() {
		if ($('#checkallaction').val() == 'deleteall') {
			return confirm('<?php echo js_encode(gettext("Are you sure you want to delete the checked items?")); ?>');
		} else {
			return true;
		}
	}
	// ]]> -->
</script>
<h1><?php echo gettext("Menu Manager")."<small>"; printf(gettext(" (Menu set: %s)"), html_encode($menuset)); echo "</small>"; ?></h1>

<form action="menu_tab.php?menuset=<?php echo $menuset; ?>" method="post" name="update" onsubmit="return confirmAction();">
	<?php XSRFToken('update_menu'); ?>
<p>
<?php echo gettext("Drag the items into the order, including sub levels, you wish them displayed. This lets you create arbitrary menus and place them on your theme pages. Use printCustomMenu() to place them on your pages."); ?>
</p>
<p class="notebox">
<?php echo gettext("<strong>IMPORTANT:</strong> This menu's order is completely independent from any order of albums or pages set on the other admin pages. It is recommend to uses is with customized themes only that do not use the standard Zenphoto display structure. Standard Zenphoto functions like the breadcrumb functions or the next_album() loop for example will NOT take care of this menu's structure!");?>
</p>
<p class="buttons">
<button type="submit" title="<?php echo gettext("Apply"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Apply"); ?></strong></button>
<strong><a href="menu_tab_edit.php?add&amp;menuset=<?php echo urlencode($menuset); ?>" title="<?php echo gettext("Add Menu Items"); ?>"><img src="../../images/add.png" alt="" /> <?php echo gettext("Add Menu Items"); ?></a></strong>
<strong><a href="javascript:newMenuSet();" title="<?php echo gettext("Add Menu set"); ?>"><img src="../../images/add.png" alt="" /> <?php echo gettext("Add Menu set"); ?></a></strong>
</p>
<br clear="all" /><br />

<table class="bordered" style="margin-top: 10px">
	<tr>
		<th colspan="2" style="text-align:left">
			<strong><?php echo gettext("Edit the menu"); ?></strong>
			<?php echo getMenuSetSelector(true); ?>
			<?php printItemStatusDropdown(); ?>
			<?php
			$checkarray = array(
					gettext('*Bulk actions*') => 'noaction',
					gettext('Delete') => 'deleteall',
					gettext('Set to published') => 'showall',
					gettext('Set to unpublished') => 'hideall'
			);
			?>
			<span style="float: right">
			<select name="checkallaction" id="checkallaction" size="1">
			<?php generateListFromArray(array('noaction'), $checkarray,false,true); ?>
			</select>
			</span>
			<span class="buttons" style="float: right"><?php
if ($count > 0) {
	$buttontext = sprintf(gettext("Delete menu set '%s'"),html_encode($menuset));
	?>
	<strong><a href="javascript:deleteMenuSet();" title="<?php echo $buttontext; ?>"><img src="../../images/fail.png" alt="" /><?php echo $buttontext; ?></a></strong>
	<?php
}
?>
</span>
		</th>
	</tr>
	 <tr>
	<td class="subhead">
		<label style="float: right"><?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
		</label>
	</td>
	</tr>
	<tr>
		<td colspan="2" style="padding: 0;">
			<ul id="left-to-right" class="page-list">
			<?php
			if(isset($_GET['visible'])) {
				$visible = sanitize($_GET['visible']);
			} else {
				$visible = 'all';
			}
			$items = getMenuItems($menuset, $visible);
			printItemsList($items);
			?>
			</ul>
		</td>
	</tr>
</table>
<br />
<div id='left-to-right-ser'><input type="hidden" name="order" size="30" maxlength="1000" /></div>
				<input name="update" type="hidden" value="Save Order" />
				<p class="buttons"><button type="submit" title="<?php echo gettext("Apply"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Apply"); ?></strong></button></p>
</form>
	<ul class="iconlegend">
	<li><img src="../../images/lock_2.png" alt="" /><?php echo gettext("Menu target is password protected"); ?></li>
	<li><img src="../../images/pass.png" alt="" /><img	src="../../images/action.png" alt="" /><?php echo gettext("Show/hide"); ?></li>
	<li><img src="../zenpage/images/view.png" alt="" /><?php echo gettext("View"); ?></li>
	<li><img src="../../images/fail.png" alt="" /><?php echo gettext("Delete"); ?></li>
	</ul>
</div>
<script type="text/javascript">
	//<!-- <![CDATA[
	jQuery( function($) {
	$('#left-to-right').NestedSortable(	{
			accept: 'page-item1',
			noNestingClass: "no-nesting",
			opacity: 0.4,
			helperclass: 'helper',
			onchange: function(serialized) {
				$('#left-to-right-ser')
				.html("<input name='order' size='100' maxlength='1000' type='hidden' value="+ serialized[0].hash +">");
			},
			autoScroll: true,
			handle: '.sort-handle'
		}
	);
	});
	// ]]> -->
</script>
</div>
<?php printAdminFooter(); ?>

</body>
</html>