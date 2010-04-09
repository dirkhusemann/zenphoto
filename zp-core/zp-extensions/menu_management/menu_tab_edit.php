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
<?php 
$_zp_gallery = new Gallery();
$menuset = checkChosenMenuset();
if(isset($_GET['add'])) { 
	printMenuEditPageJS();
} 
?>
   
</head>
<body>
<?php	printLogoAndLinks(); ?>
<div id="main">
<?php
printTabs("menu");
?>
<div id="content">
<?php

$result = "";
if(isset($_GET['id'])) {
	$result = getItem(sanitize($_GET['id']));
}
if(isset($_GET['update'])) {
	$result = updateItem();
}
if(isset($_GET['save'])) {
	$result = addItem();
}
if(isset($_GET['del'])) {
	deleteItem();
}
if(isset($_GET['add'])) {
	$headline = gettext("Menu Management: Add Menu Item");
} else {
	$headline = gettext("Menu Management: Edit Custom Item");
}
?>
<h1><?php echo $headline; ?></h1>
<div class="box" style="padding:15px; margin-top: 10px">
<?php if(is_array($result)) { ?>
<form method="post" action="menu_tab_edit.php?update" name="update">
<input type="hidden" name="id" value="<?php if(is_array($result)) { echo $result['id']; };?>" />
<input type="hidden" name="link-old" type="text" id="link-old" value="<?php echo $result['link'];?>" />
<input type="hidden" name="type" id="type" value="<?php echo $result['type'];?>" />
<input type="hidden" name="menuset" id="menuset" value="<?php echo $menuset; ?>" />
<?php } else { ?>
	<?php printTypeSelector(); ?>
	<form method="post" id="add" name="add" action="menu_tab_edit.php?save" style="display: none">
	<input name="type" type="hidden" id="type" size="48" value="" />
<?php } ?>
<table style="width: 80%">
	<tr style="vertical-align: top">
		<td style="width: 13%"><?php echo gettext("Type:"); ?></td>
		<td id="selector"></td>
	</tr>
	<tr style="vertical-align: top";>
		<td><?php echo gettext("Description:"); ?></td>
		<td id="description"></td>
	</tr>
	<tr> 
      <td><span id="titlelabel"><?php echo gettext("Title:"); ?></span></td>
		<td>
		<span id="titleinput"><?php print_language_string_list('',"title",false); ?></span>
		<?php printAlbumsSelector(); ?>
		<?php printZenpagePagesSelector(); ?>
		<?php printZenpageNewsCategorySelector(); ?>
		</td>
	</tr>
	<tr>
		<td><span id="link_label"></span></td>
		<td><input name="link" type="text" id="link" size="48" value="" /></td>
	</tr>

	<tr>
		<td><label id="show_visible" for="show" style="display: inline">
			<input name="show" type="checkbox" id="show" value="1" <?php if (is_array($result) AND $result['show'] == "1") { echo "checked='checked'"; } ?> style="display: inline" />
			<?php echo gettext("visible"); ?></label>
		</td>
	</tr>
	<tr>
		<td colspan="2"><?php printMenuSetSelector(false); echo gettext("Menu set"); ?></td>
	</tr>     
</table>
<p class="buttons">
<button type="submit" title="<?php echo gettext("Save"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
<button type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p>
<br clear="all" /><br />
</form>
</div>
</div>
<?php printAdminFooter(); ?>

</body>
</html>
