<?php

// classes.php - HEADERS STILL NOT SENT! Do not output text from this file.

// Load the authentication functions, UTF-8 Library, and kses.
require_once("auth_zp.php");
require_once("utf8.php");
require_once("kses.php");


/*******************************************************************************
 *******************************************************************************
 * Persistent Object Class *****************************************************
 *                                                                              
 * Parent ABSTRACT class of all persistent objects. This class should not be    
 * instantiated, only used for subclasses. This cannot be enforced, but please  
 * follow it!                                                                   
 *                                                                              
 * Documentation/Instructions:                                                  
 * A child class should run the follwing in its constructor:                    
 *                                                                              
 * $new = parent::PersistentObject('tablename',                                  
 *   array('uniquestring'=>$value, 'uniqueid'=>$uniqueid));                     
 *                                                                              
 * where 'tablename' is the name of the database table to use for this object   
 * type, and array('uniquestring'=>$value, ...) defines a unique set of columns 
 * (keys) and their current values which uniquely identifies a single record in 
 * that database table for this object. The return value of the constructor     
 * (stored in $new in the above example) will be (=== TRUE) if a new record was 
 * created, and (=== FALSE) if an existing record was updated. This can then be 
 * used to set() default values for NEW objects and save() them.                
 *                                                                              
 * Note: This is a persistable model that does not save automatically. You MUST 
 * call $this->save(); explicitly to persist the data in child classes.          
 *                                                                              
 *******************************************************************************
 ******************************************************************************/

// ABSTRACT
class PersistentObject {

  var $data;
  var $updates;
  var $table;
  var $unique_set;
  var $id;
  
  function PersistentObject($tablename, $unique_set) {
    // Initialize the variables.
    // Load the data into the data array using $this->load()
    $this->data = array();
    $this->updates = array();
    $this->table = $tablename;
    $this->unique_set = $unique_set;

    return $this->load();
  }
  
  /**
   * Set a variable in this object. Does not persist to the database until 
   * save() is called. So, IMPORTANT: Call save() after set() to persist.
   */
  function set($var, $value) {
    $this->updates[$var] = $value;
  }
  
  /**
   * Get the value of a variable. If $current is false, return the value
   * as of the last save of this object.
   */
  function get($var, $current=true) {
    if ($current && isset($this->updates[$var])) {
      return $this->updates[$var];
    } else if (isset($this->data[$var])) {
      return $this->data[$var];
    } else {
      return null;
    }
  }
  
  /** 
   * Load the data array from the database, using the unique id set to get the unique record.
   * @return false if the record already exists, true if a new record was created.
   *   The return value can be used to insert default data for new objects.
   */
  function load() {
    // Get the database record for this object.
    $entry = query_single_row("SELECT * FROM " . prefix($this->table) .
      getWhereClause($this->unique_set) . " LIMIT 1;");
    if (!$entry) {
      $this->save();
      return true;
    } else {
      $this->data = $entry;
      $this->id = $entry['id'];
      return false;
    }
  }

  /** 
   * Save the updates made to this object since the last update. Returns
   * true if successful, false if not.
   */
  function save() {
    if ($this->id == null) {
      // Create a new object and set the id from the one returned.
      $insert_data = array_merge($this->unique_set, $this->updates);
      $sql = "INSERT INTO " . prefix($this->table) . " (";
      if (empty($insert_data)) { return true; }
      $i = 0;
      foreach(array_keys($insert_data) as $col) {
        if ($i > 0) $sql .= ", ";
        $sql .= "`$col`";
        $i++;
      }
      $sql .= ") VALUES (";
      $i = 0;
      foreach(array_values($insert_data) as $value) {
        if ($i > 0) $sql .= ", ";
        $sql .= "'" . mysql_escape_string($value) . "'";
        $i++;
      }
      $sql .= ");";
      $success = query($sql);
      if ($success == false || mysql_affected_rows() != 1) { return false; }
      $this->id = mysql_insert_id();
      $this->updates = array();

    } else {
      // Save the existing object (updates only) based on the existing id.
      if (empty($this->updates)) {
        return true;
      } else {
        $sql = "UPDATE " . prefix($this->table) . " SET";
        $i = 0;
        foreach ($this->updates as $col => $value) {
          if ($i > 0) $sql .= ",";
          $sql .= " `$col` = '". mysql_escape_string($value) . "'";
          $this->data[$col] = $value;
          $i++;
        }
        $sql .= " WHERE id=" . $this->id . ";";
        $success = query($sql);
        if ($success == false || mysql_affected_rows() != 1) { return false; }
        $this->updates = array();
      }
    }
    return true;
  }

}



/*******************************************************************************
* Load the base classes for Image, Album, and Gallery                          *
*******************************************************************************/

require_once('class-image.php');
require_once('class-album.php');
require_once('class-gallery.php');

?>
