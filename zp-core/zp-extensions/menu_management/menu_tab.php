<?php
define ('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/admin-globals.php');
require_once(dirname(dirname(dirname(__FILE__))).'/'.PLUGIN_FOLDER.'/zenpage/zenpage-admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/'.PLUGIN_FOLDER.'/menu_management/menu_management-admin-functions.php');

$page = 'edit';
printAdminHeader(WEBPATH.'/'.ZENFOLDER.'/', false); // no tinyMCE
?>
<link rel="stylesheet" href="../zenpage/zenpage.css" type="text/css" />
<script type="text/javascript" src="../zenpage/js/interface-1.2.js"></script>
<!--Nested Sortables-->
<script type="text/javascript" src="../zenpage/js/inestedsortable.js"></script>
</head>
<body>
<?php	printLogoAndLinks(); ?>
<div id="main">
<?php
printTabs("menu");
?>
<div id="content">
<?php

// create menu table and then get albums, pages and categories and put them into menu db table
// this is rought of course...

createTable();
if (!isset($_GET['menuset'])) { //	setup default menuset
	$menuset = 'default';
} else {
	$menuset = checkChosenMenuset();
}
addAlbumsToDatabase($menuset);
if(getOption('zp_plugin_zenpage')) {
	addPagesToDatabase($menuset);
	addCategoriesToDatabase($menuset);
}
if(isset($_POST['update'])) {
	updateItemsSortorder();
}
if (isset($_GET['delete'])) {
	$sql = 'DELETE FROM '.prefix('menu').' WHERE `id`='.sanitize_numeric($_GET['id']);
	query($sql);
}
if (isset($_GET['deletemenuset'])) {
	$sql = 'DELETE FROM '.prefix('menu').' WHERE `menuset`="'.zp_escape_string(sanitize($_GET['menuset'])).'"';
	query($sql);
}

// publish or unpublish page by click
if(isset($_GET['publish'])) { 
	publishItem($_GET['id'],$_GET['show']);
}
$sql = 'SELECT COUNT(DISTINCT `menuset`) FROM '.prefix('menu');
$result = query($sql);
$count = mysql_result($result, 0);
?>
<script type="text/javascript">
	function newMenuSet() {
		var new_menuset = prompt("<?php echo gettext('Menuset id'); ?>","<?php echo 'menu_'.$count; ?>");
		if (new_menuset) {
			window.location = '?menuset='+encodeURIComponent(new_menuset);
		}
	};
	function deleteMenuSet() {
		if (confirm('<?php printf(gettext('Ok to delete menu set %s? This cannot be undone!'),htmlspecialchars($menuset)); ?>')) {
			window.location = '?deletemenuset&amp;menuset=<?php echo htmlspecialchars($menuset); ?>';
		}
	};
</script>
<h1><?php echo gettext("Menu Management")."<small>"; printf(gettext(" (Menu set: %s)"), htmlspecialchars($menuset)); echo "</small>"; ?></h1> 				
 
<form action="menu_tab.php?menuset=<?php echo $menuset; ?>" method="post" name="update">

<p><?php echo gettext("Drag the items into the order, including sub page levels, you wish them displayed. <br /><br /><strong>IMPORTANT:</strong> This menu's order is completely independend from any order of albums or pages set on the other admin pages. It is recommend to use with customized themes only that do not use the standard Zenphoto display structure. Standard Zenphoto functions like the breadcrumb functions or the next_album() loop for example will NOT take care of this menu's structure!"); ?></p>
<p class="buttons">
<button type="submit" title="<?php echo gettext("Save order"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save order"); ?></strong></button>
<strong><a href="menu_tab_edit.php?add&amp;menuset=<?php echo urlencode($menuset); ?>" title="<?php echo gettext("Add Menu Item"); ?>"><img src="../../images/add.png" alt="" /> <?php echo gettext("Add Menu Item"); ?></a></strong>
<strong><a href="javascript:newMenuSet();" title="<?php echo gettext("Add Menu set"); ?>"><img src="../../images/add.png" alt="" /> <?php echo gettext("Add Menu set"); ?></a></strong>
<strong><a href="javascript:deleteMenuSet();" title="<?php echo gettext("Delete Menu set"); ?>"><img src="../../images/fail.png" alt="" /><?php echo gettext("Delete Menu set"); ?></a></strong>
</p>
<br clear="all" /><br />

<table class="bordered" style="margin-top: 10px">
	<tr> 
	  <th colspan="2" style="text-align:left">
	  	<strong><?php echo gettext("Edit the menu"); ?></strong>
	  	<?php printMenuSetSelector(true); ?>
	  	<?php printItemStatusDropdown(); ?>
	  </th>
	</tr>
	<tr>
	 	<td colspan="2" style="padding: 0;">
			<ul id="left-to-right" class="page-list">
			<?php
			if(isset($_GET['visible'])) {
				$visible = sanitize_numeric($_GET['visible']);
			} else {
				$visible = 3;
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
 				<p class="buttons"><button type="submit" title="<?php echo gettext("Save order"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save order"); ?></strong></button></p>
</form>
	<ul class="iconlegend">
	<li><img src="../../images/pass.png" alt="" /><img	src="../../images/action.png" alt="" /><?php echo gettext("Show/hide"); ?></li>
	<li><img src="../zenpage/images/view.png" alt="" /><?php echo gettext("View"); ?></li>
	<li><img src="../../images/fail.png" alt="" /><?php echo gettext("Delete"); ?></li>
	</ul>	
</div>
<script type="text/javascript">
jQuery( function($) {
$('#left-to-right').NestedSortable(
	{
		accept: 'page-item1',
		noNestingClass: "no-nesting",
		opacity: 0.4,
		helperclass: 'helper',
		onChange: function(serialized) {
			$('#left-to-right-ser')
			.html("<input name='order' size='100' maxlength='1000' type='hidden' value="+ serialized[0].hash +">");
		},
		autoScroll: true,
		handle: '.sort-handle'
	}
);
});
</script>

<?php printAdminFooter(); ?>

</body>
</html>
