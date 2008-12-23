<?php
// force UTF-8 Ø

require_once(dirname(dirname(__FILE__)).'/spamfilters/mollom.php');

/**
 * Checks if a Captcha string matches the Captcha attached to the comment post
 * Returns true if there is a match.
 *
 * @param string $key
 * @param string $mollom_session
 * @param string $code_ok
 * @return bool
 */
function checkCaptcha($mollom_session, $code_ok) {
	// set keys
	Mollom::setPublicKey(getOption('public_key'));
	Mollom::setPrivateKey(getOption('private_key'));
	
	$servers = Mollom::getServerList();
	Mollom::setServerList($servers);
	
	if(!Mollom::checkCaptcha($mollom_session, $code)) { return false; }
	
	query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600)); // expired tickets
	$result = query('DELETE FROM '.prefix('captcha').' WHERE `hash`="'.$mollom_session.'"');
	$count = mysql_affected_rows();
	return $count == 1;
}

/**
 * generates a Mollom captcha for comments
 *
 * Returns the captcha code string and image URL (via the $image parameter).
 *
 * @return string;
 */
function generateCaptcha(&$image) {

	Mollom::setPublicKey(getOption('public_key'));
	Mollom::setPrivateKey(getOption('private_key'));
	
	$servers = Mollom::getServerList();
	Mollom::setServerList($servers);
	
	// get captcha
	$captcha = Mollom::getImageCaptcha();
	
	$session_id = $captcha['session_id'];
	
	query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600), true);  // expired tickets
	query("INSERT INTO " . prefix('captcha') . " (ptime, hash) VALUES ('" . escape(time()) . "','" . escape($session_id) . "')", true);
	$image = $captcha['url'];
	return $session_id;
}


?>
