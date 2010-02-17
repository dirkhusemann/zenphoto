<?php
/**
 * provides the Plugins tab of admin
 * @package admin
 */

// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__FILE__).'/admin-functions.php');
require_once(dirname(__FILE__).'/admin-globals.php');

if (!($_zp_loggedin & ADMIN_RIGHTS)) { // prevent nefarious access to this page.
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?from=' . currentRelativeURL(__FILE__));
	exit();
}

if (getOption('zenphoto_release') != ZENPHOTO_RELEASE) {
	header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/setup.php");
	exit();
}

$gallery = new Gallery();
$_GET['page'] = 'plugins';

/* handle posts */
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'saveplugins') {
		$filelist = getPluginFiles('*.php');
		foreach ($filelist as $extension=>$path) {
			$extension = filesystemToInternal($extension);
			$opt = 'zp_plugin_'.$extension;
			if (isset($_POST[$opt]) || !is_null(getOption($opt))) { // don't create any options until plugin is selected at least once
				if (isset($_POST[$opt])) {
					$value = sanitize_numeric($_POST[$opt]);
				} else {
					$value = 0;
				}
				setOption($opt, $value);
			}
		}
		header("Location: " . FULLWEBPATH . "/" . ZENFOLDER . "/admin-plugins.php?saved");
		exit();
	}
}
$saved = isset($_GET['saved']);
printAdminHeader();
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs('plugins');
echo "\n" . '<div id="content">';

/* Page code */

if ($saved) {
	echo '<div class="messagebox" id="fade-message">';
	echo  "<h2>".gettext("Saved")."</h2>";
	echo '</div>';
}

$paths = getPluginFiles('*.php');
$filelist = array_keys($paths);
natcasesort($filelist);

echo "<h1>Plugins</h1>\n";
echo '<p>';
echo gettext("Plugins provide optional functionality for Zenphoto.").' ';
echo gettext("They may be provided as part of the Zenphoto distribution or as offerings from third parties.").' ';
echo sprintf(gettext("Third party plugins are placed in the <code>%s</code> folder and are automatically discovered."),USER_PLUGIN_FOLDER).' ';
echo gettext("If the plugin checkbox is checked, the plugin will be loaded and its functions made available to theme pages. If the checkbox is not checked the plugin is disabled and occupies no resources.");
echo "</p>\n";
echo "<p>".gettext("<strong>Note</strong>: Not all themes are setup with support for all plugins! You may need to add the plugin theme functions of some of them yourself.");
echo "</p>\n";
echo '<form action="?action=saveplugins" method="post">'."\n";
echo '<input type="hidden" name="saveplugins" value="yes" />'."\n";
?>
<p class="buttons">
<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
<button type="reset" value="<?php echo gettext('Reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p><br clear="all" /><br /><br />
<?php
echo "<table class=\"bordered\" width=\"100%\">\n";
?>
<tr>
<th><?php echo gettext("Available Plugins"); ?></th>
<th><?php echo gettext("Description"); ?></th>
</tr>
<?php
foreach ($filelist as $extension) {
	$opt = 'zp_plugin_'.$extension;
	$third_party_plugin = strpos($paths[$extension],ZENFOLDER) === false;
	$pluginStream = file_get_contents($paths[$extension]);
	$parserr = 0;
	$str = isolate('$plugin_description', $pluginStream);
	if (false === $str) {
		$plugin_description = '';
	} else {
		if (false === eval(isolate('$plugin_description', $pluginStream))) {
			$parserr = $parserr | 1;
			$plugin_description = gettext('<strong>Error parsing <em>plugin_description</em> string!</strong> Try removing semicolons from string.');
		}
	}
	$str = isolate('$plugin_author', $pluginStream);
	if (false === $str) {
		$plugin_author = '';
	} else {
		if (false === eval($str)) {
			$parserr = $parserr | 2;
			$plugin_author = gettext('<strong>Error parsing <em>plugin_author</em> string!</strong> Try removing semicolons from string.');
		}
	}
	$str = isolate('$plugin_version', $pluginStream);
	if (false === $str) {
		$plugin_version = '';
	} else {
		if (false === eval($str)) {
			$parserr = $parserr | 4;
			$plugin_version = ' '.gettext('<strong>Error parsing <em>plugin_version</em> string!</strong> Try removing semicolons from string.');
		}
	}
	$str = isolate('$plugin_URL', $pluginStream);
	if (false === $str) {
		$plugin_URL = '';
	} else {
		if (false === eval($str)) {
			$parserr = $parserr | 8;
			$plugin_URL = gettext('<strong>Error parsing <em>plugin_URL</em> string!</strong> Try removing semicolons from string.');
		}
	}
	$str = isolate('$plugin_disable', $pluginStream);
	if (false === $str) {
		$plugin_disable = false;
	} else {
		if (false === eval($str)) {
			$parserr = $parserr | 8;
			$plugin_URL = gettext('<strong>Error parsing <em>plugin_disable</em> string!</strong> Try removing semicolons from string.');
		} else {
			if ($plugin_disable) {
				setOption($opt, 0);
			}
		}
	}
	$loadtype = 1;
	$str = isolate('$plugin_is_filter', $pluginStream);
	if (false !== $str) {
		if (false !== eval($str)) {
			if ($plugin_is_filter) {
				$loadtype = $plugin_is_filter;
			}
		}
	}
	$optionlink = isolate('$option_interface', $pluginStream);
	if (!empty($optionlink)) {
		$optionlink = FULLWEBPATH.'/'.ZENFOLDER.'/admin-options.php?page=options&amp;tab=plugin&amp;_show-'.$extension;
	}
	?>
	<tr>
		<td width="30%">
		<label>
			<input type="checkbox" name="<?php echo $opt; ?>" value="<?php echo $loadtype; ?>"
				<?php
				if ($parserr || $plugin_disable) {
					$optionlink = false;
					echo ' disabled="disabled"';
				} else {
					if (getOption($opt)) {
						echo ' checked="checked"';
					} else {
						$optionlink = false;
					}
				} ?> />
			<span<?php if (!$third_party_plugin) echo ' style="font-weight:bold"' ?>><?php echo $extension; ?></span>
		</label>
		<?php
		if (!empty($plugin_version)) {
			echo ' v'.$plugin_version;
		}
		if ($plugin_disable) {
			echo '<p><strong>'.sprintf(gettext('This plugin is disabled: %s.'),$plugin_disable).'</strong></p>';
		}
		?>
		</td>
		<td>
		<?php
		echo $plugin_description;
		if (!empty($plugin_URL)) {
			?>
			<br />
			<?php
			if ($parserr & 8) {
				echo $plugin_URL;
			} else {
				?>
				<a href="<?php echo $plugin_URL; ?>"><strong><?php echo gettext("Usage information"); ?></strong></a>
				<?php
			}
		}
		if (!empty($plugin_author)) {
			?>
			<br />
			<?php
			if (!($parserr & 2)) {
				?>
				<strong><?php echo gettext("Author"); ?></strong>
				<?php
			}
			echo $plugin_author;
		}
		if ($optionlink) {
			?>
			<br />
			<a href="<?php echo $optionlink; ?>" ><?php echo gettext("Change plugin options"); ?></a>
			<?php
		}
		?>
		</td>
	</tr>
	<?php
	}
?>
</table>
<br />
<p class="buttons">
<button type="submit" value="<?php echo gettext('save') ?>" title="<?php echo gettext("Save"); ?>"><img src="images/pass.png" alt="" /><strong><?php echo gettext("Save"); ?></strong></button>
<button type="reset" value="<?php echo gettext('Reset') ?>" title="<?php echo gettext("Reset"); ?>"><img src="images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
</p><br />
<?php
echo "</form>\n";

echo "\n" . '</div>';  //content
printAdminFooter();
echo "\n" . '</div>';  //main
echo "\n</body>";
echo "\n</html>";
?>



