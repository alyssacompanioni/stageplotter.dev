<?php
/* 
* databaseobject.class.php
* Creates a connection to the DB as a PHP Database Object for StagePlotter.dev
* 
* Defines CRUD operations, handles DB queries, and errors.
* 
*@author ALyssa Companioni
*
*/

class DatabaseObject
{

  static protected $database;
  static protected $table_name = '';
  static protected $db_columns = [];
  public $errors = [];

  static public function set_database($database)
  {
    self::$database = $database;
  }
}
