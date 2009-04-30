<?php if (!defined('WEBPATH')) die(); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Search"); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); ?>
</head>

<body>

<div id="main">

		<div id="header">
			
		<h1><?php printGalleryTitle(); ?></h1>
		<?php if (getOption('Allow_search')) {  printSearchForm("","search","",gettext("Search gallery")); } ?>
		</div>


<div id="breadcrumb">
		<h2><a href="<?php echo getGalleryIndexURL();?>" title="<?php gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> &raquo; <?php echo "<strong>".gettext("Search")."</strong>";	?>
			</h2>
			</div>


		<div id="content">
		<div id="content-left">
		<?php
		if (($total = getNumImages() + getNumAlbums()) > 0) {
			if (isset($_REQUEST['date'])){
				$searchwords = getSearchDate();
 		} else { $searchwords = getSearchWords(); }
			echo "<p>".gettext("Total matches for")." <em>".$searchwords."</em>: $total</p>";
		}
		$c = 0;
		?>
<div id="albums">
			<?php while (next_album()): $c++;?>
				<div class="album">
					<div class="thumb">
						<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:');?> <?php echo getBareAlbumTitle();?>"><?php printAlbumThumbImage(getBareAlbumTitle()); ?></a>
					</div>
					<div class="albumdesc">
						<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:');?> <?php echo getBareAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
						<p><?php printAlbumDesc(); ?></p>
						<small><?php printAlbumDate(gettext("Date:").' '); ?> </small>
					</div>
					<p style="clear: both; "></p>
				</div>
			<?php endwhile; ?>
			</div>

			<div id="images">
				<?php while (next_image(false, $firstPageImages)): $c++;?>
				<div class="image">
					<div class="imagethumb"><a href="<?php echo htmlspecialchars(getImageLinkURL());?>" title="<?php echo getBareImageTitle();?>"><?php printImageThumb(getBareImageTitle()); ?></a></div>
				</div>
				<?php endwhile; ?>
			</div>
		<br clear=all>
		<?php
		if (function_exists('printSlideShowLink')) printSlideShowLink(gettext('View Slideshow'));
		if ($c == 0) {
				echo "<p>".gettext("Sorry, no image matches. Try refining your search.")."</p>";
			}

			printPageListWithNav("&laquo; ".gettext("prev"),gettext("next")." &raquo;");
			?>

	</div><!-- content left-->
	
	
	
	<div id="sidebar">
		<?php include("sidebar.php"); ?>
	</div><!-- sidebar -->
	
	

	<div id="footer">
	<?php include("footer.php"); ?>
	</div>
</div><!-- content -->

</div><!-- main -->
<?php if (function_exists('printAdminToolbox')) printAdminToolbox(); ?>
</body>
</html>