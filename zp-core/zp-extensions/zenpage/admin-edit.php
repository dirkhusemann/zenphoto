<?php
/**
 * zenpage admin-edit.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage zenpage
 */
define("OFFSET_PATH",4); 
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
require_once(dirname(dirname(dirname(__FILE__))).'/admin-globals.php');
require_once("zenpage-admin-functions.php");
if(is_AdminEditPage('newsarticle')) {
	$rights = ZENPAGE_NEWS_RIGHTS;
} else {
	$rights = ZENPAGE_PAGES_RIGHTS;
}
admin_securityChecks($rights, currentRelativeURL(__FILE__));

$result = '';
$saveitem = '';
$reports = array();
if(is_AdminEditPage('page')) {
	if(isset($_GET['titlelink'])) {
		$result = new ZenpagePage(urldecode($_GET['titlelink']));
	} else if(isset($_GET['update'])) {
		XSRFdefender('update');
		$result = updatePage($reports);
	}
	if(isset($_GET['save'])) {
		XSRFdefender('save');
		$result = addPage($reports);
	}
	if(isset($_GET['del'])) {
		XSRFdefender('delete');
		$msg = deletePage();
		if (!empty($msg)) {
			$reports[] = $msg;
		}
	}
} else {
	if(isset($_GET['titlelink'])) {
		$result = new ZenpageNews(urldecode($_GET['titlelink']));
	} else if(isset($_GET['update'])) {
		XSRFdefender('update');
		$result = updateArticle($reports);
	}
	if(isset($_GET['save'])) {
		XSRFdefender('save');
		$result = addArticle($reports);
	}
	if(isset($_GET['del'])) {
		XSRFdefender('delete');
		$msg = deleteArticle();
		if (!empty($msg)) {
			$reports[] = $msg;
		}
	}
}
printAdminHeader();
zp_apply_filter('texteditor_config', '','zenpage'); 
zenpageJSCSS();
datepickerJS();
codeblocktabsJS();
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	var deleteArticle = "<?php echo gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!"); ?>";
	var deletePage = "<?php echo gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE!"); ?>";			
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
	// ]]> -->
</script>
</head>
<body>
<?php
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
		if (!empty($page)) {
			$zenphoto_tabs['articles']['subtabs'][gettext('articles')] .= $page;
		}
		printSubtabs('articles');
		?>
		<div id="tab_articles" class="tabbox">
		<?php
		foreach ($reports as $report) {
			echo $report;
		}
		$admintype = 'newsarticle';
		$additem = gettext('Add Article');
		$updateitem = gettext('Update Article');
		$saveitem = gettext('Save Article');
		$deleteitem = gettext('Delete Article');
		$deletemessage = 'deleteArticle';
		$themepage = ZENPAGE_NEWS;
	}

	if(is_AdminEditPage('page')) {
		foreach ($reports as $report) {
			echo $report;
		}
		$admintype = 'page';
		$additem = gettext('Add Page');
		$updateitem = gettext('Update Page');
		$saveitem = gettext('Save Page');
		$deleteitem = gettext('Delete Page');
		$deletemessage = 'deletePage';
		$themepage = ZENPAGE_PAGES;
	}

	if(is_object($result)) {
		if(is_AdminEditPage('newsarticle')) {
			?>
			<h1><?php echo gettext('Edit Article:'); ?> <em><?php checkForEmptyTitle($result->getTitle(),'news'); ?></em></h1>
			<?php
			if(is_object($result)) {
				if($result->getDatetime() >= date('Y-m-d H:i:s')) {
					echo '<small><strong id="scheduldedpublishing">'.gettext('(Article scheduled for publishing)').'</strong></small>';
					if($result->getShow() != 1) {
						echo '<p class="scheduledate"><small>'.gettext('<strong>Note:</strong> Scheduled publishing is not active unless the article is also set to <em>published</em>').'</small></p>';
					}
				}
				if(inProtectedNewsCategory($result)) {
					echo '<p class="notebox">'.gettext('<strong>Note:</strong> This article is password protected because it is assigned to a password protected category only.').'</p>';
				}
			}
		} else if(is_AdminEditPage('page')) {
			?>
			<h1><?php	echo gettext('Edit Page:'); ?> <em><?php checkForEmptyTitle($result->getTitle(),'page'); ?></em></h1>
			<?php
			if(is_object($result)) {
				if($result->getDatetime() >= date('Y-m-d H:i:s')) {
					echo ' <small><strong id="scheduldedpublishing">'.gettext('(Page scheduled for publishing)').'</strong></small>';
					if($result->getShow() != 1) {
						echo '<p class="scheduledate"><small>'.gettext('Note: Scheduled publishing is not active unless the page is also set to <em>published</em>').'</small></p>';
					}
				}
				if(isProtectedPage($result)) {
					echo '<p class="notebox">'.gettext('<strong>Note:</strong> This page is either password protected itself or subpage of a passport protected page.').'</p>';
				} 
			}
		}
	} else {
		if(is_AdminEditPage('newsarticle')) {
			?><h1><?php echo gettext('Add Article'); ?></h1>
			<?php
		} else if(is_AdminEditPage('page')) {
			?><h1><?php	echo gettext('Add Page'); ?></h1>
<?php
	}
} ?>

<p class="buttons">
<?php 
if(is_AdminEditPage("newsarticle")) {
	$backurl = 'admin-news-articles.php?'.$page;
} else {
	$backurl = 'admin-pages.php';
}
?>
<strong><a href="<?php echo $backurl; ?>" title="<?php echo gettext("Back"); ?>"><img	src="../../images/arrow_left_blue_round.png" alt="" /><?php echo gettext("Back"); ?></a></strong>
<?php
if(is_AdminEditPage("newsarticle")) {
	?>
	<strong><a href="admin-edit.php?<?php echo $admintype; ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('add')?>" title="<?php echo $additem; ?>"><img src="images/add.png" alt="" /> <?php echo $additem; ?></a></strong>
	<?php
} else if(is_AdminEditPage("page")) {
	?>
	<strong><a href="admin-edit.php?<?php echo $admintype; ?>&amp;add&amp;add&amp;XSRFToken=<?php echo getXSRFToken('add')?>" title="<?php echo $additem; ?>"><img src="images/add.png" alt="" /> <?php echo $additem; ?></a></strong>
	<?php
}
?>
<span id="tip"><a href="#"><img src="images/info.png" alt="" /><?php echo gettext("Usage tips"); ?></a></span>
<?php
if(is_object($result)) {
	?>
	<a href="../../../index.php?p=<?php echo $themepage; ?>&amp;title=<?php printIfObject($result,"titlelink") ;?>" title="<?php echo gettext("View"); ?>"><img src="images/view.png" alt="" /><?php echo gettext("View"); ?></a>
	<?php
}
?>
</p>
<br style="clear: both" /><br style="clear: both" />

<div id="tips" style="display:none">
<br />
<h2><?php echo gettext("Usage tips"); ?></h2>
<p><?php echo gettext("Check <em>Edit Titlelink</em> if you need to customize how the title appears in URLs. Otherwise it will be automatically updated to any changes made to the title. If you want to prevent this check <em>Enable permaTitlelink</em> and the titlelink stays always the same (recommended if you use Zenphoto's multilingual mode).");?></p>
<p class="notebox"><?php echo gettext("<strong>Note:</strong> Edit titlelink overrides the permalink setting."); ?></p>
<p class="notebox"><?php echo gettext("<strong>Important:</strong> If you are using Zenphoto's multi-lingual mode the Titlelink is generated from the Title of the currently selected language."); ?></p>
<p><?php echo gettext("If you lock an article only the current active author/user or any user with full admin rights will be able to edit it later again!"); ?></p>
<?php if(is_AdminEditPage("newsarticle")) { ?>
<p><?php echo gettext("<em>Custom article shortening:</em> You can set a custom article shorten length for the news loop excerpts by using the standard TinyMCE <em>page break</em> plugin button. This will override the general shorten length set on the plugin option then."); ?></p>
<?php } ?>
<p><?php echo gettext("<em>Scheduled publishing:</em> To automatically publish a page/news article in the future set it to 'published' and enter a future date in the date field manually. Note this works on server time!"); ?></p>
<p><?php echo gettext("<em>Expiration date:</em> Enter a future date in the date field manually to set a date the page or article will be set un-published automatically. After the page/article has been expired it can only be published again if the expiration date is deleted. Note this works on server time!"); ?></p>
<p><?php echo gettext("<em>ExtraContent:</em> Here you can enter extra content for example to be printed on the sidebar"); ?></p>
<p><?php echo gettext("<em>Codeblocks:</em> Use these fields if you need to enter php code (for example Zenphoto functions) or JavaScript code."); ?>
<?php echo gettext("You also can use the codeblock fields as custom fields."); ?>
<?php echo gettext("Note that your theme must be setup to use the codeblock functions. Note also that codeblock fields are not multi-lingual."); ?>
</p>
<p class="notebox"><?php echo gettext("<strong>Important:</strong> If setting a password for a page its subpages inherit the protection."); ?></p>
<p><?php echo gettext("Hint: If you need more space for your text use TinyMCE's full screen mode (Click the blue square on the top right of editor's control bar)."); ?></p>
</div>
<?php if(is_AdminEditPage("page")) { ?>
<div class="box" style="padding:15px; margin-top: 10px">
<?php } else { ?>
<div style="padding:15px; margin-top: 10px">
<?php } ?>
<?php if(is_object($result)) { ?>
<form method="post" action="admin-edit.php?<?php echo $admintype; ?>&amp;update<?php echo $page; ?>" name="update">
	<?php XSRFToken('update');?>
<input type="hidden" name="id" value="<?php printIfObject($result,"id");?>" />
<input type="hidden" name="titlelink-old" id="titlelink-old" value="<?php printIfObject($result,"titlelink"); ?>" />
<input type="hidden" name="lastchange" id="lastchange" value="<?php echo date('Y-m-d H:i:s'); ?>" />
<input type="hidden" name="lastchangeauthor" id="lastchangeauthor" value="<?php echo $_zp_current_admin_obj->getUser(); ?>" />
<input type="hidden" name="hitcounter" id="hitcounter" value="<?php printIfObject($result,"hitcounter"); ?>" />
<?php } else { ?>
	<form method="post" name="addnews" action="admin-edit.php?<?php echo $admintype; ?>&amp;save">
		<?php XSRFToken('save');?>
<?php } ?>
	<table>
		<tr>
			<td class="topalign-padding"><?php echo gettext("Title:"); ?></td>
			<td class="middlecolumn"><?php print_language_string_list_zenpage(getIfObject($result,"title"),"title",false);?></td>
			<td class="rightcolumnmiddle" rowspan="5">


			<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Publish"); ?></h2>
				<div class="box-edit-zenpage">
				<p><?php echo gettext("Author:"); ?> <?php authorSelector(getIfObject($result,"author")) ;?></p>
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
				<?php
				if(is_AdminEditPage('newsarticle')) {
					if (is_object($result)) {
						$sticky = $result->get('sticky');
					} else {
						$sticky = 0;
					}
				?>
					<p><?php echo gettext("Position:"); ?>
						<select id="sticky" name="sticky">
							<option value="0" <?php if ($sticky==0) echo 'selected="selected"';?>><?php echo gettext("normal"); ?></option>
							<option value="1" <?php if ($sticky==1) echo 'selected="selected"';?>><?php echo gettext("sticky"); ?></option>
							<option value="9" <?php if ($sticky==9) echo 'selected="selected"';?>><?php echo gettext("Stick to top"); ?></option>
						</select>
					</p>
					<?php
				}
				?>
				<p class="checkbox">
				<input name="locked" type="checkbox" id="locked" value="1" <?php checkIfChecked(getIfObject($result,"locked")); ?> />
				<label for="locked"><?php echo gettext("Locked for changes"); ?></label>
				</p>
				<?php
				if(is_object($result) && get_class($result)=='ZenpagePage') {
					$hint = $result->getPasswordHint();
					$user = $result->getUser();
						$x = $result->getPassword();
				} else {
					$hint = $user = $x = '';
				}
				if(is_AdminEditPage('page')) { ?>
  				<p class="passwordextrashow">
					<input	type="hidden" name="password_enabled" id="password_enabled" value="0" />
					<a href="javascript:toggle_passwords('',true);">
						<?php echo gettext("Page password:"); ?>
					</a>
					<?php
					if (empty($x)) {
						?>
						<img src="../../images/lock_open.png" alt="" class="icon-postiion-top8" />
						<?php
					} else {
						$x = '          ';
						?>
						<img src="../../images/lock.png" alt="" class="icon-position-top8" />
						<?php 
					} 
					?>
				</p>
				<div class="passwordextrahide" style="display:none">
					<a href="javascript:toggle_passwords('',false);">
					<?php echo gettext("Page guest user:"); ?>
					</a>
					<input type="text" size="27" name="page_user" value="<?php echo htmlspecialchars($user); ?>" />
					<?php echo gettext("Page password:"); ?>
					<br />
					<input type="password" size="27" name="pagepass" value="<?php echo $x; ?>" />
					<?php echo gettext("(repeat)"); ?>
					<br />
					<input type="password" size="27" name="pagepass_2" value="<?php echo $x; ?>" />
					<br />
					<?php echo gettext("Page password hint:"); ?>
					<br />
					<?php print_language_string_list($hint, 'page_hint', false, NULL, '', 27); ?>
				</div>
				<?php 
				}
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
				<p class="buttons"><a class="submitbutton" href="javascript:confirmDelete('admin-edit.php?<?php echo $admintype; ?>&amp;add&amp;del=<?php printIfObject($result,"id"); echo $page; ?>&amp;XSRFToken=<?php echo getXSRFToken('delete')?>
				<?php if(is_AdminEditPage("page")) { echo "&amp;sortorder=".$result->getSortorder(); } ?>',<?php echo $deletemessage; ?>)" title="<?php echo $deleteitem; ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo $deleteitem; ?></strong></a></p>
				<br style="clear:both" />
				<?php } ?>
				</div>
				<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Date"); ?></h2>
				<div class="box-edit-zenpage">
				<p>

				<script type="text/javascript">
					// <!-- <![CDATA[
					$(function() {
						$("#date").datepicker({
							showOn: 'button',
							buttonImage: '../../images/calendar.png',
							buttonText: '<?php echo gettext('calendar'); ?>',
							buttonImageOnly: true
							});
					});
					// ]]> -->
				</script>

				<strong class='scheduledpublishing'></strong>
				<input name="date" type="text" id="date" value="<?php if(is_object($result)) { echo $result->getDatetime(); } else { echo date('Y-m-d H:i:s'); } ?>" />
				</p>
				<hr />
				<strong class='expire'></strong>
				<p>

				<script type="text/javascript">
					// <!-- <![CDATA[
					$(function() {
						$("#expiredate").datepicker({
							showOn: 'button',
							buttonImage: '../../images/calendar.png',
							buttonText: '<?php echo gettext('calendar'); ?>',
							buttonImageOnly: true
							});
					});
					// ]]> -->
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
						</div>
						<br />
						<?php
				}
				?>
				<h2 class="h2_bordered_edit-zenpage"><?php echo gettext("Tags"); ?></h2>
					<div id="zenpagetags">
				<?php
				if (is_object($result)) {
					tagSelector($result, 'tags_', false, getTagOrder());
				} else {
					tagSelector(NULL, 'tags_', false, false);
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
					<textarea name="codeblock1" id="codeblock1" rows="40" cols="60"><?php echo htmlentities($codeblock[1],ENT_QUOTES); ?></textarea>
				</div>
				<div id="second">
					<textarea name="codeblock2" id="codeblock2" rows="40" cols="60"><?php echo htmlentities($codeblock[2],ENT_QUOTES); ?></textarea>
				</div>
				<div id="third">
					<textarea name="codeblock3" id="codeblock3" rows="40" cols="60"><?php echo htmlentities($codeblock[3],ENT_QUOTES); ?></textarea>
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