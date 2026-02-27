<?php
/* 
* databaseobject.class.php
* Creates class to manipulate the PHP Database Object for StagePlotter.dev
* 
* Instantiates objects with data pulled from the database.
* Defines CRUD operations, handles DB queries, and errors.
* 
*@author Alyssa Companioni
*
*/

class DatabaseObject
{

  static protected $database;
  static protected $table_name = '';
  static protected $db_columns = [];
  public $errors = [];

  // ============================================================
  // DATABASE SETUP
  // ============================================================

  /**
   * Sets the shared database connection for all DatabaseObject subclasses.
   *
   * Should be called once at application startup before any DB operations.
   *
   * @param mysqli $database An active database connection.
   * @return void
   */
  static public function set_database($database)
  {
    self::$database = $database;
  }

  // ============================================================
  // INSTANTIATION
  // ============================================================

  /**
   * Creates and populates a subclass instance from a raw database record.
   *
   * Iterates over the associative record array and assigns values to matching
   * object properties. Properties not defined on the object are skipped.
   *
   * Visibility: protected — only used internally by find methods; subclasses
   * should not need to expose this to callers.
   *
   * @param array $record Associative array representing a single DB row.
   * @return static A populated instance of the calling subclass.
   */
  static protected function instantiate($record)
  {
    $object = new static;
    foreach ($record as $property => $value) {
      if (property_exists($object, $property)) {
        $object->$property = $value;
      }
    }
    return $object;
  }

  // ============================================================
  // ATTRIBUTE MANAGEMENT
  // ============================================================

  /**
   * Returns an associative array of the object's DB column values, excluding 'id'.
   *
   * Used internally to build INSERT and UPDATE queries. The 'id' column is
   * excluded because it is auto-assigned by the database on insert and used
   * as a WHERE condition on update — it should never appear in SET clauses.
   *
   * Visibility: public — subclasses or external code may need to inspect
   * current attribute values (e.g. for debugging or serialization).
   *
   * @return array Column name => current value pairs, excluding 'id'.
   */
  public function attributes()
  {
    $attributes = [];
    foreach (static::$db_columns as $column) {
      if ($column == 'id') {
        continue;
      }
      $attributes[$column] = $this->$column;
    }
    return $attributes;
  }

  /**
   * Merges an associative array of values into the object's properties.
   *
   * Useful for bulk-assigning form input or request data to an object before
   * saving. Only existing, non-null values are applied.
   *
   * Visibility: public — intended to be called by controllers or external code
   * that needs to populate an object before a save operation.
   *
   * @param array $args Associative array of property names and their new values.
   * @return void
   */
  public function merge_attributes($args = [])
  {
    foreach ($args as $key => $value) {
      if (property_exists($this, $key) && !is_null($value)) {
        $this->$key = $value;
      }
    }
  }

  /**
   * Returns a sanitized copy of the object's attributes, safe for use in SQL queries.
   *
   * Runs each attribute value through the database's escape_string method to
   * prevent SQL injection. Should always be used when building raw SQL strings.
   *
   * Visibility: protected — this is an internal helper for create() and update();
   * callers outside the class should never need raw escaped SQL values directly.
   *
   * @return array Sanitized column name => escaped value pairs.
   */
  protected function sanitized_attributes()
  {
    $sanitized = [];
    foreach ($this->attributes() as $key => $value) {
      $sanitized[$key] = self::$database->escape_string($value);
    }
    return $sanitized;
  }

  // ============================================================
  // VALIDATION
  // ============================================================

  /**
   * Validates the object's current state before a save operation.
   *
   * Resets $errors to an empty array on each call, then applies any validation
   * rules defined in this method or overridden by subclasses. Returns the
   * errors array so callers can check for failures.
   *
   * Visibility: protected — validation is an internal pre-save step. Subclasses
   * should override this method to add their own rules, but external code should
   * not call validate() directly; use save() instead.
   *
   * @return array An array of error messages, or empty if validation passed.
   */
  protected function validate()
  {
    $this->errors = [];
    // Add custom validations
    return $this->errors;
  }

  /**
   * Returns the current list of validation errors.
   *
   * Useful for displaying error messages to a user after a failed save attempt.
   *
   * Visibility: public — errors need to be accessible to views and controllers
   * after calling save().
   *
   * @return array Array of validation error message strings.
   */
  public function get_errors()
  {
    return $this->errors;
  }

  // ============================================================
  // CRUD OPERATIONS
  // ============================================================

  /**
   * Inserts the current object as a new row in the database.
   *
   * Runs validation first and returns false if any errors exist. On success,
   * assigns the new auto-incremented ID back to $this->id.
   *
   * Visibility: protected — external code should use save(), which routes to
   * create() or update() based on whether an ID is already set. Direct access
   * to create() is intentionally restricted.
   *
   * @return bool True on successful insert, false if validation fails or query errors.
   */
  protected function create()
  {
    $this->validate();
    if (!empty($this->errors)) {
      return false;
    }

    $attributes = $this->sanitized_attributes();
    $sql = "INSERT INTO " . static::$table_name . " (";
    $sql .= join(', ', array_keys($attributes));
    $sql .= ") VALUES ('";
    $sql .= join("', '", array_values($attributes));
    $sql .= "')";
    $result = self::$database->query($sql);
    if ($result) {
      $this->id = self::$database->insert_id;
    }
    return $result;
  }

  /**
   * Updates the existing database row for this object.
   *
   * Matches the row using $this->id and applies the current attribute values.
   * Runs validation first and returns false if any errors are present.
   *
   * Visibility: protected — external code should use save(), which determines
   * whether to call create() or update(). Keeping this protected prevents
   * callers from bypassing the save() routing logic.
   *
   * @return bool True on successful update, false if validation fails or query errors.
   */
  protected function update()
  {
    $this->validate();
    if (!empty($this->errors)) {
      return false;
    }

    $attributes = $this->sanitized_attributes();
    $attribute_pairs = [];
    foreach ($attributes as $key => $value) {
      $attribute_pairs[] = "{$key}='{$value}'";
    }

    $sql = "UPDATE " . static::$table_name . " SET ";
    $sql .= join(', ', $attribute_pairs);
    $sql .= " WHERE id='" . self::$database->escape_string($this->id) . "' ";
    $sql .= "LIMIT 1";
    $result = self::$database->query($sql);
    return $result;
  }

  /**
   * Saves the object by either inserting or updating, based on whether an ID exists.
   *
   * This is the primary entry point for persisting an object. If $this->id is set,
   * the record already exists and will be updated. Otherwise, a new record is created.
   *
   * Visibility: public — this is the intended external interface for all save operations.
   *
   * @return bool True on success, false on validation failure or query error.
   */
  public function save()
  {
    if (isset($this->id)) {
      return $this->update();
    } else {
      return $this->create();
    }
  }

  /**
   * Deletes the database row corresponding to this object.
   *
   * Uses $this->id to identify the target row. After deletion, the PHP object
   * instance still exists in memory (even though the database record does not) and * its properties remain readable, but
   * further save operations (e.g. update()) should not be called on it.
   *
   * Visibility: public — deleting a record is a standard operation that external
   * code (controllers, etc.) needs to trigger directly.
   *
   * @return bool True on successful deletion, false on query failure.
   */
  public function delete()
  {
    $sql = "DELETE FROM " . static::$table_name . " ";
    $sql .= "WHERE id='" . self::$database->escape_string($this->id) . "' ";
    $sql .= "LIMIT 1";
    $result = self::$database->query($sql);
    return $result;
  }

  // ============================================================
  // QUERY / FINDER METHODS
  // ============================================================

  /**
   * Executes a raw SQL query and returns an array of instantiated objects.
   *
   * This is the core query runner used by all finder methods. It executes the
   * provided SQL, fetches each row as an associative array, and converts it
   * into a subclass instance via instantiate().
   *
   * Visibility: public — subclasses and external code may need to run custom
   * queries that go beyond find_all() or find_by_id(). Exposing this method
   * provides that flexibility while keeping query building in one place.
   *
   * @param string $sql A complete, valid SQL SELECT query string.
   * @return array An array of instantiated subclass objects, or empty on no results.
   */
  static public function find_by_sql($sql)
  {
    $result = self::$database->query($sql);
    if (!$result) {
      exit("Database query failed.");
    }

    // Convert results into objects:
    $object_array = [];
    while ($record = $result->fetch_assoc()) {
      $object_array[] = static::instantiate($record);
    }

    $result->free();
    return $object_array;
  }

  /**
   * Retrieves all rows from the subclass's table as an array of objects.
   *
   * Performs a SELECT * with no filtering. Use with caution on large tables.
   *
   * Visibility: public — a standard finder that controllers and views use to
   * list all records.
   *
   * @return array An array of instantiated subclass objects.
   */
  static public function find_all()
  {
    $sql = "SELECT * FROM " . static::$table_name;
    return static::find_by_sql($sql);
  }

  /**
   * Finds and returns a single object by its primary key ID.
   *
   * Queries for a row with a matching 'id' column and returns the first result
   * as an instantiated object. Returns false if no matching record is found.
   *
   * Visibility: public — looking up a record by ID is a fundamental operation
   * used throughout controllers and application logic.
   *
   * @param int|string $id The primary key value to search for.
   * @return static|false The matching object instance, or false if not found.
   */
  static public function find_by_id($id)
  {
    $sql = "SELECT * FROM " . static::$table_name . " ";
    $sql .= "WHERE id='" . self::$database->escape_string($id) . "'";
    $obj_array = static::find_by_sql($sql);
    if (!empty($obj_array)) {
      return array_shift($obj_array);
    } else {
      return false;
    }
  }
}
