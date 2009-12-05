<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); ?>
<?php header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Password required"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2>
		<span>
		<?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo getGalleryTitle();?></a>
		</span> | <?php echo gettext("A password is required for the page you requested"); ?>
		</h2>
	</div>

		<div id="padbox">
		<?php printPasswordForm(NULL, false); ?>
	</div>

</div>

<div id="credit">
	<?php
	if (!zp_loggedin() && function_exists('printRegistrationForm') && getOption('gallery_page_unprotected_register')) {
		echo '<p>';
		printCustomPageURL(gettext('Register for this site'), 'register', '', '<br />');
		echo '</p>';
	}
	?>
	<?php printZenphotoLink(); ?>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
