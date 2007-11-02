<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
	<?php zenJavascript(); ?>
</head>

<body>
<?php printAdminToolbox(); ?>

<div id="main">

	<div id="gallerytitle">
    <?php if (getOption('Allow_search')) {  printSearchForm(); } ?>
		<h2><?php echo getGalleryTitle(); ?></h2>
	</div>
    
    <div id="padbox">
    
		<div id="albums">
			<?php while (next_album()): ?>
			<div class="album">
        		<div class="thumb">
					<a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumThumbImage(getAlbumTitle()); ?></a>
       			 </div>
        		<div class="albumdesc">
					<h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
         			<small><?php printAlbumDate(""); ?></small>
					<p><?php printAlbumDesc(); ?></p>
				</div>
				<p style="clear: both; "></p>
			</div>
			<?php endwhile; ?>
		</div>
	
		<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
        
        
	</div>

</div>

<div id="credit"><?php printRSSLink('Gallery','','RSS', ' | '); ?> <a href="?p=archive">Archive View</a> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

</body>
</html>
