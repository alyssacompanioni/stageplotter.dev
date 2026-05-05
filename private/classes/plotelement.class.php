<?php

/**
 * plotelement.class.php
 * Represents a single element placed on a stage plot canvas.
 *
 * Each PlotElement belongs to one stage plot (id_staplot_pele) and carries:
 *   - Pixel position (x_pos_pele, y_pos_pele) for canvas placement
 *   - Real-world size in feet (width_pele, depth_pele) for usability reference
 *   - Rendered icon size in pixels (px_size_pele) for canvas display
 *   - Rotation in degrees (0–359) and horizontal flip state
 *   - Display info: SVG source path and element name
 *
 * Autoloaded as: PlotElement → plotelement.class.php
 */

class PlotElement extends DatabaseObject
{

  static protected $table_name = 'plot_element_pele';
  static protected $pk         = 'id_pele';
  static protected $db_columns = [
    'id_pele',
    'id_staplot_pele',
    'x_pos_pele',
    'y_pos_pele',
    'rotation_pele',
    'z_index_pele',
    'px_size_pele',
    'src_pele',
    'name_pele',
    'flipped_pele',
  ];

  // Properties match SQL column names exactly so instantiate() maps them automatically.
  public ?int   $id_staplot_pele = null;
  public float  $x_pos_pele      = 0.0;    // pixels
  public float  $y_pos_pele      = 0.0;    // pixels
  public int    $rotation_pele   = 0;      // degrees
  public int    $z_index_pele    = 1;
  public int    $px_size_pele    = 48;     // pixels — rendered icon size on canvas
  public string $src_pele        = '';
  public string $name_pele       = '';
  public int    $flipped_pele    = 0;

  // Validation limits
  const MIN_PX_POS  =    0.0;
  const MAX_PX_POS  = 5000.0;  // pixels — generous ceiling for any canvas size
  const MIN_PX_SIZE =    8;    // pixels — smallest icon
  const MAX_PX_SIZE =  500;    // pixels — largest icon
  const MIN_ROT     =   0;
  const MAX_ROT     = 359;
  const MIN_Z       =   1;
  const MAX_Z       = 100;

  // ============================================================
  // CONSTRUCTOR
  // ============================================================

  public function __construct(array $args = [])
  {
    $this->id_staplot_pele = isset($args['id_staplot_pele']) ? (int)   $args['id_staplot_pele'] : null;
    $this->x_pos_pele      = isset($args['x_pos_pele'])      ? (float) $args['x_pos_pele']      : 0.0;
    $this->y_pos_pele      = isset($args['y_pos_pele'])      ? (float) $args['y_pos_pele']      : 0.0;
    $this->rotation_pele   = isset($args['rotation_pele'])   ? (int)   $args['rotation_pele']   : 0;
    $this->z_index_pele    = isset($args['z_index_pele'])    ? (int)   $args['z_index_pele']    : 1;
    $this->px_size_pele    = isset($args['px_size_pele'])    ? (int)   $args['px_size_pele']    : 48;
    $this->src_pele        =        $args['src_pele']        ?? '';
    $this->name_pele       =        $args['name_pele']       ?? '';
    $this->flipped_pele    = isset($args['flipped_pele'])    ? (int)   $args['flipped_pele']    : 0;
  }

  // ============================================================
  // FINDERS
  // ============================================================

  /**
   * Returns all elements for a given stage plot, ordered by z_index ascending
   * so lower layers are rendered first.
   *
   * @param int $staplot_id
   * @return static[]
   */
  static public function find_by_plot(int $staplot_id): array
  {
    $sql = "SELECT * FROM " . static::$table_name
      . " WHERE id_staplot_pele = ?"
      . " ORDER BY z_index_pele ASC";
    return static::find_by_sql($sql, [$staplot_id]);
  }

  // ============================================================
  // BULK OPERATIONS
  // ============================================================

  /**
   * Deletes all elements belonging to a stage plot.
   *
   * Use this before re-inserting a plot's full element set on save, rather than
   * diffing individual rows. Simpler and avoids stale elements after a drag-and-drop.
   *
   * @param int $staplot_id
   * @return bool
   */
  static public function delete_by_plot(int $staplot_id): bool
  {
    $sql  = "DELETE FROM " . static::$table_name . " WHERE id_staplot_pele = ?";
    $stmt = self::$database->prepare($sql);
    return $stmt->execute([$staplot_id]);
  }

  // ============================================================
  // VALIDATION
  // ============================================================

  protected function validate(): array
  {
    $this->errors = [];

    if (is_null($this->id_staplot_pele) || $this->id_staplot_pele < 1) {
      $this->errors[] = 'A valid stage plot is required.';
    }

    $this->x_pos_pele = (float) $this->x_pos_pele;
    if ($this->x_pos_pele < self::MIN_PX_POS || $this->x_pos_pele > self::MAX_PX_POS) {
      $this->errors[] = 'X position must be between ' . self::MIN_PX_POS . ' and ' . self::MAX_PX_POS . ' px.';
    }

    $this->y_pos_pele = (float) $this->y_pos_pele;
    if ($this->y_pos_pele < self::MIN_PX_POS || $this->y_pos_pele > self::MAX_PX_POS) {
      $this->errors[] = 'Y position must be between ' . self::MIN_PX_POS . ' and ' . self::MAX_PX_POS . ' px.';
    }

    $this->rotation_pele = (int) $this->rotation_pele;
    if ($this->rotation_pele < self::MIN_ROT || $this->rotation_pele > self::MAX_ROT) {
      $this->errors[] = 'Rotation must be between ' . self::MIN_ROT . ' and ' . self::MAX_ROT . ' degrees.';
    }

    $this->z_index_pele = (int) $this->z_index_pele;
    if ($this->z_index_pele < self::MIN_Z || $this->z_index_pele > self::MAX_Z) {
      $this->errors[] = 'Z-index must be between ' . self::MIN_Z . ' and ' . self::MAX_Z . '.';
    }

    $this->px_size_pele = (int) $this->px_size_pele;
    if ($this->px_size_pele < self::MIN_PX_SIZE || $this->px_size_pele > self::MAX_PX_SIZE) {
      $this->errors[] = 'Icon size must be between ' . self::MIN_PX_SIZE . ' and ' . self::MAX_PX_SIZE . ' px.';
    }

    if ($this->src_pele === '') {
      $this->errors[] = 'Element source path is required.';
    }

    if ($this->name_pele === '') {
      $this->errors[] = 'Element name is required.';
    }

    return $this->errors;
  }
}
