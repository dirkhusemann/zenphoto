<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> | <?=getAlbumTitle();?></title>
	<link rel="stylesheet" href="<?= $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php zenJavascript(); ?>
	
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><span><a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a> | </span> <?php printAlbumTitle(true);?></h2>
	</div>
	
	<?php printAlbumDesc(true); ?>
	
	<div id="images">
		<?php while (next_image()): ?>
		<div class="image">
			<div class="imagethumb"><a href="<?=getImageLinkURL();?>" title="<?=getImageTitle();?>"><?php printImageThumb(getImageTitle()); ?></a></div>
		</div>
		<?php endwhile; ?>
	</div>
	
	<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
	
	<div id="enableSorting">
  <? printSortableAlbumLink('Click to sort album', 'Manual sorting', NULL, 'credit'); ?>
  </div>
	
</div>

<div id="credit"><?php printAdminLink('Admin', '', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

</body>
</html>
