<?php if (!defined('WEBPATH')) die(); $firstPageImages = normalizeColumns('2', '6'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
</head>
<body>

<div id="main">

		<div id="header">
		<h1><?php echo getGalleryTitle();?></h1>
		</div>

<div id="content">

	<div id="breadcrumb">
<h2><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo gettext("Index"); ?></a> &raquo; <?php echo gettext("Gallery"); ?><?php printParentBreadcrumb(" &raquo; "," &raquo; "," &raquo; "); ?><strong><?php printAlbumTitle(true);?></strong></h2>
</div>


	<div id="content-left">
	<div><p><?php printAlbumDesc(true); ?></p></div>

			<div id="albums">
			<?php while (next_album()): ?>
			<div class="album">

						<div class="thumb">
					<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?> <?php getBareAlbumTitle();?>"><?php printCustomAlbumThumbImage(getBareAlbumTitle(), NULL, 95, 95, 95, 95); ?></a>
						</div>
				<div class="albumdesc">
					<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?> <?php echo getBareAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
								<small><?php printAlbumDate(""); ?></small>
					<p><?php echo truncate_string(getAlbumDesc(), 45); ?></p>
				</div>
				<p style="clear: both; "></p>
			</div>
			<?php endwhile; ?>
		</div>

			<div id="images">
			<?php while (next_image(false, $firstPageImages)): ?>
			<div class="image">
				<div class="imagethumb"><a href="<?php echo htmlspecialchars(getImageLinkURL());?>" title="<?php echo getBareImageTitle();?>"><?php printImageThumb(getBareImageTitle()); ?></a></div>
			</div>
			<?php endwhile; ?>

		</div>
				<p style="clear: both; "></p>
		<?php printPageListWithNav("&laquo; ".gettext("prev"), gettext("next")." &raquo;"); ?>
		<?php printTags('links', gettext('<strong>Tags:</strong>').' ', 'taglist', ''); ?>

	<?php if (function_exists('printSlideShowLink')) printSlideShowLink(gettext('View Slideshow')); ?>
	<?php if (function_exists('printAlbumRating')) { printAlbumRating(); }?>


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