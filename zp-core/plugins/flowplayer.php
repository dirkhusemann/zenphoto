<?php
/*
 * flowplayer -- plugin support for the flowplayer flash video player.
 *
 *  Plugin option 'flow_player_width' -- width of the player window
 *  Plugin option 'flow_player_height' -- height of the player window
 */

$plugin_description = gettext("Enable <strong>flowplayer</strong> to handle multimedia files. IMPORTANT: Only one multimedia player plugin can be enabled at the time. <br> Please see <a href='http://flowplayer.org'>flowplayer.org</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.1.5';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---flowplayer.php.html";
$option_interface = new flowplayer();
$_zp_flash_player = $option_interface; // claim to be the flash player.
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
										'desc' => gettext("Should the video start automatically. Yes if selected."))
		);
	}

	/**
	 * Print the JS configuration of flowplayer
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
	 * @param string $imagetitle the title of the movie [not used by flowplayer]
	 * 	 */
	function playerConfig($moviepath='', $imagetitle,$count='') {
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
		if(getOption("flow_player_autoplay")) {
			$autoplay = "true";
		} else {
			$autoplay = "false";
		}
		if($ext === ".mp3") {
			echo '
			<p id="playerContainer'.$count.'"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer'.$count.'").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/FlowPlayerLight.swf\',
      	width:'.getOption('flow_player_width').', 
      	height:28
    	},
    		{config: {  
      		autoPlay: \'' . $autoplay . '\',
      		useNativeFullScreen: true,
					loop: false,
					autoBuffering: true,
      		videoFile: \'' . $moviepath . '\',
      		initialScale: \'scale\'
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
      		autoPlay: \'' . $autoplay . '\',
    			loop: false,
					controlsOverVideo: "ease",
      		videoFile: \'' . $moviepath . '\',
      		initialScale: \'scale\'
    		}} 
  		);
			</script>';
		 }
	}
}
?>