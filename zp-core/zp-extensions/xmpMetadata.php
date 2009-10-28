<?php
/*
 * xmpMetadata
 * 
 * Enable this filter to scan images (or xmp sidecar files) for metadata.
 * 
 * Relevant metadata found will be incorporated into the image (or album object)
 * see “IPTC Core” Schema for XMP http://xml.coverpages.org/IPTC-CoreSchema200503-XMPSchema8.pdf
 * for xmp metadata description. This plugin attempts to map the xmp metadata to IPTC fields
 * 
 * If a sidecar file exists, it will take precedence (the image file will not be
 * examined.) The sidecar file should reside in the same folder, have the same prefix name as the
 * image (album), and the suffix ".xmp". Thus, the sidecar for <image>.jpg would be named <image>.xmp.
 * 
 * NOTE: dynamic albums have an ".alb" suffix. Append ".xmp" to that name so
 * that the dynamic album sidecar would be named <album>.alb.xmp
 * 
 * There is one option for this plugin--to enable searching within the actual image file for
 * an xmp block. This is disabled by default scanning image files can add considerably to the
 * processing time.
 * 
 * All functions within this plugin are for internal use. The plugin does not present any 
 * theme interface.
 * 
 * @author Stephen Billard (sbillard)
 * @package plugins
 */

$plugin_is_filter = 9;
$plugin_description = gettext('Extracts EXIF metadata from images and xmp sidecar files.');
$plugin_author = "Stephen Billard (sbillard)";
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---xmpMetadata.html";
$plugin_version = '1.2.7';
$option_interface = new xmpMetadata_options();

zp_register_filter('new_album', 'xmpMetadata_new_album');
zp_register_filter('album_refresh', 'xmpMetadata_new_album');
zp_register_filter('new_image', 'xmpMetadata_new_image');
zp_register_filter('image_refresh', 'xmpMetadata_new_image');

/**
 * Plugin option handling class
 *
 */
class xmpMetadata_options {

	/**
	 * Class instantiation function
	 *
	 * @return xmpMetadata_options
	 */
	function xmpMetadata_options() {
	}

	/**
	 * Option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		global $_zp_supported_images, $_zp_extra_filetypes;
		$list = $_zp_supported_images;
		foreach (array('gif','bmp') as $suffix) {
			$key = array_search($suffix, $list);
			if ($key !== false)	unset($list[$key]);
		}
		natcasesort($list);
		$types = array();
		foreach ($_zp_extra_filetypes as $suffix=>$type) {
			if ($type == 'Video') $types[] = $suffix;
		}
		natcasesort($types);
		$list = array_merge($list, $types);
		$listi = array();
		foreach ($list as $suffix) {
			$listi[$suffix] = 'xmpMetadata_examine_images_'.$suffix;
		}
		return array(	gettext('Process extensions.') => array('key' => 'xmpMetadata_examine_imagefile', 'type' => OPTION_TYPE_CHECKBOX_UL,
										'checkboxes' => $listi,
										'desc' => gettext('If no sidecar file exists and the extension is enabled, the plugin will search within that type <em>image</em> file for an <code>xmp</code> block. <strong>Warning</strong> do not set this option unless you require it. Searching image files can be computationally intensive.'))
		);
	}
	
	/**
	 * Custom option handler
	 *
	 * @param string $option
	 * @param mixed $currentValue
	 */
	function handleOption($option, $currentValue) {
	}
}

/**
 * Parses xmp metadata for interesting tags
 *
 * @param string $xmpdata
 * @return array
 */
function xmpMetadata_extract($xmpdata) {
	$desiredtags = array(
		'EXIFLensInfo'					=>	'<aux:Lens>',
		'EXIFArtist'						=>	'<dc:creator>',
		'IPTCCopyright'					=>	'<dc:rights>',
		'EXIFDescription'				=>	'<dc:description>',
		'IPTCObjectName'				=>	'<dc:title>',
		'IPTCKeywords'  				=>	'<dc:subject>',
		'EXIFExposureTime'			=>	'<exif:ExposureTime>',
		'EXIFFNumber'						=>	'<exif:FNumber>',
		'EXIFAperatureValue'		=>	'<exif:ApertureValue>',
		'EXIFExposureProgram'		=>	'<exif:ExposureProgram>',
		'EXIFISOSpeedRatings'		=>	'<exif:ISOSpeedRatings>',
		'EXIFDateTimeOriginal'	=>	'<exif:DateTimeOriginal>',
		'EXIFExposureBiasValue'	=>	'<exif:ExposureBiasValue>',
		'EXIFMeteringMode'			=>	'<exif:MeteringMode>',
		'EXIFFocalLength'				=>	'<exif:FocalLength>',
		'EXIFContrast'					=>	'<exif:Contrast>',
		'EXIFSharpness'					=>	'<exif:Sharpness>',
		'EXIFSaturation'				=>	'<exif:Saturation>',
		'EXIFWhiteBalance'			=>	'<exif:WhiteBalance>',
		'IPTCLocationCode' 			=>	'<Iptc4xmpCore:CountryCode>',
		'IPTCSubLocation' 			=>	'<Iptc4xmpCore:Location>',
		'IPTCSource'						=>	'<photoshop:Source>',
		'IPTCCity' 							=>	'<photoshop:City>',
		'IPTCState' 						=>	'<photoshop:State>',
		'IPTCLocationName' 			=>	'<photoshop:Country>',
		'IPTCImageHeadline'  		=>	'<photoshop:Headline>',
		'IPTCImageCredit' 			=>	'<photoshop:Credit>',
		'EXIFMake'							=>	'<tiff:Make>',
		'EXIFModel'							=>	'<tiff:Model>',
		'EXIFOrientation'				=>	'<tiff:Orientation>',
		'EXIFImageWidth'				=>	'<tiff:ImageWidth>',
		'EXIFImageHeight'				=>	'<tiff:ImageLength>'
	);
	$xmp_parsed = array();
	while (!empty($xmpdata)) {
		$s = strpos($xmpdata, '<');
		$e = strpos($xmpdata,'>',$s);
		$tag = substr($xmpdata,$s,$e-$s+1);
		$xmpdata = substr($xmpdata,$e+1);
		$key = array_search($tag,$desiredtags);
		if ($key !== false) {
			$close = str_replace('<','</',$tag);
			$e = strpos($xmpdata,$close);
			$meta = trim(substr($xmpdata,0,$e));
			$xmpdata = substr($xmpdata,$e+strlen($close));
			if (strpos($meta, '<') === false) {
				$xmp_parsed[$key] = $meta;
			} else {
				$elements = array();
				while (!empty($meta)) {
					$s = strpos($meta, '<');
					$e = strpos($meta,'>',$s);
					$tag = substr($meta,$s,$e-$s+1);
					$meta = substr($meta,$e+1);
					if (strpos($tag,'rdf:li') !== false) {
						$e = strpos($meta,'</rdf:li>');
						$elements[] = trim(substr($meta, 0, $e));
						$meta = substr($meta,$e+9);
					}
				}
				$xmp_parsed[$key] = $elements;
			}
		}
	}
	return ($xmp_parsed);
}

/**
 * insures that the metadata is a string
 *
 * @param mixed $meta
 * @return string
 */
function xmpMetadata_to_string($meta) {
	if (is_array($meta)) {
		$meta = implode(',',$meta);
	}
	return trim($meta);
}

/**
 * Filter for handling album objects
 *
 * @param object $album
 * @return object
 */
function xmpMetadata_new_album($album) {
	$metadata_path = dirname($album->localpath).'/'.basename($album->localpath).'.xmp';
	if (file_exists($metadata_path)) {
		$source = file_get_contents($metadata_path);
		$metadata = xmpMetadata_extract($source);
		if (array_key_exists('EXIFDescription',$metadata)) {
			$album->setDesc(xmpMetadata_to_string($metadata['EXIFDescription']));
		}
		if (array_key_exists('IPTCImageHeadline',$metadata)) {
			$album->setTitle(xmpMetadata_to_string($metadata['IPTCImageHeadline']));
		}
		if (array_key_exists('IPTCLocationName',$metadata)) {
			$album->setPlace(xmpMetadata_to_string($metadata['IPTCLocationName']));
		}
		if (array_key_exists('IPTCKeywords',$metadata)) {
			$album->setTags(xmpMetadata_to_string($metadata['IPTCKeywords']));
		}
		if (array_key_exists('EXIFDateTimeOriginal',$metadata)) {
			$album->setDateTime($metadata['EXIFDateTimeOriginal']);
		}
	}
	$album->save();
	return $album;
}

/**
 * Finds and returns xmp metadata
 *
 * @param int $j
 * @return string
 */
function extractXMP($f) {
	if (preg_match('~<.*?xmpmeta~',$f, $m)) {
		$open = $m[0];
		$close = str_replace('<','</',$open);
		$j = strpos($f, $open);
		if ($j !== false) {
			$k = strpos($f, $close,$j+4);
			$meta = substr($f, $j, $k+14-$j);
			$l = 0;
			return $meta;
		}
	}
	return false;
}

/**
 * Filter for handling image objects
 *
 * @param object $image
 * @return object
 */
function xmpMetadata_new_image($image) {	
	global $_zp_exifvars;
	$source = '';
	$metadata_path = substr($image->localpath, 0, strrpos($image->localpath, '.')).'.xmp';
	if (file_exists($metadata_path)) {
		$source = extractXMP(file_get_contents($metadata_path));		
	} else if (getOption('xmpMetadata_examine_images_'.strtolower(substr(strrchr($image->localpath, "."), 1)))) {
		$f = file_get_contents($image->localpath);
		$l = filesize($image->localpath);
		$abort = 0;
		$i = 0;
		while ($i<$l && $abort<200 && !$source) {
			$tag = bin2hex(substr($f,$i,2));
			$size = hexdec(bin2hex(substr($f,$i+2,2)));
			switch ($tag) {
				case 'ffe1': // EXIF
				case 'ffe2': // EXIF extension
				case 'fffe': // COM
				case 'ffe0': // IPTC marker
					$source = extractXMP($f);
					$i = $i + $size+2;
					$abort = 0;
					break;
				default:
					if (substr($f,$i,1)=='<') {
						$source = extractXMP($f);
					}
					$i=$i+1;
					$abort++;
					break;
			}
		}
	}
	if (!empty($source)) {
		$metadata = xmpMetadata_extract($source);
		foreach ($metadata as $field=>$element) {
			$v = xmpMetadata_to_string($element); 
			switch ($field) {
				case 'EXIFDateTimeOriginal':
					$image->setDateTime($element);
					break;
				case 'IPTCImageCaption':
					$image->setDesc($v);
					break;
				case 'IPTCCity':
					$image->setCity($v);
					break;
				case 'IPTCState':
					$image->setState($v);
					break;
				case 'IPTCLocationName':
					$image->setCountry($v);
					break;
				case 'IPTCSubLocation':
					$image->setLocation($v);
					break;
					case 'EXIFAperatureValue':
					$v = 'f'.$element;
					break;
				case 'EXIFExposureTime':
					$v = sprintf(gettext('%s sec'),$element);
					break;
				case 'EXIFFocalLength':
				case 'EXIFFNumber':
				case 'EXIFExposureBiasValue':
					// deal with the fractional representation
					$n = explode('/',$element);
					$v = sprintf('%f', $n[0]/$n[1]);
					for ($i=strlen($v)-1;$i>1;$i--) {
						if (substr($v,$i,1) != '0') break;
					}
					if (substr($v,$i,1)=='.') $i--;
					$v = substr($v,0,$i+1);
					break;
				case 'IPTCKeywords':
					$image->setTags($element);
					break;
			}
			if (array_key_exists($field,$_zp_exifvars)) {
				$image->set($field, $v);
			}
		}
		/* iptc title */
		$title = $image->get('IPTCObjectName');
		if (empty($title)) {
			$title = $image->get('IPTCImageHeadline');
		}
		//EXIF title [sic]
		if (empty($title)) {
			$title = $image->get('EXIFImageDescription');
		}
		if (!empty($title)) {
			$image->setTitle($title);
		}
		/* iptc credit */
		$credit = $image->get('IPTCImageCredit');
		if (empty($credit)) {
			$credit = $image->get('IPTCSource');
		}
		$image->setCredit($credit);

		$image->save();
	}
	return $image;
}

?>