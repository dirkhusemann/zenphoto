<?php
/**
 * Zenphoto default captcha handler
 * 
 * @package plugins 
 */

// force UTF-8 Ø

class captcha {
	/**
	 * Class instantiator
	 *
	 * @return captcha
	 */
	function captcha() {
		setOptionDefault('zenphoto_captcha_length', 5);
		setOptionDefault('zenphoto_captcha_key', md5($_SERVER['HTTP_HOST'].'a9606420399a77387af2a4b541414ee5'.getUserIP()));
		setOptionDefault('zenphoto_captcha_string', 'abcdefghijkmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ'); 
	}

	/**
	 * Returns array of supported options for the admin-options handler
	 *
	 * @return unknown
	 */
	function getOptionsSupported() {
		return array(
								gettext('Hash key') => array('key' => 'zenphoto_captcha_key', 'type' => OPTION_TYPE_TEXTBOX, 
												'order'=> 2,
												'desc' => gettext('The key used in hashing the CAPTCHA string. Note: this key will change with each successful CAPTCHA verification.')),
								gettext('Allowed characters') => array('key' => 'zenphoto_captcha_string', 'type' => OPTION_TYPE_TEXTBOX, 
												'order'=> 1,
												'desc' => gettext('The characters which may appear in the CAPTCHA string.')),
								gettext('CAPTCHA length') => array('key' => 'zenphoto_captcha_length', 'type' => OPTION_TYPE_RADIO, 
												'order'=> 0,
												'buttons' => array(gettext('3')=>3, gettext('4')=>4, gettext('5')=>5, gettext('6')=>6),
												'desc' => gettext('The number of characters in the CAPTCHA.')),
								gettext('CAPTCHA font') => array('key' => 'zenphoto_captcha_font', 'type' => OPTION_TYPE_SELECTOR,
												'order'=> 3,
												'selections' => zp_getFonts(),
												'desc' => gettext('The font to use for CAPTCHA characters.')),
								'' 			=> array('key' => 'zenphoto_captcha_image', 'type' => OPTION_TYPE_CUSTOM,
												'order' => 4,
												'desc' => gettext('Sample CAPTCHA image'))
								);
	}
	function handleOption($key, $cv) {
		$captchaCode = $this->generateCaptcha($img);
		?>
		<script type="text/javascript">
			// <!-- <![CDATA[
			$(document).ready(function() { 	
					$('#zenphoto_captcha_font').change(function(){
						var imgsrc = '<img src="<?php echo $img; ?>&amp;f='+$('#zenphoto_captcha_font').val()+'" alt="" />';
						$('#zenphoto_captcha_image_loc').html(imgsrc);
					});	
			});
			// ]]> -->
		</script>
		<span id="zenphoto_captcha_image_loc"><img src="<?php echo $img; ?>" alt="" /></span>
		<?php
	}

	
	/**
	 * gets (or creates) the CAPTCHA encryption key
	 *
	 * @return string
	 */
	function getCaptchaKey() {
		global $_zp_authority;
		$key = getOption('zenphoto_captcha_key');
		if (empty($key)) {
			$admins = $_zp_authority->getAdministrators();
			if (count($admins) > 0) {
				$admin = array_shift($admins);
				$key = $admin['pass'];
			} else {
				$key = 'No admin set';
			}
			$key = md5('zenphoto'.$key.'captcha key');
			setOption('zenphoto_captcha_key', $key);
		}
		return $key;
	}

	/**
	 * Checks if a CAPTCHA string matches the CAPTCHA attached to the comment post
	 * Returns true if there is a match.
	 *
	 * @param string $key
	 * @param string $code
	 * @param string $code_ok
	 * @return bool
	 */
	function checkCaptcha($code, $code_ok) {
		$captcha_len = getOption('zenphoto_captcha_length');
		$key = $this->getCaptchaKey();
		$code_cypher = md5(bin2hex(rc4($key, trim($code))));
		$code_ok = trim($code_ok);
		if ($code_cypher != $code_ok || strlen($code) != $captcha_len) { return false; }
		query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600)); // expired tickets
		$result = query('DELETE FROM '.prefix('captcha').' WHERE `hash`="'.$code_cypher.'"');
		$count = mysql_affected_rows();
		if ($count == 1) {
			$len = rand(0, strlen($key)-1);
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

		$captcha_len = getOption('zenphoto_captcha_length');
		$key = $this->getCaptchaKey();
		$lettre = getOption('zenphoto_captcha_string');
		$numlettre = strlen($lettre)-1;

		$string = '';
		for ($i=0; $i < $captcha_len; $i++) {
			$string .= $lettre[rand(0,$numlettre)];
		}
		$cypher = bin2hex(rc4($key, $string));
		$code=md5($cypher);
		query('DELETE FROM '.prefix('captcha').' WHERE `ptime`<'.(time()-3600), true);  // expired tickets
		query("INSERT INTO " . prefix('captcha') . " (ptime, hash) VALUES ('" . zp_escape_string(time()) . "','" . zp_escape_string($code) . "')", true);
		$image = WEBPATH . '/' . ZENFOLDER . "/c.php?i=$cypher";
		return $code;
	}
}

?>