<?php 
define('IMAGECOLUMNS', 7);
if (!defined('WEBPATH')) die(); 
$startTime = array_sum(explode(" ",microtime())); 
$firstPageImages = normalizeColumns(1, 7);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?> | Search</title>
  <link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
  <?php printRSSHeaderLink('Gallery','Gallery RSS'); ?>
  <?php zenJavascript(); ?>
</head>
<body>

<div id="main">
  <div id="gallerytitle">
    <h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a></span> | Search<?php printSearchForm(); ?></h2>
  </div>
  <hr />
  
  <br />
  
  <?php 
  		  if ($_REQUEST['words']) {
		    if (getNumImages() != "0") {	
	  		  echo "<p>Total matches for <em>".getSearchWords()."</em>: ".getNumImages()."</p>";
	          while (next_image()) {
	            echo "<div class=\"image\"><div class=\"imagethumb\"><a href=\"".
	                 getImageLinkURL()."\"><img src='".getImageThumb().
	                 "' alt='".getImageTitle()."' /></a>";
                echo "</div></div>";
              }				
	  		  echo "<br clear=\"all\">";
	  		  printPageListWithNav("&laquo; prev","next &raquo;");
			} else { 
	  		  echo "<p>Sorry, no matches. Try refining your search.</p>"; 
    		}
  		  }
  ?>
  <hr class="space" />
  
  <p style="text-align: right;"><?php printRSSLink('Gallery', '', 'Gallery RSS', ''); ?>
    <?php /* Timer */ echo round((array_sum(explode(" ",microtime())) - $startTime),4)." Seconds, $_zp_query_count queries ran."; ?></p>

</div>

<?php printAdminToolbox(); ?>

</body>
</html>