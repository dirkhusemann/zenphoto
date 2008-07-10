<?php if (!defined('WEBPATH')) die(); normalizeColumns(3, 6); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zenJavascript(); ?>
	<title><?php printGalleryTitle(); echo gettext('Archive'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php 
	printRSSHeaderLink('Gallery','Gallery RSS');
	setOption('thumb_crop_width', 85, false);
	setOption('thumb_crop_height', 85, false);
	?>
</head>

<body class="archive">
	<?php echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(); } ?>

<div id="content">

	<h1><?php printGalleryTitle(); echo ' | '.gettext('Archive'); ?></h1>

	<div class="galleries">
 	<?php if (!checkForPassword()) {?>
		<h2><?php echo gettext("All galleries"); ?></h2>
		<ul>
			<?php $counter = 0; while (next_album(true) and $counter < 999): ?>
			<li class="gal">
			<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:').' '; echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
			<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:').' '; echo getAlbumTitle();?>" class="img"><?php printCustomAlbumThumbImage(getAlbumTitle(), null, 210, 59, 210, 59); ?></a>
			<p>
		<?php
			$number = getNumsubalbums(); 
			if ($number > 0) { 
				if (!($number == 1)) {  $number .= ' '.gettext('albums');} else {$number .=' '.gettext('album');}
				$counters = $number;
			} else {
				$counters = '';
			}
			$number = getNumImages();
			if ($number > 0) {	
				if (!empty($counters)) { $counters .= ",&nbsp;"; }					
				if ($number != 1) $number .= ' '.gettext('photos'); else $number .= ' '.gettext('photo'); 
				$counters .= $number;
			}
			if (!empty($counters)) {
				echo "<p><em>($counters)</em>";
			}
			$text = htmlspecialchars(getAlbumDesc()); 
			if(strlen($text) > 100) { $text = preg_replace("/[^ ]*$/", '', substr($text, 0, 100)) . "..."; } 
			echo $text; 
			?></p>
			<div class="date"><?php printAlbumDate(); ?></div>
			</li>
			<?php if ($counter == 2) {echo "</ul><ul>";}; $counter++; endwhile; ?>
		</ul>
	<?php } ?>
</div>

<div id="feeds">
	<h2><?php echo gettext('Gallery Feeds'); ?></h2>
	<ul>
		<li><?php echo "<a href='http://".sanitize($_SERVER['HTTP_HOST']).WEBPATH."/rss.php?albumnr=".getAlbumId()."&amp;albumname=".getAlbumTitle()."' class=\"i\">"; ?><img src="<?php echo WEBPATH; ?>/zp-core/images/rss.gif" /> <?php echo gettext('Photos'); ?></a></li>
		<li><?php echo "<a href='http://".sanitize($_SERVER['HTTP_HOST']).WEBPATH."/rss-comments.php' class=\"i\">"; ?><img src="<?php echo WEBPATH; ?>/zp-core/images/rss.gif" /> <?php echo gettext('Comments'); ?></a></li>
	</ul>
</div>

</div>

<p id="path"><?php printHomeLink('', ' > '); ?><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> &gt; <?php echo gettext('Gallery Archive'); ?></p>  

<div id="footer">
	<hr />
	<p>
		<a href="http://stopdesign.com/templates/photos/"><?php echo gettext('Photo Templates</a> from').' '; ?>Stopdesign.
		<?php echo gettext('Powered by').' '; ?><a href="http://www.zenphoto.org">ZenPhoto</a>.
	</p>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
