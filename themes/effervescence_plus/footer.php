<?php require_once ('customfunctions.php');  ?>
	<!-- Footer -->
	<div class="footlinks">
		<?php if (function_exists('printLanguageSelector')) { printLanguageSelector(); } ?>
		<?php
			printThemeInfo();
		?>
		<?php printZenphotoLink(); ?>
		<?php	if (function_exists('printUserLogout')) printUserLogout('<br />', '', true); ?>
		<?php	if (function_exists('printContactForm')) printCustomPageURL(gettext('Contact us'), 'contact', '', '<br />');	?>
	</div>
	