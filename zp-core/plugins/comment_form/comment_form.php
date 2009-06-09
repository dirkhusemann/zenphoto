					<h3><?php echo gettext("Add a comment:"); ?></h3>
					<form id="commentform" action="#" method="post">
					<div>
						<input type="hidden" name="comment" value="1" />
						<input type="hidden" name="remember" value="1" />
						<?php
						printCommentErrors();
						$stored = getCommentStored();
						?>
						<table border="0">
							<tr>
								<td>
									<label for="name"><?php echo gettext("Name:"); ?></label>
										(<input type="checkbox" name="anon" value="1"<?php if ($stored['anon']) echo " CHECKED"; ?> /> <?php echo gettext("<em>anonymous</em>"); ?>)
								</td>
								<td>
									<input type="text" id="name" name="name" size="22" value="<?php echo $stored['name'];?>" class="inputbox"<?php echo $disabled['name']; ?> />
								</td>
							</tr>
							<tr>
								<td>
									<label for="email"><?php echo gettext("E-Mail:"); ?></label>
								</td>
								<td>
									<input type="text" id="email" name="email" size="22" value="<?php echo $stored['email'];?>" class="inputbox"<?php echo $disabled['email']; ?> />
								</td>
							</tr>
							<tr>
								<td>
									<label for="website"><?php echo gettext("Site:"); ?></label>
								</td>
								<td>
									<input type="text" id="website" name="website" size="22" value="<?php echo $stored['website'];?>" class="inputbox" />
								</td>
							</tr>
							<?php
							if (getOption('comment_form_addresses')) {
								?>
								<tr>
									<td>
										<label for="0-comment_form_street"><?php echo gettext('street:'); ?></label>
									</td>
									<td>
										<input type="text" name="0-comment_form_street" id="comment_form_street" class="inputbox" size="22" value="<?php echo $address['street']; ?>"<?php echo $disabled['street']; ?> />
									</td>
								<tr>
								</tr>
									<td>
										<label for="0-comment_form_city"><?php echo gettext('city:'); ?></label>
									</td>
									<td>
										<input type="text" name="0-comment_form_city" id="comment_form_city" class="inputbox" size="22" value="<?php echo $address['city']; ?>"<?php echo $disabled['city']; ?> />
									</td>
								<tr>
								</tr>
									<td><label for="0-comment_form_state"><?php echo gettext('state:'); ?></label></td>
									<td>
										<input type="text" name="0-comment_form_state" id="comment_form_state" class="inputbox" size="22" value="<?php echo $address['state']; ?>"<?php echo $disabled['state']; ?> />
									</td>
								<tr>
								</tr>
									<td><label for="0-comment_form_country"><?php echo gettext('country:'); ?></label></td>
									<td>
										<input type="text" name="0-comment_form_country" id="comment_form_country" class="inputbox" size="22" value="<?php echo $address['country']; ?>"<?php echo $disabled['country']; ?> />
									</td>
								<tr>
								</tr>
									<td><label for="0-comment_form_postal"><?php echo gettext('postal code:'); ?></label></td>
									<td>
										<input type="text" name="0-comment_form_postal" id="comment_form_postal" class="inputbox" size="22" value="<?php echo $address['postal']; ?>"<?php echo $disabled['postal']; ?> />
									</td>
								</tr>
								<?php
								}
							if (getOption('Use_Captcha')) {
 								$captchaCode=generateCaptcha($img); ?>
 								<tr>
	 								<td>
	 									<label for="code">
	 									<?php echo gettext("Enter Captcha:"); ?>
	 									<img src=<?php echo "\"$img\"";?> alt="Code" align="bottom" />
	 									</label>
	 								</td>
	 								<td>
	 									<input type="text" id="code" name="code" size="22" class="inputbox" />
	 									<input type="hidden" name="code_h" value="<?php echo $captchaCode;?>" />
	 								</td>
 								</tr>
								<?php
							}
							?>
							<tr>
								<td colspan="2">
									<input type="checkbox" name="private" value="1"<?php if ($stored['private']) echo " CHECKED"; ?> />
									<?php echo gettext("Private (don't publish)"); ?>
								</td>
							</tr>
						</table>
						<textarea name="comment" rows="6" cols="42" class="textarea_inputbox"><?php echo $stored['comment']; ?></textarea>
						<br />
						<input type="submit" value="<?php echo gettext('Add Comment'); ?>" class="pushbutton" /></div>
					</form>
				</div>