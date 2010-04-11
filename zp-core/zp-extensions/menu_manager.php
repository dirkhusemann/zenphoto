<?php
/**
 * Menu Manager
 * 
 * Lets you create arbitrary menus and place them on your theme pages.
 * 
 * Use the "Menu" tab to create your menus. Use printCustomMenu() to place them on your pages.
 * 
 * This plugin is recommend for customized themes only that do not use the standard Zenphoto
 * display structure. Standard Zenphoto functions like the breadcrumb functions or the next_album()
 * loop for example will NOT take care of this menu's structure!
 *
 * @package plugins
 */
$plugin_is_filter = 5;
$plugin_description = gettext("A menu creation facility. The <em>Menu</em> tab admin interface lets you create arbitrary menu trees. They are placed on your theme pages by the <code>printCustomMenu()</code> function.");
$plugin_author = "Maltem Müller (acrylian), Stephen Billard (sbillard)";
$plugin_version = '1.3.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/".PLUGIN_FOLDER."--menu_manager.php.html";

zp_register_filter('admin_tabs', 'menu_tabs');
/**
 * Enter description here...
 *
 * @param unknown_type $tabs
 * @param unknown_type $current
 * @return unknown
 */
function menu_tabs($tabs, $current) {
	if (zp_loggedin()) {
		$newtabs = array();
		foreach ($tabs as $key=>$tab) {
			if ($key == 'tags') {
				$newtabs['menu'] = array(	'text'=>gettext("menu"),
																	'link'=>WEBPATH."/".ZENFOLDER.'/'.PLUGIN_FOLDER.'/menu_manager/menu_tab.php?page=menu&amp;tab=menu',
																	'default'=>'menu',
																	'subtabs'=>NULL);	
			}
			$newtabs[$key] = $tab;
		}
		return $newtabs;
	}
	return $tabs;
}

/*
 * 
 * Common functions
 * 
 */

$_menu_manager_items = array();

/**
 * Enter description here...
 *
 * @param string $menuset the menu tree desired
 * @param unknown_type $visible
 * @return unknown
 */
function getMenuItems($menuset, $visible) {
	global $_menu_manager_items;
	if (isset($_menu_manager_items[$visible])) return $_menu_manager_items[$visible];
	switch($visible) {
		case 'visible':
			$where = " WHERE `show` = 1 AND menuset = '".zp_escape_string($menuset)."'";
			break;
		case 'hidden':
			$where = " WHERE `show` = 0 AND menuset = '".zp_escape_string($menuset)."'";
			break;
		default:
			$where = " WHERE menuset = '".zp_escape_string($menuset)."'";
			$visible = 'all';
			break;
	}
	$result = query_full_array("SELECT id, parentid, title, link, type, sort_order,`show`, menuset FROM ".prefix('menu').$where." ORDER BY sort_order");
	foreach ($result as $item) {
		$_menu_manager_items[$visible][$item['id']] = $item;
	}
	return $_menu_manager_items[$visible];
}

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
function getItem($id) {
	$menuset = checkChosenMenuset();
	$result = query_single_row("SELECT id, parentid, title, link, type, sort_order,`show`, menuset FROM ".prefix('menu')." WHERE menuset = '".zp_escape_string($menuset)."' AND id = ".$id);
	return $result;
}

/**
 * Checks which menu set is chosen via $_GET. If none is explicity chosen the "default" one (create initially) is used.
 *
 * @return unknown
 */
function checkChosenMenuset() {
	if(isset($_GET['menuset'])) {
		$menuset = sanitize($_GET['menuset']);
	} else if(isset($_POST['menuset'])) {
		$menuset = sanitize($_POST['menuset']);
	} else {
		$menuset = "default";
	}
	return $menuset;
}

function checkChosenItemStatus() {
  if(isset($_GET['visible'])) {
  	return sanitize($_GET['visible']);
  } else {
  	return 'all';
  }
}

function getItemTitleAndURL($item) {
	$gallery = new Gallery();
	$array = array();
	switch ($item['type']) {
		case "galleryindex":
			$url = WEBPATH;
			$array = array("title" => get_language_string($item['title']),"url" => $url,"name" => $url);
			break;
		case "album":
			$obj = new Album($gallery,$item['link']);
			$url = rewrite_path("/".$item['link'],"/index.php?album=".$item['link']);
			$array = array("title" => $obj->getTitle(),"url" => $url,"name" => $item['link']);
			break;
		case "zenpagepage":
			$obj = new ZenpagePage($item['link']);
			$url = rewrite_path("/".ZENPAGE_PAGES."/".$item['link'],"/index.php?p=".ZENPAGE_PAGES."&amp;titlelink=".$item['link']);
			$array = array("title" => $obj->getTitle(),"url" => $url,"name" => $item['link']);
			break;
		case "zenpagenewsindex":
			$url = rewrite_path("/".ZENPAGE_NEWS,"/index.php?p=".ZENPAGE_NEWS);
			$array = array("title" => get_language_string($item['title']),"url" => $url,"name" => $url); 
			break;
		case "zenpagecategory":
			$obj = query_single_row("SELECT cat_name FROM ".prefix('zenpage_news_categories')." WHERE cat_link = '".$item['link']."'",true);
			$url = rewrite_path("/".ZENPAGE_NEWS."/category/".$item['link'],"/index.php?p=".ZENPAGE_NEWS."&amp;category=".$item['link']);
			$array = array("title" => get_language_string($obj['cat_name']),"url" => $url,"name" => $item['link']);
			break;
		case "custompage":
			$url = rewrite_path("/page/".$item['link'],"/index.php?p=".$item['link']);
			$array = array("title" => get_language_string($item['title']),"url" => $url,"name" => $item['link']);
		case "customlink":
			$array = array("title" => get_language_string($item['title']),"url" => $item['link'],"name" => $item['link']);
			break;
		case 'menulabel':
			$array = array("title" => get_language_string($item['title']),"url" => NULL, 'name'=>$item['title']);
			break;
	}
	return $array;
}


/*******************
 * Theme functions
 *******************/

function getMenuVisibility() {
	if(zp_loggedin(ZENPAGE_RIGHTS | VIEW_ALL_RIGHTS)) {
		return "all";
	} else {
		return "visible";
	}
}

function getCurrentMenuItem($menuset='default') {
	$currentpageURL = htmlentities(urldecode($_SERVER["REQUEST_URI"]), ENT_QUOTES, 'UTF-8');
	$items = getMenuItems($menuset, getMenuVisibility());
	$id = NULL;
	foreach ($items as $item) {
		$array = getItemTitleAndURL($item);
		if($currentpageURL == $array['url']) {
			$id = $item['id'];
			break;
		}
	}
	return $id;
}

function getMenumanagerPredicessor($menuset='default') {
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)==0) return NULL;
	$id = getCurrentMenuItem();
	if (empty($id)) return NULL;
	$sortorder = $items[$id]['sort_order'];
	$order = explode('-', $sortorder);
	$next = array_pop($order) - 1;
	if ($next < 0) return NULL;
	array_push($order, sprintf('%03u',$next));
	$sortorder = implode('-', $order);
	foreach ($items as $item) {
		if ($item['sort_order'] == $sortorder) {
			return getItemTitleAndURL($item);
		}
	}
	return NULL;
}

function printMenumanagerPrevLink($text, $menuset='default', $title=NULL, $class=NULL, $id=NULL) {
	$itemarray = getMenumanagerPredicessor($menuset);
	if (is_array($itemarray) && $itemarray['type']!='menulabel') {
		if (is_null($title)) $title = $itemarray['title'];
		printLink($itemarray['url'], $text, $title, $class, $id);
	} else {
		echo '<span class="disabledlink">'.htmlspecialchars($text).'"</span>';
	}
}

function getMenumanagerSuccessor($menuset='default') {
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)==0) return NULL;
	$id = getCurrentMenuItem();
	if (empty($id)) return NULL;
	$sortorder = $items[$id]['sort_order'];
	$order = explode('-', $sortorder);
	$next = array_pop($order) + 1;
	array_push($order, sprintf('%03u',$next));
	$sortorder = implode('-', $order);
	foreach ($items as $item) {
		if ($item['sort_order'] == $sortorder) {
			return getItemTitleAndURL($item);
		}
	}
	return NULL;
}

function printMenumanagerNextLink($text, $menuset='default', $title=NULL, $class=NULL, $id=NULL) {
	$itemarray = getMenumanagerSuccessor($menuset);
	if (is_array($itemarray) && $itemarray['type']!='menulabel') {
		if (is_null($title)) $title = $itemarray['title'];
		printLink($itemarray['url'], $text, $title, $class, $id);
	} else {
		echo '<span class="disabledlink">'.htmlspecialchars($text).'"</span>';
	}
}

function printMenumanagerBreadcrumb($menuset='default', $before='', $between=' | ', $after=' | ') {
	echo $before;
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)>0){
		$id = getCurrentMenuItem();
		if ($id) {
			$sortorder = $items[$id]['sort_order'];
			$order = explode('-', $sortorder);
			array_pop($order);
			$look = array();
			while (count($order) > 0) {
				$look[] = implode('-', $order);
				array_pop($order);
			}
			$parents = array();
			foreach ($items as $item) {
				foreach ($look as $key=>$see) {
					if ($see == $item['sort_order']) {
						$parents[] = $item;
						unset($look[$key]);
						break;
					}
				}
			}
			if (!empty($parents)) sortMultiArray($parents, 'sort_order', $descending=false, $natsort=false, $case_sensitive=false);
			$i = 0;
			foreach ($parents as $item) {
				if ($i > 0) echo $between;
				$itemarray = getItemTitleAndURL($item);
				if ($item['type']=='menulabel') {
					echo htmlspecialchars($itemarray['title']);
				} else {
					printLink($itemarray['url'], $itemarray['title'], $itemarray['title']);
				}
				$i++;
			}
		}
	}
	echo $after;
}

/**
 * Prints a context sensitive menu of all pages as a unordered html list
 *
 * @param string $menuset the menu tree to output
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"omit-top" only sub level pages
 * 												"list-sub" lists only the current pages direct offspring
 * @param string $css_id CSS id of the top level list
 * @param string $css_class_topactive class of the active item in the top level list
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $$css_class_active CSS class of the sub level list(s)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" (default) if you don't use it, it is not printed then.
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @return string
 */
function printCustomMenu($menuset='default', $option='list',$css_id='',$css_class_topactive='',$css_class='',$css_class_active='',$showsubs=0) {
	global $_zp_loggedin, $_zp_gallery_page, $_zp_current_zenpage_page, $_zp_current_category;
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_class_topactive != "") { $css_class_topactive = " class='".$css_class_topactive."'"; }
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($css_class_active != "") { $css_class_active = " class='".$css_class_active."'"; }
	if ($showsubs === true) $showsubs = 9999999999;

	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)==0) return; // nothing to do
	echo "<ul$css_id>";
	$id = getCurrentMenuItem();
	$sortorder = $items[$id]['sort_order'];
	
	$baseindent = max(1,count(explode("-", $sortorder)));
	$indent = 1;
	$open = array($indent=>0);
	$pageid = $id;
	$parents = array(NULL);
	$order = explode('-', $sortorder);
	$mylevel = count($order);
	$myparentsort = array_shift($order);
	
	for ($c=0; $c<=$mylevel; $c++) {
		$parents[$c] = NULL;
	}
	foreach ($items as $item) {
		$itemarray = getItemTitleAndURL($item);
		$itemURL = $itemarray['url'];
		$itemtitle = $itemarray['title'];
		$level = max(1,count(explode('-', $item['sort_order'])));
		$process = (($level <= $showsubs && $option == "list") // user wants all the pages whose level is <= to the parameter
								|| ($option == 'list' || $option == 'list-top') && $level==1 // show the top level
								|| (($option == 'list' || ($option == 'omit-top' && $level>1))
										&& (($item['id'] == $pageid) // current page
											|| ($item['parentid'] == $pageid) // offspring of current page
											|| ($level <= $mylevel && $level > 1 && strpos($item['sort_order'], $myparentsort) === 0)) // direct ancestor
									)
								|| ($option == 'list-sub'
										&& ($item['parentid']==$pageid) // offspring of the current page
									 )
								);
		if ($process) {
			if ($level > $indent) {
				echo "\n".str_pad("\t",$indent,"\t")."<ul$css_class>\n";
				$indent++;
				$parents[$indent] = NULL;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				$parents[$indent] = NULL;
				while ($indent > $level) {
					if ($open[$indent]) {
						$open[$indent]--;
						echo "</li>\n";
					}
					$indent--;
					echo str_pad("\t",$indent,"\t")."</ul>\n";
				}
			} else { // level == indent, have not changed
				if ($open[$indent]) { // level = indent
					echo str_pad("\t",$indent,"\t")."</li>\n";
					$open[$indent]--;
				} else {
					echo "\n";
				}
			}
	
			if ($open[$indent]) { // close an open LI if it exists
				echo "</li>\n";
				$open[$indent]--;
			}
			
			echo str_pad("\t",$indent-1,"\t");
			$open[$indent]++;
			$parents[$indent] = $item['id'];
			if($level == 1) { // top level
				$class = $css_class_topactive;
			} else {
				$class = $css_class_active;
			}
			if ($item['id'] == $id) {
				echo "<li $class>".$itemtitle; 
			} else {
				if (isnull($itemURL)) {
					echo '<li class="'.$item['type'].'">'.$itemtitle;
				} else {
					echo '<li class="'.$item['type'].'"><a href="'.$itemURL.'" title="'.strip_tags($itemtitle).'">"'.$itemtitle.'</a>';
				}
			}
			
		}
	}
	// cleanup any hanging list elements
	while ($indent > 1) {
		if ($open[$indent]) {
			echo "</li>\n";
			$open[$indent]--;
		}
		$indent--;
		echo str_pad("\t",$indent,"\t")."</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
		$open[$indent]--;
	} else {
		echo "\n";
	}
	echo "</ul>\n";
}
?>