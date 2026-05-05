<?php

/**
 * Session.class.php
 * Manages session state for StagePlotter.
 *
 * Stores user identity and role from user_usr after login.
 * Provides role-aware access checks using a numeric hierarchy
 * so that higher roles automatically pass lower-level checks.
 *
 * Roles (low → high): public (unauthenticated), member, admin, super_admin.
 * 'public' is not stored in the DB — it simply means is_logged_in() === false.
 *
 * Usage (after initialize.php is loaded):
 *   $session = new Session();
 *   $session->login($user_row);        // $user_row = PDO assoc fetch
 *   $session->is_logged_in();          // bool — false means public/guest
 *   $session->has_role('admin');       // bool — admins pass, members don't
 *   $session->require_role('member');  // redirects to login if not met
 *   $session->logout();
 */

class Session
{

  private int     $user_id;
  public  string  $username = '';
  public  string  $first_name = '';
  private string  $role = '';
  private int     $last_login = 0;

  // Session expires after 24 hours of inactivity 
  public const MAX_LOGIN_AGE = 60 * 60 * 24;

  // Role hierarchy — higher number passes all lower-level has_role() checks.
  private const ROLE_HIERARCHY = [
    'member'      => 1,
    'admin'       => 2,
    'super_admin' => 3,
  ];

  // Constructor
  public function __construct()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
      $this->restore_from_session();
    }
  }

  // Public: Auth

  /**
   * Logs in a user from a PDO associative array row.
   * Call this after password_verify() succeeds in login.php
   * 
   * @param array $user Associative array from user_usr fetch.
   * @return bool
   */
  public function login(array $user): bool
  {
    session_regenerate_id(true);  // prevent session fixation

    $this->user_id    =  $user['id_usr'];
    $this->username   =  $user['username_usr'];
    $this->first_name =  $user['first_name_usr'];
    $this->role       =  $user['role_usr'];
    $this->last_login =  time();

    $_SESSION['user_id']    = $this->user_id;
    $_SESSION['username']   = $this->username;
    $_SESSION['first_name'] = $this->first_name;
    $_SESSION['role']       = $this->role;
    $_SESSION['last_login'] = $this->last_login;

    return true;
  }

  /**
   * Returns true if a user is logged in AND the session is still fresh.
   * 
   * @return bool
   */
  public function is_logged_in(): bool
  {
    return isset($this->user_id) && $this->session_is_fresh();
  }

  /**
   * Returns true if the logged-in user meets or exceeds the given role.
   * Higher roles automatically pass all lower-level checks.
   *
   * @param string $required_role 'member', 'admin', or 'super_admin'
   * @return bool
   */
  public function has_role(string $required_role): bool
  {
    if (!$this->is_logged_in()) {
      return false;
    }

    $user_level     = self::ROLE_HIERARCHY[$this->role]    ??  0;
    $required_level = self::ROLE_HIERARCHY[$required_role] ??  99;

    return $user_level >= $required_level;
  }

  /**
   * Enforces a minimum role. Redirects to login.php if not met.
   * Use at the top of every protected page.
   *
   * @param string $required_role 'member', 'admin', or 'super_admin'
   * @return void
   */
  public function require_role(string $required_role): void
  {
    if (!$this->has_role($required_role)) {
      header('Location: /login.php');
      exit;
    }
  }

  /**
   * Destroys all session data and the session cookie.
   * 
   * @return bool
   */
  public function logout(): bool
  {
    // Clear superglobal:
    $_SESSION = [];

    // Expire the cookie in the browser
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
      );
    }

    session_destroy();

    // Clear instance properties
    unset($this->user_id, $this->last_login);
    $this->username   = '';
    $this->first_name = '';
    $this->role       = '';

    return true;
  }

  // Public: Getters

  /**
   * @return int|null
   */
  public function get_user_id(): ?int
  {
    return $this->user_id ?? null;
  }

  /**
   * @return string 'member', 'admin', 'super_admin', or '' (public/guest)
   */
  public function get_role(): string
  {
    return $this->role;
  }

  // Public: Flash Message

  /**
   * Get or set a one-time flash message.
   * Pass a string to set; call with no argument to get and clear.
   * 
   * @param string $msg
   * @return string|bool  Returns the message string on get, true on set.
   */
  public function message(string $msg = ''): string|bool
  {
    if ($msg !== '') {
      $_SESSION['message'] = $msg;
      return true;
    }

    $stored = $_SESSION['message'] ?? '';
    unset($_SESSION['message']);  //auto-clear on read
    return $stored;
  }

  // Private Helpers

  /**
   * Reinstates instance properties from an existing session.
   * Called in __construct() so every page load restores previous state.
   * 
   * @return void
   */
  private function restore_from_session(): void
  {
    if (isset($_SESSION['user_id'])) {
      $this->user_id    = (int) $_SESSION['user_id'];
      $this->username   =       $_SESSION['username']   ?? '';
      $this->first_name =       $_SESSION['first_name'] ?? '';
      $this->role       =       $_SESSION['role']       ?? '';
      $this->last_login = (int) ($_SESSION['last_login'] ?? 0);
    }
  }

  /**
   * Returns false if the session has been idle longer than MAX_LOGIN_AGE
   */
  private function session_is_fresh(): bool
  {
    if (empty($this->last_login)) {
      return false;
    }
    return ($this->last_login + self::MAX_LOGIN_AGE) >= time();
  }
}
