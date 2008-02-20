<?php

function setDefault($option, $default) {
	global $conf;
	$v = $conf[$option];
	if (empty($v)) {
		$v = $default;
	}
	setOptionDefault($option, $v); 
}
	require('zp-config.php');

	global $_zp_conf_vars, $_zp_options;
	$conf = $_zp_conf_vars;
	
	setOption('zenphoto_release', ZENPHOTO_RELEASE); 
	
	$admin = getOption('adminuser');
	if (!empty($admin)) {
		saveAdmin($admin, getOption('adminpass'), getOption('admin_name') , getOption('admin_email'), ALL_RIGHTS, array());
	}
	
	setDefault('gallery_title', "Gallery");
	setDefault('gallery_password', '');
	setDefault('gallery_hint', NULL);
	setDefault('search_password', '');
	setDefault('search_hint', NULL);
	setDefault('website_title', "");
	setDefault('website_url', "");
	setDefault('time_offset', 0);
	setDefault('gmaps_apikey', "");
	setDefault('album_session', 0);  
	
	if ($_GET['mod_rewrite'] == 'ON') {
		$rw = 1;
	} else {
		$rw = 0;
	}
	setDefault('mod_rewrite', $rw); 
	setDefault('mod_rewrite_image_suffix', ".php");
	
	setDefault('server_protocol', "http");
	setDefault('charset', "UTF-8");
	setDefault('image_quality', 85);
	setDefault('thumb_quality', 75);
	setDefault('image_size', 595);
	setDefault('image_use_longest_side', 1);
	setDefault('image_allow_upscale', 0);
	setDefault('thumb_size', 100);
	setDefault('thumb_crop', 1);
	setDefault('thumb_crop_width', 85);
	setDefault('thumb_crop_height', 85);
	setDefault('thumb_sharpen', 0);
	setDefault('albums_per_page', 5);
	setDefault('images_per_page', 15);
	setDefault('perform_watermark', 0);
	setDefault('watermark_h_offset', 90);
	setDefault('watermark_w_offset', 90);
	setDefault('watermark_image', "watermarks/watermark.png");
	setDefault('perform_video_watermark', 0);
	setDefault('video_watermark_image', "watermarks/watermark-video.png");
	setDefault('spam_filter', 'none');
	setDefault('email_new_comments', 1);
	setDefault('gallery_sorttype', 'ID');
	setDefault('gallery_sortdirection', '0');
	setDefault('image_sorttype', 'Filename');
	setDefault('image_sortdirection', '0');
	setDefault('current_theme', 'default');
	setDefault('feed_items', 10);
	setDefault('search_fields', 32767);  
	setOptionDefault('allowed_tags', "a => (href => () title => ()) \n".
	 															"abbr => (title => ())\n" . 
	 															"acronym => (title => ())\n" . 
	 															"b => ()\n" .
	 															"blockquote => (cite => ())\n" .
	 															"code => ()\n" .
	 															"em => ()\n" . 
	 															"i => () \n" .
	 															"strike => ()\n" . 
	 															"strong => ()\n" .
	 															"ul => ()\n" .
	 															"ol => ()\n" .
	 															"li => ()\n");
	setDefault('comment_name_required', 1);
	setDefault('comment_email_required', 1);
	setDefault('comment_web_required', 0);
	setDefault('Use_Captcha', true);
	setDefault('full_image_download', 0);
	setDefault('full_image_quality', 75);
	setDefault('persistent_archive', 0);
	setDefault('protect_full_image', 1);
	setDefault('locale', '');
	
?>