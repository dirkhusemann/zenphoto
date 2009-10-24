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
 * If a sidecar file exists, it will take precidence (the image file will not be
 * examined.) The sidecar file should have the same prefix name as the image (album) and the 
 * suffix ".xmp". Thus, the sidecar for <image>.jpg would be named <image>.xmp.
 * 
 * NOTE: dynamic albums have an ".alb" suffix. Append ".xmp" to that name so
 * that the dynamic album sidecar would be named <album>.alb.xmp
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

zp_register_filter('new_album', 'xmpMetadata_new_album');
zp_register_filter('album_refresh', 'xmpMetadata_new_album');
zp_register_filter('new_image', 'xmpMetadata_new_image');
zp_register_filter('image_refresh', 'xmpMetadata_new_image');

function xmpMetadata_extract($filename) {
	$source = file_get_contents($filename);

	$xmpdata_start = strpos($source,"<x:xmpmeta");
	$xmpdata_end = strpos($source,"</x:xmpmeta>");
	$xmplenght = $xmpdata_end-$xmpdata_start;
	$xmpdata = substr($source,$xmpdata_start,$xmplenght+12);
	$xmp_parsed = array();

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
						$elements[] = substr($meta, 0, $e);
						$meta = substr($meta,$e+9);
					}
				}
				$xmp_parsed[$key] = $elements;
			}
		}
	}
	return ($xmp_parsed);
}

function xmpMetadata_to_string($meta) {
	if (is_array($meta)) {
		$meta = implode(',',$meta);
	}
	return $meta;
}

function xmpMetadata_new_album($album) {
	$metadata_path = $album->localpath.'.xmp';
	if (file_exists($metadata_path)) {
		$metadata = xmpMetadata_extract($metadata_path);
		if (array_key_exists('EXIFDescription',$metadata)) {
			$album->setDesc(xmpMetadata_to_string($metadata['EXIFDescription']['value']));
		}
		if (array_key_exists('IPTCImageHeadline',$metadata)) {
			$album->setTitle(xmpMetadata_to_string($metadata['IPTCImageHeadline']['value']));
		}
		if (array_key_exists('IPTCLocationName',$metadata)) {
			$album->setPlace(xmpMetadata_to_string($metadata['IPTCLocationName']['value']));
		}
		if (array_key_exists('IPTCKeywords',$metadata)) {
			$album->setTags(xmpMetadata_to_string($metadata['IPTCKeywords']['value']));
		}
	}
	$album->save();
	return $album;
}

function xmpMetadata_new_image($image) {
	global $_zp_exifvars;
	$metadata_path = substr($image->localpath, 0, strrpos($image->localpath, '.')).'.xmp';
	if (!file_exists($metadata_path)) {
		$metadata_path = $image->localpath; // no sidecar, maybe there is xmp metadate in the image file itself
	}
	$metadata = xmpMetadata_extract($metadata_path);
	foreach ($metadata as $field=>$element) {
		switch ($field) {
			case 'EXIFDateTimeOriginal':
				$image->setDateTime($element);
				break;
			case 'IPTCImageCaption':
				$image->setDesc($v = xmpMetadata_to_string($element));
				break;
			case 'IPTCCity':
				$image->setCity($v = $element);
				break;
			case 'IPTCState':
				$image->setState($v = $element);
				break;
			case 'IPTCLocationName':
				$image->setCountry($v = $element);
				break;
			case 'IPTCCopyright':
				$image->setCopyright($v = xmpMetadata_to_string($element));
				break;
			case 'IPTCSource':
				$image->setCredit($v = xmpMetadata_to_string($element));
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
			default:
				$v = xmpMetadata_to_string($element);
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
	return $image;
}

?>