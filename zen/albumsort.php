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
  if (isset($_GET['album'])) {
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
       <?php printAdminLink("edit&album=$album->name", "Edit Album", "Edit Album"); ?>
    </p>
    
    <p>Sort the images by dragging them..</p>
    
      <div id="images">
      <?php foreach ($images as $filename) { 
        $image = new Image($album, $filename);
        printImageThumb($image);
      }
      ?>
      
      </div>
  
      <br>
      <?php zenSortablesSaveButton($PHP_SELF); ?>
      
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