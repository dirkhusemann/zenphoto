<?php 
if (!defined('WEBPATH')) die(); 
$themeResult = getTheme($zenCSS, $themeColor, 'light');
$firstPageImages = normalizeColumns('2', '6');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?></title>
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
	<?php zenJavascript(); ?>
</head>

<body>
<?php printAdminToolbox(); ?>

<div id="main">

	<div id="gallerytitle">
		<h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> | <?php printParentBreadcrumb(); ?></span> <?php printAlbumTitle(true);?></h2>
	</div>
    
    <div id="padbox">
	
		<?php printAlbumDesc(true); ?>
	
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
    
    	<div id="images">
			<?php while (next_image(false, $firstPageImages)): ?>
			<div class="image">
				<div class="imagethumb"><a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>"><?php printImageThumb(getImageTitle()); ?></a></div>
			</div>
			<?php endwhile; ?>
		</div>
	
		<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
        <?php printTags('links', 'Tags: ', 'taglist', ''); ?>
        
	</div>
	
</div>

<div id="credit"><?php printRSSLink('Album', '', 'Album RSS', ''); ?> | <a href="<?php echo getGalleryIndexURL();?>?p=archive">Archive View</a> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

</body>
</html>