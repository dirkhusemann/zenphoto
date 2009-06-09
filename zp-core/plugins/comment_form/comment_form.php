	<h2><?php echo gettext('Leave a Reply');?></h2>
	<?php printCommentErrors(); ?>
	<form id="commentform" action="#" method="post">
		<input type="hidden" name="comment" value="1" />
		<input type="hidden" name="remember" value="1" />
		
		<input type="text" name="name" id="name" class="textinput" size="22" value="<?php echo $stored['name'];?>" tabindex="1" />
		<label for="name"><small><?php echo gettext('Name');?> (<input type="checkbox" name="anon" value="1"<?php if ($stored['anon']) echo " CHECKED"; ?> />	<?php echo gettext("display as <em>anonymous</em>"); ?>)</small></label>
		<br />
		<input type="text" name="email" id="email" class="textinput" size="22" value="<?php echo $stored['email'];?>" tabindex="2" />
		<label for="email"><small><?php echo gettext('Email');?></small></label>
		<br />
		<input type="text" name="website" id="website" class="textinput" size="22" value="<?php echo $stored['website'];?>" tabindex="3" />
		<label for="website"><small><?php echo gettext('Website');?></small></label>
		<br />
		<?php
		if (getOption('comment_form_addresses')) {
			?>
			<input type="text" name="0-comment_form_street" id="comment_form_street" class="textinput" size="22" value="<?php echo $address['street']; ?>">
			<label for="comment_form_street"><small><?php echo gettext('street'); ?></small></label>
			<br />
			<input type="text" name="0-comment_form_city" id="comment_form_city" class="textinput" size="22" value="<?php echo $address['city']; ?>">
			<label for="comment_form_city"><small><?php echo gettext('city'); ?></small></label>
			<br />
			<input type="text" name="0-comment_form_state" id="comment_form_state" class="textinput" size="22" value="<?php echo $address['state']; ?>">
			<label for="comment_form_state"><small><?php echo gettext('state'); ?></small></label>
			<br />
			<input type="text" name="0-comment_form_country" id="comment_form_country" class="textinput" size="22" value="<?php echo $address['country']; ?>">
			<label for="comment_form_country"><small><?php echo gettext('country'); ?></small></label>
			<br />
			<input type="text" name="0-comment_form_postal" id="comment_form_postal" class="textinput" size="22" value="<?php echo $address['postal']; ?>">
			<label for="comment_form_postal"><small><?php echo gettext('postal code'); ?></small></label>
			<br />
			<?php
			}
			printCaptcha('', '', ' <small>'.gettext("Enter Captcha").'</small><br/>', 8);
			?>
		<input type="checkbox" name="private" value="1"<?php if ($stored['private']) echo " CHECKED"; ?> /> <?php echo gettext("Private (don't publish)"); ?>
		<textarea name="comment" id="comment" rows="5" cols="100%" tabindex="4"><?php echo $stored['comment']; ?></textarea>
		<input type="submit" value="<?php echo gettext('Submit');?>" class="pushbutton" />
	</form>
