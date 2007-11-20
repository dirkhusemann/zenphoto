<?php 
if (!defined('WEBPATH')) die(); 
$startTime = array_sum(explode(" ",microtime())); 
$themeResult = getTheme($zenCSS, $themeColor, 'light');
$firstPageImages = normalizeColumns('1', '6');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
	<?php zenJavascript(); ?>
</head>
<body>

<div id="main">
	<div id="gallerytitle">
    	<h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> | <?php printParentBreadcrumb(); ?></span> <?php printAlbumTitle(true);?></h2>
  	</div>
  
  	( <?php printLink(getPrevAlbumURL(), "&laquo; Prev Album"); ?> | <?php printLink(getNextAlbumURL(), "Next Album &raquo;"); ?> )
  
  	<hr />
  	<?php printTags('links', 'Album tags: '); ?>
	<?php printAlbumDesc(true); ?>
  	<br />

  
	<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>

	<!-- Sub-Albums -->
  	<div id="albums">
  		<?php while (next_album()): ?>
    	<div class="album">
      		<div class="albumthumb"><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
        		<?php printAlbumThumbImage(getAlbumTitle()); ?></a>
            </div>
      		<div class="albumtitle">
                	<h3><a href="<?php echo getAlbumLinkURL();?>" title="<?php echo getAlbumTitle();?>">
        			<?php printAlbumTitle(); ?></a></h3> <?php printAlbumDate(); ?>
                </div>
      			<div class="albumdesc"><?php printAlbumDesc(); ?></div>
    		</div>
    		<hr />
 		<?php endwhile; ?>
  	</div>
  
  	<br />
  
  	<div id="images">
		<?php while (next_image(false, $firstPageImages)): ?>
    	<div class="image">
      		<div class="imagethumb">
            	<a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>">
        		<?php printImageThumb(getImageTitle()); ?></a>
            </div>
    	</div>

		<?php endwhile; ?>
		<br clear="all" />
    	<?php printAlbumMap(); ?>
  	</div>
  	<?php printPageNav("&laquo; prev", "|", "next &raquo;"); ?>

  	<div id="credit">
		<?php printRSSLink('Album', '', 'Album RSS', ''); ?> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | <a href="?p=archive">Archive View</a><br />
  		<?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?>
  	</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
