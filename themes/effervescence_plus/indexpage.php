<?php require_once ('customfunctions.php');
define('ALBUMCOLUMNS', 3);
define('IMAGECOLUMNS', 5);
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
normalizeColumns(ALBUMCOLUMNS, IMAGECOLUMNS);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getMainSiteName(); ?></title>
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
	<script src="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.js" type="text/javascript"></script>

</head>

<body onload="blurAnchors()">

	<!-- Wrap Everything -->
	<div id="main4">
		<div id="main2">

			<!-- Wrap Header -->
			<div id="galleryheader">
				<div id="gallerytitle">


					<!-- Logo -->
					<div id="logo2">
						<?php printLogo(); ?>
					</div>
				</div>

				<!-- Crumb Trail Navigation -->
				<div id="wrapnav">
					<div id="navbar">
						<span><?php echo getMainSiteName(); ?></span>
					</div>
				</div>
			</div>

			<!-- The Image -->
			<p align="center">
			<?php echo gettext('Picture of the day'); ?>
			</p>
			<?php
	 			makeImageCurrent(getRandomImages(true));
				$s = getDefaultWidth() + 22;
				$wide = "style=\"width:".$s."px;";
				$s = getDefaultHeight() + 22;
				$high = " height:".$s."px;\"";
			?>
			<div id="image" <?php echo $wide.$high; ?>>
				<div id="image_container">
					<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>">
						<?php printDefaultSizedImage(gettext('Visit the photo gallery')); ?>
					</a>
				</div>
				<p align="center">
				<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo gettext('Visit the photo gallery');?></a>
				</p>
			</div>
			<br clear="all" />
		</div>

		<!-- Image Description -->
		<div id="description">
		<?php echo getOption('Gallery_description'); ?>
		</div>
	</div>

	<!-- Footer -->
	<div class="footlinks">
		<?php
			printThemeInfo();
		?>
		<a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>"><?php echo gettext('Powered by').' ';?>
		<font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps"><font face="Arial Black" size="1">photo</font></span></a>
		<?php
		if (function_exists('printUserLogout')) {
			printUserLogout('<br />', '', true);
		}
		?>
	</div>

	<!-- Administration Toolbox -->
	<?php printAdminToolbox(); ?>

</body>
</html>
