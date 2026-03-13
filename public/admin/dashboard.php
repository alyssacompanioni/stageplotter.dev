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
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="wrapper">
    <main>
      <h1>Admin Dashboard</h1>
      <a href="build-plot.php"><div>Build a Stage Plot</div></a>
      <a href="manage-members.php"><div>Manage Members</div></a>
      <a href="manage-library.php"><div>Manage Stage Plot Images</div></a>
    </main>
  </div>
</body>
