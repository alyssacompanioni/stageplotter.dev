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

      <?php if ($session->is_logged_in()) { ?>

        <?php if ($session->has_role('super_admin')) { ?>
          <li><a href="/super_admin/dashboard.php">Super Admin</a></li>
        <?php } elseif ($session->has_role('admin')) { ?>
          <li><a href="/admin/dashboard.php">Admin Dashboard</a></li>
        <?php } elseif ($session->has_role('member')) { ?>
          <li><a href="/member/dashboard.php">Dashboard</a></li>
        <?php } ?>

        <li>
          <span class="nav-greeting">Hi, <?= htmlspecialchars($session->first_name) ?></span>
        </li>
        <li><a href="/logout.php">Log Out</a></li>

      <?php } else { ?>

        <li><a href="/login.php">Log In</a></li>
        <li><a href="/register.php">Sign Up</a></li>

      <?php } ?>

    </ul>
  </nav>
</header>
