<?php
/** 
 * flowplayer3 playlist - Show the content of an media album with .flv/.mp4/.mp3 movie/audio files only as a playlist or as separate players with Flowplayer 3
 * IMPORTANT: The Flowplayer 3 plugin needs to be activated to use this plugin.
 * 
 * Note that this does not work with pure image albums and is not meant to!
 * 
 * See usage details below
 * 
 * NOTE: Flash players do not support external albums!
 * 
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @version 1.0.2
 * @package plugins 
 */


$plugin_description = ($external = (getOption('album_folder_class') === 'external'))? gettext('<strong>Flash players do not support <em>External Albums</em>!</strong>'): gettext("Show the content of an media album with .flv/.mp4/.mp3 movie/audio files only as a playlist or as separate players on one page with Flowplayer 3. Important: The Flowplayer 3 plugin needs to be activated to use this plugin.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0.2';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---flowplayer3_playlist.php.html";
$plugin_disable = $external;
$option_interface = new flowplayer3_playlist();

if ($external) return; // can't process external album images 

// register the scripts needed - only playlist additions, all others incl. the playlist plugin are loaded by the flowplayer3 plugin!
if ( in_context(ZP_ALBUM) && !OFFSET_PATH) {
	$theme = getCurrentTheme();
	$css = SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($theme) . '/flowplayer3_playlist.css';
	if (file_exists($css)) {
		$css = WEBPATH . '/' . THEMEFOLDER . '/' . $theme . '/flowplayer3_playlist.css';
	} else {
		$css = WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . 'flowplayer3_playlist/flowplayer3_playlist.css';
	}
	addPluginScript('
		<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER .'flowplayer3_playlist/jquery.mousewheel.pack.js"></script>
		<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER .'flowplayer3_playlist/jquery.scrollable.pack.js"></script>
		<link rel="stylesheet" type="text/css" href="' . $css . '" />
		');
}
/**
 * Plugin option handling class
 *
 */
class flowplayer3_playlist {
	function flowplayer3_playlist() {
		setOptionDefault('flow_player3_playlistwidth', '320');
		setOptionDefault('flow_player3_playlistheight', '240');
		setOptionDefault('flow_player3_playlistautoplay', '');
		setOptionDefault('flow_player3_playlistsautohide','');
		setOptionDefault('flow_player3_playlistsplashimage','');
	}

	function getOptionsSupported() {
		return array(	gettext('flow player width') => array('key' => 'flow_player3_playlistwidth', 'type' => 0,
										'desc' => gettext("Player width (Note this refers to the player window. The playlist display itself is styled via CSS.)")),
		gettext('flow player height') => array('key' => 'flow_player3_playlistheight', 'type' => 0,
										'desc' => gettext("Player height (Note this refers to the player window. The playlist display itself is styled via CSS.)")),
		gettext('Autoplay') => array('key' => 'flow_player3_playlistautoplay', 'type' => 1,
										'desc' => gettext("Should the video start automatically. Yes if selected. (NOTE: Probably because of a flowplayer bug mp3s are always autoplayed.)")),
		gettext('Controls autohide') => array('key' => 'flow_player3_playlistautohide', 'type' => 1,
										'desc' => gettext("Specifies whether the controlbar should be hidden when the user is not actively using the player.")),
		gettext('Splash image') => array('key' => 'flow_player3_playlistsplashimage', 'type' => 1,
										'desc' => gettext("Check if you want to display the videothumb of the first playlist entry as a splash/cover image. It will be cropped to the fit the width and height of the player window and will dissapear on starting the playlist."))
		);
	}
}
/**
 * Show the content of an media album with .flv/.mp4/.mp3 movie/audio files only as a playlist or as separate players with Flowplayer 3
 * Important: The Flowplayer 3 plugin needs to be activated to use this plugin. This plugin shares all settings with this plugin, too.
 * 
 * You can either show a 'one player window' playlist or show all items as separate players paginated. See the examples below.
 * (set in the settings for thumbs per page) on one page (like on a audio or podcast blog).
 * 
 * There are two usage modes:
 * 
 * a) 'playlist'
 * The playlist is meant to replace the 'next_image()' loop on a theme's album.php.
 * It can be used with a special 'album theme' that can be assigned to media albums with with .flv/.mp4/.mp3s, although Flowplayer 3 also supports images
 * Replace the entire 'next_image()' loop on album.php with this:
 * <?php flowplayerPlaylist("playlist"); ?>
 * 
 * This produces the following html:
 * <div class="wrapper">
 * <a class="up" title="Up"></a>
 * <div class="playlist">		
 * <div class="clips">
 * <!-- single playlist entry as an "template" -->
 * <a href="${url}">${title}</a>
 * </div>
 * </div>
 * <a class="down" title="Down"></a>
 * </div>
 * </div>
 * This is styled by the css file 'playlist.css" that is located within the 'zp-core/plugins/flowplayer3_playlist/flowplayer3_playlist.css' by default.
 * Alternatively you can style it specifically for your theme. Just place a css file named "flowplayer3_playlist.css" in your theme's folder.
 * 
 * b) 'players'
 * This displays each audio/movie file as a separate player on album.php. 
 * If there is no videothumb image for an mp3 file existing only the player control bar is shown.
 * Modify the 'next_image()' loop on album.php like this:
 * <?php	
 * while (next_image(false,$firstPageImages)):
 * flowplayerPlaylist("players");
 * endwhile;
 * ?>
 * Of course you can add further functions to b) like printImageTitle() etc., too. 
 * 
 * @param string $option The mode to use "playlist" or "players"
 * @param string $albumfolder For "playlist" mode only: To show a playlist of an specific album directly on another page (for example on index.php). Note: Currently it is not possible to have several playlists on one page
 */
function flowplayerPlaylist($option="playlist",$albumfolder="") {
	global $_zp_current_image,$_zp_current_album,$_zp_flash_player;
	switch($option) {
		case "playlist":	
				if(empty($albumfolder)) {
					$albumname = $_zp_current_album->name;
				} else {
					$albumname = $albumfolder;
				}
				$album = new Album(new Gallery(), $albumname);
				if(getOption("flow_player3_playlistautoplay") == 1) {
					$autoplay = "true";
				} else {
					$autoplay = "false";
				}
				if(getOption("flow_player3_playlistautohide") == 1) {
					$autohide = "true";
				} else {
					$autohide = "false";
				}
				$playlist = $album->getImages();
				//$videoThumb = checkObjectsThumb(getAlbumFolder().$albumname, $playlist[0]);
				
				// slash image fetching 
				$videoobj = new Video($_zp_current_album,$playlist[0]);
				$albumfolder = $album->name;
				$videoThumb = checkObjectsThumb(getAlbumFolder().$albumfolder, $playlist[0]);
				if (!empty($videoThumb) AND getOption('flow_player3_playlistsplashimage')) {
					$videoThumb = "<img src=\"".WEBPATH."/zp-core/i.php?i=".$videoThumb."&amp;a=".$_zp_current_album->name."&amp;w=".getOption('flow_player3_playlistwidth')."&amp;h=".getOption('flow_player3_playlistheight')."&amp;cw=".getOption('flow_player3_playlistwidth')."&amp;ch=".getOption('flow_player3_playlistheight')."\" />";
				} else {
					$videoThumb = "";
				}
			if($album->getNumImages() != 0) {
			echo '<div class="flowplayer3_playlistwrapper">
			<a id="player" class="flowplayer3_playlist" style="display:block; width: '.getOption('flow_player3_playlistwidth').'px; height: '.getOption('flow_player3_playlistheight').'px;">
			'.$videoThumb.'
			</a>
			<script>
			$(function() {
			$("div.playlist").scrollable({
				items:"div.clips",
				vertical:true,
				next:"a.down",
				prev:"a.up"
			});
			flowplayer("player","'.WEBPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . 'flowplayer3/flowplayer.swf", {
			plugins: { 
				controls: {
        	backgroundColor: "'.getOption('flow_player3_controlsbackgroundcolor').'",
        	autoHide: '.$autohide.',
        	timeColor:"'.getOption('flow_player3_controlstimecolor').'",
        	durationColor: "'.getOption('flow_player3_controlsdurationcolor').'",
        	progressColor: "'.getOption('flow_player3_controlsprogresscolor').'",
        	progressGradient: "'.getOption('flow_player3_controlsprogressgradient').'",
        	bufferColor: "'.getOption('flow_player3_controlsbuffercolor').'",
        	bufferGradient:	 "'.getOption('fflow_player3_controlsbuffergradient').'",
        	sliderColor: "'.getOption('flow_player3_controlsslidercolor').'",	
        	sliderGradient: "'.getOption('flow_player3_controlsslidergradient').'",
        	buttonColor: "'.getOption('flow_player3_controlsbuttoncolor').'",
        	buttonOverColor: "'.getOption('flow_player3_controlsbuttonovercolor').'",
        	playlist: true
        }
    	},
    	canvas: {
    		backgroundColor: "'.getOption('flow_player3_backgroundcolor').'"
    	},';
			$list = '';
			foreach($playlist as $item) {
				$image = newImage($album, $item);
				$ext = strtolower(strrchr($item, "."));
				if (($ext == ".flv") || ($ext == ".mp3") || ($ext == ".mp4")) {
				$list .= '{
					url:"'.WEBPATH.ALBUMFOLDER.$album->name.'/'.$item.'",
					autoPlay: '.$autoplay.',
					title: "'.$image->getTitle().' <small>('.$ext.')</small>",
					autoBuffering: true
				},';
				} // if ext end
			} // foreach end
			echo 'playlist: ['.substr($list,0,-1).']
			}); 
			flowplayer("player").playlist("div.clips:first", {loop:true});
			});
			</script>';
		?>
		<div class="wrapper">
					<a class="up" title="Up"></a>
			<div class="playlist">		
				<div class="clips">
					<!-- single playlist entry as an "template" -->
					<a href="${url}">${title}</a>
				</div>
			</div>
		<a class="down" title="Down"></a>	
</div>
</div><!-- flowplayer3_playlist wrapper end -->
<?php } // check if there are images end
			break;
			case "players":
				$_zp_flash_player->printPlayerConfig('','',imageNumber());
				break;
		} // switch end
}

?>