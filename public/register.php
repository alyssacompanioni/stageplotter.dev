<?php

/**
 * register.php
 * Handles display and processing of the new user registration form.
 * On success, sets a flash message and redirects to login.php.
 *
 * @author Alyssa Companioni
 */

require_once __DIR__ . '/../private/initialize.php';

// If already logged in, no need to register
if ($session->is_logged_in()) {
	header('Location: /index.php');
	exit();
}

$errors = [];
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Collect and trim inputs (never trim password)
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$phone = trim($_POST['phone'] ?? '');
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirm = $_POST['confirm_password'] ?? '';

	// ---- Validation ----

	if (empty($first_name)) {
		$errors[] = 'First name cannot be blank.';
	} elseif (strlen($first_name) > 50) {
		$errors[] = 'First name cannot exceed 50 characters.';
	}

	if (empty($last_name)) {
		$errors[] = 'Last name cannot be blank.';
	} elseif (strlen($last_name) > 50) {
		$errors[] = 'Last name cannot exceed 50 characters.';
	}

	if (empty($email)) {
		$errors[] = 'Email cannot be blank.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Please enter a valid email address.';
	} elseif (strlen($email) > 100) {
		$errors[] = 'Email cannot exceed 100 characters.';
	}

	if (!empty($phone) && strlen($phone) > 20) {
		$errors[] = 'Phone number cannot exceed 20 characters.';
	}

	if (empty($username)) {
		$errors[] = 'Username cannot be blank.';
	} elseif (strlen($username) > 20) {
		$errors[] = 'Username cannot exceed 20 characters.';
	} elseif (!preg_match('/\A[a-zA-Z0-9_]+\z/', $username)) {
		$errors[] = 'Username may only contain letters, numbers, and underscores.';
	}

	if (empty($password)) {
		$errors[] = 'Password cannot be blank.';
	} elseif (strlen($password) < 8) {
		$errors[] = 'Password must be at least 8 characters.';
	}

	if ($password !== $confirm) {
		$errors[] = 'Passwords do not match.';
	}

	// ---- Uniqueness checks (only if basic validation passed) ----

	if (empty($errors)) {
		$stmt = $db->prepare('SELECT id_usr FROM user_usr WHERE email_usr = ? LIMIT 1');
		$stmt->execute([$email]);
		if ($stmt->fetch()) {
			$errors[] = 'An account with that email already exists.';
		}

		$stmt = $db->prepare('SELECT id_usr FROM user_usr WHERE username_usr = ? LIMIT 1');
		$stmt->execute([$username]);
		if ($stmt->fetch()) {
			$errors[] = 'That username is already taken.';
		}
	}

	// ---- Insert (only if all validation passed) ----

	if (empty($errors)) {
		$password_hash = password_hash($password, PASSWORD_DEFAULT);
		$phone_val = !empty($phone) ? $phone : null;

		$stmt = $db->prepare("INSERT INTO user_usr
                            (first_name_usr, last_name_usr, email_usr, phone_usr,
                             username_usr, password_hash_usr)
                          VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->execute([$first_name, $last_name, $email, $phone_val, $username, $password_hash]);

		$session->message('Account created! You can now log in.');
		header('Location: /login.php');
		exit();
	}
}
?>

<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <title>Sign Up | Stage Plotter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create a free Stage Plotter account to start building and sharing stage plots.">
    <link rel="stylesheet" href="css/styles.css">
    <script src="/js/forms.js" defer></script>
  </head>

  <body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <div class="wrapper">
      <main class="register-main">
        <h1>Create an Account</h1>

        <?php if (!empty($errors)) { ?>
          <div role="alert">
            <ul class="error">
              <?php foreach ($errors as $error) { ?>
                <li><?= esc($error) ?></li>
              <?php } ?>
            </ul>
          </div>
        <?php } ?>

        <form action="register.php" method="post" id="register-form">

          <label for="first_name">First Name <span class="required">*</span></label><br>
          <input type="text" id="first_name" name="first_name"
            value="<?= esc($first_name) ?>"
            maxlength="50" required autocomplete="given-name"><br>

          <label for="last_name">Last Name <span class="required">*</span></label><br>
          <input type="text" id="last_name" name="last_name"
            value="<?= esc($last_name) ?>"
            maxlength="50" required autocomplete="family-name"><br>

          <label for="email">Email <span class="required">*</span></label><br>
          <input type="email" id="email" name="email"
            value="<?= esc($email) ?>"
            maxlength="100" required autocomplete="email"><br>

          <label for="phone">Phone <span class="optional">(optional)</span></label><br>
          <input type="tel" id="phone" name="phone"
            value="<?= esc($phone) ?>"
            maxlength="20" autocomplete="tel"><br>

          <label for="username">Username <span class="required">*</span></label><br>
          <input type="text" id="username" name="username"
            value="<?= esc($username) ?>"
            maxlength="20" required autocomplete="username"><br>

          <label for="password">Password <span class="required">*</span></label><br>
          <input type="password" id="password" name="password"
            minlength="8" required autocomplete="new-password"><br>

          <label for="confirm_password">Confirm Password <span class="required">*</span></label><br>
          <input type="password" id="confirm_password" name="confirm_password"
            minlength="8" required autocomplete="new-password"><br>

          <input type="submit" value="Create Account">
        </form>

        <p>Already have an account? <a href="/login.php">Log In</a></p>
      </main>
    </div>
    <?php require_once 'includes/footer.php'; ?>
  </body>

</html>
