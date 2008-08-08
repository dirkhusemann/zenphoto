<?php if (!defined('WEBPATH')) die(); normalizeColumns(3, 6); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<?php zenJavascript(); ?>
	<title><?php printGalleryTitle(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen, projection" href="<?php echo $_zp_themeroot ?>/css/master.css" />
	<?php
	printRSSHeaderLink('Gallery','Gallery RSS');
	setOption('thumb_crop_width', 85, false);
	setOption('thumb_crop_height', 85, false);
	$archivepageURL = htmlspecialchars(getCustomPageURL('albumarchive'));
	?>
</head>

<body class="index">
	<?php echo getGalleryTitle(); ?><?php if (getOption('Allow_search')) {  printSearchForm(''); } ?>

	<div id="content">

		<h1><?php echo getGalleryTitle(); ?></h1>
		<div class="galleries">
				<h2><?php echo gettext('Recently Updated Galleries'); ?></h2>
				<ul>
					<?php
					$counter = 0;
					setOption('gallery_sorttype', 'ID', false);  // set the sort type so we get most recent albums
					setOption('gallery_sortdirection', '1', false);
					while (next_album() and $counter < 6):
					?>
						<li class="gal">
							<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext("View album:").' '; echo getAlbumTitle(); ?>"><?php printAlbumTitle(); ?></a></h3>
							<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext("View album:").' '; echo getAlbumTitle();?>" class="img"><?php printCustomAlbumThumbImage(getAlbumTitle(), null, 210, 59, 210, 59); ?></a>
							<p>
					<?php
						$number = getNumsubalbums();
						if ($number > 0) {
							if (!($number == 1)) {  $number .= ' '.gettext("albums");} else {$number .= ' '.gettext("album");}
								$counters = $number;
							} else {
								$counters = '';
							}
							$number = getNumImages();
							if ($number > 0) {
								if (!empty($counters)) { $counters .= ",&nbsp;"; }
							if ($number != 1) $number .= ' '.gettext("photos"); else $number .= ' '.gettext("photo");
							$counters .= $number;
						}
						if (!empty($counters)) {
							echo "<p><em>($counters)</em><br/>";
						}
						$text = getAlbumDesc();
						if(strlen($text) > 50) {
							$text = preg_replace("/[^ ]*$/", '', substr($text, 0, 50)) . "...";
						}
						echo $text;
					?></p>
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
				<p class="mainbutton"><a href="<?php echo $archivepageURL; ?>" class="btn"><img src="<?php echo $_zp_themeroot ?>/img/btn_gallery_archive.gif" width="118" height="21" alt="<?php echo ' '.gettext('Gallery Archive'); ?>" /></a></p>
		</div>

		<div id="secondary">
			<div class="module">
				<h2>Description</h2>
				<p><?php printGalleryDesc(); ?></p>
			</div>
			<div class="module">
				<?php $selector = getOption('Mini_slide_selector'); ?>
				<ul id="randomlist">
					<?php
					switch($selector) {
						case 'Recent images':
							if (function_exists('getImageStatistic')) {
								echo '<h2>'.gettext('Recent images').'</h2>';
								$images = getImageStatistic(6, "latest");
								foreach ($images as $image) {
									echo "<li><table><tr><td>\n";
									$imageURL = htmlspecialchars(getURL($image));
									echo '<a href="'.$imageURL.'" title="'.gettext("View image:").' '.
									$image->getTitle() . '"><img src="' .
									htmlspecialchars($image->getCustomImage(null, 44, 33, null, null, null, null, true)) .
																		'" width="44" height="33" alt="' . $image->getTitle() . "\"/></a>\n";
									echo "</td></tr></table></li>\n";
								}
								break;
							}
						case 'Random images':
							echo '<h2>'.gettext('Random images').'</h2>';
							for ($i=1; $i<=6; $i++) {
								echo "<li><table><tr><td>\n";
								$randomImage = getRandomImages();
								if (is_object($randomImage)) {
									$randomImageURL = htmlspecialchars(getURL($randomImage));
									echo '<a href="' . $randomImageURL . '" title="'.gettext("View image:").' ' . $randomImage->getTitle() . '">' .
 												'<img src="' . htmlspecialchars($randomImage->getCustomImage(null, 44, 33, null, null, null, null, true)) .
												'" width="44" height="33" alt="'.$randomImage->getTitle().'"';
									echo "/></a></td></tr></table></li>\n";
								}
							}
							break;
					}
					?>
				</ul>
			</div>
			<div class="module">
				<h2><?php echo gettext("Gallery data"); ?></h2>
				<table cellspacing="0" class="gallerydata">
						<tr>
							<th><a href="<?php echo $archivepageURL; ?>"><?php echo gettext('Galleries'); ?></a></th>
							<td><?php $albumNumber = getNumAlbums(); echo $albumNumber ?></td>
							<td></td>
						</tr>
						<tr>
							<th><?php echo gettext('Photos'); ?></th>
							<td><?php $photosArray = query_single_row("SELECT count(*) FROM ".prefix('images')); $photosNumber = array_shift($photosArray); echo $photosNumber ?></td>
							<td><?php printRSSLink('Gallery','','','',true,'i'); ?></td>
						</tr>
 					<?php if (getOption('Allow_comments')) { ?>
 						<tr>
							<th><?php echo gettext('Comments'); ?></th>
							<td><?php $commentsArray = query_single_row("SELECT count(*) FROM ".prefix('comments')." WHERE inmoderation = 0"); $commentsNumber = array_shift($commentsArray); echo $commentsNumber ?></td>
							<td><?php printRSSLink('Comments','','','',true,'i'); ?></td>
							</tr>
						<?php } ?>
				</table>
			</div>
		</div>
	</div>
	<p id="path"><?php printHomeLink('', ' > '); echo getGalleryTitle(); ?></p>
	<div id="footer">
		<hr />
		<?php if (function_exists('printLanguageSelector')) { echo '<p>'; printLanguageSelector(); echo '</p>'; } ?>
		<p>
			<a href="http://stopdesign.com/templates/photos/"><?php echo gettext('Photo Templates</a> from'); ?> Stopdesign.
			<?php echo gettext('Powered by'); ?> <a href="http://www.zenphoto.org">ZenPhoto</a>.
		</p>
	</div>

	<?php printAdminToolbox(ZP_INDEX); ?>

</body>

</html>
