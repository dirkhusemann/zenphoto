<?php
/**
 * dynamic-locale -- plugin to allow the site viewer to select a localization.
 * This applies only to the theme pages--not Admin. Admin continues to use the
 * language option for its language.
 *
 * Only the zenphoto and theme gettext() string are localized by this facility.
 *
 * If you want to support image descriptions, etc. in multiple languages you will
 * have to enable the multi-lingual option found next to the language selector on
 * the admin gallery configuration page. Then you will have to provide appropriate
 * alternate translations for the fields you use. While there will be a place for
 * strings for all zenphoto supported languages you need supply only those you choose.
 * The others language strings will default to your local language.
 *
 * Uses cookies to store the individual selection. Sets the 'locale' option
 * to the selected language (non-persistent.)
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */
$plugin_description = gettext("Enable <strong>dynamic-locale</strong> to allow viewers of your site to select the language translation of their choice.");
$plugin_author = "Stephen Billard (sbillard)";
$plugin_version = '1.2.9'; 
$plugin_URL = "http://www.zenphoto.org/documentation/plugins/_".PLUGIN_FOLDER."---dynamic-locale.php.html";

/**
 * prints a form for selecting a locale
 * The POST handling is by getUserLocale() called in functions.php
 *
 */
function printLanguageSelector($class='') {
	global $_zp_languages;
	if (isset($_POST['dynamic-locale'])) {
		$locale = sanitize($_POST['dynamic-locale'], 0);
		if (getOption('locale') != $locale) {
			?>
			<div class="errorbox">
				<h2>
					<?php printf(gettext('<em>%s</em> is not available.'),$_zp_languages[$locale]); ?>
					<?php printf(gettext('The locale %s is not supported on your server.'), $locale); ?>
					<br />
					<?php echo gettext('See the troubleshooting guide on zenphoto.org for details.'); ?>
				</h2>
			</div>
			<?php
		}
	}
	if (!empty($class)) { $class = " class='$class'"; }
	?>
	<div<?php echo $class; ?>>
		<form action="#" method="post">
			<input type="hidden" name="oldlocale" value="<?php echo getOption('locale'); ?>" />
			<select id="dynamic-locale" name="dynamic-locale" onchange="this.form.submit()">
			<?php generateLanguageOptionList(false); ?>
			</select>
		</form>
	</div>
	<?php
}

?>