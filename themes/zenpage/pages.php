<?php if (!defined('WEBPATH')) die(); 
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo getBareGalleryTitle(); ?><?php if(!isset($ishomepage)) { echo " | ".getBarePageTitle(" | "); } ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
	<?php printZenpageRSSHeaderLink("News","", "Zenpage news", ""); ?>
	<?php zenJavascript(); ?>
</head>

<body>

<div id="main">

	<div id="header">
			<h1><?php printGalleryTitle(); ?></h1>
			<?php if (isset($ishomepage) AND getOption('Allow_search')) {  printSearchForm("","search","",gettext("Search gallery")); } ?>
		</div>
				
<div id="content">

	<div id="breadcrumb">
	<h2><a href="<?php echo getGalleryIndexURL(false); ?>"><?php echo gettext("Index"); ?></a><?php if(!isset($ishomepage)) { printParentPagesBreadcrumb(" &raquo; ",""); } ?><strong><?php if(!isset($ishomepage)) { printPageTitle(" &raquo; "); } ?></strong>
	</h2>
	</div>
<div id="content-left">
<h2><?php printPageTitle(); ?></h2>
<?php 
printPageContent(); 
printCodeblock(1); 
printTags('links', gettext('<strong>Tags:</strong>').' ', 'taglist', ', '); 
?>
<br style="clear:both;" /><br />
<?php
if (function_exists('printRating')) { printRating(); }
?>

<?php 
if (function_exists('printCommentForm')) { ?>
	<div id="comments">
	<?php printCommentForm(); ?>
	</div>
	<?php } ?>
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