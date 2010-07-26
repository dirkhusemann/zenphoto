					<form id="commentform" action="#" method="post">
						<input type="hidden" name="comment" value="1" />
						<input type="hidden" name="remember" value="1" />
						<?php
						printCommentErrors();
						?>
						<table border="0">
							<tr>
								<td>
									<?php
									echo gettext("Name:");
									if (getOption('comment_form_anon') && !$disabled['anon']) {
										?>
										<label>(<input type="checkbox" name="anon" value="1"<?php if ($stored['anon']) echo ' checked="checked"'; echo $disabled['anon']; ?> /> <?php echo gettext("<em>anonymous</em>"); ?>)</label>
										<?php 
									}
									?>
								</td>
								<td>
									<?php
									if ($disabled['name']) {
										?>
										<div class="disabled_input" style="background-color:LightGray;color:black;">
											<?php
											echo htmlspecialchars($stored['name'],ENT_QUOTES);
											?>
											<input type="hidden" id="name" name="name" value="<?php echo htmlspecialchars($stored['name'],ENT_QUOTES);?>" />
										</div>
										<?php
									} else {
										?>
										<input type="text" id="name" name="name" size="22" value="<?php echo htmlspecialchars($stored['name'],ENT_QUOTES);?>" class="inputbox" />
										<?php										
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<label for="email"><?php echo gettext("E-Mail:"); ?></label>
								</td>
								<td>
									<?php
									if ($disabled['email']) {
										?>
										<div class="disabled_input" style="background-color:LightGray;color:black;">
											<?php
											echo htmlspecialchars($stored['email'],ENT_QUOTES);
											?>
											<input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($stored['email'],ENT_QUOTES);?>" />
										</div>
										<?php
									} else {
										?>
										<input type="text" id="email" name="email" size="22" value="<?php echo htmlspecialchars($stored['email'],ENT_QUOTES);?>" class="inputbox" />
										<?php								
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<label for="website"><?php echo gettext("Site:"); ?></label>
								</td>
								<td>
									<?php
									if ($disabled['website']) {
										?>
										<div class="disabled_input" style="background-color:LightGray;color:black;">
											<?php
											echo htmlspecialchars($stored['website'],ENT_QUOTES);
											?>
											<input type="hidden" id="website" name="website" value="<?php echo htmlspecialchars($stored['website'],ENT_QUOTES);?>" />
										</div>
										<?php		
									} else {	
										?>							
										<input type="text" id="website" name="website" size="22" value="<?php echo htmlspecialchars($stored['website'],ENT_QUOTES);?>" class="inputbox" />
										<?php
									}
									?>
								</td>
							</tr>
							<?php
							if (getOption('comment_form_addresses')) {
								?>
								<tr>
									<td>
										<label for="comment_form_street-0"><?php echo gettext('street:'); ?></label>
									</td>
									<td>
										<?php
											if ($disabled['street']) {
												?>
												<div class="disabled_input" style="background-color:LightGray;color:black;">
													<?php
													echo htmlspecialchars($stored['street'],ENT_QUOTES);
													?>
														<input type="hidden" id="comment_form_street-0" name="0-comment_form_street" value="<?php echo htmlspecialchars($stored['street'],ENT_QUOTES);?>" />
												</div>
												<?php
											} else {
												?>
												<input type="text" name="0-comment_form_street" id="comment_form_street" class="inputbox" size="22" value="<?php echo htmlspecialchars($stored['street'],ENT_QUOTES); ?>" />
												<?php									
											}
										?>
									</td>
								</tr>
								<tr>
									<td>
										<label for="comment_form_city-0"><?php echo gettext('city:'); ?></label>
									</td>
									<td>
										<?php
										if ($disabled['city']) {
											?>
											<div class="disabled_input"  style="background-color:LightGray;color:black;">
												<?php
												echo htmlspecialchars($stored['city'],ENT_QUOTES);
												?>
												<input type="hidden" id="comment_form_city-0" name="0-comment_form_city" value="<?php echo htmlspecialchars($stored['city'],ENT_QUOTES);?>" />
											</div>
											<?php
										} else {
											?>
											<input type="text" name="0-comment_form_city" id="comment_form_city" class="inputbox" size="22" value="<?php echo htmlspecialchars($stored['city'],ENT_QUOTES); ?>" />
											<?php								
										}
										?>
									</td>
								</tr>
								<tr>
									<td><label for="0-comment_form_state"><?php echo gettext('state:'); ?></label></td>
									<td>
										<?php
										if ($disabled['state']) {
											?>
											<div class="disabled_input" style="background-color:LightGray;color:black;">
												<?php
												echo htmlspecialchars($stored['state'],ENT_QUOTES);
												?>
												<input type="hidden" name="0-comment_form_state" id="comment_form_state-0" value="<?php echo htmlspecialchars($stored['state'],ENT_QUOTES);?>" />
											</div>
											<?php
										} else {
											?>
											<input type="text" name="0-comment_form_state" id="comment_form_state-0" class="inputbox" size="22" value="<?php echo htmlspecialchars($stored['state'],ENT_QUOTES); ?>" />
											<?php									
										}
										?>
									</td>
								</tr>
								<tr>
									<td><label for="comment_form_country-0"><?php echo gettext('country:'); ?></label></td>
									<td>
										<?php
										if ($disabled['country']) {
											?>
											<div class="disabled_input"  style="background-color:LightGray;color:black;">
												<?php
												echo htmlspecialchars($stored['country'],ENT_QUOTES);
												?>
												<input type="hidden" id="comment_form_country" name="0-comment_form_country" value="<?php echo htmlspecialchars($stored['country'],ENT_QUOTES);?>" />
											</div>
											<?php
										} else {
											?>
											<input type="text" name="comment_form_country" id="comment_form_country-0" class="inputbox" size="22" value="<?php echo htmlspecialchars($stored['country'],ENT_QUOTES); ?>" />
											<?php									
										}
										?>
									</td>
								</tr>
								<tr>
									<td><label for="comment_form_postal-0"><?php echo gettext('postal code:'); ?></label></td>
									<td>
										<?php
										if ($disabled['postal']) {
											?>
											<div class="disabled_input"  style="background-color:LightGray;color:black;">
												<?php
												echo htmlspecialchars($stored['postal'],ENT_QUOTES);
												?>
												<input type="hidden" name="0-comment_form_postal" value="<?php echo htmlspecialchars($stored['postal'],ENT_QUOTES);?>" />
											</div>
											<?php
										} else {
											?>
											<input type="text" id="comment_form_country-0" name="0-comment_form_postal" id="comment_form_postal" class="inputbox" size="22" value="<?php echo htmlspecialchars($stored['postal'],ENT_QUOTES); ?>" />
											<?php									
										}
										?>
									</td>
								</tr>
								<?php
								}
							if (getOption('Use_Captcha')) {
 								$captchaCode=generateCaptcha($img); ?>
 								<tr>
	 								<td>
	 									<label>
		 									<?php echo gettext("Enter CAPTCHA:"); ?>
		 									<img src=<?php echo "\"$img\"";?> alt="Code" align="middle" />
	 									</label>
	 								</td>
	 								<td>
	 									<input type="text" id="code" name="code" size="22" class="inputbox" />
	 									<input type="hidden" name="code_h" value="<?php echo $captchaCode;?>" />
	 								</td>
 								</tr>
								<?php
							}
							if (getOption('comment_form_private') && !$disabled['private']) {
								?>
								<tr>
									<td colspan="2">
										<label>
											<input type="checkbox" name="private" value="1"<?php if ($stored['private']) echo ' checked="checked"'; ?> />
											<?php echo gettext("Private comment (don't publish)"); ?>
										</label>
									</td>
								</tr>
								<?php 
							}
							?>
						</table>
						<textarea name="comment" rows="6" cols="42" class="textarea_inputbox"><?php echo $stored['comment']; echo $disabled['comment']; ?></textarea>
						<br />
						<input type="submit" value="<?php echo gettext('Add Comment'); ?>" class="pushbutton" />
					</form>