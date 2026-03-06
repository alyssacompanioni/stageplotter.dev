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
  <script src="/js/dashboard.js" defer></script>
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="wrapper" id="dashboard-wrapper">
    <main>
      <h1>Stage Plotter Dashboard</h1>
      <div id="canvas-container">
        <header id="plot-toolbar">
          <div class="plot-meta-fields">
            <input type="text" id="plot-title" class="plot-field" placeholder="Plot title *" maxlength="50">
            <input type="text" id="plot-gig-date" class="plot-field" placeholder="Gig Date: mm/dd/yyyy *" maxlength="10">
            <input type="text" id="plot-venue" class="plot-field" placeholder="Venue (optional)" maxlength="100">
          </div>
          <div class="plot-settings">
            <input type="checkbox" id="plot-toolbar-toggle" class="dropdown-menu-checkbox">
            <label for="plot-toolbar-toggle" class="dropdown-menu-toggle" aria-label="Open plot actions menu">
              <img src="/assets/gear.svg" alt="Plot actions" width="24" height="24">
            </label>
            <ul class="dropdown-menu" id="plot-toolbar">
              <li><button id="new-plot-btn" class="btn btn-primary">New Plot</button></li>
              <li><button id="load-plot-btn" class="btn btn-secondary">Load Plot</button></li>
              <li><button id="save-plot-btn" class="btn btn-primary">Save Plot</button></li>
              <li><button id="share-plot-btn" class="btn btn-secondary">Share Plot</button></li>
              <li><button id="export-plot-btn" class="btn btn-secondary">Export Plot</button></li>
              <li><button id="print-plot-btn" class="btn btn-secondary">Print Plot</button></li>
              <li><button id="change-dimensions-btn" class="btn btn-secondary">Change Dimensions</button></li>
              <li><button id="clear-stage-btn" class="btn btn-ghost">Clear Stage</button></li>
            </ul>
          </div>
        </header>
        <div id="stage-plot-canvas">
          <!-- This is where the stage plot will be rendered -->
        </div>
      </div>
      <section id="palette">
        <header>
          <button id="instrument-palette-toggle" class="btn btn-secondary" aria-label="Toggle instrument palette">Instruments</button>
          <button id="equipment-palette-toggle" class="btn btn-secondary" aria-label="Toggle equipment palette">Equipment</button>
          <button id="input-palette-toggle" class="btn btn-secondary" aria-label="Toggle input palette">Inputs</button>
        </header>
        <!-- Load icons if instrument or equipment palette is active -->
        <!-- Display input list if input palette is active -->
      </section>
    </main>
  </div>
  <?php require_once '../includes/footer.php'; ?>
</body>
