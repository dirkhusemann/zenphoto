<?php
/**
 *Video Class
 * @package classes
 */

// force UTF-8 Ø

$plugin_is_filter = 9;
$plugin_description = gettext('Video and MP3/4 handling for Zenphoto. This plugin must always be enabled to use multimedia content.');
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 

addPluginType('flv', 'Video');
addPluginType('3gp', 'Video');
addPluginType('mov', 'Video');
addPluginType('mp3', 'Video');
addPluginType('mp4', 'Video');
$option_interface = new VideoObject_Options();


/**
 * Option class for video objects
 *
 */
class VideoObject_Options {
	
	
	function VideoObject_Options() {
		setOption('zp_plugin_class-video_mov_w',520);
		setOption('zp_plugin_class-video_mov_h',390);
		setOption('zp_plugin_class-video_3gp_w',520);
		setOption('zp_plugin_class-video_3gp_h',390);
	}
	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(gettext('Watermark default images') => array ('key' => 'video_watermark_default_images', 'type' => OPTION_TYPE_CHECKBOX,
																	'desc' => gettext('Check to place watermark image on default thumbnail images.')),
		gettext('Quicktime video width') => array ('key' => 'zp_plugin_class-video_mov_w', 'type' => OPTION_TYPE_TEXTBOX,
																	'desc' => ''),
		gettext('Quicktime video height') => array ('key' => 'zp_plugin_class-video_mov_h', 'type' => OPTION_TYPE_TEXTBOX,
																	'desc' => ''),
		gettext('3gp video width') => array ('key' => 'zp_plugin_class-video_3gp_w', 'type' => OPTION_TYPE_TEXTBOX,
																	'desc' => ''),
		gettext('3gp video height') => array ('key' => 'zp_plugin_class-video_3gp_h', 'type' => OPTION_TYPE_TEXTBOX,
																	'desc' => '')
		);
	}
	
}

class Video extends _Image {

	/**
	 * Constructor for class-video
	 *
	 * @param object &$album the owning album
	 * @param sting $filename the filename of the image
	 * @return Image
	 */
	function Video(&$album, $filename) {
		global $_zp_supported_images;
		// $album is an Album object; it should already be created.
		if (!is_object($album)) return NULL;
		if (!$this->classSetup($album, $filename)) { // spoof attempt
			$this->exists = false;
			return;
		}
		$this->sidecars = $_zp_supported_images;
		$this->video = true;
		$this->objectsThumb = checkObjectsThumb($album->localpath, $filename);
		// Check if the file exists.
		if (!file_exists($this->localpath) || is_dir($this->localpath)) {
			$this->exists = false;
			return;
		}


		// This is where the magic happens...
		$album_name = $album->name;
		$this->updateDimensions();
		if (parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', false, empty($album_name))) {
			$this->set('mtime', $ts = filemtime($this->localpath));
			$this->updateMetaData();
			$this->save();
			zp_apply_filter('new_image', $this);
		}
	}

	/**
	 * Update this object's values for width and height.
	 *
	 */
	function updateDimensions() {
		global $_zp_flash_player;
		$ext = strtolower(strrchr($this->filename, "."));
		if (is_null($_zp_flash_player) || $ext == '.3gp' || $ext == '.mov') {
			switch ($ext) {
				case '.3gp':
					$h = getOption('zp_plugin_class-video_3gp_h');
					$w = getOption('zp_plugin_class-video_3gp_w');
					break;
				case '.mov':
					$h = getOption('zp_plugin_class-video_mov_h');
					$w = getOption('zp_plugin_class-video_mov_w');
					break;
				default:
					$h = 320;
					$w = 480;
			}
		} else {
			$h = $_zp_flash_player->getVideoHeigth($this);
			$w = $_zp_flash_player->getVideoWidth($this);
		}
		$this->set('width', $w);
		$this->set('height', $h);
	}

	/**
	 * Returns the image file name for the thumbnail image.
	 * 
	 * @param string $path override path 
	 *
	 * @return string
	 */
	function getThumbImageFile($path=NULL) {
		if (is_null($path)) $path = SERVERPATH;
		if ($this->objectsThumb != NULL) {
			$imgfile = getAlbumFolder().$this->album->name.'/'.$this->objectsThumb;
		} else {
			$suffix = getSuffix($this->filename);
			switch($suffix) {
				case "mp3":
					$img = '/mp3Default.png';
					break;
				case "mp4":
					$img = '/mp4Default.png';
					break;
				case "flv":
					$img = '/flvDefault.png';
					break;
				case "mov":
					$img = '/movDefault.png';
					break;
				case "3gp":
					$img = '/3gpDefault.png';
					break;
				default: // just in case we extend and are lazy...
					$img = '/multimediaDefault.png';
					break;
			}
			$imgfile = $path . '/' . THEMEFOLDER . '/' . internalToFilesystem($this->album->gallery->getCurrentTheme()) . '/images'.$img;
			if (!file_exists($imgfile)) {  // first check if the theme has adefault image
				$imgfile = $path . '/' . THEMEFOLDER . '/' . internalToFilesystem($this->album->gallery->getCurrentTheme()) . '/images/multimediaDefault.png';
				if (!file_exists($imgfile)) { // if theme has a generic default image use it otherwise use the Zenphoto image
					$imgfile = $path . "/" . ZENFOLDER . '/'.PLUGIN_FOLDER.'/' . substr(basename(__FILE__), 0, -4).$img;
				}
			}
		}
		return $imgfile;
	}

	/**
	 * Get a default-sized thumbnail of this image.
	 *
	 * @return string
	 */
	function getThumb($type='image') {
		$wmt = getOption('Video_watermark');
		if ($this->objectsThumb == NULL) {
			$filename = makeSpecialImageName($this->getThumbImageFile());
			if (!getOption('video_watermark_default_images')) $wmt = '';
		} else {
			$filename = $this->objectsThumb;
		}
		$args = getImageParameters(array('thumb', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $wmt, NULL, NULL), $this->album->name);
		$cachefilename = getImageCacheFilename($alb = $this->album->name, $this->filename, $args);
		if (file_exists(SERVERCACHE . $cachefilename)	&& filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . '/'.CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
		} else {
			return getImageProcessorURI($args, $this->album->name, $filename); 		
		}
	}

	/**
	 *  Get a custom sized version of this image based on the parameters.
	 *
	 * @param string $alt Alt text for the url
	 * @param int $size size
	 * @param int $width width
	 * @param int $height height
	 * @param int $cropw crop width
	 * @param int $croph crop height
	 * @param int $cropx crop x axis
	 * @param int $cropy crop y axis
	 * @param string $class Optional style class
	 * @param string $id Optional style id
	 * @param bool $thumbStandin set true to inhibit watermarking
	 * @param bool $gray ignored
	 * @return string
	 */
	function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin=false, $gray=false) {
		$args = getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy, NULL, NULL, NULL, $thumbStandin, getOption('Video_watermark'), NULL, $gray), $this->album->name);
		if ($thumbStandin & 1) {
			if ($this->objectsThumb == NULL) {
				$filename = makeSpecialImageName($this->getThumbImageFile());
				return getImageProcessorURI($args, $this->album->name, $filename); 
			} else {
				$filename = $this->objectsThumb;
				$cachefilename = getImageCacheFilename($alb = $this->album->name, $filename,
														getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy, NULL, NULL, NULL, $thumbStandin, NULL, NULL)), $this->album->name);
				if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
					return WEBPATH . '/'.CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
				} else {
					return getImageProcessorURI($args, $this->album->name, $filename); 
				}
			}
		} else {
			$filename = $this->filename;
			$cachefilename = getImageCacheFilename($this->album->name, $filename,	$args);
			if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
				return WEBPATH . '/'.CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
			} else {
				return getImageProcessorURI($args, $this->album->name, $filename); 
			}
		}
	}
	
	function getBody() {
		global $_zp_flash_player;
		$w = $this->getWidth();
		$h = $this->getHeight();
		$ext = strtolower(strrchr($this->getFullImage(), "."));
		switch ($ext) {
			case '.flv':
			case '.mp3':
			case '.mp4':
				if (is_null($_zp_flash_player)) {
					return  "<img src='" . WEBPATH . '/' . ZENFOLDER . "'/images/err-noflashplayer.gif' alt='No flash player installed.' />";
				} else {
					return $_zp_flash_player->getPlayerConfig('',$this->getTitle());
				}
				break;
			case '.3gp':
			case '.mov':
				return '</a>
			 		<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="'.$w.'" height="'.$h.'" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
				 	<param name="src" value="' . $this->getFullImage() . '"/>
				 	<param name="autoplay" value="false" />
				 	<param name="type" value="video/quicktime" />
				 	<param name="controller" value="true" />
				 	<embed src="' . $this->getFullImage() . '" width="'.$w.'" height="'.$h.'" scale="aspect" autoplay="false" controller"true" type="video/quicktime"
				 		pluginspage="http://www.apple.com/quicktime/download/" cache="true"></embed>
					</object><a>';
				break;
		}		
	}
	
}
?>