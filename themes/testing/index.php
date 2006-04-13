<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?></title>
  <link rel="stylesheet" href="<?php echo  $_zp_themeroot ?>/zen.css" type="text/css" />
  <?php zenJavascript(); ?>
</head>
<body>
<div id="main">
  <div id="gallerytitle">
    <h2><?php echo getGalleryTitle(); ?></h2>
  </div>
  
  <hr />
  <?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
  
  
  <div id="albums">
  <?php while (next_album()): ?>
  
    <div class="album">
      <div class="albumthumb"><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
        <?php printAlbumThumbImage(getAlbumTitle()); ?></a></div>
      <div class="albumtitle"><h3><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
        <?php printAlbumTitle(); ?></a></h3> <?php printAlbumDate(); ?></div>
      <div class="albumdesc"><?php printAlbumDesc(); ?></div>
    </div>
    <hr />
    
  <?php endwhile; ?>
  </div>
  
  <?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>

  <div id="enableSorting">
  <?php printSortableGalleryLink('Click to sort gallery', 'Manual sorting', NULL, 'credit'); ?>
  </div>

</div>

</body>
</html>
