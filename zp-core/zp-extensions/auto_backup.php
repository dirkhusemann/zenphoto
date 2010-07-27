<?php
/**
 * Automatically backup the Zenphoto database on a regular period
 * NOTE: The WEB site must be visited for this plugin to be able to check if it is time
 * to run. Inacative sites may not get backed up as frequently as the interval specifies.
 * Of course, if the site is inactive, there probably is little need to do the backup
 * in the first place.
 * 
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

if (getOption('last_backup_run')+getOption('backup_interval')*5184000 < time()) {	// register if it is time for a backup
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

/**
 * Handles the periodic start of the backup/restore utility to backup the database
 * @param string $side set to either "front-end" or "back-end", but we do not care.
 */
function auto_backup_timer_handler($side) {
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
								array('backup'=>1, 'compress'=>sprintf('%u',getOption('backup_compression')),'XSRFTag'=>'backup')
							);
	return $side;
}

?>