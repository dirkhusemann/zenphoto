<?php if (!defined('WEBPATH')) die(); $themeResult = getTheme($zenCSS, $themeColor, 'light'); normalizeColumns(3, 5); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> | Archive View</title>
	<link rel="stylesheet" href="<?php echo  $zenCSS ?>" type="text/css" />
	<?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
	<?php zenJavascript(); ?>
</head>

<body>
<?php printAdminToolbox(); ?>

<div id="main">

	<div id="gallerytitle">
		<h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a></span> | Archive View</h2>
        <?php if (getOption('Allow_search')) {  printSearchForm(); } ?>
	</div>
    
    <div id="padbox">
    
		<div id="archive"><?php printAllDates(); ?></div>
		<div id="tag_cloud">
        	<p>Popular Tags</p>
			<?php printAllTagsAs('cloud', 'tags'); ?>
        </div>
        
	</div>

</div>

<div id="credit"><?php printRSSLink('Gallery','','RSS', ' | '); ?>Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

</body>
</html>
