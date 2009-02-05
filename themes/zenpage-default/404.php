<?php setThemeDomain("zenpage-default"); if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Object not found"); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
</head>

<body>

<div id="main">

	<div id="header">
		<h1><?php echo getGalleryTitle();?></h1>
		</div>

		<div id="content">
		<div id="breadcrumb">
	<h2><a href="<?php echo getGalleryIndexURL(); ?>">Index</a> &raquo; <strong><?php echo gettext("Object not found"); ?></strong></h2>
	</div>
	
	<div id="content-error">
	
		<div class="errorbox">
 		<?php
		echo gettext("The Zenphoto object you are requesting cannot be found.");
		if (isset($album)) {
			echo '<br />'.gettext("Album").': '.sanitize($album);
		}
		if (isset($image)) {
			echo '<br />'.gettext("Image").': '.sanitize($image);
		}
		if (isset($obj)) {
			echo '<br />'.gettext("Theme page").': '.substr(basename($obj),0,-4);
		}
 		?>
		</div>
	
</div> 
		


<div id="footer">
<?php include("footer.php"); ?>
</div>



</div><!-- content -->

</div><!-- main -->
<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>
</body>
</html>
