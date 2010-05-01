<?php 
/**
 * zenpage admin-news-articles.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
include("zp-functions.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
   <html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo gettext("zenphoto administration"); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php zenpageJSCSS(); ?> 
</head>
<body>
<?php 
printLogoAndLinks();
?>
<div id="main">
	<?php
	printTabs('articles'); 
	?>
	<div id="content">
		<?php
		checkRights('articles'); 
		printSubtabs('articles');
		?>
		<div id="tab_articles" class="tabbox">
			<?php	
			if(isset($_POST['processcheckeditems'])) {
				processZenpageCheckboxAction('news');
			}
			if(isset($_GET['del'])) {	
			  deleteArticle();
			} 
			// publish or unpublish page by click 
			if(isset($_GET['publish'])) { 
			  publishPageOrArticle('news',$_GET['id']);
			}
			if(isset($_GET['skipscheduling'])) {
				skipScheduledPublishing('news',$_GET['id']);
			}
			if(isset($_GET['commentson'])) { 
			  enableComments('news');
			}
			if(isset($_GET['hitcounter'])) {
				resetPageOrArticleHitcounter('news');
			}
			?>
			<h1><?php echo gettext('Articles'); ?> 
			<?php
			if (isset($_GET['category'])) {
			  echo "<em>".getCategoryTitle($_GET['category']).'</em>';
			}
			if (isset($_GET['date'])) {
			  echo '<em><small> ('.$_GET['date'].')</small></em>';
			}
			
			if(isset($_GET['published']) AND $_GET['published'] == 'no') {
				$published = 'unpublished';
			} 
			if(isset($_GET['published']) AND $_GET['published'] == 'yes') {
				$published = 'published';
			} 
			if(!isset($_GET['published'])) {
				$published = 'all';
			}
			
			if(isset($_GET['category'])) {
			  $result = getNewsArticles(getOption('zenpage_admin_articles'),$_GET['category'],$published);
			} else {
			  $result = getNewsArticles(getOption('zenpage_admin_articles'),"",$published);
			}	
			?>
			<span class="zenpagestats"><?php printNewsStatistic();?></span></h1>
			<form action="admin-news-articles.php" method="post" name="checkeditems">
					<input name="processcheckeditems" type="hidden" value="Save Order" />
				<div style="margin-bottom:-5px">
					<div style="float:left; margin-right: 15px; margin-top: 2px;">
						<div class="buttons">
							<button type="submit" title="<?php echo gettext('Process checked items'); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext('Process checked items'); ?></strong></button>
							<strong><a href="admin-edit.php?newsarticle&amp;add" title="<?php echo gettext('Add Article'); ?>"><img src="images/add.png" alt="" /> <?php echo gettext("Add Article"); ?></a></strong>
							<strong><a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER; ?>/tiny_mce/plugins/ajaxfilemanager/ajaxfilemanager.php?language=<?php echo getLocaleForTinyMCEandAFM(); ?>" class="colorbox">
							<img src="images/folder.png" alt="" /> <?php echo gettext('Manage files'); ?></a></strong>
						</div>
					</div>
					<?php printCategoryDropdown(); printArticleDatesDropdown(); printUnpublishedDropdown(); ?>
					<?php //echo "optionpath: ".getNewsAdminOptionPath(true,true,true); // debugging only; ?>
					<br style="clear: both" />
					<?php printArticlesPageNav($published); ?>
					<br />
				</div>
				
				<table class="bordered">
					<tr> 
				  	<th colspan="11"><strong><?php echo gettext('Edit this article'); ?></strong>
				  	<?php
				  	$checkarray = array(
				  	gettext('*Set action for checked items*') => 'noaction',
				  	gettext('Delete') => 'deleteall',
				  	gettext('Set to visible') => 'showall',
				  	gettext('Set to hidden') => 'hideall',
				  	gettext('Disable comments') => 'commentsoff',
				  	gettext('Enable comments') => 'commentson',
				  	gettext('Reset hitcounter') => 'resethitcounter',
				  	);
				  	?> <span style="float: right">
						  	<select name="checkallaction" id="checkallaction" size="1">
							  	<?php generateListFromArray(array('noaction'), $checkarray,false,true); ?>
								</select>
						</span>
				  	
				  	</th>
						</tr>
						<tr class="newstr">
							<td class="subhead" colspan="11">
								<label style="float: right"><?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
								</label>
							</td>
						</tr>
					<?php
					foreach ($result as $article) { 
						$article = new ZenpageNews($article['titlelink']);
						?>
						<tr class="newstr"> 
						  <td> 
						   <?php 
						   if(checkIfLocked($article)) {
						   	 echo '<a href="admin-edit.php?newsarticle&amp;titlelink='.urlencode($article->getTitlelink()).'&amp;pagenr='.getCurrentAdminNewsPage().'">'; checkForEmptyTitle($article->getTitle(),"news"); echo '</a>'.checkHitcounterDisplay($article->getHitcounter()); 
						   } else {
						   	 echo $article->getTitle().'</a>'.checkHitcounterDisplay($article->getHitcounter()); 
						   }
						   ?>
						  
						  </td>
						  <td>
						  <small>
						  <?php 
						  checkIfScheduled($article);
						  checkIfExpires($article);
						  ?>
						  </small>
						  </td>
						  <td>
						  <small>
						  <?php printArticleCategories($article) ?><br />
						  </small>
						  </td> 
			  		  <td>
						  <?php echo htmlspecialchars($article->getAuthor()); ?>
						  </td> 
						  <td class="icons">
						  <?php
						  	if(inProtectedNewsCategory(true,$article)) {
						  		echo '<img src="../../images/lock.png" style="border: 0px;" alt="'.gettext('Password protected').'" title="'.gettext('Password protected').'" />';
						  	} 
						  	?>
						  </td>
						  
						  <?php if(checkIfLocked($article)) { ?>
							<td class="icons">
							<?php  
								printPublishIconLink($article,'news'); ?>
							</td>
						 	<td class="icons">
								<a href="?commentson=<?php echo $article->getCommentsAllowed(); ?>&amp;id=<?php echo $article->getID(); ?>" title="<?php echo gettext('Enable or disable comments'); ?>">
								<?php echo checkIfCommentsAllowed($article->getCommentsAllowed()); ?></a>
						 	</td>
						 		 <?php } else { ?>
							<td class="icons">
						    	<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
							</td>
							<td class="icons">
						    	<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
							</td>
						  <?php } ?>
						 
						  <td class="icons">
						  	<a href="../../../index.php?p=<?php echo ZENPAGE_NEWS; ?>&amp;title=<?php echo $article->getTitlelink();?>" title="<?php echo gettext('View article'); ?>">
						  	<img src="images/view.png" alt="<?php echo gettext('View article'); ?>" />
						  	</a>
						  </td> 
						     
							<?php
							if(checkIfLocked($article)) {
								?>
								<td class="icons">
									<a href="?hitcounter=1&amp;id=<?php echo $article->getID();?>" title="<?php echo gettext('Reset hitcounter'); ?>">
									<img src="../../images/reset.png" alt="<?php echo gettext('Reset hitcounter'); ?>" /></a>
								</td>
								<td class="icons">
									<a href="javascript:confirmDeleteImage('admin-news-articles.php?del=<?php echo $article->getID(); ?>','<?php echo js_encode(gettext('Are you sure you want to delete this article? THIS CANNOT BE UNDONE!')); ?>')" title="<?php echo gettext('Delete article'); ?>">
									<img src="../../images/fail.png" alt="<?php echo gettext('Delete article'); ?>" /></a>
								</td>
								<td class="icons">
									<input type="checkbox" name="ids[]" value="<?php echo $article->getID(); ?>" onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
								</td>
								</tr>
								<?php } else { ?>
								<td class="icons">
									<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
								</td>
								<td class="icons">
									<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
								</td>
								<td class="icons">
									<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
								</td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?> 
				</table>
				<p class="buttons"><button type="submit" title="<?php echo gettext('Process checked items'); ?>"><img src="../../images/pass.png" alt="" /><strong><?php echo gettext('Process checked items'); ?></strong></button></p>
				
				</form>
				<?php printArticlesPageNav(); ?>
				<?php printZenpageIconLegend(); ?>
		</div> <!-- tab_articles -->
	</div> <!-- content -->
</div> <!-- main -->

<?php printAdminFooter(); ?>
</body>
</html>
