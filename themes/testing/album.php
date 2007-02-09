<?php $startTime = array_sum(explode(" ",microtime())); if (!defined('WEBPATH')) die(); ?>
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
    <h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a> | </span> <?php printAlbumTitle(true);?></h2>
  </div>
  
  ( <?php printLink(getPrevAlbumURL(), "&laquo; Prev Album"); ?> | <?php printLink(getNextAlbumURL(), "Next Album &raquo;"); ?> )
  
  <hr />
  <?php printAlbumDesc(true); ?>
  <br />

  
  <?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>

  <div id="images">
<?php while (next_image()): ?>
    <div class="image">
      <div class="imagethumb"><a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>">
        <?php printImageThumb(getImageTitle()); ?></a></div>
    </div>

<?php endwhile; ?>
  </div>
  
  <?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>

  <div id="credit"><?php printAdminLink('Admin', '', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a><br />
  <?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?></div>
</div>

</body>
</html>