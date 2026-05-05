<?php
/**
 * header.php
 * Sitewide header with navigation, logo, and personalized login/logout links.
 *
 * Requires: $session (provided by initialize.php on the parent page)
 */
$_nav_current = $_SERVER['PHP_SELF'];
function _nav_link(string $href, string $label): void {
  global $_nav_current;
  $current = $_nav_current === $href ? ' aria-current="page"' : '';
  echo "<li><a href=\"{$href}\"{$current}>{$label}</a></li>\n";
}
?>

<header>
  <div class="header-wrapper<?= isset($show_hero) && $show_hero ? ' hero-header' : '' ?>">
    <div class="header-left">
      <a href="/index.php" class="logo"><img src="/assets/brand/logo-final.svg" alt="Stage Plotter logo."></a>
      <?php if (isset($show_back) && $show_back): ?>
        <button class="back-btn" aria-label="Go back"><img src="/assets/icons/back.svg" alt="" width="20" height="20"></button>
      <?php endif; ?>
    </div>

    <nav aria-label="Main navigation">
      <ul>
        <?php if ($session->is_logged_in()) { ?>

          <li><span class="nav-greeting">Hi, <?= esc($session->first_name) ?></span></li>
          <li class="user-menu">
            <input type="checkbox" id="dropdown-menu-toggle" class="dropdown-menu-checkbox">
            <label for="dropdown-menu-toggle" class="dropdown-menu-toggle" aria-label="Open user menu">
              <img src="/assets/icons/user.svg" alt="User menu" width="24" height="24" class="user-icon">
            </label>

            <ul class="dropdown-menu">

              <?php if ($session->has_role('super_admin')) { ?>
                <?php _nav_link('/index.php', 'Home'); ?>
                <?php _nav_link('/super_admin/dashboard.php', 'Super Admin Dashboard'); ?>
                <?php _nav_link('/super_admin/manage-users.php', 'Manage Users'); ?>
                <?php _nav_link('/manage-library.php', 'Manage Library'); ?>
                <?php _nav_link('/stage-plotter.php', 'Stage Plotter'); ?>
                <?php _nav_link('/profile.php', 'My Profile'); ?>
                <?php _nav_link('/logout.php', 'Log Out'); ?>

              <?php } elseif ($session->has_role('admin')) { ?>
                <?php _nav_link('/index.php', 'Home'); ?>
                <?php _nav_link('/admin/dashboard.php', 'Admin Dashboard'); ?>
                <?php _nav_link('/admin/manage-members.php', 'Manage Members'); ?>
                <?php _nav_link('/manage-library.php', 'Manage Library'); ?>
                <?php _nav_link('/stage-plotter.php', 'Stage Plotter'); ?>
                <?php _nav_link('/profile.php', 'My Profile'); ?>
                <?php _nav_link('/logout.php', 'Log Out'); ?>

              <?php } elseif ($session->has_role('member')) { ?>
                <?php _nav_link('/index.php', 'Home'); ?>
                <?php _nav_link('/stage-plotter.php', 'Stage Plotter'); ?>
                <?php _nav_link('/profile.php', 'My Profile'); ?>
                <?php _nav_link('/logout.php', 'Log Out'); ?>
              <?php } ?>

            </ul>
          </li>

        <?php } elseif (!isset($show_hero) || !$show_hero) { ?>
          <li><a href="/login.php">Log In</a></li>
          <li><a href="/register.php">Sign Up</a></li>
        <?php } ?>

      </ul>
    </nav>
  </div>
</header>
<script src="/js/header.js" defer></script>

