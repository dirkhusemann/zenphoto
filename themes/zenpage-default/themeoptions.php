<?php

/* Plug-in for theme option handling 
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 * 
 */

require_once(SERVERPATH . "/" . ZENFOLDER . "/admin-functions.php");

class ThemeOptions {
	
	function ThemeOptions() {
		setOptionDefault('zenpage_comments_allowed', false); 
		setOptionDefault('zenpage_zp_index_news', false);
		setOptionDefault('Allow_search', true);
		setOptionDefault('Use_thickbox', true);
		setOptionDefault('zenpage_homepage', 'none');
		setOptionDefault('loginform', false);
	}
	
	function getOptionsSupported() {
		return array(	gettext('Allow page & news comments') => array('key' => 'zenpage_comments_allowed', 'type' => 1, 'desc' => gettext("Set to enable comment section for news and pages.")),
									gettext('Allow search') => array('key' => 'Allow_search', 'type' => 1, 'desc' => gettext('Check to enable search form.')),
									gettext('Use Thickbox') => array('key' => 'Use_thickbox', 'type' => 1, 'desc' => gettext('Check to display of the full size image with Thickbox.')),
									gettext('News on index page') => array('key' => 'zenpage_zp_index_news', 'type' => 1, 'desc' => gettext("Enable this if you want to show the news section's first page on the <code>index.php</code> page.")),
									gettext('Homepage') => array('key' => 'zenpage_homepage', 'type' => 2, 'desc' => gettext("Choose here any <em>unpublished Zenpage page</em> (listed by <em>titlelink</em>) to act as your site's homepage instead the normal gallery index. <strong>Note:</strong> This of course overrides the <em>News on index page</em> option and your theme must be setup for this feature! Visit the theming tutorial for details.")),
									gettext('Show login form') => array('key' => 'loginform', 'type' => 1, 'desc' => gettext("Enable this if you want to show the login form in the sidebar if the user_logout plugin is enabled."))
									
									);
	}

	function handleOption($option, $currentValue) {
		if($option == "zenpage_homepage") {
			$unpublishedpages = query_full_array("SELECT titlelink FROM ".prefix('zenpage_pages')." WHERE `show` != 1 ORDER by `sort_order`");
			if(empty($unpublishedpages)) {
				echo gettext("No unpublished pages available");
				// clear option if no unpublished pages are available or have been published meanwhile
				// so that the normal gallery index appears and no page is accidentally set if set to unpublished again.
				setOption("zenpage_homepage", "none", true); 
			} else {
				echo '<input type="hidden" name="'.CUSTOM_OPTION_PREFIX.'selector-zenpage_homepage" value=0 />' . "\n";
				echo '<select id="'.$option.'" name="zenpage_homepage">'."\n";
				if($currentValue === "none") {
					$selected = " selected = 'selected'";
				} else {
					$selected = "";
				}
				echo "<option$selected>".gettext("none")."</option>";
				foreach($unpublishedpages as $page) {
					if($currentValue === $page["titlelink"]) {
						$selected = " selected = 'selected'";
					} else {
						$selected = "";
					}
					echo "<option$selected>".$page["titlelink"]."</option>";
				}
				echo "</select>\n";
			}
		}
	}
}
?>
