<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php zenJavascript(); ?>
<head>
<?php printSlideShowCSS($_POST['size']); ?>

</head>
<body>
	<div id="slideshowpage">
			<?php printSlideShow(); ?>
	</div>

</body>
</html>