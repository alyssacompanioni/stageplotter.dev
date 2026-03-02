<?php

/**
 * manage_members.php
 * Lists all member accounts and lets admins activate or deactivate them.
 *
 * Requires: admin role or higher.
 *
 * @author Alyssa Companioni
 */
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('admin');

// ── Handle activate/deactivate POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $user_id = (int) $_POST['user_id'];
  $member  = User::find_by_id($user_id);

  if ($member && $member->role_usr === 'member') {
    if ($member->toggle_active()) {
      $label = $member->is_active_usr ? 'activated' : 'deactivated';
      $session->message(htmlspecialchars($member->first_name_usr) . ' has been ' . $label . '.');
    } else {
      $session->message('Could not update member status. Please try again.');
    }
  } else {
    $session->message('Member not found.');
  }

  header('Location: manage_members.php');
  exit;
}

// ── Fetch members ─────────────────────────────────────────────────────────────
$members = User::find_all_members();
$flash   = $session->message();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Members | Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <main>
    <h1>Manage Members</h1>

    <?php if ($flash !== '') { ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php } ?>

    <?php if (empty($members)) { ?>
      <p>No members found.</p>
    <?php } else { ?>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $member) { ?>
            <tr>
              <td><?= htmlspecialchars($member->first_name_usr . ' ' . $member->last_name_usr) ?></td>
              <td><?= htmlspecialchars($member->username_usr) ?></td>
              <td><?= htmlspecialchars($member->email_usr) ?></td>
              <td><?= $member->is_active_usr ? 'Active' : 'Inactive' ?></td>
              <td>
                <form method="post">
                  <input type="hidden" name="user_id" value="<?= $member->id ?>">
                  <button type="submit">
                    <?= $member->is_active_usr ? 'Deactivate' : 'Activate' ?>
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
