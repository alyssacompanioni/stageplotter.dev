<?php

/**
 * manage-users.php
 * Lists all admin and member accounts and lets super_admins activate or
 * deactivate them.
 *
 * Requires: super_admin role.
 *
 * @author Alyssa Companioni
 */
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('super_admin');

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $user_id = (int) $_POST['user_id'];
  $user    = User::find_by_id($user_id);
  $action  = $_POST['action'] ?? 'toggle_active';

  if ($user && in_array($user->role_usr, ['member', 'admin'])) {
    if ($action === 'change_role') {
      $new_role = $_POST['new_role'] ?? '';
      if (in_array($new_role, ['member', 'admin'])) {
        $user->role_usr = $new_role;
        if ($user->save()) {
          $session->message(htmlspecialchars($user->first_name_usr) . '\'s role has been updated to ' . $new_role . '.');
        } else {
          $session->message('Could not update role. Please try again.');
        }
      } else {
        $session->message('Invalid role.');
      }
    } else {
      if ($user->toggle_active()) {
        $label = $user->is_active_usr ? 'activated' : 'deactivated';
        $session->message(htmlspecialchars($user->first_name_usr) . ' has been ' . $label . '.');
      } else {
        $session->message('Could not update user status. Please try again.');
      }
    }
  } else {
    $session->message('User not found.');
  }

  header('Location: manage-users.php');
  exit;
}

// ── Fetch members and admins ───────────────────────────────────────────────
$users = User::find_members_and_admins();
$flash = $session->message();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users | Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="dashboard-wrapper">
    <main>
      <h1>Manage Users</h1>

      <?php if ($flash !== '') { ?>
        <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
      <?php } ?>

      <?php if (empty($users)) { ?>
        <p>No users found.</p>
      <?php } else { ?>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user) { ?>
              <tr>
                <td><?= htmlspecialchars($user->first_name_usr . ' ' . $user->last_name_usr) ?></td>
                <td><?= htmlspecialchars($user->username_usr) ?></td>
                <td><?= htmlspecialchars($user->email_usr) ?></td>
                <td>
                  <form method="post">
                    <input type="hidden" name="user_id" value="<?= $user->id ?>">
                    <input type="hidden" name="action" value="change_role">
                    <select name="new_role" onchange="this.form.submit()">
                      <option value="member" <?= $user->role_usr === 'member' ? 'selected' : '' ?>>Member</option>
                      <option value="admin"  <?= $user->role_usr === 'admin'  ? 'selected' : '' ?>>Admin</option>
                    </select>
                  </form>
                </td>
                <td><?= $user->is_active_usr ? 'Active' : 'Inactive' ?></td>
                <td>
                  <form method="post">
                    <input type="hidden" name="user_id" value="<?= $user->id ?>">
                    <input type="hidden" name="action" value="toggle_active">
                    <button type="submit">
                      <?= $user->is_active_usr ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>

    </main>
  </div>
  <?php require_once '../includes/footer.php'; ?>
</body>
