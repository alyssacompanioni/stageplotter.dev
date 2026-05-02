<?php

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
