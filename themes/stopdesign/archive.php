<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Gallery Archive</title>
  <link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/css/master.css" type="text/css" />

  <?php zenJavascript(); ?>

  </head>

<body class="index">

<div id="content">

	<h1>Gallery Archive</h1>

		<div class="galleries">
		  <h2>All galleries</h2>
		  <ul>
		  <?php while (next_album()): ?>

			<li>
			  <a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>" class="img">
          <?php printCustomAlbumThumbImage(getAlbumTitle(), null, 230, null, 210, 60); ?>
        </a>
			  <h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
			  <p><em>(<?php $number = getNumImages(); if ($number > 1) $number .= " photos"; else $number .=" photo"; echo$number; ?>)</em> <?php $text = getAlbumDesc(); if( strlen($text) > 50) $text = preg_replace("/[^ ]*$/", '', substr($text, 0, 50))."..."; echo$text; ?></p>
			</li>

		  <?php endwhile; ?>
		  </ul>
		</div>

</div>

<p id="path"><a href="<?php echo getGalleryIndexURL();?>/" title="Gallery Index"><?php echo getGalleryTitle();?></a> > Gallery Archive</p>

<div id="footer">
	<hr />
	<p>Design by <a href="http://stopdesign.com/templates/photos/">Stopdesign</a>.
	Powered by <a href="http://www.zenphoto.org">zenphoto</a>.<br />
	Theme by <a href="http://www.bleecken.de/bilder/">Sjard Bleecken</a>.
	<?php printAdminLink('Admin', '<br />', '.'); ?>
</div>

</body>
</html>