<?php
/**
 * manage-members.php
 * Lists all member accounts and lets admins activate or deactivate them.
 *
 * Requires: admin role or higher.
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('admin');

// ── Handle activate/deactivate POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $user_id = (int) $_POST['user_id'];
  $member = User::find_by_id($user_id);

  if ($member && $member->role_usr === 'member') {
    if ($member->toggle_active()) {
      $label = $member->is_active_usr ? 'activated' : 'deactivated';
      $session->message(esc($member->first_name_usr) . '\'s account has been ' . $label . '.');
    } else {
      $session->message('Could not update member status. Please try again.');
    }
  } else {
    $session->message('Member not found.');
  }

  header('Location: manage-members.php');
  exit();
}

// ── Fetch members ─────────────────────────────────────────────────────────────
$members = User::find_all_members();
$flash = $session->message();
?>

<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <title>Manage Members | Stage Plotter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage organization members, roles, and access permissions.">
    <link rel="stylesheet" href="/css/styles.css">
    <script src="/js/utils.js" defer></script>
    <script src="/js/manage-members.js" defer></script>
  </head>

  <body>
    <?php require_once '../includes/header.php'; ?>
    <div class="dashboard-wrapper">
      <main>
        <h1>Manage Members</h1>

        <?php if ($flash !== '') { ?>
          <div class="flash-message">
            <span><?= esc($flash) ?></span>
            <button type="button" class="msg-close-btn" aria-label="Dismiss">&times;</button>
          </div>
        <?php } ?>

        <input type="search" id="member-search" class="table-search" placeholder="Search" autocomplete="off" aria-label="Search members">

        <?php if (empty($members)) { ?>
          <p>No members found.</p>
        <?php } else { ?>
          <table>
            <thead>
              <tr>
                <th data-col="name">Name</th>
                <th data-col="username">Username</th>
                <th data-col="email">Email</th>
                <th data-col="status">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($members as $member) { ?>
                <tr>
                  <td data-label="Name"><?= esc($member->first_name_usr . ' ' . $member->last_name_usr) ?></td>
                  <td data-label="Username"><?= esc($member->username_usr) ?></td>
                  <td data-label="Email"><?= esc($member->email_usr) ?></td>
                  <td data-label="Status">
                    <div class="status-cell">
                      <form method="post" class="status-toggle-form">
                        <input type="hidden" name="user_id" value="<?= $member->id ?>">
                        <input type="checkbox" class="status-toggle" role="switch"
                          <?= $member->is_active_usr ? 'checked' : '' ?>
                          aria-label="<?= $member->is_active_usr
                            ? 'Active — click to deactivate'
                            : 'Inactive — click to activate' ?>">
                      </form>
                      <span class="status-label"><?= $member->is_active_usr ? 'Active' : 'Inactive' ?></span>
                    </div>
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
</html>

