<?php
$button_text = gettext("Purge RSS cache");
$button_action = WEBPATH.'/'.ZENFOLDER.'/admin.php?action=clear_rss_cache';
$button_icon = 'images/edit-delete.png'; 
$button_title = gettext("Sets all hitcounters to zero.");
$button_alt = gettext("Purge RSS cache");
$button_hidden = '<input type="hidden" name="action" value="clear_rss_cache" />';
$button_rights = ADMIN_RIGHTS;
?>