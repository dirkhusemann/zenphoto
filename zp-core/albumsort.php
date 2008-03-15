<?php

define('OFFSET_PATH', true);
require_once("sortable.php");

// Insert the POST operation handler
zenSortablesPostHandler('imageOrder', 'images', 'images');
// Print the admin header
printAdminHeader();
// Print the sortable stuff
zenSortablesHeader('images','imageOrder','img',"overlap:'horizontal',constraint:false");

echo "\n</head>";
?>


<body>

<?php
// If they are not logged in, display the login form and exit
if (!zp_loggedin()) {   

	printLoginForm();
	exit();

} else {
	
	// Create our gallery
	$gallery = new Gallery();
	// Create our album
	// TODO: We could be a bit more defensive here when parsing the incoming args
	if (!isset($_GET['album'])) {
		die(gettext("No album provided to sort."));
	} else {
		$folder = strip($_GET['album']);
		$album = new Album($gallery, $folder);
		if (isset($_GET['saved'])) {   
			$album->setSortType("Manual");
			$album->setSortDirection('image', 0);
			$album->save();
		}
			
		// Layout the page
		printLogoAndLinks();
?>
	
<div id="main">
	
	<?php printTabs(); ?>
	
		
	<div id="content">
		
		<h1>Sort Album: <?php echo $album->getTitle(); ?></h1>
		<p><?php printAdminLinks(gettext("edit"), "&laquo; ".gettext("back to the list"), gettext("Back to the list of albums"));?> |  
 			<?php printAdminLinks("edit&album=". urlencode( ($album->getFolder()) ), gettext("Edit Album"), gettext("Edit Album")); ?> | 
 			<?php printViewLink($album, gettext("View Album"), gettext("View Album")); ?>
		</p>
				<?php
				if (isset($saved)) {
					echo '<div class="messagebox" id="message">';
					echo  "<h2>".gettext("Image order saved");
					echo '</h2></div>';
					echo '<script type="text/javascript">';
					echo "window.setTimeout('Effect.Fade(\$(\'message\'))', 2500);";
					echo "</script>\n";
				}
				?>
		
		<div class="box" style="padding: 15px;">
		
		<p><?php echo gettext("Sort the images by dragging them..."); ?></p>
		
			<div id="images">
			<?php
				$images = $album->getImages();
				foreach ($images as $image) { 
					adminPrintImageThumb(new Image($album, $image)); 
				}
			?>
			
			</div>
			<br>

			<div>
			<?php
				zenSortablesSaveButton("?page=edit&album=". $album->getFolder() . "&saved"); 
			?>
			</div>
			
			</div>
			
		</div>
		
		<?php printAdminFooter(); ?>
		
	</div>
	
	<?php zenSortablesFooter(); ?>
	
</body>

<?php
echo "\n</html>";
	}
}
?>