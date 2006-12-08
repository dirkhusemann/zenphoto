<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php if (zp_conf('website_title') != '') { echo zp_conf('website_title') . '&#187;'; } ?><?php printGalleryTitle(); ?> &#187; License</title>
	<link rel="stylesheet" href="<?php echo  $_zp_themeroot ?>/css/master.css" type="text/css" />
	<?php zenJavascript(); ?>
</head>

<body class="index">

<div id="content" style="text-align: left;">

<h1>License & Terms</h1>
<p>This gallery design and set of template files that recreate it are licensed under a <a href="http://creativecommons.org/licenses/by-nc-sa/2.5/">Creative Commons Attribution-NonCommercial-ShareAlike 2.5 License</a>.</p>
<p>You're free to download, use, modify, and repurpose these templates in any way you wish, as long as they're not used, bartered, or sold for commercial purposes. </p>
<p>If you redistribute any work, you must release it under the same license.  If you use this template, please leave the Template section of the ZenPhoto Index page as it is.</p>
<p>- Based upon the amazing <a href="http://stopdesign.com/templates/photos/">Gallery of Douglas Bowman</a> of stopdesign.</p>
<p>- Run using <a href="http://www.zenphoto.org/">Zen Photo</a> - the best php photo gallery system out there.</p>
<p>- <a href="http://www.bleecken.de/zenphoto/">Sjard Bleeckens</a> awesome take on Dougs theme.</p>
<p>- The js image fade in is from <a href="http://www.splintered.co.uk">Patrick H Lauke</a>.</p>
<p>- And a little work by me, <a href="http://www.BenSpicer.com" title="BenSpicer.com">Ben Spicer</a>.</p>
<br />
<h2><a  href="http://www.benspicer.com/files/stoppeddesign.zip">Download</a></h2><br />


</div>
<p id="path"><?php if (zp_conf('website_url') != '') { ?> <a href="<?php echo zp_conf('website_url'); ?>" title="Back"><?php echo zp_conf('website_title'); ?></a> &#187; <?php } ?>
  <a href="<?php echo getGalleryIndexURL();?>" title="Gallery Index"><?php echo getGalleryTitle();?></a> &#187; <a href="--------------" title="Photo License">License</a></p>
<div id="footer">
	<p><?php printAdminLink('Admin'); ?></p>
</div>
</body>
</html>