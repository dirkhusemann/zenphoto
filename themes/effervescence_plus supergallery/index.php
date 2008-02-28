<?php 
require ('customfunctions.php'); 
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');normalizeColumns(3, 5);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
	<?php 
	printRSSHeaderLink('Gallery','Gallery RSS'); 
	zenJavascript(); 
	?>
</head>

<body onload="blurAnchors()">

	<!-- Wrap Header -->
	<div id="header">

		<!-- Logo -->
		<div id="gallerytitle">
			<div id="logo">
			<?php printLogo();
			?>
			</div>
		</div>
	<!-- Crumb Trail Navigation -->
		<div id="wrapnav">
			<div id="navbar">
				<span><?php printHomeLink('', ' | '); printGalleryTitle(); ?>
			</div>
		</div>
	</div>

	<!-- Random Image -->
	<?php printGalleryHeadingImage(); ?>

	<!-- Wrap Main Body -->
	<div id="content">
		<div id="main">

		<!-- Album List -->
		<ul id="albums">
			<?php while (next_gallery()) { ?>
			<li>
				<div class="imagethumb">
					<a href="<?php echo getSubgalleryURL();?>" title="View the gallery: <?php echo getSubgalleryTitle(); ?>">
						<?php printCustomGalleryThumbImage(getCustomGalleryDesc(), null, 180, null, 180, 80); ?>
 						</a>
				</div>
				<h4><a href="<?php echo getSubgalleryURL();?>" title="View the gallery: 
				 			<?php echo getSubgalleryTitle(); ?>">
				 			<?php printSubgalleryTitle(); ?></a></h4>
			</li>
			<?php } ?>
		</ul>
		<div class="clearage"></div>
	
	</div>

	<!-- Footer -->
	<div class="footlinks">
		<small>
		<?php printThemeInfo(); ?>
		</small>
		<a href="http://www.zenphoto.org" title="A simpler web photo album">Powered by <font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a><br/>
		<?php printRSSLink('Gallery','', 'Gallery RSS', ''); ?>
	</div>
		
		<?php printAdminToolbox(); ?>

</body>
</html>