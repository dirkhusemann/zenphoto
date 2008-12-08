<?php
/**
 *Video Class
 * @package classes
 */

// force UTF-8 Ø


class Video extends Image {

	/**
	 * Constructor for class-image
	 *
	 * @param object &$album the owning album
	 * @param sting $filename the filename of the image
	 * @return Image
	 */
	function Video(&$album, $filename) {
		// $album is an Album object; it should already be created.
		if (!is_object($album)) return NULL;
		$this->classSetup($album, $filename);
		$this->video = true;
		$this->objectsThumb = checkObjectsThumb($album->localpath, $filename);
		// Check if the file exists.
		if (!file_exists($this->localpath) || is_dir($this->localpath)) {
			$this->exists = false;
			return NULL;
		}


		// This is where the magic happens...
		$album_name = $album->name;
		if ( parent::PersistentObject('images', array('filename'=>$filename, 'albumid'=>$this->album->id), 'filename', false, empty($album_name))) {
			$this->updateDimensions();
			$newDate = strftime('%Y/%m/%d %T', filemtime($this->localpath));
			$this->set('date', $newDate);
			$alb = $this->album;
			if (!is_null($alb)) {
				if (is_null($alb->getDateTime()) || getOption('album_use_new_image_date')) {
					$this->album->setDateTime($newDate);   //  not necessarily the right one, but will do. Can be changed in Admin
					$this->album->save();
				}
			}

			$title = $this->getDefaultTitle();
			$this->set('title', sanitize($title, 2));
			$this->set('mtime', filemtime($this->localpath));
			$this->save();
		}
	}

	/**
	 * Update this object's values for width and height. Uses lazy evaluation.
	 *
	 */
	function updateDimensions() {
		$this->set('width', 320);
		$this->set('height', 240);
	}

	/**
	 * Get a default-sized thumbnail of this image.
	 *
	 * @return string
	 */
	function getThumb($type='image') {
		$filename = $this->filename;
		$wmv = '';
		if ($this->objectsThumb != NULL) {
			$filename = $this->objectsThumb;
			$wmv = '&wmv='.getOption('perform_video_watermark');
		}
		$cachefilename = getImageCacheFilename($alb = $this->album->name, $filename, getImageParameters(array('thumb')));
		if (file_exists(SERVERCACHE . $cachefilename)	&& filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
		} else {
			if (getOption('mod_rewrite') && empty($wmv) && !empty($alb)) {
				$path = pathurlencode($alb) . '/'.$type.'/thumb/' . urlencode($filename);
			} else {
				$path = ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($filename) . '&s=thumb'.$wmv;
				if ($type !== 'image') $path .= '&'.$type.'=true';
			}
			if (substr($path, 0, 1) == "/") $path = substr($path, 1);
			return WEBPATH . "/" . $path;
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
	 * @return string
	 */
	function getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, $thumbStandin=false) {
		$filename = $this->filename;
		$wmv = '';
		if (($this->objectsThumb != NULL)) {
			$filename = $this->objectsThumb;
			if ($thumbStandin & 1) {
				$wmv = '&wmv='.getOption('perform_video_watermark');
			}
		}
		$cachefilename = getImageCacheFilename($this->album->name, $filename,	getImageParameters(array($size, $width, $height, $cropw, $croph, $cropx, $cropy)));
		if (file_exists(SERVERCACHE . $cachefilename) && filemtime(SERVERCACHE . $cachefilename) > $this->filemtime) {
			return WEBPATH . substr(CACHEFOLDER, 0, -1) . pathurlencode($cachefilename);
		} else {
			return WEBPATH . '/' . ZENFOLDER . '/i.php?a=' . urlencode($this->album->name) . '&i=' . urlencode($filename)
			. ($size ? "&s=$size" : "" ) . ($width ? "&w=$width" : "") . ($height ? "&h=$height" : "")
			. ($cropw ? "&cw=$cropw" : "") . ($croph ? "&ch=$croph" : "")
			. ($cropx ? "&cx=$cropx" : "") . ($cropy ? "&cy=$cropy" : "")
			. ($thumbStandin ? "&t=true" : "").$wmv;
		}
	}

}
?>