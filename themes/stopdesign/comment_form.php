					<h2>
					<?php $showhide = "<a href=\"#comments\" id=\"showcomments\"><img src=\"" .
						$_zp_themeroot . "/images/btn_show.gif\" width=\"35\" height=\"11\" alt=\"".gettext("SHOW")."\" /></a> <a href=\"#content\" id=\"hidecomments\"><img src=\"" .
						$_zp_themeroot . "/images/btn_hide.gif\" width=\"35\" height=\"11\" alt=\"".gettext("HIDE")."\" /></a>";
 						$num = getCommentCount(); 
 						if ($num == 0) echo "<h2>".gettext("No comments yet")."</h2>";
 						if ($num == 1) echo "<h2>" .gettext('1 comment so far').' '. "$showhide</h2>";
 						if ($num > 1) echo "<h2>".sprintf(gettext('%u comments so far'),$num).' '. "$showhide</h2>";
 					?>
					</h2>
					<?php printCommentErrors(); ?>

					<!-- BEGIN #comments -->
					<div id="comments">
						<dl class="commentlist">
						<?php
							$autonumber = 0;
							while (next_comment()):
								$autonumber++;
						?>
							<dt id="comment<?php echo $autonumber; ?>">
								<a href="#comment<?php echo $autonumber; ?>" class="postno" title="<?php printf(gettext('Link to Comment %u'),$autonumber); ?>"><?php echo $autonumber; ?>.</a>
								<em>On <?php echo getCommentDateTime();?>, <?php printf(gettext('%s wrote:'),printCommentAuthorLink()); ?></em>
							</dt>
							<dd><p><?php echo getCommentBody();?><?php printEditCommentLink(gettext('Edit'), ' | ', ''); ?></p></dd>
							<?php endwhile; ?>
						</dl>

						<?php if (OpenedForComments()) { ?>
							<p class="mainbutton" id="addcommentbutton"><a href="#addcomment" class="btn"><img src="<?php echo $_zp_themeroot ?>/images/btn_add_a_comment.gif" alt="" width="116" height="21" /></a></p>
						<?php } else { echo '<h2>'.gettext('Comments are closed').'</h2>'; } ?>

						<!-- BEGIN #addcomment -->
						<?php if (getCommentErrors()) {
							echo '<div id="addcomment" style="display: block;">';
						} else {
							echo '<div id="addcomment" style="display: none;">';
						}
						$stored = getCommentStored(); ?>
						<h2><?php gettext("Add a comment") ?></h2>
						<form method="post" action="#" id="comments-form">
							<input type="hidden" name="comment" value="1" />
							<input type="hidden" name="remember" value="1" />
							<table cellspacing="0">
								<tr valign="top" align="left" id="row-name">
									<th><label for="name"><?php echo gettext('Name:'); ?></label></th>
									<td><input tabindex="1" id="name" name="name" class="text" value="<?php echo $stored['name'];?>" />
										(<input type="checkbox" name="anon" value="1"<?php if ($stored['anon']) echo " CHECKED"; ?> /> <?php echo gettext('<em>anonymous</em>'); ?>)
									</td>
								</tr>
								<tr valign="top" align="left" id="row-email">
									<th><label for="email"><?php echo gettext('Email:'); ?></label></th>
									<td><input tabindex="2" id="email" name="email" class="text" value="<?php echo $stored['email'];?>" /> <em><?php echo gettext("(not displayed)"); ?></em></td>
								</tr>
								<tr valign="top" align="left">
									<th><label for="website"><?php echo gettext('URL:'); ?></label></th>
									<td><input tabindex="3" type="text" name="website" id="website" class="text" value="<?php echo $stored['website'];?>" /></td>
								</tr>
								<?php printCaptcha("<tr valign=\"top\" align=\"left\"><th><label for=\"captcha\">" .gettext('Enter Captcha'), ":</label></th><td>", "</td></tr>\n", 8); ?>
								<tr valign="top" align="left">
									<th><label for="private"><?php echo gettext('Private comment:'); ?></label></th>
									<td><input type="checkbox" name="private" value="1"<?php if ($stored['private']) echo " CHECKED"; ?> /> <?php echo gettext("Private (don't publish)"); ?></td>
								</tr>
							<?php
							if (getOption('comment_form_addresses')) {
								?>
								<tr>
									<th><label for="0-comment_form_street"><?php echo gettext('street:'); ?></label></th>
									</td>
									<td>
										<input type="text" name="0-comment_form_street" id="comment_form_street" class="textinput" size="22" value="<?php echo $address['street']; ?>">
									</td>
								<tr>
								</tr>
									<th><label for="0-comment_form_city"><?php echo gettext('city:'); ?></label></th>
									</td>
									<th><label for="private">
										<input type="text" name="0-comment_form_city" id="comment_form_city" class="textinput" size="22" value="<?php echo $address['city']; ?>">
									</td>
								<tr>
								</tr>
									<th><label for="0-comment_form_state"><?php echo gettext('state:'); ?></label></th>
									</td>
									<td>
										<input type="text" name="0-comment_form_state" id="comment_form_state" class="textinput" size="22" value="<?php echo $address['state']; ?>">
									</td>
								<tr>
								</tr>
									<th><label for="0-comment_form_country"><?php echo gettext('country:'); ?></label></th>
									</td>
									<td>
										<input type="text" name="0-comment_form_country" id="comment_form_country" class="textinput" size="22" value="<?php echo $address['country']; ?>">
									</td>
								<tr>
								</tr>
									<th><label for="0-comment_form_postal"><?php echo gettext('postal code:'); ?></label></th>
									</td>
									<td>
										<input type="text" name="0-comment_form_postal" id="comment_form_postal" class="textinput" size="22" value="<?php echo $address['postal']; ?>">
									</td>
								</tr>
								<?php
								}
								?>
								<tr valign="top" align="left">
									<th><label for="comment"><?php echo gettext('Comment'); ?>:</label></th>
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
				</div>
				<!-- END #comments -->