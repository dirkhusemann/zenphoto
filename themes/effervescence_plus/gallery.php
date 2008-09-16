<?php require ('customfunctions.php'); $themeResult = getTheme($zenCSS, $themeColor, 'effervescence');normalizeColumns(3, 5);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); 	?>
</head>

<body onload="blurAnchors()">

	<!-- Wrap Header -->
	<div id="header">

	<!-- Logo -->
		<div id="gallerytitle">
			<div id="logo">
				<?php
				if (getOption('Allow_search')) {  printSearchForm(NULL, '', $_zp_themeroot.'/images/search.png'); }
				echo printLogo();
				?>
			</div>
		</div>

	<!-- Crumb Trail Navigation -->
		<div id="wrapnav">
			<div id="navbar">
				<span><?php printHomeLink('', ' | '); printGalleryTitle();?></span>
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
				<?php $annotate =  annotateAlbum();	?>
				<div class="imagethumb">
				<a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo $annotate; ?>">
						<?php printCustomAlbumThumbImage($annotate, null, 180, null, 180, 80); ?>
 				</a>
				</div>
				<h4><a href="<?php echo htmlspecialchars(getAlbumLinkURL());?>" title="<?php echo $annotate; ?>"><?php printAlbumTitle(); ?></a></h4>
			</li>
			<?php } ?>
		</ul>
		<div class="clearage"></div>
		<?php printNofM('Album', $firstAlbum, $lastAlbum, getNumAlbums()); ?>

		<!-- Page Numbers -->
		<div id="pagenumbers">
			<?php printPageListWithNav("&laquo; ".gettext('prev'), gettext('next')." &raquo;"); ?>
		</div>

	</div>

	<!-- Footer -->
	<div class="footlinks">
		<?php if (function_exists('printLanguageSelector')) { printLanguageSelector(); } ?>
		<small>
			<p><?php $albumNumber = getNumAlbums(); echo sprintf(gettext("Albums: %u"),$albumNumber); ?> &middot;
				<?php echo sprintf(gettext("Subalbums: %u"),get_subalbum_count()); ?> &middot;
				<?php $photosArray = query_single_row("SELECT count(*) FROM ".prefix('images'));
				$photosNumber = array_shift($photosArray); echo sprintf(gettext("Images: %u"),$photosNumber); ?>
				<?php if (getOption('Allow_comments')) { ?>
					&middot;
					<?php $commentsArray = query_single_row("SELECT count(*) FROM ".prefix('comments')." WHERE inmoderation = 0");
					$commentsNumber = array_shift($commentsArray); echo sprintf(gettext("Comments: %u"),$commentsNumber); ?>
				<?php } ?>
			</p>
			<?php printThemeInfo(); ?>
		</small>
		<?php echo gettext('Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album"><font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a>'); ?><br />
		<?php printRSSLink('Gallery','', 'Gallery RSS', ''); ?> 
		<?php
		if (function_exists('printUserLogout')) {
			printUserLogout('<br />', '', true);
		}
		?>
		</div>

		<?php printAdminToolbox(); ?>

</body>
</html>