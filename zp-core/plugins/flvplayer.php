<?php
/**
 * flvplayer -- plugin support for the flvplayer flash video player. Support for version 3 and 4.
 * 
 * Note: 1.1 now incorporates the former separate flv_playlist plugin to show the content of an media album with .flv/.mp4/.mp3 movie/audio files as a playlist or as separate players with flv player. Note:</strong>Currently internally uses FLV player version 3 because of API incompatiblity with version 4.
 * 
 * IMPORTANT: Flash players do not support external albums!
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @version 1.1.1
 * @package plugins
 */

$plugin_description = ($external = (getOption('album_folder_class') === 'external'))? gettext('<strong>Flash players do not support <em>External Albums</em>!</strong>'): gettext("Enable <strong>FLV</strong> player to handle multimedia files. IMPORTANT: Only one multimedia player plugin can be enabled at the time.<br />Version 1.1. now incorporates the flv_playlist plugin to show the content of an media album with .flv/.mp4/.mp3 movie/audio files only as a playlist or as separate players with flv player.<strong>Note:</strong>Currently supports only FLV player version 3.<br> <strong>NOTE: You need to buy a licence from the player's developer LongTail Video if you intend to use this plugin for commercial purposes.</strong> Please see <a href='http://www.longtailvideo.com/players/jw-flv-player/'>LongTail Video - JW players</a> for more info about the player and its licence.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.1.1';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---flvplayer.php.html";
$plugin_disable = $external;
$option_interface = new flvplayer();
$_zp_flash_player = $option_interface; // claim to be the flash player.

if ($external) return; // can't process external album images

// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . 'flvplayer/swfobject.js"></script>');


define ('FLV_PLAYER_MP3_HEIGHT', 20);
/**
 * Plugin option handling class
 *
 */
class flvplayer {
	
	function flvplayer() {
		setOptionDefault('flv_player_width', '320');
		setOptionDefault('flv_player_height', '240');
		setOptionDefault('flv_player_backcolor', '#FFFFFF');
		setOptionDefault('flv_player_frontcolor', '#000000');
		setOptionDefault('flv_player_lightcolor', '#000000');
		setOptionDefault('flv_player_screencolor', '#000000');
		setOptionDefault('flv_player_displayheight', '240');
		setOptionDefault('flv_player_autostart', '');
		setOptionDefault('flv_player_buffer','0');
		setOptionDefault('flv_player_version','player3');
		setOptionDefault('flv_player_controlbar','over');
		//setOptionDefault('flv_player_ignoresize_for_mp3', 'true');
		
		// flv_playlist options
		setOptionDefault('flvplaylist_width', '600');
		setOptionDefault('flvplaylist_height', '240');
		setOptionDefault('flvplaylist_displaywidth', '320');
		setOptionDefault('flvplaylist_displayheight', '240');
		setOptionDefault('flvplaylist_thumbsinplaylist', '');
	}

	function getOptionsSupported() {
		$result = array(	gettext('flv player width') => array('key' => 'flv_player_width', 'type' => 0,
										'desc' => gettext("Player width (ignored for <em>mp3</em> files.)")),
										gettext('flv player height') => array('key' => 'flv_player_height', 'type' => 0,
																		'desc' => gettext("Player height (ignored for .<em>mp3</em> files if there is no preview image available.)")),
										gettext('Displayheight') => array('key' => 'flv_player_displayheight', 'type' => 0,
																		'desc' => gettext("The height of the player display. Generally it should be the same as the height. (ignored for .<em>mp3</em> files if there is no preview image available.)")),
										gettext('Autostart') => array('key' => 'flv_player_autostart', 'type' => 1,
																		'desc' => gettext("Should the video start automatically. Yes if selected.")),
										gettext('BufferSize') => array('key' => 'flv_player_buffer', 'type' => 0,
																		'desc' => /*xgettext:no-php-format*/ gettext("Size of the buffer in % before the video starts.")),
										gettext('FLV Player version') => array('key' => 'flv_player_version', 'type' => 5,
																		'selections' => array(gettext('Version 3')=>"player3", gettext('Version 4')=>"player4"),
																		'desc' => gettext("The FLV Player version to be used. Note that due to API changes version 3 and 4 support not all the same options as noted.")),
										gettext('Controlbar position') => array('key' => 'flv_player_controlbar', 'type' => 5,
																		'selections' => array(gettext('Bottom')=>"bottom", gettext('Over')=>"over", gettext('None')=>"none"),
																		'desc' => gettext("The position of the controlbar. <em>Player version 4 only!</em>")),
										gettext('Playlist Width') => array('key' => 'flvplaylist_width', 'type' => 0,
																		'desc' => gettext("Player width for the playlist")),
										gettext('Playlist Height') => array('key' => 'flvplaylist_height', 'type' => 0,
																		'desc' => gettext("Player height for the playlist (ignored for .<em>mp3</em> files if there is no preview image available.)")),
										gettext('Playlist Displaywidth') => array('key' => 'flvplaylist_displaywidth', 'type' => 0,
																		'desc' => gettext("Display width for the playlist. The display width is needed for the playlist menu to be shown. In this case the 'displaywidth - width = width of the playlist menu'. See the flv player site for more info about these options.")),
										gettext('Playlist Displayheight') => array('key' => 'flvplaylist_displayheight', 'type' => 0,
																		'desc' => gettext("Display height for the playlist. If the width is too small to show the playlist menu, you can set the height higher to show it below the actual movie display. See the flv player site for more info about these options.")),
										gettext('Playlist Thumbs in playlist') => array('key' => 'flvplaylist_thumbsinplaylist', 'type' => 1,
																		'desc' => gettext("Check if you want that thumbnails of the preview images should be shown in the playlist."))
										);
		
		if (getOption('flv_player_version') == 'player3') {
			$result = array_merge($result, 
						array(	gettext('Backcolor') => array('key' => 'flv_player_backcolor', 'type' => 8,
																		'desc' => gettext("Backgroundcolor of the controls, in HEX format. <em>Player version 3 only!</em>")),
										gettext('Frontcolor') => array('key' => 'flv_player_frontcolor', 'type' => 8,
																		'desc' => gettext("Texts & buttons color of the controls, in HEX format. <em>Player version 3 only!</em>")),
										gettext('Lightcolor') => array('key' => 'flv_player_lightcolor', 'type' => 8,
																		'desc' => gettext("Rollover color of the controls, in HEX format. <em>Player version 3 only!</em>")),
										gettext('Screencolor') => array('key' => 'flv_player_screencolor', 'type' => 8,
																		'desc' => gettext("Color of the display area, in HEX format. <em>Player version 3 only!</em>"))
										));
		};
		
		return $result;
	}

	/**
	 * Prints the JS configuration of flv player
	 *
	 * @param string $moviepath the direct path of a movie (within the slideshow), if empty (within albums) the zenphoto function getUnprotectedImageURL() is used
	 * @param string $imagetitle the title of the movie to be passed to the player for display (within slideshow), if empty (within albums) the function getImageTitle() is used
	 * @param string $count unique text for when there are multiple player items on a page
	 */
	function getPlayerConfig($moviepath='',$imagetitle='',$count ='') {
		global $_zp_current_image, $_zp_current_album;
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
		$output = '';
		$output .= '<p id="player'.$count.'"><a href="http://www.macromedia.com/go/getflashplayer">'.gettext("Get Flash").'</a> to see this player.</p>
			<script type="text/javascript">';
		if($ext === ".mp3" AND !isset($videoThumb)) {
			$output .= '	var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER .PLUGIN_FOLDER . 'flvplayer/'.getOption("flv_player_version").'.swf","player'.$count.'","'.getOption('flv_player_width').'","'.FLV_PLAYER_MP3_HEIGHT.'","7");';
		} else {
			$output .= '	var so = new SWFObject("' . WEBPATH . '/' . ZENFOLDER .PLUGIN_FOLDER . 'flvplayer/'.getOption("flv_player_version").'.swf","player'.$count.'","'.getOption('flv_player_width').'","'.getOption('flv_player_height').'","7");';
			$output .=  'so.addVariable("displayheight","'.getOption('flv_player_displayheight').'");';
		}
		$output .= 'so.addParam("allowfullscreen","true");
			so.addVariable("file","' . $moviepath . '&amp;title=' . strip_tags($imagetitle) . '");
			' . (!empty($videoThumb) ? 'so.addVariable("image","' . $videoThumb . '")' : '') . '
			so.addVariable("backcolor","'.getOption('flv_player_backcolor').'");
			so.addVariable("frontcolor","'.getOption('flv_player_frontkcolor').'");
			so.addVariable("lightcolor","'.getOption('flv_player_lightcolor').'");
			so.addVariable("screencolor","'.getOption('flv_player_screencolor').'");
			so.addVariable("autostart","' . (getOption('flv_player_autostart') ? 'true' : 'false') . '");
			so.addVariable("overstretch","true");
			so.addVariable("bufferlength","'.getOption('flv_player_buffer').'");
			so.addVariable("controlbar","'.getOption('flv_player_controlbar').'");
			so.write("player'.$count.'");
			</script>';
		return $output;
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

/**
 * To show the content of an media album with .flv/.mp4/.mp3 movie/audio files only as a playlist or as separate players with flv player
 * NOTE: The flv player plugin needs to be installed (This plugin currently internally uses FLV player 3 because of FLV player 4 Api changes!)
 * 
 * The playlist is meant to replace the 'next_image()' loop on a theme's album.php.
 * It can be used with a special 'album theme' that can be assigned to media albums with with .flv/.mp4/.mp3s 

 * movie/audio files only. See the examples below 
 * You can either show a 'one player window' playlist or show all items as separate players paginated 

 * (set in the settings for thumbs per page) on one page (like on a audio or podcast blog).
 *
 * If there is no preview image for a mp3 file existing only the player control bar is shown.
 * 
 * The two modes:
 * a) 'playlist'
 * Replace the entire 'next_image()' loop on album.php with this:
 * <?php flvPlaylist("playlist"); ?>
 * 
 * b) 'players'
 * Modify the 'next_image()' loop on album.php like this:
 * <?php	
 * while (next_image(false,$firstPageImages)):
 * printImageTitle();
 * flvPlaylist("players");
 * endwhile;
 * ?>
 * Of course you can add further functions to b) like title, description, date etc., too.
 * 
 * @param string $option the mode to use "playlist" or "players"
 */
function flvPlaylist($option='') {
	global $_zp_current_album, $_zp_current_image;
	if(checkAlbumPassword($_zp_current_album->getFolder(), $hint)) {
		if($option === "players") {
			$moviepath = getUnprotectedImageURL();
			$ext = strtolower(strrchr(getUnprotectedImageURL(), "."));
		}
		$imagetitle = getImageTitle();
	}
	$albumid = getAlbumID();

	switch($option) {
		case "playlist":
			
			if(getNumImages() != 0) {
			?>
	<div id="flvplaylist"><?php echo gettext("The flv player is not installed. Please install or activate the flv player plugin."); ?></div>
	<script type="text/javascript">
		var so = new SWFObject('<?php echo WEBPATH."/".ZENFOLDER.PLUGIN_FOLDER; ?>flvplayer/player3.swf','jstest','<?php echo getOption('flvplaylist_width'); ?>','<?php echo getOption('flvplaylist_height'); ?>','8');
		so.addParam('allowfullscreen','true');
		so.addParam('overstretch','true');
		so.addVariable('displaywidth', '<?php echo getOption('flvplaylist_displaywidth'); ?>');
		so.addVariable('displayheight','<?php echo getOption('flvplaylist_displayheight'); ?>');
		so.addVariable('backcolor','<?php echo getOption('flv_player_backcolor'); ?>');
		so.addVariable('frontcolor','<?php echo getOption('flv_player_frontcolor'); ?>');
		so.addVariable('lightcolor','<?php echo getOption('flv_player_lightcolor'); ?>');
		so.addVariable('screencolor','<?php echo getOption('flv_player_screencolor'); ?>');
		so.addVariable('file','<?php echo WEBPATH."/".ZENFOLDER.PLUGIN_FOLDER; ?>flvplayer/playlist.php?albumid=<?php echo $albumid; ?>');
		so.addVariable('javascriptid','jstest');
		so.addVariable('enablejs','true');
		so.addVariable('thumbsinplaylist','<?php echo (getOption('flvplaylist_thumbsinplaylist') ? 'true' : 'false') ?>');
		so.write('flvplaylist');
	</script>
	<?php }
		break;

		case "players":
			if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
				echo "<div id=\"flvplaylist-".$imagetitle."\">".gettext("The flv player is not installed. Please install or activate the flv player plugin..")."</div>";
					
				// check if an image/videothumb is available - this shouldn't be hardcoded...
				$album = $_zp_current_image->getAlbum();
				$videoThumb = checkObjectsThumb($album->localpath, $_zp_current_image->filename);
				if (!empty($videoThumb)) {
					$videoThumb = getAlbumFolder(WEBPATH).$album->name.'/'.$videoThumb;
				}										
		?>
		<script type="text/javascript">
		 <?php 

		 if(($ext == ".mp3") && empty($videoThumb)) { 
		 ?> 
			var so = new SWFObject('<?php echo WEBPATH."/".ZENFOLDER.PLUGIN_FOLDER; ?>flvplayer/player3.swf','jstest','<?php echo getOption('flvplaylist_width'); ?>','20','8');
		<?php } else { ?>
			var so = new SWFObject('<?php echo WEBPATH."/".ZENFOLDER.PLUGIN_FOLDER; ?>flvplayer/player3.swf','jstest','<?php echo getOption('flvplaylist_width'); ?>','<?php echo getOption('flvplaylist_height'); ?>','8');
		<?php } ?>
			so.addParam('allowfullscreen','true');
			so.addParam('overstretch','true');
			so.addVariable("image",'<?php echo $videoThumb; ?>');
			so.addVariable('backcolor','<?php echo getOption('flv_player_backcolor'); ?>');
			so.addVariable('frontcolor','<?php echo getOption('flv_player_frontcolor'); ?>');
			so.addVariable('lightcolor','<?php echo getOption('flv_player_lightcolor'); ?>');
			so.addVariable('screencolor','<?php echo getOption('flv_player_screencolor'); ?>');
			so.addVariable('file','<?php echo $moviepath; ?>');
			so.addVariable('javascriptid','jstest');
			so.addVariable('enablejs','true');
			so.write('flvplaylist-<?php echo escape($imagetitle); ?>');
	<?php	} ?>
		</script>
	<?php
	break;
	}
} // password check end
?>