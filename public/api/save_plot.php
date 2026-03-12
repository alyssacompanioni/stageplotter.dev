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

// ── Save inputs (channels + details) ──────────────────────────────────────
$inputs   = $body['inputs'] ?? [];
$details  = trim((string) ($inputs['details'] ?? '')) ?: null;
$channels = is_array($inputs['channels'] ?? null) ? $inputs['channels'] : [];

// Upsert the input_list row (unique per plot)
$db->prepare("
  INSERT INTO input_list_inplst (id_staplot_inplst, notes_inplst)
  VALUES (?, ?)
  ON DUPLICATE KEY UPDATE notes_inplst = VALUES(notes_inplst)
")->execute([$plot->id, $details]);

$list_id = $db->prepare("SELECT id_inplst FROM input_list_inplst WHERE id_staplot_inplst = ?");
$list_id->execute([$plot->id]);
$list_id = (int) $list_id->fetchColumn();

// Replace all channels for this input list
$db->prepare("DELETE FROM input_list_channel_inplstch WHERE id_inplst_inplstch = ?")
   ->execute([$list_id]);

$insert_ch = $db->prepare("
  INSERT INTO input_list_channel_inplstch (id_inplst_inplstch, channel_num_inplstch, label_inplstch)
  VALUES (?, ?, ?)
");
foreach ($channels as $ch) {
  $num   = (int) ($ch['num'] ?? 0);
  $label = substr(trim((string) ($ch['label'] ?? '')), 0, 100);
  if ($num < 1 || $num > 999) continue;
  $insert_ch->execute([$list_id, $num, $label]);
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
