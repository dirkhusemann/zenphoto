<?php
/** 
 * flowplayer -- plugin support for the flowplayer flash video player.
 * NOTE: Flash players do not support external albums!
 * 
 * Plugin option 'flow_player_width' -- width of the player window
 * Plugin option 'flow_player_height' -- height of the player window
 *  
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @version 1.0.3
 * @package plugins 
 */


$plugin_description = ($external = (getOption('album_folder_class') === 'external'))? gettext('<strong>Flash players do not support <em>External Albums</em>!</strong>'): gettext("Enable <strong>flowplayer</strong> to handle multimedia files. IMPORTANT: Only one multimedia player plugin can be enabled at the time. <br> Please see <a href='http://flowplayer.org'>flowplayer.org</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.2';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---flowplayer.php.html";
$option_interface = new flowplayer();
$plugin_disable = $external;
$_zp_flash_player = $option_interface; // claim to be the flash player.

if ($external) return; // can't process external album images

// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/jquery.flashembed.pack.js"></script>');

/**
 * Plugin option handling class
 *
 */
class flowplayer {

	function flowplayer() {
		setOptionDefault('flow_player_width', '320');
		setOptionDefault('flow_player_height', '240');
		setOptionDefault('flow_player_controlbarbackgroundcolor', '0x567890');
		setOptionDefault('flow_player_controlsareabordercolor', '0x567890');
		setOptionDefault('flow_player_autoplay', '');
		setOptionDefault('flow_player_backgroundcolor', '');
	}


	function getOptionsSupported() {
		return array(	gettext('flow player width') => array('key' => 'flow_player_width', 'type' => 0,
										'desc' => gettext("Player width (ignored for <em>mp3</em> files.)")),
		gettext('flow player height') => array('key' => 'flow_player_height', 'type' => 0,
										'desc' => gettext("Player height (ignored for <em>mp3</em> files.)")),
		gettext('Controls background color') => array('key' => 'flow_player_controlbarbackgroundcolor', 'type' => 0,
										'desc' => gettext("Background color of the controls.")),
		gettext('Control area border color') => array('key' => 'flow_player_controlsareabordercolor', 'type' => 0,
										'desc' => gettext("Color of the border of the player controls")),
		gettext('Autoplay') => array('key' => 'flow_player_autoplay', 'type' => 1,
										'desc' => gettext("Should the video start automatically. Yes if selected.")),
		gettext('Background color') => array('key' => 'flow_player_backgroundcolor', 'type' => 0,
										'desc' => gettext("Changes the color of the Flowplayer's background canvas. By default the canvas is all black. You can specify a value of -1 and the background will not be drawn (only the video will be visible)."))
		);
	}

	/**
	 * Print the JS configuration of flowplayer
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
	 * @param string $imagetitle the title of the movie [not used by flowplayer]
	 * 	 */
	function playerConfig($moviepath='', $imagetitle,$count='') {
		define ('FLOW_PLYER_MP3_HEIGHT', 28);
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
		if(getOption("flow_player_autoplay") == 1) {
			$autoplay = "true";
		} else {
			$autoplay = ""; // actually false should work, but it doesn't...
		}
		if($ext === ".mp3") {
			echo '
			<p id="playerContainer'.$count.'"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer'.$count.'").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/FlowPlayerLight.swf\',
      	width:'.getOption('flow_player_width').', 
      	height:'.FLOW_PLYER_MP3_HEIGHT.'
    	},
    		{config: {  
      		autoPlay: \''.$autoplay.'\',
    			loop: false,
					controlsOverVideo: \'false\',
      		videoFile: \'' . $moviepath . '\',
      		initialScale: \'fit\',
      		backgroundColor: \''.getOption('flow_player_backgroundcolor').'\',
      		controlBarBackgroundColor: \''.getOption('flow_player_controlbarbackgroundcolor').'\',
      		controlsAreaBorderColor: \''.getOption('flow_player_controlsareabordercolor').'\'
    		}} 
  		);
			</script>';
		} else { 
			echo '
			<p id="playerContainer'.$count.'"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer'.$count.'").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/FlowPlayerLight.swf\',
      	width:'.getOption('flow_player_width').', 
      	height:'.getOption('flow_player_height').'
    	},
    		{config: {  
      		autoPlay: \''.$autoplay.'\',
    			loop: false,
					controlsOverVideo: \'ease\',
      		videoFile: \'' . $moviepath . '\',
      		initialScale: \'fit\',
      		backgroundColor: \''.getOption('flow_player_backgroundcolor').'\',
      		controlBarBackgroundColor: \''.getOption('flow_player_controlbarbackgroundcolor').'\',
      		controlsAreaBorderColor: \''.getOption('flow_player_controlsareabordercolor').'\'
    		}} 
  		);
			
  		</script>';
		 }
	}
	
	/**
	 * Returns the height of the player
	 * @param object $image the image for which the width is requested
	 *
	 * @return int
	 */
	function getVideoWidth($image=NULL) {
		return getOption('flow_player_width');
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
		return getOption('flow_player_height');
	}
}
?>