<?php
/**
 * flvplayer -- plugin support for the flvplayer flash video player.
 * NOTE: Flash players do not support external albums!
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @version 1.0.2.8.4
 * @package plugins
 */

$plugin_description = ($external = (getOption('album_folder_class') === 'external'))? gettext('<strong>Flash players do not support <em>External Albums</em>!</strong>'): gettext("Enable <strong>FLV</strong> player to handle multimedia files. IMPORTANT: Only one multimedia player plugin can be enabled at the time.<br> Please see <a href='http://www.jeroenwijering.com/?item=JW_FLV_Player'>JW FLV media player </a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.0.2.8.4';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---flvplayer.php.html";
$plugin_disable = $external;
$option_interface = new flvplayer();
$_zp_flash_player = $option_interface; // claim to be the flash player.

if ($external) return; // can't process external album images

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
		setOptionDefault('flv_player_autostart', '');
		setOptionDefault('flv_player_buffer','0');
		//setOptionDefault('flv_player_ignoresize_for_mp3', 'true');
	}

	function getOptionsSupported() {
		return array(	gettext('flv player width') => array('key' => 'flv_player_width', 'type' => 0,
										'desc' => gettext("Player width (ignored for <em>mp3</em> files.)")),
		gettext('flv player height') => array('key' => 'flv_player_height', 'type' => 0,
										'desc' => gettext("Player height (ignored for .<em>mp3</em> files if there is no preview image available.)")),
		gettext('Backcolor') => array('key' => 'flv_player_backcolor', 'type' => 0,
										'desc' => gettext("Backgroundcolor of the controls, in HEX format.")),
		gettext('Frontcolor') => array('key' => 'flv_player_frontcolor', 'type' => 0,
										'desc' => gettext("Texts & buttons color of the controls, in HEX format.")),
		gettext('Lightcolor') => array('key' => 'flv_player_lightcolor', 'type' => 0,
										'desc' => gettext("Rollover color of the controls, in HEX format.")),
		gettext('Screencolor') => array('key' => 'flv_player_screencolor', 'type' => 0,
										'desc' => gettext("Color of the display area, in HEX format.")),
		gettext('Displayheight') => array('key' => 'flv_player_displayheight', 'type' => 0,
										'desc' => gettext("The height of the player display. Generally it should be the same as the height. (ignored for .<em>mp3</em> files if there is no preview image available.)")),
		gettext('Autostart') => array('key' => 'flv_player_autostart', 'type' => 1,
										'desc' => gettext("Should the video start automatically. Yes if selected.")),
		gettext('BufferSize') => array('key' => 'flv_player_buffer', 'type' => 0,
										'desc' => gettext("Size of the buffer in % before the video starts.")),
		);
	}

	/**
	 * Prints the JS configuration of flv player
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
	 * @param string $imagetitle the title of the movie to be passed to the player for display (within slideshow), if empty (within albums) the function getImageTitle() is used
	 */
	function playerConfig($moviepath='',$imagetitle='',$count ='') {
		define ('FLV_PLAYER_MP3_HEIGHT', 20)
;		global $_zp_current_image, $_zp_current_album;
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
				$videoThumb = getAlbumFolder(WEBPATH).$albumfolder.'/'.$videoThumb;
			}
		}
		echo '<p id="player'.$count.'"><a href="http://www.macromedia.com/go/getflashplayer">'.gettext("Get Flash").'</a> to see this player.</p>
			<script type="text/javascript">';
		if($ext === ".mp3" AND !isset($videoThumb)) {
			echo'	var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER . '/plugins/flvplayer/flvplayer.swf","player'.$count.'","'.getOption('flv_player_width').'","'.FLV_PLAYER_MP3_HEIGHT.'","7");';
		} else {
			echo'	var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER . '/plugins/flvplayer/flvplayer.swf","player'.$count.'","'.getOption('flv_player_width').'","'.getOption('flv_player_height').'","7");
			so.addVariable("displayheight","'.getOption('flv_player_displayheight').'");';
		}
			echo '
			so.addParam("allowfullscreen","true");
			so.addVariable("file","' . $moviepath . '&amp;title=' . strip_tags($imagetitle) . '");
			' . (!empty($videoThumb) ? 'so.addVariable("image","' . $videoThumb . '")' : '') . '
			so.addVariable("backcolor","'.getOption('flv_player_backcolor').'");
			so.addVariable("frontcolor","'.getOption('flv_player_frontkcolor').'");
			so.addVariable("lightcolor","'.getOption('flv_player_lightcolor').'");
			so.addVariable("screencolor","'.getOption('flv_player_screencolor').'");
			so.addVariable("autostart","' . (getOption('flv_player_autostart') ? 'true' : 'false') . '");
			so.addVariable("overstretch","true");
			so.addVariable("bufferlength","'.getOption('flv_player_buffer').'");
			so.write("player'.$count.'");
			</script>';
	}

	/**
	 * Returns the height of the player
	 * @param object $image the image for which the width is requested
	 *
	 * @return int
	 */
	function getVideoWidth($image=NULL) {
		return getOption('flv_player_width');
	}

	/**
	 * Returns the width of the player
	 * @param object $image the image for which the height is requested
	 *
	 * @return int
	 */
	function getVideoHeigth($image=NULL) {
		if (!is_null($image) && strtolower(strrchr($image->filename, ".") == '.mp3')) {
			return FLV_PLAYER_MP3_HEIGHT;
		}
		return getOption('flv_player_height');
	}
}
?>