<?php

/**
 * Library for image handling using the Imagick library of functions
 *
 * @internal Imagick::setResourceLimit() causes a PHP crash if called statically (fixed in Imagick 3.0.0RC1).
 *		Imagick::getResourceLimit(), Imagick::getVersion(), Imagick::queryFonts(), and Imagick::queryFormats()
 *		might also cause PHP to crash as well, though they can be called statically.
 *
 * @package core
 *
 * @todo Perhaps add option for user to choose Imagick::FILTER_* and Imagick::COMPOSITE_*?
 *		Support interlaced image output
 *		Watermarking animations needs to keep the watermark at the same position in each frame, regardless of frame size
 *		The default ZP image is erroneously watermarked
 *		Perhaps change luminosity of watermark depending on colors used?
 *		Add ability to use text watermarks inherently from Imagick
 */

// force UTF-8 Ø

/**
 * Requires Imagick 2.1.0+ (Imagick 2.0.0+ requires PHP5)
 * Imagick 2.3.0b1+ and ImageMagick 6.3.8+ suggested to avoid deprecated functions
 */
$_imagick_loaded = extension_loaded('imagick');

$_imagick_version = phpversion('imagick');
$_imagick_required_version = '2.1.0';
$_imagick_version_pass = version_compare($_imagick_version, $_imagick_required_version, '>=');

$_zp_imagick_present = version_compare(phpversion(), '5', '>=') && $_imagick_loaded && $_imagick_version_pass;

$_zp_graphics_optionhandlers[] = new lib_Imagick_Options();

/**
 * Option class for lib-Imagick
 */
class lib_Imagick_Options {

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		global $_zp_imagick_present;

		if ($_zp_imagick_present) {
			if (!sanitize_numeric(getOption('magick_font_size'))) {
				setOption('magick_font_size', 18);
			}

			$mem_lim = getOption('magick_mem_lim');
			if (!is_numeric($mem_lim) || $mem_lim < 0 ) {
				setOption('magick_mem_lim', 0);
			}

			return array(	gettext('Enable Imagick') =>		array(	'key' => 'use_Imagick',
																		'type' => OPTION_TYPE_CHECKBOX,
																		'order' => 0,
																		'desc' => gettext('Your PHP has support for Imagick. Check this option if you wish to use the Imagick graphics library.')),
							gettext('Imagick memory limit') =>	array(	'key' => 'magick_mem_lim',
																		'type' => OPTION_TYPE_TEXTBOX,
																		'order' => 1,		
																		'desc' => '<p>' . gettext('Amount of memory allocated to Imagick in megabytes. Set to <strong>0</strong> for unlimited memory.') . '</p><p class="notebox">' . gettext('<strong>Note:</strong> Image processing will be faster with a higher memory limit. However, if your server experiences problems with image processing, try setting this lower.') . '</p>'),
							gettext('CAPTCHA font size') =>		array(	'key' => 'magick_font_size',
																		'type' => OPTION_TYPE_TEXTBOX,
																		'order' => 2,
																		'desc' => gettext('The font size (in pixels) for CAPTCHAs. Default is <strong>18</strong>.'))
			);
		}

		return array();
	}

	function canLoadMsg() {
		global $_imagick_loaded, $_imagick_version_pass, $_imagick_required_version;

		if ($_imagick_loaded) {
			if (!$_imagick_version_pass) {
				return sprintf(gettext('The <strong><em>Imagick</em></strong> library version must be <strong>%s</strong> or later.'), $_imagick_required_version);
			}
		} else {
			return gettext('The <strong><em>Imagick</em></strong> extension is not available.');
		}

		return '';
	}
}

/**
 * Zenphoto image manipulation functions using the Imagick library
 */
if ($_zp_imagick_present && (getOption('use_Imagick') || !extension_loaded('gd'))) { // only define the functions if we have the proper versions
	$temp = new Imagick();

	$mem_lim = getOption('magick_mem_lim');
	if ($mem_lim > 0) {
		$temp->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, $mem_lim);
	} else {
		$temp->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, $temp->getResourceLimit(Imagick::RESOURCETYPE_MEMORY));
	}

	$_imagemagick_version = $temp->getVersion();

	$_lib_Imagick_info = array();
	$_lib_Imagick_info['Library'] = sprintf(gettext('PHP Imagick library <em>%s</em><br /><em>%s</em>'), $_imagick_version, $_imagemagick_version['versionString']);

	$_use_imagick_deprecated = version_compare($_imagick_version, '2.3.0b1', '<') && version_compare($_imagemagick_version['versionString'], '6.3.8', '<');
	$_use_merge_grayscale = version_compare($_imagemagick_version['versionNumber'], '6.3.1', '<');
	$_use_corrected_colorspace = version_compare($_imagemagick_version['versionNumber'], '6.3.2', '>=');
	$_use_preserved_profiles = version_compare($_imagemagick_version['versionNumber'], '6.3.6', '>=');

	$format_blacklist = array(
		// video formats
		'AVI', 'M2V', 'M4V', 'MOV', 'MP4', 'MPEG', 'MPG', 'WMV',
		// text formats
		'HTM', 'HTML', 'MAN', 'PDF', 'SHTML', 'TEXT', 'TXT', 'UBRL',
		// font formats
		'DFONT', 'OTF', 'PFA', 'PFB', 'TTC', 'TTF',
		// GhostScript formats; 'MAN' and 'PDF' also require this
		'EPI', 'EPS', 'EPS2', 'EPS3', 'EPSF', 'EPSI', 'EPT', 'EPT2', 'EPT3', 'PS', 'PS2', 'PS3',
		// other formats with lib dependencies, so possibly no decode delegate
		'CGM', 'EMF', 'FIG', 'FPX', 'GPLT', 'HPGL', 'JBIG', 'RAD', 'WMF', 'WMZ',
		// just to be sure...
		'ZIP'
	);

	$formats = array_diff($temp->queryFormats(), $format_blacklist);
	$imgtypes = array_combine(array_map('strtoupper', $formats), array_map('strtolower', $formats));
	$_lib_Imagick_info += $imgtypes;

	$temp->destroy();
	unset($mem_lim);
	unset($_imagemagick_version);
	unset($format_blacklist);
	unset($formats);
	unset($imgtypes);

	if (DEBUG_IMAGE) {
		debugLog('Loading ' . $_lib_Imagick_info['Library']);
	}

	/**
	 * Takes an image filename and returns an Imagick image object
	 *
	 * @param string $imagefile the full path and filename of the image to load
	 * @return Imagick
	 */
	function zp_imageGet($imgfile) {
		global $_lib_Imagick_info;

		$ext = getSuffix($imgfile);
		if (function_exists('memory_get_usage')) {
			memory_get_usage(); // force PHP garbage collection if possible
		}
		if (in_array($ext, $_lib_Imagick_info)) {
			return new Imagick($imgfile);
		}

		return false;
	}

	/**
	 * Outputs an image resource as a given type
	 *
	 * @param Imagick $im
	 * @param string $type
	 * @param string $filename
	 * @param int $qual
	 * @return bool
	 */
	function zp_imageOutput($im, $type, $filename = NULL, $qual = 75) {
		$im->setImageFormat($type);
		$qual = max(min($qual, 100), 0);

		switch ($type) {
			case 'png':
				$im->setCompression(Imagick::COMPRESSION_ZIP);
				$im->setCompressionQuality($qual);
				break;
			case 'jpeg': case 'jpg':
				$im->setCompression(Imagick::COMPRESSION_JPEG);
				$im->setCompressionQuality($qual);
				break;
			case 'gif':
				try {
					$im->optimizeImageLayers();
				} catch (ImagickException $e) {
					if (DEBUG_IMAGE) {
						debugLog('Caught ImagickException in zp_imageOutput(): ' . $e->getMessage());
					}
				}
				break;
		}

		if ($filename == NULL) {
			header('Content-Type: image/' . $type);
			return print $im->getImagesBlob();
		}

		return $im->writeImages($filename, true);
	}

	/**
	 * Creates a true color image
	 *
	 * @param int $w the width of the image
	 * @param int $h the height of the image
	 * @return Imagick
	 */
	function zp_createImage($w, $h) {
		$im = new Imagick();
		$im->newImage($w, $h, 'none');
		$im->setImageType(Imagick::IMGTYPE_TRUECOLORMATTE);

		return $im;
	}

	/**
	 * Fills an image area
	 *
	 * @internal Imagick::floodFillPaintImage() requires Imagick 2.3.0b1+ compiled against ImageMagick 6.3.8+
	 *
	 * @param Imagick $image
	 * @param int $x
	 * @param int $y
	 * @param color $color
	 * @return bool
	 */
	function zp_imageFill($image, $x, $y, $color) {
		global $_use_imagick_deprecated;

		if ($_use_imagick_deprecated) {
			return $image->paintFloodfillImage($color, 1, $color, $x, $y);
		}

		$target = $image->getImagePixelColor($x, $y);
		return $image->floodFillPaintImage($color, 1, $target, $x, $y);
	}

	/**
	 * Sets the transparency color
	 *
	 * @internal Imagick::transparentPaintImage() requires Imagick 2.3.0b1+ compiled against ImageMagick 6.3.8+
	 *
	 * @param Imagick $image
	 * @param color $color
	 * @return bool
	 */
	function zp_imageColorTransparent($image, $color)  {
		global $_use_imagick_deprecated;

		if ($_use_imagick_deprecated) {
			return $image->paintTransparentImage($color, 0.0, 1);
		}

		return $image->transparentPaintImage($color, 0.0, 1, false);
	}

	/**
	 * Copies an image canvas
	 *
	 * @param Imagick $imgCanvas destination canvas
	 * @param Imagick $img source canvas
	 * @param int $dest_x destination x
	 * @param int $dest_y destination y
	 * @param int $src_x source x
	 * @param int $src_y source y
	 * @param int $w width
	 * @param int $h height
	 * @return bool
	 */
	function zp_copyCanvas($imgCanvas, $img, $dest_x, $dest_y, $src_x, $src_y, $w, $h) {
		$img->cropImage($w, $h, $src_x, $src_y);
		$result = true;

		for ($x = 0; $result && $x <= $imgCanvas->getNumberImages(); $x++) {
			$result = $imgCanvas->compositeImage($img, Imagick::COMPOSITE_OVER, $dest_x, $dest_y);
			$imgCanvas->previousImage();
		}

		return $result;
	}

	/**
	 * Resamples an image to a new copy
	 *
	 * @internal Imagick::getImageProfiles() requires Imagick compiled against ImageMagick 6.3.6+
	 *
	 * @param Imagick $dst_image
	 * @param Imagick $src_image
	 * @param int $dst_x
	 * @param int $dst_y
	 * @param int $src_x
	 * @param int $src_y
	 * @param int $dst_w
	 * @param int $dst_h
	 * @param int $src_w
	 * @param int $src_h
	 * @param string $suffix
	 * @return bool
	 */
	function zp_resampleImage($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $suffix) {
		global $_use_preserved_profiles;

		if ($_use_preserved_profiles) {
			foreach($src_image->getImageProfiles() as $name => $profile) {
				$dst_image->profileImage($name, $profile);
			}
		}

		$src_image->cropImage($src_w, $src_h, $src_x, $src_y);
		$src_image->resizeImage($dst_w, $dst_h, Imagick::FILTER_LANCZOS, 1);
		return $dst_image->compositeImage($src_image, Imagick::COMPOSITE_OVER, $dst_x, $dst_y);
	}

	/**
	 * Sharpens an image using an Unsharp Mask filter.
	 *
	 * @param Imagick $img the image to sharpen
	 * @param int $amount the strength of the sharpening effect
	 * @param int $radius the pixel radius of the sharpening mask
	 * @param int $threshold the color difference threshold required for sharpening
	 * @return Imagick
	 */
	function zp_imageUnsharpMask($img, $amount, $radius, $threshold) {
		$img->unsharpMaskImage($radius, 0.1, $amount, $threshold);
		return $img;
	}

	/**
	 * Resize a file with transparency to given dimensions and still retain the alpha channel information
	 *
	 * @param Imagick $src
	 * @param int $w
	 * @param int $h
	 * @return Imagick
	 */
	function zp_imageResizeAlpha($src, $w, $h) {
		$src->scaleImage($w, $h);
		return $src;
	}

	/**
	 * Returns true if Imagick library is configued with image rotation support
	 *
	 * @return bool
	 */
	function zp_imageCanRotate() {
		global $_imagick_can_rotate;

		if (!isset($_imagick_can_rotate)) {
			$_imagick_can_rotate = in_array('rotateimage', array_map('strtolower', get_class_methods('Imagick')));
		}

		return $_imagick_can_rotate;
	}

	/**
	 * Rotates an image resource according to its EXIF info and auto_rotate option
	 *
	 * @param Imagick $im
	 * @param int $rotate
	 * @return Imagick
	 */
	function zp_rotateImage($im, $rotate) {
		$im->rotateImage('none', 360 - $rotate); // GD rotates CCW, Imagick rotates CW
		return $im;
	}

	/**
	 * Returns the image height and width
	 *
	 * @param string $filename
	 * @param array $imageinfo
	 * @return array
	 */
	function zp_imageDims($filename) {
		$ping = new Imagick();

		if ($ping->pingImage($filename)) {
			return array('width' => $ping->getImageWidth(), 'height' => $ping->getImageHeight());
		}

		return false;
	}

	/**
	 * Returns the IPTC data of an image
	 *
	 * @param string $filename
	 * @throws ImagickException
	 * @return string
	 */
	function zp_imageIPTC($filename) {
		$ping = new Imagick();

		if ($ping->pingImage($filename)) {
			try {
				return $ping->getImageProfile('exif');
			} catch (ImagickException $e) {
				if (DEBUG_IMAGE) {
					debugLog('Caught ImagickException in zp_imageIPTC(): ' . $e->getMessage());
				}
			}
		}

		return false;
	}

	/**
	 * Returns the width of an image resource
	 *
	 * @param Imagick $im
	 * @return int
	 */
	function zp_imageWidth($im) {
		return $im->getImageWidth();
	}

	/**
	 * Returns the height of an image resource
	 *
	 * @param Imagick $im
	 * @return int
	 */
	function zp_imageHeight($im) {
		return $im->getImageHeight();
	}

	/**
	 * Does a copy merge of two image resources
	 *
	 * @internal Imagick::setImageOpacity() requires Imagick compiled against ImageMagick 6.3.1+
	 * @internal Imagick::getImageProperty() requires Imagick compiled against ImageMagick 6.3.2+
	 * @internal Imagick::setImageProperty() requires Imagick compiled against ImageMagick 6.3.2+
	 *
	 * @param Imagick $dst_im
	 * @param Imagick $src_im
	 * @param int $dst_x
	 * @param int $dst_y
	 * @param int $src_x
	 * @param int $src_y
	 * @param int $src_w
	 * @param int $src_h
	 * @param int $pct
	 * @return bool
	 */
	function zp_imageMerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
		global $_use_merge_grayscale, $_use_corrected_colorspace;

		$src_im->cropImage($w, $h, $src_x, $src_y);

		if ($_use_merge_grayscale) {
			$src_im->setImageType(Imagick::IMGTYPE_GRAYSCALE);

			// assumes that exif:ColorSpace is not set to an undefined colorspace
			if ($_use_corrected_colorspace && $dst_im->getImageProperty('exif:ColorSpace')) {
				$dst_im->setImageProperty('exif:ColorSpace', Imagick::IMGTYPE_GRAYSCALE);
			}
		} else {
			$src_im->setImageOpacity($pct / 100);
		}

		return $dst_im->compositeImage($src_im, Imagick::COMPOSITE_OVER, $dest_x, $dest_y);
	}

	/**
	 * Creates a grayscale image
	 *
	 * @internal Imagick::getImageProperty() requires Imagick compiled against ImageMagick 6.3.2+
	 * @internal Imagick::setImageProperty() requires Imagick compiled against ImageMagick 6.3.2+
	 *
	 * @param Imagick $image
	 * @return Imagick
	 */
	function zp_imageGray($image) {
		global $_use_corrected_colorspace;

		$image->setImageType(Imagick::IMGTYPE_GRAYSCALE);

		// assumes that exif:ColorSpace is not set to an undefined colorspace
		if ($_use_corrected_colorspace && $image->getImageProperty('exif:ColorSpace')) {
			$image->setImageProperty('exif:ColorSpace', Imagick::IMGTYPE_GRAYSCALE);
		}

		return $image;
	}

	/**
	 * Destroys an image resource
	 *
	 * @param Imagick $im
	 * @return bool
	 */
	function zp_imageKill($im) {
		return $im->destroy();
	}

	/**
	 * Returns an RGB color identifier
	 *
	 * @param Imagick $image
	 * @param int $red
	 * @param int $green
	 * @param int $blue
	 * @return ImagickPixel
	 */
	function zp_colorAllocate($image, $red, $green, $blue) {
		return new ImagickPixel("rgb($red, $green, $blue)");
	}

	/**
	 * Renders a string into the image
	 *
	 * @param Imagick $image
	 * @param ImagickDraw $font
	 * @param int $x
	 * @param int $y
	 * @param string $string
	 * @param ImagickPixel $color
	 * @return bool
	 */
	function zp_writeString($image, $font, $x, $y, $string, $color) {
		$font->setStrokeColor($color);
		return $image->annotateImage($font, $x, $y + $image->getImageHeight() / 2, 0, $string);
	}

	/**
	 * Creates a rectangle
	 *
	 * @param Imagick $image
	 * @param int $x1
	 * @param int $y1
	 * @param int $x2
	 * @param int $y2
	 * @param ImagickPixel $color
	 * @return bool
	 */
	function zp_drawRectangle($image, $x1, $y1, $x2, $y2, $color) {
		return $image->borderImage($color, 1, 1);
	}

	/**
	 * Returns array of graphics library info
	 *
	 * @return array
	 */
	function zp_graphicsLibInfo() {
		global $_lib_Imagick_info;
		return $_lib_Imagick_info;
	}

	/**
	 * Returns a list of available fonts
	 *
	 * @return array
	 */
	function zp_getFonts() {
		global $_imagick_fontlist;

		if (!is_array($_imagick_fontlist)) {
			$temp = new Imagick();
			$_imagick_fontlist = $temp->queryFonts();
			$temp->destroy();

			$_imagick_fontlist = array('system' => '') + array_combine($_imagick_fontlist, $_imagick_fontlist);

			$basefile = SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/imagick_fonts/';
			if (is_dir($basefile)) {
				chdir($basefile);
				$filelist = safe_glob('*.ttf');
				foreach($filelist as $file) {
					$key = filesystemToInternal(str_replace('.ttf', '', $file));
					$_imagick_fontlist[$key] = getcwd() . '/' . $file;
				}
			}

/** If Zenphoto distributes its own fonts, uncomment
 *			chdir($basefile = SERVERPATH . '/' . ZENFOLDER . '/imagick_fonts/');
 *			$filelist = safe_glob('*.ttf');
 *			foreach($filelist as $file) {
 *				$key = filesystemToInternal(str_replace('.ttf', '', $file));
 *				$_imagick_fontlist[$key] = getcwd() . '/' . $file;
 *			}
 */
			chdir(dirname(__FILE__));
		}

		return $_imagick_fontlist;
	}

	/**
	 * Loads a font and returns an object with the font loaded
	 *
	 * @param string $font
	 * @return ImagickDraw
	 */
	function zp_imageLoadFont($font = NULL) {
		$draw = new ImagickDraw();

		if (!empty($font)) {
			try {
				$draw->setFont($font);
			} catch(ImagickDrawException $e) {
				if (DEBUG_IMAGE) {
					debugLog('Caught ImagickDrawException in zp_imageLoadFont(): ' . $e->getMessage());
				}
			}
		}

		$draw->setFontSize(getOption('magick_font_size'));
		return $draw;
	}

	/**
	 * Returns the font width in pixels
	 *
	 * @param ImagickDraw $font
	 * @return int
	 */
	function zp_imageFontWidth($font) {
		$temp = new Imagick();
		$metrics = $temp->queryFontMetrics($font, "The quick brown fox jumps over the lazy dog");
		$temp->destroy();

		return $metrics['characterWidth'];
	}

	/**
	 * Returns the font height in pixels
	 *
	 * @param ImagickDraw $font
	 * @return int
	 */
	function zp_imageFontHeight($font) {
		$temp = new Imagick();
		$metrics = $temp->queryFontMetrics($font, "The quick brown fox jumps over the lazy dog");
		$temp->destroy();

		return $metrics['characterHeight'];
	}
}

?>
