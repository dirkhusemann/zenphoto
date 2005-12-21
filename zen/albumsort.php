<?php

require_once("sortable.php");

// Insert the POST operation handler
zenSortablesPostHandler("images");
// Print the admin header
printAdminHeader();
// Print the sortable stuff
zenSortablesHeader();

?>

</head>
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
  }
  else if (isset($_GET['album'])) {
    $folder = strip($_GET['album']);
    $album = new Album($gallery, $folder);
    $images = $album->getImages();
    $totalimages = sizeof($images);
  
    // Layout the page
    printLogoAndLinks();
?>
  
<div id="main">
  
  <?php printTabs("edit"); ?>
  
    
  <div id="content">
    
    <h1>Sort Album</h1>
    <p><?php printAdminLink("edit", "&laquo; back to the list", "Back to the list of albums");?> | 
       <?php printAdminLink("edit&album=$album->name", "Edit Album", "Edit Album"); ?> |
       <?php printLink(WEBPATH . "/index.php?album=". $album->getTitle(), "View Album", "View Album"); ?>
    </p>
    
    <p>Sort the images by dragging them..</p>
    
      <div id="images">
      <?php foreach ($images as $image) { 
        printImageThumb($image);
      }
      ?>
      
      </div>
  
      <br>
      <?php
        if (isset($_GET['saved'])) {
          echo "<p>Album order saved.</p>";
        }
      ?>
      <div>
      <?php
        zenSortablesSaveButton("?album=". $album->getFolder() . "&saved"); 
        //zenSortablesSaveButton("?album=". $album->getFolder() . "&saved", "Save and View Album"); 
      ?>
      </div>
      
    </div>
    
    <?php printAdminFooter(); ?>
    
  </div>
  
  <?php zenSortablesFooter(); ?>
  
</body>
</html>

<?php
  }
}
?>