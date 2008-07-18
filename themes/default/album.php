<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?></title>
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a> | <?php printParentBreadcrumb(); ?></span> <?php printAlbumTitle(true);?></h2>
	</div>
		
		<div id="padbox">
	
		<?php printAlbumDesc(true); ?>
	
			<div id="albums">
			<?php while (next_album()): ?>
			<div class="album">
				
						<div class="thumb">
					<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?> <?php echo getAlbumTitle();?>"><?php printAlbumThumbImage(getAlbumTitle()); ?></a>
						</div>
				<div class="albumdesc">
					<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?> <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
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
				<div class="imagethumb"><a href="<?php echo htmlspecialchars(getImageLinkURL());?>" title="<?php echo htmlspecialchars(strip_tags(getImageTitle()),ENT_QUOTES);?>"><?php printImageThumb(getImageTitle()); ?></a></div>
			</div>
			<?php endwhile; ?>
	
		</div>
	
		<?php printPageListWithNav("&laquo; ".gettext("prev"), gettext("next")." &raquo;"); ?>
		<?php printTags('links', gettext('<strong>Tags:</strong>').' ', 'taglist', ''); ?>
				
	</div>
	<?php if (function_exists('printSlideShowLink')) printSlideShowLink(gettext('View Slideshow')); ?>	
	<?php if (function_exists('printAlbumRating')) { printAlbumRating(); }?>
		
</div>

<div id="credit"><?php printRSSLink('Album', '', gettext('Album RSS'), ''); ?> | <a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>?p=archive"><?php echo gettext("Archive View"); ?></a> | <?php echo gettext("Powered by"); ?> <a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>">zenphoto</a></div>

<?php printAdminToolbox(); ?>

</body>
</html>