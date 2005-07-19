<?php

// functions-db.php - HEADERS NOT SENT YET!


require_once("functions.php");

$mysql_connection = null;

function db_connect() {
  global $mysql_connection;
  $db = zp_conf('mysql_database');
  $mysql_connection = mysql_connect(zp_conf('mysql_host'), zp_conf('mysql_user'), zp_conf('mysql_pass'))
     or die("Could not connect to MySQL: " . mysql_error());

  mysql_select_db($db) or die("Could not select database $db. Have you run the install script yet?");
  // Check for the existence of the correct tables (with $mysql_prefix!)
  // If there's a problem, set $mysql_connection to null and die with an error message.
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




?>
