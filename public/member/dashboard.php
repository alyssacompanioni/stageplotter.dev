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
            <div class="element-type">
              <!-- Load instrument and equipment icons here when their respective palettes are active -->
              <button id="guitars-button-toggle" class="btn btn-secondary" aria-label="Toggle guitar icons">Guitars</button>
              <button id="percussion-button-toggle" class="btn btn-secondary" aria-label="Toggle percussion icons">Percussion</button>
              <button id="keys-button-toggle" class="btn btn-secondary" aria-label="Toggle keyboard icons">Keys</button>
              <button id="strings-button-toggle" class="btn btn-secondary" aria-label="Toggle strings icons">Strings</button>
              <button id="winds-button-toggle" class="btn btn-secondary" aria-label="Toggle wind icons">Winds</button>
              <button id="amps-button-toggle" class="btn btn-secondary" aria-label="Toggle amp icons">Amps</button>
              <button id="misc-button-toggle" class="btn btn-secondary" aria-label="Toggle miscellaneous icons">Misc</button>
              <!-- Display input list if input palette is active -->
            </div>
            <div class="element-card-container">
              <!-- This is where draggable instrument/equipment icons and input list items will be rendered -->
              <div class="element-card">
                <img src="/assets/instruments/guitars/acoustic-guitar.svg" alt="Acoustic Guitar Icon." width="48" height="48">
                <p>Acoustic Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/acoustic-sunburst-guitar.svg" alt="Acoustic Sunburst Guitar Icon." width="48" height="48">
                <p>Acoustic Sunburst Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/lp-electric-guitar.svg" alt="Les PaulElectric Guitar Icon." width="48" height="48">
                <p>Electric Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/strat-electric-guitar.svg" alt="Strat Electric Guitar Icon." width="48" height="48">
                <p>Strat Electric Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/flying-v-electric-guitar.svg" alt="Flying V Electric Guitar Icon." width="48" height="48">
                <p>Flying V Electric Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/wooden-strat-electric-guitar.svg" alt="Wooden Strat Electric Guitar Icon." width="48" height="48">
                <p>Wooden Strat Electric Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/four-string-bass-guitar.svg" alt="Four String Bass Guitar Icon." width="48" height="48">
                <p>Four String Bass Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/five-string-bass-guitar.svg" alt="Five String Bass Guitar Icon." width="48" height="48">
                <p>Five String Bass Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/six-string-bass-guitar.svg" alt="Six String Bass Guitar Icon." width="48" height="48">
                <p>Six String Bass Guitar</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/banjo.svg" alt="Banjo Icon." width="48" height="48">
                <p>Banjo</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/mandolin.svg" alt="Mandolin Icon." width="48" height="48">
                <p>Mandolin</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/ukulele.svg" alt="Ukulele Icon." width="48" height="48">
                <p>Ukulele</p>
              </div>
              <div class="element-card">
                <img src="/assets/instruments/guitars/lute.svg" alt="Lute Icon." width="48" height="48">
                <p>Lute</p>
              </div>
            </div>
        </section>
      </main>
    </div>
    <?php require_once '../includes/footer.php'; ?>
  </body>
