<?php

/**
 * load-plot.php
 * Returns the full data for a single stage plot, including all canvas elements.
 *
 * Method:  GET
 * Params:  ?id=<plot_id>
 * Auth:    member or higher (session) — plot must be owned by the logged-in user
 * Returns: JSON {
 *   success: true,
 *   plot_id, title, gig_date, venue,
 *   elements: [ { src, label, x, y, rotation, size, flipped, z_index } ]
 * }
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('member');

header('Content-Type: application/json');

$plot_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($plot_id < 1) {
  json_error('Missing or invalid plot id');
}

$plot = StagePlot::find_owned_by($plot_id, $session->get_user_id());
if (!$plot) {
  json_error('Plot not found', 404);
}

$elements = array_map(fn(PlotElement $el) => $el->to_array(), PlotElement::find_by_plot($plot->id));

// ── Fetch inputs (channels + details) ─────────────────────────────────────
$stmt = $db->prepare("
  SELECT il.notes_inplst, ic.channel_num_inplstch, ic.label_inplstch
  FROM input_list_inplst il
  LEFT JOIN input_list_channel_inplstch ic ON ic.id_inplst_inplstch = il.id_inplst
  WHERE il.id_staplot_inplst = ?
  ORDER BY ic.channel_num_inplstch ASC
");
$stmt->execute([$plot->id]);
$input_rows = $stmt->fetchAll();

$details  = '';
$channels = [];
foreach ($input_rows as $row) {
  if ($details === '' && $row['notes_inplst'] !== null) {
    $details = $row['notes_inplst'];
  }
  if ($row['channel_num_inplstch'] !== null) {
    $channels[] = [
      'num'   => (int)    $row['channel_num_inplstch'],
      'label' => (string) $row['label_inplstch'],
    ];
  }
}

echo json_encode([
  'success'   => true,
  'plot_id'   => $plot->id,
  'title'     => $plot->title_staplot,
  'gig_date'  => db_date_to_display($plot->gig_date_staplot),
  'venue'     => $plot->venue_staplot,
  'is_public' => (bool) $plot->is_public_staplot,
  'elements'  => $elements,
  'inputs'    => ['channels' => $channels, 'details' => $details],
]);
