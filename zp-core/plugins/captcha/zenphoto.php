<?php
// force UTF-8 Ø

class captcha {
	/**
	 * Class instantiator
	 *
	 * @return captcha
	 */
	function captcha() {
		setOptionDefault('zenphoto_captcha_lenght', 5);
		$admins = getAdministrators();
		$admin = array_shift($admins);
		$key = $admin['pass'];
		setOptionDefault('zenphoto_captcha_key', $key);
	}

	/**
	 * Returns array of supported options for the admin-options handler
	 *
	 * @return unknown
	 */
	function getOptionsSupported() {
		return array(
								gettext('Hash key') => array('key' => 'zenphoto_captcha_key', 'type' => 0, 
												'desc' => gettext('The key to use in hashing the Captcha string.')),
								gettext('Captcha length') => array('key' => 'zenphoto_captcha_lenght', 'type' => 4, 
												'buttons' => array(gettext('3')=>3, gettext('4')=>4, gettext('5')=>5, gettext('6')=>6),
												'desc' => gettext('The number of characters in the Captcha.'))
								);
	}

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
		$captcha_len = getOption('zenphoto_captcha_lenght');
		$key = getOption('zenphoto_captcha_key');
		
debugLog("checkCaptcha(\$code=$code, \$code_ok=$code_ok)\$key=$key");		
		
		$code_cypher = md5(bin2hex(rc4($key, trim($code))));
		$code_ok = trim($code_ok);
		if ($code_cypher != $code_ok || strlen($code) != $captcha_len) { return false; }
		query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600)); // expired tickets
		$result = query('DELETE FROM '.prefix('captcha').' WHERE `hash`="'.$code_cypher.'"');
		$count = mysql_affected_rows();
		if ($count == 1) {
			$len = round(strlen($key)/2);
			$key = md5(substr($key, 0, $len).$code.substr($key, $len));
			setOption('zenphoto_captcha_key', $key);
			return true;
		}
		return false;
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

		$captcha_len = getOption('zenphoto_captcha_lenght');
		$key = getOption('zenphoto_captcha_key');
		$lettre='abcdefghijkmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$numlettre = strlen($lettre)-1;

		$string = '';
		for ($i=0; $i < $captcha_len; $i++) {
			$string .= $lettre[rand(0,$numlettre)];
		}
		$cypher = bin2hex(rc4($key, $string));
		$code=md5($cypher);
		query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600), true);  // expired tickets
		query("INSERT INTO " . prefix('captcha') . " (ptime, hash) VALUES ('" . escape(time()) . "','" . escape($code) . "')", true);
		$image = WEBPATH . '/' . ZENFOLDER . "/c.php?i=$cypher";

		
debugLog("generateCaptcha:\$string=$string:\$code=$code:\$key=$key");		
		
		return $code;
	}
}

?>