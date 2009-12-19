<?php
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext('Password required'); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
</head>

<body>

	<!-- Wrap Header -->
	<div id="header">
		<div id="gallerytitle">

		<!-- Logo -->
			<div id="logo">
			<?php printLogo(); ?>
			</div>
		</div>

		<!-- Crumb Trail Navigation -->
		<div id="wrapnav">
			<div id="navbar">
				<span><?php printHomeLink('', ' | '); ?>
				<?php
			if (getOption('custom_index_page') === 'gallery') {
				?>
				<a href="<?php echo htmlspecialchars(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> | 
				<?php	
				}					
				?>
				<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a></span>  | 
				<?php echo gettext('A password is required for the page you requested'); ?>
			</div>
		</div>

	</div>

	<!-- Wrap Main Body -->
	<div id="content">
		<small>&nbsp;</small>
		<div id="main">
		<?php printPasswordForm(NULL, false); ?>
		</div>
	</div>

<?php printFooter(); ?>

</body>
</html>