<?php
$plugin_description = gettext("Enable <strong>flowplayer</strong> to handle the .flv video files. IMPORTANT: Only one flash player plugin can be enabled at the time. If <strong>flowplayer</strong> is enabled it will override all other players.<br> Please see <a href='http://flowplayer.org'>flowplayer.org</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0.0';
$plugin_URL = Gettext("http://www.zenphoto.org/documentation/zenphoto/_plugins---flowplayer.php.html");
// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/jquery.flashembed.pack.js"></script>');

/**
 * Print the JS configuration of flowplayer
 *
 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
 */
function flowplayerConfig($moviepath='') {
	if(empty($moviepath)) {
		$moviepath = getUnprotectedImageURL();
	}
			echo '</a>
			<p id="playerContainer"><a href="http://www.adobe.com/go/getflashplayer">'.gettext('Get Flash').'</a> '.gettext('to see this player.').'</p>
			<script>
			$("#playerContainer").flashembed({
      	src:\'' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/FlowPlayerLight.swf\',
      	width:400, 
      	height:300
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
?>