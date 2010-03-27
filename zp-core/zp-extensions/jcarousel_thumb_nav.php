<?php
/**
 * jQuery jCarousel thumb nav plugin with dyamic loading of thumbs on request via JavaScript.
 * Place printjCarousel() on your theme's image.php where you want it to appear.
 *
 * Supports theme based custom css files (place jcarousel.css and needed images in your theme's folder).
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 */

$plugin_description = gettext("jQuery jCarousel thumb nav plugin with dyamic loading of thumbs on request via JavaScript. Place printjCarousel() on your theme's image.php where you want it to appear. Supports theme based css files (place jcarousel.css and needed images in your theme's folder).");
$plugin_author = "Malte Müller (acrylian) based on a jCarousel example";
$plugin_version = '1.3';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---jcarousel.php.html";
$option_interface = new jcarouselOptions();

/**
 * Plugin option handling class
 *
 */
class jcarouselOptions {

	function jcarouselOptions() {
		setOptionDefault('jcarousel_scroll', '3');
		setOptionDefault('jcarousel_width', '50');
		setOptionDefault('jcarousel_height', '50');
		setOptionDefault('jcarousel_croph', '50');
		setOptionDefault('jcarousel_cropw', '50');
		setOptionDefault('jcarousel_fullimagelink', '');
	}

	function getOptionsSupported() {
		return array(	gettext('Thumbs number') => array('key' => 'jcarousel_scroll', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The number of thumbs to scroll by. Note that the CSS might need to be adjusted.")),
		gettext('width') => array('key' => 'jcarousel_width', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => ""),
		gettext('height') => array('key' => 'jcarousel_height', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => ""),
		gettext('Crop width') => array('key' => 'jcarousel_cropw', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => ""),
		gettext('Crop height') => array('key' => 'jcarousel_croph', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => ""),
		gettext('Full image link') => array('key' => 'jcarousel_fullimagelink', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If checked the thumbs link to the full image instead of the imagepage."))
		);
	}

}

if (isset($_zp_current_album) && is_object($_zp_current_album) && is_object($_zp_current_image) && $_zp_current_album->getNumImages() >= 2) {
	// register the scripts needed
	$css = SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($theme) . '/jcarousel.css';
	if (file_exists($css)) {
		$css = WEBPATH . '/' . THEMEFOLDER . '/' . $theme . '/jcarousel.css';
	} else {
		$css = WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/jcarousel_thumb_nav/jcarousel.css';
	}
	addPluginScript('
<script type="text/javascript" src="'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/jcarousel_thumb_nav/jquery.jcarousel.pack.js"></script>
<link rel="stylesheet" type="text/css" href="'.WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/jcarousel_thumb_nav/jquery.jcarousel.css" />
<link rel="stylesheet" type="text/css" href="' . $css.'" />
');
}


/** Prints the jQuery jCarousel HTML setup to be replaced by JS
 *
 * @param int $thumbscroll The number of thumbs to scroll by. Note that the CSS might need to be adjusted. Set to NULL if you want to use the backend plugin options.
 * @param int $width Width Set to NULL if you want to use the backend plugin options.
 * @param int $height Height Set to NULL if you want to use the backend plugin options.
 * @param int $cropw Crop width Set to NULL if you want to use the backend plugin options.
 * @param int $croph Crop heigth Set to NULL if you want to use the backend plugin options.
 * @param bool $crop TRUE for cropped thumbs, FALSE for uncropped thumbs. $width and $height then will be used as maxspace. Set to NULL if you want to use the backend plugin options.
 * @param bool $fullimagelink Set to TRUE if you want the thumb link to link to the full image instead of the image page. Set to NULL if you want to use the backend plugin options.
 */
function printjCarouselThumbNav($thumbscroll=NULL, $width=NULL, $height=NULL,$cropw=NULL,$croph=NULL,$fullimagelink=NULL) {
global $_zp_current_album, $_zp_current_image, $_zp_current_search;
$items = "";
if(is_object($_zp_current_album) && is_object($_zp_current_image) && $_zp_current_album->getNumImages() >= 2) {
	if(is_null($thumbscroll)) {
		$thumbscroll = getOption('jcarousel_scroll');
	} else {
		$thumbscroll = sanitize_numeric($thumbscroll);
	}	
	if(is_null($width)) {
		$width = getOption('jcarousel_width');
	} else {
		$width = sanitize_numeric($width);
	}
	if(is_null($height)) {
		$height = getOption('jcarousel_height');
	} else {
		$height = sanitize_numeric($height);
	}
	if(is_null($cropw)) {
		$cropw = getOption('jcarousel_cropw');
	} else {
		$cropw = sanitize_numeric($cropw);
	}
	if(is_null($croph)) {
		$croph = getOption('jcarousel_croph');
	} else {
		$croph = sanitize_numeric($croph);
	}
	if(is_null($fullimagelink)) {
		$fullimagelink = getOption('jcarousel_fullimagelink');
	} else {
		$fullimagelink = sanitize($fullimagelink);
	}
	if(in_context(ZP_SEARCH_LINKED)) {
			if($_zp_current_search->getNumImages() === 0) {
				$searchimages = false;
			}	else {
				$searchimages = true; 
			}
		} else {
			$searchimages = false; 
		}
		if(in_context(ZP_SEARCH_LINKED) && $searchimages) {
			$jcarousel_items = $_zp_current_search->getImages();
		} else {
			$jcarousel_items =  $_zp_current_album->getImages();
		}
	if($_zp_current_album->getNumImages() >= 2 || $_zp_current_search->getNumImages() >= 2) {
		foreach($jcarousel_items as $item) {
			if(in_context(ZP_SEARCH_LINKED) && $searchimages) {
				$imgobj = newImage(new Album($_zp_gallery,$item['folder']),$item['filename']);
			} else {
				$imgobj = newImage($_zp_current_album,$item);
			}
			if($fullimagelink) {
				$link = $imgobj->getFullImage();
			} else {
				$link = $imgobj->getImageLink();
			}
			if($_zp_current_image->filename == $imgobj->filename) {
				$active = 'active';
			} else {
				$active = '';
			}
			if(isImageVideo($imgobj)) {
				$imageurl = $imgobj->getThumb();
			} else {
				$imageurl = $imgobj->getCustomImage(NULL, $width, $height, $cropw, $croph, NULL, NULL,false);
			}
			$items .= ' {url: "'.$imageurl.'", title: "'.$imgobj->getTitle().'", link: "'.$link.'", active: "'.$active.'"},';
			$items .= "\n";
		}
	}
	$items = substr($items, 0, -2);
	$numimages = getNumImages();
	if(!is_null($_zp_current_image)) {
		$imgnumber = imageNumber();
	} else {
		$imgnumber = "";
	}
	?>
	<script type="text/javascript">
var mycarousel_itemList = [
	<?php echo $items; ?>
];

function mycarousel_itemLoadCallback(carousel, state) {
		for (var i = carousel.first; i <= carousel.last; i++) {
				if (carousel.has(i)) {
						continue;
				}
				if (i > mycarousel_itemList.length) {
						break;
				}
				carousel.add(i, mycarousel_getItemHTML(mycarousel_itemList[i-1]));
		}
};

function mycarousel_getItemHTML(item) {
	if(item.active === "") {
		return '<a href="' + item.link + '" title="' + item.title + '"><img src="' + item.url + '" width="<?php  echo $width; ?>" height="<?php echo $height; ?>" alt="' + item.url + '" /></a>';
	} else {
		return '<a href="' + item.link + '" title="' + item.title + '"><img class="activecarouselimage" src="' + item.url + '" width="<?php  echo $width; ?>" height="<?php echo $height; ?>" alt="' + item.url + '" /></a>';
	}
};

jQuery(document).ready(function() {
		jQuery("#mycarousel").jcarousel({
				size: mycarousel_itemList.length,
				start: <?php echo $imgnumber; ?>,
				scroll: <?php echo $thumbscroll; ?>,
				itemLoadCallback: {onBeforeAnimation: mycarousel_itemLoadCallback}
		});
});
</script>
	<ul id="mycarousel">
		<!-- The content will be dynamically loaded in here -->
	</ul>
	<?php
	}
}
?>