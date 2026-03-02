<?php
/**
 * user.class.php
 * Represents a user account in user_usr.
 *
 * created_at_usr / updated_at_usr are excluded from $db_columns
 * because MySQL manages them automatically via DEFAULT and ON UPDATE.
 *
 * Autoloaded as: User → user.class.php
 *
 * @author Alyssa Companioni
 */

class User extends DatabaseObject
{
  static protected $table_name = 'user_usr';
  static protected $pk         = 'id_usr';
  static protected $db_columns = [
    'id_usr',
    'first_name_usr',
    'last_name_usr',
    'email_usr',
    'phone_usr',
    'username_usr',
    'password_hash_usr',
    'role_usr',
    'is_active_usr',
  ];

  public string  $first_name_usr    = '';
  public string  $last_name_usr     = '';
  public string  $email_usr         = '';
  public ?string $phone_usr         = null;
  public string  $username_usr      = '';
  public string  $password_hash_usr = '';
  public string  $role_usr          = 'member';
  public int     $is_active_usr     = 1;

  // ============================================================
  // FINDERS
  // ============================================================

  /**
   * Returns all users with role 'member', ordered by last name then first name.
   *
   * @return static[]
   */
  static public function find_all_members(): array
  {
    $sql = "SELECT * FROM " . static::$table_name
         . " WHERE role_usr = 'member'"
         . " ORDER BY last_name_usr ASC, first_name_usr ASC";
    return static::find_by_sql($sql);
  }

  /**
   * Returns all members and admins (excludes super_admin), ordered by role
   * descending (admins first) then by last name and first name.
   *
   * @return static[]
   */
  static public function find_members_and_admins(): array
  {
    $sql = "SELECT * FROM " . static::$table_name
         . " WHERE role_usr IN ('member', 'admin')"
         . " ORDER BY role_usr DESC, last_name_usr ASC, first_name_usr ASC";
    return static::find_by_sql($sql);
  }

  // ============================================================
  // ACTIONS
  // ============================================================

  /**
   * Flips is_active_usr between 1 and 0, then persists the change.
   *
   * @return bool True on success.
   */
  public function toggle_active(): bool
  {
    $this->is_active_usr = $this->is_active_usr ? 0 : 1;
    return $this->save();
  }

  // ============================================================
  // VALIDATION
  // ============================================================

  protected function validate(): array
  {
    $this->errors = [];

    if (empty(trim($this->first_name_usr))) {
      $this->errors[] = 'First name is required.';
    }
    if (empty(trim($this->last_name_usr))) {
      $this->errors[] = 'Last name is required.';
    }
    if (empty(trim($this->email_usr))) {
      $this->errors[] = 'Email is required.';
    }
    if (empty(trim($this->username_usr))) {
      $this->errors[] = 'Username is required.';
    }

    return $this->errors;
  }
}
