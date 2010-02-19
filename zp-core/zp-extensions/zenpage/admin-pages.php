<?php
/**
 * zenpage admin-pages.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
require_once('zp-functions.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo gettext('zenphoto administration'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php zenpageJSCSS(); ?>
<script type="text/javascript" src="../../js/nestedsortables/interface-1.2.js"></script>
<!--Nested Sortables-->
<script type="text/javascript" src="../../js/nestedsortables/inestedsortable.js"></script>
</head>
<body>
<?php
	printLogoAndLinks();
	echo '<div id="main">';
	printTabs('pages');
	echo '<div id="content">';
	checkRights('pages');
// update page sort order
if(isset($_POST['update'])) {
	updatePageSortorder();
}
// remove the page from the database
if(isset($_GET['del'])) {
	deletePage();
}
// publish or unpublish page by click
if(isset($_GET['publish'])) {
	publishPageOrArticle('page',$_GET['id']);
}
if(isset($_GET['skipscheduling'])) {
	skipScheduledPublishing('page',$_GET['id']);
}
if(isset($_GET['commentson'])) {
	enableComments('page');
}
if(isset($_GET['hitcounter'])) {
	resetPageOrArticleHitcounter('page');
}
?>
<h1><?php echo gettext('Pages'); ?><span class="zenpagestats"><?php printPagesStatistic();?></span></h1>
 <form action="admin-pages.php" method="post" name="update">

<div>
<p><?php echo gettext("Select a page to edit or drag the pages into the order, including sub page levels, you wish them displayed."); ?></p>
<p class="buttons"><button type="submit" title="<?php echo gettext("Save order"); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext("Save order"); ?></strong></button></p>
<p class="buttons"><strong><a href="admin-edit.php?page&amp;add" title="<?php echo gettext('Add Page'); ?>"><img src="images/add.png" alt="" /> <?php echo gettext('Add Page'); ?></a></strong>
<strong><a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER; ?>/tiny_mce/plugins/ajaxfilemanager/ajaxfilemanager.php?language=<?php echo getLocaleForTinyMCEandAFM(); ?>" class="colorbox"><img src="images/folder.png" alt="" /> <?php echo gettext("Manage files"); ?></a></strong>
</p>
</div>
<br clear="all" /><br clear="all" />
<table class="bordered" style="margin-top: 10px">
 <tr>
	<th><strong><?php echo gettext('Edit this page'); ?></strong></th>
 </tr>
 <tr><td colspan="1" style="padding: 0;">
	<ul id="left-to-right" class="page-list">
	<?php
	$pages = getPages(false);
	$toodeep = printPagesList($pages);
	?>
	</ul>
 </td></tr>
 </table>
	<?php
	if ($toodeep) {
			echo '<div class="errorbox">';
			echo  '<h2>'.gettext('The sort position of the indicated pages cannot be recorded because the nesting is too deep. Please move them to a higher level and save your order.').'</h2>';
			echo '</div>';
	}
	?>
<div id='left-to-right-ser'><input type="hidden" name="order" size="30" maxlength="1000" /></div>
				<input name="update" type="hidden" value="Save Order" />
				<p class="buttons"><button type="submit" title="<?php echo gettext('Save order'); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext('Save order'); ?></strong></button></p>

			
</form>
<?php printZenpageIconLegend(); ?>
</div>
<script type="text/javascript">
// <![CDATA[ 
jQuery( function($) {
$('#left-to-right').NestedSortable(
	{
		accept: 'page-item1',
		noNestingClass: "no-nesting",
		opacity: 0.4,
		helperclass: 'helper',
		onChange: function(serialized) {
			$('#left-to-right-ser')
			.html("<input name='order' type='hidden' value="+ serialized[0].hash +" />");
		},
		autoScroll: true,
		handle: '.sort-handle'
	} 
);
});
// ]]> 
</script>
</div>
<?php printAdminFooter(); ?>

</body>
</html>
