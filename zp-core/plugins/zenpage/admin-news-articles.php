<?php 
/**
 * zenpage admin-news-articles.php
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
<?php zenpageJSCSS(); ?> 
</head>
<body>
<?php 
	printLogoAndLinks();
	echo "<div id=\"main\">";
	printTabs("zenpage"); 
	echo "<div id=\"content\">";
	zenpageAdminnav("articles"); 
if(isset($_GET['del'])) {	
  deleteArticle();
} 
// publish or unpublish page by click 
if(isset($_GET['publish'])) { 
  publishPageOrArticle("news",$_GET['id']);
}
if(isset($_GET['commentson'])) { 
  enableComments("news");
}
if(isset($_GET['hitcounter'])) {
	resetPageOrArticleHitcounter("news");
}
?>
<h1><?php echo gettext("Articles"); ?> 
<?php
if (isset($_GET['category'])) {
  echo "<em>".getCategoryTitle($_GET['category'])."</em>";
}
if (isset($_GET['date'])) {
  echo "<em><small> (".$_GET['date'].")</small></em>";
}

if(isset($_GET['published']) AND $_GET['published'] === "no") {
	$published = "unpublished";
} 
if(isset($_GET['published']) AND $_GET['published'] === "yes") {
	$published = "published";
} 
if(!isset($_GET['published'])) {
	$published = "all";
}

if(isset($_GET['category'])) {
  $result = getNewsArticles(getOption('zenpage_admin_articles'),$_GET['category'],$published,TRUE);
} else {
  $result = getNewsArticles(getOption('zenpage_admin_articles'),"",$published,TRUE);
}

?>
<span class="zenpagestats"><?php printNewsPagesStatistic("news");?></span></h1>
<div style="margin-bottom:-5px"><div style="float:left; margin-right: 15px; margin-top: 2px;">
	<div class="buttons"><strong><a href="admin-edit.php?newsarticle&amp;add" title="<?php echo gettext("Add Article"); ?>"><img src="images/add.png" alt="" /> <?php echo gettext("Add Article"); ?></a></strong>
	<strong><a href="<?php echo WEBPATH.'/'.ZENFOLDER.PLUGIN_FOLDER; ?>tiny_mce/plugins/ajaxfilemanager/ajaxfilemanager.php?language=<?php echo getLocaleForTinyMCEandAFM(); ?>&KeepThis=true&TB_iframe=true&height=480&width=750" class="thickbox"><img src="images/folder.png" /> <?php echo gettext("Manage files"); ?></a></strong>
	</div>
</div>
<?php printCategoryDropdown(); printArticleDatesDropdown(); printUnpublishedDropdown(); ?>
<?php //echo "optionpath: ".getNewsAdminOptionPath(true,true,true); // debugging only; ?>
<br style="clear: both" /><?php printArticlesPageNav($published); ?><br />
</div>

<table class="bordered">
 <tr> 
  <th colspan="9"><strong><?php echo gettext("Edit this article"); ?></strong></th>
 </tr>
<?php foreach ($result as $article) { 
	$articleobj = new ZenpageNews($article['titlelink']);
	
	?>
 <tr> 
  <td> 
   <?php 
   if(checkIfLocked($articleobj)) {
   	 echo "<a href='admin-edit.php?newsarticle&amp;titlelink=".urlencode($articleobj->getTitlelink())."&pagenr=".getCurrentAdminNewsPage()."' title='".truncate_string(strip_tags($articleobj->getContent()),300)."'>"; checkForEmptyTitle($articleobj->getTitle(),"news"); echo "</a>".checkHitcounterDisplay($articleobj->getHitcounter()); 
   } else {
   	 echo get_language_string($articleobj->getTitle())."</a>".checkHitcounterDisplay($article->getHitcounter()); 
   }
   ?>
  
  </td>
  <td><?php echo $articleobj->getDatetime(); ?></td>
  <td>
  <?php printArticleCategories($articleobj) ?>
  </td> 
  <td>
  <?php echo htmlspecialchars($articleobj->getAuthor()); ?>
  </td> 
  
  <?php if(checkIfLocked($articleobj)) { ?>
	<td class="icons">
 		 <a href="?publish=<?php echo $articleobj->getShow(); ?>&id=<?php echo $articleobj->getID(); 
 		 if(isset($_GET['page'])) { echo "&page=".$_GET['page']; } 
 		 if(isset($_GET['date'])) { echo "&date=".$_GET['date']; }
 		 if(isset($_GET['category'])) { echo "&category=".$_GET['category']; }
 		 ?>" title="<?php echo gettext("Publish or unpublish article"); ?>">
 		 <?php echo checkIfPublished($articleobj->getShow()); ?></a>
	</td>
 	<td class="icons">
		<a href="?commentson=<?php echo $articleobj->getCommentson(); ?>&id=<?php echo $articleobj->getID(); ?>" title="<?php echo gettext("Enable or disable comments"); ?>">
		<?php echo checkIfCommentsAllowed($articleobj->getCommentson()); ?></a>
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
  	<a href="../../../index.php?p=<?php echo ZENPAGE_NEWS; ?>&title=<?php echo $articleobj->getTitlelink();?>" title="<?php echo gettext("View article"); ?>">
  	<img src="images/view.png" alt="<?php echo gettext("View article"); ?>" />
  	</a>
  </td> 
     
	<?php if(checkIfLocked($articleobj)) { ?>
	<td class="icons">
		<a href="?hitcounter=1&id=<?php echo $articleobj->getID();?>" title="<?php echo gettext("Reset hitcounter"); ?>">
		<img src="../../images/reset.png" alt="<?php echo gettext("Reset hitcounter"); ?>" /></a>
	</td>
	<td class="icons">
		<a href="javascript: confirmDeleteImage('admin-news-articles.php?del=<?php echo $articleobj->getID(); ?>','<?php echo js_encode(gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!")); ?>')" title="<?php echo gettext("Delete article"); ?>">
		<img src="../../images/fail.png" alt="<?php echo gettext("Delete article"); ?>" /></a>
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
 <?php	} ?> 
</table>
<?php printArticlesPageNav(); ?>

<ul class="iconlegend">
<li><img src="../../images/pass.png" alt="" /><img	src="../../images/action.png" alt="" /><?php echo gettext("Published/Not published"); ?></li>
<li><img src="images/comments-on.png" alt="" /><img src="images/comments-off.png" alt="" /><?php echo gettext("Comments on/off"); ?></li>
<li><img src="images/view.png" alt="" /><?php echo gettext("View page"); ?></li>
<li><img src="../../images/reset.png" alt="" /><?php echo gettext("Reset hitcounter"); ?></li>
<li><img src="../../images/fail.png" alt="" /><?php echo gettext("Delete page"); ?></li>
</ul>
</div>
<?php printZenpageFooter(); ?>
</body>
</html>
