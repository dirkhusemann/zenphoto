<?php require_once ('customfunctions.php');  ?>
	<!-- Footer -->
	<div class="footlinks">
		<?php if (function_exists('printLanguageSelector')) { printLanguageSelector(); } ?>
		<?php
			printThemeInfo();
		?>
		<?php echo gettext('Powered by <a href="http://www.zenphoto.org" title="A simpler web photo album"><font face="Arial Narrow" size="4">zen</font><span style="font-variant: small-caps; font-weight: 700"><font face="Arial Black" size="1">photo</font></span></a>'); ?>
		<?php
		if (function_exists('printUserLogout')) {
			printUserLogout('<br />', '', true);
		}
		?>
	</div>
	