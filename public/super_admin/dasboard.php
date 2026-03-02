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
  <main>
    <h1>Super Admin Dashboard</h1>
    <p>This will be the dashboard for superadmins, who will have complete stage-plot functionality, admin privileges, and the ability to activate/deactivate admins.</p>
  </main>
</body>
