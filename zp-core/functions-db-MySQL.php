<?php
/**
 * database core functions
 * @package core
 */

// force UTF-8 Ø

/**
 * Connect to the database server and select the database.
 *@return true if successful connection
 *@since 0.6
	*/
function db_connect() {
	global $mysql_connection, $_zp_conf_vars;
	$db = $_zp_conf_vars['mysql_database'];
	if (!function_exists('mysql_connect')) {
		zp_error(gettext('MySQL Error: The PHP MySQL extensions have not been installed. Please ask your administrator to add MySQL support to your PHP installation.'));
		return false;
	}
	if (!is_array($_zp_conf_vars)) {
		zp_error(gettext('The <code>$_zp_conf_vars</code> variable is not an array. Zenphoto has not been instantiated correctly.'));
		return false;
	}
	$mysql_connection = @mysql_connect($_zp_conf_vars['mysql_host'], $_zp_conf_vars['mysql_user'], $_zp_conf_vars['mysql_pass']);
	if (!$mysql_connection) {
		zp_error(sprintf(gettext('MySQL Error: Zenphoto received the error <em>%s</em> when connecting to the database server.'),mysql_error()));
		return false;
	}

	if (!@mysql_select_db($db)) {
		zp_error(sprintf(gettext('MySQL Error: The database is connected, but MySQL returned the error <em>%1$s</em> when Zenphoto tried to select the database %2$s.'),mysql_error(),$db));
		return false;
	}
	if (array_key_exists('UTF-8', $_zp_conf_vars) && $_zp_conf_vars['UTF-8']) {
		mysql_query("SET NAMES 'utf8'");
	}
	// set the sql_mode to relaxed (if possible)
	@mysql_query('SET SESSION sql_mode="";');
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
	global $mysql_connection, $_zp_query_count, $_zp_conf_vars;
	if ($mysql_connection == null) {
		db_connect();
	}
	// Changed this to mysql_query - *never* call query functions recursively...
	$result = mysql_query($sql, $mysql_connection);
	if (!$result) {
		if($noerrmsg) {
			return false;
		} else {
			$sql = sanitize($sql, 3);
			zp_error(sprintf(gettext('MySQL Query ( <em>%1$s</em> ) failed. MySQL returned the error <em>%2$s</em>' ),$sql,mysql_error()));
			return false;
		}
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
 * @param string $key optional array index key
 * @return results of the sql statements
 * @since 0.6
 */
function query_full_array($sql, $noerrmsg = false, $key=NULL) {
	$result = query($sql, $noerrmsg);
	if ($result) {
		$allrows = array();
		while ($row = mysql_fetch_assoc($result))
			if (is_null($key)) {
				$allrows[] = $row;
			} else {
				$allrows[$row[$key]] = $row;
			}
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
function prefix($tablename=NULL) {
	global $_zp_conf_vars;
	if (empty($tablename)) {
		return $_zp_conf_vars['mysql_prefix'];
	} else {
		return '`' . $_zp_conf_vars['mysql_prefix'] . $tablename . '`';
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
		$where .= ' `' . $var . '` = \'' . zp_escape_string($value) . '\'';
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
		$set .= ' `' . $var . '`=\'' . zp_escape_string($value) . '\'';
		$i++;
	}
	return $set;
}


/**
 * mysql_real_escape_string standin that insures the DB connection is passed.
 *
 * @param string $string
 * @return string
 */
function zp_escape_string($string) {
	global $mysql_connection;
	return mysql_real_escape_string($string,$mysql_connection);
}

/*
 * returns the insert id of the last database insert
 */
function db_insert_id() {
	return mysql_insert_id();
}

/*
 * get result data
 */
function db_result($result, $row, $field=0) {
	return mysql_result($result, $row, $field);
}

/*
 * Fetch a result row as an associative array
 */
function db_fetch_assoc($resource) {
	return mysql_fetch_assoc($resource);
}

/*
 * returns the connected database name
 */
function db_name() {
	global $_zp_conf_vars;
	return $_zp_conf_vars['mysql_database'];
}

/*
 * Returns the text of the error message from previous operation
 */
function db_error($resource=NULL) {
	if (is_null($resource)) {
		return mysql_error();
	} else {
		return mysql_error($resource);
	}
}

/*
 * Get number of affected rows in previous operation
 */
function db_affected_rows($link=NULL) {
	if (empty($link)) {
		return mysql_affected_rows();
	} else {
		return mysql_affected_rows($link);
	}
}

/*
 * Get a result row as an enumerated array
 */
function db_fetch_row($result) {
	return mysql_fetch_row($result);
}

/*
 * Get number of rows in result
 */
function db_num_rows($result) {
	return mysql_num_rows($result);
}

/*
 * report the software of the database
 */
function db_software() {
	$mysqlv = trim(@mysql_get_server_info());
	$i = strpos($mysqlv, "-");
	if ($i !== false) {
		$mysqlv = substr($mysqlv, 0, $i);
	}
	return array('application'=>'MySQL','required'=>'4.1','desired'=>'5.0','version'=>$mysqlv);
}
?>
