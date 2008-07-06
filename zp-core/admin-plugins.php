<?php
/**
 * provides the Plugins tab of admin
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once("admin-functions.php");

function isolate($target, $str) {
	$i = strpos($str, $target);
	if ($i===false) return '';
	$str = substr($str, $i);
	$j = strpos($str, ";\n");
	$str = substr($str, 0, $j+1);
	return $str;
}

if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin.php");
	exit();
}
$gallery = new Gallery();
$_GET['page'] = 'plugins';

/* handle posts */
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'saveplugins') {
		$curdir = getcwd();
		chdir(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER);
		$filelist = safe_glob('*'.'php');
		chdir($curdir);
		foreach ($filelist as $extension) {
			$opt = 'zp_plugin_'.substr($extension, 0, strlen($extension)-4);
			setBoolOption($opt, $_POST[$opt]);
		}
	}
}
printAdminHeader();
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';

/* Page code */

if (isset($_GET['saved'])) {
	echo '<div class="messagebox" id="fade-message">';
	echo  "<h2>".gettext("Saved")."</h2>";
	echo '</div>';
}

$curdir = getcwd();
chdir(SERVERPATH . "/" . ZENFOLDER . PLUGIN_FOLDER);
$filelist = safe_glob('*'.'php');
natcasesort($filelist);

echo "<h1>Plugins</h1>\n";
echo '<p>';
echo gettext("Plugins provide optional functionality for Zenphoto.").' ';
echo gettext("They may be provided as part of the Zenphoto distribution or as offereings from third parties.").' ';
echo gettext("Plugins are placed in the <code>zp-core/plugins</code> folder and are automatically discovered.").' ';
echo gettext("If the plugin checkbox is checked, the plugin will be loaded and its functions made available to theme pages. If the checkbox is not checked the plugin is disabled and occupies no resources.");
echo "</p>\n";
echo '<form action="?page=plugins&action=saveplugins" method="post">'."\n";
echo '<input type="hidden" name="saveplugins" value="yes" />'."\n";
echo "<table class=\"bordered\" width=\"100%\">\n";
foreach ($filelist as $extension) {
	$ext = substr($extension, 0, strlen($extension)-4);
	$opt = 'zp_plugin_'.$ext;
	
	echo "<tr>";
	echo '<td width="30%">';
	echo '<input type="checkbox" size="40" name="'.$opt.'" value="1"';
	echo checked('1', getOption($opt));
	echo ' /> ';
	echo '<strong>'.$ext.'</strong>';
	
	$pluginStream = file_get_contents($extension);
	$plugin_description = '';
	eval(isolate('$plugin_description', $pluginStream));
	$plugin_author = '';
	eval(isolate('$plugin_author', $pluginStream));
	$plugin_version = '';
	eval(isolate('$plugin_version', $pluginStream));
	$plugin_URL = '';
	eval(isolate('$plugin_URL', $pluginStream));
	
	if (!empty($plugin_version)) {
		echo ' v'.$plugin_version;
	}
	echo '</td>';
	echo '<td>';
	echo $plugin_description;
	if (!empty($plugin_URL)) {
		echo '<br /><a href="'.$plugin_URL.'"><strong>'.gettext("Usage information").'</strong></a>';
	}
	if (!empty($plugin_author)) {
		echo '<br /><strong>'.gettext("Author").'</strong>: '.$plugin_author.'';
	}
	echo '</td>';
	echo "</tr>\n";
}
echo "</table>\n";
echo '<input type="submit" value='. gettext('save').' />' . "\n";
echo "</form>\n";
chdir($curdir);

echo "\n" . '</div>';  //content
echo "\n" . '</div>';  //main

printAdminFooter();
echo "\n</body>";
echo "\n</html>";
?>



