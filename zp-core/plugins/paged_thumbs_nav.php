<?php
/**
 * Prints a paged thumbnail navigation to be used on a theme's image.php, independent of the album.php's thumbs loop
 *
 * @author Malte Müller (acrylian)
 * @version 1.0.7
 * @package plugins
 */
$plugin_description = gettext("Prints a paged thumbs navigation on image.php, independend of the album.php's thumbs. The function contains some predefined CSS ids you can use for styling. Please see the documentation for more info.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0.7';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---paged_thumbs_nav.php.html";
$option_interface = new pagedthumbsOptions();

/**
 * Plugin option handling class
 *
 */
class pagedthumbsOptions {

	function pagedthumbsOptions() {
		setOptionDefault('pagedthumbs_imagesperpage', '10');
		setOptionDefault('pagedthumbs_counter', '');
		setOptionDefault('pagedthumbs_prevtext', '« prev thumbs');
		setOptionDefault('pagedthumbs_nexttext', 'next thumbs »');
		setOptionDefault('pagedthumbs_width', '50');
		setOptionDefault('pagedthumbs_height', '50');
		setOptionDefault('pagedthumbs_crop', '1');
		setOptionDefault('pagedthumbs_placeholders', '');
		setOptionDefault('pagedthumbs_pagelist', '');
		setOptionDefault('pagedthumbs_pagelistprevnext', '');
		setOptionDefault('pagedthumbs_pagelistlenght', '6');
	}


	function getOptionsSupported() {
		return array(	gettext('Thumbs per page') => array('key' => 'pagedthumbs_imagesperpage', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("Controls the number of images on a page. You might need to change	this after switching themes to make it look better.")),
		gettext('Counter') => array('key' => 'pagedthumbs_counter', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If you want to show the counter 'x - y of z images'.")),
		gettext('Prevtext') => array('key' => 'pagedthumbs_prevtext', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The text for the previous thumbs.")),
		gettext('Nexttext') => array('key' => 'pagedthumbs_nexttext', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The text for the next thumbs.")),
		gettext('Crop width') => array('key' => 'pagedthumbs_width', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The thumb crop width is the maximum width when height is the shortest side")),
		gettext('Crop height') => array('key' => 'pagedthumbs_height', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext("The thumb crop height is the maximum height when width is the shortest side")),
		gettext('Crop') => array('key' => 'pagedthumbs_crop', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If checked the thumbnail will be a centered portion of the	image with the given width and height after being resized to thumb	size (by shortest side). Otherwise, it will be the full image resized to thumb size (by shortest side).")),
		gettext('Placeholders') => array('key' => 'pagedthumbs_placeholders', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("if you want to use placeholder for layout reasons to fill up the thumbs if the the number of thumbs does not match images per page. Recommended only for cropped thumbs.")),
		gettext('Page list') => array('key' => 'pagedthumbs_pagelist', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If you want the list of the pages to be shown.")),
		gettext('Pages list prev and next links') => array('key' => 'pagedthumbs_pagelistprevnext', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("If you want to show previous and next thumb page links with the page list.")),
		gettext('Pages list lenght') => array('key' => 'pagedthumbs_pagelistlenght', 'type' => OPTION_TYPE_CHECKBOX,
										'desc' => gettext("The number of links for the page list."))
		);
	}

}

/**
 * Prints a paged thumbnail navigation to be used on a theme's image.php, independent of the album.php's thumbs loop
 *
 * NOTE: With version 1.0.2 $size is no longer an option for this plugin. This plugin now uses the new maxspace function if crop set to false.
 *
 * The function contains some predefined CSS ids you can use for styling.
 * NOTE: In 1.0.3 a extra div around the thumbnails has been added: <div id="pagedthumbsimages">.
 * The function prints the following HTML:
 *
 * <div id="pagedthumbsnav">
 * 
 * <div id="pagedthumbsnav-prev">
 * <a href="">Previous thumbnail list</a>
 * </div> (if the link is inactive id="pagedthumbsnav-prevdisabled", you can hide it via CSS if needed)
 * 
 * <div id="pagedthumbsimages">
 * <a href=""><img></a> (...) (the active thumb has class="pagedthumbsnav-active")
 * </div>
 * 
 * <div id="pagedthumbsnav-next">
 * <a href="">Next thumbnail list</a> (if the link is inactive id="pagedthumbsnav-nextdisabled", you can hide it via CSS if needed)
 * </div>
 * 
 * <p id="pagethumbsnav-counter>Images 1 - 10 of 20 (1/3)</p> (optional)
 * <ul id="pagedthumbsnav-pagelist"> (optional)
 * <li><a href=""></a></li> (active page link has css class "pagedthumbsnav-pagelistactive" attached)
 * </ul>
 * 
 * </div>
 *
 * @param int $imagesperpage How many thumbs you want to display per list page
 * @param bool $counter If you want to show the counter of the type "Images 1 - 10 of 20 (1/3)"
 * @param string $prev The previous thumb list link text
 * @param string $next The next thumb list link text
 * @param int $width The thumbnail crop width, if set to NULL the general admin setting is used. If cropping is FALSE this is the maxwidth of the thumb
 * @param int $height The thumbnail crop height, if set to NULL the general admin setting is used. If cropping is FALSE this is the maxwheight of the thumb
 * @param bool $crop Enter 'true' or 'false' to override the admin plugin option setting, enter NULL to use the admin plugin option (default)
 * @param bool $placeholders Enter 'true' or 'false' if you want to use placeholder for layout reasons if teh the number of thumbs does not match $imagesperpage. Recommended only for cropped thumbs. This is printed as an empty <span></span> whose width and height are set via inline css. The remaining needs to be style via the css file and can be adressed via  "#pagedthumbsimages span".
 * @param bool $showpagelist Enter 'true' or 'false' if you want to a list of the pages available. Can be styled via  "#pagedthumbsnav-pagelist".
 * @param bool $showprevnext If you want to show the prev and next links with the pagelist
 * * @param int $navlen How many page links should be shown (not that there will be dotted ransition links like this: 1 2 3 4 ... 30).
 * 
 */
function printPagedThumbsNav($imagesperpage='', $counter='', $prev='', $next='', $width=NULL, $height=NULL, $crop=NULL,$placeholders=NULL, $showpagelist=false, $pagelistprevnext=false, $pagelistlength=6) {
	global $_zp_current_album, $_zp_current_image, $_zp_current_search, $_zp_gallery;
	// in case someone wants to override the options by parameter
	if(is_null($crop)) {
		$crop = getOption("pagedthumbs_crop");
	}
	if(empty($imagesperpage)) {
		$imagesperpage = getOption("pagedthumbs_imagesperpage");
	}
	if(is_null($width)) {
		$width = getOption("pagedthumbs_width");
	} else {
		$width = sanitize_numeric($width);
	}
	if(is_null($height)) {
		$height = getOption("pagedthumbs_height");
	} else {
		$height = sanitize_numeric($height);
	}
	if(empty($prev)) {
		$prev = getOption("pagedthumbs_prevtext");
	}
	if(empty($next)) {
		$next = getOption("pagedthumbs_nexttext");
	}
	if(empty($counter)) {
		$counter = getOption("pagedthumbs_counter");
	}

	// get the image of current album
	if(in_context(ZP_SEARCH_LINKED)) {
		if($_zp_current_search->getNumImages() === 0) {
			$searchimages = false;
		}	else {
			$searchimages = true;
		}
	} else {
		$searchimages = false;
	}
	if(in_context(ZP_SEARCH_LINKED) AND $searchimages) {
		$images = $_zp_current_search->getImages();
		$totalimages = $_zp_current_search->getNumImages();
		$getimagenumber = 0;
		foreach($images as $image) {
			$getimagenumber++;
			if($_zp_current_image->filename === $image['filename'] AND $_zp_current_album->name === $image['folder']) {
				$currentimgnr = $getimagenumber;
			}
		}
	} else {
		$totalimages = getNumImages();
		$images = $_zp_current_album->getImages();
		$currentimgnr = imageNumber();
	}
	$totalpages = ceil($totalimages / $imagesperpage);
	for ($nr = 1;$nr <= $totalpages; $nr++)	{
		$startimg[$nr] = $nr*$imagesperpage - ($imagesperpage - 1); // get start image number for thumb pagination
		$endimg[$nr] = $nr * $imagesperpage; // get end image number for thumb pagination
	}

	// get current page number
	for ($nr = 1;$nr <= $totalpages; $nr++)	{
		if ($startimg[$nr] <= $currentimgnr) {
			$currentpage = $nr;
		}
		if ($endimg[$nr] >= $currentimgnr) {
			$currentpage = $nr;
			break;
		}
	}
	echo "<div id=\"pagedthumbsnav\">\n";
	if ($currentpage == 1) {
		echo "<div id=\"pagedthumbsnav-prevdisabled\">".$prev.">";
	} else {
		echo "<div id=\"pagedthumbsnav-prev\">\n";
	}
	// Prev thumbnails - show only if there is a prev page
	$prevpageimage = ""; // define needed for page list
	if ($totalpages > 1)	{
		$prevpageimagenr = ($currentpage * $imagesperpage) - ($imagesperpage+1);
		if ($currentpage > 1) {
			if(in_context(ZP_SEARCH_LINKED) AND $searchimages) {
				$albumobj = new Album($_zp_gallery,$images[$prevpageimagenr]['folder']);
				$prevpageimage = newImage($albumobj,$images[$prevpageimagenr]['filename']);
			} else {
				$prevpageimage = newImage($_zp_current_album,$images[$prevpageimagenr]);
			}
			echo "<a href=\"".$prevpageimage->getImageLink()."\" title=\"".gettext("previous thumbs")."\">".$prev."</a>\n";
		}
	} 
	echo "</div>\n";
	echo "<div id='pagedthumbsimages'>";
	// the thumbnails
	$number = $startimg[$currentpage] - 2;
	for ($nr = 1;$nr <= $imagesperpage; $nr++) {
		$number++;
		if($number == $totalimages) {
			break;
		}
		if(in_context(ZP_SEARCH_LINKED) AND $searchimages) {
			$albumobj = new Album($_zp_gallery,$images[$number]['folder']);
			$image = newImage($albumobj,$images[$number]['filename']);
		} else {
			$image = newImage($_zp_current_album,$images[$number]);
		}
		if($image->id === getImageID()) {
			$css = " id='pagedthumbsnav-active' ";
		} else {
			$css = "";
		}
		echo "<a $css href=\"".$image->getImageLink()."\" title=\"".strip_tags($image->getTitle())."\">";

		if($crop) {
			echo "<img src='".$image->getCustomImage(null, $width, $height, $width, $height, null, null, true)."' alt=\"".strip_tags($image->getTitle())."\" width='".$width."' height='".$height."' />";
		} else {
			$maxwidth = $width; // needed because otherwise getMaxSpaceContainer will use the values of the first image for all others, too
			$maxheight = $height;
			getMaxSpaceContainer($maxwidth, $maxheight, $image, true);
			echo "<img src=\"".$image->getCustomImage(NULL, $maxwidth, $maxheight, NULL, NULL, NULL, NULL, false)."\" alt=\"".strip_tags($image->getTitle())."\" />";
		}
		echo "</a>\n";
		if ($number == $endimg[$currentpage]) {
			break;
		}
	}
	// hidden feature currently
	if($placeholders) {
		if($nr != $imagesperpage) {
			$placeholdernr = $imagesperpage - ($nr-1);
			for ($nr2 = 1;$nr2 <= $placeholdernr; $nr2++) {
				echo "<span class=\"placeholder\" style=\"width:".$width."px;height:".$height."px\"></span>";
			}
		}
	}
	echo "</div>";

	// next thumbnails - show only if there is a next page
	$nextpageimage = ""; // define needed for page list
	if ($currentpage == $totalpages) {
		echo "<div id=\"pagedthumbsnav-nextdisabled\">".$prev.">";
	} else {
		echo "<div id=\"pagedthumbsnav-next\">\n";
	}
	if ($totalpages > 1)	{
		if ($currentpage < $totalpages) 	{
			$nextpageimagenr = $currentpage * $imagesperpage;
			if(in_context(ZP_SEARCH_LINKED) AND $searchimages) {
				$albumobj = new Album($_zp_gallery,$images[$nextpageimagenr]['folder']);
				$nextpageimage = newImage($albumobj,$images[$nextpageimagenr]['filename']);
			} else {
				$nextpageimage = newImage($_zp_current_album,$images[$nextpageimagenr]);
			}
			echo "<a href=\"".$nextpageimage->getImageLink()."\" title=\"".gettext("next thumbs")."\">".$next."</a>\n";
		}
	}
		//first if
	echo "</div>\n";

	// image counter
	if($counter) {
		$fromimage = $startimg[$currentpage];
		if($totalimages < $endimg[$currentpage]) {
			$toimage = $totalimages;
		} else {
			$toimage = $endimg[$currentpage];
		}
		echo "<p id=\"pagedthumbsnav-counter\">".sprintf(gettext('Images %1$u-%2$u of %3$u (%4$u/%5$u)'),$fromimage,$toimage,$totalimages,$currentpage,$totalpages)."</p>\n";
	}
	
	// page list
	if($showpagelist AND $totalpages > 1) {
		$total = $totalpages;
		$current = $currentpage;
		$navlen = sanitize_numeric($pagelistlength);
		$extralinks = 4;
		//$extralinks = $extralinks + 2;
		$len = floor(($navlen-$extralinks) / 2);
		$j = max(round($extralinks/2), min($current-$len-(2-round($extralinks/2)), $total-$navlen+$extralinks-1));
		$ilim = min($total, max($navlen-round($extralinks/2), $current+floor($len)));
		$k1 = round(($j-2)/2)+1;
		$k2 = $total-round(($total-$ilim)/2);

		echo "<ul id=\"pagedthumbsnav-pagelist\">\n";
		// prev page
		if($pagelistprevnext AND $totalpages > 1 AND is_object($prevpageimage)) {
			echo "<li><a href=\"".$prevpageimage->getImageLink()."\" title=\"".gettext("previous thumbs")."\">".$prev."</a></li>\n";
		}
		// 1st page
		printPagedThumbsNavPagelink($imagesperpage,$searchimages,$images,$currentpage,1,1);
		
		// transitional page
		if ($j>2) {
			printPagedThumbsNavPagelink($imagesperpage,$searchimages,$images,$currentpage,$k1,"...");
		}
		// normal page
		for ($i=$j; $i <= $ilim; $i++) {
			printPagedThumbsNavPagelink($imagesperpage,$searchimages,$images,$currentpage,$i,$i);
		}
		// transition page
		if ($i < $total) {
			printPagedThumbsNavPagelink($imagesperpage,$searchimages,$images,$currentpage,$i,"...");
		}
		// last page
		if ($i <= $total) {
			printPagedThumbsNavPagelink($imagesperpage,$searchimages,$images,$currentpage,$totalpages,$totalpages);
		}
		// next page
		if($pagelistprevnext AND $totalpages > 1 AND is_object($nextpageimage)) {
			echo "<li><a href=\"".$nextpageimage->getImageLink()."\" title=\"".gettext("next thumbs")."\">".$next."</a></li>\n";
		}
		echo "</ul>\n";
	}
	echo "</div>\n";
}

/* Helper function for printPagedThumbsNav(). Variables are passed from within that function! Not for standalone use! 
 * 
 *  @param int $imagesperpage How many thumbs you want to display per list page
 *  @param bool $searchimages if we are in search and have images
 *  @param string $images array of images
 *  @param int $currentpage number of the current paged thumbs page
 *  @param int $i The number of the page to print a link
 *  @param string $linktext The text to print for the link
 */
function printPagedThumbsNavPagelink($imagesperpage,$searchimages,$images,$currentpage,$i,$linktext) {
	global $_zp_gallery, $_zp_current_album;
	$i = sanitize_numeric($i);
	$imagesperpage = sanitize_numeric($imagesperpage);
	$searchimages = sanitize($searchimages);
	$images = sanitize($images);
	$currentpage = sanitize_numeric($currentpage);
	$linktex = sanitize($linktext);
	$imagenr = ($i * $imagesperpage) - ($imagesperpage);
	if(in_context(ZP_SEARCH_LINKED) AND $searchimages) {
		$albumobj = new Album($_zp_gallery,$images[$imagenr]['folder']);
		$pageimage = newImage($albumobj,$images[$imagenr]['filename']);
	} else {
		$pageimage = newImage($_zp_current_album,$images[$imagenr]);
	}
	if($currentpage == $i) {
		$activcss = "  class=\"pagedthumbsnav-pagelistactive\" ";
		echo "<li class=\"pagedthumbsnav-pagelistactive\">".$linktext."</a>\n";
	} else {
		echo "<li><a href=\"".$pageimage->getImageLink()."\" title=\"Seite ".$i."\">".$linktext."</a></li>\n";
	}
}

?>