<?php if (!defined('WEBPATH')) die(); normalizeColumns(1, 7);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
		<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
		<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); ?>
</head>
<body>
<div id="main">
		<div id="gallerytitle">
		<h2>
		<?php printHomeLink('', ' | '); ?>
		<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo gettext('Gallery Index');?></a> | 
		<?php echo gettext('Contact us') ?>
		</h2>
		</div>
		
		<h3><?php echo gettext('Contact us.') ?></h3>
		
		<?php  printContactForm();  ?>


		<?php if (function_exists('printLanguageSelector')) { printLanguageSelector(); } ?>

		<div id="credit"> 
		<?php printZenphotoLink(); ?>
		<?php
		if (function_exists('printUserLogout')) {
			printUserLogout(' | ', '', true);
		}
		?>
		</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
