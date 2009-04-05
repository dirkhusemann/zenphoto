<?php
if (!defined('WEBPATH')) die(); $firstPageImages = normalizeColumns('2', '6');?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php echo getBareGalleryTitle(); ?></title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" type="text/css" />
	<?php printRSSHeaderLink('Gallery',gettext('Gallery RSS')); ?>
</head>
<body>

<div id="main">

		<div id="header">
			
		<h1><?php printGalleryTitle(); ?></h1>
		<?php if (getOption('Allow_search')) {  printSearchForm("","search","",gettext("Search gallery")); } ?>
		</div>

<div id="content">

	<div id="breadcrumb">
	<h2><a href="<?php echo getGalleryIndexURL(); ?>"><strong><?php echo gettext("Index"); ?></strong></a>
	</h2>
	</div>

	<div id="content-left">	
	<?php if(!getOption("zenpage_zp_index_news")) { ?>
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
		<br style="clear: both" />
		<?php printPageListWithNav("&laquo; ".gettext("prev"), gettext("next")." &raquo;"); ?>

	<?php } else { // news article loop
printNewsPageListWithNav(gettext('next &raquo;'), gettext('&laquo; prev')); 
echo "<hr />";	
while (next_news()): ;?> 
 <div class="newsarticle"> 
    <h3><?php printNewsTitleLink(); ?><?php echo " <span class='newstype'>[".getNewsType()."]</span>"; ?></h3>
        <div class="newsarticlecredit"><span class="newsarticlecredit-left"><?php printNewsDate();?> | <?php echo gettext("Comments:"); ?> <?php echo getCommentCount(); ?> | </span>
<?php
if(is_GalleryNewsType()) {
	if(!is_NewsType("album")) {
		echo gettext("Album:")."<a href='".getNewsAlbumURL()."' title='".getBareNewsAlbumTitle()."'> ".getNewsAlbumTitle()."</a>";
	} else {
		echo "<br />";
	}
} else {
	printNewsCategories(", ",gettext("Categories: "),"newscategories");
	
}
?>
</div>
    <?php printNewsContent(); ?>
    <p><?php printNewsReadMoreLink(); ?></p>
    <?php printCodeblock(1); ?>
    
    </div>	
<?php
  endwhile; 
  printNewsPageListWithNav(gettext('next &raquo;'), gettext('&laquo; prev'));
} ?> 

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