<?php 
if (!defined('WEBPATH')) die();
$firstPageImages = normalizeColumns(2, 6);
#========================[Private album]===========================
/* $newwws_password is set in the album specific configuration file, but */
/* if the name of an album starts starts with an underscore, we consider it to be */
/* a private album too. The default-password will be zen */
if (substr(getAlbumTitle(), 0, 1) == "_" && empty($newwws_password)) $newwws_password = "zen";
/* Check if this is a private album and ask for a password */
$newwws_passwordfile = $themepath . "/" . $theme . "/" . "password.php";
$album_image_file = $themepath . "/" . $theme . "/" . "album_images.php";
if (empty($newwws_password)){
$newwws_authenticated = false;
} else {
$newwws_authenticated = true;
}
$newwws_password_soll = $newwws_password;
$newwws_password_ist = $_POST['newwws_pw'];
if($_COOKIE["newwws_".strtolower(str_replace(" ", "_", getAlbumTitle()))] == "OK") $newwws_password_ist = $newwws_password_soll;
if ($newwws_password_ist == $newwws_password_soll) {
$newwws_authenticated = false;
setcookie ("newwws_".strtolower(str_replace(" ", "_", getAlbumTitle())),"OK",time()+1800, "/");
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?php printGalleryTitle(); ?> | <?php echo getAlbumTitle();?></title>
	<link rel="stylesheet" href="<?php echo $_zp_themeroot ?>/zen.css" type="text/css" />
	<?php printRSSHeaderLink('Album',getAlbumTitle()); ?>
	<?php zenJavascript(); ?>
</head>

<body>

<div id="main">

	<div id="gallerytitle">
		<h2><span><a href="<?php echo getGalleryIndexURL();?>" title="Albums Index"><?php echo getGalleryTitle();?></a> | <?php printParentBreadcrumb(); ?></span> <?php printAlbumTitle(true);?></h2>
	</div>
	
	<?php printAlbumDesc(true); ?>
	
	<?php
	if ($newwws_authenticated){
		include("$newwws_passwordfile");
	} else {
  		include("$album_image_file");
	}
	?>
	
	<?php printPageListWithNav("&laquo; prev", "next &raquo;"); ?>
	
</div>

<div id="credit"><?php printRSSLink('Album', '', 'Album RSS', ''); ?> | Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album">zenphoto</a></div>

<?php printAdminToolbox(); ?>

</body>
</html>