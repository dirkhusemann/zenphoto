<?php require ('customfunctions.php'); 
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php printGalleryTitle(); ?> | <?php echo gettext('Object not found'); ?></title>
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
				<span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a></span>  | <?php echo gettext('Object not found'); ?>
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
	

	<!-- Footer -->
	<div class="footlinks">
		<small><?php printThemeInfo(); ?></small>
		<a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>"><?php echo gettext('Powered by').' '; ?><font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a><br/>
	</div>
		
	<?php printAdminToolbox(); ?>

</body>
</html>