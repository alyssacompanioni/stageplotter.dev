<?php
/**
 * logout.php
 * Destroys the user session and redirects to the homepage.
 */

require_once __DIR__ . '/../private/initialize.php';
// $session is available

$session->logout();

header('Location: /index.php');
exit;
