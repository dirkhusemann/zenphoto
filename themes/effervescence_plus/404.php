<?php
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zp_apply_filter('theme_head'); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext('Object not found'); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo pathurlencode($zenCSS); ?>" type="text/css" />
</head>

<body>
<?php zp_apply_filter('theme_body_open'); ?>

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
				<a href="<?php echo html_encode(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> | 
				<?php	
				}					
				?>
				<a href="<?php echo html_encode(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a></span>  | 
				<?php echo gettext('Object not found'); ?>
			</div>
		</div>

	</div>

	<!-- Wrap Main Body -->
	<div id="content">
		<small>&nbsp;</small>
		<div id="main">
		<?php
		echo gettext("The Zenphoto object you are requesting cannot be found.");
		if (isset($album)) {
			echo '<br />'.sprintf(gettext('Album: %s'),sanitize($album));
		}
		if (isset($image)) {
			echo '<br />'.sprintf(gettext('Image: %s'),sanitize($image));
		}
		if (isset($obj)) {
			echo '<br />'.sprintf(gettext('Page: %s'),substr(basename($obj),0,-4));
		}
		?>
		</div>
	</div>

	<!-- Footer -->
	<div class="footlinks">
		<small><?php printThemeInfo(); ?></small>
		<?php printZenphotoLink(); ?>
		<br />
	</div>
	
<?php printAdminToolbox(); ?>

</body>
</html>