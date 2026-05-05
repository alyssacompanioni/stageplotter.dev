<?php
/**
 * stage-plotter.php
 * Main stage plot creation interface with drag-and-drop canvas and building tools.
 *
 * Requires: member role or higher.
 */

require_once __DIR__ . '/../private/initialize.php';
$session->require_role('member');
?>

<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <title>Build a Stage Plot | Stage Plotter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Build a stage plot for your band or live event. Drag and drop instruments, equipment, and labels to design your setup.">
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="/js/stage-plotter.js" defer></script>
  </head>

  <body>
    <?php require_once 'includes/header.php'; ?>
    <div class="wrapper" id="stage-plotter-wrapper">
      <main>
        <h1>Stage Plotter Dashboard</h1>
        <div class="canvas-container">
          <header id="plot-toolbar">
            <div class="plot-meta-fields" id="plot-meta-fields">
              <label for="plot-title"><span class="sr-only">Title (required)</span>
                <input type="text" id="plot-title" class="plot-field" placeholder="Plot title *" maxlength="50" required>
              </label>
              <label for="plot-gig-date"><span class="sr-only">Gig Date (required, mm/dd/yyyy)</span>
                <input type="text" id="plot-gig-date" class="plot-field" placeholder="Gig Date: mm/dd/yyyy *" maxlength="10">
              </label>
              <label for="plot-venue"><span class="sr-only">Venue (optional)</span>
                <input type="text" id="plot-venue" class="plot-field" placeholder="Venue (optional)" maxlength="100">
              </label>
            </div>
            <span id="autosave-status" class="autosave-status" aria-live="polite"></span>
            <div class="plot-settings">
              <input type="checkbox" id="plot-toolbar-toggle" class="dropdown-menu-checkbox">
              <label for="plot-toolbar-toggle" class="dropdown-menu-toggle" >
                <img src="/assets/icons/tools.svg" alt="Plot actions menu" width="24" height="24">
              </label>
              <ul class="dropdown-menu">
                <li><button id="new-plot-btn" class="btn">New Plot</button></li>
                <li><button id="my-plots-btn" class="btn">My Plots</button></li>
                <li><button id="save-plot-btn" class="btn">Save Plot</button></li>
                <li><button id="share-plot-btn" class="btn">Share Plot</button></li>
                <li><button id="export-plot-btn" class="btn">Export Plot</button></li>
                <li><button id="print-plot-btn" class="btn">Print Plot</button></li>
                <li><button id="clear-stage-btn" class="btn btn-ghost">Clear Stage</button></li>
                <li class="toolbar-visibility-item">
                  <label for="plot-public-toggle" class="toolbar-visibility-label">
                    <span class="toggle-label">Publish:</span>
                    <input type="checkbox" id="plot-public-toggle" role="switch">
                  </label>
                </li>
              </ul>
            </div>
          </header>
          <div class="stage-plot-canvas">
            <!-- This is where the stage plot will be rendered -->
          </div>
        </div>

        <div class="palette-container">
          <header>
            <button id="instrument-palette-toggle" class="btn" aria-label="Toggle instrument palette">Instruments</button>
            <button id="equipment-palette-toggle" class="btn" aria-label="Toggle equipment palette">Equipment</button>
            <button id="input-palette-toggle" class="btn" aria-label="Toggle input palette">Inputs</button>
          </header>
          <div class="palette">
            <div class="element-type" id="instrument-subcategories">
              <?php
              foreach (INSTRUMENT_CATEGORIES as $slug => $label):

              	$dir = __DIR__ . '/assets/instruments/' . $slug . '/';
              	$files = glob($dir . '*.{svg,png}', GLOB_BRACE) ?: [];
              	sort($files);
              	$icon_src = !empty($files) ? '/assets/instruments/' . $slug . '/' . basename($files[0]) : null;
              	?>
                <button type="button"
                  value="<?= esc($slug) ?>"
                  data-palette-type="instruments"
                  class="btn element-type-btn"
                  aria-label="Show <?= esc($label) ?> icons">
                  <?php if ($icon_src): ?>
                    <img src="<?= esc($icon_src) ?>" alt="" width="32" height="32" aria-hidden="true">
                  <?php endif; ?>
                  <span><?= esc($label) ?></span>
                </button>
              <?php
              endforeach;
              ?>
            </div>
            <div class="element-type" id="equipment-subcategories" hidden>
              <?php
              foreach (EQUIPMENT_CATEGORIES as $slug => $label):

              	$dir = __DIR__ . '/assets/equipment/' . $slug . '/';
              	$files = glob($dir . '*.{svg,png}', GLOB_BRACE) ?: [];
              	sort($files);
              	$icon_src = !empty($files) ? '/assets/equipment/' . $slug . '/' . basename($files[0]) : null;
              	?>
                <button type="button"
                  value="<?= esc($slug) ?>"
                  data-palette-type="equipment"
                  class="btn element-type-btn"
                  aria-label="Show <?= esc($label) ?> equipment icons">
                  <?php if ($icon_src): ?>
                    <img src="<?= esc($icon_src) ?>" alt="" width="32" height="32" aria-hidden="true">
                  <?php endif; ?>
                  <span><?= esc($label) ?></span>
                </button>
              <?php
              endforeach;
              ?>
            </div>
            <div class="element-card-container">
              <!-- Populated on page load by switchPalette() in stage-plotter.js -->
            </div>
          </div>

          <div id="inputs-panel" class="inputs-panel" hidden>
            <div class="inputs-panel-tabs">
              <button id="channels-tab-btn" class="btn inputs-tab-btn">Channels</button>
              <button id="details-tab-btn" class="btn inputs-tab-btn">Details</button>
            </div>
            <div id="channels-view">
              <ol id="channel-list" class="channel-list"></ol>
              <button id="add-channel-btn" class="btn">+ Add Channel</button>
            </div>
            <div id="details-view" hidden>
              <textarea id="inputs-details" aria-label="Input details." placeholder="Notes about gear details, musician info, etc."></textarea>
            </div>
          </div>
        </div> 
      </main>
    </div>
    <?php require_once 'includes/footer.php'; ?>

    <div id="share-modal" class="modal-overlay" hidden>
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title">
        <h2 id="share-modal-title">Share Plot</h2>
        <p>Anyone with this link can view your stage plot:</p>
        <div class="share-link-row">
          <input type="text" aria-label="share link" id="share-link-input" class="share-link-input" readonly>
          <button id="copy-link-btn" class="btn">Copy Link</button>
        </div>
        <button id="share-modal-close" class="btn btn-ghost">Close</button>
      </div>
    </div>

    <div id="my-plots-modal" class="modal-overlay" hidden>
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="my-plots-modal-title">
        <h2 id="my-plots-modal-title">My Plots</h2>
        <ul id="my-plots-list" class="my-plots-list">
          <!-- Populated by showMyPlots() in stage-plotter.js -->
        </ul>
        <button id="my-plots-cancel" class="btn btn-ghost">Cancel</button>
      </div>
    </div>
  </body>
</html>
