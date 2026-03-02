<?php
/**
 * plotelement.class.php
 * Represents a single element placed on a stage plot canvas.
 *
 * Each PlotElement belongs to one stage plot (id_staplot_pele) and carries
 * its physical position, size, rotation, stacking order, and references to
 * its type (id_etyp_pele) and display name (id_enam_pele).
 *
 * Position and size values are in feet, matching the stage plot's coordinate
 * system (width_staplot / depth_staplot). Rotation is in degrees (0–359).
 *
 * No timestamps — this table has no created_at / updated_at columns, so every
 * column is included in $db_columns.
 *
 * Autoloaded as: PlotElement → plotelement.class.php
 *
 * @author Alyssa Companioni
 */

class PlotElement extends DatabaseObject
{

  static protected $table_name = 'plot_element_pele';
  static protected $pk         = 'id_pele';
  static protected $db_columns = [
    'id_pele',
    'id_staplot_pele',
    'id_etyp_pele',
    'x_pos_pele',
    'y_pos_pele',
    'rotation_pele',
    'width_pele',
    'depth_pele',
    'z_index_pele',
    'id_enam_pele',
  ];

  // Properties match SQL column names exactly so instantiate() maps them automatically.
  public ?int   $id_staplot_pele = null;
  public ?int   $id_etyp_pele    = null;
  public float  $x_pos_pele      = 0.0;
  public float  $y_pos_pele      = 0.0;
  public int    $rotation_pele   = 0;
  public float  $width_pele      = 1.0;
  public float  $depth_pele      = 1.0;
  public int    $z_index_pele    = 1;
  public ?int   $id_enam_pele    = null;

  // Validation limits — positions use the largest possible stage dimension as a ceiling.
  const MIN_POS   =   0.0;
  const MAX_POS   = 200.0;   // matches StagePlot::MAX_WIDTH
  const MIN_SIZE  =   0.5;   // feet — smallest an element can be
  const MAX_SIZE  =  50.0;   // feet — largest an element can be
  const MIN_ROT   =   0;
  const MAX_ROT   = 359;
  const MIN_Z     =   1;
  const MAX_Z     = 100;

  // ============================================================
  // CONSTRUCTOR
  // ============================================================

  public function __construct(array $args = [])
  {
    $this->id_staplot_pele = isset($args['id_staplot_pele']) ? (int)   $args['id_staplot_pele'] : null;
    $this->id_etyp_pele    = isset($args['id_etyp_pele'])    ? (int)   $args['id_etyp_pele']    : null;
    $this->x_pos_pele      = isset($args['x_pos_pele'])      ? (float) $args['x_pos_pele']      : 0.0;
    $this->y_pos_pele      = isset($args['y_pos_pele'])      ? (float) $args['y_pos_pele']      : 0.0;
    $this->rotation_pele   = isset($args['rotation_pele'])   ? (int)   $args['rotation_pele']   : 0;
    $this->width_pele      = isset($args['width_pele'])      ? (float) $args['width_pele']      : 1.0;
    $this->depth_pele      = isset($args['depth_pele'])      ? (float) $args['depth_pele']      : 1.0;
    $this->z_index_pele    = isset($args['z_index_pele'])    ? (int)   $args['z_index_pele']    : 1;
    $this->id_enam_pele    = isset($args['id_enam_pele'])    ? (int)   $args['id_enam_pele']    : null;
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

    if (is_null($this->id_etyp_pele) || $this->id_etyp_pele < 1) {
      $this->errors[] = 'A valid element type is required.';
    }

    if (is_null($this->id_enam_pele) || $this->id_enam_pele < 1) {
      $this->errors[] = 'A valid element name is required.';
    }

    $this->x_pos_pele = (float) $this->x_pos_pele;
    if ($this->x_pos_pele < self::MIN_POS || $this->x_pos_pele > self::MAX_POS) {
      $this->errors[] = 'X position must be between ' . self::MIN_POS . ' and ' . self::MAX_POS . ' ft.';
    }

    $this->y_pos_pele = (float) $this->y_pos_pele;
    if ($this->y_pos_pele < self::MIN_POS || $this->y_pos_pele > self::MAX_POS) {
      $this->errors[] = 'Y position must be between ' . self::MIN_POS . ' and ' . self::MAX_POS . ' ft.';
    }

    $this->rotation_pele = (int) $this->rotation_pele;
    if ($this->rotation_pele < self::MIN_ROT || $this->rotation_pele > self::MAX_ROT) {
      $this->errors[] = 'Rotation must be between ' . self::MIN_ROT . ' and ' . self::MAX_ROT . ' degrees.';
    }

    $this->width_pele = (float) $this->width_pele;
    if ($this->width_pele < self::MIN_SIZE || $this->width_pele > self::MAX_SIZE) {
      $this->errors[] = 'Element width must be between ' . self::MIN_SIZE . ' and ' . self::MAX_SIZE . ' ft.';
    }

    $this->depth_pele = (float) $this->depth_pele;
    if ($this->depth_pele < self::MIN_SIZE || $this->depth_pele > self::MAX_SIZE) {
      $this->errors[] = 'Element depth must be between ' . self::MIN_SIZE . ' and ' . self::MAX_SIZE . ' ft.';
    }

    $this->z_index_pele = (int) $this->z_index_pele;
    if ($this->z_index_pele < self::MIN_Z || $this->z_index_pele > self::MAX_Z) {
      $this->errors[] = 'Z-index must be between ' . self::MIN_Z . ' and ' . self::MAX_Z . '.';
    }

    return $this->errors;
  }
}
