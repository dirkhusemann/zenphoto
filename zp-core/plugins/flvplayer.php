<?php
$plugin_description = gettext("Enable FLV player to handle the .flv video files. IMPORTANT: Only one flash player plugin can be enabled at the time.<br> Please see <a href='http://http://www.jeroenwijering.com/?item=JW_FLV_Player'>http://www.jeroenwijering.com/?item=JW_FLV_Player</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0.0';

// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/plugins/flvplayer/flvplayer.js"></script>');

function flvplayerConfig($moviepath='',$imagetitle='') {
	if(empty($moviepath)) {
		$moviepath = getUnprotectedImageURL();
	}
	if(empty($imagetitle)) {
		$imagetitle = getImageTitle();
	}
			echo '</a>
			<p id="player"><a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.</p>
			<script type="text/javascript">
			var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER . '/plugins/flvplayer/flvplayer.swf","player","320","240","7");
			so.addParam("allowfullscreen","true");
			so.addVariable("file","' . $moviepath . '&amp;title=' . $imagetitle . '");
			so.addVariable("displayheight","310");
			so.write("player");
			</script><a>'; 
}
?>