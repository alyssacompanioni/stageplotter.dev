<?php

/**
 * save-plot.php
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
  json_error('Method not allowed', 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
  json_error('Invalid JSON');
}

$user_id = $session->get_user_id();
$plot_id = isset($body['plot_id']) ? (int) $body['plot_id'] : null;

// ── Find or create the StagePlot ───────────────────────────────────────────
if ($plot_id) {
  $plot = StagePlot::find_owned_by($plot_id, $user_id);
  if (!$plot) {
    json_error('Plot not found or access denied', 403);
  }
} else {
  $plot                  = new StagePlot();
  $plot->id_usr_staplot  = $user_id;
}

$plot->title_staplot    = $body['title']    ?? '';
$plot->gig_date_staplot = $body['gig_date'] ?? '';
$plot->venue_staplot    = ($body['venue'] !== '' && $body['venue'] !== null) ? $body['venue'] : null;
$plot->is_public_staplot = isset($body['is_public']) ? (int) (bool) $body['is_public'] : $plot->is_public_staplot;

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

// ── Auto-create share token when plot is made public ──────────────────────
if ($plot->is_public_staplot) {
  $stmt = $db->prepare("SELECT share_token_shrplot FROM shared_plot_shrplot WHERE id_staplot_shrplot = ? LIMIT 1");
  $stmt->execute([$plot->id]);
  if (!$stmt->fetchColumn()) {
    $token = bin2hex(random_bytes(32));
    $db->prepare("INSERT INTO shared_plot_shrplot (id_staplot_shrplot, share_token_shrplot) VALUES (?, ?)")
       ->execute([$plot->id, $token]);
  }
}

echo json_encode(['success' => true, 'plot_id' => $plot->id]);
