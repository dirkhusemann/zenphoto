<?php

/* Plug-in for theme option handling
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 *
 */

class ThemeOptions {

	function ThemeOptions() {
		setThemeOptionDefault('Theme_logo', '');
		setThemeOptionDefault('Allow_search', true);
		setThemeOptionDefault('enable_album_zipfile', false);
		setThemeOptionDefault('Slideshow', true);
		setThemeOptionDefault('Graphic_logo', '*');
		setThemeOptionDefault('Watermark_head_image', true);
		setThemeOptionDefault('Theme_personality', 'Image page');
		setThemeOptionDefault('Theme_colors', 'effervescence');
		setThemeOptionDefault('effervescence_menu', 'effervescence');
		setThemeOptionDefault('albums_per_row', 3);
		setThemeOptionDefault('images_per_row', 5);
		setThemeOptionDefault('thumb_transition', 1);

		if (function_exists('createMenuIfNotExists')) {
			$menuitems = array(
										array('type'=>'menulabel','title'=>gettext('News Articles'),'link'=>'','show'=>1,'nesting'=>0),
										array('type'=>'menufunction','title'=>gettext('All news'),'link'=>'printAllNewsCategories("All news",TRUE,"","menu-active",false);','show'=>1,'include_li'=>0,'nesting'=>1),
										array('type'=>'html','title'=>gettext('News Articles Rule'),'link'=>'<li class="menu_rule menu_menulabel"></li>','show'=>1,'include_li'=>0,'nesting'=>0),
										array('type'=>'custompage','title'=>gettext('Gallery'),'link'=>'gallery','show'=>1,'nesting'=>0),
										array('type'=>'menufunction','title'=>gettext('All Albums'),'link'=>'printAlbumMenuList("list",NULL,"","menu-active","submenu","menu-active","",false,false,false,false);','show'=>1,'include_li'=>0,'nesting'=>1),
										array('type'=>'html','title'=>gettext('Gallery Rule'),'link'=>'<li class="menu_rule menu_menulabel"></li>','show'=>1,'include_li'=>0,'nesting'=>0),
										array('type'=>'menulabel','title'=>gettext('Pages'),'link'=>'','show'=>1,'nesting'=>0),
										array('type'=>'menufunction','title'=>gettext('All pages'),'link'=>'printPageMenu("list","","menu-active","submenu","menu-active","",0,false);','show'=>1,'include_li'=>0,'nesting'=>1),
										array('type'=>'html','title'=>gettext('Pages Rule'),'link'=>'<li class="menu_rule menu_menulabel"></li>','show'=>1,'include_li'=>0,'nesting'=>0),
										array('type'=>'menulabel','title'=>gettext('Archive'),'link'=>'','show'=>1,'nesting'=>0),
										array('type'=>'custompage','title'=>gettext('Gallery and News'),'link'=>'archive','show'=>1,'nesting'=>1),
										array('type'=>'html','title'=>gettext('Archive Rule'),'link'=>'<li class="menu_rule menu_menulabel"></li>','show'=>1,'include_li'=>0,'nesting'=>0),
										array('type'=>'menulabel','title'=>gettext('RSS'),'link'=>'','show'=>1,'nesting'=>0),
										array('type'=>'customlink','title'=>gettext('Gallery'),'link'=>WEBPATH.'/rss.php','show'=>1,'nesting'=>1),
										array('type'=>'customlink','title'=>gettext('News'),'link'=>WEBPATH.'/rss-news.php','show'=>1,'nesting'=>1),
										array('type'=>'customlink','title'=>gettext('News and Gallery'),WEBPATH.'link'=>'/rss-news.php?withimages','show'=>1,'nesting'=>1),
										);
			createMenuIfNotExists($menuitems, 'effervescence');
		}

	}

	function getOptionsSupported() {
		return array(	gettext('Theme logo') => array('key' => 'Theme_logo', 'type' => OPTION_TYPE_TEXTBOX, 'multilingual' => 1, 'desc' => gettext('The text for the theme logo')),
									gettext('Watermark head image') => array('key' => 'Watermark_head_image', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to place a watermark on the heading image. (Image watermarking must be set.)')),
									gettext('ZIP file download') => array('key' => 'enable_album_zipfile', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable album ZIP file download link.')),
									gettext('Allow search') => array('key' => 'Allow_search', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable search form.')),
									gettext('Slideshow') => array('key' => 'Slideshow', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Check to enable slideshow for the <em>Smoothgallery</em> personality.')),
									gettext('Graphic logo') => array('key' => 'Graphic_logo', 'type' => OPTION_TYPE_CUSTOM, 'desc' => sprintf(gettext('Select a logo (PNG files in the <em>%s/images</em> folder) or leave empty for text logo.'),UPLOAD_FOLDER)),
									gettext('Theme personality') => array('key' => 'Theme_personality', 'type' => OPTION_TYPE_SELECTOR, 'selections' => array(gettext('Image page') => 'Image page', gettext('Simpleviewer') => 'Simpleviewer', gettext('Slimbox') => 'Slimbox', gettext('Smoothgallery') => 'Smoothgallery'),
													'desc' => gettext('Select the theme personality')),
									gettext('Theme colors') => array('key' => 'Theme_colors', 'type' => OPTION_TYPE_CUSTOM, 'desc' => gettext('Select the colors of the theme')),
									gettext('Custom menu') => array('key' => 'effervescence_menu', 'type' => OPTION_TYPE_CUSTOM, 'desc' => gettext('Set this to the <em>menu_manager</em> menu set you wish to use.').
													'<p class="notebox">'.gettext('<strong>Note:</strong> This option is valid only if you have the <em>Gallery index page link</em> option set to "gallery". Of course the <em>menu_manager</em> plugin must also be enabled.').'</p>')
									);
	}

	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'Theme_colors':
				$theme = basename(dirname(__FILE__));
				$themeroot = SERVERPATH . "/themes/$theme/styles";
				echo '<select id="EF_themeselect_colors" name="' . $option . '"' . ">\n";
				generateListFromFiles($currentValue, $themeroot , '.css');
				echo "</select>\n";
				break;
			case 'effervescence_menu':
				$menusets = array();
				echo '<select id="EF_menuset" name="effervescence_menu"';
				if (function_exists('printCustomMenu') && getThemeOption('custom_index_page',NULL, 'effervescence_plus') === 'gallery') {
					$result = query_full_array("SELECT DISTINCT menuset FROM ".prefix('menu')." ORDER BY menuset");
					foreach ($result as $set) {
						$menusets[$set['menuset']] = $set['menuset'];
					}
				} else {
					echo ' disabled="disabled"';
				}
				echo ">\n";
				echo '<option value="" style="background-color:LightGray">'.gettext('*standard menu').'</option>';
				generateListFromArray(array($currentValue), $menusets , false, false);
				echo "</select>\n";
				break;
			case 'Graphic_logo':
				?>
				<select id="EF_themeselect_logo" name="Graphic_logo">
					<option value="" style="background-color:LightGray"><?php echo gettext('*no logo selected'); ?></option>';
					<option value="*"<?php if ($currentValue == '*') echo ' selected="selected"'; ?>><?php echo gettext('Effervescence'); ?></option>';
					<?php
					generateListFromFiles($currentValue, SERVERPATH.'/'.UPLOAD_FOLDER.'/images' , '.png');
					?>
				</select>
				<?php
				break;
		}
	}
}
?>
