<?php

session_start();
require_once __DIR__ . '/../private/db_connection.php';

$errors = [];
$username = '';
$password = '';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  //Sanitizes inputs  
  $username = htmlspecialchars(trim($_POST['username'] ?? ''));
  $password = $_POST['password'] ?? '';
  
}

 {

  // Validations
  if (is_blank($username)) {
    $errors[] = "Username cannot be blank.";
  }
  if (is_blank($password)) {
    $errors[] = "Password cannot be blank.";
  }

  // No errors => Log in
  if (empty($errors)) {
  } else {
    // Username  not found OR password does not match
    $errors[] = "Username and/or Password not found. Try again.";
  }
}

$page_title = 'Log in';
?>

<h1>Log in</h1>
