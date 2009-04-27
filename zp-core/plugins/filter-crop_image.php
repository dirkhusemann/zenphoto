<?php
/**
 * Provides extensions to the admin toolbox to crop images.
 * This is intended as an example only.
 *
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */

$plugin_description = gettext("An image crop tool for the admin toolbox on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---crop_image.php.html";

if (!isset($_REQUEST['performcrop'])) {
	register_filter('admin_toolbox_image', 'toolbox_crop_image', 2);
	register_filter('edit_image_utilities', 'edit_crop_image', 4);
	
	return;
}

function toolbox_crop_image($albumname, $imagename) {
	if (zp_loggedin(ADMIN_RIGHTS | EDIT_RIGHTS)) {
		?>
		<li>
		<a href="<?php echo WEBPATH."/".ZENFOLDER . PLUGIN_FOLDER; ?>filter-crop_image.php?a=<?php echo pathurlencode($albumname); ?>
				&amp;i=<?php echo urlencode($imagename); ?>&amp;performcrop=frontend "><?php echo gettext("Crop image"); ?></a>
		</li>
		<?php
	}
}

function edit_crop_image($albumname, $imagename, $subpage, $tagsort) {
	?>
	<hr />
	<p>
		<a href="<?php echo WEBPATH."/".ZENFOLDER . PLUGIN_FOLDER; ?>filter-crop_image.php?a=<?php echo pathurlencode($albumname); ?>
				&amp;i=<?php echo urlencode($imagename); ?>&amp;performcrop=backend&amp;subpage=<?php echo $subpage; ?>&amp;tagsort=<?php echo $tagsort; ?>">
				<?php echo gettext("Crop image"); ?></a>
	?>
	</p>
	<?php
}

if (!defined('OFFSET_PATH')) define('OFFSET_PATH', 3);
require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
require_once(dirname(dirname(__FILE__)).'/functions-image.php');

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

if (!($_zp_loggedin & (THEMES_RIGHTS | ADMIN_RIGHTS))) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}

$albumname = sanitize_path($_REQUEST['a']);
$imagename = sanitize_path($_REQUEST['i']);

// get full width and height
$gallery = new Gallery();
$albumobj = new Album($gallery,$albumname);
$imageobj = newImage($albumobj,$imagename);

if (isImagePhoto($imageobj)) {
	$imgpath = $imageobj->localpath;
	$imagepart = basename($imgpath);
	$timg = imageGet($imgpath);
	$width = imageWidth($timg);
	$height = imageHeight($timg);
} else {
	die(gettest('attempt to crop an object which is not an image.'));
}
	
$size = min(400, $width, $height);
if ($width >= $height) {
	$sr = $size/$width;
	$sizedwidth = $size;
	$sizedheight = round($height/$width*$size);
} else {
	$sr = $size/$height;
	$sizedwidth = Round($width/$height*$size);
	$sizedheight = $size;
}

$imageurl = "../i.php?a=".pathurlencode($albumname)."&i=".urlencode($imagename)."&s=".$size.'&admin';
$iW = $sizedwidth;
$iH = $sizedheight;
$iX = 0;
$iY = 0;

if (isset($_REQUEST['crop'])) {
	$cw = $_REQUEST['w'];
	$ch = $_REQUEST['h'];
	$cx = $_REQUEST['x'];
	$cy = $_REQUEST['y'];

	$rw = $width/$sizedwidth;
	$rh = $height/$sizedheight;
	$cw = round($cw*$rw);
	$ch = round($ch*$rh);
	$cx = round($cx*$rw);
	$cy = round($cy*$rh);
	if ($cx == 0) $cx = 1;
	if ($cy == 0) $cy = 1;
	
	//create a new image with the set cropping
	$quality = getOption('full_image_quality');
	if (DEBUG_IMAGE) debugLog("image_crop: crop ".basename($imgpath).":\$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy");
	$newim = createImage($cw, $ch);
	resampleImage($newim, $timg, 0, 0, $cx, $cy, $cw, $ch, $cw, $ch);
	@unlink($imgpath);
	if (imageOutput($newim, 'jpg', $imgpath, $quality)) {
		if (DEBUG_IMAGE) debugLog('image_crop Finished:'.basename($imgpath));
	} else {
		if (DEBUG_IMAGE) debugLog('image_crop: failed to create '.$imgpath);
	}
	@chmod($imgpath, 0666 & CHMOD_VALUE);
	imageKill($newim);
	imageKill($timg);
	$gallery->clearCache(SERVERCACHE . '/' . $albumname);
	// be sure the diminsions reflect the new cropping
	$imageobj->updateDimensions();
	$imageobj->save();
	
	if ($_REQUEST['performcrop']=='backend') {
		$return = FULLWEBPATH . '/' . ZENFOLDER . '/admin-edit.php?page=edit&album=' . pathurlencode($albumname).'&saved&subpage='.sanitize($_REQUEST['subpage']).'&tagsort='.sanitize($_REQUEST['tagsort']).'&tab=imageinfo';
	} else {
		$return = FULLWEBPATH . $imageobj->getImageLink();
	}

	header('Location: ' . $return);
	exit();
	}
if (isset($_REQUEST['subpage'])) {
	$subpage = sanitize($_REQUEST['subpage']);
	$tagsort = sanitize($_REQUEST['tagsort']);
} else {
	$subpage = $tagsort = '';
}
printAdminHeader('../');
?>

<script src="../js/jquery.Jcrop.pack.js"></script>
<link rel="stylesheet" href="../js/jquery.Jcrop.css" type="text/css" />
<script language="Javascript">
	// Remember to invoke within jQuery(window).load(...)
	// If you don't, Jcrop may not initialize properly
	jQuery(window).load(function(){

		jQuery('#cropbox').Jcrop({
			onChange: showCoords,
			bgOpacity:   .4,
			bgColor:     'black'
			});
	});

	// Our simple event handler, called from onChange and onSelect
	// event handlers, as per the Jcrop invocation above
	function showCoords(c) {
		jQuery('#x').val(c.x);
		jQuery('#y').val(c.y);
		jQuery('#x2').val(c.x2);
		jQuery('#y2').val(c.y2);
		jQuery('#w').val(c.w);
		jQuery('#h').val(c.h);
	};
	function checkCoords() {
		return true;
	};
</script>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	 	
	<div id="main">
		<?php printTabs('edit'); ?>
		<div id="content">
				<h1><?php echo gettext("Image cropping"); ?></h1>
				<p><?php echo gettext("You can crop your image by draging the your mouse on the image.<br /><br /><strong>NOTE:</strong> these changes are permanent!."); ?></p>
				<div style="display:block">
		 			
					<div style="text-align:left; float: left;">
					
						<div style="width: <?php echo $sizedwidth; ?>px; height: <?php echo $sizedheight; ?>px; margin-bottom: 10px; border: 4px solid gray;">
							<!-- This is the image we're attaching Jcrop to -->
							<img src="<?php echo $imageurl; ?>" id="cropbox" />
						</div>
						
						<!-- This is the form that our event handler fills -->
						<form name="crop" id="crop" action="?crop" onsubmit="return checkCoords();">
							<input type="hidden" size="4" id="x" name="x" value="<?php echo $iX ?>" />
							<input type="hidden" size="4" id="y" name="y" value="<?php echo $iY ?>" />
							<input type="hidden" size="4" id="x2" name="x2" value="<?php echo $iX+$iW ?>" />
							<input type="hidden" size="4" id="y2" name="y2" value="<?php echo $iY+$iH ?>" />
							<input type="hidden" size="4" id="w" name="w" value="<?php echo $iW ?>" />
							<input type="hidden" size="4" id="h" name="h" value="<?php echo $iH ?>"  />
							<input type="hidden" id="a" name="a" value="<?php echo $albumname; ?>" />
							<input type="hidden" id="i" name="i" value="<?php echo $imagename; ?>" />
							<input type="hidden" id="tagsort" name="tagsort" value="<?php echo $tagsort; ?>" />
							<input type="hidden" id="subpage" name="subpage" value="<?php echo $subpage; ?>" />
							<input type="hidden" id="crop" name="crop" value="crop" />
							<input type="hidden" id="performcrop" name="performcrop" value="<?php echo $_REQUEST['performcrop'] ?>" />
							<br />	
							<p class="buttons">
							<button type="submit" id="submit" name="submit" value="<?php echo gettext('Save the cropping') ?>" title="<?php echo gettext("Save"); ?>"><img src="../images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
							<?php
							if ($_REQUEST['performcrop'] == 'backend') {
								?>
								<button type="reset" value="<?php echo gettext('Cancel') ?>" title="<?php echo gettext("Cancel"); ?>" onClick="window.location='../admin-edit.php?page=edit&amp;album=<?php echo urlencode($albumname); ?>&amp;subpage=<?php echo $subpage; ?>&amp;tagsort=<?php echo $tagsort; ?>&amp;tab=imageinfo'"><img src="../images/reset.png" alt="" /><strong><?php echo gettext("Cancel"); ?></strong></button>
								</p><br />
								<?php
							} else {
								?>
								<button type="reset" value="<?php echo gettext('Cancel') ?>" title="<?php echo gettext("Cancel"); ?>" onClick="window.location='/index.php?album=<?php echo urlencode($albumname); ?>&amp;image=<?php echo urlencode($imagename); ?>'"><img src="../images/reset.png" alt="" /><strong><?php echo gettext("Cancel"); ?></strong></button>
								</p><br />
								<?php
							}
							?>			
						</form>

					</div>
					
				<br style="clear: both" />
				</div><!-- block -->
	
		</div><!-- content -->
		
	<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>

</html>
