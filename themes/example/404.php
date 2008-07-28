<?php if (!defined('WEBPATH')) die();?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php zenJavascript(); ?>
	<title><?php printGalleryTitle(); ?> | <?php echo gettext("Object not found"); ?></title>
		<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
</head>
<body>
<div id="main">
		<div id="gallerytitle">
			<h2><span><?php printHomeLink('', ' | '); ?><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo getGalleryTitle();?></a></span> | <?php echo gettext("Object not found"); ?></h2>
		</div>
	
		<hr />
		
 	<?php 
		echo gettext("The Zenphoto object you are requesting cannot be found,");
		if (isset($album)) {
			echo '<br />'.gettext("Album").': '.sanitize($album);
		}
		if (isset($image)) {
			echo '<br />'.gettext("Image").': '.sanitize($image);
		}
		if (isset($obj)) {
			echo '<br />'.gettext("Theme page").': '.substr(basename($obj),0,-4);
		}
 	?>
	<br /><br />
	<div id="credit"><?php echo gettext("Powered by"); ?> <a href="http://www.zenphoto.org" title="<?php echo gettext('A simpler web photo album'); ?>">zenphoto</a></div>

</div>

<?php printAdminToolbox(); ?>

</body>
</html>
