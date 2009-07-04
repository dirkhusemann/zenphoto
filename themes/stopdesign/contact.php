<?php
if (!defined('WEBPATH')) die();
require_once('normalizer.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> <?php echo gettext("Contact form"); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php
	printRSSHeaderLink('Gallery','Gallery RSS');
	setOption('thumb_crop_width', 85, false);
	setOption('thumb_crop_height', 85, false);
	?>
</head>

<body class="archive">
	<?php echo getGalleryTitle(); ?>
	<div id="content">
		<h1><?php printGalleryTitle(); ?> <em><?php echo gettext('Contact'); ?></em></h1>
		<div class="galleries">
		<h2><?php echo gettext('Please use the form below to contact us.') ?></h2>
		<?php  printContactForm();  ?>
	</div>
</div>

<p id="path">
	<?php printHomeLink('', ' &gt; '); ?>
	<a href="<?php echo htmlspecialchars(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> &gt;
	<?php echo getGalleryTitle();?> 
	&gt; <em><?php echo gettext('Contact'); ?></em>
</p>

<div id="footer">
	<p>
		<?php echo gettext('<a href="http://stopdesign.com/templates/photos/">Photo Templates</a> from Stopdesign');?>.
		<?php printZenphotoLink(); ?>
	</p>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
