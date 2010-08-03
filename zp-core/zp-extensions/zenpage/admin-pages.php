<?php
/**
 * zenpage admin-pages.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
define("OFFSET_PATH",4); 
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/admin-globals.php');
require_once("zenpage-admin-functions.php");

admin_securityChecks(ZENPAGE_PAGES_RIGHTS, currentRelativeURL(__FILE__));

$reports = array();
// update page sort order
if(isset($_POST['update'])) {
	XSRFdefender('update');	
	processZenpageBulkActions('pages', $reports);
	updatePageSortorder($reports);
}
// remove the page from the database
if(isset($_GET['del'])) {
	XSRFdefender('delete');
	$msg = deletePage();
	if (!empty($msg)) {
		$reports[] = $msg;
	}
}
// publish or un-publish page by click
if(isset($_GET['publish'])) {
	XSRFdefender('update');
	publishPageOrArticle('page',$_GET['id']);
}
if(isset($_GET['skipscheduling'])) {
	XSRFdefender('update');
	skipScheduledPublishing('page',$_GET['id']);
}
if(isset($_GET['commentson'])) {
	XSRFdefender('update');
	enableComments('page');
}
if(isset($_GET['hitcounter'])) {
	XSRFdefender('hitcounter');
	resetPageOrArticleHitcounter('page');
}
printAdminHeader();
printSortableHead();
zenpageJSCSS();
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	var deleteArticle = "<?php echo gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!"); ?>";
	var deletePage = "<?php echo gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE!"); ?>";			
	function confirmAction() {
		if ($('#checkallaction').val() == 'deleteall') {
			return confirm('<?php echo js_encode(gettext("Are you sure you want to delete the checked items?")); ?>');
		} else {
			return true;
		}
	}
	// ]]> -->
</script>

</head>
<body>
<?php
	printLogoAndLinks();
	echo '<div id="main">';
	printTabs('pages');
	echo '<div id="content">';
	foreach ($reports as $report) {
		echo $report;
	}
	?>
<h1><?php echo gettext('Pages'); ?><span class="zenpagestats"><?php printPagesStatistic();?></span></h1>
<form action="admin-pages.php" method="post" name="update" onsubmit="return confirmAction();">
	<?php XSRFToken('update');?>

<div>
<p><?php echo gettext("Select a page to edit or drag the pages into the order, including subpage levels, you wish them displayed."); ?></p>
<p class="notebox"><?php echo gettext("<strong>Note:</strong> Subpages of password protected pages inherit the protection."); ?></p>
<p class="buttons">
	<button type="submit" title="<?php echo gettext("Apply"); ?>">
		<img src="../../images/pass.png" alt="" />
		<strong><?php echo gettext("Apply"); ?></strong>
	</button>
	<?php 
	if (zp_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
		?>
		<strong>
			<a href="admin-edit.php?page&amp;add&amp;XSRFToken=<?php echo getXSRFToken('add')?>" title="<?php echo gettext('Add Page'); ?>">
			<img src="images/add.png" alt="" /> <?php echo gettext('Add Page'); ?></a>
		</strong>
		<?php
	}
	?>
</p>
</div>
<br clear="all" /><br clear="all" />
<table class="bordered" style="margin-top: 10px">
 <tr>
	<th><?php echo gettext('Edit this page'); ?>
	<?php
	  	$checkarray = array(
			  	gettext('*Bulk actions*') => 'noaction',
			  	gettext('Delete') => 'deleteall',
			  	gettext('Set to published') => 'showall',
			  	gettext('Set to unpublished') => 'hideall',
			  	gettext('Disable comments') => 'commentsoff',
			  	gettext('Enable comments') => 'commentson',
			  	gettext('Reset hitcounter') => 'resethitcounter',
	  	);
	  	?>
	  	<span style="float:right">
	  	<select name="checkallaction" id="checkallaction" size="1">
	  	<?php generateListFromArray(array('noaction'), $checkarray,false,true); ?>
			</select>
			</span>
	</th>
 </tr>
  <tr>
	<td class="subhead">
		<label style="float: right"><?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
		</label>
	</td>
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
	<p class="buttons">
		<button type="submit" title="<?php echo gettext('Apply'); ?>">
			<img src="../../images/pass.png" alt="" />
			<strong><?php echo gettext('Apply'); ?></strong>
		</button>
	</p>

</form>
<?php printZenpageIconLegend(); ?>
</div>
<script type="text/javascript">
	// <!-- <![CDATA[ 
	jQuery( function($) {
	$('#left-to-right').NestedSortable(
		{
			accept: 'page-item1',
			noNestingClass: "no-nesting",
			opacity: 0.4,
			helperclass: 'helper',
			onchange: function(serialized) {
				$('#left-to-right-ser')
				.html("<input name='order' type='hidden' value="+ serialized[0].hash +" />");
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
