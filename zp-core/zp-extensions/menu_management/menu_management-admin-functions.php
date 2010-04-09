<?php
/*******************
 * Menu management admin functions
 *******************/

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
		</td>
		<td style="text-align: left; width: 260px">
			<?php
			printItemEditLink($item); 
			$array = getItemTitleAndURL($item);
			?>
		</td>
	
		<td style="text-align: left; width: 460px"><?php echo $item['link']; ?></td>
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
 * Enter description here...
 *
 * @param unknown_type $item
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
		case "custompage":
			$link = '<a href="menu_tab_edit.php?id='.$item['id']."&amp;type=custompage&amp;menuset=".zp_escape_string(checkChosenMenuset()).'">'.$title.'</a>';
			break;
		case "customlink":
			$link = '<a href="menu_tab_edit.php?id='.$item['id']."&amp;type=customlink&amp;menuset=".zp_escape_string(checkChosenMenuset()).'">'.$title.'</a>';
			break;
		default: // items you can't edit like Zenpage news index or gallery index
			$link = $title;
			break;
		
	}
	echo $link;
}

/**
 * Enter description here...
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
 * Enter description here...
 *
 */
function printMenuSetSelector($active) {
	$menuset = checkChosenMenuset();
	$menusets = array($menuset => $menuset);
	$result = query_full_array("SELECT DISTINCT menuset FROM ".prefix('menu')." ORDER BY menuset");
	foreach ($result as $set) {
		$menusets[$set['menuset']] = $set['menuset'];
	}
	natsort($menusets);
	if($active) {
		?>
		<select name="menuset" id="menuset" size="1" onchange="window.location='?menuset='+encodeURIComponent($('#menuset').val())">
		<?php
	} else {
		?>
	  <select name="menuset" size="1">
	  <?php
	}
  foreach($menusets as $set) {
  	if($menuset == $set) {
  		$selected = 'selected="selected"';
  	} else {
  		$selected = '';
  	}
 		echo '<option '.$selected.' value="'.htmlspecialchars($set).'">'.htmlspecialchars($set)."</option>\n";
  }
	?>
 </select>
 <?php
}



/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @param unknown_type $show
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
	$sql = "INSERT INTO ".prefix('menu')." (`link`,`title`,`type`,`show`,`menuset`,`sort_order`, `parentid`) ".
																				'VALUES ("'.zp_escape_string($link).'", "'.zp_escape_string($title).'", "album", "'.$show.'","'.zp_escape_string($menuset).'", "'.$sort.'",'.$id.')';
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

function addPagesToDatabase($menuset) {
	$sql = "SELECT COUNT(id) FROM ". prefix('menu') .' WHERE menuset="'.zp_escape_string($menuset).'"';
	$result = query($sql);
	$pagebase = mysql_result($result, 0);
	$parents = array(0);
	$result = query_full_array("SELECT `title`, `titlelink`, `show`, `sort_order` FROM ".prefix('zenpage_pages')." ORDER BY sort_order");
	foreach($result as $key=>$item) {
		$sorts = explode('-',$item['sort_order']);
		$level = count($sorts);
		$sorts[0] = sprintf('%03u',$sorts[0]+$pagebase);
		$order = implode('-',$sorts);
		$show = $item['show'];
		$link = $item['titlelink'];
		$parent = $parents[$level-1];
		$title = $item['title'];
		$sql = "INSERT INTO ".prefix('menu')." (`link`, `title`, `type`, `show`,`menuset`,`sort_order`, `parentid`) ".
				'VALUES ("'.zp_escape_string($link).'","'.zp_escape_string($title).'", "zenpagepage",'.$show.',"'.zp_escape_string($menuset).'", "'.$order.'",'.$parent.')';
		if (query($sql, true)) {
			$id = mysql_insert_id();
		} else {
			$rslt = query_single_row('SELECT `id` FROM'.prefix('menu').' WHERE `type`="zenpagepage" AND `link`="'.$link.'"');
			$id = $rslt['id'];																		
		}
		$parents[$level] =$id;
	}
}

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
 * Enter description here...
 *
 * @return unknown
 */
function addItem() {
	$menuset = checkChosenMenuset();
	$result['type'] = sanitize($_POST['type']);
	switch ($result['type']) {
		case 'all_albums':
			addAlbumsToDatabase($menuset);
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all albums added.")."</p>";
			$result = NULL;
			break;
		case 'all_zenpagepages':
			addPagesToDatabase($menuset);
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenpage pages added.")."</p>";
			$result = NULL;
			break;
		case 'all_zenpagecategories':
			addCategoriesToDatabase($menuset);
			echo "<p class='messagebox' id='fade-message'>".gettext("Menu items for all Zenpage categories added.")."</p>";
			$result = NULL;
			break;
		default:
			$result['title'] = process_language_string_save("title",2);
			if(empty($_POST['link'])) {
				$result['link'] = seoFriendly(get_language_string($title));
			} else {
				$result['link'] = sanitize($_POST['link']);
			}
			$result['show'] = getCheckboxState('show');
			if(empty($result['title']) OR empty($result['link'])) {
				echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your custom menu item a <strong>title or link</strong>!")."</p>";
			} else {
				$sql = "INSERT INTO ".prefix('menu')." (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) ".
						"VALUES ('".zp_escape_string($result['title']).
						"', '".zp_escape_string($result['link']).
						"','".zp_escape_string($result['type'])."','".zp_escape_string($result['show']).
						"','".zp_escape_string($menuset)."','000')";
				if (query($sql, true)) {
					echo "<p class='messagebox' id='fade-message'>".sprintf(gettext("Custom menu item <em>%s</em> added"),$result['link'])."</p>";
				} else {
					echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("A menu item with the title/link <em>%s</em> already exists!"),$result['link'])."</p>";
				}
			}
			break;
	}
	return $result;
}

/**
 * Enter description here...
 *
 */
function updateItem() {
	$menuset = checkChosenMenuset();
	$result['id'] = sanitize($_POST['id']);
	$result['title'] = process_language_string_save("title",2);
	if(empty($_POST['link'])) {
		$result['link'] = seoFriendly(get_language_string($title));
	} else {
		$result['link'] = sanitize($_POST['link']);
	}
	$result['show'] = getCheckboxState('show');
	$result['type'] = sanitize($_POST['type']);
	// update the category in the category table
	if(query("UPDATE ".prefix('menu')." SET title = '".	zp_escape_string($result['title']).
						"',link='".zp_escape_string($result['link']).
						"',type='".zp_escape_string($result['type'])."', `show`= '".zp_escape_string($result['show']).
						"',menuset='".zp_escape_string($menuset).						
						"' WHERE `id`=".zp_escape_string($result['id']),true)) {
		
		if(empty($result['title']) OR empty($result['link'])) {
			echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your custom menu item a <strong>title or link</strong>!")."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".gettext("Custom menu item updated!")."</p>";
		}
	} else {
		echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("A custom menu item with the link <em>%s</em> already exists!"),$result['link'])."</p>";
	}
	return $result;
}

/**
 * Enter description here...
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
 	* Prints the items as a partial dropdown (pages, news articles, categories)
 	* 
  * @return string
 	*/
function printTypeSelector() {
	?>
	<select id="typeselector" name="typeselector">
	<option value=""><?php echo gettext("*Select the type of the menu item type you wish to add*"); ?></option>
	<option value="galleryindex"><?php echo gettext("Gallery index"); ?></option>
	<option value="all_albums"><?php echo gettext("All Albums"); ?></option>
	<option value="album"><?php echo gettext("Album"); ?></option>
	<?php if(getOption('zp_plugin_zenpage')) { ?>
	<option value="all_zenpagepages"><?php echo gettext("All Zenpage pages"); ?></option>
	<option value="zenpagepage"><?php echo gettext("Zenpage page"); ?></option>
	<option value="zenpagenewsindex"><?php echo gettext("Zenpage news index"); ?></option>
	<option value="all_zenpagecategorys"><?php echo gettext("All Zenpage news categorys"); ?></option>
	<option value="zenpagecategory"><?php echo gettext("Zenpage news category"); ?></option>
	<?php } ?>
	<option value="custompage"><?php echo gettext("Custom theme page"); ?></option>
	<option value="customlink"><?php echo gettext("Custom link"); ?></option>
	</select>
<?php 
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

function printMenuEditPageJS() {
	?>
<script type="text/javascript">
$(document).ready(function() {
		$('#albumselector,#pageselector,#categoryselector,#titleinput').hide();
		$('#typeselector').change(function() {
			var type = $(this).val();
			$('input').val(''); // reset all input values so we do not carry them over from one type to another
			$('#link').attr('value','');
			$('#add,#titlelabel,#link,#link_label,#show_visible').show();
			$('#type').attr('value',type);
			$('#link_label').html('<?php echo gettext('URL'); ?>');
			switch(type) {
				case "galleryindex":
					$('#albumselector,#pageselector,#categoryselector').hide();
					$('#selector').html('<?php echo gettext("Gallery index"); ?>');
					$('#description').html('<?php echo gettext("This is the normal Zenphoto gallery index page. You cannot change anything for these item except a custom title here."); ?>');
					$('#link').attr('disabled',true);
					$('#titleinput').show();
					$('#link').attr('value','<?php echo WEBPATH; ?>');
					break;
				case 'all_albums':
					$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link,#link_label,#show_visible').hide();
					$('#selector').html('<?php echo gettext("All Albums"); ?>');
					$('#description').html('<?php echo gettext("This adds menu items for all Zenphoto albums."); ?>');
					break;
				case 'album':
					$('#pageselector,#categoryselector,#titleinput').hide();
					$('#selector').html('<?php echo gettext("Album"); ?>');
					$('#description').html('<?php echo gettext("This is for Zenphoto albums. Naturally you cannot change anything for these items here here."); ?>');
					$('#link').attr('disabled',true);
					$('#albumselector').show();
					$('#albumselector').change(function() {
						var val = $('#albumselector').val();
						$('#link').attr('value',val);
					});
					break;
				case 'all_zenpagepages':
					$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link,#link_label,#show_visible').hide();
					$('#selector').html('<?php echo gettext("All Zenpage pages"); ?>');
					$('#description').html('<?php echo gettext("This adds menu items for all Zenppage pages."); ?>');
					break;
				case 'zenpagepage':
					$('#albumselector,#categoryselector,#titleinput').hide();
					$('#selector').html('<?php echo gettext("Zenpage page"); ?>');
					$('#description').html('<?php echo gettext("This is for Zenpage CMS pages. Naturally you cannot change anything for these items here."); ?>');
					$('#link').attr('disabled',true);
					$('#pageselector').show();
					$('#pageselector').change(function() {
						var val = $('#pageselector').val();
						$('#link').attr('value',val);
					});
					break;
				case 'zenpagenewsindex':
					$('#albumselector,#pageselector,#categoryselector').hide();
					$('#selector').html('<?php echo gettext("Zenpage news index"); ?>');
					$('#description').html('<?php echo gettext("This is for news loop of the Zenpage CMS plugin. You cannot change anything for these item except a custom title here."); ?>');
					$('#link').attr('disabled',true);
					$('#titleinput').show();
					$('#link').attr('value','<?php echo rewrite_path(ZENPAGE_NEWS,'?p='.ZENPAGE_NEWS); ?>');
					break;	
				case 'all_zenpagecategorys':
					$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link,#link_label,#show_visible').hide();
					$('#selector').html('<?php echo gettext("All Zenpage categories"); ?>');
					$('#description').html('<?php echo gettext("This adds menu items for all Zenppage categories."); ?>');
					break;
				case 'zenpagecategory':
					$('#albumselector,#pageselector,#titleinput').hide();
					$('#selector').html('<?php echo gettext("Zenpage news category"); ?>');
					$('#description').html('<?php echo gettext("This is for the news categories for Zenpage CMS news articles. Naturally you cannot change anything for these items here."); ?>');
					$("#link").attr('disabled',true);
					$('#categoryselector').show();
					$('#categoryselector').change(function() {
						var val = $('#categoryselector').val();
						$('#link').attr('value',val);
					});
					break;
				case 'custompage':
					$('#albumselector,#pageselector,#categoryselector').hide();
					$('#selector').html('<?php echo gettext("Custom page"); ?>');
					$('#description').html('<?php echo gettext("This refers to the custom theme page feater which is described on the theming tutorial. Just enter a custom title and the file name (e.g. archive.php) and the correct URL is created automatically."); ?>');
					$('#link').removeAttr('disabled');
					$('#link_label').html('<?php echo gettext('Script page'); ?>');
					$('#titleinput').show();
					break;
				case "customlink":
					$('#albumselector,#pageselector,#categoryselector').hide();
					$('#selector').html('<?php echo gettext("Custom link"); ?>');
					$('#description').html('<?php echo gettext("This can be be a external link for example so the full URL is recommended (e.g http://www.domain.com)."); ?>');
					$('#link').removeAttr('disabled');
					$('#link_label').html('<?php echo gettext('URL'); ?>');
					$('#titleinput').show();
					break;
				case "":
					$("#selector").html("");
					$("#add").hide();
					break;
			}
    })
 });
</script>
<?php
}
?>