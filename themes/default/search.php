<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
	<?php 
		printGalleryTitle(); 
		echo " | "; 
		if (in_context(ZP_ALBUM)) {
			echo getAlbumTitle();
		} else {
			Echo "<em>Search</em>";
		}
		?>
	</title>
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
	<?php zenJavascript(); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<?php printSearchForm(); ?>
		<h2><span><?php printHomeLink('', ' | '); ?><a href="
		<?php echo getGalleryIndexURL();?>" title="Gallery Index">
		<?php echo getGalleryTitle();?></a></span> | 
		<?php
		if (in_context(ZP_ALBUM)) {
			echo getAlbumTitle();
		} else {
		  echo "<em>Search</em>";
		}
		?>
		</h2>
	</div>
		
		<div id="padbox">
		<?php 
		if (($_REQUEST['words'] OR $_REQUEST['date'])) {
			if (!in_context(ZP_ALBUM)) {
				if (($total = getNumImages() + getNumAlbums()) > 0) {
					if ($_REQUEST['date']){
						$searchwords = getSearchDate();
		 		} else { $searchwords = getSearchWords(); }
					echo "<p>Total matches for <em>".$searchwords."</em>: $total</p>";
				}
			}
		?>
<div id="albums">
			<?php while (next_album()): ?>
				<div class="album">
					<div class="thumb">
						<a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumThumbImage(getAlbumTitle()); ?></a>
					</div>
					<div class="albumdesc">
						<h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
						<p><?php printAlbumDesc(); ?></p>
						<small><?php printAlbumDate("Date: "); ?></small>
					</div>
					<p style="clear: both; "></p>
				</div>
			<?php endwhile; ?>
			</div>
		
			<div id="images">
				<?php while (next_image(false, $firstPageImages)): ?>
				<div class="image">
					<div class="imagethumb"><a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>"><?php printImageThumb(getImageTitle()); ?></a></div>
				</div>
				<?php endwhile; ?>
			</div>
		<?php
				} else { 
					if (in_context(ZP_ALBUM)) {
						echo "<p> The album is empty.</p>";
					} else {
						echo "<p>Sorry, no image matches. Try refining your search.</p>"; 
					}
			}

			printPageListWithNav("&laquo; prev","next &raquo;");
			?> 

	</div>
	
</div>

<div id="credit"><?php printRSSLink('Gallery', '', 'Gallery RSS', ' | '); ?> <a href="<?php echo getGalleryIndexURL();?>?p=archive">Archive View</a> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

<?php printAdminToolbox(); ?>

</body>
</html>