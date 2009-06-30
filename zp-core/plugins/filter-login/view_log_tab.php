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
	$file = SERVERPATH.'/'.DATA_FOLDER . '/'.sanitize($_POST['filename'],3);
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
		case 'download':
			include_once(SERVERPATH.'/'.ZENFOLDER . '/archive.php');
			$subtab = sanitize($_GET['tab'],3);
			$dest = SERVERPATH.'/'.DATA_FOLDER . '/'.$subtab. ".zip";
			$rp = dirname($file);
			$z = new zip_file($dest);
			$z->set_options(array('basedir' => $rp, 'inmemory' => 0, 'recurse' => 0, 'storepaths' => 1));
			$z->add_files(array(basename($file)));
			$z->create_archive();
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="' . $subtab . '.zip"');
			header("Content-Length: " . filesize($dest));
			printLargeFileContents($dest);
			unlink($dest);
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
			$logfile = SERVERPATH . "/" . DATA_FOLDER . '/'.$subtab.'.txt';
			$logtext = explode("\n",file_get_contents($logfile));
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
			}
			?>
			<!-- A log -->
			<div id="theme-editor" class="tabbox">
			
				<form name="delete_log" action="?action=delete&page=logs&tab=<?php echo $subtab; ?>" method="post" style="float: left">
					<input type="hidden" name="action" value="delete">
					<input type="hidden" name="filename" value="<?php echo $subtab; ?>.txt">
					<div class="log_buttons">
						<button type="submit" class="tooltip" id="delete_log" title="<?php printf(gettext("Delete <em>%s</em>"),$logfiletext);?>">
							<img src="../../images/edit-delete.png" style="border: 0px;" /> <?php echo gettext("Delete");?>
						</button>
					</div>
				</form>
				<?php
				if (filesize($logfile) > 0) {
					?>
					<form name="clear_log" action="?action=clear&page=logs&tab=<?php echo $subtab; ?>" method="post" style="float: left">
						<input type="hidden" name="action" value="clear">
						<input type="hidden" name="filename" value="<?php echo $subtab; ?>.txt">
						<div class="log_buttons">
							<button type="submit" class="tooltip" id="clear_log" title="<?php printf(gettext("Reset <em>%s</em>"),$logfiletext);?>">
								<img src="../../images/refresh.png" style="border: 0px;" /> <?php echo gettext("Reset");?>
							</button>
						</div>
					</form>
					
					<form name="download_log" action="?action=download&page=logs&tab=<?php echo $subtab; ?>" method="post" style="float: left">
						<input type="hidden" name="action" value="download">
						<input type="hidden" name="filename" value="<?php echo $subtab; ?>.txt">
						<div class="log_buttons">
							<button type="submit" class="tooltip" id="download_log" title="<?php printf(gettext("Download <em>%s</em> zipfile"),$logfiletext);?>">
								<img src="down.png" style="border: 0px;" /> <?php echo gettext("Download");?>
							</button>
						</div>
					</form>
					<?php
				}
				?>
				<br clear="all" />
				<br />
				<blockquote class="logtext">
					<?php
					if ($subtab == 'security_log') {
						$header = explode("\t", array_shift($logtext));
						?>
						<table id="log_table">
							<tr>
								<?php
								foreach ($sizes as $width) {
									?>
									<th>
										<span class="nowrap"><?php echo array_shift($header); ?></span>
									</th>
									<?php
								}
								?>
							</tr>
							<?php
							foreach ($logtext as $line) {
								?>
								<tr>
								<?php
								$fields = explode("\t", $line);
								foreach ($fields as $key=>$field) {
									?>
									<td>
										<span class="nowrap"><?php echo $field; ?></span>
									</td>
									<?php
								}
								?>
								</tr>
								<?php
							}
							?>
						</table>
						<?php
					} else {
						foreach ($logtext as $line) {
							?>
							<p>
								<span class="nowrap"><?php echo $line; ?></span>
							</p>
							<?php
						}
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