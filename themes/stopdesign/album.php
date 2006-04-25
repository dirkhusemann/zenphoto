<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> > <?=getAlbumTitle();?></title>
	<link rel="stylesheet" href="<?= $_zp_themeroot ?>/css/master.css" type="text/css" />
	<?php zenJavascript(); ?>
</head>

<body class="gallery">

<div id="content">

	<div class="galleryinfo">
	<h1><?php printAlbumTitle(true);?></h1>
	<p class="desc"><?php printAlbumDesc(true); ?></p>
	</div>

	<ul class="slides">
	<?php while (next_image()): ?>
	<li><a href="<?=getImageLinkURL();?>" title="<?=getImageTitle();?>"><?php printImageThumb(getImageTitle()); ?></a></li>
	<?php endwhile; ?>
	</ul>

	<div class="galleryinfo">
	<?php $number = getNumImages(); if ($number > $conf['images_per_page']) printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
	</div>

</div>

<p id="path"><a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a> > <?php printAlbumTitle(false);?></p>

<div id="footer">
	<hr />
	<p>Design by <a href="http://stopdesign.com/templates/photos/">Stopdesign</a>.
	Powered by <a href="http://www.zenphoto.org">zenphoto</a>.<br />
	Theme by <a href="http://www.bleecken.de/bilder/">Sjard Bleecken</a>.
	<?php printAdminLink('Admin', '<br />', '.'); ?>
</div>

</body>
</html>
