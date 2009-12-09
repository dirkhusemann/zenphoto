<?php if (!defined('WEBPATH')) die(); $firstPageImages = normalizeColumns('2', '6');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Archive View"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); ?>
</head>

<body>

<div id="main">

		<div id="header">
		<h1><?php printGalleryTitle(); ?></h1>
		<?php if (getOption('Allow_search')) {  printSearchForm("","search","",gettext("Search gallery")); } ?>
		</div>
	
	
		<div id="breadcrumb">
		<h2><a href="<?php echo getGalleryIndexURL(false);?>" title="<?php gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> &raquo; <strong><?php echo gettext("Archive View"); ?></strong>
			</h2>
			</div>

<div id="content">
	<div id="content-left">
			<div id="archive">
			<h3>Gallery archive</h3>
			<?php printAllDates(); ?>
			<hr />
			<?php if(function_exists("printNewsArchive")) { ?>
			<h3>News archive</h3>
			<?php printNewsArchive("archive"); ?>
			<hr />
			<?php } ?>
			</div>
					<h3>Popular Tags</h3>
			<div id="tag_cloud">
 			<?php printAllTagsAs('cloud', 'tags'); ?>
				</div>
 			
	</div><!-- content left-->
	
	
	
	<div id="sidebar">
		<?php include("sidebar.php"); ?>
	</div><!-- sidebar -->

	<div id="footer">
	<?php include("footer.php"); ?>
	</div>
</div><!-- content -->

</div><!-- main -->
<?php printAdminToolbox(); ?>
</body>
</html>