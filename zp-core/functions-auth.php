<?php
/**
 * functions used in password hashing for zenphoto
 * 
 * @package functions
 */
global $x;


/**
 * Returns the hash of the zenphoto password
 *
 * @param string $user
 * @param string $pass
 * @return string
 */
function passwordHash($user, $pass) {
	return md5($user . $pass);
}
?>