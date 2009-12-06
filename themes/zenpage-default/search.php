<?php if (!defined('WEBPATH')) die(); $firstPageImages = normalizeColumns('2', '6');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?> | <?php echo gettext("Search"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
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
		<h2><a href="<?php echo getGalleryIndexURL(false);?>" title="<?php gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> &raquo; <?php echo "<strong>".gettext("Search")."</strong>";	?>
			</h2>
			</div>


		<div id="content">
		<div id="content-left">
				<h2>
		<?php
			$numimages = getNumImages();
			$numalbums = getNumAlbums();
			$total = $numimages + $numalbums;
			if ($zenpage = getOption('zp_plugin_zenpage')) {
				$numpages = getNumPages();
				$numnews = getNumNews();
				$total = $total + $numnews + $numpages;
			} else {
				$numpages = $numnews = 0;
			}
			$searchwords = getSearchWords();
			$searchdate = getSearchDate();
			if (!empty($searchdate)) {
				if (!empty($seachwords)) {
					$searchwords .= ": ";
				}
				$searchwords .= $searchdate;
			}
			if ($total > 0 ) {
				printf(ngettext('%1$u Hit for <em>%2$s</em>','%1$u Hits for <em>%2$s</em>',$total), $total, $searchwords); 
			}
		$c = 0;
		?>
		
		<?php if ($_zp_page == 1) { //test of zenpage searches
			if ($numpages > 0) {
				?>
				</h2>
				<h3><?php printf(gettext('Pages (%s)'),$numpages); ?></h3>
					<ul>
					<?php
					while (next_page()) {
						?>
						<li>
						<h4><?php printPageTitleLink(); ?></h4>
						<p><?php echo shortenContent(strip_tags($_zp_current_zenpage_page->getContent()),80,getOption("zenpage_textshorten_indicator")); ?></p>
						</li>
						<?php
					}
					?>
					</ul>
				<?php
				}
			if ($numnews>0) {
				?>
				<h3><?php printf(gettext('Articles (%s)'),$numnews); ?></h3>
				<div class="zenpagesearchtext">
					<ul>
					<?php
					while (next_news()) {
						?>
						<li>
						<h4><?php printNewsTitleLink(); ?></h4>
							<p><?php echo shortenContent(strip_tags(getNewsContent()),80,getOption("zenpage_textshorten_indicator")); ?></p>
						</li>
						<?php
					}
					?>
					</ul>
				</div>
				<?php
				}
			}
			?>
		<?php	if (!getOption('search_no_albums') && getNumAlbums() > 0) {	?>
			<h3><?php printf(gettext('Albums (%s)'),$numalbums); ?></h3>
			<div id="albums">
				<?php while (next_album()): ?>
					<div class="album">
							<div class="thumb">
							<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?> <?php echo getBareAlbumTitle();?>"><?php printCustomAlbumThumbImage(getBareAlbumTitle(), NULL, 95, 95, 95, 95); ?></a>
 							 </div>
								<div class="albumdesc">
									<h3><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo gettext('View album:'); ?> <?php echo getBareAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
 									<?php printAlbumDate(""); ?>
									<p><?php echo truncate_string(getAlbumDesc(), 45); ?></p>
								</div>
					</div>
				<?php endwhile; ?>
		</div>
	<?php } ?>
<?php if (getNumImages() > 0) { ?>
<h3><?php printf(gettext('Images (%s)'),$numimages); ?></h3>
			<div id="images">
				<?php while (next_image(false, $firstPageImages)): $c++;?>
				<div class="image">
					<div class="imagethumb"><a href="<?php echo htmlspecialchars(getImageLinkURL());?>" title="<?php echo getBareImageTitle();?>"><?php printImageThumb(getBareImageTitle()); ?></a></div>
				</div>
				<?php endwhile; ?>
			</div>
		<br clear=all>
<?php } ?>
		<?php
		if (function_exists('printSlideShowLink')) printSlideShowLink(gettext('View Slideshow'));
		if ($total == 0) {
				echo "<p>".gettext("Sorry, no matches. Try refining your search.")."</p>";
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
<?php printAdminToolbox(); ?>
</body>
</html>