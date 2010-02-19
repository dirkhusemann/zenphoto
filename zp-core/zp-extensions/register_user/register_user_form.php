<?php
/**
 * Form for registering users
 * 
 * @package plugins 
 */
?>
	<form action="?action=register_user" method="post" autocomplete="off">
		<input type="hidden" name="register_user" value="yes" />
		<table class="register_user">
		<tr>
			<td><?php echo gettext("Name:"); ?></td>
			<td><input type="text" id="admin_name" name="admin_name" value="<?php echo $admin_n; ?>" size="22" /></td>
		</tr>
		<tr>
			<td><?php if (getOption('register_user_email_is_id')) echo gettext("Email:"); else echo gettext("User ID:"); ?></td>
			<td><input type="text" id="adminuser" name="adminuser" value="<?php echo $user; ?>" size="22" /></td>
		</tr>
		<tr>
			<td valign="top"><?php echo gettext("Password:"); ?></td>
			<td width=400 valign="top">
				<p style="line-height: 1em;">
					<input type="password" id="adminpass" name="adminpass"	value="" size="23" />
				</p>
			</td>
		</tr>
		<tr>
			<td valign="top"><?php echo gettext("re-enter:"); ?></td>
			<td>
				<p style="line-height: 1em;">
					<input type="password" id="adminpass_2" name="adminpass_2"	value="" size="23" />
					<?php
					$msg = passwordNote();
					if (!empty($msg)) {
						?>
						<br />
						<?php
						echo  $msg;
					}
					?>
				</p>
			</td>
		</tr>
		<?php
		if (!getOption('register_user_email_is_id')) {
			?>
			<tr>
				<td><?php echo gettext("Email:"); ?></td>
				<td><input type="text" id="admin_email" name="admin_email" value="<?php echo $admin_e; ?>" size="22" /></td>
			</tr>
			<?php
		}
		$html = zp_apply_filter('register_user_form', '');
		if (!empty($html)) echo $html;
		if (getOption('register_user_captcha')) {
			?>
			<tr>
				<td>
					<?php
					$captchaCode = generateCaptcha($img);
					$html = "<label><img src=\"" . $img . "\" alt=\"Code\" align=\"bottom\"/></label>";
					?>
					<input type="hidden" name="code_h" value="<?php echo $captchaCode; ?>" size="22" />
					<?php
					printf(gettext("Enter %s:"),$html);
					?>
				</td>
				<td><input type="text" id="code" name="code" value="" size="22" /></td>
			</tr>
			<?php
		}
		?>
		</table>
		<input type="submit" value="<?php echo gettext('Submit') ?>" />
	</form>