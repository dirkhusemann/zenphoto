<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php zenJavascript(); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><?php echo getGalleryTitle(); ?></h2>
	</div>

<div id="padbox">
	
	<div id="albums">
		<?php while (next_album()): ?>
		<div class="album">
			<div class="imagethumb">
				<a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printCustomAlbumThumbImage(getAlbumTitle(), 100, null, null, 85, 85); ?></a>
			</div>
			<div class="albumdesc">
      			<small><?php printAlbumDate("Date Taken: "); ?></small>
				<h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
				<p><?php printAlbumDesc(); ?></p>
			</div>
			<p style="clear: both; "></p>
		</div>
		<?php endwhile; ?>
	</div>
	
	<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>

</div>

</div>

<div id="credit"><?php printAdminLink('Admin', '', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | 'Sterile' Theme by <a href="http://www.levibuzolic.com" traget="_blank">Levi Buzolic</a></div>

</body>
</html>
