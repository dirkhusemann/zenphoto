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
  unset($setup);  // it is now ok (and necessary) to access the database options table

  global $_zp_conf_vars;
  $conf = $_zp_conf_vars;
  
  setDefault('gallery_title', "Gallery");
  setDefault('website_title', "");
  setDefault('website_url', "");
  setDefault('time_offset', 0);
  setDefault('gmaps_apikey', "");
  setDefault('mod_rewrite', 0);
  setDefault('mod_rewrite_image_suffix', ".php");
  setDefault('adminuser', "");
  setDefault('adminpass', "");
  setDefault('admin_email', "");
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
  setDefault('watermark_image', "images/watermark.png");
  setDefault('perform_video_watermark', 0);
  setDefault('video_watermark_image', "images/watermark-video.png");
  setDefault('spam_filter', 'none');
  setDefault('email_new_comments', 1);
  setDefault('gallery_sorttype', 'Manual');
  setDefault('gallery_sortdirection', '0');
  setDefault('current_theme', 'default');
  setDefault('feed_items', 10);

?>