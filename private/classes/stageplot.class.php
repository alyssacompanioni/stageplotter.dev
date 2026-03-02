<?php
/**
 * stageplot.class.php
 * Represents a stage plot record in stage_plot_staplot.
 *
 * Column names, property names, and $db_columns all match the SQL schema exactly.
 * $pk overrides DatabaseObject's default so the ORM uses id_staplot as the WHERE key.
 *
 * Excluded from $db_columns (auto-managed by MySQL):
 *   created_at_staplot  — DEFAULT current_timestamp()
 *   updated_at_staplot  — DEFAULT current_timestamp() ON UPDATE current_timestamp()
 *
 * @author Alyssa Companioni
 */

class StagePlot extends DatabaseObject
{
  static protected $table_name = 'stage_plot_staplot';
  static protected $pk         = 'id_staplot';
  static protected $db_columns = [
    'id_staplot',
    'id_usr_staplot',
    'title_staplot',
    'gig_date_staplot',
    'description_staplot',
    'width_staplot',
    'depth_staplot',
    'is_active_staplot',
  ];

  // Properties match SQL column names exactly so instantiate() maps them automatically.
  public ?int    $id_usr_staplot       = null;
  public string  $title_staplot        = '';
  public string  $gig_date_staplot     = '';   // 'YYYY-MM-DD'
  public ?string $description_staplot  = null;
  public float   $width_staplot        = 50.00;
  public float   $depth_staplot        = 40.00;
  public int     $is_active_staplot    = 1;

  // Validation limits
  const MIN_DIM      = 10.0;
  const MAX_WIDTH    = 200.0;
  const MAX_DEPTH    = 150.0;
  const MAX_TITLE    = 50;
  const MAX_DESC     = 255;

  // ============================================================
  // CONSTRUCTOR
  // ============================================================

  public function __construct(array $args = [])
  {
    $this->id_usr_staplot      = isset($args['id_usr_staplot'])      ? (int)   $args['id_usr_staplot']      : null;
    $this->title_staplot       = $args['title_staplot']              ?? '';
    $this->gig_date_staplot    = $args['gig_date_staplot']           ?? '';
    $this->description_staplot = $args['description_staplot']        ?? null;
    $this->width_staplot       = isset($args['width_staplot'])       ? (float) $args['width_staplot']       : 50.00;
    $this->depth_staplot       = isset($args['depth_staplot'])       ? (float) $args['depth_staplot']       : 40.00;
    $this->is_active_staplot   = isset($args['is_active_staplot'])   ? (int)   $args['is_active_staplot']   : 1;
  }

  // ============================================================
  // FINDERS
  // ============================================================

  /**
   * Returns all active stage plots owned by the given user, sorted by title.
   *
   * @param int $user_id
   * @return static[]
   */
  static public function find_by_user(int $user_id): array
  {
    $sql = "SELECT * FROM " . static::$table_name
         . " WHERE id_usr_staplot = ? AND is_active_staplot = 1"
         . " ORDER BY title_staplot ASC";
    return static::find_by_sql($sql, [$user_id]);
  }

  /**
   * Finds a plot by primary key only if it is owned by the given user.
   * Returns false if not found or if ownership does not match.
   *
   * @param int $id
   * @param int $user_id
   * @return static|false
   */
  static public function find_owned_by(int $id, int $user_id): static|false
  {
    $sql     = "SELECT * FROM " . static::$table_name
             . " WHERE id_staplot = ? AND id_usr_staplot = ? LIMIT 1";
    $results = static::find_by_sql($sql, [$id, $user_id]);
    return !empty($results) ? $results[0] : false;
  }

  // ============================================================
  // SOFT DELETE
  // ============================================================

  /**
   * Marks the plot inactive instead of removing the row.
   * Use this instead of delete() to preserve referential integrity
   * with plot_element_pele, input_list_inplst, etc.
   *
   * @return bool
   */
  public function soft_delete(): bool
  {
    $this->is_active_staplot = 0;
    return $this->save();
  }

  // ============================================================
  // VALIDATION
  // ============================================================

  protected function validate(): array
  {
    $this->errors = [];

    $title = trim($this->title_staplot);
    if ($title === '') {
      $this->errors[] = 'Title is required.';
    } elseif (strlen($title) > self::MAX_TITLE) {
      $this->errors[] = 'Title must be ' . self::MAX_TITLE . ' characters or fewer.';
    } else {
      $this->title_staplot = $title;
    }

    $date = trim($this->gig_date_staplot);
    if ($date === '') {
      $this->errors[] = 'Gig date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate(
        (int) substr($date, 5, 2),
        (int) substr($date, 8, 2),
        (int) substr($date, 0, 4)
      )) {
      $this->errors[] = 'Gig date must be a valid date (YYYY-MM-DD).';
    } else {
      $this->gig_date_staplot = $date;
    }

    if ($this->description_staplot !== null && strlen($this->description_staplot) > self::MAX_DESC) {
      $this->errors[] = 'Description must be ' . self::MAX_DESC . ' characters or fewer.';
    }

    $this->width_staplot = (float) $this->width_staplot;
    if ($this->width_staplot < self::MIN_DIM || $this->width_staplot > self::MAX_WIDTH) {
      $this->errors[] = 'Stage width must be between ' . self::MIN_DIM . ' and ' . self::MAX_WIDTH . ' ft.';
    }

    $this->depth_staplot = (float) $this->depth_staplot;
    if ($this->depth_staplot < self::MIN_DIM || $this->depth_staplot > self::MAX_DEPTH) {
      $this->errors[] = 'Stage depth must be between ' . self::MIN_DIM . ' and ' . self::MAX_DEPTH . ' ft.';
    }

    if (is_null($this->id_usr_staplot) || $this->id_usr_staplot < 1) {
      $this->errors[] = 'A valid user is required.';
    }

    return $this->errors;
  }
}
