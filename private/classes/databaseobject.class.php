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
  static protected $pk = 'id';   // subclasses override this
  static protected $database;
  static protected $table_name = '';
  static protected $db_columns = [];
  public ?int $id = null;
  public $errors = [];
  // Note: $id defaults to null so that isset($this->id) in save() correctly returns false for a new object that hasn't been inserted yet, routing it to create() instead of update()

  // ============================================================
  // DATABASE SETUP
  // ============================================================

  /**
   * Sets the shared database connection for all DatabaseObject subclasses.
   *
   * Should be called once at application startup before any DB operations.
   *
   * @param PDO $database An active PDO database connection.
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
      // Map the custom PK column to $this->id (the parent's in-memory identifier)
      if ($property === static::$pk && $property !== 'id') {
        $object->id = (int) $value;
      } elseif (property_exists($object, $property)) {
        $object->$property = $value;
      }
    }
    return $object;
  }

  // ============================================================
  // ATTRIBUTE MANAGEMENT
  // ============================================================

  /**
   * Returns an associative array of the object's DB column values, excluding the PK.
   *
   * Used internally to build INSERT and UPDATE queries. The PK column ($pk) is
   * excluded because it is auto-assigned by the database on insert and used
   * as a WHERE condition on update — it should never appear in SET clauses.
   *
   * Visibility: public — subclasses or external code may need to inspect
   * current attribute values (e.g. for debugging or serialization).
   *
   * @return array Column name => current value pairs, excluding the PK column.
   */
  public function attributes()
  {
    $attributes = [];
    foreach (static::$db_columns as $column) {
      if ($column == static::$pk) {
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
   * Uses a PDO prepared statement with named placeholders built from $db_columns.
   * Runs validation first and returns false if any errors exist. On success,
   * assigns the new auto-incremented ID back to $this->id.
   *
   * Visibility: protected — external code should use save(), which routes to
   * create() or update() based on whether an ID is already set. Direct access
   * to create() is intentionally restricted.
   *
   * @return bool True on successful insert, false if validation fails.
   */
  protected function create()
  {
    $this->validate();
    if (!empty($this->errors)) {
      return false;
    }

    $attributes   = $this->attributes();
    $columns      = join(', ', array_keys($attributes));
    $placeholders = ':' . join(', :', array_keys($attributes));

    $sql    = "INSERT INTO " . static::$table_name . " ({$columns}) ";
    $sql   .= "VALUES ({$placeholders})";
    $stmt   = self::$database->prepare($sql);
    $result = $stmt->execute($attributes);

    if ($result) {
      $this->id = (int) self::$database->lastInsertId();
    }
    return $result;
  }

  /**
   * Updates the existing database row for this object.
   *
   * Uses a PDO prepared statement with named placeholders. Matches the row
   * using $this->id and applies the current attribute values.
   * Runs validation first and returns false if any errors are present.
   *
   * Visibility: protected — external code should use save(), which determines
   * whether to call create() or update(). Keeping this protected prevents
   * callers from bypassing the save() routing logic.
   *
   * @return bool True on successful update, false if validation fails.
   */
  protected function update()
  {
    $this->validate();
    if (!empty($this->errors)) {
      return false;
    }

    $attributes      = $this->attributes();
    $attribute_pairs = [];
    foreach (array_keys($attributes) as $key) {
      $attribute_pairs[] = "{$key} = :{$key}";
    }

    $sql  = "UPDATE " . static::$table_name . " SET ";
    $sql .= join(', ', $attribute_pairs);
    $sql .= " WHERE " . static::$pk . " = :_pk_val LIMIT 1";

    $attributes['_pk_val'] = $this->id;
    $stmt = self::$database->prepare($sql);
    return $stmt->execute($attributes);
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
   * instance still exists in memory (even though the database record does not)
   * and its properties remain readable, but further save operations (e.g.
   * update()) should not be called on it.
   *
   * Visibility: public — deleting a record is a standard operation that external
   * code (controllers, etc.) needs to trigger directly.
   *
   * @return bool True on successful deletion, false on query failure.
   */
  public function delete()
  {
    $sql  = "DELETE FROM " . static::$table_name . " ";
    $sql .= "WHERE " . static::$pk . " = :_pk_val LIMIT 1";
    $stmt = self::$database->prepare($sql);
    return $stmt->execute(['_pk_val' => $this->id]);
  }

  // ============================================================
  // QUERY / FINDER METHODS
  // ============================================================

  /**
   * Executes a parameterized SQL query and returns an array of instantiated objects.
   *
   * This is the core query runner used by all finder methods. It prepares and
   * executes the provided SQL with optional bound parameters, fetches each row,
   * and converts it into a subclass instance via instantiate().
   *
   * Visibility: public — subclasses and external code may need to run custom
   * queries that go beyond find_all() or find_by_id(). Exposing this method
   * provides that flexibility while keeping query building in one place.
   *
   * @param string $sql    A complete, valid SQL SELECT query string.
   * @param array  $params Optional array of values to bind to placeholders.
   * @return array An array of instantiated subclass objects, or empty on no results.
   */
  static public function find_by_sql($sql, $params = [])
  {
    $stmt = self::$database->prepare($sql);
    $stmt->execute($params);

    $object_array = [];
    while ($record = $stmt->fetch()) {
      $object_array[] = static::instantiate($record);
    }
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
    $sql       = "SELECT * FROM " . static::$table_name . " WHERE " . static::$pk . " = ?";
    $obj_array = static::find_by_sql($sql, [$id]);
    if (!empty($obj_array)) {
      return array_shift($obj_array);
    } else {
      return false;
    }
  }
}
