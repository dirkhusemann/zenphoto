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
    <h2><span><a href="<?=getGalleryIndexURL();?>" title="Gallery Index"><?=getGalleryTitle();?></a> | </span> <?php printAlbumTitle(true);?></h2>
  </div>
  <hr />
  <?php printAlbumDesc(true); ?>
  <br />

  
  <?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
  
  <?php
  //// Subalbum tests.
    $subalbums = $_zp_current_album->getSubAlbums();
    if (count($subalbums) > 0) {
      foreach ($subalbums as $suba) {
        echo '<b><a href="' . WEBPATH . '/index.php?album=' . $suba->name . '">' . $suba->name . "</a></b><br />";
      }
    }
    $parent = $_zp_current_album->getParent();
    if ($parent != NULL) { echo '<br /><strong>Parent album: <a href="' . WEBPATH . '/index.php?album=' . $parent->name . '">' . $parent->name . "</a></strong><br />"; }
  
  ?>
  
  

  <div id="images">
<?php while (next_image()): ?>
    <div class="image">
      <div class="imagethumb"><a href="<?=getImageLinkURL();?>" title="<?=getImageTitle();?>">
        <?php printImageThumb(getImageTitle()); ?></a></div>
    </div>

<?php endwhile; ?>
  </div>
  
  <?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>

  
</div>


</body>
</html>