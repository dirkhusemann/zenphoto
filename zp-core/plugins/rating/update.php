<?php
/**
 * rating plugin updater - Updates the rating in the database
 * @author Stephen Billard (sbillard)
 * @version 2.0.0
 * @package plugins
 */

$zp = dirname(dirname(dirname(__FILE__)));
define ('OFFSET_PATH', 4);
require_once($zp.'/functions-basic.php');

if (isset($_GET['clear_rating'])) {
	require_once($zp.'/admin-functions.php'); // you have to be loged in to do this
	if (!(zp_loggedin(ADMIN_RIGHTS | EDIT_RIGHTS))) { // prevent nefarious access to this page.
		$const_webpath = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
		header("Location: " . PROTOCOL."://" . $_SERVER['HTTP_HOST'] . $const_webpath . ZENFOLDER . "/admin.php");
		exit();
	}
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<title><?php echo gettext("zenphoto administration"); ?></title>
	<link rel="stylesheet" href="../../admin.css" type="text/css" />
	</head>
	<body>
		<?php
		query('UPDATE '.prefix('images').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
		query('UPDATE '.prefix('albums').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
		query('UPDATE '.prefix('zenpage_news').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
		query('UPDATE '.prefix('zenpage_pages').' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
		?>
		<div style="margin-top: 20px; text-align: left;">
			<h2>
				<img src='<?php echo WEBPATH.'/'.ZENFOLDER; ?>/images/pass.png' style='position: relative; top: 3px; margin-right: 5px' />
				<?php echo gettext("Ratings have been reset!"); ?>
			</h2>
			<div class='buttons'>
				<a href='#' onclick='self.parent.tb_remove();'><?php echo gettext('Close'); ?>
				</a>
			</div>
		</div>
	</body>
	</html>
	<?php
	exit;
} else {
	require($zp.'/template-functions.php');
	require_once('functions-rating.php');
	$id = sanitize_numeric($_POST['id']);
	$rating = max(0, min(5, round(sanitize_numeric($_POST['star_rating-value'])/2)));
	$dbtable = sanitize($_POST['table'],3);
	$ip = sanitize($_SERVER['REMOTE_ADDR'], 0);
	$oldrating = checkForIP($ip,$id,$dbtable);
	if(!$oldrating || getOption('rating_recast')) {
		if ($rating) {
			$_rating_current_IPlist[$ip] = $rating;
		} else {
			if (isset($_rating_current_IPlist[$ip])) {
				unset($_rating_current_IPlist[$ip]); // retract vote
			}
		}
		$insertip = serialize($_rating_current_IPlist);
		if ($oldrating) {
			$voting = 0;
			$valuechange = $rating-$oldrating;
			if ($valuechange>=0) {
				$valuechange = '+'.$valuechange;
			}
		} else {
			$voting = 1;
			$valuechange = '+'.$rating;
		}
		$sql = "UPDATE ".$dbtable.'SET total_votes=total_votes+'.$voting.", total_value=total_value".$valuechange.", rating=total_value/total_votes, used_ips='".$insertip."' WHERE id='".$id."'";
		$rslt = query($sql,true);
		//if (!$rslt) debugLog("MySQL Query"." ( $sql ) ".gettext("Failed. Error:").' '.mysql_error());
	}
}
?>
