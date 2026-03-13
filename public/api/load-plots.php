<?php

/**
 * load-plots.php
 * Returns a summary list of all active stage plots owned by the logged-in user.
 *
 * Method:  GET
 * Auth:    member or higher (session)
 * Returns: JSON { success: true, plots: [ { id, title, gig_date, venue } ] }
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('member');

header('Content-Type: application/json');

$plots = StagePlot::find_by_user($session->get_user_id());

$list = array_map(function (StagePlot $p): array {
  return [
    'id'       => $p->id,
    'title'    => $p->title_staplot,
    'gig_date' => db_date_to_display($p->gig_date_staplot),
    'venue'    => $p->venue_staplot,
  ];
}, $plots);

echo json_encode(['success' => true, 'plots' => $list]);

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
