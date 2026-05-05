<?php
/**
 * initialize.php
 * Bootstraps the application: connects to the database, registers the class autoloader, and starts a hardened session.
 */

require_once('db_connection.php');
require_once __DIR__ . '/../vendor/autoload.php';

// Autoload class definitions
function my_autoload($class)
{
  if (preg_match('/\A\w+\Z/', $class)) {
    include('classes/' . strtolower($class) . '.class.php');
  }
}
spl_autoload_register('my_autoload');

// Use this if autoload fails:
// Manually loads all files in classes folder with a loop
// foreach (glob(__DIR__ . '/classes/*.class.php') as $file) {
//   require_once($file);
// }

DatabaseObject::set_database($db);

/**
 * HTML-escapes a string for safe output in any HTML context.
 * ENT_QUOTES escapes both " and ' (needed for single-quoted attribute values).
 * ENT_SUBSTITUTE replaces invalid UTF-8 sequences instead of returning ''.
 */
function esc(?string $str): string
{
  return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Converts a YYYY-MM-DD date string to mm/dd/yyyy for display. */
function db_date_to_display(string $db_date): string
{
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $db_date, $m)) {
    return $m[2] . '/' . $m[3] . '/' . $m[1];
  }
  return $db_date;
}

/** Sends a JSON error response and exits. */
function json_error(string $error, int $code = 400): never
{
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => $error]);
  exit;
}

/** Validates the shared user profile fields. Returns an array of error strings. */
function validate_user_fields(string $first_name, string $last_name, string $email, string $phone, string $username): array
{
  $errors = [];

  if ($first_name === '') {
    $errors[] = 'First name cannot be blank.';
  } elseif (strlen($first_name) > 50) {
    $errors[] = 'First name cannot exceed 50 characters.';
  }

  if ($last_name === '') {
    $errors[] = 'Last name cannot be blank.';
  } elseif (strlen($last_name) > 50) {
    $errors[] = 'Last name cannot exceed 50 characters.';
  }

  if ($email === '') {
    $errors[] = 'Email cannot be blank.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
  } elseif (strlen($email) > 100) {
    $errors[] = 'Email cannot exceed 100 characters.';
  }

  if ($phone !== '' && strlen($phone) > 20) {
    $errors[] = 'Phone number cannot exceed 20 characters.';
  }

  if ($username === '') {
    $errors[] = 'Username cannot be blank.';
  } elseif (strlen($username) > 20) {
    $errors[] = 'Username cannot exceed 20 characters.';
  } elseif (!preg_match('/\A[a-zA-Z0-9_]+\z/', $username)) {
    $errors[] = 'Username may only contain letters, numbers, and underscores.';
  }

  return $errors;
}

const INSTRUMENT_CATEGORIES = [
  'guitars'    => 'Guitars',
  'drums'      => 'Drums',
  'keys'       => 'Keys',
  'strings'    => 'Strings',
  'brass'      => 'Brass',
  'winds'      => 'Woodwinds',
  'percussion' => 'Percussion',
  'misc'       => 'Misc',
];

const EQUIPMENT_CATEGORIES = [
  'audio'     => 'Audio',
  'furniture' => 'Furniture',
  'lighting'  => 'Lighting',
  'misc'      => 'Misc',
];

// Harden the session cookie before session_start() fires inside new Session().
// httponly: blocks JS from reading the cookie, limiting XSS-based session theft.
// SameSite=Strict: blocks the cookie from being sent on cross-origin requests,
//   providing CSRF protection without a separate token on same-site forms.
// use_strict_mode: PHP rejects session IDs supplied by the client that don't
//   match an existing server-side session, blocking session fixation attempts.
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
  'httponly' => true,
  'samesite' => 'Strict',
]);

$session = new Session;
