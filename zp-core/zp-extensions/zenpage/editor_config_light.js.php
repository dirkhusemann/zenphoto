<?php
/**
 * The configuration functions for TinyMCE with Ajax File Manager.
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */ 


/**
 * The Javascript configuration code for TinyMCE with Ajax File Manager.
 * 
 * @param string $locale You can set a specific language locale for TinyMCE and the Ajax File Manager since there are especially for TinyMCE a lot more language available than for Zenphoto and Zenpage. But note this requires a locale name like "en" instead of "en_EN". If empty the language set in the Zenphoto options is used.
 * 
 */ 
function printTextEditorConfigJS($locale='') {
	if(empty($locale)) {
		$locale = getLocaleForTinyMCEandAFM();
	} 
?>
 <script language="javascript" type="text/javascript" src="../tiny_mce/tiny_mce.js"></script>
	<script language="javascript" type="text/javascript">
		tinyMCE.init({
			mode : "textareas",
			language: "<?php echo $locale; ?>",
			editor_selector: "mceEditor",
			elements : "ajaxfilemanager",
			theme : "advanced",
			plugins : "safari,pagebreak,style,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,tinyzenpage",
			theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,|,bullist,numlist,|,outdent,indent,blockquote",
			theme_advanced_buttons2 : "undo,redo,|,link,unlink,anchor,image,cleanup,help,code,fullscreen,|,pagebreak,tinyzenpage",
			theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_resize_horizontal : false,
			file_browser_callback : "ajaxfilemanager",
			paste_use_dialog : true,
			paste_create_paragraphs : false,
			paste_create_linebreaks : false,
			paste_auto_cleanup_on_paste : true,
			apply_source_formatting : true,
			force_br_newlines : false,
			force_p_newlines : true,	
			relative_urls : false,
			document_base_url : "<?php echo WEBPATH."/"; ?>",
			convert_urls : false,
			entity_encoding: "raw"
		});

		function ajaxfilemanager(field_name, url, type, win) {
<?php	echo "var ajaxfilemanagerurl = \"".FULLWEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER."/tiny_mce/plugins/ajaxfilemanager/ajaxfilemanager.php?editor=tinymce\";"; ?>
			switch (type) {
				case "image":
					ajaxfilemanagerurl += "&amp;type=img&amp;language=<?php echo $locale; ?>";
					break;
				case "media":
					ajaxfilemanagerurl += "&amp;type=media&amp;language=<?php echo $locale; ?>";
					break;
				case "flash": //for older versions of tinymce
					ajaxfilemanagerurl += "&amp;type=media&amp;language=<?php echo $locale; ?>";
					break;
				case "file":
					ajaxfilemanagerurl += "&amp;type=files&amp;language=<?php echo $locale; ?>";
					break;
				default:
					return false;
			}
				tinyMCE.activeEditor.windowManager.open({
			  file : ajaxfilemanagerurl,
			  input : field_name,
			  width : 750,
			  height : 500,
			  resizable : "yes",
			  inline : "yes",
			  close_previous: "yes"
			},{
			  window: win,
			  input: field_name
		 	});
		}
		
 function toggleEditor(id) {
	if (!tinyMCE.get(id))
		tinyMCE.execCommand('mceAddControl', false, id);
	else
		tinyMCE.execCommand('mceRemoveControl', false, id);
}
	</script>
<?php
} // function end
?>