	<p class="mainbutton" id="addcommentbutton"><a href="#addcomment" class="btn"><img src="<?php echo $_zp_themeroot ?>/images/btn_add_a_comment.gif" alt="" width="116" height="21" /></a></p>
	<!-- BEGIN #addcomment -->
	<div id="addcomment">
		<script type="text/javascript">
			$(function() {
  			window.onload = initCommentState;
			});
		</script>
		<h2><?php echo gettext("Add a comment") ?></h2>
		<form method="post" action="#" id="comments-form">
			<input type="hidden" name="comment" value="1" />
			<input type="hidden" name="remember" value="1" />
			<table cellspacing="0">
				<tr valign="top" align="left" id="row-name">
					<th><?php echo gettext('Name:'); ?></th>
					<td>
						<?php 
						if ($disabled['name']) {
							?>
							<input tabindex="1" id="name" name="name" class="text" type="hidden" value="<?php echo $stored['name'];?>" />
							<?php echo $stored['name'];?>
							<?php
						} else {
							?>
							<input tabindex="1" id="name" name="name" class="text" value="<?php echo $stored['name'];?>" />
							<?php
						}
						?>
						<label>
						(<input type="checkbox" name="anon" value="1"<?php if ($stored['anon']) echo ' checked="checked"'; ; ?> /> <?php echo gettext('<em>anonymous</em>'); ?>)
						</label>
					</td>
				</tr>
				<tr valign="top" align="left" id="row-email">
					<th><?php echo gettext('Email:'); ?></th>
					<td>
						<?php 
						if ($disabled['email']) {
							?>
							<input tabindex="2" id="email" name="email" class="text" type="hidden" value="<?php echo $stored['email'];?>" />
							<?php echo $stored['email'];?>
							<?php
						} else {
							?>
							<input tabindex="2" id="email" name="email" class="text" value="<?php echo $stored['email'];?>" />
							<?php
						}
						?>
						<em><?php echo gettext("(not displayed)"); ?></em>
					</td>
				</tr>
				<tr valign="top" align="left">
					<th><?php echo gettext('URL:'); ?></th>
					<td>
						<?php 
						if ($disabled['website']) {
							?>
							<input tabindex="3" name="website" id="website" class="text" type="hidden" value="<?php echo $stored['website'];?>" />
							<?php echo $stored['website'];?>
							<?php
						} else {
							?>
							<input tabindex="3" name="website" id="website" class="text" value="<?php echo $stored['website'];?>" />
							<?php
						}
						?>
					</td>
				</tr>
				<?php printCaptcha("<tr valign=\"top\" align=\"left\"><th><label>" .gettext('Enter Captcha').' ', ":</label></th><td>", "</td></tr>\n", 8); ?>
				<tr valign="top" align="left">
					<th><?php echo gettext('Private comment:'); ?></th>
					<td>
						<label>
						<input type="checkbox" name="private" value="1"<?php if ($stored['private']) echo ' checked="checked"'; ; ?> /> <?php echo gettext("(don't publish)"); ?>
						</label>
					</td>
				</tr>
			<?php
			if (getOption('comment_form_addresses')) {
				?>
				<tr>
					<th><?php echo gettext('street:'); ?></th>
					<td>
						<?php 
						if ($disabled['street']) {
							?>
							<input name="0-comment_form_street" id="comment_form_street" class="text" type="hidden" size="22" value="<?php echo $address['street']; ?>" />
							<?php echo $address['street']; ?>
							<?php
						} else {
							?>
							<input name="0-comment_form_street" id="comment_form_street" class="text" size="22" value="<?php echo $address['street']; ?>" />
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php echo gettext('city:'); ?></th>
					<td>
						<?php 
						if ($disabled['city']) {
							?>
							<input name="0-comment_form_city" id="comment_form_city" class="text" type="hidden" size="22" value="<?php echo $address['city']; ?>" />
							<?php echo $address['city']; ?>
							<?php
						} else {
							?>
							<input name="0-comment_form_city" id="comment_form_city" class="text" size="22" value="<?php echo $address['city']; ?>" />
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php echo gettext('state:'); ?></th>
					<td>
						<?php 
						if ($disabled['state']) {
							?>
							<input name="0-comment_form_state" id="comment_form_state" class="text" type="hidden" size="22" value="<?php echo $address['state']; ?>" />
							<?php echo $address['state']; ?>
							<?php
						} else {
							?>
							<input name="0-comment_form_state" id="comment_form_state" class="text" size="22" value="<?php echo $address['state']; ?>" />
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php echo gettext('country:'); ?></th>
					<td>
						<?php 
						if ($disabled['country']) {
							?>
							<input name="0-comment_form_country" id="comment_form_country" class="text" type="hidden" size="22" value="<?php echo $address['country']; ?>" />
							<?php echo $address['country']; ?>
							<?php
						} else {
							?>
							<input name="0-comment_form_country" id="comment_form_country" class="text" size="22" value="<?php echo $address['country']; ?>" />
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php echo gettext('postal code:'); ?></th>
					<td>
						<?php 
						if ($disabled['postal']) {
							?>
							<input name="0-comment_form_postal" id="comment_form_postal" class="text" size="22" type="hidden" value="<?php echo $address['postal']; ?>" />
							<?php echo $address['postal']; ?>
							<?php
						} else {
							?>
							<input name="0-comment_form_postal" id="comment_form_postal" class="text" size="22" value="<?php echo $address['postal']; ?>" />
							<?php
						}
						?>
					</td>
				</tr>
				<?php
				}
				?>
				<tr valign="top" align="left">
					<th><?php echo gettext('Comment'); ?>:</th>
					<td><textarea tabindex="4" id="comment" name="comment" rows="10" cols="40"><?php echo $stored['comment']; ?></textarea></td>
				</tr>
				<tr valign="top" align="left">
					<th class="buttons">&nbsp;</th>
					<td class="buttons">
						<!--<input type="submit" name="preview" tabindex="5" value="Preview" id="btn-preview" />-->
						<input type="submit" name="post" tabindex="6" value="Post" id="btn-post" />
						<p><?php echo gettext('Avoid clicking &ldquo;Post&rdquo; more than once.'); ?></p>
					</td>
				</tr>
			</table>
		</form>

	</div>
	<!-- END #addcomment -->