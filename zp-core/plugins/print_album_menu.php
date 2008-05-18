<?php	
/** printAlbumMenu for Zenphoto 1.1.6 
 * 
 * Changelog
 * 
 * 1.3.3:
 * - Code reworked, now uses the gallery/album objects so that protected and unpublished 
 * 	 albums as well as the album sortorder are handled automatically
 * - For better usability selected album names in the list are now not links anymore as suggested 
 *   on the forum a while ago (also the former used <strong> is skipped)
 * 
 * 1.3.2:
 * - turned into a plugin for zenphoto 1.1.5 svn/1.1.6
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
 * 1.2: Now works with sbillard's album publishing function.
 * 
 * 1.1.:
 * - Option for album list or a drop down jump menu if you want to save space 
 * - Displays the number of images in the album (like e.g. wordpress does with articles)
 * - Option for disabling the counter
 * - Parameters for CSS-Ids for styling, separate ones for main album and subalbums
 * - Renamed the function name from show_album_menu() to more zp style printAlbumMenu()
 */

$plugin_description = gettext("Adds a theme function printAlbumMenu() to print an album menu either as a nested list up to 4 sublevels (context sensitive) or as a dropdown menu.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.3.3';
$plugin_URL = "http://www.zenphoto.org/documentation/zenphoto/_plugins---print_album_menu.php.html";

/**
 * Prints a list of all albums context sensitive up to the 4th subalbum level.
 * 
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the zenphoto admin plugins tab;
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
	global $_zp_gallery, $_zp_current_album;
	$albumpath = rewrite_path("/", "/index.php?album=");
	if(!empty($_zp_current_album)) {
		$currentfolder = $_zp_current_album->name;
	}
	// check if css parameters are used
	if ($css_id != "") { $css_id = " id='".$css_id."'"; }
	if ($css_id_active != "") { $css_id_active = " id='".$css_id_active."'"; }
	if ($css_class != "") { $css_class = " class='".$css_class."'"; }
	if ($css_class_active != "") { $css_class_active = " id='".$css_class_active."'"; }

	/**** TOP LEVEL ALBUM  ****/
	if($option === "list") {
		echo "<ul".$css_id.">\n"; // start top level list
	} else if ($option === "jump") { ?>
<form name="AutoListBox">
<p><select name="ListBoxURL" size="1" language="javascript"
		onchange="gotoLink(this.form);">
		<option value="<?php echo getGalleryIndexURL(); ?>">Gallery Index</option>
		<?php }

		// top level
		$gallery = $_zp_gallery;
		$albums = $_zp_gallery->getAlbums();
		foreach ($albums as $toplevelalbum) {
			$topalbum = new Album($gallery,$toplevelalbum,true);
			if($option2 === "count" AND $topalbum->getNumImages() > 0) {
				$count = " (".$topalbum->getNumImages().")";
			} else {
				$count = "";
			}
			if(getAlbumID() === $topalbum->getAlbumID()) {
				$link_start = "";
				$link_end = "";
				$active = $css_id_active;
			} else {
				$link_start = "<a href='".$albumpath.$topalbum->name."'>";
				$link_end = "</a>";
				$active = "";
			}
			if($option === "list") {
				echo "<li".$active.">".$link_start.htmlspecialchars($topalbum->getTitle()).$link_end.$count;
			} else if ($option === "jump") {
				echo "<option value='".$albumpath.$topalbum->name."'>".htmlspecialchars($topalbum->getTitle()).$count."</option>";
			}
			$sub1_count = 0;

			// 1st sublevel
			$subalbums1 = $topalbum->getSubAlbums();
			foreach($subalbums1 as $sublevelalbum1) {
				$subalbum1 = new Album($gallery,$sublevelalbum1,true);
				$sublevel_1_folder = explode("/",$subalbum1->name);
				$sublevel_current = explode("/", $currentfolder);
					
				// if 2: (if in parentalbum) OR (if in subalbum)
				if((strpos($subalbum1->name,$topalbum->name) === 0
				AND strpos($subalbum1->name,$currentfolder) === 0
				AND $currentfolder === $sublevel_1_folder[0])
				OR
				(getAlbumID() != $topalbum->getAlbumID()
				AND strpos($subalbum1->name,$topalbum->name) === 0
				AND $sublevel_current[0] === $topalbum->name)) {
					$sub1_count++; // count subalbums for checking if to open or close the sublist
					if ($sub1_count === 1) { // open sublevel 1 sublist once if subalbums
						echo "<ul".$css_class.">\n";
					}
					if($option2 === "count" AND $subalbum1->getNumImages() > 0) {
						$count = " (".$subalbum1->getNumImages().")";
					} else {
						$count = "";
					}
					if(getAlbumID() === $subalbum1->getAlbumID()) {
						$link_start = "";
						$link_end = "";
						$active = $css_class_active;
					} else {
						$link_start = "<a href='".$albumpath.$subalbum1->name."'>";
						$link_end = "</a>";
						$active = "";
					}
					if ($option === "list") {
						echo "<li".$active.">".$link_start.htmlspecialchars($subalbum1->getTitle()).$link_end.$count;
					} else if ($option === "jump") {
						echo "<option value='".$albumpath.$subalbum1->name."'>&raquo; ".htmlspecialchars($subalbum1->getTitle()).$count."</option>";
					}
					$sub2_count = 0;

					// 2nd sublevel
					$subalbums2 = $subalbum1->getSubAlbums();
					foreach($subalbums2 as $sublevelalbum2) {
						$subalbum2 = new Album($gallery,$sublevelalbum2,true);
						$sublevel_2_folder = explode("/",$subalbum2->name);
						$sublevel_current = explode("/", $currentfolder);

						// if 3
						if((strpos($subalbum2->name,$subalbum1->name) === 0
						AND strpos($subalbum2->name,$currentfolder) === 0
						AND $currentfolder === $sublevel_2_folder[0]."/".$sublevel_2_folder[1])
						OR
						(getAlbumID() != $subalbum1->getAlbumID()
						AND strpos($subalbum2->name,$subalbum1->name) === 0
						AND $sublevel_current[1] === $sublevel_1_folder[1])) {
							$sub2_count++; // count subalbums for checking if to open or close the sublist
							if ($sub2_count === 1) { // open sublevel 1 sublist once if subalbums
								echo "<ul".$css_class.">\n";
							}
							if($option2 === "count" AND $subalbum2->getNumImages() > 0) {
								$count = " (".$subalbum2->getNumImages().")";
							} else {
								$count = "";
							}
							if(getAlbumID() === $subalbum2->getAlbumID()) {
								$link_start = "";
								$link_end = "";
								$active = $css_class_active;
							} else {
								$link_start = "<a href='".$albumpath.$subalbum2->name."'>";
								$link_end = "</a>";
								$active = "";
							}
							if($option === "list") {
								echo "<li".$active.">".$link_start.htmlspecialchars($subalbum2->getTitle()).$link_end.$count;
							} else if ($option === "jump") {
								echo "<option value='".$albumpath.$subalbum2->name."'>&raquo; &raquo; ".htmlspecialchars($subalbum2->getTitle()).$count."</option>";
							}
							$sub3_count = 0;

							// 3rd sublevel
							$subalbums3 = $subalbum2->getSubAlbums();
							foreach($subalbums3 as $sublevelalbum3) {
								$subalbum3 = new Album($gallery,$sublevelalbum3,true);
								$sublevel_3_folder = explode("/",$subalbum3->name);
								$sublevel_current = explode("/", $currentfolder);

								// if 4
								if((strpos($subalbum3->name,$subalbum2->name) === 0
								AND strpos($subalbum3->name,$currentfolder) === 0
								AND $currentfolder === $sublevel_3_folder[0]."/".$sublevel_3_folder[1]."/".$sublevel_3_folder[2])
								OR
								(getAlbumID() != $subalbum2->getAlbumID()
								AND strpos($subalbum3->name,$subalbum2->name) === 0
								AND $sublevel_current[2] === $sublevel_3_folder[2])) {
									$sub3_count++; // count subalbums for checking if to open or close the sublist
									if ($sub3_count === 1) { echo "<ul".$css_class.">\n"; } // open sublevel 1 sublist once if subalbums
									if($option2 === "count" AND $subalbum3->getNumImages() > 0) {
										$count = " (".$subalbum3->getNumImages().")";
									} else {
										$count = "";
									}
									if(getAlbumID() === $subalbum3->getAlbumID()) {
										$link_start = "";
										$link_end = "";
										$active = $css_class_active;
									} else {
										$link_start = "<a href='".$albumpath.$subalbum3->name."'>";
										$link_end = "</a>";
										$active = "";
									}
									if($option === "list") {
										echo "<li".$active.">".$link_start.htmlspecialchars($subalbum3->getTitle()).$link_end.$count;
									} else if ($option === "jump") {
										echo "<option value='".$albumpath.$subalbum3->name."'>&raquo; &raquo; &raquo; ".htmlspecialchars($subalbum3->getTitle()).$count."</option>";
									}
									$sub4_count = 0;

									/**** SUBALBUM LEVEL 4 ****/
									$subalbums4 = $subalbum3->getSubAlbums();
									foreach($subalbums4 as $sublevelalbum4) {
										$subalbum4 = new Album($gallery,$sublevelalbum4,true);
										$sublevel_4_folder = explode("/",$subalbum4->name);
										$sublevel_current = explode("/", $currentfolder);

										// if 5
										if((strpos($subalbum4->name,$subalbum3->name) === 0
										AND strpos($subalbum4->name,$currentfolder) === 0
										AND $currentfolder === $sublevel_4_folder[0]."/".$sublevel_4_folder[1]."/".$sublevel_4_folder[2]."/".$sublevel_4_folder[3])
										OR
										(getAlbumID() != $subalbum3->getAlbumID()
										AND strpos($subalbum4->name,$subalbum3->name) === 0
										AND $sublevel_current[3] === $sublevel_4_folder[3])){
											$sub4_count++; // count subalbums for checking if to open or close the sublist
											if ($sub4_count === 1) { echo "<ul".$css_class.">\n"; } // open sublevel 1 sublist once if subalbums
											if($option2 === "count" AND $subalbum4->getNumImages() > 0) {
												$count = " (".$subalbum4->getNumImages().")";
											} else {
												$count = "";
											}
											if(getAlbumID() === $subalbum4->getAlbumID()) {
												$link_start = "";
												$link_end = "";
												$active = $css_class_active;
											} else {
												$link_start = "<a href='".$albumpath.$subalbum4->name."'>";
												$link_end = "</a>";
												$active = "";
											}
											if($option === "list") {
												echo "<li".$active.">".$link_start.htmlspecialchars($subalbum4->getTitle()).$link_end.$count;
											} else if ($option === "jump") {
												echo "<option value='".$albumpath.$subalbum4->name."'>&raquo; &raquo; &raquo; &raquo; ".htmlspecialchars($subalbum4->getTitle()).$count."</option>";
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
		 //	} // if Top level albums - end
		} // top level album loop - end
		if($option === "list"){
			echo "</ul>\n";
		}
		if($option === "jump") {
			?>
		<option selected>*Choose an album*</option>
</select></p>
<script language="JavaScript">
<!--
function gotoLink(form) {
 	var OptionIndex=form.ListBoxURL.selectedIndex;
	parent.location = form.ListBoxURL.options[OptionIndex].value;}
//-->
</script></form>
			<?php }
} // function end
?>