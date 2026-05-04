<?php
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('admin');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Stage Plotter</title>
  <meta name="description" content="Admin Dashboard — manage members and review platform activity.">
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="dashboard-wrapper">
    <main>
      <h1>Admin Dashboard</h1>
      <div class="dashboard-links">
        <a href="../stage-plotter.php">
          <div class="dashboard-btn">Build a Stage Plot</div>
        </a>
        <a href="manage-members.php">
          <div class="dashboard-btn">Manage Members</div>
        </a>
        <a href="../manage-library.php">
          <div class="dashboard-btn">Manage Stage Plot Images</div>
        </a>
        <a href="../profile.php">
          <div class="dashboard-btn">My Profile</div>
        </a>
      </div>
    </main>
  </div>
  <?php require_once '../includes/footer.php'; ?>
</body>

</html>
