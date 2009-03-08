<?php
/**
 * zenpage admin functions
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */ 

/**
 * Calls the zenpage class
 *
 */
require_once("zenpage-class-page.php");
require_once("zenpage-class-news.php");
require_once("zenpage-functions.php");

global $_zp_current_zenpage_news, $_zp_current_zenpage_page;
//$_zp_current_zenpage_news = new ZenpageNews();
//$_zp_current_zenpage_page = new ZenpagePage();
/**
 * Returns the value of a checkbox form item
 *
 * @param string $id the $_REQUEST index
 * @return int (0 or 1)
 */
function getCheckboxState($id) {
	if (isset($_REQUEST[$id])) return 1; else return 0;
}

/**************************
/* page functions
***************************/

/**
 * Updates a new page to that database and returns the object of that page
 * 
 * @return object
 */
function addPage() {
	$title = mysql_real_escape_string(process_language_string_save("title",2));
	$titlelink = process_language_string_save("title",2);
	$titlelink = mysql_real_escape_string(seoFriendlyURL(get_language_string($titlelink)));
	$author = mysql_real_escape_string(sanitize($_POST['author']));
	$content = mysql_real_escape_string(process_language_string_save("content",0)); // TinyMCE already clears unallowed code
	$extracontent = mysql_real_escape_string(process_language_string_save("extracontent",0)); // TinyMCE already clears unallowed code
	$show = getCheckboxState('show');
	$date = sanitize($_POST['date']);
	$commentson = getCheckboxState('commentson');
	$parentid = "";
	$permalink = getCheckboxState('permalink');
	$codeblock1 = $_POST['codeblock1'];
	$codeblock2 = $_POST['codeblock2'];
	$codeblock3 = $_POST['codeblock3'];
	$codeblock = array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3);
	$codeblock = base64_encode(serialize($codeblock));
	$locked = getCheckboxState('locked');

	if(empty($title) OR empty($titlelink)) {
		$titlelink = seoFriendlyURL($date);
	}

	//query("INSERT INTO ".prefix('zenpage_pages')." (title, content, extracontent, `show`, `titlelink`, parentid, codeblock, author, `date`, commentson, permalink, locked) VALUES ('$title', '$content', '$extracontent', '$show', '$titlelink', '$parentid', '$codeblock', '$author', '$date', '$commentson','$permalink','$locked')");
	if (query("INSERT INTO ".prefix('zenpage_pages')." (title, content, extracontent, `show`, `titlelink`, parentid, codeblock, author, `date`, commentson, permalink, locked) VALUES ('$title', '$content', '$extracontent', '$show', '$titlelink', '$parentid', '$codeblock', '$author', '$date', '$commentson','$permalink','$locked')",true)) {
		if(empty($title) OR empty($titlelink)) {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("Page <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink))."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".sprintf(gettext("Page <em>%s</em> added"),$titlelink)."</p>";
		}
	}	else {
		echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("A page with the title/titlelink <em>%s</em> already exists"),$titlelink)."</p>";
	}
	$result = new ZenpagePage($titlelink);
	return $result;
}



/**
 * Updates a page and returns the object of that page
 *
 * @return object
 */
function updatePage() {
	global $_zp_current_admin, $_zp_current_zenpage_page;
	$result['id'] = sanitize($_POST['id']);
	$result['title']  = mysql_real_escape_string(process_language_string_save("title",2));
	$result['permalink'] = getCheckboxState('permalink');
	$result['author'] = mysql_real_escape_string(sanitize($_POST['author']));
	$result['content'] = mysql_real_escape_string(process_language_string_save("content",0)); // TinyMCE already clears unallowed code
	$result['extracontent'] = mysql_real_escape_string(process_language_string_save("extracontent",0)); // TinyMCE already clears unallowed code
	$result['show'] = getCheckboxState('show');
	$result['date'] = sanitize($_POST['date']);
	$result['lastchange'] = sanitize($_POST['lastchange']);
	$result['lastchangeauthor'] = mysql_real_escape_string(sanitize($_POST['lastchangeauthor']));
	$result['commentson'] = getCheckboxState('commentson');
	if($result['permalink'] === 1) {
		$result['titlelink'] = sanitize($_POST['titlelink-old']);
	}
	if(getCheckboxState('edittitlelink') === 1) {
		$result['titlelink'] = sanitize($_POST['titlelink']);
	}
	if($result['permalink'] === 0 AND getCheckboxState('edittitlelink') === 0) {
		$result['titlelink'] = process_language_string_save("title",2);
		$result['titlelink'] = mysql_real_escape_string(seoFriendlyURL(get_language_string($result['titlelink'])));
	}
	if(isset($_POST['resethitcounter'])) {
		$result['hitcounter'] = "0";
	} else {
		$result['hitcounter'] = sanitize($_POST['hitcounter']);
	}
	$codeblock1 = $_POST['codeblock1'];
	$codeblock2 = $_POST['codeblock2'];
	$codeblock3 = $_POST['codeblock3'];
	$codeblock = array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3);
	$result['codeblock'] = base64_encode(serialize($codeblock));
	$result['locked'] = getCheckboxState('locked');

	if(empty($result['title']) OR empty($result['titlelink'])) {
		$result['titlelink'] = seoFriendlyURL($date);;
	}

	// update the article in the database
	if(query("UPDATE ".prefix('zenpage_pages')." SET title = '".$result['title']."', content = '".$result['content']."', extracontent= '".$result['extracontent']."', `show` = '".$result['show']."', `titlelink` = '".$result['titlelink']."' , `codeblock` = '".$result['codeblock']."', `author` ='".$result['author']."', `date` ='".$result['date']."', `lastchange` ='".$result['lastchange']."', `lastchangeauthor` ='".$result['lastchangeauthor']."', `commentson` ='".$result['commentson']."', `hitcounter` ='".$result['hitcounter']."', `permalink` = '".$result['permalink']."', `locked` = '".$result['locked']."' WHERE id = ".$result['id'], true)) {
		if(empty($result['title']) OR empty($result['titlelink'])) {
			echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your page a <strong>title</strong>!")."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".sprintf(gettext("Page <em>%s</em> changes saved"),$result['titlelink'])."</p>";
		}
	} else {
		echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("A page with the title/titlelink <em>%s</em> already exists"),$result['titlelink'])."</p>";
	}
	$result = new ZenpagePage($result['titlelink']);
	return $result;
}


/**
 * Deletes a page (and also if existing its sub pages) from the database
 *
 */
function deletePage() {
	$id = sanitize_numeric($_GET['del']);
	$sortorder = sanitize($_GET['sortorder']);
	query("DELETE FROM ".prefix('zenpage_pages')." WHERE id = ".$id." OR `sort_order` like '".$sortorder."-%'"); // Delete the actual page
	//query("DELETE FROM ".prefix('zenpage_pages')." WHERE OR `sort_order` like '".$sortorder."%'"); // delete sub pages if there are some
	echo"<p class='messagebox' id='fade-message'>".gettext("Page successfully deleted!")."</p>";
}


/**
 * Updates the sortorder of the pages list in the database
 *
 */
function updatePageSortorder() {
	if(!empty($_POST['order'])) { // if someone didn't sort anything there are no values!
		$orderarray = explode("&",$_POST['order']);
		
		// first clear out unnecessary info from the submitted data string
		$count = 0;
		foreach($orderarray as $order) {
			$count++;
				
			// get ID
			$id[$count] = substr(strstr($order,"="),1); 
			//echo $order."<br />"; // debugging
			// get sort level
			$replace = array("left-to-right[" => "", "][id]=$id[$count]" => "", "][children][" => "-");
			$sortlevel[$count] = strtr($order,$replace);
			
			//TODO confirm page sortorder sticks
			$sortlevelex = explode("-",$sortlevel[$count]);
			//echo "<pre>";print_r($sortlevelex); echo "</pre>";
			$sortlevelnew[$count] = "";
			$count2 = 0;
			$sortarraycount = count($sortlevelex);
			foreach($sortlevelex as $sortex) {
				$count2++;
				$countchar = strlen($sortex);
				//echo "countchar".$countchar." / ";
				switch($countchar) {
					case 1:
						$sort = "00".$sortex;
						break;
					case 2:
						$sort = "0".$sortex;
						break;
					case 3:
						$sort = $sortex;
						break;
				}
				if($count2 === $sortarraycount) {
					$dash = "";
				} else {
					$dash = "-";
				}
				$sortlevelnew[$count] .= $sort.$dash;
			}
			//echo $count.". ".$sortlevelnew[$count]."<br />"; //debugging
			$sortlevel[$count] = $sortlevelnew[$count];
		} 	
		
		// update loop
		for($nr = 1; $nr <= $count; $nr++) {
				
			// check if top level (no "-" means top level)
			if(!strrpos($sortlevel[$nr],"-")) { 
				$parentid = "NULL";
							
			// if not top level, get parent id
			} else { 
				
				// nasty check if too many sub levels, since the sortables do not have a sub level limit option yet.
				$leveldepth = substr_count($sortlevel[$nr],"-");
				if($leveldepth > 4) {
					echo "<p class='errorbox' id='fade-message'>".gettext("You have more than the maximum supported 4 sub levels! Please try sorting differently.")."</p>";
					break;
				}
				//get parent id
				$test = strrpos($sortlevel[$nr],"-");
				$parent = substr($sortlevel[$nr],0,$test);
				for($nr2 = 1; $nr2 <= $count; $nr2++) {
					if($sortlevel[$nr2] === $parent) {
						$parentid = $id[$nr2];
					} 
				} 
			}
				// replace with db update query
				//echo "ID: ".$id[$nr]." /  Sortorder: ".$sortlevel[$nr]." / ParentID: ".$parentid."<br />"; // for debugging
				query("UPDATE " . prefix('zenpage_pages') . " SET `sort_order` = '".$sortlevel[$nr]."', `parentid`= ".$parentid." WHERE `id`=" . $id[$nr]);
			}
		
	} // if empty end
		echo "<br clear: all><p class='messagebox' id='fade-message'>".gettext("Sort order saved.")."</p>";
}


/**
 * Prints the table part of a single page item for the sortable pages list
 *
 * @param object $page The array containing the single page
 */
function printPagesListTable($page) {
	?>
 <table class='bordered2'>
   <tr>
    <td class='sort-handle' style="padding-bottom: 15px; ">
       <img src="../../images/drag_handle.png" style="position: relative; top: 7px; margin-right: 4px; width:14px; height:14px" />
  	<?php if(checkIfLocked($page)) { 
  		echo "<a href='admin-edit.php?page&amp;titlelink=".urlencode($page->getTitlelink())."' title='".truncate_string(strip_tags($page->getContent()),300)."'> "; checkForEmptyTitle($page->getTitle(),"page"); echo "</a>".checkHitcounterDisplay($page->getHitcounter());
  	} else { 
  		echo $page->getTitle(); checkHitcounterDisplay($page->getShow());
  	}	?>
    </td>
    <td class="icons3">
      <?php echo $page->getDatetime() ;?>
    </td> 
    <td class="icons3" style="text-align: left">
      <?php echo htmlspecialchars($page->getAuthor()) ;?>
    </td> 
    
    
	<?php if(checkIfLocked($page)) { ?>
	<td class="icons">
		<a href="?publish=<?php echo $page->getShow(); ?>&amp;id=<?php echo $page->getID(); ?>" title="<?php echo gettext("Publish or unpublish page"); ?>"><?php echo checkIfPublished($page->getShow()); ?></a>
	</td>
	<td class="icons">
		<a href="?commentson=<?php echo $page->getCommentson(); ?>&amp;id=<?php echo $page->getID(); ?>" title="<?php echo gettext("Enable or disable comments"); ?>">
		<?php echo checkIfCommentsAllowed($page->getCommentson()); ?>
	</td>
	<?php } else { ?>
	<td class="icons">
		<img src="images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</td>	
	<td class="icons">
		<img src="images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</td>	
	<?php } ?>
    
    <td class="icons">
      <a href="../../../index.php?p=<?php echo ZENPAGE_PAGES; ?>&amp;title=<?php echo $page->getTitlelink() ;?>" title="<?php echo gettext("View page"); ?>">
      <img src="images/view.png" alt="view" />
      </a>
    </td>
    
  
	<?php if(checkIfLocked($page)) { ?>
	<td class="icons">
		<a href="?hitcounter=1&amp;id=<?php echo $page->getID(); ?>"" title="<?php echo gettext("Reset hitcounter"); ?>">
		<img src="../../images/reset.png" alt="<?php echo gettext("Reset hitcounter"); ?>" /></a>
	</td>
	<td class="icons">
		<a href="javascript: confirmDeleteImage('admin-pages.php?del=<?php echo $page->getID(); ?>&amp;sortorder=<?php echo $page->getSortorder(); ?>','<?php echo js_encode(gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE AND WILL ALSO DELETE ALL SUB PAGES OF THIS PAGE!")); ?>')" title="<?php echo gettext("Delete page"); ?>">
		<img src="../../images/fail.png" alt="delete" /></a>
	</td>
	<?php } else { ?>
	<td class="icons">
    <img src="images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</td> 
	<td class="icons">
    <img src="images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
	</td> 
	<?php } ?>
   
  </tr>
 </table>
<?php
}


/**
 * Prints the sortable pages list up to the 4th sublevel
 *
 * @param array $pages The array containing all pages
 */
function printPagesList($pages) {
	foreach($pages as $page) { 
		$pageobj = new ZenpagePage($page['titlelink']);
		$count = 0;
	 	if($pageobj->getParentId() === NULL OR $pageobj->getParentID() === "0") {
	 		$count++;
			echo "<li id=\"".$pageobj->getID()."\" class=\"clear-element page-item1 left\">";
			printPagesListTable($pageobj);
			
			// sublevel 1 start
			$subcount1 = 0;
			foreach($pages as $sub1) {
				$sub1obj = new ZenpagePage($sub1['titlelink']);
				if($sub1obj->getParentID() === $pageobj->getID()) {
					$subcount1++;
					if($subcount1 === 1) {
						echo "<ul class=\"page-list\">\n";
					}
					echo "<li id=\"".$sub1obj->getID()."\" class=\"clear-element page-item1 left\">";
					printPagesListTable($sub1obj);
					
					// sublevel 2 start
					$subcount2 = 0;
					foreach($pages as $sub2) {
						$sub2obj = new ZenpagePage($sub2['titlelink']);
						if($sub2obj->getParentID() === $sub1obj->getID()) {
							$subcount2++;
							if($subcount2 === 1) {
								echo "<ul class=\"page-list\">\n";
							}
							echo "<li id=\"".$sub2obj->getID()."\" class=\"clear-element page-item1 left\">";
							printPagesListTable($sub2obj);
							
						// sublevel 3 start
						$subcount3 = 0;
						foreach($pages as $sub3) {
							$sub3obj = new ZenpagePage($sub3['titlelink']);
							if($sub3obj->getParentID() === $sub2obj->getID()) {
								$subcount3++;
								if($subcount3 === 1) {
									echo "<ul class=\"page-list\">\n";
								}
								echo "<li id=\"".$sub3obj->getID()."\" class=\"clear-element page-item1 left\">";
								printPagesListTable($sub3obj);
							
								// sublevel 4 start
								$subcount4 = 0;	
								foreach($pages as $sub4) {
									$sub4obj = new ZenpagePage($sub4['titlelink']);
									if($sub4obj->getParentID() === $sub3obj->getID()) {
										$subcount4++;
										if($subcount4 === 1) {
											echo "<ul class=\"page-list\">\n";
										}
										echo "<li id=\"".$sub4obj->getID()."\" class=\"clear-element page-item1 left\">";
										printPagesListTable($sub4obj);	
										if($subcount4 >= 1) {
											echo "</li>\n"; // sublevel 4 li end
										}
									}
								}
								if($subcount4 >= 1) { // sublevel 4 end
									echo "</ul>\n";
								}
								if($subcount3 >= 1) {	
									echo "</li>\n"; // sublevel 3 li end
								}
							}
						}
						if($subcount3 >= 1) { // sublevel 3 end
							echo "</ul>\n";
						}
							if($subcount2 >= 1) {
								echo "</li>\n"; // sublevel 2 li end
							}
						}
					}
					if($subcount2 >= 1) { // sublevel 2 end
						echo "</ul>\n";
					}
					if($subcount1 >= 1) {
						echo "</li>\n"; // sublevel 1 li end
					}
				}
			}
			if($subcount1 >= 1) { // sublevel 1 end
					echo "</ul>\n";
			} 
		}
		if($count === 1) {
			echo "</li>\n"; // top level li end
		}
	} // foreach end
}


/**************************
/* news article functions
***************************/

/**
 * Adds a new news article to the database from $_POST data and returns the object of that article
 *
 * @return object
 */
function addArticle() {
	$title = mysql_real_escape_string(process_language_string_save("title",2));
	$titlelink = process_language_string_save("title",2);
	$titlelink = mysql_real_escape_string(seoFriendlyURL(get_language_string($titlelink)));
	$author = mysql_real_escape_string(sanitize($_POST['author']));
	$content = mysql_real_escape_string(process_language_string_save("content",0)); // TinyMCE already clears unallowed code
	$extracontent = mysql_real_escape_string(process_language_string_save("extracontent",0)); // TinyMCE already clears unallowed code
	$show = getCheckboxState('show');
	$date = sanitize($_POST['date']);
	$permalink = getCheckboxState('permalink');
	$lastchange = getCheckboxState('lastchange');
	$commentson = getCheckboxState('commentson');
	$codeblock1 = $_POST['codeblock1'];
	$codeblock2 = $_POST['codeblock2'];
	$codeblock3 = $_POST['codeblock3'];
	$codeblock = array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3);
	$codeblock = base64_encode(serialize($codeblock));
	$locked = getCheckboxState('locked');

	if(empty($title) OR empty($titlelink)) {
		$titlelink = seoFriendlyURL($date);;
	}

	// create new article
	if (query("INSERT INTO ".prefix('zenpage_news')." (title, content, extracontent, `show`, date, `titlelink`, commentson, codeblock, author, lastchange, permalink, locked) VALUES ('$title', '$content', '$extracontent', '$show', '$date', '$titlelink', '$commentson', '$codeblock', '$author', '$lastchange', '$permalink','$locked')",true)) {
		if(empty($title) OR empty($titlelink)) {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("Article <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink))."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".sprintf(gettext("Article <em>%s</em> added"),$titlelink)."</p>";
		}
	}	else {
		echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("An article with the title/titlelink <em>%s</em> already exists!"),$titlelink)."</p>";
	}

	// get id of new article
	$result = query_single_row("SELECT id FROM ".prefix('zenpage_news')." WHERE titlelink = '$titlelink'");
	$newsid = $result['id'];

	// create news2cat rows
	$result2 = query_full_array("SELECT id, cat_name, cat_link FROM ".prefix('zenpage_news_categories')." ORDER BY cat_name");
	foreach ($result2 as $cat) {
		if (isset($_POST["cat".$cat['id']])) {
			query("INSERT INTO ".prefix('zenpage_news2cat')." (cat_id, news_id) VALUES ('".$cat['id']."', '$newsid')");
		}
	}
	$result = new ZenpageNews($titlelink);
	return $result;
}


/**
 * Updates a news article and returns the object of that article
 *
 * @return object
 */
function updateArticle() {
	$result['id'] = sanitize($_POST['id']);
	$result['title'] = mysql_real_escape_string(process_language_string_save("title",2));
	$result['permalink'] = getCheckboxState('permalink');
	$result['author'] = mysql_real_escape_string(sanitize($_POST['author']));
	$result['content'] = mysql_real_escape_string(process_language_string_save("content",0)); // TinyMCE already clears unallowed code
	$result['extracontent'] = mysql_real_escape_string(process_language_string_save("extracontent",0)); // TinyMCE already clears unallowed code
	$result['show'] = getCheckboxState('show');
	$result['date'] = sanitize($_POST['date']);
	$result['lastchange'] = sanitize($_POST['lastchange']);
	$result['lastchangeauthor'] = mysql_real_escape_string(sanitize($_POST['lastchangeauthor']));
	$result['commentson'] = getCheckboxState('commentson');
	if($result['permalink'] === 1) {
		$result['titlelink'] = sanitize($_POST['titlelink-old']);
	}
	if(getCheckboxState('edittitlelink') === 1) {
		$result['titlelink'] = sanitize($_POST['titlelink']);
	}
	if($result['permalink'] === 0 AND getCheckboxState('edittitlelink') === 0) {
		$result['titlelink'] = process_language_string_save("title",2);
		$result['titlelink'] = mysql_real_escape_string(seoFriendlyURL(get_language_string($result['titlelink'])));
	}
	if(isset($_POST['resethitcounter'])) {
		$result['hitcounter'] = "0";
	} else {
		$result['hitcounter'] = sanitize($_POST['hitcounter']);
	}
	$codeblock1 = $_POST['codeblock1'];
	$codeblock2 = $_POST['codeblock2'];
	$codeblock3 = $_POST['codeblock3'];
	$codeblock = array("1" => $codeblock1, "2" => $codeblock2, "3" => $codeblock3);
	$result['codeblock'] = base64_encode(serialize($codeblock));
	$result['locked'] = getCheckboxState('locked');

	if(empty($result['title']) OR empty($result['titlelink'])) {
		$result['titlelink'] = seoFriendlyURL($date);
	}

	// update the article in the database
	//query("UPDATE ".prefix('zenpage_news')." SET title = '".$result['title']."', content = '".$result['content']."', extracontent = '".$result['extracontent']."', `show` = '".$result['show']."', date = '".$result['date']."', titlelink = '".$result['titlelink']."', commentson = '".$result['commentson']."', codeblock = '".$result['codeblock']."', author = '".$result['author']."', lastchange = '".$result['lastchange']."', lastchangeauthor = '".$result['lastchangeauthor']."', `hitcounter` ='".$result['hitcounter']."', `permalink` ='".$result['permalink']."', `locked` = '".$result['locked']."' WHERE id = ".$result['id']);
	if(query("UPDATE ".prefix('zenpage_news')." SET title = '".$result['title']."', content = '".$result['content']."', extracontent = '".$result['extracontent']."', `show` = '".$result['show']."', date = '".$result['date']."', titlelink = '".$result['titlelink']."', commentson = '".$result['commentson']."', codeblock = '".$result['codeblock']."', author = '".$result['author']."', lastchange = '".$result['lastchange']."', lastchangeauthor = '".$result['lastchangeauthor']."', `hitcounter` ='".$result['hitcounter']."', `permalink` ='".$result['permalink']."', `locked` = '".$result['locked']."' WHERE id = ".$result['id'], true)) {
		if(empty($result['title']) OR empty($result['titlelink'])) {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("Article <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"),get_language_string($titlelink))."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".sprintf(gettext("Article <em>%s</em> updated"),$result['titlelink'])."</p>";
		}
	}	else {
		echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("An article with the title/titlelink <em>%s</em> already exists!"),$result['titlelink'])."</p>";
	}
	// create news2cat rows
	$result2 = query_full_array("SELECT id, cat_name, cat_link FROM ".prefix('zenpage_news_categories')." ORDER BY id");
	foreach($result2 as $cat) {

		// if category is sent
		if(isset($_POST["cat".$cat['id']])) {

			// check if category is already set in db, if not add it to news2cat
			$checkcat = query_single_row("SELECT cat_id, news_id FROM ".prefix('zenpage_news2cat')." WHERE cat_id = ".$cat['id']. " AND news_id = ".$result['id']);
			if(!$checkcat) {
				query("INSERT INTO ".prefix('zenpage_news2cat')." (cat_id, news_id) VALUES ('".$cat['id']."', '".$result['id']."')");
			}

			// if category is not sent, delete it from news2cat
		} else {
			query("DELETE FROM ".prefix('zenpage_news2cat')." WHERE cat_id = ".$cat['id']." AND news_id = ".$result['id']);
		}
	}
	$result = new ZenpageNews($result['titlelink']);
	return $result;
}


/**
 * Deletes an news article from the database
 *
 */
function deleteArticle() {
	$id = $_GET['del'];
	query("DELETE FROM ".prefix('zenpage_news')." WHERE id = $id");  // remove the article
	query("DELETE FROM ".prefix('zenpage_news2cat')." WHERE news_id = $id"); // delete the category association
	echo "<p class='messagebox' id='fade-message'>".gettext("Article successfully deleted!")."</p>";
}


/**
 * Print the categories of a news article for the news articles list
 *
 * @param obj $obj object of the news article
 */
function printArticleCategories($obj) {
  global $_zp_current_zenpage_news;
  $cat = $obj->getCategories();
  $number = 0;
  foreach ($cat as $cats) {
    $number++;
    if($number != 1) {
      echo ", ";
    }
    echo get_language_string($cats['cat_name']);
  }
}


/**
 * Prints the checkboxes to select and/or show the catagory of an news article on the edit or add page
 *
 * @param int $id ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 */
function printCategorySelection($id='', $option='') {
  global $_zp_current_zenpage_news;
  $all_cats = getAllCategories();
  $selected = '';
 	echo "<ul class='zenpagechecklist'>\n";
	foreach ($all_cats as $cats) {
    if($option != "all") {
   	  $cat2news = query_single_row("SELECT cat_id FROM ".prefix('zenpage_news2cat')." WHERE news_id = ".$id." AND cat_id = ".$cats['id']);
   	  if($cat2news['cat_id'] != "") {
   	    $selected ="checked ='checked'";
   	  } else {
   	    $selected ="";
   	  }
    }
    $catname = get_language_string($cats['cat_name']);
    $catlink = $cats['cat_link'];
    $catid = $cats['id'];
    echo "<li><label for='cat".$catid."'><input name='cat".$catid."' id='cat".$catid."' type='checkbox' value='".$catid."' ".$selected." />".$catname."</label></li>\n";
  }
  echo "</ul>\n";
}


/**
 * Prints the dropdown menu for the date archive selector for the news articles list
 *
 */
function printArticleDatesDropdown() {
  global $_zp_current_zenpage_news;
  $datecount = getAllArticleDates();
  $currentpage = getCurrentAdminNewsPage();
  $lastyear = "";
  $nr = "";
 ?>
  <form name="AutoListBox1" style="float:left; margin-left: 10px;">
  <select name="ListBoxURL" size="1" onchange="gotoLink(this.form)">
 <?php
 		if(!isset($_GET['date'])) {
			$selected = "selected";
		 } else {
				$selected = "";
			}
	   echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,false,true)."'>".gettext("View all months")."</option>";
	 	while (list($key, $val) = each($datecount)) {
	  $nr++;
	  if ($key == '0000-00-01') {
	    $year = "no date";
	    $month = "";
	  } else {
	    $dt = strftime('%Y-%B', strtotime($key));
	    $year = substr($dt, 0, 4);
	    $month = substr($dt, 5);
	  }
    if(isset($_GET['category'])) {
	      $catlink = "&amp;category=".$_GET['category'];
	    } else {
	      $catlink = "";
	    }
		$check = $month."-".$year;
		 if(isset($_GET['date']) AND $_GET['date'] === substr($key,0,7)) {
				$selected = "selected";
		 } else {
		 		$selected = "";
		 	}
		 	if(isset($_GET['date'])) {
		 		echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,false,true)."'>$month $year ($val)</option>\n";
		 	} else {
	  		echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage."&amp;date=".substr($key,0,7).getNewsAdminOptionPath(true,false,true)."'>$month $year ($val)</option>\n";
	  	}
	}
?>
	</select>
	<script language="JavaScript">
	<!--
	function gotoLink(form) {
	var OptionIndex=form.ListBoxURL.selectedIndex;
	parent.location = form.ListBoxURL.options[OptionIndex].value;}
	//-->
	</script></form>
<?php
}


/**
 * Prints news articles list page navigation
 *
 */
function printArticlesPageNav() {
  global $_zp_zenpage_total_pages;
  $current = getCurrentAdminNewsPage();
  $total = $_zp_zenpage_total_pages;
  if($total > 1) {
    echo "<ul class=\"pagelist\">";
    if ($current != 1) {
      echo "<li class='prev'><a href='admin-news-articles.php?pagenr=".($current - 1).getNewsAdminOptionPath(true,true,true)."' title='".gettext("Prev Page")." ".($current - 1)."' >&laquo; ".gettext("prev")."</a></li>\n";
    } else {
      echo "<li class='prev'><span class='disabledlink'>&laquo; ".gettext("prev")."</span></li>\n";
    }
    $j=max(1, min($current-3, $total-6));
		if ($j != 1) {
			$p = max($j-4,1);
		echo "\n <li><a href='admin-news-articles.php?pagenr=".$p.' title="page '.$p.'">...</a></li>';
	}
	for ($i=$j; $i <= min($total, $j+6); $i++) {
      if($i == $current) {
        echo "<li>".$i."</li>\n";
      } else {
        echo "<li><a href='admin-news-articles.php?pagenr=".$i.getNewsAdminOptionPath(true,true,true)."' title='Page ".$i."'>".$i."</a></li>\n";
      }
    }
    if ($i < $total) {
    	$p = min($j+10,$total);
    	echo "\n <li><a href='admin-news-articles.php?pagenr=".$p.' title="page '.$p.'">...</a></li>';
    }
    echo "<li class=\"next\">";
    
    if ($current != $total)	{
      echo "<li class='next'><a href='admin-news-articles.php?pagenr=".min($j+10,$total).getNewsAdminOptionPath(true,true,true)."' title='".gettext("Next page")." ".min($j+10,$total)."'>".gettext("next")." &raquo;</a></li>\n";
    } else {
      echo "<li class='next'><span class='disabledlink'>".gettext("next")." &raquo;</span></li>\n";
    }
    echo "</ul>";
  }
}


/**
 * Prints the dropdown menu for the category selector for the news articles list
 *
 */
function printCategoryDropdown() {
	global $_zp_current_zenpage_news;
  $currentpage = getCurrentAdminNewsPage();
  $result = getAllCategories();
  if(isset($_GET['date'])) {
    $datelink = "&amp;date=".$_GET['date'];
    $datelinkall = "?date=".$_GET['date'];
  } else {
    $datelink = "";
    $datelinkall ="";
  }
?>
  <form name ="AutoListBox2" style="float:left">
  <select name="ListBoxURL" size="1" onchange="gotoLink(this.form)">
<?php
if(!isset($_GET['category'])) {
			$selected = "selected";
    } else {
    	$selected ="";
    } 
		echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(false,true,true)."'>".gettext("All categories")."</option>";
  foreach ($result as $cat) {
    // check if there are articles in this category. If not don't list the category.
    $count = countArticles($cat['cat_link'],false);
    $count = " (".$count.")";
    if(isset($_GET['category']) AND $_GET['category'] === $cat['cat_link']) {
    	$selected = "selected";
    } else {
    	$selected ="";
    }
    if ($count != " (0)") {
			echo "<option $selected value='admin-news-articles.php?pagenr=".$currentpage."&amp;category=".$cat['cat_link'].getNewsAdminOptionPath(false,true,true)."'>".get_language_string($cat['cat_name']).$count."</option>\n";
  	}
	}
	
?>
 </select>
  <script language="JavaScript">
	<!--
	function gotoLink(form) {
	var OptionIndex=form.ListBoxURL.selectedIndex;
	parent.location = form.ListBoxURL.options[OptionIndex].value;}
	//-->
	</script>
	</form>
<?php
} 


/**
 * Creates the admin paths for news articles if you use the dropdowns for category, published and date together
 *
 * @param bool $categorycheck true or false if 'category' should be included in the url
 * @param bool $postedcheck true or false if 'date' should be included in the url
 * @param bool $publishedcheck true or false if 'published' should be included in the url
 * @return string
 */
function getNewsAdminOptionPath($categorycheck='', $postedcheck='',$publishedcheck='') {
	global $_zp_current_zenpage_news;
	$category = "";
	$posted = "";
	$published = "";
	if(isset($_GET['category']) AND $categorycheck === true) {
		$category = "&amp;category=".$_GET['category'];
	}
	if(isset($_GET['date']) AND $postedcheck === true) {
		$posted = "&amp;date=".$_GET['date'];
	}
	if(isset($_GET['published']) AND $publishedcheck === true) {
		$published = "&amp;published=".$_GET['published'];
	}
	$optionpath = $category.$posted.$published;
	return $optionpath;
}


/**
 * Prints the dropdown menu for the published/unpublishd selector for the news articles list
 *
 */
function printUnpublishedDropdown() {
	global $_zp_current_zenpage_news;
  $currentpage = getCurrentAdminNewsPage();
?>
  <form name="AutoListBox3" style="float:left; margin-left: 10px;">
  <select name="ListBoxURL" size="1" onchange="gotoLink(this.form)">
 <?php
 		$all="";
		$published="";
		$unpublished="";
		 if(isset($_GET['published']) AND $_GET['published'] === "no") {
				$unpublished="selected";
		 }
		 if(isset($_GET['published']) AND $_GET['published'] === "yes") {
				$published="selected";
		 	}
		 	if(!isset($_GET['published'])) {
		 	 	$all="selected";
		 	}
 	echo "<option $all value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."'>".gettext("All articles")."</option>\n";
	echo "<option $published value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."&amp;published=yes'>".gettext("Published")."</option>\n";
	echo "<option $unpublished value='admin-news-articles.php?pagenr=".$currentpage.getNewsAdminOptionPath(true,true,false)."&amp;published=no'>".gettext("Unpublished")."</option>\n"; 
	?>
	</select>
	<script language="JavaScript">
	<!--
	function gotoLink(form) {
	var OptionIndex=form.ListBoxURL.selectedIndex;
	parent.location = form.ListBoxURL.options[OptionIndex].value;}
	//-->
	</script></form>
<?php 
}


/**************************
/* Category functions
***************************/

/**
 * Adds a category to the database
 *
 */
function addCategory() {
	$catname = process_language_string_save("category",2); // so that no \ are shown in the 'Category x added' message
	$catlink = seoFriendlyURL(get_language_string($catname));
	if(empty($catlink) OR empty($catname)) {
		echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your category a <strong>title or titlelink</strong>!")."</p>";
	} else {
		if (query("INSERT INTO ".prefix('zenpage_news_categories')." (cat_name, cat_link, permalink) VALUES ('".mysql_real_escape_string($catname)."', '".
		mysql_real_escape_string(seoFriendlyURL($catlink))."','".getCheckboxState('permalink')."')", true)) {
			echo "<p class='messagebox' id='fade-message'>".sprintf(gettext("Category <em>%s</em> added"),$catlink)."</p>";
		} else {
			echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("A category with the title/titlelink <em>%s</em> already exists!"),$catlink)."</p>";
		}
	}
}


/**
 * Updates a category
 *
 */
function updateCategory() {
	global $_zp_current_zenpage_news;
	$result['id'] = sanitize($_POST['id']);
	$result['cat_name'] = mysql_real_escape_string(process_language_string_save("category",2));
	$result['permalink'] = getCheckboxState('permalink');
	if($result['permalink'] === 1) {
		$result['cat_link'] = sanitize($_POST['catlink-old']);
	}
	if(getCheckboxState('edittitlelink') === 1) {
		$result['cat_link'] = sanitize($_POST['catlink']);
	}
	if($result['permalink'] === 0 AND getCheckboxState('edittitlelink') === 0) {
		$result['cat_link'] = process_language_string_save("category",2);
		$result['cat_link'] = mysql_real_escape_string(seoFriendlyURL(get_language_string($result['cat_link'])));
	}
	// update the category in the category table
	if(query("UPDATE ".prefix('zenpage_news_categories')." SET cat_name = '".$result['cat_name']."', cat_link = '".$result['cat_link']."', permalink = '".$result['permalink']."' WHERE id = ".$result['id'],true)) {
		if(empty($result['cat_name']) OR empty($result['cat_link'])) {
			echo "<p class='errorbox' id='fade-message'>".gettext("You forgot to give your category a <strong>title or titlelink</strong>!")."</p>";
		} else {
			echo "<p class='messagebox' id='fade-message'>".gettext("Category updated!")."</p>";
		}
	} else {
		echo "<p class='errorbox' id='fade-message'>".sprintf(gettext("A category with the title/titlelink <em>%s</em> already exists!"),$catlink)."</p>";
	}
	$result = getCategory($result['id']);
	return $result;
}


/**
 * Delets a category from the database
 *
 */
function deleteCategory() {
  global $_zp_current_zenpage_news;
  if(isset($_GET['delete'])) {
    // check if the category is in use, don't delete
    $count = countArticles($_GET['cat_link'],false);
    if ($count != 0) {
			query("DELETE FROM ".prefix('zenpage_news2cat')." WHERE cat_id = '{$_GET['delete']}'");
    } 
    query("DELETE FROM ".prefix('zenpage_news_categories')." WHERE id = '{$_GET['delete']}'");
    echo "<p class='messagebox' id='fade-message'>".gettext("Category successfully deleted!")."</p>";
  }
}


function printCategoryList() {
	global $_zp_current_zenpage_news;
  $result = getAllCategories();
	foreach($result as $cat) {
		$count = countArticles($cat['cat_link'],false);
			if(get_language_string($cat['cat_name'])) {
  			$catname = get_language_string($cat['cat_name']);
  		} else {
  			$catname = "<span style='color:red; font-weight: bold'>".gettext("Untitled category")."</span>";
  		}
?>
 <tr> 
  <td><?php echo "<a href='admin-categories.php?edit&amp;id=".$cat['id']."' title='".gettext('Edit this category')."'>".$catname."</a>".checkHitcounterDisplay($cat['hitcounter']); ?></td>
  <td class="icons3"><?php echo $count; ?> <?php echo gettext("articles"); ?></td>
  <td class="icons">
  	<a href="?hitcounter=1&amp;id=<?php echo $cat['id'];?>" title="<?php echo gettext("Reset hitcounter"); ?>">
  	<img src="../../images/reset.png" alt="<?php echo gettext("Reset hitcounter"); ?>" />
  	</a>
  </td> 
  <td class="icons">
  <a href="javascript:confirmDeleteImage('admin-categories.php?delete=<?php echo $cat['id']; ?>&amp;cat_link=<?php echo js_encode($cat['cat_link']); ?>','<?php echo js_encode(gettext("Are you sure you want to delete this category? THIS CANNOT BE UNDONE!")); ?>')" title="<?php echo gettext("Delete Category"); ?>"><img src="../../images/fail.png" alt="<?php echo gettext("Delete"); ?>" title="<?php echo gettext("Delete Category"); ?>" /></a>
  </td>
  </tr>
 <?php	
	} 
}


/**************************
/* General functions
***************************/

function checkForEmptyTitle($titlefield,$type) {
	switch($type) {
		case "page":
			$text = gettext("Untitled page");
		 break;
		case "news":
			$text = gettext("Untitled article");
			break;
		case "category":
			$text = gettext("Untitled category");
			break;
	}
	if($titlefield) {
		$title = strip_tags($titlefield);
	} else {
		$title = "<span style='color:red; font-weight: bold'>".$text."</span>";
	}
	echo $title;
}


/**
 * Publishes a page or news article
 *
 * @param string $option "page" or "news"
 * @param int $id the id of the article or page
 * @return string
 */
function publishPageOrArticle($option,$id) {
  switch ($option) {
		case "news":
			$dbtable = prefix('zenpage_news');
			break;
		case "page":
			$dbtable = prefix('zenpage_pages');
			break;
	}
  if($_GET['publish'] === "1") {
		$show = "0";
  } else {
  	$show = "1";
  }
  query("UPDATE ".$dbtable." SET `show` = ".$show." WHERE id = ".$id);
}


/**
 * Checks if comments are allowed for a article or page and prints the matching image icon for the articles or pages list
 *
 * @param string $commentson the comments array field of "commentson"
 * @return string 
 */
function checkIfCommentsAllowed($commentson='') {
  if ($commentson === "1") {
    $check = "<img src=\"images/comments-on.png\" alt=\"".gettext("Comments on")."\" />";
  } else {
    $check = "<img src=\"images/comments-off.png\" alt=\"".gettext("Comments off")."\" />";
  }
  return $check;
}


/**
 * Enables comments for a news article or page
 *
 * @param string $type "news" or "pages"
 */
function enableComments($type) {
  if($_GET['commentson'] === "1") {
		$comments = "0";
  } else {
  	$comments = "1";
  }
  switch($type) {
  	case "news":
  		$dbtable = prefix('zenpage_news');
  		break;
  	case "page":
  		$dbtable = prefix('zenpage_pages');
  		break;
  }
  query("UPDATE ".$dbtable." SET `commentson` = ".$comments." WHERE id = ".sanitize_numeric($_GET['id']));
}


/**
 * Resets the hitcounter for a page, article or category
 *
 * @param string $option "news", "page" or "cat"
 */
function resetPageOrArticleHitcounter($option='') {
	switch ($option) {
		case "news":
			$dbtable = prefix('zenpage_news');
			break;
		case "page":
			$dbtable = prefix('zenpage_pages');
			break;
		case "cat":
			$dbtable = prefix('zenpage_news_categories');
			break;
	}
	$id = sanitize_numeric($_GET['id']);
  if($_GET['hitcounter'] === "1") {
		query("UPDATE ".$dbtable." SET `hitcounter` = 0 WHERE id = ".$id);
  }   
}


/**
 * Checks if there are hitcounts and if they are displayed behind the news article, page or category title
 *
 * @param string $item The array of the current news article, page or category in the list.
 * @return string
 */
function checkHitcounterDisplay($item) {
	if($item == 0) {
		$hitcount = "";
	} else {
		if($item == 1) {
			$hits = gettext("hit");
		} else {
			$hits = gettext("hits");
		}
		$hitcount = " (".$item." ".$hits.")";
	}
	return $hitcount;
}


/**
 * Prints how many pages, articles, categories and news or pages comments we got.
 *
 * @param string $option What the statistic should be shown of: "news", "pages", "categories"
 */
function printNewsPagesStatistic($option) {
	global $_zp_current_zenpage_page, $_zp_current_zenpage_news;
	switch($option) {
		case "news":
			$items = getNewsArticles("","");
			$type = gettext("Articles");
		break;
		case "pages":
			$items = getPages(false);
			$type = gettext("Pages");
		break;
		case "categories":
			$cats = getAllCategories();
			$catstotal = count($cats);
			printf(gettext('(<strong>%u</strong> Categories)'),$catstotal);
		break;
	}	
		if($option === "news" OR $option === "pages") {	
			$total = count($items);
			$pub = 0;
			foreach($items as $item) {
				switch ($option) {
				case "news":
					$itemobj = new ZenpageNews($item['titlelink']);
					$show = $itemobj->getShow();
					break;
				case "pages":
					$itemobj = new ZenpagePage($item['titlelink']);
					$show = $itemobj->getShow();
					break;
				case "categories":
					$show = $item['show'];
					break;
			}
			if($show == 1) {
				$pub++;
			}
		}
			//echo " (unpublished: ";
			$unpub = $total - $pub;
			printf(gettext('(<strong>%1$u</strong> %2$s, <strong>%3$u</strong> unpublished)'),$total,$type,$unpub);
		} 
}


/**
 * Prints the links to JavaScript and CSS files zenpage needs. 
 * Actually the same as for zenphoto but with different paths since we are in the plugins folder.
 *
 */
function zenpageJSCSS() {
  echo "<link rel=\"stylesheet\" href=\"../../admin.css\" type=\"text/css\" />";
  echo "<link rel=\"stylesheet\" href=\"zenpage.css\" type=\"text/css\" />";
  echo "<script type=\"text/javascript\" src=\"../../js/admin.js\"></script>";
  echo "<script type=\"text/javascript\" src=\"../../js/jquery.js\"></script>";
	echo "\n  <script src=\"../../js/jquery.dimensions.js\" type=\"text/javascript\"></script>";
	?>
	<script type="text/javascript" src="../../js/zenphoto.js.php"></script>
	<script type="text/javascript" src="../../js/thickbox.js"></script>
	<link rel="stylesheet" href="thickbox-mod.css" type="text/css" />
	<?php
	echo "\n  <script type=\"text/javascript\">";
	echo "\n  \tjQuery(function( $ ){";
	echo "\n  \t\t $(\"#fade-message\").fadeTo(5000, 1).fadeOut(1000);";
	echo "\n  \t});";
	echo "\n  </script>";
	?>
	<script type="text/javascript">
  $(document).ready(function(){
    
    $("#tip a").click(function() {
      $("#tips").toggle("slow");
    });
   
  });
  </script>
	<?php
}


/**
 * Wrapper function to print the zenphoto head and main admin navigation
 *
 */
function zenpageHeader() {
	printLogoAndLinks();
	echo "<div id=\"main\">";
	printTabs("zenpage"); setPluginDomain("zenpage");
	echo "<div id=\"content\">";
	zenpageAdminnav();
}


/**
 * Prints the zenpage admin subnavigation
 *
 * @param string $currentpage Which tab should be hightlighted as the current page
 */
function zenpageAdminnav($currentpage) {
 	global $_zp_loggedin;
 	if (($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
 		// for return from news article edit to the page we came from
		if(strstr($currentpage,"admin-edit.php") AND isset($_GET['newsarticle'])) {
			if(empty($_GET['pagenr'])) {
				$page = "";
			} else {
				$page = "?pagenr=".$_GET['pagenr'];
			}
		} else {
			$page = "";
		}
		echo "<ul class=\"zptabs\">\n";
		if($currentpage === "pages") {
			echo "<li><a href=\"admin-pages.php\" class=\"active-zptab\" title=\"".gettext("Pages")."\">".gettext("pages")."</a></li>\n";
		} else {
			echo "<li><a href=\"admin-pages.php\" title=\"".gettext("Pages")."\">".gettext("pages")."</a></li>\n";
		}
		if($currentpage === "articles") {
			echo "<li><a href=\"admin-news-articles.php".$page."\" class=\"active-zptab\" title=\"".gettext("Articles")."\">".gettext("articles")."</a></li>\n";
		} else {
			echo "<li><a href=\"admin-news-articles.php\" title=\"".gettext("Articles")."\">".gettext("articles")."</a></li>\n";
		}
		if($currentpage === "categories") {
			echo "<li><a href=\"admin-categories.php\" class=\"active-zptab\" title=\"".gettext("Categories")."\">".gettext("categories")."</a></li>\n";
		} else {
			echo "<li><a href=\"admin-categories.php\" title=\"".gettext("Categories")."\">".gettext("categories")."</a></li>\n";
		}
		echo "</ul>\n";
  } else {
  	echo "<div class='errorbox'>".gettext("You need Admin Rights or Zenpage rights to use Zenpage")."</div>";
  	exit;
  }
}


/**
 * Prints a dropdown to select the author of a page or news article (Admin rights users only)
 *
 * @param string $currentadmin The current admin is selected if adding a new article, otherwise the original author
 */
function AuthorSelector($currentadmin='') {
	$admins = getAdministrators();
	?>
<select size='1' name="author" id="author">
<?php
foreach($admins as $admin) {
	if($admin['rights'] & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
		if($currentadmin === $admin['user']) {
			echo "<option selected value='".$admin['user']."'>".$admin['user']."</option>";
		} else {
			echo "<option value='".$admin['user']."'>".$admin['user']."</option>";
		}
	}
} 
?>
</select>
<?php
}


/**
 * Checks if a page or articles is published and prints the icon for the artilces or pages list
 *
 * @param string $show The item array field "show"
 * @return string
 */
function checkIfPublished($show) {
  if ($show === "1") {
    $publish = "<img src=\"../../images/pass.png\" alt=\"".gettext("Published")."\" />";
  } else {
    $publish = "<img src=\"../../images/action.png\" alt=\"".gettext("Unpublished")."\" />";
  }
  return $publish;
}


/**
 * Checks if a checkbox is selected and checks it if.
 *
 * @param string $field the array field of an item array to be checked (for example "permalink" or "comments on")
 */
function checkIfChecked($field) {
	if ($field === "1") {
		echo "checked='checked'";
	} 
}

/**
 * Gets the db field $field of the $object (page or news article) if $object is an object. 
 * Used to share the same page for page/news article add (no object) and edit (object available)
 *
 * @param object $object If this is an object the function returns the value of the db field $field
 * @param string $field The db field to get (Note: "parentid" and "sortorder" are not availabe for news articles!)
 * @return string
 */
function getIfObject($object,$field) {
 if(is_object($object)) {
 		return $object->get($field);
  } else {
  	return "";
  }
}

/**
 * Prints the db field $field of the $object (page or news article) if $object is an object. 
 * Used to share the same page for page/news article add (no object) and edit (object available)
 *
 * @param object $object If this is an object the function returns the value of the db field $field
 * @param string $field The db field to get (Note: "parent id" is not availabe for news articles!)
 * @return string
 */
function printIfObject($object,$field) { 
	echo getIfObject($object,$field);
}

/**
 * Checks if the current logged in admin user is the author that locked the page/article.
 * Only that author or any user with admin rights will be able to edit or unlock. 
 *
 * @param object $page The array of the page or article to check
 * @return bool
 */
function checkIfLocked($page) {
	global $_zp_current_admin; 
	$admins = getAdministrators();
	$admin = array_shift($admins);
	$adminname = $admin['user'];
	if($page->getLocked() === 1) {
		if($_zp_current_admin['user'] === $page->getAuthor() OR $_zp_current_admin['user'] === $adminname) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return TRUE;
	}
}

/**
 * Checks if the current admin-edit.php page is called for news articles or for pages.
 *
 * @param string $page What you want to check for, "page" or "newsarticle"
 * @return bool
 */
function is_AdminEditPage($page) {
	switch ($page) {
		case "page":
			if(isset($_GET['page'])) {
				return TRUE;
			} else {
				return FALSE;
			}
			break;
		case "newsarticle":
			if(isset($_GET['newsarticle'])) {
				return TRUE;
			} else {
				return FALSE;
			}
			break;
	}
}

/**
 * Codeblock tabs javascript code
 *
 */
function codeblocktabsJS() { ?>
	<script type="text/javascript" charset="utf-8">
		$(function () {
			var tabContainers = $('div.tabs > div');
			tabContainers.hide().filter(':first').show();
			
			$('div.tabs ul.tabNavigation a').click(function () {
				tabContainers.hide();
				tabContainers.filter(this.hash).show();
				$('div.tabs ul.tabNavigation a').removeClass('selected');
				$(this).addClass('selected');
				return false;
			}).filter(':first').click();
		});
	</script>
<?php }



// test of the hack of shortschoolbus
function print_language_string_list_zenpage($dbstring, $name, $textbox=false, $locale=NULL) {
	global $_zp_languages, $_zp_active_languages, $_zp_current_locale;
	if (is_null($locale)) {
		if (is_null($_zp_current_locale)) {
			$_zp_current_locale = getUserLocale();
			if (empty($_zp_current_locale)) $_zp_current_locale = 'en_US';
		}
		$locale = $_zp_current_locale;
	}
	if($name === "content") { // for the different sizes of content and extracontent textareas
		$rows = "rows='35'";
	} else {
		$rows = "rows='10'";
	}
	$locale = $_zp_current_locale;
	if (preg_match('/^a:[0-9]+:{/', $dbstring)) {
		$strings =unserialize($dbstring);
	} else {
		$strings = array($locale=>$dbstring);
	}
	if (getOption('multi_lingual')) {
		if (is_null($_zp_active_languages)) {
			$_zp_active_languages = generateLanguageList();
		}
		$emptylang = array_flip($_zp_active_languages);
		unset($emptylang['']);
		natsort($emptylang);
		if ($textbox) $class = 'box'; else $class = '';
		echo "<ul class=\"zenpage_language_string_list".$class."\">\n";
		$empty = true;
		foreach ($emptylang as $key=>$lang) {
			if (isset($strings[$key])) {
				$string = $strings[$key];
				if (!empty($string)) {
					unset($emptylang[$key]);
					$empty = false;
					echo '<li><label for="'.$name.'_'.$key.'">';
					if ($textbox) {
						echo $lang;
						echo '<textarea id="'.$name.'_'.$key.'" name="'.$name.'_'.$key.'" class="mceEditor" cols="60"	'.$rows.' style="width:575px;">'.htmlentities($string,ENT_COMPAT,getOption("charset")).'</textarea><a href="javascript:toggleEditor(\''.$name.'_'.$key.'\');">' . gettext('Toggle Editor') . '</a><br /><br />';
					} else {
						echo '<input id="'.$name.'_'.$key.'" name="'.$name.'_'.$key.'" type="text" value="'.$string.'" size="96" style="width:400px;"/>'.$lang;
					}
					echo "</label></li>\n";
				}
			}
		}
		if ($empty) {
			$element = $emptylang[$locale];
			unset($emptylang[$locale]);
			$emptylang = array_merge(array($locale=>$element), $emptylang);
		}
		foreach ($emptylang as $key=>$lang) {
			echo '<li><label for="'.$name.'_'.$key.'">';
			if ($textbox) {
				echo $lang;
				echo '<textarea id="'.$name.'_'.$key.'" name="'.$name.'_'.$key.'" class="mceEditor" cols="60"	'.$rows.' style="width:575px;"></textarea><a href="javascript:toggleEditor(\''.$name.'_'.$key.'\');">' . gettext('Toggle Editor') . '</a><br /><br />';
			} else {
				echo '<input id="'.$name.'_'.$key.'" name="'.$name.'_'.$key.'" type="text" value="" size="96" style="width:400px;"/>'.$lang;
			}
			echo "</label></li>\n";

		}
		echo "</ul>\n";
	} else {
		if (empty($locale)) $locale = 'en_US';
		if (isset($strings[$locale])) {
			$dbstring = $strings[$locale];
		} else {
			$dbstring = array_shift($strings);
		}
		if ($textbox) {
			echo '<textarea id="'.$name.'_'.$locale.'" name="'.$name.'" class="mceEditor" cols="60"	'.$rows.' style="width:600px;">'.htmlentities($dbstring,ENT_COMPAT,getOption("charset")).'</textarea><a href="javascript:toggleEditor(\''.$name.'_'.$locale.'\');">' . gettext('Toggle Editor') . '</a><br /><br />';	
		} else {
			echo '<input id="'.$name.'_'.$locale.'" name="'.$name.'_'.$locale.'" type="text" value="'.$dbstring.'" size="96" style="width:600px;"/>';
		}
	}
}


/**
 * Extracs the first two characters from the Zenphoto locale names like 'de_DE' so that
 * TinyMCE and the Ajax File Manager who use two character locales like 'de' can set their language packs
 *
 * @return string
 */
function getLocaleForTinyMCEandAFM() {
	$locale = substr(getOption("locale"),0,2);
	if (empty($locale)) $locale = 'en';
	return $locale;
}

/**
 * returns the zenpage version string
 *
 * @return string
 */
function getZenpageVersion() {
	$pluginStream = file_get_contents(SERVERPATH . '/' . ZENFOLDER . PLUGIN_FOLDER . 'zenpage.php');
	$str =  $pluginStream;
	$i = strpos($str, '$plugin_version');
	if ($i === false) return false;
	$str = substr($str, $i);
	//$j = strpos($str, ";\n"); // This is wrong - PHP will not treat all newlines as \n.
	$j = strpos($str, ";"); // This is also wrong; it disallows semicolons in strings. We need a regexp.
	$str = substr($str, 0, $j+1);
	eval($str);
	return ($plugin_version);
}

function printZenpageFooter() {
	$footer = sprintf(gettext('<a href="http://zenpage.maltem.de/zenpage/index.php">zenpage</a> version %1$s [%2$s]'), getZenpageVersion(), ZENPHOTO_RELEASE);
	printAdminFooter($footer);
}

/**
 * Calls the configuration file for the rich text editor used for pages and news articles. 
 * Default is TinyMCE, but basically other editors would be possible, too. 
 * The by default included Ajax File Manager does only work with TinyMCE and FCKEditor though.
 *
 */
require_once("texteditorconfig.php");
?>