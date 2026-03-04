<?php
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('member');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Dashboard | Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="wrapper">
    <main>
      <h1>Stage Plotter Dashboard</h1>
      <header>
        <div class="plot-meta-fields">
          <input type="text"  id="plot-title"    class="plot-field" placeholder="Plot title *"       maxlength="50">
          <input type="date"  id="plot-gig-date" class="plot-field" title="Gig date">
          <input type="text"  id="plot-venue"    class="plot-field" placeholder="Venue (optional)"   maxlength="100">
        </div>
        <div class="plot-toolbar-actions">
          <ul>
            <li><button id="clear-stage-btn" class="btn btn-ghost">Clear Stage</button></li>
            <li><button id="new-plot-btn" class="btn btn-primary">New Plot</button></li>
            <li><button id="load-plot-btn" class="btn btn-secondary">Load Plot</button></li>
            <li><button id="save-plot-btn"   class="btn btn-primary">Save Plot</button></li>
            <li><button id="share-plot-btn"  class="btn btn-secondary">Share Plot</button></li>
            <li><button id="export-plot-btn" class="btn btn-secondary">Export Plot</button></li>
            <li><button id="print-plot-btn"  class="btn btn-secondary">Print Plot</button></li>
            <li><button id="change-dimensions-btn" class="btn btn-secondary">Change Dimensions</button></li>
          </ul>
        </div>
      </header>
      <div id="stage-plot-canvas">
        <!-- This is where the stage plot will be rendered -->
      </div>
    </main>
  </div>
  <?php require_once '../includes/footer.php'; ?>
</body>
