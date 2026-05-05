<?php
/**
 * databaseobject.class.php
 * Base ORM class for all database-mapped objects in StagePlotter.
 *
 * Instantiates objects with data pulled from the database.
 * Defines CRUD operations, handles DB queries, and errors.
 */

class DatabaseObject
{
  static protected $pk         = 'id';   // subclasses override this
  static protected $database;
  static protected $table_name = '';
  static protected $db_columns = [];
  public ?int   $id     = null;
  public array  $errors = [];
  // $id defaults to null so save() correctly routes new objects to create() instead of update()

  // ============================================================
  // DATABASE SETUP
  // ============================================================

  /** Sets the shared PDO connection for all DatabaseObject subclasses. */
  static public function set_database(PDO $database): void
  {
    self::$database = $database;
  }

  // ============================================================
  // INSTANTIATION
  // ============================================================

  /** Creates and populates a subclass instance from a raw database row. */
  static protected function instantiate(array $record): static
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

  /** Returns all DB column values for this object, excluding the PK. */
  public function attributes(): array
  {
    $attributes = [];
    foreach (static::$db_columns as $column) {
      if ($column === static::$pk) {
        continue;
      }
      $attributes[$column] = $this->$column;
    }
    return $attributes;
  }

  /** Bulk-assigns values from $args into matching object properties. */
  public function merge_attributes(array $args = []): void
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

  protected function validate(): array
  {
    $this->errors = [];
    return $this->errors;
  }

  public function get_errors(): array
  {
    return $this->errors;
  }

  // ============================================================
  // CRUD OPERATIONS
  // ============================================================

  protected function create(): bool
  {
    $this->validate();
    if (!empty($this->errors)) {
      return false;
    }

    $attributes   = $this->attributes();
    $columns      = implode(', ', array_keys($attributes));
    $placeholders = ':' . implode(', :', array_keys($attributes));

    $sql    = "INSERT INTO " . static::$table_name . " ({$columns}) VALUES ({$placeholders})";
    $stmt   = self::$database->prepare($sql);
    $result = $stmt->execute($attributes);

    if ($result) {
      $this->id = (int) self::$database->lastInsertId();
    }
    return $result;
  }

  protected function update(): bool
  {
    $this->validate();
    if (!empty($this->errors)) {
      return false;
    }

    $attributes = $this->attributes();
    $pairs      = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($attributes)));
    $sql        = "UPDATE " . static::$table_name . " SET {$pairs}"
                . " WHERE " . static::$pk . " = :_pk_val LIMIT 1";

    $attributes['_pk_val'] = $this->id;
    return self::$database->prepare($sql)->execute($attributes);
  }

  public function save(): bool
  {
    return isset($this->id) ? $this->update() : $this->create();
  }

  public function delete(): bool
  {
    $sql = "DELETE FROM " . static::$table_name . " WHERE " . static::$pk . " = :_pk_val LIMIT 1";
    return self::$database->prepare($sql)->execute(['_pk_val' => $this->id]);
  }

  // ============================================================
  // QUERY / FINDER METHODS
  // ============================================================

  /** Executes a parameterized SQL query and returns an array of instantiated objects. */
  static public function find_by_sql(string $sql, array $params = []): array
  {
    $stmt = self::$database->prepare($sql);
    $stmt->execute($params);
    return array_map(fn($record) => static::instantiate($record), $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  /** Retrieves all rows as an array of objects. Use with caution on large tables. */
  static public function find_all(): array
  {
    return static::find_by_sql("SELECT * FROM " . static::$table_name);
  }

  /** Finds a single object by its primary key. Returns false if not found. */
  static public function find_by_id(int|string $id): static|false
  {
    $sql     = "SELECT * FROM " . static::$table_name . " WHERE " . static::$pk . " = ? LIMIT 1";
    $results = static::find_by_sql($sql, [$id]);
    return $results[0] ?? false;
  }
}
