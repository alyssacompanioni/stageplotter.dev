<?php
/**
 * logout.php
 * Destroys the session via the Session class and redirects home.
 * 
 * @author Alyssa Companioni
 */

require_once __DIR__ . '/../private/initialize.php';
// $session is available

$session->logout();

header('Location: /index.php');
exit;
