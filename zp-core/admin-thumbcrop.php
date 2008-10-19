<?php
define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
	
		$albumname = sanitize($_REQUEST['a']);
		$imagename = sanitize($_REQUEST['i']);
		
		// get full width and height
		$albumobj = new Album(new Gallery,$albumname);
		$imageobj = new Image($albumobj,$imagename);
		$currentthumbimage = $imageobj->getThumb();
		$width = $imageobj->getWidth();
		$height = $imageobj->getHeight();
		setOption('image_use_longest_side', 1, false);
		$size = min(400, $width, $height);
		$cropwidth = getOption("thumb_crop_width");
		$cropheight = getOption("thumb_crop_height");
		if ($width >= $height) {
			$sr = $size/$width;
			$sizedwidth = $size;
			$sizedheight = round($height/$width*$size);
		} else {
			$sr = $size/$height;
			$sizedwidth = Round($width/$height*$size);
			$sizedheight = $size;
		}
		$imageurl = "i.php?a=".$albumname."&i=".$imagename."&s=".$size.'&t=true';
		
		$iY = round($imageobj->get('thumbY')*$sr);
		if ($iY) {
			$iX = round($imageobj->get('thumbX')*$sr);
			$iW = round($imageobj->get('thumbW')*$sr);
			$iH = round($imageobj->get('thumbH')*$sr);
		} else {
			$cr = max($cropwidth,$cropheight)/getOption('thumb_size');
			$si = min($sizedwidth,$sizedheight);
			$iW = round($si*$cr);
			$iH = round($si*$cr);
			$iX = round(($sizedwidth - $iW)/2);
			$iY = round(($sizedheight - $iH)/2);
		}

		if (isset($_REQUEST['crop'])) {
			$cw = $_REQUEST['w'];
			$ch = $_REQUEST['h'];
			$cx = $_REQUEST['x'];
			$cy = $_REQUEST['y'];
			if (isset($_REQUEST['clear_crop']) || ($cw == 0 && $ch == 0)) {
				$cx = $cy = $cw = $ch = NULL;
			} else {
		
				$rw = $width/$sizedwidth;
				$rh = $height/$sizedheight;
				$cw = round($cw*$rw);
				if ($cropwidth == $cropheight) {
					$ch = $cw;
				} else {
					$ch = round($ch*$rh);
				}
				$cx = round($cx*$rw);
				$cy = round($cy*$rh);
				if ($cx == 0) $cx = 1;
				if ($cy == 0) $cy = 1;
			}
			$imageobj->set('thumbX', $cx);
			$imageobj->set('thumbY', $cy);
			$imageobj->set('thumbW', $cw);
			$imageobj->set('thumbH', $ch);
			$imageobj->save();
		
			$return = '/admin.php?page=edit&album=' . urlencode($albumname).'&saved&subpage='.sanitize($_REQUEST['subpage']).'&tagsort='.sanitize($_REQUEST['tagsort']).'#tab_imageinfo';
			header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . $return);
			exit();
			}
	$subpage = sanitize($_REQUEST['subpage']);
	$tagsort = sanitize($_REQUEST['tagsort']);
	printAdminHeader();
?>
	
		<script src="js/jquery.Jcrop.pack.js"></script>
		<link rel="stylesheet" href="js/jquery.Jcrop.css" type="text/css" />
		<script language="Javascript">
			// Remember to invoke within jQuery(window).load(...)
			// If you don't, Jcrop may not initialize properly
			jQuery(window).load(function(){

				jQuery('#cropbox').Jcrop({
//					onChange: showPreview,
//					onSelect: showPreview,
					onChange: showCoords,
					setSelect: [ <?php echo $iX; ?>, <?php echo $iY; ?>, <?php echo $iX+$iW; ?>, <?php echo $iY+$iH; ?> ],					
					bgOpacity:   .4,
					bgColor:     'black',
					aspectRatio: <?php echo $cropwidth; ?> / <?php echo $cropheight; ?>
					});
			});

			// Our simple event handler, called from onChange and onSelect
			// event handlers, as per the Jcrop invocation above
			function showPreview(coords)
			{
				var rx = <?php echo $cropwidth; ?> / coords.w;
				var ry = <?php echo $cropheight; ?> / coords.h;

				jQuery('#preview').css({
					width: Math.round(rx * <?php echo $sizedwidth; ?>) + 'px', // we need to calcutate the resized width and height here...
					height: Math.round(ry * <?php echo $sizedheight; ?>) + 'px',
					marginLeft: '-' + Math.round(rx * coords.x) + 'px',
					marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
			}
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
 	<div class="jcExample">
	<div class="article">
		<h1><?php echo gettext("Custom thumbnail cropping"); ?></h1>
		
		<p><?php echo gettext("You can change the protion of your image which is shown in thumbnails by cropping it here."); ?></p>
		
		<div style="display:block">
	<div style="width: <?php echo $sizedwidth; ?>px; height: <?php echo $sizedheight; ?>px; border: 1px solid gray; margin-bottom: 10px; margin-right: 10px; float: left">
		<!-- This is the image we're attaching Jcrop to -->
		<img src="<?php echo $imageurl; ?>" id="cropbox" />
	</div>
	
<?php /*
  <div style="float: left; width:<?php echo $cropwidth; ?>px; text-align: center; margin: 0px 10px 0px 10px;">
	<div style="width:<?php echo $cropwidth; ?>px;height:<?php echo $cropheight; ?>px; overflow:hidden; border: 1px solid gray; float: left">
		<img src="<?php echo $imageurl; ?>" id="preview" />
	</div>
	<?php echo gettext("preview"); ?>
	</div>
*/ ?>
	
	<div style="float: left; width:<?php echo $cropwidth; ?>px; text-align: center; margin: 0px 10px 0px 10px;">
	<img src="<?php echo $currentthumbimage; ?>" style="width:<?php echo $cropwidth; ?>px;height:<?php echo $cropheight; ?>px; border: 1px solid gray; float: left"/>
 <?php echo gettext("current"); ?>
 </div>

</div>
	
	<br style="clear:both" />
		<!-- This is the form that our event handler fills -->
		<form name="crop" id="crop" action="?crop" onsubmit="return checkCoords();">
			<input type="hidden" size="4" id="x" name="x" value="<?php echo $iX ?>" />
			<input type="hidden" size="4" id="y" name="y" value="<?php echo $iY ?>" />
			<input type="hidden" size="4" id="x2" name="x2" value="<?php echo $iX+iW ?>" />
			<input type="hidden" size="4" id="y2" name="y2" value="<?php echo $iY+iH ?>" />
			<input type="hidden" size="4" id="w" name="w" value="<?php echo $iW ?>" />
			<input type="hidden" size="4" id="h" name="h" value="<?php echo $iH ?>"  />
			<input type="hidden" id="cropw" name="cropw" value="<?php echo $cropwidth; ?>" />
			<input type="hidden" id="croph" name="croph" value="<?php echo $cropheight; ?>" />
			<input type="hidden" id="a" name="a" value="<?php echo $albumname; ?>" />
			<input type="hidden" id="i" name="i" value="<?php echo $imagename; ?>" />
			<input type="hidden" id="tagsort" name="tagsort" value="<?php echo $tagsort; ?>" />
			<input type="hidden" id="subpage" name="subpage" value="<?php echo $subpage; ?>" />
			<input type="hidden" id="crop" name="crop" value="crop" />
			<input type="checkbox" name="clear_crop" value="1" /> <?php echo gettext("Reset to the default cropping"); ?><br>
			<input type="submit" size="4" id="submit" name="submit" value="<?php echo gettext("Save the cropping"); ?>" style="margin-top: 10px" />
			<input type="button"  value="<?php echo gettext("cancel"); ?>" style="margin-top: 10px" 
								onClick="window.location='admin.php?page=edit&amp;album=<?php echo urlencode($albumname); ?>&amp;subpage=<?php echo $subpage; ?>&amp;tagsort=<?php echo $tagsort; ?>#tab_imageinfo'" />
		</form>

	</div>
	</div>
	</div><!-- div id content end -->
	<?php printAdminFooter(); ?>
	</div><!-- div id=mail end -->
	</body>

</html>
