<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><?php printHomeLink('', ' | '); echo getGalleryTitle(); ?></h2>
	</div>

<h3><?php echo gettext('Please use the form below to contact us.') ?></h3>

<?php  printContactForm();  ?>

</div>
<?php	if (function_exists('printContactForm')) printCustomPageURL(gettext('Contact us'), 'contact', '', '<br />');	?>
<?php if (function_exists('printLanguageSelector')) { printLanguageSelector(); } ?>

<div id="credit"><?php printRSSLink('Gallery','','RSS', ' | '); ?> <?php printCustomPageURL(gettext("Archive View"),"archive"); ?> | 
<?php printZenphotoLink(); ?>
<?php
if (function_exists('printUserLogout')) {
	printUserLogout(" | ");
}
?>
</div>

<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>

</body>
</html>
