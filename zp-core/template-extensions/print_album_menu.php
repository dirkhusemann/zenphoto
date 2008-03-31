<?php	

/** printAlbumMenu Custom Function 1.3 for zenphoto 1.1 or newer 
 * 
 * Changelog
 *
 * 1.3.1:
 * - support for album passwords
 * - a little code reformatting
 * - the return of the somehow forgotten published or not published check
 * 
 * 1.3:
 * - only for zenphoto 1.1. or newer
 * - nearly completly rewritten
 * - Supports 4 subalbum levels with context sensitive fold out display
 * 	
 * 1.2.2.3:
 * - Automatic detection if mod_rewrite is enabled but it has to be set and save in the admin options. 
 * - Better looking source code thanks to spacing and linebreaks implemented by aitf311
 * 
 * 1.2.2.2:
 * - Automatic disabling of the counter for main albums so that they don't show "(0)" anymore if you only use subalbums for images
 * now for subalbums, too.
 * 
 * 1.2.2.1:
 * - Automatic disabling of the counter for main albums so that they don't show "(0)" anymore if you only use subalbums for images
 * 
 * 1.2.2: 
 * - Change Subalbum CSS-ID "$id2" to CLASS "$class" ((X)HTML Validation issue)
 * - Add htmlspecialchars to the printed album titles, so that validation does not fail because of ampersands in names.
 * 
 * 1.2.1: 
 * - New option for mod_rewrite (needs to be automatic...),
 * - bug fixes for the id-Tags, which didn't get used.
 * 
 * 1.2 What's new: Now works with sbillard's album publishing function.
 * 
 * 1.1. What's new:
 * - Option for album list or a drop down jump menu if you want to save space 
 * - Displays the number of images in the album (like e.g. wordpress does with articles)
 * - Option for disabling the counter
 * - Parameters for CSS-Ids for styling, separate ones for main album and subalbums
 * - Renamed the function name from show_album_menu() to more zp style printAlbumMenu()
 */

/**
 * Prints a list of all albums context sensitive up to the 4th subalbum level.
 * 
 * Usage: add the following to the php page where you wish to use these menus:
 * require_once(SERVERPATH . "/" . ZENFOLDER . "/plugins/print_album_menu.php");
 * Call the function printAlbumMenu() at the point where you want the menu to appear.
 * 
 * @param string $option "list" for html list, "jump" for a jump drop down menu
 * @param string $option2 "count" for a image counter in brackets behind the album name, "" = for no image numbers or leave blank if you don't set css styles
 * @param string $css_id insert css id for the main album list, leave blank if you don't use 
 * @param string $css_id_active insert css class for the active link in the main album list
 * @param string $css_class insert css class for the sub album lists
 * @param string $css_class_active insert css class for the active link in the sub album lists
 * @return html list or drop down jump menu of the albums
 * @since 1.2
 */

function printAlbumMenu($option,$option2,$css_id='',$css_id_active='',$css_class='',$css_class_active='') {

	// check if css parameters are used
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_id_active != "") { $css_id_active = " id='".$css_id_active."'"; }
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($css_class_active != "") { $css_class_active = " id='".$css_class_active."'"; }

	if (getOption('mod_rewrite')) {
		$albumlinkpath = WEBPATH."/";
	} else 	{
		$albumlinkpath = "index.php?album=";
	}

 $albumscheck = query_full_array("SELECT * FROM " . prefix('albums'). " ORDER BY title");
	foreach($albumscheck as $albumcheck) {
		if(!checkAlbumPassword($albumcheck['folder'], $hint)) {
			$albumpasswordcheck = " AND id != ".$albumcheck['id'];
			$passwordcheck = $passwordcheck.$albumpasswordcheck;
		}
	} 

	// album query
	$result = query("SELECT id, parentid, folder, title FROM ". prefix('albums') ." WHERE `show` = 1 ".$passwordcheck." ORDER BY sort_order");
	while($row = mysql_fetch_array($result)) {
		$number++;
		$id[$number] = $row['id'];
		$parentid[$number] = $row['parentid'];
		$folder[$number] = $row['folder'];
		$title[$number] = $row['title'];

		// Get the folder of the current album
		if(getAlbumID() ===	$id[$number]) {
			$currentfolder = $folder[$number];
		}
		switch($option2) {
			case "count":
				// count images in the main albums
				$result2 = query("SELECT COUNT(id) FROM ". prefix('images') ." WHERE `show` = 1 AND albumid = $id[$number]");
				$count = mysql_result($result2, 0);
				if($count === "0") {
					$imagecount[$number] = "";
				} else {
					$imagecount[$number] = " (".$count.")";
				}
		}
	}

	/**** TOP LEVEL ALBUM  ****/
	if($option === "list") {
		echo "<ul".$css_id.">\n"; // start top level list
	} else if ($option === "jump") { ?>
		<form name ="AutoListBox">
		<p><select name="ListBoxURL" size="1" language="javascript" onchange="gotoLink(this.form);">
		<option value="<?php echo getGalleryIndexURL(); ?>">Gallery Index</option>
<?php }

for ($top = 1;$top <= $number; $top++) {

// if Top level albums
if(!$parentid[$top]) { 
 	$toplevel_folder = $folder[$top];
 	
 	// make link <strong> if selected
 	if(getAlbumID() === $id[$top]) { 
 		$strong_start = "<strong>"; $strong_end = "</strong>\n"; $active = $css_id_active;
 	} else { 
 		$strong_start = ""; $strong_end = ""; $active = "";
 	}
 		
 	// link either for list or jump menu display 
 	if($option === "list") { 
 		echo "<li".$active.">".$strong_start."<a href='".$albumlinkpath.$folder[$top]."'>".htmlspecialchars($title[$top])."</a>".$strong_end.$imagecount[$top]; 
		} else if ($option === "jump") { 
			echo "<option value='".$albumlinkpath.$folder[$top]."'>".htmlspecialchars($title[$top]).$imagecount[$top]."</option>"; 
		}
 	$sub1_count = 0;
 	
 	/**** SUBALBUM LEVEL 1 ****/
 	for ($sub1 = 1;$sub1 <= $number; $sub1++) {	 
 		$sublevel_1_folder = explode("/",$folder[$sub1]);
 		$sublevel_current = explode("/", $currentfolder);  
 		
				// if 2: (if in parentalbum) OR (if in subalbum)
				if( 
				($folder[$top] === $sublevel_1_folder[0] 
				AND count($sublevel_1_folder) === 2 
				AND $currentfolder === $sublevel_1_folder[0]) 
				OR 
				(getAlbumID() != $id[$top] 
				AND $id[$top] === $parentid[$sub1] 
				AND count($sublevel_1_folder) === 2 
				AND $sublevel_current[0] === $folder[$top])) {
					$sub1_count++; // count subalbums for checking if to open or close the sublist
					if ($sub1_count === 1) { echo "<ul".$css_class.">\n"; } // open sublevel 1 sublist once if subalbums
				
				// print link <strong> if selected
					if(getAlbumID() === $id[$sub1]) { 
						$strong_start = "<strong>"; $strong_end = "</strong>"; $active = $css_class_active;
					} else { 
						$strong_start = ""; $strong_end = ""; $active = "";
					}
					
					// link either for list or jump menu display
					if($option === "list") { echo "<li".$active.">".$strong_start."<a href='".$albumlinkpath.$folder[$sub1]."'>".htmlspecialchars($title[$sub1])."</a>".$strong_end.$imagecount[$sub1]; 
					}	else if ($option === "jump") { 
						echo "<option value='".$albumlinkpath.$folder[$sub1]."'>&gt; ".htmlspecialchars($title[$sub1]).$imagecount[$sub1]."</option>"; 
					}
					$sub2_count = 0;
					
 							/**** SUBALBUM LEVEL 2 ****/
 							for ($sub2 = 1;$sub2 <= $number; $sub2++) {	 
								$sublevel_2_folder = explode("/",$folder[$sub2]);
								
								// if 3
								if 
								(($folder[$top] === $sublevel_2_folder[0] 
								AND count($sublevel_2_folder) === 3 
								AND $currentfolder === $sublevel_2_folder[0]."/".$sublevel_2_folder[1] 
								AND $parentid[$sub2] === $id[$sub1]) 
								OR
								(getAlbumID() != $id[$sub1] 
								AND $id[$sub1] === $parentid[$sub2] 
								AND count($sublevel_2_folder) === 3 
								AND $sublevel_current[1] === $sublevel_1_folder[1])) {
									$sub2_count++; // count subalbums for checking if to open or close the sublist
									if ($sub2_count === 1) { 
										echo "<ul".$css_class.">\n"; // open sublevel 2 sublist once if subalbums
									 } 
									
									// print link <strong> if selected
									if(getAlbumID() === $id[$sub2]) { 
									$strong_start = "<strong>"; $strong_end = "</strong>"; $active = $css_class_active;
									} else { 
										$strong_start = ""; $strong_end = ""; $active = "";
									}
									
									// link either for list or jump menu display
									if($option === "list") { 
										echo "<li".$active.">".$strong_start."<a href='".$albumlinkpath.$folder[$sub2]."'>".htmlspecialchars($title[$sub2])."</a>".$strong_end.$imagecount[$sub2]; 
									} else if ($option === "jump") { 
									echo "<option value='".$albumlinkpath.$folder[$sub2]."'>&gt; &gt; ".htmlspecialchars($title[$sub2]).$imagecount[$sub2]."</option>"; 
									}
									$sub3_count = 0;  
									
 					 					/**** SUBALBUM LEVEL 3 ****/
										for ($sub3 = 1;$sub3 <= $number; $sub3++) {	 
											$sublevel_3_folder = explode("/",$folder[$sub3]);
										
											// if 4
											if 
											(($folder[$top] === $sublevel_3_folder[0] 
											AND count($sublevel_3_folder) === 4 
											AND $currentfolder === $sublevel_2_folder[0]."/".$sublevel_2_folder[1]."/".$sublevel_2_folder[2] 
											AND $parentid[$sub3] === $id[$sub2]) 
											OR
											(getAlbumID() != $id[$sub2] 
											AND $id[$sub2] === $parentid[$sub3] 
											AND count($sublevel_3_folder) === 4 
											AND $sublevel_current[2] === $sublevel_3_folder[2])) {
												$sub3_count++; // count subalbums for checking if to open or close the sublist
												if ($sub3_count === 1) { 
													echo "<ul".$css_class.">\n"; // open sublevel 3 sublist once if subalbums
									 			} 		
												
												// print link <strong> if selected
												if(getAlbumID() === $id[$sub3]) { 
												$strong_start = "<strong>"; $strong_end = "</strong>"; $active = $css_class_active;
												} else { 
													$strong_start = ""; $strong_end = ""; $active = "";
												}
												
												// link either for list or jump menu display
												if($option === "list") {	
													echo "<li".$active.">".$strong_start."<a href='".$albumlinkpath.$folder[$sub3]."'>".htmlspecialchars($title[$sub3])."</a>".$strong_end.$imagecount[$sub3]; 
												}	else if ($option === "jump"){ 
													echo "<option value='".$albumlinkpath.$folder[$sub3]."'>&gt; &gt; &gt; ".htmlspecialchars($title[$sub3]).$imagecount[$sub3]."</option>"; 
												 }
												$sub4_count = 0;
												
												/**** SUBALBUM LEVEL 4 ****/
														for ($sub4 = 1;$sub4 <= $number; $sub4++) {	 
														$sublevel_4_folder = explode("/",$folder[$sub4]);
														
														// if 5
														if 
														(($folder[$top] === $sublevel_4_folder[0] 
														AND count($sublevel_4_folder) === 5 
														AND $currentfolder === $sublevel_3_folder[0]."/".$sublevel_3_folder[1]."/".$sublevel_3_folder[2]."/".$sublevel_3_folder[3]
														AND $parentid[$sub4] === $id[$sub3]) 
														OR
														(getAlbumID() != $id[$sub3] 
														AND $id[$sub3] === $parentid[$sub4] 
														AND count($sublevel_4_folder) === 5 
														AND $sublevel_current[3] === $sublevel_4_folder[3])){
															$sub4_count++; // count subalbums for checking if to open or close the sublist
															if ($sub4_count === 1) { 
																echo "<ul".$css_class.">\n"; // open sublevel 4 sublist once if subalbums		
															} 
															// print link <strong> if selected
															if(getAlbumID() === $id[$sub4]) {
																$strong_start = "<strong>"; $strong_end = "</strong>"; $active = $css_class_active;
															} else { 
																$strong_start = ""; $strong_end = ""; $active = "";
																}
															// link either for list or jump menu display
														 if($option === "list") {	
														 	echo "<li".$css_class_active.">".$strong_start."<a href='".$albumlinkpath.$folder[$sub4]."'>".htmlspecialchars($title[$sub4])."</a>".$strong_end.$imagecount[$sub4]; 
														 } else if ($option === "jump") { 
														 	echo "<option value='".$albumlinkpath.$folder[$sub4]."'>&gt; &gt; &gt; &gt; ".htmlspecialchars($title[$sub4]).$imagecount[$sub4]."</option>"; 
														 }
														} // if subalbum level 4 - end
 													}	// subalbum level 4 - end
 													if($sub4_count > 0 AND $option === "list") 	{ // sublevel 4 sublist end if subalbums
 				 											echo "</ul>\n";
 				 										} 
 													if($option2 === "list")	{	// sub level 4 list item end		
 														echo "</li>\n"; 
 													}
 										} // if subalbum level 3 - end
 										}	// subalbum level 3 - end
 											if($sub3_count > 0  AND $option === "list") { // sublevel 3 sublist end if subalbums
 				 									echo "</ul>\n";
 				 									} 
										if($option === "list") { // sub level 2 list item end
											echo "</li>\n"; 
										} 
											
 					 				} // if subalbum level 2 - end
 								} // subalbum level 2 - end
 								if($sub2_count > 0 AND $option === "list") {// sublevel 2 sublist end if subalbums
 				 				echo "</ul>\n";
 				 				}
 							if($option === "list") { // sub level 1 list item end
								echo "</li>\n"; 
							}
 								 
 					}  // if subalbum level 1 - end
 				} // subalbum level 1 - end
 			 	if($sub1_count > 0 AND $option === "list") {// sublevel 1 sublist end if subalbums
 				 echo "</ul>\n"; 
 			 	}
				 if($option === "list") { // top level list item end
		 	echo "</li>\n"; 
		 }
 		} // if Top level albums - end
} // top level album loop - end
if($option === "list"){ 
	echo "</ul>\n";	
}
if($option === "jump") {
?>

<option selected> *Choose an album*</option>
</select></p>
<script language="JavaScript">
<!--
function gotoLink(form) {
 	var OptionIndex=form.ListBoxURL.selectedIndex;
	parent.location = form.ListBoxURL.options[OptionIndex].value;}
//-->
</script>
</form>

<?php }

} // function end

?>
