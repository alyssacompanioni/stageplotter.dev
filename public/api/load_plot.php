<?php

/**
 * load_plot.php
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
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Missing or invalid plot id']);
  exit;
}

$plot = StagePlot::find_owned_by($plot_id, $session->get_user_id());
if (!$plot) {
  http_response_code(404);
  echo json_encode(['success' => false, 'error' => 'Plot not found']);
  exit;
}

$raw_elements = PlotElement::find_by_plot($plot->id);

$elements = array_map(function (PlotElement $el): array {
  return [
    'src'      => $el->src_pele,
    'label'    => $el->name_pele,
    'x'        => (float) $el->x_pos_pele,
    'y'        => (float) $el->y_pos_pele,
    'rotation' => (int)   $el->rotation_pele,
    'size'     => (int)   $el->px_size_pele,
    'flipped'  => (bool)  $el->flipped_pele,
    'z_index'  => (int)   $el->z_index_pele,
  ];
}, $raw_elements);

echo json_encode([
  'success'  => true,
  'plot_id'  => $plot->id,
  'title'    => $plot->title_staplot,
  'gig_date' => db_date_to_display($plot->gig_date_staplot),
  'venue'    => $plot->venue_staplot,
  'elements' => $elements,
]);

// ── Helper ─────────────────────────────────────────────────────────────────

/**
 * Converts a stored YYYY-MM-DD date string back to mm/dd/yyyy for display.
 *
 * @param string $db_date
 * @return string
 */
function db_date_to_display(string $db_date): string
{
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $db_date, $m)) {
    return $m[2] . '/' . $m[3] . '/' . $m[1];
  }
  return $db_date;
}
