<?php if (!defined('WEBPATH')) die(); normalizeColumns(1, 7);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php printGalleryTitle(); ?></title>
		<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
		<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
		<?php zenJavascript(); ?>
</head>
<body>
<div id="main">
		<div id="gallerytitle">
			<h2><?php printHomeLink('', ' | '); echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(); } ?></h2>
		</div>
	
		<hr />
		<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
	
		<div id="albums">
			<?php while (next_album()): ?>
	
 			<div class="album">
					<div class="albumthumb">
							<a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
						<?php printAlbumThumbImage(getAlbumTitle()); ?></a>
						</div>
					<div class="albumtitle">
							<h3><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
						<?php printAlbumTitle(); ?></a></h3> <?php printAlbumDate(); ?>
						</div>
					<div class="albumdesc"><?php printAlbumDesc(); ?></div>
			</div>
			<hr />
		
			<?php endwhile; ?>
		</div>
	
		<?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>
	
		<div id="credit"><?php printRSSLink('Gallery','','RSS', ''); ?> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | <a href="?p=archive">Archive View</a></div>

</div>

<?php printAdminToolbox(); ?>

</body>
</html>
