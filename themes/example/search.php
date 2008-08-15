<?php
if (!defined('WEBPATH')) die();
$startTime = array_sum(explode(" ",microtime()));
$firstPageImages = normalizeColumns(1, 7);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo strip_tags(getGalleryTitle()); ?> | <?php echo gettext("Search"); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); ?>
</head>
<body>

<div id="main">
	<div id="gallerytitle">
		<?php
			printSearchForm();
		?>
		<h2><span><?php printHomeLink('', ' | '); ?><a href="
		<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Gallery Index') ?>">
		<?php echo getGalleryTitle();?></a> |
		<?php
		  echo "<em>".gettext("Search")."</em>";
		?>
		</span></h2>
		</div>

		<hr />

		<?php
			if (($total = getNumImages() + getNumAlbums()) > 0) {
				if (isset($_REQUEST['date']))	{
					$searchwords = getSearchDate();
	 		} else {
	 			$searchwords = getSearchWords(); }
				echo "<p>".gettext("Total matches for")." <em>".$searchwords."</em>: $total</p>";
			}
			$c = 0;
		?>
<div id="albums">
			<?php while (next_album()): $c++;?>
			<div class="album">
					<div class="albumthumb"><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo strip_tags(getAlbumTitle());?>">
						<?php printAlbumThumbImage(getAlbumTitle()); ?></a>
						</div>
					<div class="albumtitle">
									<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo strip_tags(getAlbumTitle());?>">
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
							<a href="<?php echo htmlspecialchars(getImageLinkURL());?>" title="<?php echo strip_tags(getImageTitle());?>">
						<?php printImageThumb(getImageTitle()); ?></a>
						</div>
			</div>
				<?php endwhile; ?>
		</div>

	<br clear="all" />
	<?php
			if (function_exists('printSlideShowLink')) {
				echo "<p align=\"center\">";
				printSlideShowLink(gettext('View Slideshow'));
				echo "</p>";
			}
			if ($c == 0) {
				echo "<p>".gettext("Sorry, no image matches. Try refining your search.")."</p>";
			}

			echo '<br clear="all" />';
			printPageListWithNav("&laquo; ".gettext("prev"),gettext("next")." &raquo;");
	?>


		<div id="credit">
			<?php printRSSLink('Gallery', '', gettext('Gallery RSS'), ''); ?> | <?php echo gettext("Powered by"); ?> <a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>">zenphoto</a> | <?php printCustomPageURL(gettext("Archive View"),"archive"); ?>
		<?php
		if (function_exists('printUserLogout')) {
			printUserLogout(" | ");
		}
		?>
		<br />
		<?php echo round((array_sum(explode(" ",microtime())) - $startTime),4).' '.gettext('Seconds').'</strong>'; ?>
	</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
