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
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
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
 * Gets the menu items 
 *
 * @param string $menuset the menu tree desired
 * @param string $visible
 * @return array
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
	$result = query_full_array("SELECT id, parentid, title, link, type, sort_order,`show`, menuset FROM ".prefix('menu').$where." ORDER BY sort_order", true, 'sort_order');
	$_menu_manager_items[$visible] = $result;
	return $_menu_manager_items[$visible];
}

/**
 * Gets a menu item by its id
 *
 * @param integer $id id of the item
 * @return array
 */
function getItem($id) {
	$menuset = checkChosenMenuset();
	$result = query_single_row("SELECT id, parentid, title, link, type, sort_order,`show`, menuset FROM ".prefix('menu')." WHERE menuset = '".zp_escape_string($menuset)."' AND id = ".$id);
	return $result;
}

/**
 * Checks which menu set is chosen via $_GET. If none is explicity chosen the "default" one (create initially) is used.
 *
 * @return string
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


/**
 * Checks if the menu item is set visible or not
 *
 * @return string
 */
function checkChosenItemStatus() {
  if(isset($_GET['visible'])) {
  	return sanitize($_GET['visible']);
  } else {
  	return 'all';
  }
}

/**
 * Gets the title, url and name of a menu item
 *
 * @return array
 */
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

/**
 * Gets the menu visibility
 * @return string
 */
function getMenuVisibility() {
	if(zp_loggedin(ZENPAGE_RIGHTS | VIEW_ALL_RIGHTS)) {
		return "all";
	} else {
		return "visible";
	}
}

/**
 * Returns the ID of the current menu item
 * @param string $menuset current menu set
 * @return int
 */
function getCurrentMenuItem($menuset='default') {
	$currentpageURL = htmlentities(urldecode($_SERVER["REQUEST_URI"]), ENT_QUOTES, 'UTF-8');
	$currentpageURL = str_replace('\\','/',$currentpageURL);
	if (substr($currentpageURL,-1) == '/') $currentpageURL = substr($currentpageURL, 0, -1);
	$items = getMenuItems($menuset, getMenuVisibility());
	$currentkey = NULL;
	foreach ($items as $key=>$item) {
		$checkitem = getItemTitleAndURL($item);
		if($currentpageURL == $checkitem['url']) {
			$currentkey = $key;
			break;
		}
	}
	return $currentkey;
}

/**
 * Returns the link to the predicessor of the current menu item
 * @param string $menuset current menu set
 * @return string
 */
function getMenumanagerPredicessor($menuset='default') {
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)==0) return NULL;
	$sortorder = getCurrentMenuItem();
	if (empty($sortorder)) return NULL;
	$shortorder = $order = explode('-', $sortorder);
	$next = array_pop($order);
	while ($next >= 0) {
		$order = $shortorder;
		array_push($order, sprintf('%03u',$next));
		$sortorder = implode('-', $order);
		if (array_key_exists($sortorder, $items) && $items[$sortorder]['type'] != 'menulabel') {	// omit the menulabels
			return getItemTitleAndURL($items[$sortorder]);
		}
		$next--;
	}
	return NULL;
}

/**
 * Prints the previous link of the current menu item
 * @param string  $text
 * @param string  $menuset
 * @param string  $title
 * @param string  $class
 * @param string  $id
 */
function printMenumanagerPrevLink($text, $menuset='default', $title=NULL, $class=NULL, $id=NULL) {
	$itemarray = getMenumanagerPredicessor($menuset);
	if (is_array($itemarray)) {
		if (is_null($title)) $title = $itemarray['title'];
		printLink($itemarray['url'], $text, $title, $class, $id);
	} else {
		echo '<span class="disabledlink">'.htmlspecialchars($text).'</span>';
	}
}

/**
 * Returns the successor link of the current menu item
 * @param string $menuset
 * @return string
 */
function getMenumanagerSuccessor($menuset='default') {
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)==0) return NULL;
	$sortorder = getCurrentMenuItem();
	if (empty($sortorder)) return NULL;
	$order = explode('-', $sortorder);
	$next = array_pop($order) + 1;
	$short_order = $order;
	array_push($order, sprintf('%03u',$next));
	$sortorder = implode('-', $order);
	while ($next <= 999) {
		$order = $short_order;
		array_push($order, sprintf('%03u',$next));
		$sortorder = implode('-', $order);
		if (array_key_exists($sortorder, $items)) {
			if ($items[$sortorder]['type'] != 'menulabel') {	// omit the menulabels
				return getItemTitleAndURL($items[$sortorder]);
			}
		}
		$next++;
	}
	return NULL;
}

/**
 * Gets the link to the next menu item
 * @param string $text
 * @param string $menuset current menu set
 * @param string $title
 * @param string $class
 * @param string $id
 */
function printMenumanagerNextLink($text, $menuset='default', $title=NULL, $class=NULL, $id=NULL) {
	$itemarray = getMenumanagerSuccessor($menuset);
	if (is_array($itemarray)) {
		if (is_null($title)) $title = $itemarray['title'];
		printLink($itemarray['url'], $text, $title, $class, $id);
	} else {
		echo '<span class="disabledlink">'.htmlspecialchars($text).'</span>';
	}
}

/**
 * Prints the breadcrumbs of the current page
 * @param string $menuset current menu set
 * @param string $before before text
 * @param string $between between text
 * @param string $after after text
 */
function printMenumanagerBreadcrumb($menuset='default', $before='', $between=' | ', $after=' | ') {
	echo $before;
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)>0){
		$sortorder = getCurrentMenuItem();
		if ($sortorder) {
			$parents = array();
			$order = explode('-', $sortorder);
			array_pop($order);
			$look = array();
			while (count($order) > 0) {
				$look = implode('-', $order);
				array_pop($order);
				if (array_key_exists($look, $items)) {
					array_unshift($parents, $items[$look]);
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
 * Returns the menu item corresponding to $link
 * @param string $link
 * @param string $menuset
 * @return array
 */
function getMenuFromLink($link, $menuset='default') {
	$link = str_replace('\\','/',$link);
	if (substr($link,-1) == '/') $link = substr($link, 0, -1);
	$items = getMenuItems($menuset, getMenuVisibility());
	foreach ($items as $item) {
		$itemarray = getItemTitleAndURL($item);
		if ($itemarray['url'] == $link) return $item;
	}
	return NULL;
}

/**
 * Returns true if the current menu item is a sub item of $link
 * @param string $link possible parent
 * @param string $menuset current menuset
 * @return bool
 */
function submenuOf($link, $menuset='default') {
	$link_menu = getMenuFromLink($link, $menuset);
	if (is_array($link_menu)) {
		$items = getMenuItems($menuset, getMenuVisibility());
		$current = getCurrentMenuItem();
		if (!is_null($current)) {
			$sortorder = $link_menu['sort_order'];
			if (strlen($current) > strlen($sortorder)) {		
				$p = strpos($current,$sortorder);
				return $p === 0;
			}
		}
	}
	return false;
}

/**
 * Creates a menu set from the items passed. But only if the menu set does not already exist
 * @param array $menuitems items for the menuset
 * 		array elements:
 * 			'type'=>menuset type
 * 			'title'=>title for the menu item
 * 			'link'=>URL or other data for the item link
 * 			'show'=>set to 1:"visible" or 0:"hidden", 
 * 			'nesting'=>nesting level of this item in the menu heirarchy
 * 			
 * @param string $menuset current menuset
 */
function createMenuIfNotExists($menuitems, $menuset='default') {
	$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
	$result = query($sql);
	if (mysql_result($result, 0)==0) {	// there was not an existing menu set
		require_once(dirname(__FILE__).'/menu_manager/menu_manager-admin-functions.php');
		$success = 1;
		$orders = array();
		foreach ($menuitems as $result) {
			if (array_key_exists('nesting',$result)) {
				$nesting = $result['nesting'];
			} else {
				$nesting = 0;
			}
			while ($nesting+1 < count($orders)) array_pop($orders);
			while ($nesting+1 > count($orders)) array_push($orders, -1);
			$result['id'] = 0;
			$type = $result['type'];
			switch($type) {
				case 'all_items':
					$orders[$nesting]++;
					query("INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
								"VALUES ('".gettext('Home')."', '".WEBPATH.'/'.	"','galleryindex','1','".zp_escape_string($menuset).'","'.$orders.'"',true);
					$orders[$nesting] = addAlbumsToDatabase($menuset,$orders);
					if(getOption('zp_plugin_zenpage')) {
						$orders[$nesting]++;
						query("INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
									"VALUES ('".gettext('News index')."', '".rewrite_path(ZENPAGE_NEWS,'?p='.ZENPAGE_NEWS).	"','zenpagenewsindex','1','".zp_escape_string($menuset).'","'.sprintf('%3u',$base+1).'"',true);
						$orders[$nesting] = addPagesToDatabase($menuset, $orders)+1;
						$orders[$nesting] = addCategoriesToDatabase($menuset,$orders);
					}
					$type = false;
					break;
				case 'all_albums':
					$orders[$nesting]++;
					$orders[$nesting] = addAlbumsToDatabase($menuset,$orders);
					$type = false;
					break;
				case 'all_zenpagepages':
					$orders[$nesting]++;
					$orders[$nesting] = addPagesToDatabase($menuset,$orders);
					$type = false;
					break;
				case 'all_zenpagecategories':
					$orders[$nesting]++;
					$orders[$nesting] = addCategoriesToDatabase($menuset,$orders);
					$type = false;
					break;
				case 'album':
					$result['title'] = NULL;
					if(empty($result['link'])) {
						$success = -1;
					}
					break;
				case 'galleryindex':
					$result['link'] = NULL;
					if(empty($result['title'])) {
						$success = -1;
					}
					break;
				case 'zenpagepage':
					$result['title'] = NULL;
					if(empty($result['link'])) {
						$success = -1;
					}
					break;
				case 'zenpagenewsindex':
					$result['link'] = NULL;
					if(empty($result['title'])) {
						$success = -1;
					}
					break;
				case 'zenpagecategory':
					$result['title'] = NULL;
					if(empty($result['link'])) {
						$success = -1;
					}
					break;
				case 'custompage':
					if(empty($result['title']) || empty($result['link'])) {
						$success = -1;;
					}
					break;
				case 'customlink':
					if(empty($result['title'])) {
						$success = -1;
					} else if(empty($result['link'])) {
						$result['link'] = seoFriendly(get_language_string($result['title']));
					}
					break;
				case 'menulabel':
					if(empty($result['title'])) {
						$success = -1;
					}
					$result['link'] = md5($result['title']);
					break;
			}
			if ($success >0 && $type) {
				$orders[$nesting]++;
				$sort_order = '';
				for ($i=0;$i<count($orders);$i++) {
					$sort_order .= sprintf('%03u',$orders[$i]).'-';
				}
				$sort_order = substr($sort_order,0,-1);
				$sql = "INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
									"VALUES ('".zp_escape_string($result['title']).
									"', '".zp_escape_string($result['link']).
									"','".zp_escape_string($result['type'])."','".$result['show'].
									"','".zp_escape_string($menuset)."','$sort_order')";
				if (!query($sql, true)) {
					$success = -2;
				}
			}
		}
	} else {
		$success = 0;
	}
	return $success;
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
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($showsubs === true) $showsubs = 9999999999;

	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items)==0) return; // nothing to do
	echo "<ul$css_id>";
	$sortorder = getCurrentMenuItem();
	$pageid = $items[$sortorder]['id'];
	
	$baseindent = max(1,count(explode("-", $sortorder)));
	$indent = 1;
	$open = array($indent=>0);
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
			if ($item['id'] == $pageid) {
				if($level == 1) { // top level
					$class = $css_class_topactive;
				} else {
					$class = $css_class_active;
				}
				echo '<li class="'.trim($item['type'].' '.$class).'">'.$itemtitle; 
			} else {
				if (is_null($itemURL)) {
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