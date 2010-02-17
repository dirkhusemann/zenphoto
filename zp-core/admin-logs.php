<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');
if (!zp_loggedin(ADMIN_RIGHTS)) {
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
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
printAdminHeader();
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
		$default = '';
		if (count($filelist)>0) {
			foreach ($filelist as $logfile) {
				$log = substr(basename($logfile), 0, -4);
				$logfiletext = str_replace('_', ' ',$log);
				$logfiletext = strtoupper(substr($logfiletext, 0, 1)).substr($logfiletext, 1);
				$subtabs = array_merge($subtabs, array($logfiletext => 'admin-logs.php?page=logs&amp;tab='.$log));
				if (filesize($logfile) > 0 && empty($default)) $default = $log;
			}
			$zenphoto_tabs['logs']['subtabs'] = $subtabs;
			$subtab = printSubtabs('logs', $default);
			$logfiletext = str_replace('_', ' ',$subtab);
			$logfiletext = strtoupper(substr($logfiletext, 0, 1)).substr($logfiletext, 1);
			$logfile = SERVERPATH . "/" . DATA_FOLDER . '/'.$subtab.'.txt';
			if (filesize($logfile) > 0) {
				$logtext = explode("\n",file_get_contents($logfile));
			} else {
				$logtext = array();
			}
			?>
			<!-- A log -->
			<div id="theme-editor" class="tabbox">
			
				<form name="delete_log" action="?action=delete&amp;page=logs&amp;tab=<?php echo $subtab; ?>" method="post" style="float: left">
					<input type="hidden" name="action" value="delete" />
					<input type="hidden" name="filename" value="<?php echo $subtab; ?>.txt" />
					<div class="buttons">
						<button type="submit" class="tooltip" id="delete_log" title="<?php printf(gettext("Delete %s"),$logfiletext);?>">
							<img src="images/edit-delete.png" style="border: 0px;" alt="delete" /> <?php echo gettext("Delete");?>
						</button>
					</div>
				</form>
				<?php
				if (!empty($logtext)) {
					?>
					<form name="clear_log" action="?action=clear&amp;page=logs&amp;tab=<?php echo $subtab; ?>" method="post" style="float: left">
						<input type="hidden" name="action" value="clear" />
						<input type="hidden" name="filename" value="<?php echo $subtab; ?>.txt" />
						<div class="buttons">
							<button type="submit" class="tooltip" id="clear_log" title="<?php printf(gettext("Reset %s"),$logfiletext);?>">
								<img src="images/refresh.png" style="border: 0px;" alt="clear" /> <?php echo gettext("Reset");?>
							</button>
						</div>
					</form>
					
					<form name="download_log" action="?action=download&amp;page=logs&amp;tab=<?php echo $subtab; ?>" method="post" style="float: left">
						<input type="hidden" name="action" value="download" />
						<input type="hidden" name="filename" value="<?php echo $subtab; ?>.txt" />
						<div class="buttons">
							<button type="submit" class="tooltip" id="download_log" title="<?php printf(gettext("Download %s zipfile"),$logfiletext);?>">
								<img src="images/down.png" style="border: 0px;" alt="download" /> <?php echo gettext("Download");?>
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
					if (!empty($logtext)) {
						$header = array_shift($logtext);
						$fields = explode("\t", $header);
						if (count($fields) > 1) { // there is a header row, display in a table
							?>
							<table id="log_table">
								<?php
								if (!empty($header)) {
									?>
									<tr>
										<?php
											foreach ($fields as $field) {
												?>
												<th>
													<span class="nowrap"><?php echo $field; ?></span>
												</th>
												<?php
											}
										?>
									</tr>
									<?php
								}
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
							array_unshift($logtext, $header);
							foreach ($logtext as $line) {
								?>
								<p>
									<span class="nowrap"><?php echo strip_tags($line); ?></span>
								</p>
								<?php
							}
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
echo "\n</body>";
echo "\n</html>";

?>