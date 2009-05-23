<?php
/** 
 * flowplayer -- plugin support for the flowplayer 3.x.x flash video player.
 * NOTE: Flash players do not support external albums!
 * 
 * Note on splash images: Flowplayer will try to use the first frame of a movie as a splash image or a videothumb if existing.
 * 
 * @author Malte Müller (acrylian)
 * @version 1.0.3
 * @package plugins 
 */


$plugin_description = ($external = (getOption('album_folder_class') === 'external'))? gettext('<strong>Flash players do not support <em>External Albums</em>!</strong>'): gettext("Enable <strong>flowplayer 3</strong> to handle multimedia files. IMPORTANT: Only one multimedia player plugin can be enabled at the time. <br> Please see <a href='http://flowplayer.org'>flowplayer.org</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.3';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---flowplayer3.php.html";
$plugin_disable = $external;
$option_interface = new flowplayer3();
$_zp_flash_player = $option_interface; // claim to be the flash player.

if ($external) return; // can't process external album images

// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER .'flowplayer3/flowplayer.js"></script>
<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER .'flowplayer3/flowplayer.playlist.pack.js"></script>');

if (!defined('FLOW_PLAYER_MP3_HEIGHT')) define ('FLOW_PLAYER_MP3_HEIGHT', 24);
/**
 * Plugin option handling class
 *
 */
class flowplayer3 {

	function flowplayer3() {
		setOptionDefault('flow_player3_width', '320');
		setOptionDefault('flow_player3_height', '240');
		setOptionDefault('flow_player3_controlsbackgroundcolor', '#110e0e');
		setOptionDefault('flow_player3_controlsbackgroundcolorgradient', 'none');
		setOptionDefault('flow_player3_controlsbordercolor', '#000000');
		setOptionDefault('flow_player3_autoplay', '');
		setOptionDefault('flow_player3_backgroundcolor', '#000000');
		setOptionDefault('flow_player3_backgroundcolorgradient', 'none');
		setOptionDefault('flow_player3_controlsautohide', 'never');
		setOptionDefault('flow_player3_controlstimecolor', '#fcfcfc');
		setOptionDefault('flow_player3_controlsdurationcolor', '#ffffff');
		setOptionDefault('flow_player3_controlsprogresscolor', '#ffffff');
		setOptionDefault('flow_player3_controlsprogressgradient', 'low');
		setOptionDefault('flow_player3_controlsbuffercolor', '#275577');	
		setOptionDefault('flow_player3_controlsbuffergradient', 'low');	
		setOptionDefault('flow_player3_controlsslidercolor', '#ffffff');	
		setOptionDefault('flow_player3_controlsslidergradient', 'low');	
		setOptionDefault('flow_player3_controlsbuttoncolor', '#567890');
		setOptionDefault('flow_player3_controlsbuttonovercolor', '#999999');
		setOptionDefault('flow_player3_splashimagescale', 'fit');
	}


	function getOptionsSupported() {
		return array(	gettext('flow player width') => array('key' => 'flow_player3_width', 'type' => 0,
										'desc' => gettext("Player width (ignored for <em>mp3</em> files.)")),
		gettext('flow player height') => array('key' => 'flow_player3_height', 'type' => 0,
										'desc' => gettext("Player height (ignored for <em>mp3</em> files.)")),
		gettext('Player background color') => array('key' => 'flow_player3_backgroundcolor', 'type' => 0,
										'desc' => gettext("The color of the player background.")),
		gettext('Player background color gradient') => array('key' => 'flow_player3_backgroundcolorgradient', 'type' => 5,
										'selections' => array(gettext('none')=>"none",gettext('low')=>"low", gettext('medium')=>"medium", gettext('high')=>"high"),
										'desc' => gettext("Gradient setting for playler background color.")),
		gettext('Controls background color') => array('key' => 'flow_player3_controlsbackgroundcolor', 'type' => 8,
										'desc' => gettext("Background color of the controls.")),
		gettext('Controls background color gradient') => array('key' => 'flow_player3_controlsbackgroundcolorgradient', 'type' => 5,
										'selections' => array(gettext('none')=>"none",gettext('low')=>"low", gettext('medium')=>"medium", gettext('high')=>"high"),
										'desc' => gettext("Gradient setting for background color of the controls.")),
		gettext('Controls border color') => array('key' => 'flow_player3_controlsbordercolor', 'type' => 8,
										'desc' => gettext("Color of the border of the player controls")),
		gettext('Autoplay') => array('key' => 'flow_player3_autoplay', 'type' => 1,
										'desc' => gettext("Should the video start automatically. Yes if selected. (NOTE: Probably because of a flowplayer bug mp3s are always autoplayed.)")),
		gettext('Background color') => array('key' => 'flow_player3_backgroundcolor', 'type' => 8,
										'desc' => gettext("Changes the color of the Flowplayer's background canvas. By default the canvas is all black. You can specify a value of -1 and the background will not be drawn (only the video will be visible).")),
		gettext('Controls autohide') => array('key' => 'flow_player3_controlsautohide', 'type' => 5,
										'selections' => array(gettext('never')=>"never", gettext('always')=>"always", gettext('fullscreen')=>"fullscreen"),
										'desc' => gettext("Specifies whether the controlbar should be hidden when the user is not actively using the player.")),
		gettext('Controls time color') => array('key' => 'flow_player3_controlstimecolor', 'type' => 8,
										'desc' => gettext("Value for the font color in the time field. This is the running time.")),
		gettext('Controls duration color') => array('key' => 'flow_player3_controlsdurationcolor', 'type' => 8,
										'desc' => gettext("Value for the font color in the time field that specifies the total duration of the clip or total time.")),
		gettext('Controls progressbar color') => array('key' => 'flow_player3_progresscolor', 'type' => 8,
										'desc' => gettext("Color of the progress bar. This is the bar in the timeline from zero time to the point where playback is at a given time.")),
		gettext('Controls progressbar gradient') => array('key' => 'flow_player3_progressgradient', 'type' => 5,
										'selections' => array(gettext('none')=>"none",gettext('low')=>"low", gettext('medium')=>"medium", gettext('high')=>"high"),
										'desc' => gettext("Gradient setting for the progress bar.")),
		gettext('Controls buffer color') => array('key' => 'flow_player3_controlsbuffercolor', 'type' => 8,
										'desc' => gettext("Color of the buffer. The buffer is the bar that indicates how much video data has been read into the player's memory.")),
		gettext('Controls buffer gradient') => array('key' => 'flow_player3_controlsbuffergradient', 'type' => 5,
										'selections' => array(gettext('none')=>"none",gettext('low')=>"low", gettext('medium')=>"medium", gettext('high')=>"high"),
										'desc' => gettext("Gradient setting for the buffer.")),
		gettext('Controls slider color') => array('key' => 'flow_player3_controlsslidercolor', 'type' => 8,
										'desc' => gettext("Background color for the timeline before the buffer bar fills it. The same background color is also used in the volume slider.")),		
		gettext('Controls slider gradient') => array('key' => 'flow_player3_controlsslidergradient', 'type' => 5,
										'selections' => array(gettext('none')=>"none",gettext('low')=>"low", gettext('medium')=>"medium", gettext('high')=>"high"),
										'desc' => gettext("Gradient setting for the sliders.")),
		gettext('Controls button color') => array('key' => 'flow_player3_controlsbuttoncolor', 'type' => 8,
										'desc' => gettext("Color of the player buttons: stop, play, pause and fullscreen.")),		
		gettext('Controls hover button color') => array('key' => 'flow_player3_controlsbuttonovercolor', 'type' => 8,
										'desc' => gettext("Button color when the mouse is positioned over them.")),
		gettext('Splash image scale') => array('key' => 'flow_player3_splashimagescale', 'type' => 5,
										'selections' => array(gettext('fit')=>"fit",gettext('half')=>"half", gettext('orig')=>"orig", gettext('scale')=>"scale"),
										'desc' => gettext("Setting which defines how video is scaled on the video screen. Available options are:: <br /><em>fit</em> : Fit to window by preserving the aspect ratio encoded in the file's metadata.<br /><em>half</em>: Half-size (preserves aspect ratio)<br /><em>orig</em>: Use the dimensions encoded in the file. If the video is too big for the available space, the video is scaled using the 'fit' option.<br /><em>scale</em>: Scale the video to fill all available space. This is the default setting."))
		);
	}

	/**
	 * Print the JS configuration of flowplayer
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums)
	 * the zenphoto function getUnprotectedImageURL() is used
	 * 
	 * @param string $imagetitle the title of the movie
	 * 	 */
	function getPlayerConfig($moviepath='', $imagetitle,$count='') {
		global $_zp_current_image;
		if(empty($moviepath)) {
			$moviepath = getUnprotectedImageURL();
			$ext = strtolower(strrchr(getUnprotectedImageURL(), "."));
		} else {
			$ext = strtolower(strrchr($moviepath, "."));
		}
		if(!empty($count)) {
			$count = "-".$count;
		}
		$imgextensions = array(".jpg",".jpeg",".gif",".png");
		if(is_null($_zp_current_image)) {
			$albumfolder = $moviepath;
			$filename = $imagetitle;
			$videoThumb = '';
		} else {
			$album = $_zp_current_image->getAlbum();
			$albumfolder = $album->name;
			$filename = $_zp_current_image->filename;
			$videoThumb = checkObjectsThumb(getAlbumFolder().$albumfolder, $filename);
			if (!empty($videoThumb)) {
				$videoThumb = getAlbumFolder(WEBPATH).pathurlencode($albumfolder.'/'.$videoThumb);
			}
		}
		if(getOption("flow_player3_autoplay") == 1) {
			$autoplay = "true";
		} else {
			$autoplay = "false";
		}
		if(getOption("flow_player3_autohide") == 1) {
			$autohide = "true";
		} else {
			$autohide = "false";
		}
		if($ext === ".mp3") {
			if(empty($videoThumb)) {
				$height = FLOW_PLAYER_MP3_HEIGHT;
			} else {
				$height = getOption('flow_player3_height');
			}
				// inline css is kind of ugly but since we need to style dynamically there is no other way
			$playerconfig = '
			<span id="player'.$count.'" class="flowplayer" style="display:block; width: '.getOption('flow_player3_width').'px; height: '.$height.'px;">
			</span>
			<script>
			flowplayer("player'.$count.'","'.WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . 'flowplayer3/flowplayer.swf", {
			plugins: { 
        controls: {
        	backgroundColor: "'.getOption('flow_player3_controlsbackgroundcolor').'",
        	backgroundGradient: "'.getOption('flow_player3_controlsbackgroundcolorgradient').'",
        	autoHide: '.$autohide.',
        	timeColor:"'.getOption('flow_player3_controlstimecolor').'",
        	durationColor: "'.getOption('flow_player3_controlsdurationcolor').'",
        	progressColor: "'.getOption('flow_player3_controlsprogresscolor').'",
        	progressGradient: "'.getOption('flow_player3_controlsprogressgradient').'",
        	bufferColor: "'.getOption('flow_player3_controlsbuffercolor').'",
        	bufferGradient:	 "'.getOption('flow_player3_controlsbuffergradient').'",
        	sliderColor: "'.getOption('flow_player3_controlsslidercolor').'",	
        	sliderGradient: "'.getOption('flow_player3_controlsslidergradient').'",
        	buttonColor: "'.getOption('flow_player3_controlsbuttoncolor').'",
        	buttonOverColor: "'.getOption('flow_player3_controlsbuttonovercolor').'"
        }
    	},
    	canvas: {
    		backgroundColor: "'.getOption('flow_player3_backgroundcolor').'",
    		backgroundGradient: "'.getOption('flow_player3_backgroundcolorgradient').'"
    	},
			playlist: [ 
				{
					url:"'.$videoThumb.'",
					scaling: "'.getOption('flow_player3_splashimagescale').'"
				},
				{
					url:"' . $moviepath . '",
					autoPlay: '.$autoplay.',
					autoBuffering: true
				}
			]
			}); 
			</script>';
			} else {
			$playerconfig = '
			<span id="player'.$count.'" class="flowplayer" style="display:block; width: '.getOption('flow_player3_width').'px; height: '.getOption('flow_player3_height').'px;">
			</span>
			<script>
			flowplayer("player'.$count.'","'.WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . 'flowplayer3/flowplayer.swf", {
			plugins: { 
        controls: {
        	backgroundColor: "'.getOption('flow_player3_controlsbackgroundcolor').'",
        	backgroundGradient: "'.getOption('flow_player3_controlsbackgroundcolorgradient').'",
        	autoHide: '.$autohide.',
        	timeColor:"'.getOption('flow_player3_controlstimecolor').'",
        	durationColor: "'.getOption('flow_player3_controlsdurationcolor').'",
        	progressColor: "'.getOption('flow_player3_controlsprogresscolor').'",
        	progressGradient: "'.getOption('flow_player3_controlsprogressgradient').'",
        	bufferColor: "'.getOption('flow_player3_controlsbuffercolor').'",
        	bufferGradient:	 "'.getOption('flow_player3_controlsbuffergradient').'",
        	sliderColor: "'.getOption('flow_player3_controlsslidercolor').'",	
        	sliderGradient: "'.getOption('flow_player3_controlsslidergradient').'",
        	buttonColor: "'.getOption('flow_player3_controlsbuttoncolor').'",
        	buttonOverColor: "'.getOption('flow_player3_controlsbuttonovercolor').'"
        }
    	},
    	canvas: {
    		backgroundColor: "'.getOption('flow_player3_backgroundcolor').'",
    		backgroundGradient: "'.getOption('flow_player3_backgroundcolorgradient').'"
    	},';
			if(empty($videoThumb)) { // use first frame as slash image
				$playerconfigadd = 'clip:  
				{
					url:"' . $moviepath . '",
					autoPlay: '.$autoplay.',
					autoBuffering: true
				}
			}); 
			</script>';
			} else { // use existing videothumb as splash image
				$playerconfigadd = 'playlist: [ 
				{
					url:"'.$videoThumb.'",
					scaling: "'.getOption('flow_player3_splashimagescale').'"
				},
				{
					url:"' . $moviepath . '",
					autoPlay: '.$autoplay.',
					autoBuffering: true
				}
			]
			}); 
			</script>';
			}
			$playerconfig = $playerconfig.$playerconfigadd;
			}
			
			return $playerconfig;
		}
	
	
	/**
	 * outputs the player configuration HTML
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
	 * @param string $imagetitle the title of the movie to be passed to the player for display (within slideshow), if empty (within albums) the function getImageTitle() is used
	 * @param string $count unique text for when there are multiple player items on a page
	 */
	function printPlayerConfig($moviepath='',$imagetitle='',$count ='') {
		echo $this->getPlayerConfig($moviepath,$imagetitle,$count);
	}

	/**
	 * Returns the height of the player
	 * @param object $image the image for which the width is requested
	 *
	 * @return int
	 */
	function getVideoWidth($image=NULL) {
		return getOption('flow_player3_width');
	}
	
	/**
	 * Returns the width of the player
	 * @param object $image the image for which the height is requested
	 *
	 * @return int
	 */
	function getVideoHeigth($image=NULL) {
		if (!is_null($image) && strtolower(strrchr($image->filename, ".") == '.mp3')) {
			return FLOW_PLAYER_MP3_HEIGHT;
		}
		return getOption('flow_player3_height');
	}
	
	/**
	 * Returns the file extension if the item passed is displayable by the player
	 * 
	 * @param mixed $image either an image object or the filename of an image.
	 * @return string;
	 */
	function is_valid($image) {
		$valid_types = array('jpg','jpeg','gif','png','mov','3gp','flv','mp3','mp4');
		if (is_object($image)) $image = $image->filename;
		$ext = getSuffix($image);
		if (in_array($ext, $valid_types)) {
			return $ext; 
		}
		return false;
	}
}
?>