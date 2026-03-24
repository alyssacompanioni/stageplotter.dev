<?php
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('super_admin');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Dashboard | Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="dashboard-wrapper">
    <main>
      <h1>Super-Admin Dashboard</h1>
      <div class="dashboard-links">
        <a href="../stage-plotter.php">
          <div class="dashboard-btn">Build a Stage Plot</div>
        </a>
        <a href="manage-users.php">
          <div class="dashboard-btn">Manage Users</div>
        </a>
        <a href="../manage-library.php">
          <div class="dashboard-btn">Manage Stage Plot Images</div>
        </a>
        <a href="../profile.php">
          <div class="dashboard-btn">My Profile</div>
        </a>
      </div>
    </main>
</body>
