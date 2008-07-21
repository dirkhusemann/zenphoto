<?php
require_once ('customfunctions.php');
$themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/slideshow.css" type="text/css" />
<?php printSlideShowJS(); ?>

</head>
<body>
	<div id="subcontent">
		<div id="submain">	
			<div id="logo2">
			<?php printLogo(); ?>
			</div>
			<div id="wrapnav">
				<div id="navbar">
					<span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo getGalleryTitle();?></a> | 
					<?php
					if (!is_null($_zp_current_album)) {
						printParentBreadcrumb(); 
						printAlbumBreadcrumb("", " | "); 
					} else {
						$search = new SearchEngine();
						$params = trim(zp_getCookie('zenphoto_image_search_params'));
						$search->setSearchParams($params);
						$images = $search->getImages(0);
						$searchwords = $search->words;
						$searchdate = $search->dates;
						$searchfields = $search->fields;
						$page = $search->page;
						$returnpath = getSearchURL($searchwords, $searchdate, $searchfields, $page);
						echo '<a href='.$returnpath.'><em>'.gettext('Search').'</em></a> | ';
					}
					?> </span> 
					Slideshow
				</div>
			</div>
			<div id="content">
 				<div id="main">
					<div id="slideshowpage">
					<?php printSlideShow(false,true); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Footer -->
	<div class="footlinks">
		<?php 
		printThemeInfo(); 
		?>
		<a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>"><?php echo gettext('Powered by').' ';?>
		<font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps"><font face="Arial Black" size="1">photo</font></span></a>
	</div>
</body>
</html>