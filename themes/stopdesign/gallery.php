<?php
if (!defined('WEBPATH')) die();
require_once('normalizer.php');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zp_apply_filter('theme_head'); ?>
	<title><?php echo getBareGalleryTitle(); ?> <?php echo gettext("Archive"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php
	printRSSHeaderLink('Gallery','Gallery RSS');
	setOption('thumb_crop_width', 85, false);
	setOption('thumb_crop_height', 85, false);
	?>
</head>

<body class="archive">
	<?php zp_apply_filter('theme_body_open'); ?>
	<?php echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

<div id="content">

	<h1><?php printGalleryTitle(); echo ' | '.gettext('Archive'); ?></h1>

	<div class="galleries">
		<h2><?php echo gettext("All galleries"); ?></h2>
		<ul>
			<?php
			$counter = 0;
			while (next_album()):
			?>
	<li class="gal">
	<h3><a href="<?php echo html_encode(getAlbumLinkURL());?>"
		title="<?php echo gettext('View album:').' '; echo getAnnotatedAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
	<a href="<?php echo html_encode(getAlbumLinkURL());?>"
		title="<?php echo gettext('View album:').' '; echo getAnnotatedAlbumTitle();?>"
		class="img"><?php printCustomAlbumThumbImage(getAnnotatedAlbumTitle(), null, 210, 59, getOption('Gallery_image_crop_width'), getOption('Gallery_image_crop_height')); ?></a>
		<p>
		<?php
			$anumber = getNumAlbums();
			$inumber = getNumImages();
			if ($anumber > 0 || $inumber > 0) {
				echo '<p><em>(';
				if ($anumber == 0) {
					if ($inumber != 0) {
						printf(ngettext('%u photo','%u photos', $inumber), $inumber);
					}
				} else if ($anumber == 1) {
					if ($inumber > 0) {
						printf(ngettext('1 album,&nbsp;%u photo','1 album,&nbsp;%u photos', $inumber), $inumber);
					} else {
						printf(gettext('1 album'));
					}
				} else {
					if ($inumber == 1) {
						printf(ngettext('%u album,&nbsp;1 photo','%u albums,&nbsp;1 photo', $anumber), $anumber);
					} else if ($inumber > 0) {
						printf(ngettext('%1$u album,&nbsp;%2$s','%1$u albums,&nbsp;%2$s', $anumber), $anumber, sprintf(ngettext('%u photo','%u photos',$inumber),$inumber));
					} else {
						printf(ngettext('%u album','%u albums', $anumber), $anumber);
					}
				}
				echo ')</em><br />';
			}
			$text = getAlbumDesc();
			if(strlen($text) > 100) { $text = preg_replace("/[^ ]*$/", '', substr($text, 0, 100)) . "..."; }
			echo $text;
		?>
		</p>
	<div class="date"><?php printAlbumDate(); ?></div>
	</li>
	<?php
			if ($counter == 2) {
				echo "</ul><ul>";
			}
			$counter++;
			endwhile;
			?>
		</ul>
			<div class="archiveinfo">
				<br />
				<p>
				<?php if (hasPrevPage()) { ?>
						<a href="<?php echo html_encode(getPrevPageURL()); ?>" accesskey="x">&laquo; <?php echo gettext('prev page'); ?></a>
				<?php } ?>
				<?php if (hasNextPage()) { if (hasPrevPage()) { echo '&nbsp;'; } ?>
						<a href="<?php echo html_encode(getNextPageURL()); ?>" accesskey="x"><?php echo gettext('next page'); ?> &raquo;</a>
				<?php } ?>
				</p>
			</div>
</div>

<div id="feeds">
	<h2><?php echo gettext('Gallery Feeds'); ?></h2>
	<ul>
		<li><a href="http://<?php echo sanitize($_SERVER['HTTP_HOST']).WEBPATH; ?>/rss.php" class="i"><img src="<?php echo WEBPATH.'/'.ZENFOLDER; ?>/images/rss.png" /> <?php echo gettext('Photos'); ?></a></li>
		<li><a href="http://<?php echo sanitize($_SERVER['HTTP_HOST']).WEBPATH; ?>/rss-comments.php" class="i"><img src="<?php echo WEBPATH.'/'.ZENFOLDER; ?>/images/rss.png" /> <?php echo gettext('Comments'); ?></a></li>
	</ul>
</div>

</div>

<p id="path">
	<?php printHomeLink('', ' > '); ?>
	<a href="<?php echo html_encode(getGalleryIndexURL(false));?>" title="<?php echo gettext('Main Index'); ?>"><?php echo gettext('Home');?></a> &gt;
	<?php echo getGalleryTitle();?> 
	<?php echo gettext('Gallery Archive'); ?>
</p>

<div id="footer">
	<hr />
	<?php if (function_exists('printUserLogin_out')) { printUserLogin_out(""); } ?>
	<p>
		<?php echo gettext('<a href="http://stopdesign.com/templates/photos/">Photo Templates</a> from Stopdesign');?>.
		<?php printZenphotoLink(); ?>
	</p>
</div>

<?php
printAdminToolbox();
zp_apply_filter('theme_body_close');
?>

</body>
</html>
