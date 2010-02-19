<?php
/**
 * Form for contact_form plugin
 * 
 * @package plugins 
 */
?>
<form id="mailform" action="<?php echo sanitize($_SERVER['REQUEST_URI']); ?>" method="post" accept-charset="UTF-8">
	<input type="hidden" id="sendmail" name="sendmail" value="sendmail" />
	<table border="0">
		<?php if(showOrNotShowField(getOption('contactform_title'))) { ?>
		<tr>
			<td><label for="title"><?php printf(gettext("Title<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_title')))); ?></label></td>
			<td><input type="text" id="title" name="title" size="50" value="<?php echo $mailcontent['title']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"';?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_name'))) { ?>
		<tr>
			<td><label for="name"><?php printf(gettext("Name<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_name')))); ?></label></td>
			<td><input type="text" id="name" name="name" size="50" value="<?php echo $mailcontent['name']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_company'))) { ?>
		<tr>
			<td><label for="company"><?php printf(gettext("Company<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_company')))); ?></label></td>
			<td><input type="text" id="company" name="company" size="50" value="<?php echo $mailcontent['company']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_title'))) { ?>
		<tr>
			<td><label for="street"><?php printf(gettext("Street<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_street')))); ?></label></td>
			<td><input type="text" id="street" name="street" size="50" value="<?php echo $mailcontent['street']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_city'))) { ?>
		<tr>
			<td><label for="city"><?php printf(gettext("City<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_city')))); ?></label></td>
			<td><input type="text" id="city" name="city" size="50" value="<?php echo $mailcontent['city']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_country'))) { ?>
  	<tr>
			<td><label for="city"><?php printf(gettext("Country<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_country')))); ?></label></td>
			<td><input type="text" id="country" name="country" size="50" value="<?php echo $mailcontent['country']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_email'))) { ?>	
		<tr>
			<td><label for="email"><?php printf(gettext("E-Mail<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_email')))); ?></label></td>
			<td><input type="text" id="email" name="email" size="50" value="<?php echo $mailcontent['email']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_website'))) { ?>
		<tr>
			<td><label for="website"><?php printf(gettext("Website<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_website')))); ?></label></td>
			<td><input type="text" id="website" name="website" size="50" value="<?php echo $mailcontent['website']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> />
			</td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_phone'))) { ?>
		<tr>
			<td><label for="phone"><?php printf(gettext("Phone<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_phone')))); ?></label></td>
			<td><input type="text" id="phone" name="phone" size="50" value="<?php echo $mailcontent['phone']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> /></td>
		</tr>
		<?php } ?>
		<?php if(getOption("contactform_captcha") && !$_processing_post) { $captchaCode=generateCaptcha($img); ?>
 		<tr>
 			<td>
 				<label><?php echo gettext("Enter Captcha<strong>*</strong>:"); ?>
 					<img src=<?php echo "\"$img\"";?> alt="Code" align="middle" />
 				</label>
 			</td>
 			<td><input type="text" id="code" name="code" size="50" />
 					<input type="hidden" name="code_h" value="<?php echo $captchaCode;?>"/></td>
 		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_subject'))) { ?>
		<tr>
			<td><label for="subject"><?php printf(gettext("Subject<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_subject')))); ?></label></td>
			<td><input type="text" id="subject" name="subject" size="50" value="<?php echo $mailcontent['subject']; ?>"<?php if ($_processing_post) echo ' disabled="disabled"'; ?> /></td>
		</tr>
		<?php } ?>
		<?php if(showOrNotShowField(getOption('contactform_message'))) { ?>
		<tr>
			<td><label for="message"><?php printf(gettext("Message<strong>%s</strong>:"),(checkRequiredField(getOption('contactform_message')))); ?></label></td>
			<td><textarea id="message" name="message" rows="5" cols="39" <?php if ($_processing_post) echo ' disabled="disabled"'; ?>><?php echo $mailcontent['message']; ?></textarea></td>
		</tr>
		<?php } ?>
		<?php if (!$_processing_post) { ?>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="<?php echo gettext("Send e-mail"); ?>" />
				<input type="reset" value="<?php echo gettext("Reset"); ?>" />
			</td>
		</tr>
		<?php } ?>
	</table>
</form>