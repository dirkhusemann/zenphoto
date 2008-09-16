<?php require ('customfunctions.php');
define('ALBUMCOLUMNS', 3);
define('IMAGECOLUMNS', 5);
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
normalizeColumns(ALBUMCOLUMNS, IMAGECOLUMNS);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext('Archive'); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
</head>

<body onload="blurAnchors()">

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
				<span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a></span>  | <?php echo gettext('Archive View'); ?>
			</div>
		</div>

		<!-- Random Image -->
		<?php printHeadingImage(getRandomImages()); ?>
	</div>

	<!-- Wrap Main Body -->
	<div id="content">
		<small>&nbsp;</small>
		<div id="main">
		<?php if (!checkForPassword()) {?>
			<!-- Date List -->
			<div id="archive"><p><?php echo gettext('Images By Date'); ?></p><?php printAllDates('archive', 'year', 'month', 'desc'); ?></div>
			<div id="tag_cloud"><p><?php echo gettext('Popular Tags'); ?></p><?php printAllTagsAs('cloud', 'tags'); ?></div>
		<?php } ?>
		</div>
	</div>


	<!-- Footer -->
	<div class="footlinks">
		<small><?php printThemeInfo(); ?></small>
		<?php echo gettext('Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album"><font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a>'); ?>
		<?php
		if (function_exists('printUserLogout')) {
			printUserLogout('<br />', '', true);
		}
		?>
	</div>

	<?php printAdminToolbox(); ?>

</body>
</html>