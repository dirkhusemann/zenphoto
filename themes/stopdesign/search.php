<?php
	if (!defined('WEBPATH')) die();
		$firstPageImages = normalizeColumns(3, 6);
	setOption('images_per_page', getOption('images_per_page') - 1, false);
		if ($firstPageImages > 0)  { $firstPageImages = $firstPageImages - 1; }
	setOption('thumb_crop_width', 89, false);
	setOption('thumb_crop_height', 67, false);
	global $_zp_current_image;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Search"); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
</head>

<body class="gallery">
	<?php echo getGalleryTitle(); ?>
	<?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

	<div id="content">

		<div class="galleryinfo">
		<?php
		  echo "<h1><em>". gettext('Search'). "</em></h1>";
		?>
		</div>
		<?php
		$results = 0;
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
					<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php printf(gettext('View album: %s'), getAnnotatedAlbumTitle());?>" class="img"><?php printCustomAlbumThumbImage(getAnnotatedAlbumTitle(), null, 230, null, 210, 60); ?></a>
					<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php printf(gettext('View album: %s'), getAnnotatedAlbumTitle());?>"><?php printAlbumTitle(); ?></a></h3>
					<p>
						<?php
						$anumber = getNumAlbums();
						$inumber = getNumImages();
						if ($anumber > 0 || $inumber > 0) {
							echo '<p><em>(';
							if ($anumber == 0 && $inumber == 1) {
								printf(gettext('1 photo'));
							} else if ($anumber == 0 && $inumber > 1) {
								printf(gettext('%u photos'), $inumber);
							} else if ($anumber == 1 && $inumber == 1) {
								printf(gettext('1 album,&nbsp;1 photo'));
							} else if ($anumber > 1 && $inumber == 1) {
								printf(gettext('%u album,&nbsp;1 photo'), $anumber);
							} else if ($anumber > 1 && $inumber > 1) {
								printf(gettext('%1$u album,&nbsp;%2$u photos'), $anumber, $inumber);
							} else if ($anumber == 1 && $inumber == 0) {
								printf(gettext('1 album'));
							} else if ($anumber > 1 && $inumber == 0) {
								printf(gettext('%u album'),$anumber);
							} else if ($anumber == 1 && $inumber > 1) {
								printf(gettext('1 album,&nbsp;%u photos'), $inumber);
							}
							echo ')</em><br/>';
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
			htmlspecialchars(getImageLinkURL()) . '" title="' . getAnnotatedImageTitle() . '" style="background:#fff;">"'.
			getImageTitle().'"</a></em></span></li>';
		}
		if (!is_null($firstImage)  && hasNextPage()) {
		?>
		<li class="thumb"><span class="forward"><em style="background-image:url('<?php echo $_zp_themeroot ?>/images/moreslide_next.gif');"><a href="<?php echo htmlspecialchars(getNextPageURL()); ?>" style="background:#fff;"><?php echo gettext('Next page'); ?></a></em></span></li>
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
					printf( gettext('Photos %1$u-%2$u of %3$u'), $firstImage, $lastImage, getNumImages());
					echo "</em>";
				if (function_exists('printSlideShowLink')) {
					printSlideShowLink(gettext('View Slideshow'));
				}
					?>
				<?php if (hasPrevPage()) { ?>
				<a href="<?php echo htmlspecialchars(getPrevPageURL()); ?>" accesskey="x">&laquo; <?php echo gettext('prev page'); ?></a>
				<?php }
					if (hasNextPage()) { if (hasPrevPage()) { echo '&nbsp;'; }
			?>
				<a href="<?php echo htmlspecialchars(getNextPageURL()); ?>" accesskey="x"><?php echo gettext('next page'); ?> &raquo;</a>
			<?php
					}
					echo '</p>';
					echo "<em class=\"count\">"  .sprintf(gettext('Total matches for <em>%1$s</em>: %2$u'),getSearchWords(), $results);
				} else {
					echo "<p>".gettext('Sorry, no matches. Try refining your search.')."</p>";
				}
			}
			?>
	</div>
	</div>

	<p id="path"><?php printHomeLink('', ' > '); ?>
	<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>">
	<?php echo getGalleryTitle();?></a> &gt;
	<?php
	echo "<em>".gettext('Search')."</em>";
	?>

	<div id="footer">
		<hr />
		<p>
		<?php echo gettext('Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album"><font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a>'); ?>
		</p>
	</div>
	<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>
</body>
</html>
