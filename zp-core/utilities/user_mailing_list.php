<?php
/**
 * User mailing list
 * 
 * A tool to send e-mails to all registered users who have provided an e-mail address.
 * 
 * @package admin
 */

define('OFFSET_PATH', 3);
chdir(dirname(dirname(__FILE__)));

require_once(dirname(dirname(__FILE__)).'/admin-functions.php');
require_once(dirname(dirname(__FILE__)).'/admin-globals.php');

if(getOption('zp_plugin_zenphoto_sendmail')) {
	require_once(dirname(dirname(__FILE__)).'/'.PLUGIN_FOLDER.'/zenphoto_sendmail.php');
} else if(getOption('zp_plugin_PHPMailer')) {
	require_once(dirname(dirname(__FILE__)).'/'.PLUGIN_FOLDER.'/PHPMailer.php');
}
$button_text = gettext('User mailing list');
$button_hint = gettext('A tool to send e-mails to all registered users who have provided an e-mail address.');
$button_icon = 'images/icon_mail.gif';

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}
if (!($_zp_loggedin & (ADMIN_RIGHTS | OVERVIEW_RIGHTS))) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

$gallery = new Gallery();
$webpath = WEBPATH.'/'.ZENFOLDER.'/';
$admins = $_zp_authority->getAdministrators();
printAdminHeader();
?>
</head>
<body>
<?php printLogoAndLinks(); ?>
<div id="main">
<?php printTabs('home'); ?>
<div id="content">
<h1><?php echo gettext('User mailing list'); ?></h1>
<p><?php echo gettext("A tool to send e-mails to all registered users who have provided an e-mail address. It will send the e-mails as <em>blind copies</em>."); ?></p>
<?php if(!getOption('zp_plugin_PHPMailer') || !getOption('zp_plugin_zenphoto_sendmail')) { ?>
<p class="notebox">
<?php echo gettext("<strong>Note:</strong> Either the <em>PHPMailer</em> plugin or the <em>zenphoto_sendmail</em> plugin must be activated and properly configured."); ?>
</p>
<?php } 
if(isset($_GET['sendmail'])) {
	//form handling stuff to add...
	$subject = NULL;
	$message = NULL;
	if(isset($_POST['subject'])) {
		$subject = sanitize($_POST['subject']);
	}
	if(isset($_POST['message'])) {
		$message = sanitize($_POST['message']);
	}
	$cc_addresses = NULL;
	$count = '';
	$admincount = count($admins);
	foreach($admins as $admin) {
		$count++;
		if (isset($_POST["admin_".$admin['id']])) {
			$cc_addresses .= $admin['email'].", ";
		}
	}
	$cc_addresses = substr($cc_addresses,0,-2);
	zp_mail($subject, $message, null, $cc_addresses);	
	?>
	<h3><strong><?php echo gettext('Subject:'); ?> </strong><?php echo $subject; ?></h3>
	<p><strong><?php echo gettext('To:'); ?> </strong><?php echo $cc_addresses; ?></p>
	<strong><?php echo gettext('Message:'); ?> </strong><?php echo $message; ?>
	<p class="buttons"><a href="user_mailing_list.php" title="<?php echo gettext('Send another mail'); ?>"><?php echo gettext('Send another mail'); ?></a></p>
<?php
} else { 
?>
<h2><?php echo gettext('Please enter the message you want to sent.'); ?></h2>
<form id="massmail" action="?sendmail" method="post" accept-charset="UTF-8">
	<table>
		<tr>
				<td valign="top">
				<labelfor="subject"><?php echo gettext('Subject:'); ?></label><br />
				<input type="text" id="subject" name="subject" value="" size="70" /><br /><br />
				<label for="message"><?php echo gettext('Message:'); ?></label><br />
				<textarea id="message" name="message" value="" cols="68" rows="10"></textarea>
				</td>
				<td valign="top" align="left">
				<?php echo gettext('Select users:'); ?>
				<ul class="customchecklist">
				<?php 
				foreach($admins as $admin) {
					if(!empty($admin['email'])) {
						echo "<li><label for='admin_".$admin['id']."'><input name='admin_".$admin['id']."' id='admin_".$admin['id']."' type='checkbox' value='".$admin['email']."' checked='checked' />".$admin['user']." (".$admin['name']." - ".$admin['email'].")</label></li>\n";
					}
				}
				?>
				</ul>
				<br />
				</td>
		</tr>
</table>
<p class="buttons">
		<button class="submitbutton" type="submit"
		title="<?php echo gettext("Send mail"); ?>"><img
		src="../images/pass.png" alt="" /><strong><?php echo gettext("Send mail"); ?></strong></button>
</p>
<p class="buttons">
<button class="submitbutton" type="reset"
		title="<?php echo gettext("Reset"); ?>"><img src="../images/reset.png"
		alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p>
<br style="clear: both" />
</form>
<?php } ?>
</div><!-- content -->
</div><!-- main -->
<?php printAdminFooter(); ?>
</body>
</html>