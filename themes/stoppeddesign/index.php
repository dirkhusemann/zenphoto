<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php if (zp_conf('website_title') != '') { echo zp_conf('website_title') . '&#187; '; } ?><?php printGalleryTitle(); ?></title>
  <link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/css/master.css" type="text/css" />
  <?php zenJavascript(); ?>
</head>

<body class="index">

<div id="content">

	<h1><?php echo getGalleryTitle(); ?></h1>

	<div class="galleries">
	  <h2>Recently Updated Galleries</h2>
	  <ul>
	  <?php $counter = 0; while (next_album() and $counter < 6): ?>

		<li>
		  <a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>" class="img">
        <?php printCustomAlbumThumbImage(getAlbumTitle(), null, 230, null, 210, 60); ?>
      </a>
		  <h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
		  <p><?php $text = getAlbumDesc(); if( strlen($text) > 44) $text = preg_replace("/[^ ]*$/", '', substr($text, 0, 22))."&#8230;"; echo$text;  ?> <em><?php $number = getNumImages(); if ($number > 1) $number .= " photos"; else $number .=" photo"; echo$number; ?></em> </p>
		</li>

	  <?php if ($counter == 2) {echo "</ul><ul>";}; $counter++; endwhile; ?>
	  </ul>

		<p class="mainbutton"><a href="<?php echo WEBPATH; ?>/index.php?p=archive"><h3 class="big">View all Galleries</h3></a></p>

	</div>


	<div id="secondary">

		<div class="module">
			<h2>Description</h2>
			<p>Photo Gallery</p>
		</div>

		<div class="module">
			<h2>Gallery data</h2>
			<table cellspacing="0" class="gallerydata">
			  <tr>
				<th><a href="<?php echo WEBPATH; ?>/index.php?p=archive">Galleries</a></th>
				<td><?php $albumNumber = getNumAlbums(); echo $albumNumber ?></td>
			  </tr>
			  <tr>
				<th>Photos</th>
				<td><?php $photosArray = query_single_row("SELECT count(*) FROM ".prefix('images')); $photosNumber = array_shift($photosArray); echo $photosNumber ?></td>
			  </tr>
			  <tr>
				<th>Comments</th>
				<td><?php $commentsArray = query_single_row("SELECT count(*) FROM ".prefix('comments')." WHERE inmoderation = 0"); $commentsNumber = array_shift($commentsArray); echo $commentsNumber ?></td>
			  </tr>
			</table>
		</div>

		<div class="module">
			<h2>Template</h2>
			<p>This gallery uses the Stoppeddesign theme, modified for <a href="http://www.zenphoto.org">ZenPhoto</a> by <a href="http://www.benspicer.com">Ben Spicer</a>.  You can get it <a href="http://www.benspicer.com/files/stoppeddesign.zip">here</a>, or find out more information about it <a href="index.php?p=license" title="The template License">here</a>.</p>
		</div>

	</div>


</div>

<p id="path"><?php if (zp_conf('website_url') != '') { ?> <a href="<?php echo zp_conf('website_url'); ?>" title="Back"><?php echo zp_conf('website_title'); ?></a> &#187; <?php } ?>
  <a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index" class="active"><?php echo getGalleryTitle();?></a></p>

<div id="footer">
	<p><?php printAdminLink('Admin'); ?></p>
</div>

</body>
</html>