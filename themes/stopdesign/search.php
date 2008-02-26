<?php 
	if (!defined('WEBPATH')) die();
		$firstPageImages = normalizeColumns(3, 6);
	setOption('images_per_page', getOption('images_per_page') - 1, false);
		if ($firstPageImages > 0)  { $firstPageImages = $firstPageImages - 1; }
	setOption('thumb_crop_width', 89, false);
	setOption('thumb_crop_height', 67, false);
	global $_zp_current_image; 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>
	<?php 
		printGalleryTitle(); 
		echo " | Search";
		?>
	</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); zenJavascript(); global $_zp_gallery; ?>
</head>

<body class="gallery">
	<?php echo getGalleryTitle(); ?>
	<?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

	<div id="content">

		<div class="galleryinfo">
		<?php
		  echo "<h1><em>Search</em></h1>";
		?>
		</div>
		<?php
		$Results = 0;
 		?>
		<?php
			$first = true;
			while (next_album()) { 
				if ($first) {
					echo '<div class="galleries">';
					echo "\n<h2></h2>\n<ul>\n";
					$first = false;
				}
			?>
				<li class="gal">
					<a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>" class="img"><?php printCustomAlbumThumbImage(getAlbumTitle(), null, 230, null, 210, 60); ?></a>
					<h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
					<p>
						<?php 
						$number = getNumAlbums(); 
						$results + $number;
						if ($number > 0) { 
						if (!($number == 1)) {  $number .= " albums";} else {$number .=" album";}
							$counters = $number;
						} else {
							$counters = '';
						}
						$number = getNumImages();
						if ($number > 0) {	
							if (!empty($counters)) { $counters .= ",&nbsp;"; }					
							if ($number != 1) $number .= " photos"; else $number .=" photo"; 
							$counters .= $number;
						}
						if (!empty($counters)) {
							echo "<p><em>($counters)</em><br/>";
						}
						$text = getAlbumDesc(); 
							if(strlen($text) > 50) {
							$text = preg_replace("/[^ ]*$/", '', substr($text, 0, 50))."..."; 
						}
						echo $text; 
						?>
					</p>
				</li>
			<?php 
			} 
			if (!$first) { echo "\n</ul>\n</div>\n"; }
			?>
		
	<ul class="slideset">
		<?php  
		$results = $results + getNumImages();
		$firstImage = null;
		$lastImage = null;
		while (next_image(false, $firstPageImages)) { 
			if (is_null($firstImage)) { 
				$lastImage = imageNumber();
				$firstImage = $lastImage; 
			} else {
				$lastImage++;
			}	
			echo "\n<li class=\"thumb\"><span><em style=\"background-image:url(" . getImageThumb() . '); "><a href="' . 
			getImageLinkURL() . '" title="' . getImageTitle() . '" style="background:#fff;">"'.
			getImageTitle().'"</a></em></span></li>';
		}
		if (!is_null($firstImage)  && hasNextPage()) { 
		?>
		<li class="thumb"><span class="forward"><em style="background-image:url('<?php echo $_zp_themeroot ?>/img/moreslide_next.gif');"><a href="<?php echo getNextPageURL(); ?>" style="background:#fff;">Next page</a></em></span></li>
		<?php
		}
		?>
	</ul>

	<div class="galleryinfo">
		<p>Feed for this album: <?php printRSSLink('Album','','','',true,'i'); ?></p>
		<?php 
			$params = $_zp_current_search->getSearchParams();
			if (!empty($params)) {
				if ($results != "0") {
					echo '<em class="count">';
					echo "Photos $firstImage-$lastImage of " . getNumImages();  
					echo "</em>";
		?>
				<?php if (hasPrevPage()) { ?>
				<a href="<?php echo getPrevPageURL(); ?>" accesskey="x">&laquo; Prev page</a>
				<?php } 
					if (hasNextPage()) { if (hasPrevPage()) { echo '&nbsp;'; } 
			?>
				<a href="<?php echo getNextPageURL(); ?>" accesskey="x">next page &raquo;</a>
			<?php
					}
					if (!in_context(ZP_ALBUM)) {
						echo '</p>';
						echo "<em class=\"count\">Total matches for <em>".getSearchWords()."</em>: ".$results."</em>";
					}
				} else {
					echo "<p>Sorry, no matches. Try refining your search.</p>"; 
				}
			}
			?>
	</div>
	</div>

	<p id="path"><?php printHomeLink('', ' > '); ?>
	<a href="<?php echo getGalleryIndexURL();?>" title="Albums Index">
	<?php echo getGalleryTitle();?></a> &gt; 
	<?php
	echo "<em>Search</em>";
	?>

	<div id="footer">
		<hr />
		<p>Powered by <a href="http://www.zenphoto.org">ZenPhoto</a>.</p>
	</div>
	<?php printAdminToolbox(); ?>
</body>
</html>
