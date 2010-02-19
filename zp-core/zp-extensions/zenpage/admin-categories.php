<?php 
/**
 * zenpage admin-categories.php
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
<title><?php echo gettext('zenphoto administration'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php zenpageJSCSS(); ?>  
<script type="text/javascript">
<?php if(isset($_GET["edit"])) { // prevent showing the message when adding page or article ?>
$(document).ready(function() {
	if(jQuery('#edittitlelink:checked').val() != 1) {
		$('#catlink').attr("disabled", true); 
	}
	$('#edittitlelink').change(function() {
		if(jQuery('#edittitlelink:checked').val() == 1) {	
			$('#catlink').removeAttr("disabled"); 
		} else {
			$('#catlink').attr("disabled", true); 
		}
	});
});
<?php } ?>
</script>
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
		checkRights('categories'); 
		printSubtabs('articles');
		?>
		<div id="tab_articles" class="tabbox">
			<?php	
			
			if(isset($_GET['delete'])) {
			  deleteCategory();
			}
			if(isset($_GET['hitcounter'])) {
				resetPageOrArticleHitcounter('cat');
			}
			if(isset($_GET['save'])) {
			  addCategory();
			}
			if(isset($_GET['id'])){
			  $result = getCategory($_GET['id']);
			} else if(isset($_GET['update'])) {
			  $result = updateCategory();
			}
			?> 
			<h1>
			<?php 
			if(isset($_GET['edit'])) {
				echo gettext('Edit Category:').'<em> '; checkForEmptyTitle(get_language_string($result['cat_name']),'category'); echo '</em>';
			} else {
				echo gettext('Categories'); 
			}
			?>
			<span class="zenpagestats"><?php printCategoriesStatistic();?></span></h1>
			<p class="buttons">
			<span id="tip"><a href="#"><img src="images/info.png" alt="" /><?php echo gettext('Usage tips'); ?></a></span>
			<?php if(isset($_GET['edit'])) { ?>
				<a href="admin-categories.php?tab=categories"><img src="images/add.png" alt="" /><?php echo gettext('Add New Category'); ?></a>
			<?php } ?>
			</p>
			<br clear="all" /><br clear="all" />
			<div id="tips" style="display:none">	
				<p><?php echo gettext("A search engine friendly <em>titlelink</em> (aka slug) without special characters to be used in URLs is generated from the title of the currently chosen language automatically if a new category is added. You can edit it later manually if necessary."); ?></p> 
				<p><?php echo gettext("If you are editing a category check <em>Edit Titlelink</em> if you need to customize how the title appears in URLs. Otherwise it will be automatically updated to any changes made to the title. If you want to prevent this check <em>Enable permaTitlelink</em> and the titlelink stays always the same (recommended if you use Zenphoto's multilingual mode). <strong>Note: </strong> <em>Edit titlelink</em> overrides the permalink setting."); ?></p> 
				<p><?php echo gettext("<strong>Important:</strong> If you are using Zenphoto's multi-lingual mode the Titlelink is generated from the Title of the currently selected language."); ?></p> 
				<p><?php echo gettext("Hint: If you need more space for your text use TinyMCE's full screen mode (Click the blue square on the top right of editor's control bar)."); ?></p> 
			</div>
			<div style="padding:15px; margin-top: 10px">
				<?php
				if(isset($_GET['edit'])) {
					$formname = 'update';
					$formaction = 'admin-categories.php?edit&amp;update&amp;tab=categories';
				} else {
					$formname = 'addcat';
					$formaction = 'admin-categories.php?save&amp;tab=categories';
				}
				?>
				<form method="post" name="<?php echo $formname; ?>update" action="<?php echo $formaction; ?>">
				<?php
				if(isset($_GET['edit'])) {
					?>
					<input type="hidden" name="id" value="<?php echo $result['id'];?>" />
					<input type="hidden" name="catlink-old" id="catlink-old" value="<?php echo $result['cat_link']; ?>" />
					<?php
				}
				?>
					<table>
			    	<tr> 
				     <?php
				     if(isset($_GET['edit'])) {
				     	$cattitlemessage = gettext('Category Title:');
				     } else {
				     	$cattitlemessage =  gettext('New Category Title:');
				     }
				     	?>
				      <td class="topalign-padding"><?php echo $cattitlemessage; ?></td>
				      <td><?php if(isset($_GET['edit'])) { print_language_string_list_zenpage($result['cat_name'],'category',false) ; } else { print_language_string_list_zenpage('','category',false) ;} ?>
				      	<input name="permalink" type="checkbox" id="permalink" value="1" <?php if(isset($_GET['edit'])) { checkIfChecked($result['permalink']); } else { echo 'checked="checked"'; } ?> /> <?php echo gettext('Enable permaTitleLink'); ?>
				      </td>
				    </tr>
						<?php
						if(isset($_GET['edit'])) {
							?>
					    <tr> 
					      <td class="topalign-padding"><?php echo gettext('Category TitleLink:'); ?></td>
					      <td><input name="catlink" type="text" size="85" id="catlink" value="<?php echo $result['cat_link']; ?>" />
					      <input name="edittitlelink" type="checkbox" id="edittitlelink" value="1" /> <?php echo gettext('Edit TitleLink'); ?>
					      </td>
					    </tr>
							<?php
						}
						?>
				    <tr>
				      <td colspan="3">
				    	<?php 
				    	if(isset($_GET['edit'])) { 
				     		$submittext =  gettext('Update Category');
				    	} else {
				     		$submittext =  gettext('Save New Category');
				    	}
				     	?>
				      	<p class="buttons">
					      	<button type="submit" title="<?php echo $submittext; ?>">
					      		<img src="../../images/pass.png" alt="" />
					      		<strong><?php echo $submittext; ?></strong>
					      	</button>
				      	</p>
								<p class="buttons">
									<button type="reset" title="<?php echo gettext('Reset'); ?>">
										<img src="../../images/reset.png" alt="" />
										<strong><?php echo gettext('Reset'); ?></strong>
									</button>
								</p>
								<br clear="all" /><br />
							</td>	
			    	</tr>
			 		</table>
				</form>
				<table class="bordered">
				 <tr> 
				  <th colspan="4"><strong><?php echo gettext('Edit this Category'); ?></strong></th>
				  </tr>
					<?php printCategoryList(); ?>
				</table>
				
			</div> <!-- box -->
			<ul class="iconlegend">
				<li><img src="../../images/reset.png" alt="" /><?php echo gettext('Reset hitcounter'); ?></li>
				<li><img src="../../images/fail.png" alt="" /><?php echo gettext('Delete category'); ?></li>
			</ul>
		</div> <!-- tab_articles -->
	</div> <!-- content -->
</div> <!-- main -->
<?php printAdminFooter(); ?>

</body>
</html>
