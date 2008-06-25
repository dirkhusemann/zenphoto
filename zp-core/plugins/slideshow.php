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
 * folder. If you are creating a custom theme, copy these files form the "default" theme of the Zenphoto 
 * distribution.
 */

$plugin_description = gettext("Adds a theme function to call a slideshow either based on jQuery (default) or Flash using Flowplayer if installed. Additionally the files <em>slideshow.php</em>, <em>slideshow.css</em> and <em>slideshow-controls.png</em> need to be present in the theme folder.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard( sbillard)";
$plugin_version = '1.0.2.6';
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
		setOptionDefault('slideshow_flow_player_width', '640');
		setOptionDefault('slideshow_flow_player_height', '480');
	}
		
	
	function getOptionsSupported() {
		return array(	gettext('Size') => array('key' => 'slideshow_size', 'type' => 0, 
										'desc' => gettext("Size of the images in the slideshow. <em>[jQuery mode option]</em><br />If empty the theme options <em>image size</em> is used.")),
									gettext('Mode') => array('key' => 'slideshow_mode', 'type' => 2, 
										'desc' => gettext("<em>jQuery</em> for JS ajax slideshow, <em>flash</em> for flash based slideshow (requires Flowplayer.)")),
									gettext('Effect') => array('key' => 'slideshow_effect', 'type' => 2, 
										'desc' => gettext("The cycle slide effect to be used. <em>[jQuery mode option]</em>")),
									gettext('Speed') => array('key' => 'slideshow_speed', 'type' => 0,
										'desc' => gettext("Speed of the transition in milliseconds.")),
									gettext('Timeout') => array('key' => 'slideshow_timeout', 'type' => 0,
										'desc' => gettext("Milliseconds between slide transitions (0 to disable auto advance.) <em>[jQuery mode option]</em>")),
									gettext('Description') => array('key' => 'slideshow_showdesc', 'type' => 1,
										'desc' => gettext("Check if you want to show the image's description below the slideshow.")),
									gettext('flow player width') => array('key' => 'slideshow_flow_player_width', 'type' => 0,
										'desc' => gettext("Width of the Flowplayer display for thee slideshow <em>(Flash mode)</em>.")),
									gettext('flow player height') => array('key' => 'slideshow_flow_player_height', 'type' => 0,
										'desc' => gettext("Height of the Flowplayer display for thee slideshow <em>(Flash mode)</em>."))
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
 * Prints a link to call the slideshow (not shown if there are no images in the album)
 * To be used on album.php and image.php
 * A CSS id names 'slideshowlink' is attached to the link so it can be directly styled.
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
	$slideshowlink = rewrite_path($_zp_current_album->getFolder()."/page/slideshow","index.php?p=slideshow&album=".$_zp_current_album->getFolder());
if($numberofimages != 0) {
?>	
	<form name="slideshow" method="post" action="<?php echo htmlspecialchars($slideshowlink); ?>">
		<input type="hidden" name="pagenr" value="<?php echo $pagenr;?>" />
		<input type="hidden" name="albumid" value="<?php echo getAlbumID();?>" />
		<input type="hidden" name="numberofimages" value="<?php echo $numberofimages;?>" />
		<input type="hidden" name="imagenumber" value="<?php echo $imagenumber;?>" />
		<input type="hidden" name="imagefile" value="<?php echo $imagefile;?>" />
		<a id="slideshowlink" href="javascript:document.slideshow.submit()"><?php echo $linktext; ?></a>
	</form>
<?php }
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
 * @param bool $heading set to true (default) to emit the slideshow breadcrumbs in flash mode
 */
function printSlideShow($heading = true) {
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
	$albumq = query_single_row("SELECT title, folder FROM ". prefix('albums') ." WHERE `show` = 1 AND id = ".$albumid);
	if(!checkAlbumPassword($albumq['folder'], $hint)) {
		echo gettext("This album is password protected!"); exit;
	}		
	$gallery = new Gallery();
	$album = new Album($gallery, $albumq['folder']);
	$images = $album->getImages(0);
	
	// return path to get back to the page we called the slideshow from
	if (empty($_POST['imagenumber'])) {
		$returnpath = rewrite_path('/'.$album->name.'/page/'.$_POST['pagenr'],'/index.php?album='.$album->name.'&page='.$_POST['pagenr']);
	} else {
		$returnpath = rewrite_path('/'.$album->name.'/'.$_POST['imagefile'].getOption('mod_rewrite_image_suffix'),'/index.php?album='.$album->name.'&image='.$_POST['imagefile']);
	}
	// slideshow display section
	switch($option) {
		case "jQuery":
?>
<script type="text/javascript">
		$(function() {
			$('#pause').click(function() { $('#slides').cycle('pause'); return false; });
			$('#play').click(function() { $('#slides').cycle('resume'); return false; });
							
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
				<span><a href="#" id="prev" title="Previous"></a></span>
				<a href="<?php echo $returnpath; ?>" id="stop" title="Stop and return to album or image page"></a>
  			<a href="#" id="pause" title="Pause (to stop the slideshow without returning)"></a>
				<a href="#" id="play" title="Play"></a>
				<a href="#" id="next" title="Next"></a>
			</div>
		</div>
<div id="slides" class="pics"><?php
		// 1.2 the slides
		foreach($images as $filename) {
			$count++;
			if($count > $numberofimages){
				$count = 1;
			}
			$image = new Image($album, $filename);
			$imagepath = FULLWEBPATH.getAlbumFolder('').$album->name."/".$filename;
			$ext = strtolower(strrchr($filename, "."));
			echo "<span class='slideimage'><h4><strong>".$album->getTitle().":</strong> ".$image->getTitle()." (".$count."/".$numberofimages.")</h4>";
			if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
				//Player Embed...
				if (is_null($_zp_flash_player)) {
					echo "<img src='" . WEBPATH . '/' . ZENFOLDER . "'/images/err-noflashplayer.gif' alt='No flash player installed.' />";
				} else {
					$_zp_flash_player->playerConfig($imagepath,$image->getTitle(),$count);
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
			echo "<img src='".WEBPATH."/".ZENFOLDER."/i.php?a=".$album->name."&i=".$filename."&s=".$imagesize."' alt='".$image->getTitle()."' title='".$image->getTitle()."' />";
		}
		if(getOption("slideshow_showdesc")) { echo "<p class='imgdesc'>".$image->getDesc()."</p>"; }
		echo "</span>";
	}

	break;

case "flash":
	if ($heading) {
		echo "<span class='slideimage'><h4><strong>".$album->name."</strong> (".$numberofimages." images) | <a style='color: white' href='".$returnpath."' title='".gettext("back")."'>".gettext("back")."</a></h4>";
	}
	echo "<span id='slideshow'></span>";
	?>	
<script type="text/javascript">
$("#slideshow").flashembed({
      src:'<?php echo FULLWEBPATH . '/' . ZENFOLDER; ?>/plugins/flowplayer/FlowPlayerLight.swf',
      width:<?php echo getOption("slideshow_flow_player_width"); ?>, 
      height:<?php echo getOption("slideshow_flow_player_height"); ?>
    },
    {config: {  
      autoPlay: true,
      useNativeFullScreen: true,
      playList: [
<?php
	$count = 0;
	foreach($images as $filename) {
		$count++;
		$ext = strtolower(strrchr($filename, "."));
		if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
			$duration = ""; 
		} else {
			$duration = " duration: ".getOption("slideshow_speed")/10;
		}
		echo "{ url: '".FULLWEBPATH.getAlbumFolder('').$album->name."/".$filename."', ".$duration." }\n";
		if($count < $numberofimages) { echo ","; }
	}
?>     
     ],
      showPlayListButtons: true, 
      showStopButton: true, 
      controlBarBackgroundColor: 0,
     	showPlayListButtons: true,
    }} 
  );
</script>	
	
	
<?php
	echo "</span>";
		echo "<p>Click on <img style='position: relative; top: 4px; border: 1px solid gray' src='".WEBPATH . "/" . ZENFOLDER."/plugins/slideshow/flowplayerfullsizeicon.png' /> on the right in the player control bar to view full size</p>";
	
	break;
}
?>
</div>
</div>
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