<?php
/*
 * flowplayer -- plugin support for the flowplayer flash video player.
 * 
 */

$plugin_description = gettext("Enable <strong>flowplayer</strong> to handle the .flv video files. IMPORTANT: Only one flash player plugin can be enabled at the time. If <strong>flowplayer</strong> is enabled it will override all other players.<br> Please see <a href='http://flowplayer.org'>flowplayer.org</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0.1';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---flowplayer.php.html";
$option_interface = new flowplayerOptions();
// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/jquery.flashembed.pack.js"></script>');

/**
 * Plugin option handling class
 *
 */
class flowplayerOptions {

	function flowplayerOptions() {
		setOptionDefault('flow_player_width', '320');
		setOptionDefault('flow_player_height', '240');
		
	}
	
	
	function getOptionsSupported() {
		return array(	gettext('flow player width') => array('key' => 'flow_player_width', 'type' => 0, 
										'desc' => gettext("Player width (not for .mp3).")),
									gettext('flow player height') => array('key' => 'flow_player_height', 'type' => 0, 
										'desc' => gettext("Player height (not for .mp3)."))
								);
	}

}

/**
 * Print the JS configuration of flowplayer
 *
 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
 */
function flowplayerConfig($moviepath='') {
	if(empty($moviepath)) {
		$moviepath = getUnprotectedImageURL();
		$ext = strtolower(strrchr(getUnprotectedImageURL(), "."));
	} else {
		$ext = strtolower(strrchr($moviepath, "."));
	}
	if($ext === ".mp3") {
			echo '</a>
			<p id="playerContainer"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/FlowPlayerLight.swf\',
      	width:400, 
      	height:28
    	},
    		{config: {  
      		autoPlay: false,
					loop: false,
      		videoFile: \'' . $moviepath . '\',
      		initialScale: \'scale\'
    		}} 
  		);
			</script><a>';
	} else {
		echo '</a>
			<p id="playerContainer"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/FlowPlayerLight.swf\',
      	width:'.getOption('flow_player_width').', 
      	height:'.getOption('flow_player_height').'
    	},
    		{config: {  
      		autoPlay: false,
					loop: false,
      		videoFile: \'' . $moviepath . '\',
      		initialScale: \'scale\'
    		}} 
  		);
			</script><a>';
	}
}
?>