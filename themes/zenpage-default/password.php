<?php setThemeDomain("zenpage-default"); if (!defined('WEBPATH')) die();
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Password required"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
</head>

<body>

<div id="main">

	<div id="header">
		<h1><?php echo getGalleryTitle();?></h1>
		</div>

		<div id="content">
		<div id="breadcrumb">
	<h2><a href="<?php echo getGalleryIndexURL(); ?>">Index</a> &raquo; <strong><?php echo gettext("A password is required for the page you requested"); ?></strong></h2>
	</div>
	
	<div id="content-error">
	
		<div class="errorbox">
		<?php printPasswordForm(NULL, false); ?>
		</div>
	
</div> 
		


<div id="footer">
<?php include("footer.php"); ?>
</div>



</div><!-- content -->

</div><!-- main -->
<?php printAdminToolbox(); ?>
</body>
</html>
