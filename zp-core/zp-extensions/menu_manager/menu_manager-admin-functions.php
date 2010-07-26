<?php
/*******************************
 * Menu manager admin functions
 *******************************/

/**
 * Updates the sortorder of the pages list in the database
 *
 */
function updateItemsSortorder(&$reports) {
	if(!empty($_POST['order'])) { // if someone didn't sort anything there are no values!
		$orderarray = explode("&",$_POST['order']);
		$parents = array('NULL');
		foreach($orderarray as $order) {
			$id = str_replace('id_','',substr(strstr($order,"="),1));
			
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
	$reports[] =  "<br clear: all><p class='messagebox' id='fade-message'>".gettext("Sort order saved.")."</p>";
	}
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
			<img src="<?php echo $img; ?>" style="position: relative; top: 7px; margin-right: 4px; width: 14px; height: 14px" alt="" />
			<?php
			$array = getItemTitleAndURL($item);
			printItemEditLink($item); 
			?>
		</td>
		<td class="icons3"><?php echo htmlspecialchars($array['name']); ?></td>
		<td class="icons3"><em><?php echo $item['type']; ?></em></td>
		<td  class="icons">
			<?php
			if ($array['protected']) {
				?> 
				<img src="../../images/lock_2.png" alt="<?php echo gettext("The object of this menu is under password protection"); ?>" title="<?php echo gettext("The object of this menu is under password protection"); ?>" />
				<?php
			} else {
				?>
				<img src="../../images/place_holder_icon.png" alt="<?php echo gettext("The object of this menu is under password protection"); ?>" title="<?php echo gettext("The object of this menu is under password protection"); ?>" />
				<?php
			}
			?>
		</td>
		<td class="icons">
		<?php
		if($item['show'] === '1') {
			?>
			<a href="menu_tab.php?publish&amp;id=<?php echo $item['id']."&amp;show=0"; ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('update_menu')?>"><img src="../../images/pass.png"	alt="<?php echo gettext('show/hide'); ?>" title="<?php echo gettext('show/hide'); ?>" />	</a>
			<?php
		} else {
			?>
			<a href="menu_tab.php?publish&amp;id=<?php echo $item['id']."&amp;show=1"; ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('update_menu')?>"><img src="../../images/action.png"	alt="<?php echo gettext('show/hide'); ?>" title="<?php echo gettext('show/hide'); ?>" />	</a>
			<?php
		}
		?>
	</td>
		<td class="icons">
			<?php
			switch ($item['type']) {
				default:
					if (!empty($array['url'])) {
						?>
						<a href="<?php echo $array['url']; ?>">
						<img src="../../images/view.png" alt="<?php echo gettext('view'); ?>" title="<?php echo gettext('view'); ?>" /></a>	
						<?php 
						break;
					}
				case 'menulabel':
				case 'menufunction':
				case 'html':
					?>
					<img src="../../images/icon_inactive.png" alt="" title="" />
					<?php
					break;
			}
			?>					
		</td>
		<td class="icons">
			<a href="javascript:deleteMenuItem('menu_tab.php?delete&amp;id=<?php echo $item['id']; ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('delete_menu')?>','<?php printf(gettext('Ok to delete %s? This cannot be undone.'),htmlspecialchars($array['name'])); ?>');" >
			<img src="../../images/fail.png" alt="<?php echo gettext('delete'); ?>" title="<?php echo gettext('delete'); ?>" /></a>		
		</td>
		<td class="icons">
		<input type="checkbox" name="ids[]" value="<?php echo htmlspecialchars($item['id'],ENT_QUOTES); ?>" onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
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
		echo str_pad("\t",$indent-1,"\t")."<li id=\"id_".$item['id']."\" class=\"clear-element page-item1 left\">";
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
	if (is_null($array['title'])) {
		$title = '<span class="notebox">'.gettext('The target for this menu element no longer exists').'</span>';
	} else {
		$title = htmlspecialchars($array['title'],ENT_QUOTES);
	}				
	switch($item['type']) {
		case "album":
			$link = '<a href="../../admin-edit.php?page=edit&amp;album='.$item['link'].'">'.$title.'</a>';
			break;
		case "zenpagepage":
			$link = '<a href="../zenpage/admin-edit.php?page&amp;titlelink='.$item['link'].'">'.$title.'</a>';
			break;
		case "zenpagecategory":
			$catid = getCategoryID($item['link']);
			$link = '<a href="../zenpage/admin-categories.php?edit&amp;id='.$catid.'&amp;tab=categories">'.$title.'</a>';
			break;
		default:
			$link = '<a href="menu_tab_edit.php?edit&amp;id='.$item['id']."&amp;type=".$item['type']."&amp;menuset=".htmlspecialchars(checkChosenMenuset(),ENT_QUOTES).'">'.$title.'</a>';
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
	$menusets = array($menuset => $menuset,'default'=>'default');
	$result = query_full_array("SELECT DISTINCT menuset FROM ".prefix('menu')." ORDER BY menuset");
	foreach ($result as $set) {
		$menusets[$set['menuset']] = $set['menuset'];
	}
	natcasesort($menusets);
	
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
 		$selector .= '<option '.$selected.' value="'.htmlspecialchars($set,ENT_QUOTES).'">'.htmlspecialchars($set,ENT_QUOTES)."</option>\n";
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
	$sql = "INSERT INTO ".prefix('menu')." (`link`,`type`,`title`,`show`,`menuset`,`sort_order`, `parentid`) ".
																				'VALUES ("'.zp_escape_string($link).'", "album", "'.zp_escape_string($album->name).'", "'.$show.'","'.zp_escape_string($menuset).'", "'.$sort.'",'.$id.')';
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
 * Adds albums to the menu set. Returns the next sort order base
 * @param string $menuset current menu set
 * @param string $base starting "sort order"
 * @return int
 */
function addalbumsToDatabase($menuset, $base=NULL) {
	if (is_null($base)) {
		$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
		$result = query($sql);
		$albumbase = mysql_result($result, 0);
		$sortbase = '';
	} else {
		$albumbase = array_pop($base);
		$sortbase = '';
		for ($i=0;$i<count($base);$i++) {
			$sortbase .= sprintf('%03u',$base[$i]).'-';
		}
	}
	$result = $albumbase;
	$gallery = new Gallery();
	$albums = $gallery->getAlbums();
	foreach ($albums as $key=>$link) {
		addSubalbumMenus($menuset, $gallery, 0, $link, $sortbase.sprintf('%03u', $result = $key+$albumbase));
	}
	return $result;
}

/**
 * Adds Zenpage pages to the menu set 
 * @param string $menuset current menu set
 * @param int $pagebase starting "sort order"
 * @return int
 */
function addPagesToDatabase($menuset, $base=NULL) {
	if (is_null($base)) {
		$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
		$result = query($sql);
		$pagebase = mysql_result($result, 0);
		$sortbase = '';
	} else {
		$pagebase = array_pop($base);
		$sortbase = '';
		for ($i=0;$i<count($base);$i++) {
			$sortbase .= sprintf('%03u',$base[$i]).'-';
		}
	}
	$result = $pagebase;
	$parents = array(0);
	$result = query_full_array("SELECT `titlelink`, `show`, `sort_order` FROM ".prefix('zenpage_pages')." ORDER BY sort_order");
	foreach($result as $key=>$item) {
		$sorts = explode('-',$item['sort_order']);
		$level = count($sorts);
		$sorts[0] = sprintf('%03u',$result = $sorts[0]+$pagebase);
		$order = $sortbase.implode('-',$sorts);
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
	return $result;
}
/**
 * Adds Zenpage news categories to the menu set 
 * @param string $menuset chosen menu set
 */
function addCategoriesToDatabase($menuset, $base=NULL) {
	if (is_null($base)) {
		$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
		$result = query($sql);
		$categorybase = mysql_result($result, 0);
		$sortbase = '';
	} else {
		$categorybase = array_pop($base);
		$sortbase = '';
		for ($i=0;$i<count($base);$i++) {
			$sortbase .= sprintf('%03u',$base[$i]).'-';
		}
	}
	$result = $categorybase;
	$result = query_full_array("SELECT * FROM ".prefix('zenpage_news_categories')." ORDER BY cat_name");
	foreach($result as $key=>$item) {
		$order = $sortbase.sprintf('%03u',$result = $key+$categorybase);
		$link = $item['cat_link'];
		$sql = "INSERT INTO ".prefix('menu')." (`link`, `type`, `show`,`menuset`,`sort_order`) ".
										'VALUES ("'.zp_escape_string($link).'","zenpagecategory", 1,"'.zp_escape_string($menuset).'","'.$order.'")';
		query($sql, true);
	}
	return $result;
}


/********************************************************************
 * FUNCTIONS FOR THE SELECTORS ON THE "ADD MENU ITEM" Page
*********************************************************************/

/**
 * Adds an menu item set via POST
 *
 * @return array
 */
function addItem(&$reports) {
	$menuset = checkChosenMenuset();
	$result['type'] = sanitize($_POST['type']);
	$result['show'] = getCheckboxState('show');
	$result['include_li'] = getCheckboxState('include_li');
	$result['id'] = 0;
	//echo "<pre>"; print_r($_POST); echo "</pre>"; // for debugging
	switch($result['type']) {
		case 'all_items':
			query("INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
						"VALUES ('".gettext('Home')."', '".WEBPATH.'/'.	"','galleryindex','1','".zp_escape_string($menuset)."','000')",true);
			addAlbumsToDatabase($menuset);
			if(getOption('zp_plugin_zenpage')) {
				query("INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
							"VALUES ('".gettext('News index')."', '".rewrite_path(ZENPAGE_NEWS,'?p='.ZENPAGE_NEWS).	"','zenpagenewsindex','1','".zp_escape_string($menuset)."','001')",true);
				addPagesToDatabase($menuset);
				addCategoriesToDatabase($menuset);
			}
			$reports[] =  "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenphoto objects added.")."</p>";
			return NULL;
		case 'all_albums':
			addAlbumsToDatabase($menuset);
			$reports[] =  "<p class='messagebox' id='fade-message'>".gettext("Menu items for all albums added.")."</p>";
			return NULL;
		case 'all_zenpagepages':
			addPagesToDatabase($menuset);
			$reports[] =  "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenpage pages added.")."</p>";
			return NULL;
		case 'all_zenpagecategorys':
			addCategoriesToDatabase($menuset);
			$reports[] =  "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenpage categories added.")."</p>";
			return NULL;
		case 'album':
			$result['title'] = $result['link'] = sanitize($_POST['albumselect']);
			if(empty($result['link'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to select an album.")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Album menu item <em>%s</em> added"),$result['link']);
			break;
		case 'galleryindex':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = NULL;
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Gallery index menu item <em>%s</em> added"),$result['link']);
			break;
		case 'zenpagepage':
			$result['title'] = NULL;
			$result['link'] = sanitize($_POST['pageselect']);
			if(empty($result['link'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>link</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Zenpage page menu item <em>%s</em> added"),$result['link']);
			break;
		case 'zenpagenewsindex':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = NULL;
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Zenpage news index menu item <em>%s</em> added"),$result['link']);
			break;
		case 'zenpagecategory':
			$result['title'] = NULL;
			$result['link'] = sanitize($_POST['categoryselect']);
			if(empty($result['link'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>link</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Zenpage news category menu item <em>%s</em> added"),$result['link']);
			break;
		case 'custompage':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = sanitize($_POST['custompageselect']);
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Custom page menu item <em>%s</em> added"),$result['link']);
			break;
		case 'customlink':
			$result['title'] = process_language_string_save("title",2);
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$result['link'] = sanitize($_POST['link']);
			if (empty($result['link'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to provide a <strong>function</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Custom page menu item <em>%s</em> added"),$result['link']);
			break;
		case 'menulabel':
			$result['title'] = process_language_string_save("title",2);
			$result['link'] = NULL;
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$successmsg = gettext("Custom label added");
			break;
		case 'menufunction':
			$result['title'] = process_language_string_save("title",2);
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$result['link'] = sanitize($_POST['link'],0);
			if (empty($result['link'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to provide a <strong>function</strong>!")."</p>";
				return $result;
			}
			$successmsg = sprintf(gettext("Function  menu item <em>%s</em> added"),$result['link']);
			break;
		case 'html':
			$result['title'] = process_language_string_save("title",2);
			if(empty($result['title'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
				return $result;
			}
			$result['link'] = sanitize($_POST['link'],0);
			if (empty($result['link'])) {
				$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to provide a <strong>function</strong>!")."</p>";
				return $result;
			}
			$successmsg = gettext("Horizontal rule added");
			break;
		default:
			break;
			
	}
	$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
	$rslt = query($sql);
	$order = sprintf('%03u',mysql_result($rslt, 0));
	$sql = "INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`,`include_li`) ".
						"VALUES ('".zp_escape_string($result['title']).
						"', '".zp_escape_string($result['link']).
						"','".zp_escape_string($result['type'])."','".$result['show'].
						"','".zp_escape_string($menuset)."','".$order."',".$result['include_li'].")";
	if (query($sql, true)) {
		$reports[] =  "<p class='messagebox' id='fade-message'>".$successmsg."</p>"; 
		//echo "<pre>"; print_r($result); echo "</pre>";
		$result['id'] =  mysql_insert_id();
		return $result;
	} else {
		if (empty($result['link'])) {
			$reports[] =  "<p class='errorbox' id='fade-message'>".sprintf(gettext('A <em>%1$s</em> item already exists in <em>%2$s</em>!'),$result['type'],$menuset)."</p>";
		} else {
			$reports[] =  "<p class='errorbox' id='fade-message'>".sprintf(gettext('A <em>%1$s</em> item with the link <em>%2$s</em> already exists in <em>%3$s</em>!'),$result['type'],$result['link'],$menuset)."</p>";
		}
		return NULL;
	}
}

/**
 * Updates a menu item (custom link, custom page only) set via POST
 *
 */
function updateItem(&$reports) {
	$menuset = checkChosenMenuset();
	$result['id'] = sanitize($_POST['id']);
	$result['show'] = getCheckboxState('show');
	$result['type'] = sanitize($_POST['type']);
	$result['title'] = process_language_string_save("title",2);
	$result['include_li'] = getCheckboxState('include_li');
	if (isset($_POST['link'])) {
		$result['link'] = sanitize($_POST['link'],0);
	} else {
		$result['link'] = '';
	}
	// update the category in the category table
	if(query("UPDATE ".prefix('menu')." SET title = '".	zp_escape_string($result['title']).
						"',link='".zp_escape_string($result['link']).
						"',type='".zp_escape_string($result['type'])."', `show`= '".zp_escape_string($result['show']).
						"',menuset='".zp_escape_string($menuset).						
						"',include_li='".$result['include_li'].						
	"' WHERE `id`=".zp_escape_string($result['id']))) {
		
		if(isset($_POST['title']) && empty($result['title'])) {
			$reports[] = "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>title</strong>!")."</p>";
		} else if(isset($_POST['link']) && empty($result['link'])) {
			$reports[] =  "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your menu item a <strong>link</strong>!")."</p>";
		} else {
			$reports[] =  "<p class='messagebox' id='fade-message'>".gettext("Menu item updated!")."</p>";
		}
	}
	return $result;
}

/**
 * Deletes a menu item set via GET
 *
 */
function deleteItem(&$reports) {
  if(isset($_GET['delete'])) {
    $delete = sanitize_numeric($_GET['delete'],3);
    query("DELETE FROM ".prefix('menu')." WHERE `id`=$delete");
    $reports[] =  "<p class='messagebox' id='fade-message'>".gettext("Custom menu item successfully deleted!")."</p>";
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
		echo "<option value='".htmlspecialchars($albumobj->name)."'>";
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
		echo "<option value='".htmlspecialchars($pageobj->getTitlelink())."'>";
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
		echo "<option value='".htmlspecialchars($cat['cat_link'])."'>";
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
		generateListFromArray(array($current), $list, false, false);
		chdir($curdir);
		?>
	</select>
	<?php
}

/**
 * checks if a album or image is un-published and returns a '*'
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

/**
 * Processes the check box bulk actions
 *
 */
function processMenuBulkActions(&$reports) {
	if (isset($_POST['ids'])) {
		$action = sanitize($_POST['checkallaction']);
		$ids = $_POST['ids'];
		$total = count($ids);
		$message = NULL;
		if($action != 'noaction') {
			if ($total > 0) {
				$n = 0;
				switch($action) {
					case 'deleteall':
						$sql = "DELETE FROM ".prefix('menu')." WHERE ";
						$message = gettext('Selected items deleted');
						break;
					case 'showall':
						$sql = "UPDATE ".prefix('menu')." SET `show` = 1 WHERE ";
						$message = gettext('Selected items published');
						break;
					case 'hideall':
						$sql = "UPDATE ".prefix('menu')." SET `show` = 0 WHERE ";
						$message = gettext('Selected items unpublished');
						break;
				}
				foreach ($ids as $id) {
					$n++;
					$sql .= " id='".sanitize_numeric($id)."' ";
					if ($n < $total) $sql .= "OR ";
				}
				query($sql);
			}
			if(!is_null($message)) $reports[] = "<p class='messagebox fade-message'>".$message."</p>";
		}
	}
}
?>