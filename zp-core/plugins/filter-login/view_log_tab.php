<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
 * @version 1.0.0
 * @package plugins
 */
define ('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))).'/admin-functions.php');
if (!zp_loggedin(ADMIN_RIGHTS)) {
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__) );
	exit();
}
if (isset($_GET['action'])) {
	$action = sanitize($_GET['action'],3);
	$file = SERVERPATH.'/'.DATA_FOLDER . '/'.sanitize($_POST['filename'],3).'.txt';
	switch ($action) {
		case 'clear':
			$f = fopen($file, 'w');
			fclose($f);
			chmod($file, 0600);
			break;
		case 'delete':
			@unlink($file);
			unset($_GET['tab']); // it is gone, after all
			break;
	}
}
// Print our header
$page = 'logs';
printAdminHeader(WEBPATH.'/'.ZENFOLDER.'/');
?>
<link rel="stylesheet" href="log.css" type="text/css" />
<?php
echo "\n</head>";
?>

<body>

<?php	printLogoAndLinks(); ?>
<div id="main">
	<?php
	printTabs($page);
	?>
	<div id="content">
	<?php
	?>
		<h1><?php echo gettext("View logs:");?></h1>
		
		<?php
		$filelist = safe_glob(SERVERPATH . "/" . DATA_FOLDER . '/*.txt');
		$subtabs = array();
		if (count($filelist)>0) {
			foreach ($filelist as $logfile) {
				$logfiletext = str_replace('_', ' ',$log = substr(basename($logfile), 0, -4));
				$logfiletext = strtoupper(substr($logfiletext, 0, 1)).substr($logfiletext, 1);
				$subtabs = array_merge($subtabs, array($logfiletext => PLUGIN_FOLDER.'/filter-login/view_log_tab.php?page=logs&amp;tab='.$log));
			}
			$zenphoto_tabs['logs']['subtabs'] = $subtabs;
			
			$subtab = printSubtabs('logs', 'security_log');
			$logfiletext = str_replace('_', ' ',$subtab);
			$logfiletext = strtoupper(substr($logfiletext, 0, 1)).substr($logfiletext, 1);
			$logtext = explode("\n",file_get_contents(SERVERPATH . "/" . DATA_FOLDER . '/'.$subtab.'.txt'));
			if ($subtab == 'security_log') {
				// pretty up the tabs
				$fields = array();
				$sizes = array(0,0,0,0,0,0,0);
				foreach ($logtext as $lineno=>$line) {
					$fields[$lineno] = explode("\t", $line);
					foreach ($fields[$lineno] as $key=>$field) {
						if ($sizes[$key] < strlen($field)) $sizes[$key] = strlen($field);
					}
				}
				$logtext = array();
				foreach ($fields as $fieldset) {
					$line = '';
					foreach ($fieldset as $key=>$field) {
						if (strlen($field) < $sizes[$key]+2) $field .= str_repeat('&nbsp;',$sizes[$key]+2-strlen($field));
						$line .= $field;
					}
					$logtext[] = $line;
				}
			}
			?>
			<!-- A log -->
			<div id="theme-editor" class="tabbox">
				<form action="?action=delete&page=logs&tab=<?php echo $subtab; ?>" method="post">
					<input type="hidden" name="filename" value="<?php echo $subtab; ?>" />
					<p class="buttons">
						<button type="submit" value="delete" title="<?php printf(gettext("Delete %s"),$logfiletext); ?>"><img src="../../images/fail.png" alt="" /><strong><?php echo gettext("Delete"); ?></strong></button>
					</p>
				</form>
				<br clear="all" />
				<br />
				<blockquote class="logtext">
					<?php
					foreach ($logtext as $line) {
						?>
						<p>
							<span class="nowrap"><?php echo $line; ?></span>
						</p>
						<?php
					}
					?>
				</blockquote>
			</div>
			<?php
		} else {
			?>
			<h2><?php echo gettext("There are no logs to view.");?></h2>
			<?php
		}
		?>
	</div>
</div>
<?php printAdminFooter(); ?>
<?php // to fool the validator
echo "\n</html>";

?>