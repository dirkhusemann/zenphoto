<?php
/**
 * slideshow -- Supports showing slideshows of images in an album.
 * 
 * 	Plugin Option 'slideshow_size' -- Size of the images
 *	Plugin Option 'slideshow_mode' -- The player to be used
 *	Plugin Option 'slideshow_effect' -- The cycle effect
 *	Plugin Option 'slideshow_speed' -- How fast it runs
 *	Plugin Option 'slideshow_timeout' -- Transition time
 *	Plugin Option 'slideshow_showdesc' -- Allows the show to display image descriptons
 * 
 * The theme files 'slideshow.php', 'slideshow.css', and 'slideshow-controls.png' must reside in the theme
 * folder of the Gallery theme. (Slideshows do not take on 'album themes'). If you are creating a custom
 * theme, copy these files form the "default" theme of the Zenphoto distribution.
 */

$plugin_description = gettext("Adds a theme function to call a slideshow either based on jQuery (default) or Flash using Flowplayer if installed. Additionally the files <em>slideshow.php</em>, <em>slideshow.css</em> and <em>slideshow-controls.png</em> need to be present in the theme folder. Copy them from one of the distributed themes.");
$plugin_author = "Malte Müller (acrylian) ".gettext("and").' Stephen Billard( sbillard)';
$plugin_version = '1.0.2';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---slideshow.php.html";
$option_interface = new slideshowOptions();


/**
 * Plugin option handling class
 *
 */
class slideshowOptions {

	function slideshowOptions() {
	 	setOptionDefault('slideshow_size', '595');
		setOptionDefault('slideshow_mode', 'jQuery');
		setOptionDefault('slideshow_effect', 'fade');
		setOptionDefault('slideshow_speed', '500');
		setOptionDefault('slideshow_timeout', '3000');
		setOptionDefault('slideshow_showdesc', '');
		// incase the flowplayer has not been enabled!!!
		setOptionDefault('flow_player_width', '320');
		setOptionDefault('flow_player_height', '240');
	}
		
	
	function getOptionsSupported() {
		return array(	gettext('Size') => array('key' => 'slideshow_size', 'type' => 0, 
										'desc' => gettext("Size of the images in the slideshow. If empty the normal image size set in the theme options is used (on jQuery mode)")),
									gettext('Mode') => array('key' => 'slideshow_mode', 'type' => 2, 
										'desc' => gettext("'jQuery' (default) for JS ajax slideshow, 'flash' for flash based slideshow (Flow player needs to be installed.).")),
									gettext('Effect') => array('key' => 'slideshow_effect', 'type' => 2, 
										'desc' => gettext("The Cycle slide effect to be used (only jQuery mode).")),
									gettext('Speed') => array('key' => 'slideshow_speed', 'type' => 0,
										'desc' => gettext("Speed of the transition in milliseconds")),
									gettext('Timeout') => array('key' => 'slideshow_timeout', 'type' => 0,
										'desc' => gettext("Milliseconds between slide transitions (0 to disable auto advance) (only jQuery mode).")),
									gettext('Description') => array('key' => 'slideshow_showdesc', 'type' => 1,
										'desc' => gettext("If you want to show the image's description below the image"))
		);
	}

	function handleOption($option, $currentValue) {
		if ($option=='slideshow_mode') {
			$modes = array("jQuery", "flash");
			echo "<select size='1' name='".$option."'>";
			generateListFromArray(array($currentValue), $modes);
			echo "</select>";
		}
		if ($option=='slideshow_effect') {
			$effects = array("fade", "shuffle", "zoom", "slideX", "slideY","scrollUp","scrollDown","scrollLeft","scrollRight");
			echo "<select size='1' name='".$option."'>";
			generateListFromArray(array($currentValue), $effects);
			echo "</select>";
		}
	}

}


/**
 * Prints a link to call the slideshow
 * To be used on album.php and image.php
 *
 * @param string $linktext Text for the link
 */
function printSlideShowLink($linktext='') {
 	global $_zp_current_image,$_zp_current_album;
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
	$slideshowlink = getCustomPageURL('slideshow');
?>	
	<form name="slideshow" method="post" action="<?php echo htmlspecialchars($slideshowlink); ?>">
		<input type="hidden" name="pagenr" value="<?php echo $pagenr;?>" />
		<input type="hidden" name="albumid" value="<?php echo getAlbumID();?>" />
		<input type="hidden" name="numberofimages" value="<?php echo $numberofimages;?>" />
		<input type="hidden" name="imagenumber" value="<?php echo $imagenumber;?>" />
		<input type="hidden" name="imagefile" value="<?php echo $imagefile;?>" />
		<a href="javascript:document.slideshow.submit()"><?php echo $linktext; ?></a>
	</form>
<?php
}


/**
 * Prints the slideshow using the jQuery plugin Cycle (http://http://www.malsup.com/jquery/cycle/)
 * or Flash based using Flowplayer http://flowplayer.org if installed
 * If called from image.php it starts with that image, called from album.php it starts with the first image (jQuery only)
 * To be used on slideshow.php only and called from album.php or image.php. 
 * Image size is taken from the calling link or if not specified there the sized image size from the options
 * In jQuery mode the slideshow has to be stopped to view a movie. 
 *  
 * NOTE: Since all images of an album are generated/listed in total, it can happen that some images are skipped until all are generated.
 * And of course on slower connections this could take some time if you have many images.
 *
  */
function printSlideShow() {
	global $_zp_flash_player;
	if(empty($_POST['imagenumber'])) {
		$imagenumber = 0; 
		$count = 0;
	} else {
		$imagenumber = ($_POST['imagenumber']-1); // slideshows starts with 0, but zp with 1.
		$count = $_POST['imagenumber'];
	}
	$numberofimages = sanitize_numeric($_POST['numberofimages']);
	$albumid = sanitize_numeric($_POST['albumid']);
	if(getOption("slideshow_size")) {
		$imagesize = getOption("slideshow_size");
	} else {
		$imagesize = getOption("image_size");
	}
	$option = getOption("slideshow_mode");
	// jQuery Cycle slideshow config
	// get slideshow data
	$album = query_single_row("SELECT title, folder FROM ". prefix('albums') ." WHERE `show` = 1 AND id = ".$albumid);
	if(!checkAlbumPassword($album['folder'], $hint)) {
		echo gettext("This album is password protected!"); exit;
	}		
	$images = query_full_array("SELECT title, filename, `desc` FROM ". prefix('images') ." WHERE `show` = 1 AND albumid = ".$albumid." ORDER BY sort_order");
	
	// return path to get back to the page we called the slideshow from
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
	// slideshow display section
	switch($option) {
		case "jQuery":
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
					fx:     '<?php echo getOption("slideshow_effect"); ?>',
					speed:   <?php echo getOption("slideshow_speed"); ?>,
					timeout: <?php echo getOption("slideshow_timeout"); ?>,
					next:   '#next',
					prev:   '#prev',
					delay: 2000,
					startingSlide: <?php echo $imagenumber; ?>
			});
		});
	</script>
<div id="slideshow" align="center">
		<div id="controls">
			<div>
				<span><a href="#" id="prev"></a></span>
				<a href="<?php echo $returnpath; ?>" id="stop"></a>
  			<a href="#" id="pause"></a>
				<a href="#" id="play"></a>
				<a href="#" id="next"></a>
			</div>
		</div>
<div id="slides" class="pics"><?php
		// 1.2 the slides
		foreach($images as $image) {
			$count++;
			if($count > $numberofimages){
				$count = 1;
			}
			$imagepath = FULLWEBPATH."/albums/".$album['folder']."/".$image['filename'];
			$ext = strtolower(strrchr($image['filename'], "."));
			echo "<span class='slideimage'><h4><strong>".$album['title'].":</strong> ".$image['title']." (".$count."/".$numberofimages.")</h4>";
			if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
				//Player Embed...
				if (is_null($_zp_flash_player)) {
					echo "<img src='" . WEBPATH . '/' . ZENFOLDER . "'/images/err-noflashplayer.gif' alt='No flash player installed.' />";
				} else {
					 $_zp_flash_player->playerConfig($imagepath,$image['title']);
				}
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
		if(getOption("slideshow_desc")) { echo "<p class='imgdesc'>".$image['desc']."</p>"; }
		echo "</span>";
	}
	break;

case "flash":
	echo "<span class='slideimage'><h4><strong>".$album['title']."</strong> (".$numberofimages." images) | <a style='color: white' href='".$returnpath."'>back</a></h4>";
	echo "<span id='slideshow'></span>";
	?>	
<script type="text/javascript">
$("#slideshow").flashembed({
      src:'<?php echo FULLWEBPATH . '/' . ZENFOLDER; ?>/plugins/flowplayer/FlowPlayerLight.swf',
      width:<? echo getOption("flow_player_width"); ?>, 
      height:<? echo getOption("flow_player_height"); ?>
    },
    {config: {  
      autoPlay: true,
      playList: [
<?php
	foreach($images as $image) {
		$count++;
		$ext = strtolower(strrchr($image['filename'], "."));
		if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
			$duration = ""; 
		} else {
			$duration = " duration: ".getOption("slideshow_speed")/10;
		}
		echo "{ url: '".FULLWEBPATH."/albums/".$album['folder']."/".$image['filename']."', ".$duration." }\n";
		if($count < $numberofimages) { echo ","; }
	}
?>     
     ],
      howPlayListButtons: true, 
      showStopButton: true, 
      controlBarBackgroundColor: 0,
     	showPlayListButtons: true
    }} 
  );
</script>	
	
	
<?php
	echo "</span>";
	break;
}
?></div>
<?php
}


/**
 * Prints the path to the slideshow JS and CSS (printed because some values need to be changed dynamically).
 * CSS can be adjusted
 * To be used on slideshow.php
 *
 */
function printSlideShowJS() {
?>
	<script src="<?php echo FULLWEBPATH . '/' . ZENFOLDER ?>/js/jquery.js" type="text/javascript"></script>
	<script src="<?php echo FULLWEBPATH . '/' . ZENFOLDER ?>/plugins/slideshow/jquery.cycle.all.pack.js" type="text/javascript"></script>
	<script type="text/javascript" src="<?php echo WEBPATH . "/" . ZENFOLDER; ?>/plugins/flowplayer/jquery.flashembed.pack.js"></script>

<?php
}

?>