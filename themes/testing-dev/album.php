<?php $startTime = array_sum(explode(" ",microtime())); if (!defined('WEBPATH')) die(); ?>
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
    <h2>
      <span><a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a> | 
      <?php printParentBreadcrumb(); ?></span> 
      <?php printAlbumTitle(true);?>
    </h2>
  </div>
  <hr />
  <div class="desc">
    <?php printAlbumDesc(true); ?>
  </div>
  
  <br />
  
  <!-- Sub-Albums -->
  <div id="albums">
  <?php while (next_album()): ?>
    <div class="album">
      <div class="albumthumb"><a href="<?=getAlbumLinkURL();?>" title="<?=getAlbumTitle();?>">
        <?php printAlbumThumbImage(getAlbumTitle()); ?></a></div>
      <div class="albumtitle"><h3><a href="<?=getAlbumLinkURL();?>" title="<?=getAlbumTitle();?>">
        <?php printAlbumTitle(); ?></a></h3> <?php printAlbumDate(); ?></div>
      <div class="albumdesc"><?php printAlbumDesc(); ?></div>
    </div>
    <hr />
  <?php endwhile; ?>
  </div>
  
  
  <?php if (getNumImages() > 0): /* Only print if we have images. */ ?>
  
  <?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>

  <!-- Images -->
  <div id="images">
  <?php while (next_image()): ?>
    <div class="image">
      <div class="imagethumb"><a href="<?=getImageLinkURL();?>" title="<?=getImageTitle();?>">
        <?php printImageThumb(getImageTitle()); ?></a>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
  
  <br style="clear: both;" />
  
  <?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
  <hr class="space" />
  
  <?php endif; ?>
  
  <p style="text-align: right;"><?php printAdminLink("Admin"); ?> 
    <?php /* Timer */ echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?></p>

  
</div>


</body>
</html>