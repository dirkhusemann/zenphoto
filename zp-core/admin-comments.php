<?php
/**
 * provides the Comments tab of admin
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once("admin-functions.php");

if (!($_zp_loggedin & (ADMIN_RIGHTS | COMMENT_RIGHTS))) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}
$gallery = new Gallery();
if (isset($_get['page'])) {
	$page = $_GET['page'];
} else {
	$page = '';
}
if (isset($_GET['fulltext'])) $fulltext = true; else $fulltext = false;
if (isset($_GET['viewall'])) $viewall = true; else $viewall = false;

/* handle posts */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	/** un-moderate comment *********************************************************/
	/********************************************************************************/
	if ($action == "moderation") {
		$sql = 'UPDATE ' . prefix('comments') . ' SET `inmoderation`=0 WHERE `id`=' . $_GET['id'] . ';';
		query($sql);
		header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin-comments.php');
		exit();
	} else if ($action == 'deletecomments') {
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
					$sql .= "id='$id' ";
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
	} else if ($action == 'savecomment') {
		if (!isset($_POST['id'])) {
			header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-comments.php");
			exit();
		}
		$id = $_POST['id'];
		$name = escape($_POST['name']);
		$email = escape($_POST['email']);
		$website = escape($_POST['website']);
		$date = escape($_POST['date']);
		$comment = escape($_POST['comment']);

		$sql = "UPDATE ".prefix('comments')." SET `name` = '$name', `email` = '$email', `website` = '$website', `comment` = '$comment' WHERE id = $id";
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

if ($page == "editcomment") { ?>
<h1><?php echo gettext("edit comment"); ?></h1>
<?php
	if (isset($_GET['id'])) $id = $_GET['id'];
	else echo "<h2>". gettext("No comment specified.")." <a href=\"#\">&laquo ".gettext("Back")."</a></h2>";

	$commentarr = query_single_row("SELECT name, website, date, comment, email FROM ".prefix('comments')." WHERE id = $id LIMIT 1");
	extract($commentarr);
	?>

<form action="?action=savecomment" method="post"><input
	type="hidden" name="id" value="<?php echo $id; ?>" />
<table>

	<tr>
		<td width="100"><?php echo gettext("Author:"); ?></td>
		<td><input type="text" size="40" name="name"
			value="<?php echo $name; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("Web Site:"); ?></td>
		<td><input type="text" size="40" name="website"
			value="<?php echo $website; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("E-Mail:"); ?></td>
		<td><input type="text" size="40" name="email"
			value="<?php echo $email; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo gettext("Date/Time:"); ?></td>
		<td><input type="text" size="18" name="date"
			value="<?php echo $date; ?>" /></td>
	</tr>
	<tr>
		<td valign="top"><?php echo gettext("Comment:"); ?></td>
		<td><textarea rows="8" cols="60" name="comment" /><?php echo $comment; ?></textarea></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="<?php echo gettext('save'); ?>" /> <input type="button"
			value="cancel" onClick="window.location = '#';" />

</table>
</form>

<?php
} else {
	// Set up some view option variables.
	
	if (isset($_GET['fulltext'])) {
		define('COMMENTS_PER_PAGE',10);
		$fulltext = true; 
		$fulltexturl = '?fulltext';
	} else {
		define('COMMENTS_PER_PAGE',20);
		$fulltext = false;
		$fulltexturl = '';
	}
	$allcomments = fetchComments(""); 
	
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
	<?php adminPageNav($pagenum,$totalpages,'admin-comments.php'.$fulltexturl); ?>
	</div>
	<?php } ?>

<form name="comments" action="?action=deletecomments"
	method="post"	onSubmit="return confirm('<?php echo gettext("Are you sure you want to delete these comments?"); ?>');">
<input type="hidden" name="subpage" value="<?php echo $pagenum ?>">
<table class="bordered">
	<tr>
		<th>&nbsp;</th>
		<th><?php echo gettext("Album/Image"); ?></th>
		<th><?php echo gettext("Author/Link"); ?></th>
		<th><?php echo gettext("Date/Time"); ?></th>
		<th><?php echo gettext("Comment"); ?> 
		<?php if(!$fulltext) { ?>(
			<a href="?fulltext<?php echo $viewall ? "&viewall":""; ?>"><?php echo gettext("View full text"); ?></a>) <?php 
		} else { 
			?>(<a	href="admin-comments.php"<?php echo $viewall ? "?viewall":""; ?>"><?php echo gettext("View truncated"); ?></a>)<?php 
		} ?>
		</th>
		<th><?php echo gettext("E-Mail"); ?></th>
		<th><?php echo gettext("IP address"); ?></th>
		<th><?php echo gettext("Show"); ?></th>
		<th><?php echo gettext("Spam"); ?></th>
		<th><?php echo gettext("Edit"); ?></th>
		<th><?php echo gettext("Delete"); ?>

	<?php
	foreach ($comments as $comment) {
		$id = $comment['id'];
		$author = $comment['name'];
		$email = $comment['email'];
		if ($comment['type']=='images') {
			$imagedata = query_full_array("SELECT `title`, `filename`, `albumid` FROM ". prefix('images') .
 										" WHERE `id`=" . $comment['ownerid']);
			if ($imagedata) {
				$imgdata = $imagedata[0];
				$image = $imgdata['filename'];
				if ($imgdata['title'] == "") $title = $image; else $title = $imgdata['title'];
				$title = '/ ' . $title;
				$albmdata = query_full_array("SELECT `folder`, `title` FROM ". prefix('albums') .
 											" WHERE `id`=" . $imgdata['albumid']);
				if ($albmdata) {
					$albumdata = $albmdata[0];
					$album = $albumdata['folder'];
					$albumtitle = $albumdata['title'];
					if (empty($albumtitle)) $albumtitle = $album;
				} else {
					$title = gettext('database error');
				}
			} else {
				$title = gettext('database error');
			}
		} else {
			$image = '';
			$title = '';
			$albmdata = query_full_array("SELECT `title`, `folder` FROM ". prefix('albums') .
 										" WHERE `id`=" . $comment['ownerid']);
			if ($albmdata) {
				$albumdata = $albmdata[0];
				$album = $albumdata['folder'];
				$albumtitle = $albumdata['title'];
				if (empty($albumtitle)) $albumtitle = $album;
			} else {
				$title = gettext('database error');
			}
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
			onClick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" /></td>
		<td style="font-size: 7pt;"><?php echo "<a href=\"" . (getOption("mod_rewrite") ? "../$album/$image" : "../index.php?album=".urlencode($album).
											"&image=".urlencode($image)) . "\">$albumtitle $title</a>"; ?></td>
		<td>
		<?php 
		echo $website ? "<a href=\"$website\">$author</a>" : $author; 
		if ($anon) {
			echo ' <a><img src="images/action.png" style="border: 0px;" alt='. gettext("Anonymous").' /></a>';
		}
		?>
		</td>
		<td style="font-size: 7pt;"><?php echo $date; ?></td>
		<td><?php echo ($fulltext) ? $fullcomment : $shortcomment; ?></td>
		<td align="center"><a
			href="mailto:<?php echo $email; ?>?body=<?php echo commentReply($fullcomment, $author, $image, $albumtitle); ?>">
		<img src="images/envelope.png" style="border: 0px;" alt="Reply" /></a></td>
		<td><?php echo $comment['ip']; ?></td>
		<td align="center">
			<?php 
			if($private) {
				echo '<a><img src="images/reset.png" style="border: 0px;" alt='. gettext("Private").' /></a>';
			} 
			?>
		</td>
		<td align="center"><?php
		if ($inmoderation) {
			echo "<a href=\"?action=moderation&id=" . $id . "\">";
			echo '<img src="images/warn.png" style="border: 0px;" alt='. gettext("remove from moderation").' /></a>';
		}
		?></td>
		<td align="center"><a href="?page=editcomment&id=<?php echo $id; ?>"
			title="Edit this comment."> <img src="images/pencil.png"
			style="border: 0px;" alt="Edit" /></a></td>
		<td align="center"><a
			href="javascript: if(confirm('Are you sure you want to delete this comment?')) { window.location='?action=deletecomments&id=<?php echo $id; ?>'; }"
			title="Delete this comment." style="color: #c33;"> <img
			src="images/fail.png" style="border: 0px;" alt="Delete" /></a></td>
	</tr>
	<?php } ?>
	<tr>
		<td colspan="11" class="subhead"><label><input type="checkbox"
			name="allbox" onClick="checkAll(this.form, 'ids[]', this.checked);" />
		<?php echo gettext("Check All"); ?></label></td>
	</tr>


</table>

<input type="submit" value="<?php echo gettext('Delete Selected Comments'); ?>" class="button" />


</form>

<?php 
}

echo "\n" . '</div>';  //content
echo "\n" . '</div>';  //main

printAdminFooter();

echo "\n</body>";
echo "\n</html>";
?>



