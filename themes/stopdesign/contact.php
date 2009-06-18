<?php
if (!defined('WEBPATH')) die();
require_once('normalizer.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> <?php echo gettext("Archive"); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php
	printRSSHeaderLink('Gallery','Gallery RSS');
	setOption('thumb_crop_width', 85, false);
	setOption('thumb_crop_height', 85, false);
	?>
</head>

<body class="archive">
	<?php echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

<div id="content">

	<h1><?php printGalleryTitle(); echo ' | '.gettext('Contact'); ?></h1>

<h3><?php echo gettext('Please use the form below to contact us.') ?></h3>

<?php  printContactForm();  ?>

</div>

<p id="path">
	<?php printHomeLink('', ' > '); ?>
	<a href="<?php echo htmlspecialchars(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> &gt;
	<?php echo getGalleryTitle();?> 
	<?php echo gettext('Gallery Archive'); ?>
</p>

<div id="footer">
	<hr />
	<?php if (function_exists('printUserLogout')) { echo '<p>'; printUserLogout(""); echo '</p>'; } ?>
	<p>
		<?php echo gettext('<a href="http://stopdesign.com/templates/photos/">Photo Templates</a> from Stopdesign');?>.
		<?php printZenphotoLink(); ?>
	</p>
</div>

<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>

</body>
</html>
