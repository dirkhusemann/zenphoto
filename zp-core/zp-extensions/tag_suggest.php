<?php
/**
 * tag suggest plugin draft based on Remy Sharp's jQuery Tag Suggestion plugin
 * Just activate the plugin and the feature is available on the theme's search field.
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard) - an adaption of Remy Sharp's <a href='http://remysharp.com/2007/12/28/jquery-tag-suggestion/ '>jQuery Tag Suggestion</a>
 * @package plugins
 */

$plugin_description = gettext("Enables jQuery tag suggestions on the search field. Just activate the plugin and the feature is available on the theme's search field.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard) — ".gettext("an adaption of Remy Sharp's <a href='http://remysharp.com/2007/12/28/jquery-tag-suggestion/ '>jQuery Tag Suggestion</a>");
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---tag_suggest.php.html";

// register the scripts needed
addPluginScript('<script type="text/javascript" src="' . WEBPATH . '/' . ZENFOLDER . '/js/tag.js"></script>');
addPluginScript('<link type="text/css" rel="stylesheet" href="' . WEBPATH . '/' . ZENFOLDER .'/'.PLUGIN_FOLDER . '/tag_suggest/tag.css" />');
$taglist = getAllTagsUnique();
$c = 0;
$list = '';
foreach ($taglist AS $tag) {
	if ($c>0) $list .= ',';
	$c++;
	$list .= '"'.addslashes($tag).'"';
}
$js = '<script type="text/javascript">'."\n".
			'var _tagList = ['.$list."];\n".
			"$(function () {\n".
				"$('#search_input, #edit-editable_4').tagSuggest({ separator:'".(getOption('search_space_is_or')?' ':',')."', tags: _tagList });\n".
			"});\n".
		'</script>';
addPluginScript($js);
?>