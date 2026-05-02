<?php

/**
 * profile.php
 * Allows any logged-in member (or higher) to view and update their account
 * information and change their password.
 *
 * @author Alyssa Companioni
 */

require_once __DIR__ . '/../private/initialize.php';
$session->require_role('member');

$user = User::find_by_id($session->get_user_id());
if (!$user) {
  header('Location: /logout.php');
  exit;
}

$profile_errors  = [];
$profile_success = false;
$password_errors = [];
$password_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ── Update profile info ────────────────────────────────────────────────────
  if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $username   = trim($_POST['username']   ?? '');

    // Field validation (mirrors register.php rules)
    if (empty($first_name)) {
      $profile_errors[] = 'First name cannot be blank.';
    } elseif (strlen($first_name) > 50) {
      $profile_errors[] = 'First name cannot exceed 50 characters.';
    }

    if (empty($last_name)) {
      $profile_errors[] = 'Last name cannot be blank.';
    } elseif (strlen($last_name) > 50) {
      $profile_errors[] = 'Last name cannot exceed 50 characters.';
    }

    if (empty($email)) {
      $profile_errors[] = 'Email cannot be blank.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $profile_errors[] = 'Please enter a valid email address.';
    } elseif (strlen($email) > 100) {
      $profile_errors[] = 'Email cannot exceed 100 characters.';
    }

    if (!empty($phone) && strlen($phone) > 20) {
      $profile_errors[] = 'Phone number cannot exceed 20 characters.';
    }

    if (empty($username)) {
      $profile_errors[] = 'Username cannot be blank.';
    } elseif (strlen($username) > 20) {
      $profile_errors[] = 'Username cannot exceed 20 characters.';
    } elseif (!preg_match('/\A[a-zA-Z0-9_]+\z/', $username)) {
      $profile_errors[] = 'Username may only contain letters, numbers, and underscores.';
    }

    // Uniqueness checks — exclude the current user's own row
    if (empty($profile_errors)) {
      $stmt = $db->prepare("SELECT id_usr FROM user_usr WHERE email_usr = ? AND id_usr != ? LIMIT 1");
      $stmt->execute([$email, $user->id]);
      if ($stmt->fetch()) {
        $profile_errors[] = 'An account with that email already exists.';
      }

      $stmt = $db->prepare("SELECT id_usr FROM user_usr WHERE username_usr = ? AND id_usr != ? LIMIT 1");
      $stmt->execute([$username, $user->id]);
      if ($stmt->fetch()) {
        $profile_errors[] = 'That username is already taken.';
      }
    }

    if (empty($profile_errors)) {
      $user->first_name_usr = $first_name;
      $user->last_name_usr  = $last_name;
      $user->email_usr      = $email;
      $user->phone_usr      = !empty($phone) ? $phone : null;
      $user->username_usr   = $username;

      if ($user->save()) {
        // Keep session in sync with the updated name and username
        $_SESSION['first_name'] = $user->first_name_usr;
        $_SESSION['username']   = $user->username_usr;
        $profile_success = true;
      } else {
        $profile_errors = array_merge($profile_errors, $user->get_errors());
      }
    }
  }

  // ── Change password ────────────────────────────────────────────────────────
  if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_password, $user->password_hash_usr)) {
      $password_errors[] = 'Current password is incorrect.';
    } elseif (empty($new_password)) {
      $password_errors[] = 'New password cannot be blank.';
    } elseif (strlen($new_password) < 8) {
      $password_errors[] = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
      $password_errors[] = 'Passwords do not match.';
    } else {
      $user->password_hash_usr = password_hash($new_password, PASSWORD_DEFAULT);
      if ($user->save()) {
        $password_success = true;
      } else {
        $password_errors = $user->get_errors();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | Stage Plotter</title>
  <meta name="description" content="Manage your Stage Plotter profile and account settings.">
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
  <script src="/js/forms.js" defer></script>
</head>

<body>
  <?php require_once 'includes/header.php'; ?>
  <div class="wrapper">
    <main class="profile-main">
      <h1>My Profile</h1>

      <div class="profile-sections">

      <!-- ── Profile Info ──────────────────────────────────────────────── -->
      <section class="profile-section">
        <h2>Account Information</h2>

        <?php if ($profile_success): ?>
          <p class="form-success" role="status">Profile updated successfully.</p>
        <?php endif; ?>

        <?php if (!empty($profile_errors)): ?>
          <ul class="error" role="alert">
            <?php foreach ($profile_errors as $e): ?>
              <li><?= esc($e) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form action="/profile.php" method="post" id="profile-form">

          <label for="first_name">First Name <span class="required" aria-label="required">*</span></label><br>
          <input type="text" id="first_name" name="first_name"
            value="<?= esc($user->first_name_usr) ?>"
            maxlength="50" required autocomplete="given-name"><br>

          <label for="last_name">Last Name <span class="required" aria-label="required">*</span></label><br>
          <input type="text" id="last_name" name="last_name"
            value="<?= esc($user->last_name_usr) ?>"
            maxlength="50" required autocomplete="family-name"><br>

          <label for="email">Email <span class="required" aria-label="required">*</span></label><br>
          <input type="email" id="email" name="email"
            value="<?= esc($user->email_usr) ?>"
            maxlength="100" required autocomplete="email"><br>

          <label for="phone">Phone <span class="optional">(optional)</span></label><br>
          <input type="tel" id="phone" name="phone"
            value="<?= esc($user->phone_usr ?? '') ?>"
            maxlength="20" autocomplete="tel"><br>

          <label for="username">Username <span class="required" aria-label="required">*</span></label><br>
          <input type="text" id="username" name="username"
            value="<?= esc($user->username_usr) ?>"
            maxlength="20" required autocomplete="username"><br>

          <input type="submit" name="update_profile" value="Save Changes">
        </form>
      </section>

      <!-- ── Change Password ───────────────────────────────────────────── -->
      <section class="profile-section">
        <h2>Change Password</h2>

        <?php if ($password_success): ?>
          <p class="form-success" role="status">Password changed successfully.</p>
        <?php endif; ?>

        <?php if (!empty($password_errors)): ?>
          <ul class="error" role="alert">
            <?php foreach ($password_errors as $e): ?>
              <li><?= esc($e) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form action="/profile.php" method="post" id="password-form">

          <label for="current_password">Current Password <span class="required" aria-label="required">*</span></label><br>
          <input type="password" id="current_password" name="current_password"
            required autocomplete="current-password"><br>

          <label for="new_password">New Password <span class="required" aria-label="required">*</span></label><br>
          <input type="password" id="new_password" name="new_password"
            minlength="8" required autocomplete="new-password"><br>

          <label for="confirm_password">Confirm New Password <span class="required" aria-label="required">*</span></label><br>
          <input type="password" id="confirm_password" name="confirm_password"
            minlength="8" required autocomplete="new-password"><br>

          <input type="submit" name="change_password" value="Change Password">
        </form>
      </section>

      </div><!-- /.profile-sections -->
    </main>
  </div>
  <?php require_once 'includes/footer.php'; ?>
</body>

</html>
