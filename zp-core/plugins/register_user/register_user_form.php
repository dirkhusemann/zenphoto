	<form action="?action=register_user" method="post" AUTOCOMPLETE=OFF>
		<input type="hidden" name="register_user" value="yes" />
		<table class="register_user">
		<tr>
			<td>Name:</td>
			<td><input type="text" id="admin_name" name="admin_name" value="<?php echo $admin_n; ?>" /></td>
		</tr>
		<tr>
			<td>User ID:</td>
			<td><input type="text" id="adminuser" name="adminuser" value="<?php echo $user; ?>" /></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input type="password" id="adminpass" name="adminpass"	value="" /></td>
		</tr>
		<tr>
			<td>re-enter:</td>
			<td><input type="password" id="adminpass_2" name="adminpass_2"	value="" /></td>
		</tr>
		<tr>
			<td>Email:</td>
			<td><input type="text" id="admin_email" name="admin_email" value="<?php echo $admin_e; ?>" /></td>
		</tr>
		</table>
		<input type="submit" value= <?php echo gettext('Submit') ?> />
	</form>