<?php

/**
 * get-public-plots.php
 * Returns all active stage plots for public browsing. No login required.
 *
 * Method:  GET
 * Auth:    none
 * Returns: JSON { success: true, plots: [ { title, gig_date, venue, created_by, token } ] }
 */

require_once __DIR__ . '/../../private/initialize.php';
/* require_once $_SERVER['DOCUMENT_ROOT'] . '/../private/initialize.php'; */

header('Content-Type: application/json');

$stmt = $db->prepare("
  SELECT
    s.title_staplot,
    s.gig_date_staplot,
    s.venue_staplot,
    u.username_usr,
    sh.share_token_shrplot
  FROM  stage_plot_staplot   s
  JOIN  user_usr             u  ON u.id_usr              = s.id_usr_staplot
  LEFT JOIN shared_plot_shrplot sh ON sh.id_staplot_shrplot = s.id_staplot
  WHERE s.is_active_staplot = 1
    AND s.is_public_staplot = 1
  ORDER BY s.title_staplot ASC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$plots = array_map(function (array $row): array {
  $d = $row['gig_date_staplot'];
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
    $d = $m[2] . '/' . $m[3] . '/' . $m[1];
  }
  return [
    'title'      => $row['title_staplot'],
    'gig_date'   => $d,
    'venue'      => $row['venue_staplot'] ?? '',
    'created_by' => $row['username_usr'],
    'token'      => $row['share_token_shrplot'],
  ];
}, $rows);

echo json_encode(['success' => true, 'plots' => $plots]);
