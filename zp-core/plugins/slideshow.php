<?php

/**
 * Prints a link to call the slideshow
 * To be used on album.php and image.php
 *
 * @param int $size The size of the slideshow slides
 * @param string $linktext Text for the link
 */
function printSlideShowLink($size='', $linktext='View slideshow') {
 	global $_zp_current_image;
	if(empty($size)) {
		$size = getOption('image_size');
	} else {
		$size = $size;
	}
	if(in_context(ZP_IMAGE)) {
		$imagenumber = imageNumber();
		$imagefile = $_zp_current_image->filename;
	} else {
		$imagenumber = "";
		$imagefile = "";	
	}
	if(empty($_GET['page'])) { 
		$pagenr = 1; 
	} else {
		$pagenr = $_GET['page'];
	}
	$numberofimages = getNumImages();
	
?>	
	<form name="slideshow" method="post" action="<?php echo getCustomPageURL('slideshow'); ?>">
		<input type="hidden" name="pagenr" value="<?php echo $pagenr;?>" />
		<input type="hidden" name="albumid" value="<?php echo getAlbumID();?>" />
		<input type="hidden" name="size" value="<?php echo $size;?>" />
		<input type="hidden" name="numberofimages" value="<?php echo $numberofimages;?>" />
		<input type="hidden" name="imagenumber" value="<?php echo $imagenumber;?>" />
		<input type="hidden" name="imagefile" value="<?php echo $imagefile;?>" />
		<a href="javascript:document.slideshow.submit()"><?php echo $linktext; ?></a>
	</form>
<?php
}


/**
 * Prints the slideshow using the jQuery plugin Cycle: http://http://www.malsup.com/jquery/cycle/
 * If called from image.php it starts with that image, called from album.php it starts with the first image.
 * To be used on slideshow.php only and called from album.php or image.php. 
 * Image size is taken from the calling link or if not specified there the sized image size from the options
 * Basic video support, the slideshow has to be stopped to view a movie.
 * 
 * @param string $effect The Cycle slide effect to be used: "fade", "shuffle", "zoom", "slideX", "slideY" (crollUp/Down/Left/Right currently does not work)
 * @param int $speed Speed of the transition (any valid fx speed value) 
 * @param int $timeout milliseconds between slide transitions (0 to disable auto advance)
 */
function printSlideShow($effect='fade', $speed=300, $timeout=3000, $showdesc=true) {
	if(empty($_POST['imagenumber'])) {
		$imagenumber = 0; 
		$count = 0;
	} else {
		$imagenumber = ($_POST['imagenumber']-1); // slideshows starts with 0, but zp with 1.
		$count = $_POST['imagenumber'];
	}
	$numberofimages = sanitize_numeric($_POST['numberofimages']);
	$albumid = sanitize_numeric($_POST['albumid']);
	$imagesize = sanitize_numeric($_POST['size']);
	if(empty($imagesize)) {
		$imagesize = getOption('image_size');
	} 
	
	// jQuery Cycle slideshow config
?>
	<script type="text/javascript">
		$(function() {
			$('#pause').click(function() { $('#slides').cycle('pause'); return false; });
			$('#play').click(function() { $('#slides').cycle('resume'); return false; });
						
			$('#slideshow').hover(
					function() { $('#controls').fadeIn(); },
					function() { $('#controls').fadeOut(); }
			);
						
			$('#slides').cycle({
					fx:     '<?php echo $effect; ?>',
					speed:   <?php echo $speed; ?>,
					timeout: <?php echo $timeout; ?>,
					next:   '#next',
					prev:   '#prev',
					delay: 2000,
					startingSlide: <?php echo $imagenumber; ?>
			});
		});
	</script>
<?php
	// get slideshow data
	$album = query_single_row("SELECT title, folder FROM ". prefix('albums') ." WHERE `show` = 1 AND id = ".$albumid);
	if(!checkAlbumPassword($album['folder'], $hint)) {
		echo "This album is password protected!"; exit;
	}		
	$images = query_full_array("SELECT title, filename, `desc` FROM ". prefix('images') ." WHERE `show` = 1 AND albumid = ".$albumid." ORDER BY sort_order");
	
	// slideshow display section
	// 1. the slideshow controls
	?>
	<div id="slideshow" align="center">
		<div id="controls">
		<div>
			<span><a href="#" id="prev"></a></span>
  	<?php
			if (empty($_POST['imagenumber'])) {
				if(getOption('mod_rewrite')) {
					$returnpath = WEBPATH.'/'.$album['folder'].'/page/'.$_POST['pagenr'];
				} else {
					$returnpath = WEBPATH.'/index.php?album='.$album['folder'].'&page='.$_POST['pagenr'];
				}
			} else {
				if(getOption('mod_rewrite')) {
					$returnpath = WEBPATH.'/'.$album['folder'].'/'.$_POST['imagefile'].getOption('mod_rewrite_image_suffix');
				} else {
					$returnpath = WEBPATH.'/index.php?album='.$album['folder'].'&image='.$_POST['imagefile'];
				}
			}
			?>
			<a href="<?php echo $returnpath; ?>" id="stop"></a>
  		<a href="#" id="pause"></a>
			<a href="#" id="play"></a>
			<a href="#" id="next"></a>
		</div>
		</div>
		<div id="slides" class="pics">  
<?php
		// 2. the slides
		foreach($images as $image) {
			$count++;
			if($count > $numberofimages){
				$count = 1;
			}
			$imagepath = FULLWEBPATH."/albums/".$album['folder']."/".$image['filename'];
			$ext = strtolower(strrchr($image['filename'], "."));
			echo "<span class='slideimage'><h4><strong>".$album['title'].":</strong> ".$image['title']." (".$count."/".$numberofimages.")</h4>";
			if ($ext == ".flv") {
				//Player Embed...
				echo '</a>
			<p id="playerContainer"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/extensions/FlowPlayerLight.swf\',
      	width:400, 
      	height:300
    	},
    		{config: {  
      		autoPlay: false,
					loop: false,
      		videoFile: \'' . $imagepath . '\',
      		initialScale: \'scale\'
    		}} 
  		);
			</script><a>';
			}
			elseif ($ext == ".3gp") {
				echo '</a>
				<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="352" height="304" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
				<param name="src" value="' . $imagepath. '"/>
				<param name="autoplay" value="false" />
				<param name="type" value="video/quicktime" />
				<param name="controller" value="true" />
				<embed src="' . $imagepath. '" width="352" height="304" autoplay="false" controller"true" type="video/quicktime"
				pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
				</object><a>';
			}
			elseif ($ext == ".mov") {
				echo '</a>
		 		<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="640" height="496" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
			 	<param name="src" value="' . $imagepath. '"/>
			 	<param name="autoplay" value="false" />
			 	<param name="type" value="video/quicktime" />
			 	<param name="controller" value="true" />
			 	<embed src="'  . $imagepath. '" width="640" height="496" autoplay="false" controller"true" type="video/quicktime"
			 	pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
				</object><a>';
		} else { 
			echo "<img src='".WEBPATH."/".ZENFOLDER."/i.php?a=".$album['folder']."&i=".$image['filename']."&s=".$imagesize."' alt=".$image['title']." title=".$image['title']." />";
		}
		echo "<p class='imgdesc'>".$image['desc']."</p></span>";
	}
?>	
		</div>
<?php
}


/**
 * Prints the path to the slideshow JS and CSS (printed because some values need to be changed dynamically).
 * CSS can be adjusted
 * To be used on slideshow.php
 *
 */
function printSlideShowCSS($size) {
$controlsimage = FULLWEBPATH . "/" . ZENFOLDER."/plugins/slideshow/controls.png";

?>
	<script src="<?php echo FULLWEBPATH . '/' . ZENFOLDER ?>/js/jquery.js" type="text/javascript"></script>
	<script src="<?php echo FULLWEBPATH . '/' . ZENFOLDER ?>/plugins/slideshow/jquery.cycle.all.pack.js" type="text/javascript"></script>
	<script type="text/javascript" src="<?php echo WEBPATH . "/" . ZENFOLDER; ?>/js/jquery.flashembed.pack.js"></script>
	<style type="text/css" media="screen">
		body {
		background-color: #151515;
		text-align: center;
	}
	
	h4 {
		margin-top: 30px;	
		font-weight: normal;
	}
	
	#slideshowpage {
		position: relative;
		padding-top: 0px;
		margin: 0 auto;
		width: 100%;
		text-align:center;
		border: 0px solid white;
		font-size: 0.8em;
	}

	#slideshow { 
		color: white;
		width: 100%;
	
		text-align: center;
	}
	
	#slideshow a img {
		border: 0;
	}
	
		
	#controls { 
		z-index: 1000; 
		position: relative; 
		top: 80px;
		display: none;   
		background-color: transparent; 
    border: 0px solid #ddd; 
    text-align: center;
    margin: 0 auto; 
    padding: 0; 
    width: 217px; 
    font-size: 0.8em;
	}
	
	#prev { 
		display: block;
		width: 46px;
		height: 41px;
		margin: 0px; 
		padding: 0;
		float: left;
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: 0 0;
	}
	
	#prev:hover { 
		background-image:url(<?php echo$controlsimage; ?>);
		background-position: 0 -43px;
	}
	#prev:active { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: 0 -86px;
	}
	
	#stop { 
		display: block;
		width: 41px;
		height: 41px;
		margin: 0px; 
		padding: 0;
		float: left;
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -46px 0px;
	}
	
	#stop:hover { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -46px -43px;
	}
	
	#stop:active { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -46px -86px;
	}
	
	#pause { 
		display: block;
		width: 39px;
		height: 41px;
		margin: 0px; 
		padding: 0;
		float: left;
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -85px 0px;
	}
	
	#pause:hover { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -85px -43px;
	}
	
	#pause:active { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -85px -86px;
	}
	
	#play { 
		display: block;
		width: 41px;
		height: 41px;
		margin: 0px; 
		padding: 0;
		float: left;
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -126px 0px;
	}
	
	#play:hover { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -126px -43px;
	}
	
	#play:active { 
		background-image:url(<?php $controlsimage; ?>);
		background-position: -126px -86px;
	}
	
	
	#next { 
		display: block;
		width: 50px;
		height: 41px;
		margin: 0px; 
		padding: 0;
		float: left;
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -167px 0px;
	}
	
	#next:hover { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -167px -43px;
	}
	
	#next:active { 
		background-image:url(<?php echo $controlsimage; ?>);
		background-position: -167px -86px;
	}
	
	#slides {
		width: <?php echo $_GET['size']; ?>px;
	}

	.slideimage {
		width: 100%;
		text-align: center;
		margin: 0 auto;
	}

	</style>
<?php
}

?>