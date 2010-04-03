<?php
/**
 * Provides the means to set an limit of the number of images that can be uploaded to an album in total. Of course this is bypassed if using FTP upload or ZIP files! If you want to limit the latter you need to use the quota_managment plugin additonally.
 * NOTE: Using this plugin with the http browser single file upload is limited to the upload of the maximum 5 images at the time! 
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 */

$plugin_is_filter = 5;
$plugin_description = gettext("Provides the means to set an limit of the number of images that can be uploaded to an album in total. Of course this is bypassed if using FTP upload or ZIP files! If you want to limit the latter you need to use the quota_managment plugin additonally.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.3.0'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---filter-image_uplad_limiter.php.html";

$option_interface = new uploadlimit();
zp_register_filter('upload_helper_js', 'uploadLimiterJS');
zp_register_filter('get_upload_header_text', 'uploadLimiterHeaderMessage');

/**
 * Option handler class
 *
 */
class uploadlimit {
	/**
	 * class instantiation function
	 *
	 * @return filter_zenphoto_seo
	 */
	function uploadlimit() {
		setOptionDefault('imageuploadlimit', 5);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(	gettext('Upload limit') => array('key' => 'imageuploadlimit', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('The maximum number of images per album if uploading via the multifile upload.')));
	}

	function handleOption($option, $currentValue) {
	}

}


/** 
 * Prints the jQuery JS setup for the upload limiting
 * 
 * @return string
 */
function uploadLimiterJS() {
global $_zp_loggedin;
//if(!($_zp_loggedin & ADMIN_RIGHTS)) {
	$albumlist = array();
	genAlbumUploadList($albumlist);
	$rootrights = isMyAlbum('/', UPLOAD_RIGHTS);
	$uploadtype = zp_getcookie('uploadtype');
?>
<script type="text/javascript">
<?php if($uploadtype == "http") { ?>
$('#albumselect').hide();
<?php } ?>
var buttonenable = true;
function generateUploadlimit(selectedalbum,limitalbums) {
	$('#uploadlimitmessage').remove();
	var imagenumber = new Array(<?php printUploadImagesInAlbum($albumlist); ?>);
	var message = "";
	var uploadlimit = <?php echo getOption('imageuploadlimit'); ?>;
	var imagesleft = ""; 
	$.each(limitalbums, function(key,value) {
		if(value == selectedalbum) {
			if(imagenumber[key] >= uploadlimit) {
				imagesleft = 0;
			} else if (imagenumber[key] < uploadlimit) {
				imagesleft = uploadlimit - imagenumber[key];
			} 
			if(imagesleft === 0) {
		   	$('#fileUploadbuttons').hide();
		   	queuelimit = 0;
		   	message = '<?php echo gettext('The album exceeded the image number limit. You cannot upload more images!'); ?>';
				//alert(message);
				$('#albumselect').prepend('<span id="uploadlimitmessage" style="color:red; font-weight: bold;">'+message+'<br /><br /></span>');
			} else {
				queuelimit = imagesleft;	
				message = '<?php echo gettext("Maximum number of images to upload for this album: "); ?>'+imagesleft;
				//alert(message);
			 $('#albumselect').prepend('<span id="uploadlimitmessage" style="color:green">'+message+'<br /><br /></span>');
			}
		}
	});
	return queuelimit;
}
var limitalbums = new Array(<?php printUploadLimitedAlbums($albumlist); ?>);
<?php if(isset($_GET['album']) && !empty($_GET['album'])) { // if we upload 
	$selectedalbum = sanitize($_GET['album']);
?>
var selectedalbum = '<?php echo $selectedalbum; ?>'; 
	<?php } else if($rootrights) { ?>
var selectedalbum = ""; // choose the first entry of the select list if nothing is selected and the user has root rights (so root no message...)
	<?php } else {?>
var selectedalbum = limitalbums[0]; // choose the first entry of the select list if nothing is selected and no rootrights
	<?php } ?>
var queuelimit = generateUploadlimit(selectedalbum,limitalbums);	
$("#albumselect").change(function() {
	selectedalbum = $("#albumselectmenu").val();
	queuelimit = generateUploadlimit(selectedalbum,limitalbums);	
});
function uploadify_onSelectOnce(event, data) {
	if (data.fileCount > queuelimit) {
		alert('<?php echo gettext('Too many images! You can only upload: '); ?>'+queuelimit);
		$('#fileUpload').uploadifyClearQueue();
	} 
}
</script>
<?php //}
}

function uploadLimiterHeaderMessage($default) {
	/*if (zp_loggedin(ADMIN_RIGHTS)) {
		return $default;
		} */
	$warn = "";
	$uploadtype = zp_getcookie('uploadtype');
	echo $uploadtype;
	if($uploadtype != "multifile") {
		$warn = '<p style="color:red;">'.gettext('HTTP single file uploading is disabled because upload limitations are in effect. Please use the <a href="javascript:switchUploader(\'admin-upload.php?uploadtype=multifile\');" >multi file upload</a>').'</p>';
	}
	return $warn;
}

/*
 * Prints a list of all albums for a JS array
 *
 * @param array $albumslist the array of the albums as generated by genAlbumUploadList()
 * @return string
 */
function printUploadLimitedAlbums($albumlist) {
	$limitedalbums = array();
	foreach($albumlist as $key => $value) {
		$obj = new Album($gallery,$key);
		$limitedalbums[] = $obj->name;
	}
	$numalbums = count($limitedalbums);
	$count = '';
	foreach($limitedalbums as $album) {
		$content = "'";
		$count++;
		$content .= $album;
		if($count < $numalbums) {
			$content .= "',"; // js array entry end
		} else {
			$content .= "'"; // js array end
		}
		echo $content;
	}
}

/*
 * Prints the number of images within each album for a JS array
 *
 * @param array $albumslist the array of the albums as generated by genAlbumUploadList()
 * @return string
 */
function printUploadImagesInAlbum($albumlist) {
	$numbers = array();
	foreach($albumlist as $key => $value) {
		$obj = new Album($gallery,$key);
		$numbers[] = $obj->getNumImages();
	}
	$numimages = count($numbers);
	$count = '';
	foreach($numbers as $number) {
		$content = "'";
		$count++;
		$content .= $number;
		if($count < $numimages) {
			$content .= "',"; // js array entry end
		} else {
			$content .= "'"; // js array end
		}
		echo $content;
	}
}