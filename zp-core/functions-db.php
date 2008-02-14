<?php

// functions-db.php - HEADERS NOT SENT YET!

require_once("functions.php");

$mysql_connection = null;

// Fix mysql_real_escape_string for PHP < 4.3.0
if (!function_exists('mysql_real_escape_string')) {
	function mysql_real_escape_string($string) {
		mysql_escape_string($string);
	}
}


/**
 * Connect to the database server and select the database.
 *@return true if successful connection
 *@since 0.6
	*/
function db_connect() {
 /* TODO: Handle errors more gracefully. */
	global $mysql_connection, $_zp_conf_vars;
	$db = $_zp_conf_vars['mysql_database'];
	if (!function_exists('mysql_connect')) {
		zp_error('MySQL Error: The PHP MySQL extentions have not been installed. '
			.  'Please ask your administrator to add MySQL support to your PHP installation.');
		return false;
	}

	$mysql_connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass']);
	if (!$mysql_connection) {
		zp_error('MySQL Error: Zenphoto could not connect to the database server. Check '
			.  'your <strong>zp-config.php</strong> file for the correct <em><strong>host</strong>, '
			.  '<strong>user name</strong>, and <strong>password</strong></em>. Note that you may need to change the '
			.  '<em>host</em> from localhost if your web server uses a separate MySQL server, which is '
			.  'common in large shared hosting environments like Dreamhost and GoDaddy. Also make sure the server '
			.  'is running, if you control it.');
		return false;
	}

	if (!@mysql_select_db($db)) {
		zp_error('MySQL Error: The database is connected, but Zenphoto could not select the database "' . $db . '". '
			.  'Make sure it already exists, create it if you need to. Also make sure the user you\'re trying to '
			.  'connect with has privileges to use this database.');
		return false;
	}
	return true;
}

// Connect to the database immediately.
db_connect();

$_zp_query_count = 0;

/**
 * The main query function. Runs the SQL on the connection and handles errors.
 * @param string $sql sql code
 * @param bool $noerrmsg set to true to supress the error message
 * @return results of the sql statements
 * @since 0.6
 */
function query($sql, $noerrmsg = false) {
 /* TODO: Handle errors more gracefully. */
	global $mysql_connection;
	global $_zp_query_count;
	if ($mysql_connection == null) {
		db_connect();
	}
	$result = mysql_query($sql, $mysql_connection);
	if (!$result && !$noerrmsg) {
		$sql = sanitize($sql, true);
		$error = "MySQL Query ( <em>$sql</em> ) Failed. Error: " . mysql_error();
		// Changed this to mysql_query - *never* call query functions recursively...
		if (!mysql_query("SELECT 1 FROM " . prefix('albums') . " LIMIT 0", $mysql_connection)) {
			$error .= "<br>It looks like your zenphoto tables haven't been created. You may need to "
				. " <a href=\"" . WEBPATH . '/' . ZENFOLDER . "/setup.php\">run the setup script</a>.";
		}
		zp_error($error);
		return false;
	}
	$_zp_query_count++;
	return $result;
}

/**
 * Runs a SQL query and returns an associative array of the first row.
 * Doesn't handle multiple rows, so this should only be used for unique entries.
 * @param string $sql sql code
 * @param bool $noerrmsg set to true to supress the error message
 * @return results of the sql statements
 * @since 0.6
 */
function query_single_row($sql, $noerrmsg=false) {
	$result = query($sql, $noerrmsg);
	if ($result) {
		return mysql_fetch_assoc($result);
	} else {
		return false;
	}
}

/**
 * Runs a SQL query and returns an array of associative arrays of every row returned.
 * @param string $sql sql code
 * @param bool $noerrmsg set to true to supress the error message
 * @return results of the sql statements
 * @since 0.6
 */
function query_full_array($sql, $noerrmsg = false) {
/* TODO: This may not be very efficient. Could use a global resultset instead,
 * 			then use a next_db_entry()-like function to get the next row.
 *		But this is probably just fine.
 */
	$result = query($sql, $noerrmsg);
	if ($result) {
		$allrows = array();
		while ($row = mysql_fetch_assoc($result))
			$allrows[] = $row;
		return $allrows;
	} else {
		return false;
	}
}


/**
 * Prefix a table name with a user-defined string to avoid conflicts.
 * This MUST be used in all database queries.
 *@param string $tablename name of the table
 *@return prefixed table name
 *@since 0.6
	*/
function prefix($tablename) {
	global $_zp_conf_vars;
	return '`' . $_zp_conf_vars['mysql_prefix'] . $tablename . '`';
}

/**
 * For things that *are* going into the database, but not from G/P/C.
 *@param string $string string to clean
 *@return cleaned up string
 *@since 0.6
	*/
function escape($string) {
	if (get_magic_quotes_gpc()) {
		return $string;
	} else {
		return mysql_real_escape_string($string);
	}
}

/**
 * For things that *aren't* going into the database, but not from G/P/C.
 *@param string $string string to clean
 *@return cleaned up string
 *@since 0.6
	*/
function strip($string) {
	if (get_magic_quotes_gpc()) {
		return stripslashes($string);
	} else {
		return $string;
	}
}

/**
 * Constructs a WHERE clause ("WHERE uniqueid1='uniquevalue1' AND uniqueid2='uniquevalue2' ...")
 *  from an array (map) of variables and their values which identifies a unique record
 *  in the database table.
 *@param string $unique_set what to add to the WHERE clause
 *@return contructed WHERE cleause
 *@since 0.6
	*/
function getWhereClause($unique_set) {
	if (empty($unique_set)) return ' ';
	$i = 0;
	$where = ' WHERE';
	foreach($unique_set as $var => $value) {
		if ($i > 0) $where .= ' AND';
		$where .= ' `' . $var . '` = \'' . mysql_real_escape_string($value) . '\'';
		$i++;
	}
	return $where;
}

/**
 * Constructs a SET clause ("SET uniqueid1='uniquevalue1', uniqueid2='uniquevalue2' ...")
 *  from an array (map) of variables and their values which identifies a unique record
 *  in the database table. Used to 'move' records. Note: does not check anything.
 *@param string $new_unique_set what to add to the SET clause
 *@return contructed SET cleause
 *@since 0.6
	*/
function getSetClause($new_unique_set) {
	$i = 0;
	$set = ' SET';
	foreach($new_unique_set as $var => $value) {
		if ($i > 0) $set .= ', ';
		$set .= ' `' . $var . '`=\'' . mysql_real_escape_string($value) . '\'';
		$i++;
	}
	return $set;
}

/**
 * encodes a query
 *@param string $url url to encode
 *@return encoded url
 *@since 0.6
	*/
function queryEncode($url) {
	$encode = str_replace('%26', '%%1', urlencode($url));  
	return str_replace('%23', '%%2', $encode); 
} 

/**
 * decodes a query
 *@param string $url url to decode
 *@return decoded url
 *@since 0.6
	*/
function queryDecode($url) { 
	$decode = str_replace('%%1', '%26', $url); 
	return urldecode(str_replace('%%2', '%23', $decode)); 
}

?>
