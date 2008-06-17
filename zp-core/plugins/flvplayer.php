<?php
/**
 * flvplayer -- plugin support for the flvplayer flash video player.
 *
 *  Plugin option 'flv_player_width' -- width of the player window
 *  Plugin option 'flv_player_height' -- height of the player window
  */

$plugin_description = gettext("Enable <strong>FLV</strong> player to handle multimedia files. IMPORTANT: Only one multimedia player plugin can be enabled at the time.<br> Please see <a href='http://www.jeroenwijering.com/?item=JW_FLV_Player'>JW FLV media player </a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.2.4';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---flvplayer.php.html";
$option_interface = new flvplayer();
$_zp_flash_player = $option_interface; // claim to be the flash player.
// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/plugins/flvplayer/flvplayer.js"></script>');


/**
 * Plugin option handling class
 *
 */
class flvplayer {

	function flvplayer() {
		setOptionDefault('flv_player_width', '320');
		setOptionDefault('flv_player_height', '240');
		setOptionDefault('flv_player_backcolor', '0xFFFFFF');
		setOptionDefault('flv_player_frontcolor', '0x000000');
		setOptionDefault('flv_player_lightcolor', '0x000000');
		setOptionDefault('flv_player_screencolor', '0x000000');
		setOptionDefault('flv_player_displayheight', '240');
		setOptionDefault('flv_player_autostart', 'true');
		//setOptionDefault('flv_player_ignoresize_for_mp3', 'true');
	}

	function getOptionsSupported() {
		return array(	gettext('flv player width') => array('key' => 'flv_player_width', 'type' => 0,
										'desc' => gettext("Player width (ignored for <em>mp3</em> files.)")),
		gettext('flv player height') => array('key' => 'flv_player_height', 'type' => 0,
										'desc' => gettext("Player height (ignored for .<em>mp3</em> files.)")),
		gettext('Backcolor') => array('key' => 'flv_player_backcolor', 'type' => 0,
										'desc' => gettext("Backgroundcolor of the controls, in HEX format.")),
		gettext('Frontcolor') => array('key' => 'flv_player_frontcolor', 'type' => 0,
										'desc' => gettext("Texts & buttons color of the controls, in HEX format.")),
		gettext('Lightcolor') => array('key' => 'flv_player_lightcolor', 'type' => 0,
										'desc' => gettext("Rollover color of the controls, in HEX format.")),
		gettext('Screencolor') => array('key' => 'flv_player_screencolor', 'type' => 0,
										'desc' => gettext("Color of the display area, in HEX format.")),
		gettext('Displayheight') => array('key' => 'flv_player_displayheight', 'type' => 0,
										'desc' => gettext("The height of the player display. Generally it should be the same as the height.")),
		gettext('Autostart') => array('key' => 'flv_player_autostart', 'type' => 1,
										'desc' => gettext("Should the video start automatically. Yes if selected.")),
		);
	}

	/**
	 * Prints the JS configuration of flv player
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
	 * @param string $imagetitle the title of the movie to be passed to the player for display (within slideshow), if empty (within albums) the function getImageTitle() is used
	 */
	function playerConfig($moviepath='',$imagetitle='',$count ='') {
		global $_zp_current_image;
		if(empty($moviepath)) {
			$moviepath = getUnprotectedImageURL();
			$ext = strtolower(strrchr(getUnprotectedImageURL(), "."));
		} else {
			$ext = strtolower(strrchr($moviepath, "."));
		}
		if(empty($imagetitle)) {
			$imagetitle = getImageTitle();
		}
		if(!empty($count)) {
			$count = "-".$count;
		}
		// check if an image/videothumb is available - this shouldn't be hardcoded...
		$imgextensions = array(".jpg",".jpeg",".gif",".png");
		// this just won't work - here's a messy solution
		$album = $_zp_current_image->getAlbum();
		$albumfolder = $album->getFolder();

		// again, the horrid problem with mixed / and \ or windows
		$fullpath = str_replace('\\', '/', SERVERPATH . ALBUMFOLDER . $albumfolder . '/' . $_zp_current_image->getFileName());

		foreach ($imgextensions as $imgext){				
			$vt = preg_replace("/" . $ext . "/i", "" . $imgext . "", $fullpath);
			if(file_exists($vt)) {
				$videoThumb = substr(getFullImageURL(), 0, strrpos(getFullImageURL(), '.')) . $imgext;
				break;
			}
		}
		echo '
			<p id="player'.$count.'"><a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.</p>
			<script type="text/javascript">
			var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER . '/plugins/flvplayer/flvplayer.swf","player'.$count.'","'.getOption('flv_player_width').'","'.getOption('flv_player_height').'","7");
			so.addParam("allowfullscreen","true");
			so.addVariable("file","' . $moviepath . '&amp;title=' . $imagetitle . '");
			' . (isset($videoThumb) ? 'so.addVariable("image","' . $videoThumb . '")' : '') . '
			so.addVariable("backcolor","'.getOption('flv_player_backcolor').'");
			so.addVariable("frontcolor","'.getOption('flv_player_frontkcolor').'");
			so.addVariable("lightcolor","'.getOption('flv_player_lightcolor').'");
			so.addVariable("screencolor","'.getOption('flv_player_screencolor').'");
			so.addVariable("displayheight","'.getOption('flv_player_displayheight').'");
			so.addVariable("autostart","' . (getOption('flv_player_autostart') == 0 ? 'false' : 'true') . '");
			so.write("player");
			</script>'; 
	}
}
?>