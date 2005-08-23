<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>

	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?= $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php zenJavascript(); ?>

</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><?php echo getGalleryTitle(); ?></h2>
	</div>
	
	<div id="albums">
		<?php while (next_album()): ?>
		<div class="album">
			<a href="<?=getAlbumLinkURL();?>" title="View album: <?=getAlbumTitle();?>">
			<?php printAlbumThumbImage(getAlbumTitle()); ?>
			</a>
			<div class="albumdesc">
				<?php /* Not Yet Implemented: <small>Date Taken: xx-xx-xxxx</small> */ ?>
				<h3><a href="<?=getAlbumLinkURL();?>" title="View album: <?=getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
				<p><?php printAlbumDesc(); ?></p>
			</div>
			<p style="clear: both; "></p>
		</div>
		<?php endwhile; ?>
	</div>
	
	<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
	<?php // printPageNav("&laquo; prev", "|", "next &raquo;"); ?>
</div>

<div id="credit"><?php if (zp_loggedin()) { ?><a href="<?=getGalleryIndexURL();?>/admin/">Admin</a> | <?php } ?>Powered by <a href="http://www.trisweb.com" title="A simpler web photo album">Zen Photo</a></div>

</body>
</html>
