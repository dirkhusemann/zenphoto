<?php
define('ALBUMCOLUMNS', 3);
define('IMAGECOLUMNS', 5);
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
normalizeColumns(ALBUMCOLUMNS, IMAGECOLUMNS);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php $mainsite = getMainSiteName(); echo (empty($mainsite))?gettext("zenphoto gallery"):$mainsite; ?></title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo getOption('charset'); ?>" />
	<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo FULLWEBPATH . "/" . ZENFOLDER ?>/js/thickbox.css" type="text/css" />
	<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
</head>

<body onload="blurAnchors()">

	<!-- Wrap Header -->
	<div id="header">
		<div id="gallerytitle">

		<!-- Logo -->
			<div id="logo">
				<?php 
				if (getOption('Allow_search')) {  printSearchForm(NULL, '', $_zp_themeroot.'/images/search.png'); }
				printLogo();
				?>
			</div>
		</div> <!-- gallerytitle -->

		<!-- Crumb Trail Navigation -->
		<div id="wrapnav">
			<div id="navbar">
				<span><?php printHomeLink('', ' | '); echo (empty($mainsite))?'&nbsp;':$mainsite; ?></span>
			</div>
		</div> <!-- wrapnav -->

	</div> <!-- header -->

			<!-- The Image -->
			<?php
	 			makeImageCurrent(getRandomImages(true));
	 			$size = floor(getOption('image_size') * $imagereduction);
				$s = getDefaultWidth($size) + 22;
				$wide = " style=\"width:".$s."px;";
				$s = getDefaultHeight($size) + 72;
				$high = " height:".$s."px;\"";
			?>
			<div id="image" <?php echo $wide.$high; ?>>
			<p align="center">
			<?php echo gettext('Picture of the day'); ?>
			</p>
				<div id="image_container">
					<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>">
						<?php printCustomSizedImage(gettext('Visit the photo gallery'), $size); ?>
					</a>
				</div>
				<?php if (!$zenpage) { ?>
				<p align="center">
				<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo gettext('Visit the photo gallery');?></a>
				</p>
				<?php } ?>
			</div> <!-- image -->
			<br />
	<?php if($zenpage)  {?>
	<!-- Wrap Main Body -->
	<div id="content">
	
		<small>&nbsp;</small>
		<div id="main2">
			<div id="content-left">
			<?php commonNewsLoop(false); ?>	
			</div><!-- content left-->
			
			<div id="sidebar">
			<?php include("sidebar.php"); ?>
			</div><!-- sidebar -->
			<br style="clear:both" />
		</div> <!-- main2 -->
		
	</div> <!-- content -->
	<?php } ?>
<div class="aligncenter2">
<?php printGalleryDesc(); ?>
</div>
	
<?php printFooter(); ?>

</body>
</html>