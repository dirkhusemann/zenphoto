<?php
// force UTF-8 Ø

define('CAPTCHA_LENGTH', 5);

/**
 * Checks if a Captcha string matches the Captcha attached to the comment post
 * Returns true if there is a match.
 *
 * @param string $key
 * @param string $code
 * @param string $code_ok
 * @return bool
 */
function checkCaptcha($code, $code_ok) {
	$admins = getAdministrators();
	$admin = array_shift($admins);
	$key = $admin['pass'];
	$code_cypher = md5(bin2hex(rc4($key, trim($code))));
	$code_ok = trim($code_ok);
	if ($code_cypher != $code_ok || strlen($code) != CAPTCHA_LENGTH) { return false; }
	query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600)); // expired tickets
	$result = query('DELETE FROM '.prefix('captcha').' WHERE `hash`="'.$code_cypher.'"');
	$count = mysql_affected_rows();
	return $count == 1;
}

/**
 * generates a simple captcha for comments
 *
 * Thanks to gregb34 who posted the original code
 *
 * Returns the captcha code string and image URL (via the $image parameter).
 *
 * @return string;
 */
function generateCaptcha(&$image) {

	$lettre='abcdefghijkmnpqrstuvwxyz23456789';

	$string = '';
	for ($i=0; $i < CAPTCHA_LENGTH; $i++) {
		$string .= $lettre[rand(0,31)];
	}
	$admins = getAdministrators();
	$admin = array_shift($admins);
	$key = $admin['pass'];
	$cypher = bin2hex(rc4($key, $string));
	$code=md5($cypher);
	query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600), true);  // expired tickets
	query("INSERT INTO " . prefix('captcha') . " (ptime, hash) VALUES ('" . escape(time()) . "','" . escape($code) . "')", true);
	$image = WEBPATH . '/' . ZENFOLDER . "/c.php?i=$cypher";

	return $code;
}


?>