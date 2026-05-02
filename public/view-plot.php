<?php

/**
 * plot.php
 * Public read-only view of a shared stage plot. No login required.
 *
 * Query param: ?token=<share_token>
 */

require_once __DIR__ . '/../private/initialize.php';

$token = trim($_GET['token'] ?? '');

if (!$token) {
  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

// Resolve token → plot
$stmt = $db->prepare("
  SELECT s.id_staplot, s.title_staplot, s.gig_date_staplot, s.venue_staplot
  FROM   shared_plot_shrplot sh
  JOIN   stage_plot_staplot  s  ON s.id_staplot = sh.id_staplot_shrplot
  WHERE  sh.share_token_shrplot = ? AND s.is_active_staplot = 1
  LIMIT  1
");
$stmt->execute([$token]);
$plot = $stmt->fetch();

if (!$plot) {
  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

$plot_id = $plot['id_staplot'];

// Canvas elements
$el_stmt = $db->prepare("
  SELECT src_pele, name_pele, x_pos_pele, y_pos_pele,
         rotation_pele, px_size_pele, flipped_pele, z_index_pele
  FROM   plot_element_pele
  WHERE  id_staplot_pele = ?
  ORDER  BY z_index_pele ASC
");
$el_stmt->execute([$plot_id]);
$elements = array_map(fn($r) => [
  'src'      => $r['src_pele'],
  'label'    => $r['name_pele'],
  'x'        => (float) $r['x_pos_pele'],
  'y'        => (float) $r['y_pos_pele'],
  'rotation' => (int)   $r['rotation_pele'],
  'size'     => (int)   $r['px_size_pele'],
  'flipped'  => (bool)  $r['flipped_pele'],
  'z_index'  => (int)   $r['z_index_pele'],
], $el_stmt->fetchAll());

// Inputs
$inp_stmt = $db->prepare("
  SELECT il.notes_inplst, ic.channel_num_inplstch, ic.label_inplstch
  FROM   input_list_inplst il
  LEFT JOIN input_list_channel_inplstch ic ON ic.id_inplst_inplstch = il.id_inplst
  WHERE  il.id_staplot_inplst = ?
  ORDER  BY ic.channel_num_inplstch ASC
");
$inp_stmt->execute([$plot_id]);
$input_rows = $inp_stmt->fetchAll();

$details  = '';
$channels = [];
foreach ($input_rows as $row) {
  if ($details === '' && $row['notes_inplst'] !== null) {
    $details = $row['notes_inplst'];
  }
  if ($row['channel_num_inplstch'] !== null) {
    $channels[] = ['num' => (int) $row['channel_num_inplstch'], 'label' => $row['label_inplstch']];
  }
}

// Format date mm/dd/yyyy
$d    = $plot['gig_date_staplot'];
$date = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) ? "$m[2]/$m[3]/$m[1]" : $d;

$subtitle = implode(' — ', array_filter([$date, $plot['venue_staplot']]));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($plot['title_staplot']) ?> | Stage Plotter</title>
  <meta name="description" content="View the stage plot &ldquo;<?= esc($plot['title_staplot']) ?>&rdquo; on Stage Plotter.">
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
</head>

<body>
  <?php $show_back = true; require_once 'includes/header.php'; ?>

  <div class="wrapper">
    <main class="shared-plot-main">

      <div class="shared-plot-body<?= (!empty($channels) || $details) ? ' has-inputs' : '' ?>">

        <div class="shared-plot-header">
          <h1><?= esc($plot['title_staplot']) ?></h1>
          <?php if ($subtitle): ?>
            <p class="shared-plot-subtitle"><?= esc($subtitle) ?></p>
          <?php endif; ?>
        </div>

        <?php if (!empty($channels) || $details): ?>
          <aside class="shared-inputs">
            <?php if (!empty($channels)): ?>
              <h2>Input List</h2>
              <ol class="shared-channel-list">
                <?php foreach ($channels as $ch): ?>
                  <li>
                    <span class="shared-ch-num"><?= esc((string) $ch['num']) ?></span>
                    <span class="shared-ch-label"><?= esc($ch['label']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>

            <?php if ($details): ?>
              <div class="shared-details">
                <h3>Details</h3>
                <p><?= nl2br(esc($details)) ?></p>
              </div>
            <?php endif; ?>
          </aside>
        <?php endif; ?>

        <section class="stage-plot-canvas" id="shared-canvas">
          <!-- Elements injected by view-plot.js -->
        </section>

      </div>

    </main>
  </div>

  <?php require_once 'includes/footer.php'; ?>

  <script>
    window.PLOT_ELEMENTS = <?= json_encode($elements, JSON_HEX_TAG) ?>;
  </script>
  <script src="/js/view-plot.js"></script>
</body>

</html>
