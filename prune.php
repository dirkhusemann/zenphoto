<?php 
require_once("zen/template-functions.php"); /* Don't put anything before this line! */ 
$_zp_gallery->garbageCollect(true, true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
  <title><?php printGalleryTitle(); ?></title>
  <link rel="stylesheet" href="/zp/zen.css" type="text/css" />
</head>
<body>
<div id="main">
  <div id="gallerytitle">
    <h2><?php echo getGalleryTitle(); ?></h2>
  </div>
  
  <hr />
  <h3>Database pruned. <a href="<?= WEBPATH ?>">&laquo; Back to photo index</a></h3>


</div>

</body>
</html>
