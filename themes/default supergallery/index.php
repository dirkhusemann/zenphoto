<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); $firstPageImages = normalizeColumns('2', '6');?>
<?php require_once(SERVERPATH . "/" . ZENFOLDER . '/plugins/supergallery-functions.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
	<?php zenJavascript(); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><?php printHomeLink('', ' | '); echo getGalleryTitle(); ?></h2>
	</div>
		
		<div id="padbox">
		
		<div id="albums">
			<?php while (next_gallery()): ?>
			<div class="album">
						<div class="thumb">
 				<a href="<?php echo getSubgalleryURL();?>" title="View gallery: <?php echo getSubgalleryTitle();?>"><?php printSubgalleryThumbImage(getSubgalleryTitle()); ?></a>
 						</div>
						<div class="albumdesc">
					<h3><a href="<?php echo getSubgalleryURL();?>" title="View gallery: <?php echo getSubgalleryTitle();?>"><?php printSubgalleryTitle(); ?></a></h3>
 					<p><?php printSubgalleryDesc(); ?></p>
				</div>
				<p style="clear: both; "></p>
			</div>
			<?php endwhile; ?>
		</div>
		<br clear="all" />        
	</div>

</div>

<div id="credit"><?php printRSSLink('Gallery','','RSS', ' | '); ?> <a href="?p=archive">Archive View</a> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

<?php printAdminToolbox(); ?>

</body>
</html>
