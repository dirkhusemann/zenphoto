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


/** Connect to the database server and select the database.
 *  TODO: Handle errors more gracefully.
 */
function db_connect() {
  global $mysql_connection;
  $db = zp_conf('mysql_database');
  if (!function_exists('mysql_connect')) {
    echo 'MySQL Error: The PHP MySQL extentions have not been installed. Please ask your administrator to add them to your PHP installation.<br />';
    return false;
  }

  $mysql_connection = @mysql_connect(zp_conf('mysql_host'), zp_conf('mysql_user'), zp_conf('mysql_pass'));
  if (!$mysql_connection) {
    echo 'MySQL Error: Could not connect to the database server. Check your <strong>zp-config.php</strong> file for the correct '
      .  '<strong>Host, User name, and Password</strong>.<br />';
    return false;
  }

  if (!@mysql_select_db($db)) {
    echo 'MySQL Error: Could not select the database ' . $db . '<br />';
    return false;
  }
  return true;
}

// Connect to the database immediately.
db_connect();

/** The main query function. Runs the SQL on the connection and handles errors.
 *  TODO: Handle errors more gracefully.
 */
function query($sql) {
  global $mysql_connection;
  if ($mysql_connection == null) {
    db_connect();
  }
  $result = mysql_query($sql, $mysql_connection) or die('MySQL Query ( '.$sql.' ) Failed. Error: ' . mysql_error());
  return $result;
}

/** Runs a SQL query and returns an associative array of the first row.
 *  Doesn't handle multiple rows, so this should only be used for unique entries.
 */
function query_single_row($sql) {
  $result = query($sql);
  return mysql_fetch_assoc($result);
}

/** Runs a SQL query and returns an array of associative arrays of every row returned.
 *  TODO: This may not be very efficient. Could use a global resultset instead,
 *        then use a next_db_entry()-like function to get the next row.
 *        But this is probably just fine.
 */
function query_full_array($sql) {
  $result = query($sql);
  $allrows = array();
  while ($row = mysql_fetch_assoc($result))
    $allrows[] = $row;
  return $allrows;
}


/** Prefix a table name with a user-defined string to avoid conflicts.
 *  This MUST be used in all database queries.
 */
function prefix($tablename) {
  return '`' . zp_conf('mysql_prefix') . $tablename . '`';
}

// For things that *are* going into the database, but not from G/P/C.
function escape($string) {
  if (get_magic_quotes_gpc()) {
    return $string;
  } else {
    return mysql_real_escape_string($string);
  }
}

// For things that *aren't* going into the database, from G/P/C.
function strip($string) {
  if (get_magic_quotes_gpc()) {
    return stripslashes($string);
  } else {
    return $string;
  }
}


/**
 * Constructs a WHERE clause ("WHERE uniqueid1='uniquevalue1' AND uniqueid2='uniquevalue2' ...")
 * from an array (map) of variables and their values which identifies a unique record
 * in the database table.
 */
function getWhereClause($unique_set) {
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
 * from an array (map) of variables and their values which identifies a unique record
 * in the database table. Used to 'move' records. Note: does not check anything.
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




?>
