<?php
$plugin_description = gettext("Enable Flowplayer to handle the .flv video files. IMPORTANT: Only one flash player plugin can be enabled at the time.<br> Please see <a href='http://flowplayer.org'>flowplayer.org</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller";
$plugin_version = '1.0.0';
// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/plugins/flowplayer/jquery.flashembed.pack.js"></script>');

function flowplayerConfig() {
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
      		videoFile: \'' . getUnprotectedImageURL() . '\',
      		initialScale: \'scale\'
    		}} 
  		);
			</script><a>';
}
?>