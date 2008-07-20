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
 * 
 * NOTE: Slideshow 1.0.3 adds experimental progressive preloading provided by Don Peterson for the jQuery mode that does not work 100% correctly yet. 
 * 
 * @author Malte Müller (acrylian), Stephen Billard (sbillard), Don Peterson (dpeterson)
 * @version 1.0.3
 * @package plugins 
 */

/* 7/13/08dp (Don Peterson)  dongayle@centurytel.net
This is the original slideshow.php in core/plugins, revised to handle use of
the jquery cycle addslide option to allow the use of progressive image loading.
This IS NOT A FINISHED PRODUCT, just a prelim prototype, which happens (maybe by luck) to 
seem to work correctly. Where I have made changes, I preface them with
the date and my initials. Note: this apparently is an older version of the plugin,
and I found the jquery cycle plugin was also older, and needed updating to include
the new addslide option.  As more of a disclaimer, I haven't tested, nor have inclination to
test any adverse affects these hacks might have on the flash-related components of this code.
Sorry :) .
Oh, and, per malsups advice on cycle page, some of the fx can be kind of funky when 
utilizing the addslide option, so I suppose experimentation is the best option there. 
*/

$plugin_description = gettext("Adds a theme function to call a slideshow either based on jQuery (default) or Flash using Flowplayer if installed. Additionally the files <em>slideshow.php</em>, <em>slideshow.css</em> and <em>slideshow-controls.png</em> need to be present in the theme folder.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard), Don Peterson";
$plugin_version = '1.0.3';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---slideshow.php.html";
$option_interface = new slideshowOptions();

/* added by dp 7/13/08
Lots of the photos in the galleries I have been pursuaded by relatives to create,
have ampersands and apostrophes in their names (I try to tell my relatives NOT TO USE THEM, and
I'm too busy to 'cleanse' and rename them, so - it's a lost cause).
Something (either the php or cycle plugin), gags on them, causing the <img> attributes to
get messed up, resulting in "image not found", or the like - somehow, somewhere, ?something?
is breaking up the original <img src="Don's & John's big adventure.jpg"> into something along the
lines of <img Don="", John="", src="big", blablabla="jpg"> .  Got me on that one- I suspect cycle
is the culprit?  This fixPixPath seems to make things all better. 
*/
function fixPixPath($s) {
	return str_replace("&","%26",str_replace("'","%27",$s));
}

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
										'desc' => gettext("Check if you want to show the image's description below the slideshow <em>[jQuery mode option]</em>.")),
									gettext('flow player width') => array('key' => 'slideshow_flow_player_width', 'type' => 0,
										'desc' => gettext("Width of the Flowplayer display for the slideshow <em>(Flash mode)</em>.")),
									gettext('flow player height') => array('key' => 'slideshow_flow_player_height', 'type' => 0,
										'desc' => gettext("Height of the Flowplayer display for the slideshow <em>(Flash mode)</em>."))
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
 * NOTE: slideshow 1.0.3 adds experimental progressive preloading that does not work 100% correctly yet.
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
	$albumq = query_single_row("SELECT title, folder FROM ". prefix('albums') ." WHERE id = ".$albumid);
	if(!checkAlbumPassword($albumq['folder'], $hint)) {
		echo gettext("This album is password protected!"); exit;
	}		
	$gallery = new Gallery();
	$album = new Album($gallery, $albumq['folder']);
	$dynamic = $album->isDynamic();
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

		// ***************************************************************************
		// ***************************************************************************
		// dp 7/13/08
		// Expose some of the interesting data created via php to javascript, so it can be had by the cycle plugin.
		// Basically, plug 3 hardwired arrays into the page: 1 for the image paths, 1 for the titles, and 1 for descriptions
		// If I knew more javascript (or had more time) i suppose 1 multi-dimensional array would be more elegant.
		// Also as an aside, i wonder if there is any benefit to writing this data into the <head> section rather than the body,
		// since if it were in <head> then maybe the "document.ready" capabilities of jquery might eliminate some other 'quirks'
		// that seem to show up somewhat randomly depending on the slideshow image set (like controls not immediately appearing).
		//  Use of chr(13) just for readability when checking results during prototyping.
		
		// making ThisGallery a global - no sense (i dont think) in calling $album->getTitle() multiple times, since I assume
		// only 1 gallery is being shown at one time.
		
              var ThisGallery = '<?php echo $album->getTitle(); ?>';
              var ImageList = new Array();
              var TitleList = new Array();
              var DescList = new Array();
<?php 

			// a bit of a 'gotcha' here: the caller may have passed a starting slide # other than 1,
			// so this array  must be built in the relative order that would be seen by the viewer,
			// >>> AND SO <<< the cycle parameter for startingSlide: php echo $imagenumber should not be present
			// in this version (see below where cycle() is initialized! 
			// But this is where my php skill level breaks down, being a mostly non-php, (and mostly non-javascript) coder:
			// falling back to old C-style for() looping, hope it doesn't bite me later!
						
			for ($cntr = 0, $idx = $imagenumber; $cntr < $numberofimages; $cntr++, $idx++) {
				if ($dynamic) {
					$filename = $images[$idx]['filename'];
					$image = new Image(new Album($gallery, $images[$idx]['folder']), $filename);
				} else {
					$filename = $images[$idx];
					$image = new Image($album, $filename);
				}
				$img = WEBPATH . '/' . ZENFOLDER . '/i.php?a=' . $image->album->name . '&i=' . fixPixPath($filename) . '&s=' . $imagesize;
				echo 'ImageList[' . $cntr . '] = "' . $img . '";'. chr(13);
				echo 'TitleList[' . $cntr . '] = "' . $image->getTitle() . '";'. chr(13);
				// I'm replicating this test from the main php loop below, since it seems excessive to declare
				// and populate an array that may not get used.
				if(getOption("slideshow_showdesc")) {
					echo 'DescList[' . $cntr . '] = "' . $image->getDesc() . '";'. chr(13);
				}
				if ($idx == $numberofimages - 1) { $idx = -1; }
			}
			echo chr(13);
	
?>		
			
			// the following is adapted (most comments left intact) from malsup demo 2 of the addslide option.
			
            // set totalSlideCount var; 
            // we'll be adding slides to the slideshow 
			
			// dp 4/13/08 need to give javascript the starting image # passed in, so it can show the (X/X) count correctly
			var countOffset = <?php echo $imagenumber; ?>
			
            var totalSlideCount = <?php echo $numberofimages; ?>; 
            var currentslide = 2;		// dp. be sure to have at least 2 'normal' slides set up for the callback to work correctly.
										// In this case, the '2 slides' will be the slides = $imagenumber & $imagenumber+1 (but this
										// callback function doesnt 'know' it :) .
            function onBefore(curr, next, opts) { 
            	
                // on the first pass, addSlide is undefined (plugin hasn't yet created the fn); 
                // when we're finshed adding slides we'll null it out again 
                if (!opts.addSlide) 
                    return; 
        
                // on Before arguments: 
                //  curr == DOM element for the slide that is currently being displayed 
                //  next == DOM element for the slide that is about to be displayed 
                //  opts == slideshow options 
                     
                var currentImageNum = currentslide;
                currentslide++;
                if (currentImageNum == totalSlideCount) { 
                    // final slide in our slide slideshow is about to be displayed 
                    // so there are no more to fetch 
                    opts.addSlide = null; 
                	return; 
            	} 
    
                // add our next slide 
				// braindrain: display slide sequence # (x/x) in correct sequence (not the same as array's index).
				var relativeSlot = (currentslide + countOffset) % totalSlideCount;
				if (relativeSlot == 0) {relativeSlot = totalSlideCount;}
                var htmlblock = "<span class='slideimage'><h4><strong>" + ThisGallery + ":</strong> ";
//				htmlblock += TitleList[currentImageNum]  + " (" + currentslide + "/" + totalSlideCount + ")</h4>";
				htmlblock += TitleList[currentImageNum]  + " (" + relativeSlot + "/" + totalSlideCount + ")</h4>";
                htmlblock += "<img src='" + ImageList[currentImageNum] + "'/>";
                htmlblock += "<p class='imgdesc'>" + DescList[currentImageNum] + "</p></span>";        
                opts.addSlide(htmlblock); 
    	
    	}; 
		// remove the option for startingSlide:  (also removed delay: not sure why it was needed)	
		// AND... IE and cycle don't play well regarding text if the FX = fade, so I'm adding the 
		// cleartype: option since cycle is now dynamically outputting the header and title.
		// See the cycle page: http://malsup.com/jquery/cycle/cleartype.html?v3.
		$('#slides').cycle({
					fx:     '<?php echo getOption("slideshow_effect"); ?>',
					speed:   <?php echo getOption("slideshow_speed"); ?>,
					timeout: <?php echo getOption("slideshow_timeout"); ?>,
					next:   '#next',
					prev:   '#prev',
        			cleartype: 1,
					before: onBefore 
			});
	
		// ***************************************************************************
		// ***************************************************************************
		// END OF dp 7/13/08
						
			$('#pause').click(function() { $('#slides').cycle('pause'); return false; });
			$('#play').click(function() { $('#slides').cycle('resume'); return false; });
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
<div id="slides" class="pics">

<?php
		// 7/13/08dp  note Here I am doing the same klunky replacement of foreach(), and also limit the number of 'normal' slides to 2, and letting
		// the cycle callback add the remainder  (I left $count alone, since I'm too lazy and tired to see where else it's used other than the image count display.
		// 1.2 the slides
		
		for ($cntr = 0, $idx = $imagenumber; $cntr < 2; $cntr++, $idx++) {
// 7/16/08dp moved to below. $filename = fixPixPath($images[$idx]); 		// again, minor mystery why & and ' are thowing <img> attributes off (haywire)? 
		
			$count++;
			if($count > $numberofimages){
				$count = 1;
			}
// 7/16/08dp Glad I noticed! I forgot to add this test: otherwise get counter like (17 of 16)!
			if ($idx >= $numberofimages) { $idx = 0; }
			if ($dynamic) {
				$folder = $images[$idx]['folder'];
				$dalbum = new Album($gallery, $folder);
				$filename = $images[$idx]['filename'];
				$image = new Image($dalbum, $filename);
				$imagepath = FULLWEBPATH.getAlbumFolder('').$folder."/".$filename;
			} else { 
				$folder = $album->name;
				$filename = fixPixPath($images[$idx]); 		// again, minor mystery why & and ' are thowing <img> attributes off (haywire)? 
				//$filename = $animage;
				$image = new Image($album, $filename);
				$imagepath = FULLWEBPATH.getAlbumFolder('').$folder."/".$filename;
			}
			$ext = strtolower(strrchr($filename, "."));
// 7/16/08dp  Note that $count CANNOT be used as an index into the count of the series any longer, since
// the only 2 slides being drawn (by virtue of the forced "$cntr < 2" in the for() loop, may not actually be the 1st and 2nd slides in the album-
// the caller may have started the show at a specific image order location e.g. the 5th image in the album.
// If $count is used, it will be the wrong sequence number potentially.
		//	echo "<span class='slideimage'><h4><strong>".$album->getTitle().":</strong> ".$image->getTitle()." (".$count."/".$numberofimages.")</h4>";
			echo "<span class='slideimage'><h4><strong>".$album->getTitle().":</strong> ".$image->getTitle()." (". ($idx + 1) ."/".$numberofimages.")</h4>";
		
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
			echo "<img src='".WEBPATH."/".ZENFOLDER."/i.php?a=".$folder."&i=".$filename."&s=".$imagesize."' alt='".$image->getTitle()."' title='".$image->getTitle()."' />\n";
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
	foreach($images as $animage) {
			if ($dynamic) {
				$folder = $animage['folder'];
				$filename = $animage['filename'];
				$image = new Image($dalbum, $filename);
				$imagepath = FULLWEBPATH.getAlbumFolder('').$salbum->name."/".$filename;
			} else {
				$folder = $album->name;
				$filename = $animage;
				$image = new Image($album, $filename);
				$imagepath = FULLWEBPATH.getAlbumFolder('').$folder."/".$filename;
			}
		$count++;
		$ext = strtolower(strrchr($filename, "."));
		if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
			$duration = ""; 
		} else {
			$duration = " duration: ".getOption("slideshow_speed")/10;
		}
		echo "{ url: '".FULLWEBPATH.getAlbumFolder('').$folder."/".$filename."', ".$duration." }\n";
		if($count < $numberofimages) { echo ","; }
	}
?>     
     ],
      showPlayListButtons: true, 
      showStopButton: true, 
      controlBarBackgroundColor: 0,
     	showPlayListButtons: true,
     	controlsOverVideo: 'ease',
     	controlBarBackgroundColor: '<?php echo getOption('flow_player_controlbarbackgroundcolor'); ?>',
      controlsAreaBorderColor: '<?php echo getOption('flow_player_controlsareabordercolor'); ?>'
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