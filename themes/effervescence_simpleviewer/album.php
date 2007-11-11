<?php 
define('ALBUMCOLUMNS', 3);
define('IMAGECOLUMNS', 5);
if (!defined('WEBPATH')) die(); 
$_noFlash = false;
if ((getOption('Use_Simpleviewer')==0) || !getOption('mod_rewrite')) { $_noFlash = true; }

if (isset($_GET['noflash'])) {
  $_noFlash = true;
  setcookie("noFlash", "noFlash");
  } elseif (isset($_COOKIE["noFlash"])) {
  $_noFlash = true;
}
  
// Change the configuration here

$maxImageWidth="600";
$maxImageHeight="600";

$preloaderColor="0xFFFFFF";
$textColor="0xFFFFFF";
$frameColor="0xFFFFFF";

$frameWidth="10";
$stagePadding="20";

$thumbnailColumns="3";
$thumbnailRows="6";
$navPosition="left";

$enableRightClickOpen="true";

$backgroundImagePath="";
// End of config

if ($_GET['format'] != 'xml') { 
  require_once ('customfunctions.php');  
  $themeResult = getTheme($zenCSS, $themeColor, 'effervescence');
  $firstPageImages = normalizeColumns(ALBUMCOLUMNS, IMAGECOLUMNS);
  if ($_noFlash) {
    $backgroundColor = "#0";  // who cares, we won't use it
  } else {
    $backgroundColor = parseCSSDef($zenCSS);  
  }
  
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>


<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?></title>
<link rel="stylesheet" href="<?php echo $zenCSS ?>" type="text/css" />
<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/bluranchors.js"></script>
<script type="text/javascript" src="<?php echo  $_zp_themeroot ?>/scripts/swfobject.js"></script>
<link rel="stylesheet" href="<?= $_zp_themeroot ?>/slimbox/css/slimbox.css" type="text/css" media="screen" />
<!-- prep for lightbox, but doesn't work, get a cannot load if the slimbox.js loaded.
<script type="text/javascript" src="<?= $_zp_themeroot ?>/slimbox/js/mootools.js"></script>
<script type="text/javascript" src="<?= $_zp_themeroot ?>/slimbox/js/slimbox.js"></script>
-->
<?php zenJavascript(); global $_zp_current_album; ?>

</head>

<body onload="blurAnchors()">

<!-- Wrap Header -->
<div id="header">
  <div id="gallerytitle">

<!-- Subalbum Navigation -->
    <div class="albnav">
      <div class="albprevious">
	  <?php
	   if (is_null($album = $_zp_current_album->getPrevAlbum())) {
	    echo '<div class="albdisabledlink">&laquo; prev</div>';
	  } else {
	    echo '<a href="' . getPrevAlbumURL() .'" title="' . $album->getTitle() . '">&laquo; prev</a>';
	    } 
	  ?>
	  </div>
	  <div class="albnext">
	    <?php
	    if (is_null($album = $_zp_current_album->getNextAlbum())) {
	      echo '<div class="albdisabledlink">next &raquo;</div>';
	    } else {
	      echo '<a href="' . getNextAlbumURL() . '" title="' . $album->getTitle() . '">next &raquo;</a>';
	    } 
	    ?>
	  </div>
    </div>

<!-- Logo -->
    <div id="logo">
      <?php
      echo '<h1><a href="' . getMainSiteURL() . '" title="Visit ' . getMainSiteName() . '">' . $_SERVER['HTTP_HOST'] . '</a></h1>';
      ?>
    </div>
  </div>

<!-- Crumb Trail Navigation -->

<div id="wrapnav">
  <div id="navbar">
    <span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> | 
      <?php printParentBreadcrumb(); ?>
    </span> 
    <?php printAlbumTitle(true);?>
  </div>
</div>

<!-- Random Image -->
<?php if (isAlbumPage()) {printHeadingImage(getRandomImagesAlbum()); } ?>
</div>

<!-- Wrap Subalbums -->
<div id="subcontent">
<div id="submain">

<!-- Album Description -->
<div id="description">
<?php printAlbumDesc(true); ?>

</div>

	<!-- SubAlbum List -->
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
		<li><div class="imagethumb"><a href="<?php echo getAlbumLinkURL();?>" title="View the album: <?php echo getAlbumTitle();
			  printImage_AlbumCount();
			?>">
		<?php printCustomAlbumThumbImage(getCustomAlbumDesc(), null, 180, null, 180, 80); ?></a></div>
		<h4><a href="<?php echo getAlbumLinkURL();?>" title="View the album: <?php echo getAlbumTitle();
			  printImage_AlbumCount();
			?>">
		<?php printAlbumTitle(); ?></a></h4></li>
		<?php 
		  } 
		?>
	</ul> 
	<div class="clearage"></div>
	<?php printNofM('Album', $firstAlbum, $lastAlbum, getNumSubAlbums()); ?>
</div>

<!-- Wrap Main Body -->
       <?php 
       if (getNumImages() > 0){  /* Only print if we have images. */
         if ($_noFlash) {
	     ?>
           <div id="content">
           <div id="main">
           <div id="images">
           <?php 
           
           $firstImage = null;
           $lastImage = null;
           
           while (next_image(false, $firstPageImages)){  
             if (is_null($firstImage)) { 
               $lastImage = imageNumber();
               $firstImage = $lastImage; 
             } else {
               $lastImage++;
             }
             echo '<div class="image">' . "\n";
             echo '<div class="imagethumb">' . "\n";
			 
             echo '<a href="' . getImageLinkURL() .'" title="' . getImageTitle() . '">' . "\n";
			 /* lightbox, if we can get slimbox.js loaded
                                  echo "<a href=\"" . getCustomImageURL(550, null) . "\" rel=\"lightbox[" . getAlbumTitle() . 
                                           "]\" title=\"" . getImageTitle() . "\">";
                                  */
				  
			 
             echo printImageThumb(getImageTitle()) . "</a>\n";
             echo "</div>\n</div>\n";
           } ?>
           </div>
           </div>
	       <div class="clearage"></div>
           <?php printNofM('Photo', $firstImage, $lastImage, getNumImages()); ?>
           </div>
	     <?php 
	     } else {  /* flash */
	       if (isImagePage()) {
	       ?>
             <div id="flash"><p align=center><font color=#663300>For the best viewing experience <a href="http://www.macromedia.com/go/getflashplayer/">Get Macromedia Flash.</a></p> 
             <p align=center><a href="
             <?php 
             if ($imagePage) {
               $url = getPageURL(getTotalPages(true));
             } else {
               $url = getPageURL(getCurrentPage());
             } 
             if (substr($url, -1, 1) == '/') {$url = substr($url, 0, (strlen($url)-1));}
             echo $url = $url . (getOption("mod_rewrite") ? "?" : "&") . 'noflash'; 
             ?>">
             View gallery without Flash</a>.</p></font></div>
             <?php
             $flash_url = getAlbumLinkURL();	
             if (substr($flash_url, -1, 1) == '/') {$flash_url= substr($flash_url, 0, -1);}
             $flash_url = $flash_url . (getOption("mod_rewrite") ? "?" : "&") . "format=xml";
             ?>
             <script type="text/javascript">
                  var fo = new SWFObject("<?php echo  $_zp_themeroot ?>/simpleviewer.swf", "viewer", "100%", "100%", "7", "<?php echo $backgroundColor ?>");	
                  fo.addVariable("preloaderColor", "<?php echo $preloaderColor ?>");
                  fo.addVariable("xmlDataPath", "<?php echo $flash_url ?>");
                  fo.addVariable("width", "100%");
                  fo.addVariable("height", "100%");	
				  fo.addParam("wmode", "opaque");	
                  fo.write("flash");
             </script>
             <?php 
	       }
	     } /* image loop */
	   } else { /* no images to display */
		  if (getNumSubalbums() == 0){
		  ?>
	        <div id="main3">
	        <div id="main2">
	        <br />
	        <p align="center">Album is empty</font></p>
	        </div>
	        </div>
	      <?php 
	     } 
	   } ?>

<!-- Page Numbers -->
<?php
  echo '<div id="submain"><div id="pagenumbers">';
  if ((getNumSubalbums() != 0) || $_noFlash){
    printPageListWithNav("&laquo; prev", "next &raquo;", !$_noFlash); 
    echo "</div></div>";
  } 
  printAlbumMap();
  echo "</div></div>";
?>

</div>

<!-- Footer -->
<div class="footlinks">

<?php 
if (getOption('Use_Simpleviewer') && !getOption('mod_rewrite')) { 
  /* display missing css file error */
  echo '<div class="errorbox" id="message">'; 
  echo  "<h2>" . "Simpleviewer requires <em>mod_rewrite</em> to be set. Simpleviewer is disabled." . "</h2>";  
  echo '</div>'; 
  } 
printThemeInfo(); 
?>
<a href="http://www.zenphoto.org" title="A simpler web photo album">Powered by 
<font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps"><font size="1" face="Arial Black">photo</font></span></a><br />
<?php printRSSLink('Album', '', 'Album RSS', ''); ?>

</div>
</div>

<?php printAdminToolbox(); ?>

</body>
</html>
<?php
} else {
  header ('Content-Type: application/xml');

  $path = '';
  $levels = explode('/', getAlbumLinkURL());
  foreach ($levels as $v) {$path = $path . '../';}
  $path=substr($path, 0, -1);

  echo '<?xml version="1.0" encoding="UTF-8"?>
  <simpleviewerGallery title=""  maxImageWidth="'.$maxImageWidth.'" maxImageHeight="'.$maxImageHeight.
    '" textColor="'.$textColor.'" frameColor="'.$frameColor.'" frameWidth="'.$frameWidth.'" stagePadding="'.
    $stagePadding.'" thumbnailColumns="'.$thumbnailColumns.'" thumbnailows="'.$thumbnailRows.'" navPosition="'.
    $navPosition.'" enableRightClickOpen="'.$enableRightClickOpen.'" backgroundImagePath="'.$backgroundImagePath.
    '" imagePath="'.$path.'" thumbPath="'.$path.'">'; 
  while (next_image(true)){ 
    if (!getImageVideo()) {  // simpleviewer does not do videos
?>
      <image><filename><?php echo getDefaultSizedImage();?></filename>
        <caption>
	      <![CDATA[<a href="<?php echo getImageLinkURL();?>" title="Open in a new window">
          <font face="Times"><u><b><em><?php echo getImageTitle() ?></font></em></b></u></a></u>
          <br /></font><?php echo getImageDesc(); ?>]]>
	    </caption>
      </image>
<?php
    }
  }
  echo "</simpleviewerGallery>";
} 
?>