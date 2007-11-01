<?php	

/* printAlbumMenu Custom Function 1.2.2.3 for the zenphoto_updated community build WITH subalbum sorting

1.2.2.3:
- Automatic detection if mod_rewrite is enabled but it has to be set and save in the admin options. The
zp-config.php entry is not used.
- Better looking source code thanks to spacing and linebreaks implemented by aitf311

1.2.2.2:
- Automatic disabling of the counter for main albums so that they don't show "(0)" anymore if you only use subalbums for images
now for subalbums, too.

1.2.2.1:
- Automatic disabling of the counter for main albums so that they don't show "(0)" anymore if you only use subalbums for images

1.2.2: 
- Change Subalbum CSS-ID "$id2" to CLASS "$class" ((X)HTML Validation issue)
- Add htmlspecialchars to the printed album titles, so that validation does not fail because of ampersands in names.


1.2.1: 
- New option for mod_rewrite (needs to be automatic...),
- bug fixes for the id-Tags, which didn't get used.


1.2 What's new: Now works with sbillard's album publishing function.

1.1. What's new:
- Option for album list or a drop down jump menu if you want to save space 
- Displays the number of images in the album (like e.g. wordpress does with articles)
- Option for disabling the counter
- Parameters for CSS-Ids for styling, separate ones for main album and subalbums
- Renamed the function name from show_album_menu() to more zp style printAlbumMenu()

IMPORTANT: 
This version lists all albums by sort order. Thanks to sbillard's new subalbum functions and aitf311's zenphoto_updated build we now can
sort subalbums.
Therefore I suggest you use the zenphoto_updated package even if it is not the official version and if you want the list displayed 
correctly set a sort order to your albums and subalbums even if you have not sorted anything. 

Usage:
Copy the function either to your custom_functions.php or include it directly in the head of your theme files:
<?php require_once("print_album_menu-updated.php"); ?>

Place where ever you want to show the menu:
<?php printAlbumMenu($option1,$option2,$id1,$active1,$id2,$active2); ?>

$option1 = 
"list" for the menu as a list like in wordpress 
"jump" for a space saving jump menu
This option must be set.

$option2 = 
"count" shows the number of images in the album in brackets behind the album name
"" = for no image numbers

Example: If you want a list without counter write this:
<?php printAlbumMenu("list",""); ?>

The CSS parameters are optional if you want specific CSS to style. If not leave blank.
$id = Insert here the ID for the main album menu like this: "mainmenu".
$active1 = Insert the ID for the currently active album link
$class = Insert here the CLASS for the sub album sub menu if you want to style it differently than the main menu
$active2 = Insert the ID for the currently active subalbum link. 
Of course these parameters are useless with the jump menu option.

$rewrite =
put here "yes" if you use mod_rewrite, leave blank if you don't

*/
function printAlbumMenu($option1,$option2,$id,$active,$class,$active2) {

// check if parameters are used
if ($id != "") { $id = " id='".$id."'"; }
if ($active1 != "") { $active1 = " id='".$active1."'"; }
if ($class != "") { $class = " class='".$class."'"; }
if ($active2 != "") { $active2 = " id='".$active2."'"; }

if (getOption('mod_rewrite')) 
{ $albumlinkpath = WEBPATH."/"; }
else 
{ $albumlinkpath = "index.php?album="; }

// main album query
$sql = "SELECT id, parentid, folder, title FROM ". prefix('albums') ." WHERE `show` = 1 ORDER BY sort_order";
$result = mysql_query($sql);

while($row = mysql_fetch_array($result))
    {
    $number++;
    $idnr[$number] = $row['id']; 
	$parentid[$number] = $row['parentid']; 
	$folder[$number] = $row['folder'];
	$title[$number] = $row['title'];

	switch($option2) {
	case "count": 
	// count images in the albums
	$sql = "SELECT COUNT(id) FROM ". prefix('images') ." WHERE albumid = $idnr[$number]";
	$result2 = query($sql);
	$count = mysql_result($result2, 0);
	$imagecount[$number] = " (".$count.")"; 
	}
	}

switch($option1) {

/*** LIST MENU SECTION ***/
case "list": 


// open main albums list
echo "<ul".$id.">\n";

// here the Gallery index link
if (getAlbumTitle() === false) 
	{ echo "	<li".$active1."><strong><a href='".getGalleryIndexURL()."' title='Gallery Index'>Gallery Index</a></strong></li>\n"; }
else
	{ echo "	<li><a href='".getGalleryIndexURL()."' title='Gallery Index'>Gallery Index</a></li>\n"; }

/* main album loop start */	
for ($nr = 1;$nr <= $number; $nr++)
	{
 	if ($parentid[$nr] === NULL ) // check if album is main album
		{ 
		if ($imagecount[$nr] == " (0)") // prevent "(0)" behind main albums if you only have subalbums with images
			{ $imagecount[$nr] = ""; }
		// if check if the album is the currently selected album
		if ($title[$nr] === getAlbumTitle()) 
			 { echo "	<li".$active1."><strong><a href='".$albumlinkpath.$folder[$nr]."'>".htmlspecialchars($title[$nr])."</a>".$imagecount[$nr]."</strong></li>\n"; }
		else 
			 {  echo "	<li><a href='".$albumlinkpath.$folder[$nr]."'>".htmlspecialchars($title[$nr])."</a>".$imagecount[$nr]."</li>\n"; }   					   			
		
		/*  open sublist for subalbums */
		// check if any subalbums are available. If then open sublist for subalums
		for ($nr2 = 1;$nr2 <= $number; $nr2++)
			{	     	 
			if ($idnr[$nr] === $parentid[$nr2]) 
			   { echo "		<ul".$class.">\n"; break; }
			}  
		// subalbum subloop start			
		for ($nr2 = 1;$nr2 <= $number; $nr2++)
			{	 
			// check if any subalbums are available		     	 
			if ($idnr[$nr] === $parentid[$nr2]) 
			   { 		
			   if ($imagecount[$nr2] == " (0)") // prevent "(0)" behind main albums if you only have subalbums with images
					{ $imagecount[$nr2] = ""; }
				// if check if the subalbum is the currently selected album
			    if ($title[$nr2] === getAlbumTitle()) 
					{ echo "			<li".$active2."><strong><a href='".$albumlinkpath.$folder[$nr2]."'>".htmlspecialchars($title[$nr2])."</a>".$imagecount[$nr2]."</strong></li>\n"; }
				else 
					{  echo "			<li><a href='".$albumlinkpath.$folder[$nr2]."'>".htmlspecialchars($title[$nr2])."</a>".$imagecount[$nr2]."</li>\n"; 
					}   					   			
				}				
			} // subalbum loop end 
		// close subalbums sublist (only printed if there are subalbums) 
		for ($nr2 = 1;$nr2 <= $number; $nr2++)
			{	     	 
			if ($idnr[$nr] === $parentid[$nr2]) 
			   { echo "		</ul>\n"; break; }
			}  
		echo "	</li>\n"; // close main album list item
		} // close main album check
	} // main album loop end
echo "</ul>\n"; // close main album list
break; 

/*** JUMP MENU SECTION ***/
case "jump": 
?>

<form name ="AutoListBox">
<p><select name="ListBoxURL" size="1" language="javascript" onchange="gotoLink(this.form);"
<option value="<?php echo getGalleryIndexURL(); ?>">Gallery Index</option>

<?php
/* main album loop start */	
for ($nr = 1;$nr <= $number; $nr++)
	{

	
 	if ($parentid[$nr] === NULL ) // check if album is main album 
		{
		// if check if the album is the currently selected album
			echo "<option value='".$albumlinkpath.$folder[$nr]."'>".$title[$nr].$imagecount[$nr]."</option>"; 
		// open sublist for subalbums
		// subalbum subloop start			
		for ($nr2 = 1;$nr2 <= $number; $nr2++)
			{	 
				     	 
			if ($idnr[$nr] === $parentid[$nr2]) // check if any subalbums are available and print them	 
			   { 
				echo "<option value='".$albumlinkpath.$folder[$nr2]."'>".$title[$nr].": ".$title[$nr2].$imagecount[$nr2]."</option>";		  					   			
				}
							
			} // subalbum loop end 		
		
		} // close main album check	
	} // main album loop end

; ?>
<option selected>Choose an album</option>
</select></p>

<script language="JavaScript">
<!--
function gotoLink(form) {
   var OptionIndex=form.ListBoxURL.selectedIndex;
  parent.location = form.ListBoxURL.options[OptionIndex].value;}
//-->
</script>

</form>

<?php } // switch end
} 

; ?>