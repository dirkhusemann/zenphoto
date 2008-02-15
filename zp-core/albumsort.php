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
		die("No album provided to sort.");
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
		<p><?php printAdminLinks("edit", "&laquo; back to the list", "Back to the list of albums");?> |  
 			<?php printAdminLinks("edit&album=". urlencode( ($album->getFolder()) ), "Edit Album", "Edit Album"); ?> | 
 			<?php printViewLink($album, "View Album", "View Album"); ?>
		</p>
		
		<div class="box" style="padding: 15px;">
		
		<p>Sort the images by dragging them...</p>
		
			<div id="images">
			<?php
				$images = $album->getImages();
				foreach ($images as $image) { 
					adminPrintImageThumb(new Image($album, $image)); 
				}
			?>
			
			</div>
				<?php
				if (isset($_GET['saved'])) {
					echo "<p>Album order saved.";

					if ($album->getSortType() == "Manual") {
						echo " Album sorting set to Manual.";
					}
					echo "</p>";
				}
				?>
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