<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
</head>
<body>
<?php
echo "\n<strong>".gettext("Zenphoto Error:</strong> the requested object was not found.");
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
<br />
<a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo gettext("Return to").' '.getGalleryTitle();?></a>
</body>
</html>