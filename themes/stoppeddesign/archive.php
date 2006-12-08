<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php if (zp_conf('website_title') != '') { echo zp_conf('website_title') . '&#187; '; } ?>Gallery Archive</title>
  <link rel="stylesheet" href="<?php echo  $_zp_themeroot ?>/css/master.css" type="text/css" />
  <?php zenJavascript(); ?>
  </head>

<body class="index">

<div id="content">

	<h1>Gallery Archive</h1>

		<div class="galleries">
		  <h2>All galleries</h2>
		  <ul>
      <?php /* Remove the page album limit for this page: */ $_zp_conf_vars['albums_per_page'] = 9999; ?>
		  <?php while (next_album()): ?>

			<li>
			  <a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>" class="img">
          <?php printCustomAlbumThumbImage(getAlbumTitle(), null, 230, null, 210, 60); ?></a>
			  <h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
			  <p><em><? $number = getNumImages(); if ($number > 1) $number .= " photos"; else $number .=" photo"; echo$number; ?></em> <?php $text = getAlbumDesc(); if( strlen($text) > 44) $text = preg_replace("/[^ ]*$/", '', substr($text, 0, 22))."..."; echo$text; ?></p>
			</li>

		  <?php endwhile; ?>
		  </ul>
		</div>

</div>

<p id="path"><?php if (zp_conf('website_url') != '') { ?> <a href="<?php echo zp_conf('website_url'); ?>" title="Back"><?php echo zp_conf('website_title'); ?></a> &#187; <?php } ?>
  <a href="<?php echo getGalleryIndexURL();?>/" title="Gallery Index"><?php echo getGalleryTitle();?></a> &#187; <a href="archive.php" title="Gallery Archive" class="active">Gallery Archive</a></p>

<div id="footer">
	<p><?php printAdminLink('Admin'); ?></p>
</div>

</body>
</html>