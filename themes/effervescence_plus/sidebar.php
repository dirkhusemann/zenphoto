<?php 
rem_context(ZP_ALBUM | ZP_IMAGE);
if(function_exists("printAllNewsCategories")) { 
?>
<div class="menu">
	<h3><?php echo gettext("News articles"); ?></h3>
	<?php printAllNewsCategories("All news",TRUE,"","menu-active"); ?>
</div>
<?php } ?>

<div class="menu">
	<?php
	if(function_exists("printAlbumMenu")) {
		?>
		<h3><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Visit the photo gallery'); ?>"><?php echo gettext("Gallery"); ?></a></h3>
		<?php
		printAlbumMenu("list",NULL,"","menu-active","submenu","menu-active","");
	} else {
		?>
		<h3><?php echo gettext("Gallery"); ?></h3>
		<ul>
		<li><a href="<?php echo htmlspecialchars(getGalleryIndexURL());?>" title="<?php echo gettext('Visit the photo gallery'); ?>"><?php echo getGalleryTitle();?></a></li>
		</ul>
		<?php
	}
	?>
</div>
<?php if(function_exists("printPageMenu")) { ?>
<div class="menu">
	<h3><?php echo gettext("Pages"); ?></h3>
	<?php	printPageMenu("list","","menu-active","submenu","menu-active"); ?>
</div>
<?php } ?>

<div class="menu">
<h3><?php echo gettext("Archive"); ?></h3>
	<ul>
	<?php
	  if($_zp_gallery_page == "archive.php") {
	  	?>
	  	<li class='menu-active'>
	  	<?php echo gettext("Gallery and News");	?>
	  	</li>
	  	<?php
	  } else {
	  	?>
	  	<li>
	  	<?php echo printCustomPageURL(gettext("Gallery and News"),"archive"); ?>
	  	</li>
	  	<?php
 	 	} 
		?>
	</ul>
</div>

<?php
if (getOption('RSS_album_image') || getOption('RSS_articles')) {
	?>
	<div class="menu">
	<h3><?php echo gettext("RSS"); ?></h3>
		<ul>
			<?php printRSSLink('Gallery','<li>',gettext('Gallery'), '</li>'); ?>
			<?php
			if(function_exists("printZenpageRSSLink")) {
				?>
				<?php printZenpageRSSLink("News","","<li>",gettext("News"), '</li>'); ?>
				<?php printZenpageRSSLink("NewsWithImages","","<li>",gettext("News and Gallery"),'</li>'); ?>
			<?php
			}
			?>
		</ul>
	</div>
	<?php
}
?>
