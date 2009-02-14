<?php 
/**
 * zenpage admin-edit.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
include("zp-functions.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo gettext("zenphoto administration"); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php printTextEditorConfigJS(); ?>
<?php zenpageJSCSS(); codeblocktabsJS(); ?>
</head>
<body>
<?php 
	printLogoAndLinks();
	echo "<div id=\"main\">";
	printTabs("zenpage"); 
	echo "<div id=\"content\">";

	if(empty($_GET['pagenr'])) {
		$page = "";
	} else {
		$page = "&amp;pagenr=".$_GET['pagenr'];
	}

	if(is_AdminEditPage("newsarticle")) {
		zenpageAdminnav("articles"); 
		if(isset($_GET['titlelink'])) {
			$result = new ZenpageNews(urldecode($_GET['titlelink']));
		} else if(isset($_GET['update'])) {
			$result = updateArticle();
		} else if(isset($_GET['add'])) {
			$result = "";
		}
		if(isset($_GET['save'])) {
			$result = addArticle();
		}
		if(isset($_GET['del'])) {	
			deleteArticle();
		}
		$admintype = "newsarticle";
		$additem = gettext("Add Article");
		$updateitem = gettext("Update Article");
		$saveitem = gettext("Save Article");
		$deleteitem = gettext("Delete Article");
		$deletemessage = js_encode(gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!"));
		$themepage = ZENPAGE_NEWS;
	}
	
	if(is_AdminEditPage("page")) {
		zenpageAdminnav("pages"); 
		if(isset($_GET['titlelink'])) {
			$result = new ZenpagePage(urldecode($_GET['titlelink']));
		} else if(isset($_GET['update'])) {
			$result = updatePage();
		} else if(isset($_GET['add'])) {
			$result = "";
		}
		if(isset($_GET['save'])) {
			$result = addPage();
		}
		if(isset($_GET['del'])) {	
			deletePage();
		}
		$admintype = "page";
		$additem = gettext("Add Page");
		$updateitem = gettext("Update Page");
		$saveitem = gettext("Save Page");
		$deleteitem = gettext("Delete Page");
		$deletemessage = js_encode(gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE AND WILL ALSO DELETE ALL SUB PAGES OF THIS PAGE!"));
		$themepage = ZENPAGE_PAGES;
	}

?>
<h1>
<?php
if(is_object($result)) {
	if(is_AdminEditPage("newsarticle")) {
		echo gettext("Edit Article:"); ?> <em><?php checkForEmptyTitle($result->getTitle(),"news"); ?></em>
<? } else if(is_AdminEditPage("page")) {
		 echo gettext("Edit Page:"); ?> <em><?php checkForEmptyTitle($result->getTitle(),"page"); ?></em>
<?php } ?> 
<?php } else {
	if(is_AdminEditPage("newsarticle")) {
		echo gettext("Add Article");
	} else if(is_AdminEditPage("page")) {
		echo gettext("Add Page");
	}
} ?>
</h1>
<p class="buttons">

<?php if(is_AdminEditPage("newsarticle")) { ?>
	<strong><a href="admin-edit.php?<?php echo $admintype; ?>&amp;add" title="<?php echo $additem; ?>"><img src="images/add.png" alt="" /> <?php echo $additem; ?></a></strong>
<?php } else if(is_AdminEditPage("page")) { ?>
	<strong><a href="admin-edit.php?<?php echo $admintype; ?>&amp;add" title="<?php echo $additem; ?>"><img src="images/add.png" alt="" /> <?php echo $additem; ?></a></strong>
<?php } ?>

<span id="tip"><a href="#"><img src="images/info.png" alt="" /><?php echo gettext("Usage tips"); ?></a></span>
<?php if(is_object($result)) { ?> 
	<a href="../../../index.php?p=<?php echo $themepage; ?>&amp;title=<?php printIfObject($result,"titlelink") ;?>" title="<?php echo gettext("View"); ?>"><img src="images/view.png" alt="" /><?php echo gettext("View"); ?></a>
<?php } ?>
</p>
<br clear: both /><br clear: both />

<div id="tips" style="display:none">		
<p><?php echo gettext("Check <em>Edit Titlelink</em> if you need to customize how the title appears in URLs. Otherwise it will be automatically updated to any changes made to the title. If you want to prevent this check <em>Enable permaTitlelink</em> and the titlelink stays always the same (recommended if you use Zenphoto's multilingual mode). <strong>Note: </strong> <em>Edit titlelink</em> overrides the permalink setting.");?>
<br /><?php echo gettext("If you lock an article only the current active author/user or any user with full admin rights will be able to edit it later again!"); ?></p> 
<p><?php echo gettext("<strong>Important:</strong> If you are using Zenphoto's multi-lingual mode the Titlelink is generated from the Title of the currently selected language."); ?></p> 
<p><?php echo gettext("Hint: If you need more space for your text use TinyMCE's full screen mode (Click the blue square on the top right of editor's control bar)."); ?></p> 
</div>

<div class="box" style="padding:15px; margin-top: 10px">

<?php if(is_object($result)) { ?>
<form method="post" action="admin-edit.php?<?php echo $admintype; ?>&amp;update<?php echo $page; ?>" name="update">
<input type="hidden" name="id" value="<?php printIfObject($result,"id");?>" />
<input type="hidden" name="titlelink-old" type="text" id="titlelink-old" value="<?php printIfObject($result,"titlelink"); ?>" />
<input type="hidden" name="lastchange" type="text" id="lastchange" value="<?php echo date('Y-m-d H:i:s'); ?>" />
<input type="hidden" name="lastchangeauthor" type="text" id="lastchangeauthor" value="<?php echo $_zp_current_admin['user']; ?>" />
<input type="hidden" name="hitcounter" type="text" id="hitcounter" value="<?php printIfObject($result,"hitcounter"); ?>" />		
<?php } else { ?>
	<form method="post" name="addnews" action="admin-edit.php?<?php echo $admintype; ?>&amp;save">
<?php } ?>
  <table>
    <tr> 
      <td class="topalign-padding"><?php echo gettext("Title:"); ?></td>
      <td><?php print_language_string_list_zenpage($result->get("title"),"title",false) ;?></td>
    	<td class="rightcolumnmiddle" style="vertical-align: top" rowspan="3"><?php echo gettext("Author:"); ?><?php AuthorSelector(getIfObject($result,"author")) ;?>
    		<?php if(is_object($result)) { ?>
					<p><input name="edittitlelink" type="checkbox" id="edittitlelink" value="1" /> <?php echo gettext("Edit TitleLink"); ?></p>
				<?php } ?>
    		<p><input name="permalink" type="checkbox" id="permalink" value="1" <?php if (is_object($result)) { checkIfChecked($result->getPermalink()); } else { echo "checked='checked'"; } ?> /> <?php echo gettext("Enable permaTitlelink"); ?></p>
     		<p><?php echo gettext("Date:"); ?> <input name="date" type="text" id="date" value="<?php if(is_object($result)) { echo $result->getDatetime(); } else { echo date('Y-m-d H:i:s'); } ?>" /></p>
    		<p>
				<?php if(getIfObject($result,"lastchangeauthor") != "") { ?>
				<?php printf(gettext('Last change: %1$s by %2$s'),$result->getLastchange(),$result->getLastchangeauthor()); 
					} ?>
				</p>
    	  <input name="show" type="checkbox" id="show" value="1" <?php checkIfChecked(getIfObject($result,"show"));?> /> <?php echo gettext("Published"); ?><br />
    	 <input name="commentson" type="checkbox" id="commentson" value="1" <?php checkIfChecked(getIfObject($result,"commentson"));?> /> <?php echo gettext("Comments on"); ?><br />
    	 <input name="locked" type="checkbox" id="locked" value="1" <?php checkIfChecked(getIfObject($result,"locked"));?>; /> <?php echo gettext("Locked for changes"); ?><br />
			<p><?php if(is_object($result)) { printf(gettext('%1$s hits'),$result->getHitcounter()); ?> <input name="resethitcounter" type="checkbox" id="resethitcounter" value="1" /> <?php echo gettext("Reset hitcounter"); } ?></p>
    	<p class="buttons"><button class="submitbutton" type="submit" title="<?php echo $updateitem; ?>"><img src="../../images/pass.png" alt="" /><strong><?php if(is_object($result)) { echo $updateitem; } else { echo $saveitem; } ?></strong></button></p>
			<br style="clear:both" />
			<p class="buttons"><button class="submitbutton" type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button></p>
			<br style="clear:both" />
			<?php if(is_object($result)) { ?>
    	<p class="buttons"><a class="submitbutton" href="javascript: confirmDeleteImage('admin-edit.php?<?php echo $admintype; ?>&amp;add&amp;del=<?php printIfObject($result,"id"); echo $page; ?><?php if(is_AdminEditPage("page")) { echo "&amp;sortorder=".$result->getSortorder(); } ?>','<?php echo $deletemessage; ?>')" title="<?php echo $deleteitem; ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo $deleteitem; ?></strong></a></p>
    	<br /><br />
			<?php } ?>
    	<p>
    	<?php
    if(is_AdminEditPage("newsarticle")) {
    	echo gettext("Categories:"); ?>
    	<?php 
    	if(is_object($result)) {
    	 	printCategorySelection(getIfObject($result,"id")); 
    	} else {
    		printCategorySelection("","all");
    	}
		} ?>
      </p>
    </td>
   </tr>
    <tr>
			<td><?php echo gettext("TitleLink:"); ?></td>
			<td>
			<?php if(is_object($result)) { ?>
				<input name="titlelink" class="inputfield" type="text" size="96" id="titlelink" value="<?php printIfObject($result,"titlelink");?>" />
			<?php } else {
				echo gettext("A search engine friendly <em>titlelink</em> (aka slug) without special characters to be used in URLs is generated from the title of the currently chosen language automatically. You can edit it manually later after saving if necessary.");
			  }
			 ?>
			</td>
	 </tr>
    <tr> 
			<td class="topalign-padding"><?php echo gettext("Content:"); ?></td>
			<td class="topalign-padding"><?php print_language_string_list_zenpage($result->get("content"),"content",TRUE) ;?></td>
    </tr>
    <tr> 
			<td class="topalign-padding"><?php echo gettext("ExtraContent:"); ?></td>
			<td><?php print_language_string_list_zenpage(sanitize(htmlentities($result->get("extracontent"),ENT_COMPAT,getOption("charset")),0),"extracontent",TRUE) ;?></td>
			<td class="rightcolumn"><?php echo gettext("Here you can enter extra content for example to be printed on the sidebar"); ?></td>
    <tr> 
		<td class="topalign-nopadding"><br /><?php echo gettext("Codeblocks:"); ?></td>
		<td class="topalign-padding">
		<br />
			<div class="tabs">
				<ul class="tabNavigation">
					<li><a href="#first"><?php echo gettext("Codeblock 1"); ?></a></li>
					<li><a href="#second"><?php echo gettext("Codeblock 2"); ?></a></li>
					<li><a href="#third"><?php echo gettext("Codeblock 3"); ?></a></li>
				</ul>
    			<?php
							$getcodeblock = getIfObject($result,"codeblock");
							if(!empty($getcodeblock)) {
								$codeblock = unserialize(base64_decode($getcodeblock));
								$codeblock[1] = stripslashes($codeblock[1]);
								$codeblock[2] = stripslashes($codeblock[2]);
								$codeblock[3] = stripslashes($codeblock[3]);
							} else {
								$codeblock[1] = "";
								$codeblock[2] = "";
								$codeblock[3] = "";
							}
							?>
				<div id="first">
					<textarea name="codeblock1" id="codeblock1"><?php echo stripslashes($codeblock[1]); ?></textarea>
				</div>
				<div id="second">
					<textarea name="codeblock2" id="codeblock2"><?php echo stripslashes($codeblock[2]); ?></textarea>
				</div>
				<div id="third">
					<textarea name="codeblock3" id="codeblock3"><?php echo stripslashes($codeblock[3]); ?></textarea></div>
				</div>
			</div>
		</td>
		<td class="rightcolumn"><br />
			<p><?php echo gettext("Use the codeblock text fields if you need to enter php code (for example Zenphoto functions) or javascript code."); ?></p> 
			<p><?php echo gettext("You also can use the codeblock fields as custom fields."); ?></p> 
			<p><?php echo gettext("Note that your theme must be setup to use the codeblock functions. Note also that codeblock fields are not multi-lingual."); ?></p>
		</td>
		</tr>
  </table>
</form>
</div>
</div>
<?php printZenpageFooter(); ?>
</body>
</html>