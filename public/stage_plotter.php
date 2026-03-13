<?php
require_once __DIR__ . '/../private/initialize.php';
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="/js/stage_plotter.js" defer></script>
</head>

<body>
  <?php require_once '../includes/header.php'; ?>
  <div class="wrapper" id="stage-plotter-wrapper">
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
              <li><button id="new-plot-btn" class="btn">New Plot</button></li>
              <li><button id="load-plot-btn" class="btn">Load Plot</button></li>
              <li><button id="save-plot-btn" class="btn">Save Plot</button></li>
              <li><button id="share-plot-btn" class="btn">Share Plot</button></li>
              <li><button id="export-plot-btn" class="btn">Export Plot</button></li>
              <li><button id="print-plot-btn" class="btn">Print Plot</button></li>
              <li><button id="change-dimensions-btn" class="btn">Change Dimensions</button></li>
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
        <section class="stage-plot-canvas">
          <!-- This is where the stage plot will be rendered -->
        </section>
      </div>
      <section class="palette-container">
        <header>
          <button id="instrument-palette-toggle" class="btn" aria-label="Toggle instrument palette">Instruments</button>
          <button id="equipment-palette-toggle" class="btn" aria-label="Toggle equipment palette">Equipment</button>
          <button id="input-palette-toggle" class="btn" aria-label="Toggle input palette">Inputs</button>
        </header>
        <div class="palette">
          <div class="element-type">
            <?php
            $categories = [
              'guitars'    => 'Guitars',
              'percussion' => 'Percussion',
              'keys'       => 'Keys',
              'strings'    => 'Strings',
              'winds'      => 'Winds',
              'amps'       => 'Amps',
              'misc'       => 'Misc',
            ];
            foreach ($categories as $slug => $label):
              $dir        = $_SERVER['DOCUMENT_ROOT'] . '/assets/instruments/' . $slug . '/';
              $files      = glob($dir . '*.{svg,png}', GLOB_BRACE) ?: [];
              sort($files);
              $icon_src   = !empty($files)
                ? '/assets/instruments/' . $slug . '/' . basename($files[0])
                : null;
            ?>
              <button type="button"
                      value="<?= htmlspecialchars($slug) ?>"
                      class="btn element-type-btn"
                      aria-label="Show <?= htmlspecialchars($label) ?> icons">
                <?php if ($icon_src): ?>
                  <img src="<?= htmlspecialchars($icon_src) ?>" alt="" width="32" height="32" aria-hidden="true">
                <?php endif; ?>
                <span><?= htmlspecialchars($label) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
          <div class="element-card-container">
            <!-- Populated on page load by switchPalette() in stage_plotter.js -->
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
            <textarea id="inputs-details" placeholder="Notes about gear details, musician info, etc."></textarea>
          </div>
        </div>
      </section>
    </main>
  </div>
  <?php require_once '../includes/footer.php'; ?>

  <div id="share-modal" class="modal-overlay" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title">
      <h2 id="share-modal-title">Share Plot</h2>
      <p>Anyone with this link can view your stage plot:</p>
      <div class="share-link-row">
        <input type="text" id="share-link-input" class="share-link-input" readonly>
        <button id="copy-link-btn" class="btn">Copy</button>
      </div>
      <button id="share-modal-close" class="btn btn-ghost">Close</button>
    </div>
  </div>

  <div id="load-plot-modal" class="modal-overlay" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="load-modal-title">
      <h2 id="load-modal-title">Load Plot</h2>
      <ul id="load-plot-list" class="load-plot-list">
        <!-- Populated by showLoadModal() in stage_plotter.js -->
      </ul>
      <button id="load-modal-cancel" class="btn btn-ghost">Cancel</button>
    </div>
  </div>
</body>
