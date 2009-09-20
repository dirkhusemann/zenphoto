<?php
if (!defined('WEBPATH')) die();
$startTime = array_sum(explode(" ",microtime()));
$themeResult = getTheme($zenCSS, $themeColor, 'light');
$firstPageImages = normalizeColumns(1, 7);
?>
<?php
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
header('Content-Type: text/html; charset=' . getOption('charset'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
</head>
<body>

<div id="main">
	<div id="gallerytitle">
			<h2><span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo ('Albums Index'); ?>"><?php echo getGalleryTitle();?></a> | <?php printParentBreadcrumb(); ?></span> <?php printAlbumTitle(true);?></h2>
		</div>

		( <?php printLink(getPrevAlbumURL(), "&laquo; ".gettext("Prev Album")); ?> | <?php printLink(getNextAlbumURL(), gettext("Next Album")." &raquo;"); ?> )

		<hr />
		<?php printTags('links', gettext('<strong>Tags:</strong>').' ', 'taglist', ''); ?>
	<?php printAlbumDesc(true); ?>
		<br />


	<?php printPageListWithNav("&laquo; ".gettext("prev"), gettext("next")." &raquo;"); ?>

	<!-- Sub-Albums -->
		<div id="albums">
			<?php while (next_album()): ?>
			<div class="album">
					<div class="albumthumb"><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo getAnnotatedAlbumTitle();?>">
						<?php printAlbumThumbImage(getAnnotatedAlbumTitle()); ?></a>
						</div>
					<div class="albumtitle">
									<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo getAnnotatedAlbumTitle();?>">
							<?php printAlbumTitle(); ?></a></h3> <?php printAlbumDate(); ?>
								</div>
						<div class="albumdesc"><?php printAlbumDesc(); ?></div>
				</div>
				<hr />
 		<?php endwhile; ?>
		</div>

		<br />

		<div id="images">
		<?php
		if (function_exists('flvPlaylist') && getOption('Use_flv_playlist')) {
			if (getOption('flv_playlist_option') == 'playlist') {
				flvPlaylist('playlist');
			} else {
				while (next_image(false,$firstPageImages)) {
					printImageTitle();
					flvPlaylist("players");
				}

			}
		} else {
			while (next_image(false, $firstPageImages)) { ?>
				<div class="image">
					<div class="imagethumb">
							<a href="<?php echo htmlspecialchars(getImageLinkURL());?>" title="<?php echo getBareImageTitle();?>">
							<?php printImageThumb(getAnnotatedImageTitle()); ?></a>
						</div>
				</div>
		<?php
			}
		}
		?>



		<br clear="all" />
		<?php if (function_exists('printSlideShowLink')) printSlideShowLink(gettext('View Slideshow')); ?>
			<div class="rating"><?php if (function_exists('printAlbumRating')) printAlbumRating(); ?></div>
			<?php if (function_exists('printAlbumMap')) printAlbumMap(); ?>
		</div>


 		<?php printPageNav("&laquo; ".gettext("prev"), "|", gettext("next")." &raquo;"); ?>

<!-- begin comment block -->
			<?php if (function_exists('printCommentForm')  && getCurrentPage() == 1) {
				printCommentForm();
			}
			?>
<!--  end comment block -->

		<div id="credit">
		<?php printRSSLink('Album', '', gettext('Album RSS'), ' | '); ?> 
		<?php printZenphotoLink(); ?>
		| <?php printCustomPageURL(gettext("Archive View"),"archive"); ?>
		<?php
		if (function_exists('printUserLogin_out')) {
			printUserLogin_out(" | ");
		}
		?>
		<br />
			<?php printf(gettext("%u seconds"), round((array_sum(explode(" ",microtime())) - $startTime),4)); ?>
		</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
