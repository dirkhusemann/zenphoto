<?php
/**
 * provides the Comments tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (!($_zp_loggedin & (ADMIN_RIGHTS | COMMENT_RIGHTS))) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}


$gallery = new Gallery();
if (isset($_GET['page'])) {
	$page = sanitize($_GET['page']);
} else {
	$page = '';
}
if (isset($_GET['fulltext']) && $_GET['fulltext']) $fulltext = true; else $fulltext = false;
if (isset($_GET['viewall'])) $viewall = true; else $viewall = false;

/* handle posts */
if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	
	case "spam":
		$comment = new Comment(sanitize_numeric($_GET['id']));
		zp_apply_filter('comment_disapprove', $comment);
		$comment->setInModeration(1);
		$comment->save();
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin-comments.php');
		exit();

	case "notspam":
		$comment = new Comment(sanitize_numeric($_GET['id']));
		zp_apply_filter('comment_approve', $comment);
		$comment->setInModeration(0);
		$comment->save();
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin-comments.php');
		exit();
	
	case 'deletecomments':
		if (isset($_POST['ids']) || isset($_GET['id'])) {
			if (isset($_GET['id'])) {
				$ids = array($_GET['id']);
			} else {
				$ids = $_POST['ids'];
			}
			$total = count($ids);
			if ($total > 0) {
				$n = 0;
				$sql = "DELETE FROM ".prefix('comments')." WHERE ";
				foreach ($ids as $id) {
					$n++;
					$sql .= "id='".sanitize_numeric($id)."' ";
					if ($n < $total) $sql .= "OR ";
				}
				query($sql);
			}
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-comments.php?ndeleted=$n");
			exit();
		} else {
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-comments.php?ndeleted=0");
			exit();
		}

	case 'savecomment':
		if (!isset($_POST['id'])) {
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-comments.php");
			exit();
		}
		$id = sanitize_numeric($_POST['id']);
		$name = zp_escape_string(sanitize($_POST['name'], 3));
		$email = zp_escape_string(sanitize($_POST['email'], 3));
		$website = zp_escape_string(sanitize($_POST['website'], 3));
		$date = zp_escape_string(sanitize($_POST['date'], 3));
		$comment = zp_escape_string(sanitize($_POST['comment'], 1));
		$custom = zp_apply_filter('save_comment_custom_data', '');
		if (!empty($custom)) {
			$custom = ", `custom_data`='".zp_escape_string($custom)."'";
		}

		$sql = "UPDATE ".prefix('comments')." SET `name` = '$name', `email` = '$email', `website` = '$website', `comment` = '$comment'".$custom." WHERE id = $id";
		query($sql);

		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-comments.php?sedit");
		exit();

	}
}


printAdminHeader();
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs('comments');
echo "\n" . '<div id="content">';

if ($page == "editcomment" && isset($_GET['id']) ) { ?>
<h1><?php echo gettext("edit comment"); ?></h1>
<div class="box" style="padding: 10px">
<?php
	$id = sanitize_numeric($_GET['id']);
	
	$commentarr = query_single_row("SELECT * FROM ".prefix('comments')." WHERE id = $id LIMIT 1");
	extract($commentarr);
	?>

<form action="?action=savecomment" method="post"><input
	type="hidden" name="id" value="<?php echo $id; ?>" />
<table style="float:left;margin-right:2em;">

	<tr>
		<td width="100"><?php echo gettext("Author:"); ?></td>
		<td><input type="text" size="40" name="name" value="<?php echo $name; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("Web Site:"); ?></td>
		<td><input type="text" size="40" name="website" value="<?php echo $website; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("E-Mail:"); ?></td>
		<td><input type="text" size="40" name="email" value="<?php echo $email; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("Date/Time:"); ?></td>
		<td><input type="text" size="18" name="date" value="<?php echo $date; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("IP:"); ?></td>
		<td><input type="text" disabled="disabled" size="18" name="date" value="<?php echo $IP; ?>" /></td>
	</tr>
	<?php
 	echo zp_apply_filter('edit_comment_custom_data', '', $custom_data);
	?>
	<tr>
		<td valign="top"><?php echo gettext("Comment:"); ?></td>
		<td><textarea rows="8" cols="60" name="comment" /><?php echo $comment; ?></textarea></td>
	</tr>
	<tr>
		<td></td>
		<td>
		
		
		</td>
	</tr>
</table>
<div style="width:260px; float:right">
<h2 class="h2_bordered_edit"><?php echo gettext('Comment management'); ?></h2>
<div class="box-edit">
<?php
	if ($inmoderation) {
		$status_moderation = gettext('Comment is unapproved');
		$link_moderation = gettext('Approve');
		$title_moderation = gettext('Approve this comment');
		$url_moderation = '?action=moderation&amp;id='.$id;
		$linkimage = "images/pass.png";
	} else {
		$status_moderation = gettext('Comment is approved');
		$link_moderation = gettext('Unapprove');
		$title_moderation = gettext('Unapprove this comment');
		$url_moderation = '?action=unapprove&amp;id='.$id;
		$linkimage = "images/warn.png";
	}
	
	if ($private) {
		$status_private = gettext('Comment is private');
	} else {
		$status_private = gettext('Comment is public');
	}

	if ($anon) {
		$status_anon = gettext('Comment is anonymous');
	} else {
		$status_anon = gettext('Comment is not anonymous');
	}


?>
<p><?php echo $status_moderation; ?>. <div class="buttons"><a href="<?php echo $url_moderation; ?>" title="<?php echo $title_moderation; ?>" ><img src="<?php echo $linkimage; ?>" alt="" /><?php echo $link_moderation; ?></a></div></p>
<br clear="all" />
<hr />
<p><?php echo $status_private; ?></p>
<p><?php echo $status_anon; ?></p>
<hr />
<p class="buttons">
<a href="javascript:if(confirm('<?php echo gettext('Are you sure you want to delete this comment?'); ?>')) { window.location='?action=deletecomments&id=<?php echo $id; ?>'; }"
		title="<?php echo gettext('Delete'); ?>" ><img src="images/fail.png" alt="" />
		<?php echo gettext('Delete'); ?></a></p>
		<br style="clear:both" />
<p class="buttons" style="margin-top: 10px">
		<button type="submit" title="<?php echo gettext("Save"); ?>">
		<img src="images/pass.png" alt="" />
		<strong><?php echo gettext("Save"); ?></strong>
		</button>
		</p>
		<br style="clear:both;" />
<p class="buttons" style="margin-top: 10px">
		<button type="button" title="<?php echo gettext("Cancel"); ?>" onclick="window.location = 'admin-comments.php';">
		<img src="images/reset.png" alt="" />
		<strong><?php echo gettext("Cancel"); ?></strong>
		</button>
		</p>
		<br style="clear:both" />
</div><!-- div box-edit-unpadded end -->
</div>
</form>
<br clear="all" />
</div> <!-- div box end -->
<?php
// end of $page == "editcomment"
} else {
	// Set up some view option variables.

	if (isset($_GET['fulltext']) && $_GET['fulltext']) {
		define('COMMENTS_PER_PAGE',10);
		$fulltext = true;
		$fulltexturl = '?fulltext=1';
	} else {
		define('COMMENTS_PER_PAGE',20);
		$fulltext = false;
		$fulltexturl = '';
	}
	$allcomments = fetchComments(NULL);

	if (isset($_GET['subpage'])) {
		$pagenum = max(intval($_GET['subpage']),1);
	} else {
		$pagenum = 1;
	}

	$comments = array_slice($allcomments, ($pagenum-1)*COMMENTS_PER_PAGE, COMMENTS_PER_PAGE);
	$allcommentscount = count($allcomments);
	$totalpages = ceil(($allcommentscount / COMMENTS_PER_PAGE));
	?>
<h1><?php echo gettext("Comments"); ?></h1>

<?php /* Display a message if needed. Fade out and hide after 2 seconds. */
	if ((isset($_GET['ndeleted']) && $_GET['ndeleted'] > 0) || isset($_GET['sedit'])) { ?>
<div class="messagebox" id="fade-message"><?php if (isset($_GET['ndeleted'])) { ?>
<h2><?php echo $_GET['ndeleted']; ?> <?php echo gettext("Comments deleted successfully."); ?></h2>
<?php } ?> <?php if (isset($_GET['sedit'])) { ?>
<h2><?php echo gettext("Comment saved successfully."); ?></h2>
<?php } ?></div>
<?php } ?>

<p><?php echo gettext("You can edit or delete comments on your photos."); ?></p>

<?php if ($totalpages > 1) {?>
	<div align="center">
	<?php adminPageNav($pagenum,$totalpages,'admin-comments.php',$fulltexturl); ?>
	</div>
	<?php } ?>

<form name="comments" action="?action=deletecomments" method="post"	onsubmit="return confirm('<?php echo gettext("Are you sure you want to delete these comments?"); ?>');">
<input type="hidden" name="subpage" value="<?php echo $pagenum ?>" />
<table class="bordered">
	<tr>
		<th>&nbsp;</th>
		<th><?php echo gettext("Album/Image"); ?></th>
		<th><?php echo gettext("Author/Link"); ?></th>
		<th><?php echo gettext("Date/Time"); ?></th>
		<th><?php echo gettext("Comment"); ?>
		<?php if(!$fulltext) { ?>(
			<a href="?fulltext=1<?php echo $viewall ? "&amp;viewall":""; ?>"><?php echo gettext("View full text"); ?></a> ) <?php
		} else {
			?>( <a	href="admin-comments.php?fulltext=0"<?php echo $viewall ? "?viewall":""; ?>"><?php echo gettext("View truncated"); ?></a> )<?php
		} ?>
		</th>
		<th><?php echo gettext("E&#8209;Mail"); ?></th>
		<th><?php echo gettext("IP address"); ?></th>
		<th><?php echo gettext("Private"); ?></th>
		<th><?php echo gettext("SPAM"); ?></th>
		<th><?php echo gettext("Edit"); ?></th>
		<th><?php echo gettext("Delete"); ?></th>
	</tr>
	<?php
	foreach ($comments as $comment) {
		$id = $comment['id'];
		$author = $comment['name'];
		$email = $comment['email'];
		$link = gettext('<strong>database error</strong> '); // in case of such
		$image = '';
		$albumtitle = '';
		
		// ZENPAGE: switch added for zenpage comment support
		switch ($comment['type']) {
			case "albums":
				$image = '';
				$title = '';
				$albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
 										" WHERE `id`=" . $comment['ownerid']);
				if ($albmdata) {
					$albumdata = $albmdata[0];
					$album = $albumdata['folder'];
					$albumtitle = get_language_string($albumdata['title']);
					$link = "<a href=\"".rewrite_path("/$album","/index.php?album=".urlencode($album))."\">".$albumtitle.$title."</a>";
					if (empty($albumtitle)) $albumtitle = $album;
				}
				break;
			case "news": // ZENPAGE: if plugin is installed
				if(getOption("zp_plugin_zenpage")) {
					$titlelink = '';
					$title = '';
					$newsdata = query_full_array("SELECT `title`, `titlelink` FROM ". prefix('zenpage_news') .
 										" WHERE `id`=" . $comment['ownerid']);
					if ($newsdata) {
						$newsdata = $newsdata[0];
						$titlelink = $newsdata['titlelink'];
						$title = get_language_string($newsdata['title']);
				  	$link = "<a href=\"".rewrite_path("/".ZENPAGE_NEWS."/".$titlelink,"/index.php?p=".ZENPAGE_NEWS."&amp;title=".urlencode($titlelink))."\">".$title."</a><br /> ".gettext("[news]");
					}
				}
				break;
			case "pages": // ZENPAGE: if plugin is installed
				if(getOption("zp_plugin_zenpage")) {
					$image = '';
					$title = '';
					$pagesdata = query_full_array("SELECT `title`, `titlelink` FROM ". prefix('zenpage_pages') .
 										" WHERE `id`=" . $comment['ownerid']);
					if ($pagesdata) {
						$pagesdata = $pagesdata[0];
						$titlelink = $pagesdata['titlelink'];
						$title = get_language_string($pagesdata['title']);
						$link = "<a href=\"".rewrite_path("/".ZENPAGE_PAGES."/".$titlelink,"/index.php?p=".ZENPAGE_PAGES."&amp;title=".urlencode($titlelink))."\">".$title."</a><br /> ".gettext("[page]");
					}
				}
				break;
			default: // all the image types
				$imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
 										" WHERE `id`=" . $comment['ownerid']);
				if ($imagedata) {
					$imgdata = $imagedata[0];
					$image = $imgdata['filename'];
					if ($imgdata['title'] == "") $title = $image; else $title = get_language_string($imgdata['title']);
					$title = '/ ' . $title;
					$albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
 											" WHERE `id`=" . $imgdata['albumid']);
					if ($albmdata) {
						$albumdata = $albmdata[0];
						$album = $albumdata['folder'];
						$albumtitle = get_language_string($albumdata['title']);
						$link = "<a href=\"".rewrite_path("/$album/$image","/index.php?album=".urlencode($album).	"&amp;image=".urlencode($image))."\">".$albumtitle.$title."</a>";
						if (empty($albumtitle)) $albumtitle = $album;
					}
				}
				break;
		}
		$date  = myts_date('%m/%d/%Y %I:%M %p', $comment['date']);
		$website = $comment['website'];
		$shortcomment = truncate_string($comment['comment'], 123);
		$fullcomment = $comment['comment'];
		$inmoderation = $comment['inmoderation'];
		$private = $comment['private'];
		$anon = $comment['anon'];
		?>

	<tr>
		<td><input type="checkbox" name="ids[]" value="<?php echo $id; ?>"
			onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" /></td>
		<td><?php echo $link; ?></td>
		<td>
		<?php
		echo $website ? "<a href=\"$website\">$author</a>" : $author;
		if ($anon) {
			echo ' <a title="'.gettext('Anonymous posting').'"><img src="images/action.png" style="border: 0px;" alt="'. gettext("Anonymous posting").'" /></a>';
		}
		?>
		</td>
		<td><?php echo $date; ?></td>
		<td><?php echo ($fulltext) ? $fullcomment : $shortcomment; ?></td>
		<td align="center"><a
			href="mailto:<?php echo $email; ?>?body=<?php echo commentReply($fullcomment, $author, $image, $albumtitle); ?>" title="<?php echo gettext('Reply'); ?>">
		<img src="images/envelope.png" style="border: 0px;" alt="<?php echo gettext('Reply'); ?>" /></a></td>
		<td><?php echo $comment['IP']; ?></td>
		<td align="center">
			<?php
			if($private) {
				echo '<a title="'.gettext("Private message").'"><img src="images/reset.png" style="border: 0px;" alt="'. gettext("Private message").'" /></a>';
			}
			?>
		</td>
		<td align="center"><?php
		if ($inmoderation) {
			echo "<a href=\"?action=notspam&amp;id=" . $id . "\" title=\"".gettext('Approve this message (not SPAM)')."\">";
			echo '<img src="images/lock_open.png" style="border: 0px;" alt="'. gettext("Approve this message (not SPAM").'" /></a>';
		} else {
			echo "<a href=\"?action=spam&amp;id=" . $id . "\" title=\"".gettext('Mark this message as SPAM')."\">";
			echo '<img src="images/lock_2.png" style="border: 0px;" alt="'. gettext("Mark this message as SPAM").'" /></a>';
		}
		?></td>
		<td align="center"><a href="?page=editcomment&amp;id=<?php echo $id; ?>" title="<?php echo gettext('Edit this comment.'); ?>"> 
			<img src="images/pencil.png" style="border: 0px;" alt="<?php echo gettext('Edit'); ?>" /></a></td>
		<td align="center">
			<a href="javascript:if(confirm('<?php echo gettext('Are you sure you want to delete this comment?'); ?>')) { window.location='?action=deletecomments&id=<?php echo $id; ?>'; }"
			title="<?php echo gettext('Delete this comment.'); ?>" > <img
			src="images/fail.png" style="border: 0px;" alt="<?php echo gettext('Delete'); ?>" /></a></td>
	</tr>
	<?php } ?>
	<tr>
		<td colspan="11" class="subhead">
			<label>
				<input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
				<?php echo gettext("Check All"); ?>
			</label>
		</td>
	</tr>


</table>
<br />
<p class="buttons"><button type="submit" title="<?php echo gettext("Delete Selected Comments"); ?>"><img src="images/fail.png" alt="" /><strong><?php echo gettext("Delete Selected Comments"); ?></strong></button></p>

</form>

<?php
}

echo "\n" . '</div>';  //content
printAdminFooter();
echo "\n" . '</div>';  //main


echo "\n</body>";
echo "\n</html>";
?>



