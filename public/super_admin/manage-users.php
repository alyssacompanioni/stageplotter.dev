<?php
/**
 * manage-users.php
 * Lists all admin and member accounts and lets super_admins activate or deactivate them.
 *
 * Requires: super_admin role.
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('super_admin');

// ── Handle bulk role update POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roles'])) {
	$updated = 0;
	foreach ((array) $_POST['roles'] as $user_id => $new_role) {
		if (!in_array($new_role, ['member', 'admin'])) {
			continue;
		}
		$user = User::find_by_id((int) $user_id);
		if (!$user || !in_array($user->role_usr, ['member', 'admin'])) {
			continue;
		}
		if ($user->role_usr === $new_role) {
			continue;
		}
		$user->role_usr = $new_role;
		if ($user->save()) {
			$updated++;
		}
	}
	$session->message($updated > 0 ? 'Roles updated.' : 'No changes made.');
	header('Location: manage-users.php');
	exit();
}

// ── Handle toggle active POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
	$user = User::find_by_id((int) $_POST['user_id']);
	if ($user && in_array($user->role_usr, ['member', 'admin'])) {
		if ($user->toggle_active()) {
			$label = $user->is_active_usr ? 'activated' : 'deactivated';
			$session->message(esc($user->first_name_usr) . ' has been ' . $label . '.');
		} else {
			$session->message('Could not update user status. Please try again.');
		}
	} else {
		$session->message('User not found.');
	}
	header('Location: manage-users.php');
	exit();
}

// ── Fetch members and admins ───────────────────────────────────────────────
$users = User::find_members_and_admins();
$flash = $session->message();
?>

<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <title>Manage Users | Stage Plotter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage all users across the platform, including roles and permissions.">
    <link rel="stylesheet" href="/css/styles.css">
    <script src="/js/utils.js" defer></script>
    <script src="/js/manage-users.js" defer></script>
  </head>

  <body>
    <?php require_once '../includes/header.php'; ?>
    <div class="dashboard-wrapper">
      <main>
        <h1>Manage Users</h1>

        <?php if ($flash !== '') { ?>
        <div class="flash-message">
          <span><?= esc($flash) ?></span>
          <button type="button" class="msg-close-btn" aria-label="Dismiss">&times;</button>
        </div>
        <?php } ?>

        <input type="search" id="user-search" class="table-search" placeholder="Search" autocomplete="off" aria-label="Search users">
        <form id="roles-form" method="post"></form>
        <?php if (empty($users)) { ?>
        <p>No users found.</p>
        <?php } else { ?>
        <table>
          <thead>
            <tr>
              <th data-col="name">Name</th>
              <th data-col="username">Username</th>
              <th data-col="email">Email</th>
              <th>Role</th>
              <th data-col="status">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user) { ?>
            <tr>
              <td data-label="Name"><?= esc($user->first_name_usr . ' ' . $user->last_name_usr) ?></td>
              <td data-label="Username"><?= esc($user->username_usr) ?></td>
              <td data-label="Email"><?= esc($user->email_usr) ?></td>
              <td data-label="Role">
                <select name="roles[<?= $user->id ?>]" form="roles-form"
                        aria-label="Role for <?= esc($user->first_name_usr . ' ' . $user->last_name_usr) ?>">
                  <option value="member" <?= $user->role_usr === 'member' ? 'selected' : '' ?>>Member</option>
                  <option value="admin" <?= $user->role_usr === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
              </td>
              <td data-label="Status">
                <div class="status-cell">
                  <form method="post" class="status-toggle-form">
                    <input type="hidden" name="user_id" value="<?= $user->id ?>">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="checkbox" class="status-toggle" role="switch"
                           <?= $user->is_active_usr ? 'checked' : '' ?>
                           aria-label="<?= $user->is_active_usr
                           	? 'Active — click to deactivate'
                           	: 'Inactive — click to activate' ?>">
                  </form>
                  <span class="status-label"><?= $user->is_active_usr ? 'Active' : 'Inactive' ?></span>
                </div>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <input type="submit" form="roles-form" class=" btn btn-update" value="Update" aria-label="Update user roles">
        <?php } ?>

      </main>
    </div>
    <?php require_once '../includes/footer.php'; ?>
  </body>

</html>
