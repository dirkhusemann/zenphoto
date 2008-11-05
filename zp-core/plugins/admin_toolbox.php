<?php
/**
 * Provides a toolbox of admin functions.
 * Replaces the same function in template-functions.php so that it may be extended
 *
 * Place a call on printAdminToolbox() in scripts where you want the toolbox.
 *
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */

$plugin_description = gettext("Creates an administrative toolbox on your theme pages.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.0.0';
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_plugins---admin_toolbox.php.html";

/**
 * Prints the clickable drop down toolbox on any theme page with generic admin helpers
 * @param string $id the html/css theming id
 */
function printAdminToolbox($id='admin') {
	global $_zp_current_album, $_zp_current_image, $_zp_current_search, $_zp_loggedin, $_zp_gallery_page;
	if (zp_loggedin()) {
		$zf = WEBPATH."/".ZENFOLDER;
		$dataid = $id . '_data';
		$page = getCurrentPage();
		$redirect = '';
		echo "\n<script type=\"text/javascript\" src=\"".$zf."/js/admin.js\"></script>\n";
		echo '<div id="' .$id. '">'."\n".'<h3><a href="javascript: toggle('. "'" .$dataid."'".');">'.gettext('Admin Toolbox').'</a></h3>'."\n"."\n</div>";
		echo '<div id="' .$dataid. '" style="display: none;">'."\n";
		echo "<ul style='list-style-type: none;'>";
		echo "<li>";
		printAdminLink(gettext('Admin'), '', "</li>\n");
		if (isset($_GET['p'])) {
			$redirect = "&amp;p=" . $_GET['p'];
		}
		if ($page>1) {
			$redirect .= "&amp;page=$page";
		}
		$gal = getOption('custom_index_page');
		if (empty($gal) || !file_exists(SERVERPATH.'/'.THEMEFOLDER.'/'.getOption('current_theme').'/'.UTF8ToFilesystem($gal).'.php')) {
			$gal = 'index.php';
		} else {
			$gal .= '.php';
		}
		if ($_zp_gallery_page === $gal) {
			if ($_zp_loggedin & (ADMIN_RIGHTS | EDIT_RIGHTS)) {
				echo "<li>";
				printSortableGalleryLink(gettext('Sort gallery'), gettext('Manual sorting'));
				echo "</li>\n";
			}
			if ($_zp_loggedin & (ADMIN_RIGHTS | UPLOAD_RIGHTS)) {
				echo "<li>";
				printLink($zf . '/admin-upload.php', gettext("New album"), NULL, NULL, NULL);
				echo "</li>\n";
			}
		} else if ($_zp_gallery_page === 'album.php') {
			$albumname = $_zp_current_album->name;
			if (isMyAlbum($albumname, EDIT_RIGHTS)) {
				echo "<li>";
				printSubalbumAdmin(gettext('Edit album'), '', "</li>\n");
				if (!$_zp_current_album->isDynamic()) {
					echo "<li>";
					printSortableAlbumLink(gettext('Sort album'), gettext('Manual sorting'));
					echo "</li>\n";
				}
				echo "<li><a href=\"javascript: confirmDeleteAlbum('".$zf."/admin.php?page=edit&amp;action=deletealbum&amp;album=" .
					urlencode(urlencode($albumname)) .
					"','".js_encode(gettext("Are you sure you want to delete this entire album?"))."','".js_encode(gettext("Are you Absolutely Positively sure you want to delete the album? THIS CANNOT BE UNDONE!")).
					"');\" title=\"".gettext("Delete the album")."\">".gettext("Delete album")."</a></li>\n";
			}
			if (isMyAlbum($albumname, UPLOAD_RIGHTS) && !$_zp_current_album->isDynamic()) {
				echo "<li>";
				printLink($zf . '/admin-upload.php?album=' . urlencode($albumname), gettext("Upload Here"), NULL, NULL, NULL);
				echo "</li>\n";
				echo "<li>";
				printLink($zf . '/admin-upload.php?new&album=' . urlencode($albumname), gettext("New Album Here"), NULL, NULL, NULL);
				echo "</li>\n";
			}
			$redirect = "&amp;album=".urlencode($albumname)."&amp;page=$page";
		} else if ($_zp_gallery_page === 'image.php') {
			$albumname = $_zp_current_album->name;
			$imagename = urlencode($_zp_current_image->filename);
			if (isMyAlbum($albumname, EDIT_RIGHTS)) {
				echo "<li><a href=\"javascript: confirmDeleteImage('".$zf."/admin.php?amp;page=edit&action=deleteimage&amp;album=" .
				urlencode(urlencode($albumname)) . "&amp;image=". urlencode(urlencode($imagename)) . "','". js_encode(gettext("Are you sure you want to delete the image? THIS CANNOT BE UNDONE!")) . "');\" title=\"".gettext("Delete the image")."\">".gettext("Delete image")."</a>";
				echo "</li>\n";
			}
			$redirect = "&amp;album=".urlencode($albumname)."&amp;image=$imagename";
		} else if (($_zp_gallery_page === 'search.php')&& !empty($_zp_current_search->words)) {
			if ($_zp_loggedin & (ADMIN_RIGHTS | UPLOAD_RIGHTS)) {
				echo "<li><a href=\"".$zf."/admin-dynamic-album.php\" title=\"".gettext("Create an album from the search")."\">".gettext("Create Album")."</a></li>";
			}
			$redirect = "&amp;p=search" . $_zp_current_search->getSearchParams() . "&amp;page=$page";
		}
		if(function_exists('is_NewsArticle')) {
			if ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS)) {
				echo "<li><a href=\"".$zf."/plugins/zenpage/page-admin.php\">".gettext("Zenpage")."</a></li>";
			}
			if (is_NewsArticle() && ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
				echo "<li><a href=\"".$zf."/plugins/zenpage/news-article-edit.php?id=".getNewsID()."\">".gettext("Edit Article")."</li>";
				?> 
				<li><a href="javascript: confirmDeleteImage('<?php echo $zf; ?>/plugins/zenpage/news-article-admin.php?del=<?php echo getNewsID(); ?>','<?php echo js_encode(gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!")); ?>')" title="<?php echo gettext("Delete article"); ?>"><?php echo gettext("Delete Article"); ?></a></li>
				<?php
				echo "<li><a href=\"".$zf."/plugins/zenpage/news-article-add.php\">".gettext("Add Article")."</li>";
			}
			if (is_Pages() && ($_zp_loggedin & (ADMIN_RIGHTS | ZENPAGE_RIGHTS))) {
				echo "<li><a href=\"".$zf."/plugins/zenpage/page-edit.php?id=".getPageID()."\">".gettext("Edit Page")."</li>";
				?> 
				<li><a href="javascript: confirmDeleteImage('<?php echo $zf; ?>/plugins/zenpage/page-admin.php?del=<?php echo getPageID(); ?>','<?php echo js_encode(gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE!")); ?>')" title="<?php echo gettext("Delete page"); ?>"><?php echo gettext("Delete Page"); ?></a></li>
				<?php	
				echo "<li><a href=\"".FULLWEBPATH."/".ZENFOLDER."/plugins/zenpage/page-add.php\">".gettext("Add Page")."</li>";
			}
		}	
		if ($_zp_loggedin & (ADMIN_RIGHTS | OPTIONS_RIGHTS)) {
			echo "<li>";
			printLink($zf . '/admin-options.php', gettext("Options"), NULL, NULL, NULL);
			echo "</li>\n";
		}
		echo "<li><a href=\"".$zf."/admin.php?logout$redirect\">".gettext("Logout")."</a></li>\n";
		echo "</ul></div>\n";
	}
}
?>