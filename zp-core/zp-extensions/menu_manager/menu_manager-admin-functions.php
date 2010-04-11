<?php
/*******************************
 * Menu manager admin functions
 *******************************/

/**
 * Creates the db table for the menu (probably needs to be able to use different tables to use several custom menues)
 *
 */
function createTable() {
	$collation = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci';
	$db_schema = "CREATE TABLE IF NOT EXISTS ".prefix('menu')." (
	`id` int(11) unsigned NOT NULL auto_increment,
	`parentid` int(11) unsigned NOT NULL,
	`title` text,
	`link` varchar(255) NOT NULL,
	`type` varchar(16) NOT NULL,
	`sort_order`varchar(48) NOT NULL default '',
	`show` int(1) unsigned NOT NULL default '1',
	`menuset` varchar(32) NOT NULL,
	PRIMARY KEY  (`id`),
	UNIQUE (`type`,`link`, `menuset`)
	) $collation;";
	query($db_schema);
}

/**
 * Updates the sortorder of the pages list in the database
 *
 */
function updateItemsSortorder() {
	if(!empty($_POST['order'])) { // if someone didn't sort anything there are no values!
		$orderarray = explode("&",$_POST['order']);
		$parents = array('NULL');
		foreach($orderarray as $order) {
			$id = substr(strstr($order,"="),1);
			// clear out unnecessary info from the submitted data string
			$sortstring = strtr($order, array("left-to-right[" => "", "][id]=$id" => "", "][children][" => "-"));
			$sortlevelex = explode("-",$sortstring);
			$sortstring = '';
			//regenerate the sort key in connical form
			foreach($sortlevelex as $sortex) {
				$sort = sprintf('%03u', $sortex);
				$sortstring .= $sort.'-';
			}
			$sortstring = substr($sortstring, 0, -1);
			// now fix the parent ID and update the DB record
			$level = count($sortlevelex);
			$parents[$level] = $id;
			$myparent = $parents[$level-1];
			$sql = "UPDATE " . prefix('menu') . " SET `sort_order` = '".$sortstring."', `parentid`= ".$myparent." WHERE `id`=" . $id;
			query($sql);
		}
	}
	echo "<br clear: all><p class='messagebox' id='fade-message'>".gettext("Sort order saved.")."</p>";
}

/**
 * Prints the table part of a single page item for the sortable pages list
 *
 * @param object $page The array containing the single page
 * @param bool $flag set to true to flag the element as having a problem with nesting level
 */
function printItemsListTable($item, $flag) {
	$gallery = new Gallery();
	if ($flag) {
		$img = '../../images/drag_handle_flag.png';
	} else {
		$img = '../../images/drag_handle.png';
	}
	?>
<table class='bordered2'>
	<tr>
		<td class='sort-handle' style="padding-bottom: 15px;">
			<img src="<?php echo $img; ?>" style="position: relative; top: 7px; margin-right: 4px; width: 14px; height: 14px" />
			<?php
			printItemEditLink($item); 
			$array = getItemTitleAndURL($item);
			?>
		</td>
	
		<td class="icons3"><?php echo $array['name']; ?></td>
		<td class="icons3"><em><?php echo $item['type']; ?></em></td>
				
		<td class="icons">
		<?php
		if($item['show'] === '1') {
			?>
			<a href="menu_tab.php?publish&amp;id=<?php echo $item['id']."&amp;show=0"; ?>"><img src="../../images/pass.png"	alt="<?php echo gettext('show/hide'); ?>" title="<?php echo gettext('show/hide'); ?>" />	</a>
			<?php
		} else {
			?>
			<a href="menu_tab.php?publish&amp;id=<?php echo $item['id']."&amp;show=1"; ?>"><img src="../../images/action.png"	alt="<?php echo gettext('show/hide'); ?>" title="<?php echo gettext('show/hide'); ?>" />	</a>
			<?php
		}
		?>
	</td>
		<td class="icons">
			<a href="<?php echo $array['url']; ?>">
			<img src="../../images/view.png" alt="<?php echo gettext('view'); ?>" title="<?php echo gettext('view'); ?>" /></a>						
		</td>
		<td class="icons">
			<a href="menu_tab.php?delete&amp;id=<?php echo $item['id']; ?>">
			<img src="../../images/fail.png" alt="<?php echo gettext('delete'); ?>" title="<?php echo gettext('delete'); ?>" /></a>		
		</td>
	</tr>
</table>
	<?php
}


/**
 * Prints the sortable pages list
 * returns true if nesting levels exceede the database container
 *
 * @param array $pages The array containing all pages
 *
 * @return bool
 */
function printItemsList($items) {
	$indent = 1;
	$open = array(1=>0);
	$rslt = false;
	foreach ($items as $item) {
		$order = explode('-', $item['sort_order']);
		$level = max(1,count($order));
		if ($toodeep = $level>1 && $order[$level-1] === '') {
			$rslt = true;
		}
		if ($level > $indent) {
			echo "\n".str_pad("\t",$indent,"\t")."<ul class=\"page-list\">\n";
			$indent++;
			$open[$indent] = 0;
		} else if ($level < $indent) {
			while ($indent > $level) {
				$open[$indent]--;
				$indent--;
				echo "</li>\n".str_pad("\t",$indent,"\t")."</ul>\n";
			}
		} else { // indent == level
			if ($open[$indent]) {
				echo str_pad("\t",$indent,"\t")."</li>\n";
				$open[$indent]--;
			} else {
				echo "\n";
			}
		}
		if ($open[$indent]) {
			echo str_pad("\t",$indent,"\t")."</li>\n";
			$open[$indent]--;
		}
		echo str_pad("\t",$indent-1,"\t")."<li id=\"".$item['id']."\" class=\"clear-element page-item1 left\">";
		echo printItemsListTable($item, $toodeep);
		$open[$indent]++;
	}
	while ($indent > 1) {
		echo "</li>\n";
		$open[$indent]--;
		$indent--;
		echo str_pad("\t",$indent,"\t")."</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
	} else {
		echo "\n";
	}
	return $rslt;
}



/**
 * Prints the link to the edit page of a menu item. For gallery and Zenpage items it links to their normal edit pages, for custom pages and custom links to menu specific edit page.
 *
 * @param array $item Array of the menu item
 */
function printItemEditLink($item) {
	$link = "";
	$array = getItemTitleAndURL($item);
	$title = $array['title']; 				
	switch($item['type']) {
		case "album":
			$link = '<a href="../../admin-edit.php?page=edit&album='.$item['link'].'">'.$title.'</a>';
			break;
		case "zenpagepage":
			$link = '<a href="../zenpage/admin-edit.php?page&titlelink='.$item['link'].'">'.$title.'</a>';
			break;
		case "zenpagecategory":
			$catid = getCategoryID($item['link']);
			$link = '<a href="../zenpage/admin-categories.php?edit&id='.$catid.'&tab=categories">'.$title.'</a>';
			break;
		default:
			$link = '<a href="menu_tab_edit.php?edit&amp;id='.$item['id']."&amp;type=".$item['type']."&amp;menuset=".zp_escape_string(checkChosenMenuset()).'">'.$title.'</a>';
			break;		
	}
	echo $link;
}

/**
 * Prints the item status selector to choose if all items or only hidden or visible should be listed
 *
 */
function printItemStatusDropdown() {
  $all="";
  $visible="";
  $hidden="";
  $status = checkChosenItemStatus();
  $menuset = checkChosenMenuset();
	?>
  <select name="ListBoxURL" id="ListBoxURL" size="1" onchange="window.location='?menuset=<?php echo urlencode($menuset); ?>&amp;visible='+$('#ListBoxURL').val()">
  <?php
  switch($status) {
  	case "hidden":
  		$hidden = 'selected="selected"';
  		break;
  	case "visible":
  		$visible = 'selected="selected"';
  		break;
  	default:
  		$all = 'selected="selected"';
  		break;
  }
 	echo "<option $all value='all'>".gettext("Hidden and visible items")."</option>\n";
 	echo "<option $visible value='visible'>".gettext("Visible items")."</option>\n";
 	echo "<option $hidden value='hidden'>".gettext("hidden items")."</option>\n";
	?>
	</select>
	<?php
}

/**
 * returns the menu set selector
 * @param string $active the active menu set
 *
 */
function getMenuSetSelector($active) {
	$menuset = checkChosenMenuset();
	$menusets = array($menuset => $menuset);
	$result = query_full_array("SELECT DISTINCT menuset FROM ".prefix('menu')." ORDER BY menuset");
	foreach ($result as $set) {
		$menusets[$set['menuset']] = $set['menuset'];
	}
	natsort($menusets);
	
	if($active) {
		$selector = '<select name="menuset" id="menuset" size="1" onchange="window.location=\'?menuset=\'+encodeURIComponent($(\'#menuset\').val())">'."\n";
	} else {
		$selector = '<select name="menuset" size="1">'."\n";
	}
  foreach($menusets as $set) {
  	if($menuset == $set) {
  		$selected = 'selected="selected"';
  	} else {
  		$selected = '';
  	}
 		$selector .= '<option '.$selected.' value="'.htmlspecialchars($set).'">'.htmlspecialchars($set)."</option>\n";
  }
  $selector .= "</select>\n";
  return $selector;
 }



/**
 * Sets a menu item to published/visible
 *
 * @param integer $id id of the item
 * @param string $show published status.
 * @param string $menuset chosen menu set
 */
function publishItem($id,$show,$menuset) {
	query("UPDATE ".prefix('menu')." SET `show` = '".$show."' WHERE id = ".$id,true." AND menuset = '".zp_escape_string($menuset)."'");
}

/**
 * adds (sub)albums to menu base with their gallery sorting order intact
 *
 * @param string $menuset chosen menu set
 * @param object $gallery a gallery object
 * @param int $id table id of the parent.
 * @param string $link folder name of the album
 * @param string $sort xxx-xxx-xxx style sort order for album
 */
function addSubalbumMenus($menuset, $gallery, $id, $link, $sort) {
	$album = new Album($gallery, $link);
	$show = $album->get('show');
	$title = $album->getTitle();
	$sql = "INSERT INTO ".prefix('menu')." (`link`,`type`,`show`,`menuset`,`sort_order`, `parentid`) ".
																				'VALUES ("'.zp_escape_string($link).'", "album", "'.$show.'","'.zp_escape_string($menuset).'", "'.$sort.'",'.$id.')';
	$result = query($sql, true);													
	if ($result) {
		$id = mysql_insert_id();
	} else {
		$result = query_single_row('SELECT `id` FROM'.prefix('menu').' WHERE `type`="album" AND `link`="'.zp_escape_string($link).'"');
		$id = $result['id'];																		
	}
	if (!$album->isDynamic()) {
		$albums = $album->getAlbums();
		foreach ($albums as $key=>$link) {
			addSubalbumMenus($menuset, $gallery, $id, $link, $sort.'-'.sprintf('%03u', $key));
		}
	}
}
/**
 * Adds album items for the menu
 */
function addalbumsToDatabase($menuset) {
	$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
	$result = query($sql);
	$albumbase = mysql_result($result, 0);
	$gallery = new Gallery();
	$albums = $gallery->getAlbums();
	foreach ($albums as $key=>$link) {
		addSubalbumMenus($menuset, $gallery, 0, $link, sprintf('%03u', $key+$albumbase));
	}
}
/**
 * Adds Zenpage pages to the menu set 
  * @param string $menuset chosen menu set
 */
function addPagesToDatabase($menuset) {
	$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
	$result = query($sql);
	$pagebase = mysql_result($result, 0);
	$parents = array(0);
	$result = query_full_array("SELECT `titlelink`, `show`, `sort_order` FROM ".prefix('zenpage_pages')." ORDER BY sort_order");
	foreach($result as $key=>$item) {
		$sorts = explode('-',$item['sort_order']);
		$level = count($sorts);
		$sorts[0] = sprintf('%03u',$sorts[0]+$pagebase);
		$order = implode('-',$sorts);
		$show = $item['show'];
		$link = $item['titlelink'];
		$parent = $parents[$level-1];
		$sql = "INSERT INTO ".prefix('menu')." (`link`, `type`, `show`,`menuset`,`sort_order`, `parentid`) ".
				'VALUES ("'.zp_escape_string($link).'","zenpagepage",'.$show.',"'.zp_escape_string($menuset).'", "'.$order.'",'.$parent.')';
		if (query($sql, true)) {
			$id = mysql_insert_id();
		} else {
			$rslt = query_single_row('SELECT `id` FROM'.prefix('menu').' WHERE `type`="zenpagepage" AND `link`="'.$link.'"');
			$id = $rslt['id'];																		
		}
		$parents[$level] =$id;
	}
}
/**
 * Adds Zenpage news categories to the menu set 
 * @param string $menuset chosen menu set
 */
function addCategoriesToDatabase($menuset) {
	$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
	$result = query($sql);
	$categorybase = mysql_result($result, 0);
	$result = query_full_array("SELECT cat_link FROM ".prefix('zenpage_news_categories')." ORDER BY cat_name");
	foreach($result as $key=>$item) {
		$order = sprintf('%03u',$key+$categorybase);
		$link = $item['cat_link'];
		$sql = "INSERT INTO ".prefix('menu')." (`link`, `type`, `show`,`menuset`,`sort_order`) ".
										'VALUES ("'.zp_escape_string($link).'","zenpagecategory", 1,"'.zp_escape_string($menuset).'","'.$order.'")';
		query($sql, true);
	}
}


/********************************************************************
 * FUNCTIONS FOR THE SELECTORS ON THE "ADD MENU ITEM" Page
*********************************************************************/

/**
 * Adds an menu item set via POST
 *
 * @return array
 */
function addItem() {
	$menuset = checkChosenMenuset();
	$result['type'] = sanitize($_POST['type']);
	$result['show'] = getCheckboxState('show');
	$result['id'] = 0;
	//echo "<pre>"; print_r($_POST); echo "</pre>"; // for debugging
	switch($result['type']) {
		case 'all_items':
			addAlbumsToDatabase($menuset);
			query("INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
						"VALUES ('".gettext('Home')."', '".WEBPATH.'/'.	"','galleryindex','1','".zp_escape_string($menuset)."','000')",true);
			if(getOption('zp_plugin_zenpage')) {
				addPagesToDatabase($menuset);
				addCategoriesToDatabase($menuset);
				query("INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
							"VALUES ('".gettext('News index')."', '".rewrite_path(ZENPAGE_NEWS,'?p='.ZENPAGE_NEWS).	"','zenpagenewsindex','1','".zp_escape_string($menuset)."','001')",true);
			}
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenphoto objects added.")."</p>";
			return NULL;
		case 'all_albums':
			addAlbumsToDatabase($menuset);
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all albums added.")."</p>";
			return NULL;
		case 'all_zenpagepages':
			addPagesToDatabase($menuset);
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenpage pages added.")."</p>";
			return NULL;
		case 'all_zenpagecategories':
			addCategoriesToDatabase($menuset);
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenpage categories added.")."</p>";
			return NULL;
		case 'album':
			$result['title'] = NULL;
			$result['link'] = sanitize($_POST['albumselect']);
			if(empty($result['link'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to select an album.")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Album menu item <em>%s</em> added"),$result['link']);
			break;
		case 'galleryindex':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = NULL;
			if(empty($result['title'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Gallery index menu item <em>%s</em> added"),$result['link']);
			break;
		case 'zenpagepage':
			$result['title'] = NULL;
			$result['link'] = sanitize($_POST['pageselect']);
			if(empty($result['link'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>link</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Zenpage page menu item <em>%s</em> added"),$result['link']);
			break;
		case 'zenpagenewsindex':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = NULL;
			if(empty($result['title'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Zenpage news index menu item <em>%s</em> added"),$result['link']);
			break;
		case 'zenpagecategory':
			$result['title'] = NULL;
			$result['link'] = sanitize($_POST['categoryselect']);
			if(empty($result['link'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>link</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Zenpage news category menu item <em>%s</em> added"),$result['link']);
			break;
		case 'custompage':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = sanitize($_POST['custompageselect']);
			if(empty($result['title'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Custom page menu item <em>%s</em> added"),$result['link']);
			break;
		case 'customlink':
			$result['title'] = process_language_string_save("title",2);
			if(empty($result['title'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			if(empty($_POST['link'])) {
				$result['link'] = seoFriendly(get_language_string($title));
			} else {
				$result['link'] = sanitize($_POST['link']);
			}
			$successmsg = sprintf(gettext("Custom page menu item <em>%s</em> added"),$result['link']);
			break;
		case 'menulabel':
			$result['title'] = process_language_string_save("title",2);
			if(empty($result['title'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$result['link'] = md5($result['title']);
			$successmsg = gettext("Custom label added");
			break;
	}
	$sql = "INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
						"VALUES ('".zp_escape_string($result['title']).
						"', '".zp_escape_string($result['link']).
						"','".zp_escape_string($result['type'])."','".$result['show'].
						"','".zp_escape_string($menuset)."','000')";
	if (query($sql, true)) {
		echo "<p class='messagebox' id='fade-message'>".$successmsg."</p>"; 
		//echo "<pre>"; print_r($result); echo "</pre>";
		$result['id'] =  mysql_insert_id();
		return $result;
	} else {
		if (empty($result['link'])) {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext('A <em>%1$s</em> item already exists in <em>%2$s</em>!'),$result['type'],$menuset)."</p>";
		} else {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext('A <em>%1$s</em> item with the link <em>%2$s</em> already exists in <em>%3$s</em>!'),$result['type'],$result['link'],$menuset)."</p>";
		}
		return NULL;
	}
}

/**
 * Updates a menu item (custom link, custom page only) set via POST
 *
 */
function updateItem() {
	$menuset = checkChosenMenuset();
	$result['id'] = sanitize($_POST['id']);
	$result['show'] = getCheckboxState('show');
	$result['type'] = sanitize($_POST['type']);
	$result['title'] = process_language_string_save("title",2);
	if (isset($_POST['link'])) {
		$result['link'] = sanitize($_POST['link']);
	} else {
		$result['link'] = '';
	}
	// update the category in the category table
	if(query("UPDATE ".prefix('menu')." SET title = '".	zp_escape_string($result['title']).
						"',link='".zp_escape_string($result['link']).
						"',type='".zp_escape_string($result['type'])."', `show`= '".zp_escape_string($result['show']).
						"',menuset='".zp_escape_string($menuset).						
						"' WHERE `id`=".zp_escape_string($result['id']),true)) {
		
		if(isset($_POST['title']) && empty($result['title'])) {
			echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
		} else if(isset($_POST['link']) && empty($result['link'])) {
			echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>link</strong>!")."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu item updated!")."</p>";
		}
	} else {
		if (empty($result['link'])) {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext('A <em>%1$s</em> item already exists in <em>%2$s</em>!'),$result['type'],$menuset)."</p>";
		} else {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext('A <em>%1$s</em> item with the link <em>%2$s</em> already exists in <em>%3$s</em>!'),$result['type'],$result['link'],$menuset)."</p>";
		}
		return NULL;
	}
	return $result;
}

/**
 * Deletes a menu item set via GET
 *
 */
function deleteItem() {
  if(isset($_GET['delete'])) {
    $delete = zp_escape_string(sanitize($_GET['delete'],3));
    query("DELETE FROM ".prefix('menu')." WHERE menuset = '".zp_escape_string($menuset)."' AND id = '{$delete}'");
    echo "<p class='messagebox' id='fade-message'>".gettext("Custom menu item successfully deleted!")."</p>";
  }
}

/**
 * Prints all albums of the Zenphoto gallery as a partial drop down menu (<option></option> parts).
 * 
 * @return string
 */

function printAlbumsSelector() {
	global $_zp_gallery;
	$albumlist;
	genAlbumUploadList($albumlist);
		?>
	<select id="albumselector" name="albumselect">
	<?php
	foreach($albumlist as $key => $value) {
		$albumobj = new Album($_zp_gallery,$key);
		$albumname = $albumobj->name;
		$level = substr_count($albumname,"/");
		$arrow = "";
		for($count = 1; $count <= $level; $count++) {
			$arrow .= "&raquo; ";
		}
		echo "<option value='".pathurlencode($albumobj->name)."'>";
		echo $arrow.$albumobj->getTitle().unpublishedZenphotoItemCheck($albumobj)."</option>";
	}
	?>
	</select>
	<?php
}

/**
 	* Prints all available pages in Zenpage
 	* 
  * @return string
 	*/
function printZenpagePagesSelector() {
	global $_zp_gallery;
	?>
	<select id="pageselector" name="pageselect">
	<?php
	$pages = getPages(false);
	foreach ($pages as $key=>$page) {
		$pageobj = new ZenpagePage($page['titlelink']);
		$level = substr_count($pageobj->getSortOrder(),"-");
		$arrow = "";
		for($count = 1; $count <= $level; $count++) {
			$arrow .= "&raquo; ";
		}
		echo "<option value='".urlencode($pageobj->getTitlelink())."'>";
		echo $arrow.$pageobj->getTitle().unpublishedZenphotoItemCheck($pageobj)."</option>";
	}
	?>
	</select>
	<?php
}


/**
 	* Prints all available articles or categories in Zenpage
  *
 	* @return string
 	*/
function printZenpageNewsCategorySelector() {
	global $_zp_gallery;
	?>
<select id="categoryselector" name="categoryselect">
<?php
	$cats = getAllCategories();
	foreach($cats  as $cat) {
		echo "<option value='".urlencode($cat['cat_link'])."'>";
		echo get_language_string($cat['cat_name'])."</option>";
	}
?>
</select>
<?php
}
/**
 * Prints the selector for custom pages
 *
 * @return string
 */
function printCustomPageSelector($current) {
	$gallery = new Gallery();
	?>
	<select id="custompageselector" name="custompageselect">
		<?php
		$curdir = getcwd();
		$themename = $gallery->getCurrentTheme();
		$root = SERVERPATH.'/'.THEMEFOLDER.'/'.$themename.'/';
		chdir($root);
		$filelist = safe_glob('*.php');
		$list = array();
		foreach($filelist as $file) {
			$list[] = str_replace('.php', '', filesystemToInternal($file));
		}
		$list = array_diff($list, standardScripts());
		generateListFromArray(array($current), $list, false, false);
		chdir($curdir);
		?>
	</select>
	<?php
}

/**
 * checks if a album or image is unpublished and returns a '*'
	*
  * @return string
 	*/
function unpublishedZenphotoItemCheck($obj,$dropdown=true) {
	if($obj->getShow() != "1") {
		$show = "*";
	} else {
		$show = "";
	}
	return $show;
}
?>