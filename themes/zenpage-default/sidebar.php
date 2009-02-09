<?php if(function_exists("printAllNewsCategories")) { ?>
<div class="menu">
	<h3><?php echo gettext("News articles"); ?></h3>
	<?php printAllNewsCategories("All news",TRUE,"","menu-active"); ?>
</div>
<?php } ?>

<?php if(function_exists("printAlbumMenu")) { ?>
<div class="menu">
	<h3><?php echo gettext("Gallery"); ?></h3>
	<?php printAlbumMenu("list","count","","menu-active","submenu","menu-active",""); ?>
</div>
<?php } ?>

<?php if(function_exists("printPageMenu")) { ?>
<div class="menu">
	<h3><?php echo gettext("Pages"); ?></h3>
	<?php printPageMenu("list","","menu-active","submenu","menu-active"); ?>
</div>
<?php } ?>

<div class="menu">
<h3><?php echo gettext("Archive"); ?></h3>
	<ul>
	<?php
	  if($_zp_gallery_page == "archive.php") {
	  	echo "<li class='menu-active'>".gettext("Gallery And News")."</li>";
 	 	} else {
			echo "<li>"; printCustomPageURL(gettext("Gallery and News"),"archive")."</li>";
		} 
		?>
	</ul>
</div>

<div class="menu">
<h3><?php echo gettext("RSS"); ?></h3>
	<ul>
		<li><?php printRSSLink('Gallery','','Gallery', ''); ?></li>
		<?php if(function_exists("printZenpageRSSLink")) { ?>
		<li><?php printZenpageRSSLink("News","","",gettext("News")); ?></li>
		<li><?php printZenpageRSSLink("NewsWithImages","","",gettext("News and Gallery")); ?></li>
		<?php } ?>
	</ul>
</div>



<?php if (function_exists('printLanguageSelector')) { ?>
 <?php printLanguageSelector("langselector"); ?>
<?php } ?>