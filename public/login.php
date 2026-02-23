<?php
/**
 * Login page for StagePlotter.dev
 * Handles the display AND processing of the login form
 * Alyssa Companioni
 */

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
  // Sanitizes inputs  
  $username = htmlspecialchars(trim($_POST['username'] ?? ''));
  $password = $_POST['password'] ?? '';

  // Server-side validations
  if (empty($username)) {
    $errors[] = "Username cannot be blank.";
  } elseif (empty($password)) {
    $errors[] = "Password cannot be blank.";
  } else {
    // Query the database
    $stmt = $db->prepare("SELECT id_usr,
                                 username_usr,
                                 password_hash_usr,
                                 first_name_usr,
                                 role_usr
                           FROM user_usr
                           WHERE username_usr = ? 
                            AND is_active_usr = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password_hash_usr'])) {
      //Successful login - set session variables
      //Regenerate session ID to prevent session fixation attacks
      session_regenerate_id(true);
      
      $_SESSION['user_id'] =    $user['id_usr'];
      $_SESSION['username'] =   $user['username_usr'];
      $_SESSION['first_name'] = $user['first_name_usr'];
      $_SESSION['role'] =       $user['role_usr'];

      if($user['role_usr'] === 'admin') {
        header('Location: /admin/dashboard.php');
      } else {
        header('Location: /member/dashboard.php');
      }
      exit;
    } else {
      // Intentionally vague - don't reveal which field is wrong for security measures
      $error = 'Invalid username or password.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, intial-scale=1.0">
    <title>Log In | Stage Plotter</title>
  </head>
  <body>
    <!-- inlcude header here -->
    <main>
      <h1>Log In</h1>

      <?php if(!empty($error)): ?>
        <p class="error" role="alert"><?= $error ?></p>
      <?php endif; ?>

      <form action="login.php" method="post" id="login-form">
          <label for="username">Username <span class="required" aria-label="required">*</span></label>
          <input type="text" id="username" name="username" value="<?= $username ?>" required autocomplete="username">

          <label for="password">Password <span class="required" aria-label="required">*</span></label>
          <input type="text" id="password" name="password" required autocomplete="current-password">

          <input type="submit" value="Log in">
      </form>
    </main> 
    <!-- include footer here -->
    <!-- run js validation script here -->
  </body>
</html>
