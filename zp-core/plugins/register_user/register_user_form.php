<?php
/**
 * Form for registering users
 * 
 * @package plugins 
 */
?>
	<form action="?action=register_user" method="post" AUTOCOMPLETE=OFF>
		<input type="hidden" name="register_user" value="yes" />
		<table class="register_user">
		<tr>
			<td><?php echo gettext("Name:"); ?></td>
			<td><input type="text" id="admin_name" name="admin_name" value="<?php echo $admin_n; ?>" /></td>
		</tr>
		<tr>
			<td><?php echo gettext("User ID:"); ?></td>
			<td><input type="text" id="adminuser" name="adminuser" value="<?php echo $user; ?>" /></td>
		</tr>
		<tr>
			<td><?php echo gettext("Password:"); ?></td>
			<td><input type="password" id="adminpass" name="adminpass"	value="" /></td>
		</tr>
		<tr>
			<td><?php echo gettext("re-enter:"); ?></td>
			<td><input type="password" id="adminpass_2" name="adminpass_2"	value="" /></td>
		</tr>
		<tr>
			<td><?php echo gettext("Email:"); ?></td>
			<td><input type="text" id="admin_email" name="admin_email" value="<?php echo $admin_e; ?>" /></td>
		</tr>
		<?php
		if (getOption('register_user_captcha')) {
			?>
			<tr>
				<td>
					<?php
					$captchaCode = generateCaptcha(&$img);
					$html = "<label for=\"code\"><img src=\"" . $img . "\" alt=\"Code\" align=\"bottom\"/></label>";
					?>
					<input type="hidden" name="code_h" value="<?php echo $captchaCode; ?>"/>
					<?php
					printf(gettext("Enter %s:"),$html);
					?>
				</td>
				<td><input type="text" id="code" name="code" value="" /></td>
			</tr>
			<?php
		}
		?>
		</table>
		<input type="submit" value= <?php echo gettext('Submit') ?> />
	</form>