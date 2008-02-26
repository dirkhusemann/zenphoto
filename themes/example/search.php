<?php 
if (!defined('WEBPATH')) die(); 
$startTime = array_sum(explode(" ",microtime())); 
$firstPageImages = normalizeColumns(1, 7);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
	<?php 
		printGalleryTitle(); 
		echo " | Search";
		?>
	</title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
	<?php zenJavascript(); ?>
</head>
<body>

<div id="main">
	<div id="gallerytitle">
		<?php 
		if (!in_context(ZP_ALBUM)) {
			printSearchForm(); 
		}
		?>
		<h2><span><?php printHomeLink('', ' | '); ?><a href="
		<?php echo getGalleryIndexURL();?>" title="Gallery Index">
		<?php echo getGalleryTitle();?></a> | 
		<?php
		  echo "<em>Search</em>";
		?>
		</span></h2>
		</div>
	
		<hr />

		<?php
		if (!in_context(ZP_ALBUM)) {
			if (($total = getNumImages() + getNumAlbums()) > 0) {
				if ($_REQUEST['date'])
	 		{ $searchwords = getSearchDate();
	 		} else { $searchwords = getSearchWords(); }
				echo "<p>Total matches for <em>".$searchwords."</em>: $total</p>";
			}
		}
		$c = 0;
		?>
<div id="albums">
			<?php while (next_album()): $c++;?>
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
		
		<div id="images">
		<?php while (next_image(false, $firstPageImages)): $c++;?>
			<div class="image">
					<div class="imagethumb">
							<a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>">
						<?php printImageThumb(getImageTitle()); ?></a>
						</div>
			</div>
				<?php endwhile; ?>
		</div>

	<br clear="all" />
	<?php
			if ($c == 0) { 
				echo "<p>Sorry, no image matches. Try refining your search.</p>"; 
			}
	
			echo '<br clear="all" />';
			printPageListWithNav("&laquo; prev","next &raquo;");
	?> 
	

		<div id="credit">
			<?php printRSSLink('Gallery', '', 'Gallery RSS', ''); ?> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a> | <a href="?p=archive">Archive View</a><br />
		<?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' Seconds</strong>'; ?>
	</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
