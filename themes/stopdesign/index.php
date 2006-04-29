<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php printGalleryTitle(); ?></title>
  <link rel="stylesheet" href="<?= $_zp_themeroot ?>/css/master.css" type="text/css" />
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
		  <a href="<?=getAlbumLinkURL();?>" title="View album: <?=getAlbumTitle();?>" class="img">
        <?php printCustomAlbumThumbImage(getAlbumTitle(), null, 230, null, 210, 60); ?>
      </a>
		  <h3><a href="<?=getAlbumLinkURL();?>" title="View album: <?=getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
		  <p><em>(<? $number = getNumImages(); if ($number > 1) $number .= " photos"; else $number .=" photo"; echo$number; ?>)</em> <?php $text = getAlbumDesc(); if( strlen($text) > 50) $text = preg_replace("/[^ ]*$/", '', substr($text, 0, 50))."&#8230;"; echo$text; ?></p>
		</li>

	  <?php if ($counter == 2) {echo "</ul><ul>";}; $counter++; endwhile; ?>
	  </ul>

		<p class="mainbutton"><a href="index.php?p=archive" class="btn"><img src="<?= $_zp_themeroot ?>/img/btn_gallery_archive.gif" alt="Gallery Archive" /></a></p>

	</div>


	<div id="secondary">

		<div class="module">
			<h2>Description</h2>
			<p>Here you can write a small description about you, your galleries, how you make the photos or something else. Just edit the index.php in the themes directory.</p>
		</div>

		<div class="module">
			<h2>Gallery data</h2>
			<table cellspacing="0" class="gallerydata">
			  <tr>
				<th><a href="index.php?p=archive">Galleries</a></th>
				<td><? $albumNumber = getNumAlbums(); echo $albumNumber ?></td>
			  </tr>
			  <tr>
				<th>Photos</th>
				<td><? $photosArray = query_single_row("SELECT count(*) FROM ".prefix('images')); $photosNumber = array_shift($photosArray); echo $photosNumber ?></td>
			  </tr>
			  <tr>
				<th>Comments</th>
				<td><? $commentsArray = query_single_row("SELECT count(*) FROM ".prefix('comments')." WHERE inmoderation = 0"); $commentsNumber = array_shift($commentsArray); echo $commentsNumber ?></td>
			  </tr>
			</table>
		</div>

		<div class="module">
			<h2>Template</h2>
			<p>This gallery design and set of template files that recreate it are licensed under a <a href="http://creativecommons.org/licenses/by-nc-sa/2.5/">Creative Commons License</a> and are based on a design from <a href="http://stopdesign.com/templates/photos/">Stopdesign</a>. You can download the zenphoto theme <a href="http://www.bleecken.de/bilder/">here</a>.</p>
		</div>

	</div>


</div>

<p id="path"><?php echo getGalleryTitle(); ?></p>

<div id="footer">
	<hr />
	<p>Design by <a href="http://stopdesign.com/templates/photos/">Stopdesign</a>.
	Powered by <a href="http://www.zenphoto.org">zenphoto</a>.<br />
	Theme by <a href="http://www.bleecken.de/bilder/">Sjard Bleecken</a>.
	<?php printAdminLink('Admin', '<br />', '.'); ?>
</div>

</body>
</html>