<?php

// functions-db.php - HEADERS NOT SENT YET!


require_once("functions.php");

$mysql_connection = null;

function db_connect() {
  global $mysql_connection;
  $db = zp_conf('mysql_database');
  $mysql_connection = @mysql_connect(zp_conf('mysql_host'), zp_conf('mysql_user'), zp_conf('mysql_pass'));
  if (!$mysql_connection) {
    echo "MySQL Error: Could not connect to the database server.<br />";
    return false;
  }

  if (!@mysql_select_db($db)) {
    echo "MySQL Error: Could not select the database $db<br />";
    return false;
  }
  return true;
}
db_connect();

function query($sql) {
  global $mysql_connection;
  if ($mysql_connection = null)
    db_connect();
  $result = mysql_query($sql) or die("MySQL Query ( $sql ) Failed. Error: " . mysql_error());
  return $result;
}

function query_single_row($sql) {
  $result = query($sql);
  return mysql_fetch_assoc($result);
}

function query_full_array($sql) {
  $result = query($sql);
  $allrows = array();
  while ($row = mysql_fetch_assoc($result))
    $allrows[] = $row;
  return $allrows;
}

function prefix($tablename) {
  return zp_conf('mysql_prefix').$tablename;
}

// For things that *are* going into the database, but not from G/P/C.
function escape($string) {
  if (get_magic_quotes_gpc()) 
    return $string;
  else 
    return mysql_real_escape_string($string);
}

// For things that *aren't* going into the database, from G/P/C.
function strip($string) {
  if (get_magic_quotes_gpc()) 
    return stripslashes($string);
  else 
    return $string;
}




?>
