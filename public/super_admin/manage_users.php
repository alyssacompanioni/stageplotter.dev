<?php
/**
 * manage_users.php
 * Lists all admin and member accounts and lets super_admins activate or
 * deactivate them.
 *
 * Requires: super_admin role.
 *
 * @author Alyssa Companioni
 */
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('super_admin');

// ── Handle activate/deactivate POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $user_id = (int) $_POST['user_id'];
  $user    = User::find_by_id($user_id);

  if ($user && in_array($user->role_usr, ['member', 'admin'])) {
    if ($user->toggle_active()) {
      $label = $user->is_active_usr ? 'activated' : 'deactivated';
      $session->message(htmlspecialchars($user->first_name_usr) . ' has been ' . $label . '.');
    } else {
      $session->message('Could not update user status. Please try again.');
    }
  } else {
    $session->message('User not found.');
  }

  header('Location: manage_users.php');
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
              <td><?= htmlspecialchars(ucfirst($user->role_usr)) ?></td>
              <td><?= $user->is_active_usr ? 'Active' : 'Inactive' ?></td>
              <td>
                <form method="post">
                  <input type="hidden" name="user_id" value="<?= $user->id ?>">
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
</body>
