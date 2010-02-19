<?php
/**
 * zenpage admin-edit.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
include('zp-functions.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo gettext("zenphoto administration"); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php printTextEditorConfigJS(); ?>
<?php zenpageJSCSS(); codeblocktabsJS(); ?>
<script type="text/javascript">
<?php if(!isset($_GET['add'])) { // prevent showing the message when adding page or article ?>
$(document).ready(function() {
	$('#date').change(function() {
		if($('#date').val() > '<?php echo date('Y-m-d H:i:s'); ?>') {
			$(".scheduledpublishing").html('<?php echo addslashes(gettext('Future publishing date:')); ?>');
		} else {
			$(".scheduledpublishing").html('');
		}
	});
		if($('#date').val() > '<?php echo date('Y-m-d H:i:s'); ?>') {
			$(".scheduledpublishing").html('<?php echo addslashes(gettext('Future publishing date:')); ?>');
		} else {
			$(".scheduledpublishing").html('');
		}
	$('#expiredate').change(function() {
		if($('#expiredate').val() > '<?php echo date('Y-m-d H:i:s'); ?>' || $('#expiredate').val() === '') {
			$(".expire").html('');
		} else {
			$(".expire").html('<?php echo addslashes(gettext('This is not a future date!')); ?>');
		}
	});
	if(jQuery('#edittitlelink:checked').val() != 1) {
		$('#titlelink').attr("disabled", true);
	}
	$('#edittitlelink').change(function() {
		if(jQuery('#edittitlelink:checked').val() == 1) {
			$('#titlelink').removeAttr("disabled");
		} else {
			$('#titlelink').attr("disabled", true);
		}
	});
});
<?php } ?>
</script>
</head>
<body>
<?php
	$result = '';
	$saveitem = '';
	printLogoAndLinks();
	echo '<div id="main">';
	if(is_AdminEditPage('newsarticle')) {
		printTabs('articles');
	} else {
		printTabs('pages');
	}
	echo '<div id="content">';

	if(empty($_GET['pagenr'])) {
		$page = "";
	} else {
		$page = '&amp;pagenr='.$_GET['pagenr'];
	}

	if(is_AdminEditPage('newsarticle')) {
		checkRights('articles');
		if (!empty($page)) {
			$zenphoto_tabs['articles']['subtabs'][gettext('articles')] .= $page;
		}
		printSubtabs('articles');
		?>
		<div id="tab_articles" class="tabbox">
		<?php
		if(isset($_GET['titlelink'])) {
			$result = new ZenpageNews(urldecode($_GET['titlelink']));
		} else if(isset($_GET['update'])) {
			$result = updateArticle();
		}
		if(isset($_GET['save'])) {
			$result = addArticle();
		}
		if(isset($_GET['del'])) {
			deleteArticle();
		}
		$admintype = 'newsarticle';
		$additem = gettext('Add Article');
		$updateitem = gettext('Update Article');
		$saveitem = gettext('Save Article');
		$deleteitem = gettext('Delete Article');
		$deletemessage = js_encode(gettext('Are you sure you want to delete this article? THIS CANNOT BE UNDONE!'));
		$themepage = ZENPAGE_NEWS;
	}

	if(is_AdminEditPage('page')) {
		if(isset($_GET['titlelink'])) {
			$result = new ZenpagePage(urldecode($_GET['titlelink']));
		} else if(isset($_GET['update'])) {
			$result = updatePage();
		}
		if(isset($_GET['save'])) {
			$result = addPage();
		}
		if(isset($_GET['del'])) {
			deletePage();
		}
		$admintype = 'page';
		$additem = gettext('Add Page');
		$updateitem = gettext('Update Page');
		$saveitem = gettext('Save Page');
		$deleteitem = gettext('Delete Page');
		$deletemessage = js_encode(gettext('Are you sure you want to delete this page? THIS CANNOT BE UNDONE AND WILL ALSO DELETE ALL SUB PAGES OF THIS PAGE!'));
		$themepage = ZENPAGE_PAGES;
	}

?>
<h1>
<?php
if(is_object($result)) {
	if(is_AdminEditPage('newsarticle')) {
		echo gettext('Edit Article:'); ?> <em><?php checkForEmptyTitle($result->getTitle(),'news');
		if(is_object($result)) {
			if($result->getDatetime() >= date('Y-m-d H:i:s')) {
				echo ' <small><strong id="cheduldedpublishing">'.gettext('(Article scheduled for publishing)').'</strong></small>';
				if($result->getShow() != 1) {
					echo '<p class="scheduledate"><small>'.gettext('Note: Scheduled publishing is not active unless the article is also set to <em>published</em>').'</small></p>';
				}
			}
		}
	 ?>
		</em>
<? } else if(is_AdminEditPage('page')) {
	echo gettext('Edit Page:'); ?> <em><?php checkForEmptyTitle($result->getTitle(),'page');
	if(is_object($result)) {
		if($result->getDatetime() >= date('Y-m-d H:i:s')) {
			echo ' <small><strong id="scheduldedpublishing">'.gettext('(Page scheduled for publishing)').'</strong></small>';
			if($result->getShow() != 1) {
				echo '<p class="scheduledate"><small>'.gettext('Note: Scheduled publishing is not active unless the page is also set to <em>published</em>').'</small></p>';
			}
		}
	}
	?> </em>
<?php } ?>
<?php } else {
	if(is_AdminEditPage('newsarticle')) {
		echo gettext('Add Article');
	} else if(is_AdminEditPage('page')) {
		echo gettext('Add Page');
	}
} ?>
</h1>
<p class="buttons">

<?php if(is_AdminEditPage("newsarticle")) { ?>
	<strong><a href="admin-edit.php?<?php echo $admintype; ?>&amp;add" title="<?php echo $additem; ?>"><img src="images/add.png" alt="" /> <?php echo $additem; ?></a></strong>
<?php } else if(is_AdminEditPage("page")) { ?>
	<strong><a href="admin-edit.php?<?php echo $admintype; ?>&amp;add" title="<?php echo $additem; ?>"><img src="images/add.png" alt="" /> <?php echo $additem; ?></a></strong>
<?php } ?>
<strong><a href="<?php echo WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER; ?>/tiny_mce/plugins/ajaxfilemanager/ajaxfilemanager.php?language=<?php echo getLocaleForTinyMCEandAFM(); ?>" class="colorbox">
							<img src="images/folder.png" alt="" /> <?php echo gettext("Manage files"); ?></a></strong>
<span id="tip"><a href="#"><img src="images/info.png" alt="" /><?php echo gettext("Usage tips"); ?></a></span>
<?php if(is_object($result)) { ?>
	<a href="../../../index.php?p=<?php echo $themepage; ?>&amp;title=<?php printIfObject($result,"titlelink") ;?>" title="<?php echo gettext("View"); ?>"><img src="images/view.png" alt="" /><?php echo gettext("View"); ?></a>
<?php } ?>
</p>
<br style="clear: both" /><br style="clear: both" />

<div id="tips" style="display:none">
<br />
<h2><?php echo gettext("Usage tips"); ?></h2>
<p><?php echo gettext("Check <em>Edit Titlelink</em> if you need to customize how the title appears in URLs. Otherwise it will be automatically updated to any changes made to the title. If you want to prevent this check <em>Enable permaTitlelink</em> and the titlelink stays always the same (recommended if you use Zenphoto's multilingual mode). <strong>Note: </strong> <em>Edit titlelink</em> overrides the permalink setting.");?>
<br /><?php echo gettext("If you lock an article only the current active author/user or any user with full admin rights will be able to edit it later again!"); ?></p>
<p><?php echo gettext("<strong>Important:</strong> If you are using Zenphoto's multi-lingual mode the Titlelink is generated from the Title of the currently selected language."); ?></p>
<?php if(is_AdminEditPage("newsarticle")) { ?>
<p><?php echo gettext("<em>Custom article shortening:</em> You can set a custom article shorten length for the news loop excerpts by using the standard TinyMCE <em>page break</em> plugin button. This will override the general shorten length set on the plugin option then."); ?></p>
<?php } ?>
<p><?php echo gettext("<em>Scheduled publishing:</em> To automatically publish a page/news article in the future set it to 'published' and enter a future date in the date field manually. Note this works on server time!"); ?></p>
<p><?php echo gettext("<em>Expiration date:</em> Enter a future date in the date field manually to set a date the page or article will be set unpublished automatically. After the page/article has been expired it can only be published again if the expiration date is deleted. Note this works on server time!"); ?></p>
<p><?php echo gettext("<em>ExtraContent:</em> Here you can enter extra content for example to be printed on the sidebar"); ?></p>
<p><?php echo gettext("<em>Codeblocks:</em> Use these fields if you need to enter php code (for example Zenphoto functions) or javascript code."); ?>
<?php echo gettext("You also can use the codeblock fields as custom fields."); ?>
<?php echo gettext("Note that your theme must be setup to use the codeblock functions. Note also that codeblock fields are not multi-lingual."); ?>
</p>
<p><?php echo gettext("Hint: If you need more space for your text use TinyMCE's full screen mode (Click the blue square on the top right of editor's control bar)."); ?></p>
</div>
<?php if(is_AdminEditPage("page")) { ?>
<div class="box" style="padding:15px; margin-top: 10px">
<?php } else { ?>
<div style="padding:15px; margin-top: 10px">
<?php } ?>
<?php if(is_object($result)) { ?>
<form method="post" action="admin-edit.php?<?php echo $admintype; ?>&amp;update<?php echo $page; ?>" name="update">
<input type="hidden" name="id" value="<?php printIfObject($result,"id");?>" />
<input type="hidden" name="titlelink-old" id="titlelink-old" value="<?php printIfObject($result,"titlelink"); ?>" />
<input type="hidden" name="lastchange" id="lastchange" value="<?php echo date('Y-m-d H:i:s'); ?>" />
<input type="hidden" name="lastchangeauthor" id="lastchangeauthor" value="<?php echo $_zp_current_admin['user']; ?>" />
<input type="hidden" name="hitcounter" id="hitcounter" value="<?php printIfObject($result,"hitcounter"); ?>" />
<?php } else { ?>
	<form method="post" name="addnews" action="admin-edit.php?<?php echo $admintype; ?>&amp;save">
<?php } ?>
	<table>
		<tr>
			<td class="topalign-padding"><?php echo gettext("Title:"); ?></td>
			<td class="middlecolumn"><?php print_language_string_list_zenpage(getIfObject($result,"title"),"title",false);?></td>
			<td class="rightcolumnmiddle" rowspan="5">
			
			
			<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Publish"); ?></h2>
				<div class="box-edit-zenpage">
				<p><?php echo gettext("Author:"); ?> <?php AuthorSelector(getIfObject($result,"author")) ;?></p>
				<?php if(is_object($result)) { ?>
				<p class="checkbox">
				<input name="edittitlelink" type="checkbox" id="edittitlelink" value="1" />
				<label for="edittitlelink"><?php echo gettext("Edit TitleLink"); ?></label>
				</p>
				<?php } ?>
				<p class="checkbox">
				<input name="permalink" type="checkbox" id="permalink" value="1" <?php if (is_object($result)) { checkIfChecked($result->getPermalink()); } else { echo 'checked="checked"'; } ?> />
				<label for="permalink"><?php echo gettext("Enable permaTitlelink"); ?></label>
				</p>
				<p class="checkbox">
				<input name="show" type="checkbox" id="show" value="1" <?php checkIfChecked(getIfObject($result,"show"));?> />
				<label for="show"><?php echo gettext("Published"); ?></label>
				</p>
				<p class="checkbox">
				<input name="locked" type="checkbox" id="locked" value="1" <?php checkIfChecked(getIfObject($result,"locked")); ?> />
				<label for="locked"><?php echo gettext("Locked for changes"); ?></label>
				</p>

				<?php
				if(is_AdminEditPage("newsarticle")) {
					echo zp_apply_filter('publish_article_utilities', '');
				} else {
					echo zp_apply_filter('publish_page_utilities', '');
				}

				?>

				<p class="buttons"><button class="submitbutton" type="submit" title="<?php echo $updateitem; ?>"><img src="../../images/pass.png" alt="" /><strong><?php if(is_object($result)) { echo $updateitem; } else { echo $saveitem; } ?></strong></button></p>
				<br style="clear:both" />
				<p class="buttons"><button class="submitbutton" type="reset" title="<?php echo gettext("Reset"); ?>"><img src="../../images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button></p>
				<br style="clear:both" />
				<?php if(is_object($result)) { ?>
				<p class="buttons"><a class="submitbutton" href="javascript:confirmDeleteImage('admin-edit.php?<?php echo $admintype; ?>&amp;add&amp;del=<?php printIfObject($result,"id"); echo $page; ?><?php if(is_AdminEditPage("page")) { echo "&amp;sortorder=".$result->getSortorder(); } ?>','<?php echo $deletemessage; ?>')" title="<?php echo $deleteitem; ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo $deleteitem; ?></strong></a></p>
				<br style="clear:both" />
				<?php } ?>
				</div>
				<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Date"); ?></h2>
				<div class="box-edit-zenpage">
				<p>
				
				<script type="text/javascript">
					$(function() {
						$("#date").datepicker({
							showOn: 'button',
							buttonImage: '../../images/calendar.png',
							buttonText: '<?php echo gettext('calendar'); ?>',
							buttonImageOnly: true
							});
					});
				</script>

				<strong class='scheduledpublishing'></strong>
				<input name="date" type="text" id="date" value="<?php if(is_object($result)) { echo $result->getDatetime(); } else { echo date('Y-m-d H:i:s'); } ?>" />
				</p>
				<hr />
				<strong class='expire'></strong>
				<p>
				
				<script type="text/javascript">
					$(function() {
						$("#expiredate").datepicker({
							showOn: 'button',
							buttonImage: '../../images/calendar.png',
							buttonText: '<?php echo gettext('calendar'); ?>',
							buttonImageOnly: true
							});
					});
				</script>

				<?php echo gettext("Expiration date:"); ?><br />
				<input name="expiredate" type="text" id="expiredate" value="<?php if(is_object($result)) { if($result->getExpireDate() != NULL) { echo $result->getExpireDate();} } ?>" />
				</p>
				<?php if(getIfObject($result,"lastchangeauthor") != "") { ?>
				<hr /><p><?php printf(gettext('Last change:<br />%1$s<br />by %2$s'),$result->getLastchange(),$result->getLastchangeauthor()); ?>
				</p>
				<?php	} ?>
				</div>
			
				<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("General"); ?></h2>
				<div class="box-edit-zenpage">
		
				<p class="checkbox">
				<input name="commentson" type="checkbox" id="commentson" value="1" <?php checkIfChecked(getIfObject($result,"commentson"));?> />
				<label for="commentson"> <?php echo gettext("Comments on"); ?></label>
				</p>
				<?php if(is_object($result)) { ?>
				<p class="checkbox">
				<input name="resethitcounter" type="checkbox" id="resethitcounter" value="1" />
				<label for="resethitcounter"> <?php printf(gettext('Reset hitcounter (Hits: %1$s)'),$result->getHitcounter()); ?></label>
				</p>
				<?php } ?>
				<?php echo zp_apply_filter('general_zenpage_utilities', '', $result); ?>
				</div>
				
				<?php
				if (is_object($result)) {
					?>
					<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Tags"); ?></h2>
					<div id="zenpagetags">
						<?php	tagSelector($result, 'tags_', false, getTagOrder());	?>
				</div>
				<br />
				<?php
				}
				
				if (is_AdminEditPage("newsarticle")) {
					?>
					<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Categories"); ?></h2>
											<?php
						if(is_object($result)) {
							printCategorySelection(getIfObject($result,"id"));
						} else {
							printCategorySelection("","all");
						}
						?>
						<?php
				}
				?>

		</td>
	 </tr>
		<tr>
			<td><?php echo gettext("TitleLink:"); ?></td>
			<td width="175">
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
			<td><?php print_language_string_list_zenpage(getIfObject($result,"content"),"content",TRUE) ;?></td>
		</tr>
		<tr>
			<td class="topalign-padding"><?php echo gettext("ExtraContent:"); ?></td>
			<td><?php print_language_string_list_zenpage(getIfObject($result,"extracontent"),"extracontent",TRUE) ;?></td>
		</tr>
		<tr>
		<td class="topalign-nopadding"><br /><?php echo gettext("Codeblocks:"); ?></td>
		<td>
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
								$codeblock = unserialize($getcodeblock);
							} else {
								$codeblock[1] = "";
								$codeblock[2] = "";
								$codeblock[3] = "";
							}
							?>
				<div id="first">
					<textarea name="codeblock1" id="codeblock1" rows="40" cols="60"><?php echo $codeblock[1]; ?></textarea>
				</div>
				<div id="second">
					<textarea name="codeblock2" id="codeblock2" rows="40" cols="60"><?php echo $codeblock[2]; ?></textarea>
				</div>
				<div id="third">
					<textarea name="codeblock3" id="codeblock3" rows="40" cols="60"><?php echo $codeblock[3]; ?></textarea>
				</div>
			</div>
		</td>
		</tr>
	</table>
</form>
</div>
</div>
</div>
<?php if(is_AdminEditPage("newsarticle")) { ?>
</div>
<?php } ?>
<?php printAdminFooter(); ?>
</body>
</html>