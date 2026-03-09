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
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <script src="/js/dashboard.js" defer></script>
  </head>

  <body>
    <?php require_once '../includes/header.php'; ?>
    <div class="wrapper" id="dashboard-wrapper">
      <main>
        <h1>Stage Plotter Dashboard</h1>
        <div class="canvas-container">
          <header id="plot-toolbar">
            <div class="plot-meta-fields">
              <input type="text" id="plot-title" class="plot-field" placeholder="Plot title *" maxlength="50">
              <input type="text" id="plot-gig-date" class="plot-field" placeholder="Gig Date: mm/dd/yyyy *" maxlength="10">
              <input type="text" id="plot-venue" class="plot-field" placeholder="Venue (optional)" maxlength="100">
            </div>
            <div class="plot-settings">
              <input type="checkbox" id="plot-toolbar-toggle" class="dropdown-menu-checkbox">
              <label for="plot-toolbar-toggle" class="dropdown-menu-toggle" aria-label="Open plot actions menu">
                <img src="/assets/icons/gear.svg" alt="Plot actions" width="24" height="24">
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
          <div class="stage-plot-canvas">
            <!-- This is where the stage plot will be rendered -->
          </div>
        </div>
        <section class="palette-container">
          <header>
            <button id="instrument-palette-toggle" class="btn btn-secondary" aria-label="Toggle instrument palette">Instruments</button>
            <button id="equipment-palette-toggle" class="btn btn-secondary" aria-label="Toggle equipment palette">Equipment</button>
            <button id="input-palette-toggle" class="btn btn-secondary" aria-label="Toggle input palette">Inputs</button>
          </header>
          <div class="palette">
            <form method="get" class="element-type">
              <!-- Load instrument and equipment icons here when their respective palettes are active -->
              <button type="submit" name="element_type" id="guitars-button-toggle" value="guitars" class="btn btn-secondary" aria-label="Toggle guitar icons">Guitars</button>
              <button type="submit" name="element_type" id="percussion-button-toggle" value="percussion" class="btn btn-secondary" aria-label="Toggle percussion icons">Percussion</button>
              <button type="submit" name="element_type" id="keys-button-toggle" value="keys" class="btn btn-secondary" aria-label="Toggle keyboard icons">Keys</button>
              <button type="submit" name="element_type" id="strings-button-toggle" value="strings" class="btn btn-secondary" aria-label="Toggle strings icons">Strings</button>
              <button type="submit" name="element_type" id="winds-button-toggle" value="winds" class="btn btn-secondary" aria-label="Toggle wind icons">Winds</button>
              <button type="submit" name="element_type" id="amps-button-toggle" value="amps" class="btn btn-secondary" aria-label="Toggle amp icons">Amps</button>
              <button type="submit" name="element_type" id="misc-button-toggle" value="misc" class="btn btn-secondary" aria-label="Toggle miscellaneous icons">Misc</button>
              <!-- Display input list if input palette is active -->
            </form>
            <div class="element-card-container">
              <!-- This is where draggable instrument/equipment icons and input list items will be rendered -->
              <?php
                $active_element_type = $_GET['element_type'] ?? 'guitars';
                $valid_categories = ['guitars', 'percussion', 'keys', 'strings', 'winds', 'amps', 'misc'];

                if (in_array($active_element_type, $valid_categories)):
                  $category_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/instruments/' . $active_element_type . '/';
                  $category_files = glob($category_dir . '*.{svg,png}', GLOB_BRACE) ?: [];
                  sort($category_files);
                  foreach ($category_files as $file):
                    $filename = basename($file);
                    $label = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
                ?>
                <div class="element-card">
                  <img src="/assets/instruments/<?= htmlspecialchars($active_element_type) ?>/<?= htmlspecialchars($filename) ?>" alt="<?= htmlspecialchars($label) ?> Icon." width="48" height="48">
                  <p><?= htmlspecialchars($label) ?></p>
                </div>
                <?php
                  endforeach;
                endif;
              ?>
            </div>
        </section>
      </main>
    </div>
    <?php require_once '../includes/footer.php'; ?>
  </body>
