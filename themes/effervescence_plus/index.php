<?php require ('customfunctions.php'); $themeResult = getTheme($zenCSS, $themeColor, 'effervescence');normalizeColumns(3, 5);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
	<?php 
			printRSSHeaderLink('Gallery','Gallery RSS'); 
			zenJavascript(); 
	?>
</head>

<body onload="blurAnchors()">

	<!-- Wrap Header -->
	<div id="header">

		<!-- Logo -->
			<div id="gallerytitle">
				<div id="logo">
					<?php
						if (getOption('Allow_search')) {  printSearchForm(''); }
				echo '<h1><a href="' . getMainSiteURL() . '" title="Visit ' . getMainSiteName() . '">' . printHome() . '</a></h1>';
			?>
				</div>
			</div>
	</div>

	<!-- Random Image -->
	<?php printHeadingImage(getRandomImages()); ?>

	<!-- Wrap Main Body -->
	<div id="content">
		<div id="main">

		<!-- Album List -->
		<ul id="albums">
				<?php
						$firstAlbum = null;
						$lastAlbum = null;
						while (next_album()){ 
						if (is_null($firstAlbum)) { 
								$lastAlbum = albumNumber();
								$firstAlbum = $lastAlbum; 
				} else {
								$lastAlbum++;
						}
			?>
			<li>
				<div class="imagethumb">
					<a href="<?php echo getAlbumLinkURL();?>" title="View the album: <?php echo getAlbumTitle(); printImage_AlbumCount(); ?>">
						<?php printCustomAlbumThumbImage(getCustomAlbumDesc(), null, 180, null, 180, 80); ?>
 						</a>
								</div>
				<h4><a href="<?php echo getAlbumLinkURL();?>" title="View the album: <?php echo getAlbumTitle(); printImage_AlbumCount();?>"><?php printAlbumTitle(); ?></a></h4>
			</li>
			<?php } ?>
		</ul>
		<div class="clearage"></div>
		<?php printNofM('Album', $firstAlbum, $lastAlbum, getNumAlbums()); ?>
	
		<!-- Page Numbers -->
		<div id="pagenumbers">
			<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
		</div>
	
	</div>

	<!-- Footer -->
	<div class="footlinks">
		<small>
			<p>Albums: <?php $albumNumber = getNumAlbums(); echo $albumNumber ?> &middot; 
				SubAlbums: <?php show_subalbum_count(); ?> &middot; 
				Images: <?php $photosArray = query_single_row("SELECT count(*) FROM ".prefix('images')); 
				$photosNumber = array_shift($photosArray); echo $photosNumber ?> 
				<?php if (getOption('Allow_comments')) { ?>
					&middot; 
					Comments: <?php $commentsArray = query_single_row("SELECT count(*) FROM ".prefix('comments')." WHERE inmoderation = 0"); 
					$commentsNumber = array_shift($commentsArray); echo $commentsNumber ?>
				<?php } ?>
			</p>
			<?php printThemeInfo(); ?>
		</small>
		<a href="http://www.zenphoto.org" title="A simpler web photo album">Powered by <font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a><br/>
		<?php printRSSLink('Gallery','', 'Gallery RSS', ''); ?>
	</div>
		
		<?php printAdminToolbox(); ?>

</body>
</html>