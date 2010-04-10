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
if(isset($_GET['edit'])) {
	$headline = gettext("Menu Management: Edit Custom Item");
} else {
	$headline = gettext("Menu Management: Add Menu Items");
}
 
?>
<script type="text/javascript">
<?php
if (is_array($result)) {
	?>
	$(document).ready(function() {
			handleSelectorChange('<?php echo $result['type']; ?>');
		});
	<?php
} else {
	?>
	$(document).ready(function() {
		$('#albumselector,#pageselector,#categoryselector,#titleinput').hide();
		$('#typeselector').change(function() {
				$('input').val(''); // reset all input values so we do not carry them over from one type to another
				$('#link').val('');
				handleSelectorChange($(this).val());
			});
		});
	<?php
}
?>
function handleSelectorChange(type) {
	$('#add,#titlelabel,V,#link_row,#link,#link_label,#visible_row,#show_visible').show();
	$('#type').val(type);
	$('#link_label').html('<?php echo gettext('URL'); ?>');
	switch(type) {
		case 'all_items':
			$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link_row,#link,#link_label,#visible_row').hide();
			$('#selector').html('<?php echo gettext("All menu items"); ?>');
			$('#description').html('<?php echo gettext('This adds menu items for all Zenphoto objects. (It creates a "default" menuset.)'); ?>');
			break;
		case "galleryindex":
			$('#albumselector,#pageselector,#categoryselector,#link_row,#link,#link_label').hide();
			$('#selector').html('<?php echo gettext("Gallery index"); ?>');
			$('#description').html('<?php echo gettext("This is the normal Zenphoto gallery index page."); ?>');
			$('#link').attr('disabled',true);
			$('#titleinput').show();
			$('#link').val('<?php echo WEBPATH; ?>/');
			break;
		case 'all_albums':
			$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link_row,#link,#link_label,#visible_row').hide();
			$('#selector').html('<?php echo gettext("All Albums"); ?>');
			$('#description').html('<?php echo gettext("This adds menu items for all Zenphoto albums."); ?>');
			break;
		case 'album':
			$('#pageselector,#categoryselector,#titleinput').hide();
			$('#selector').html('<?php echo gettext("Album"); ?>');
			$('#description').html('<?php echo gettext("This is for Zenphoto albums. Naturally you cannot change anything for these items here here."); ?>');
			$('#link').attr('disabled',true);
			$('#albumselector').show();
			$('#albumselector').change(function() {
				$('#link').val($('#albumselector').val());
			});
			break;
		case 'all_zenpagepages':
			$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link_row,#link,#link_label,#visible_row').hide();
			$('#selector').html('<?php echo gettext("All Zenpage pages"); ?>');
			$('#description').html('<?php echo gettext("This adds menu items for all Zenppage pages."); ?>');
			break;
		case 'zenpagepage':
			$('#albumselector,#categoryselector,#titleinput').hide();
			$('#selector').html('<?php echo gettext("Zenpage page"); ?>');
			$('#description').html('<?php echo gettext("This is for Zenpage CMS pages. Naturally you cannot change anything for these items here."); ?>');
			$('#link').attr('disabled',true);
			$('#pageselector').show();
			$('#pageselector').change(function() {
				$('#link').val($('#pageselector').val());
			});
			break;
		case 'zenpagenewsindex':
			$('#albumselector,#pageselector,#categoryselector,#link,#link_label').hide();
			$('#selector').html('<?php echo gettext("Zenpage news index"); ?>');
			$('#description').html('<?php echo gettext("This is for news loop of the Zenpage CMS plugin."); ?>');
			$('#link').attr('disabled',true);
			$('#titleinput').show();
			$('#link').val('<?php echo rewrite_path(ZENPAGE_NEWS,'?p='.ZENPAGE_NEWS); ?>');
			break;	
		case 'all_zenpagecategorys':
			$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link_row,#link,#link_label,#show_visible').hide();
			$('#selector').html('<?php echo gettext("All Zenpage categories"); ?>');
			$('#description').html('<?php echo gettext("This adds menu items for all Zenppage categories."); ?>');
			break;
		case 'zenpagecategory':
			$('#albumselector,#pageselector,#titleinput').hide();
			$('#selector').html('<?php echo gettext("Zenpage news category"); ?>');
			$('#description').html('<?php echo gettext("This is for the news categories for Zenpage CMS news articles. Naturally you cannot change anything for these items here."); ?>');
			$("#link").attr('disabled',true);
			$('#categoryselector').show();
			$('#categoryselector').change(function() {
				$('#link').val($('#categoryselector').val());
			});
			break;
		case 'custompage':
			$('#albumselector,#pageselector,#categoryselector').hide();
			$('#selector').html('<?php echo gettext("Custom page"); ?>');
			$('#description').html('<?php echo gettext("This refers to the custom theme page feater which is described on the theming tutorial. Just enter a custom title and the file name (e.g. archive.php) and the correct URL is created automatically."); ?>');
			$('#link').removeAttr('disabled');
			$('#link_label').html('<?php echo gettext('Script page'); ?>');
			$('#titleinput').show();
			break;
		case "customlink":
			$('#albumselector,#pageselector,#categoryselector').hide();
			$('#selector').html('<?php echo gettext("Custom link"); ?>');
			$('#description').html('<?php echo gettext("This can be be a external link for example so the full URL is recommended (e.g http://www.domain.com)."); ?>');
			$('#link').removeAttr('disabled');
			$('#link_label').html('<?php echo gettext('URL'); ?>');
			$('#titleinput').show();
			break;
		case "":
			$("#selector").html("");
			$("#add").hide();
			break;
	}
};
</script>
<h1><?php echo $headline; ?></h1>
<p class="buttons"><strong><a href="menu_tab.php?menuset=<?php echo $menuset; ?>" title="<?php echo gettext("Back"); ?>"><img	src="../../images/arrow_left_blue_round.png" alt="" /><?php echo gettext("Back"); ?></a></strong></p>
<br clear="all" /><br />
<div class="box" style="padding:15px; margin-top: 10px">
<?php
if(is_array($result)) {
	?>
	
	
	<form method="post" action="menu_tab_edit.php?update" name="update">
	<input type="hidden" name="id" value="<?php if(is_array($result)) { echo $result['id']; };?>" />
	<input type="hidden" name="link-old" type="text" id="link-old" value="<?php echo $result['link'];?>" />
	<input type="hidden" name="type" id="type" value="<?php echo $result['type'];?>" />
	<input type="hidden" name="menuset" id="menuset" value="<?php echo $menuset; ?>" />
	<?php
} else {
	?>
	<select id="typeselector" name="typeselector">
		<option value=""><?php echo gettext("*Select the type of the menus item you wish to add*"); ?></option>
		<option value="all_items"><?php echo gettext("All menu items"); ?></option>
		<option value="galleryindex"><?php echo gettext("Gallery index"); ?></option>
		<option value="all_albums"><?php echo gettext("All Albums"); ?></option>
		<option value="album"><?php echo gettext("Album"); ?></option>
		<?php
		if(getOption('zp_plugin_zenpage')) {
			?>
			<option value="all_zenpagepages"><?php echo gettext("All Zenpage pages"); ?></option>
			<option value="zenpagepage"><?php echo gettext("Zenpage page"); ?></option>
			<option value="zenpagenewsindex"><?php echo gettext("Zenpage news index"); ?></option>
			<option value="all_zenpagecategorys"><?php echo gettext("All Zenpage news categorys"); ?></option>
			<option value="zenpagecategory"><?php echo gettext("Zenpage news category"); ?></option>
			<?php
		}
		?>
		<option value="custompage"><?php echo gettext("Custom theme page"); ?></option>
		<option value="customlink"><?php echo gettext("Custom link"); ?></option>
	</select>
	<form method="post" id="add" name="add" action="menu_tab_edit.php?save" style="display: none">
	<input name="type" type="hidden" id="type" size="48" value="" />
<?php
}
?>
<table style="width: 80%">
<?php
if(!is_array($result)) {
	$result = array('id'=>NULL, 'title'=>'', 'link'=>'', 'show'=>1,'type'=>NULL);
	?>
	<tr>
		<td colspan="2"><?php printMenuSetSelector(false); echo gettext("Menu set"); ?></td>
	</tr>     
	<?php
}
?>
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
		<span id="titleinput"><?php print_language_string_list($result['title'],"title",false); ?></span>
		<?php printAlbumsSelector(); ?>
		<?php printZenpagePagesSelector(); ?>
		<?php printZenpageNewsCategorySelector(); ?>
		</td>
	</tr>
	<tr id="link_row">
		<td><span id="link_label"></span></td>
		<td><input name="link" type="text" id="link" size="48" value="<?php echo htmlspecialchars($result['link']); ?>" /></td>
	</tr>
	<tr id="visible_row">
		<td><label id="show_visible" for="show" style="display: inline">
			<input name="show" type="checkbox" id="show" value="1" <?php if ($result['show'] == 1) { echo "checked='checked'"; } ?> style="display: inline" />
			<?php echo gettext("visible"); ?></label>
		</td>
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
