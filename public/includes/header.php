<?php

/**
 * header.php
 * Sitewide header. Displays a personalized logout link
 * for logged-in users, or a login link for guests.
 *
 * Requires: $session (provided by initialize.php on the parent page)
 *
 * @author Alyssa Companioni
 */
?>

<header>
  <a href="/index.php" class="site-title">Stage Plotter</a>

  <nav aria-label="Main navigation">
    <ul>
      <li>Logo Here</li>
      <?php if ($session->is_logged_in()) { ?>
        <li><span class="nav-greeting">Hi, <?= htmlspecialchars($session->first_name) ?></span></li>

        <?php if ($session->has_role('super_admin')) { ?>
          <li><a href="../index.php">Home</a></li>
          <li><a href="/super_admin/dashboard.php">Super Admin Dashboard</a></li>
          <li><a href="/super_admin/manage_users.php">Manage Users</a></li>
          <li><a href="../manage_library.php">Manage Library</a></li>
          <li><a href="/logout.php">Log Out</a></li>

        <?php } elseif ($session->has_role('admin')) { ?>
          <li><a href="../index.php">Home</a></li>
          <li><a href="/admin/dashboard.php">Admin Dashboard</a></li>
          <li><a href="/admin/manage_members.php">Manage Members</a></li>
          <li><a href="../manage_library.php">Manage Library</a></li>
          <li><a href="/logout.php">Log Out</a></li>

        <?php } elseif ($session->has_role('member')) { ?>
          <li><a href="../index.php">Home</a></li>
          <li><a href="/member/dashboard.php">Member Dashboard</a></li>
          <li><a href="/logout.php">Log Out</a></li>
        <?php } ?>

      <?php } else { ?>
        <li><a href="/login.php">Log In</a></li>
        <li><a href="/register.php">Sign Up</a></li>
      <?php } ?>

    </ul>
  </nav>
</header>
