<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); $firstPageImages = normalizeColumns('2', '6');?>
<?php header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo  getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2>
		<?php printHomeLink('', ' | '); ?>
		<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo gettext('Gallery Index');?></a> | 
		<em><?php echo gettext('Contact us'); ?></em>
		</h2>
	</div>

<h3><?php echo gettext('Contact us.') ?></h3>

<?php  printContactForm();  ?>

</div>
<?php if (function_exists('printLanguageSelector')) { printLanguageSelector(); } ?>

<div id="credit">
<?php printZenphotoLink(); ?>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
