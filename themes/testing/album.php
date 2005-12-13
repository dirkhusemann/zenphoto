<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?></title>
  <link rel="stylesheet" href="<?= $_zp_themeroot ?>/zen.css" type="text/css" />
  <?php zenJavascript(); ?>
  
  <?php zenSortablesHeader(); ?>
</head>
<body>

<div id="main">
  <div id="gallerytitle">
    <h2><span><a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a> | </span> <?php printAlbumTitle(true);?></h2>
  </div>
  
  <hr />
  <?php printAlbumDesc(true); ?>
  <br />

  
  <?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>

  <div id="images">
    <?php while (next_image()): ?>  
      <div class="image">
        <div class="imagethumb">
          <?php printImageDiv(); ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
  
  <?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>
  
  <div id="enableSorting" style="display: block;">
  <? printSortableAlbumLink('Click to sort album', 'Enable sorting'); ?>
  </div>
 
</div>

<div id="credit"><?php printAdminLink('Admin', '', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

<?php zenSortablesFooter(); ?>


</body>
</html>