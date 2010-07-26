<?php
/**
 * Automatically backup the Zenphoto database on a regular period
 * Backups are run under the master administrator authority.
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_is_filter = 2;
$plugin_description = gettext("Periodically backup the Zenphoto database.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/".PLUGIN_FOLDER."--auto_backup.php.html";
$plugin_version = '1.3.2';

$option_interface = new auto_backup();

require_once(dirname(dirname(__FILE__)).'/admin-functions.php');

if (getOption('last_backup_run')+getOption('backup_interval')*5184000 < time()) {
	zp_register_filter('output_started','auto_backup_timer_handler');
}

/**
 * Option handler class
 *
 */
class auto_backup {
	/**
	 * class instantiation function
	 *
	 * @return security_logger
	 */
	function auto_backup() {
		setOptionDefault('backup_interval', 7);
		setOptionDefault('backups_to_keep', 5);
	}


	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return  array(	gettext('Run interval') => array('key' => 'backup_interval', 'type' => OPTION_TYPE_TEXTBOX,
												'desc' => gettext('The run interval (in days) for auto backup.')),
										gettext('Backups to keep') => array('key' => 'backups_to_keep', 'type' => OPTION_TYPE_TEXTBOX,
												'desc' => gettext('Auto backup will keep only this many backup sets. Older sets will be removed.'))
		);
	}

	function handleOption($option, $currentValue) {
	}

}

function auto_backup_timer_handler() {
	setOption('last_backup_run',time());
	$curdir = getcwd();
	chdir(SERVERPATH . "/" . BACKUPFOLDER);
	$filelist = safe_glob('*'.'.zdb');
	$list = array();
	foreach($filelist as $file) {
		$list[$file] = filemtime($file);
	}
	chdir($curdir);
	asort($list);
	$list = array_flip($list);
	$keep = getOption('backups_to_keep');
	while (count($list) >= $keep) {
		$file = array_shift($list);
		unlink(SERVERPATH . "/" . BACKUPFOLDER.'/'.$file);
	}

	cron_starter(	SERVERPATH.'/'.ZENFOLDER.'/'.UTILITIES_FOLDER.'/backup_restore.php',
								array('backup'=>1, 'backup_compression'=>sprintf('%u',getOption('backup_compression')),'XSRFTag'=>'backup')
							);
}

?>