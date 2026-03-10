<?php

/**
 * save_plot.php
 * Creates or updates a stage plot and replaces all of its canvas elements.
 *
 * Method:  POST
 * Auth:    member or higher (session)
 * Body:    JSON — see expected shape below
 * Returns: JSON { success, plot_id } on success
 *               { success: false, errors: [] } on validation failure
 *
 * Expected JSON body:
 * {
 *   "plot_id":  null | int,      // null = new plot, int = update existing
 *   "title":    string,          // required
 *   "gig_date": string,          // mm/dd/yyyy format — required
 *   "venue":    string | null,
 *   "elements": [
 *     {
 *       "src":      string,      // e.g. /assets/instruments/guitars/acoustic.svg
 *       "label":    string,      // display name — stored as name_pele
 *       "x":        number,      // canvas pixel position
 *       "y":        number,
 *       "rotation": number,      // degrees 0–359
 *       "size":     number,      // icon pixel size
 *       "flipped":  bool,
 *       "z_index":  number
 *     }
 *   ]
 * }
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('member');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
  exit;
}

$user_id = $session->get_user_id();
$plot_id = isset($body['plot_id']) ? (int) $body['plot_id'] : null;

// ── Find or create the StagePlot ───────────────────────────────────────────
if ($plot_id) {
  $plot = StagePlot::find_owned_by($plot_id, $user_id);
  if (!$plot) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Plot not found or access denied']);
    exit;
  }
} else {
  $plot                  = new StagePlot();
  $plot->id_usr_staplot  = $user_id;
}

$plot->title_staplot    = $body['title']    ?? '';
$plot->gig_date_staplot = $body['gig_date'] ?? '';
$plot->venue_staplot    = ($body['venue'] !== '' && $body['venue'] !== null) ? $body['venue'] : null;

if (!$plot->save()) {
  echo json_encode(['success' => false, 'errors' => $plot->get_errors()]);
  exit;
}

// ── Replace all elements for this plot ────────────────────────────────────
PlotElement::delete_by_plot($plot->id);

foreach (($body['elements'] ?? []) as $el) {
  $element = new PlotElement([
    'id_staplot_pele' => $plot->id,
    'x_pos_pele'      => (float) ($el['x']        ?? 0),
    'y_pos_pele'      => (float) ($el['y']        ?? 0),
    'rotation_pele'   => (int)   ($el['rotation'] ?? 0),
    'z_index_pele'    => max(1, min(100, (int) ($el['z_index'] ?? 1))),
    'px_size_pele'    => (int)   ($el['size']     ?? 48),
    'src_pele'        =>          $el['src']      ?? '',
    'type_pele'       => type_from_src($el['src'] ?? ''),
    'name_pele'       =>          $el['label']    ?? '',
    'flipped_pele'    => (int) (bool) ($el['flipped'] ?? false),
  ]);
  $element->save();
}

echo json_encode(['success' => true, 'plot_id' => $plot->id]);

// ── Helper ─────────────────────────────────────────────────────────────────

/**
 * Derives the ENUM type value from a canvas element's SVG src path.
 * Expects paths like /assets/instruments/{category}/filename.svg.
 *
 * @param string $src
 * @return string  One of the VALID_TYPES values; falls back to 'Misc'.
 */
function type_from_src(string $src): string
{
  $map = [
    'guitars'    => 'Guitar',
    'percussion' => 'Percussion',
    'keys'       => 'Keys',
    'strings'    => 'Strings',
    'winds'      => 'Winds',
    'amps'       => 'Amps',
    'misc'       => 'Misc',
  ];

  $category = basename(dirname($src));  // e.g. "guitars" from ".../instruments/guitars/..."
  return $map[$category] ?? 'Misc';
}
