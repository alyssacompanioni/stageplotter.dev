<?php

/**
 * share-plot.php
 * Generates (or returns an existing) share token for a saved plot.
 *
 * Method:  POST
 * Auth:    member or higher (session) — plot must be owned by the caller
 * Body:    JSON { "plot_id": int }
 * Returns: JSON { success: true, url: string }
 *               { success: false, error: string }
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('member');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$plot_id = isset($body['plot_id']) ? (int) $body['plot_id'] : 0;

if ($plot_id < 1) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid plot ID']);
  exit;
}

$plot = StagePlot::find_owned_by($plot_id, $session->get_user_id());
if (!$plot) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Plot not found or access denied']);
  exit;
}

// Return existing token if one already exists for this plot
$stmt = $db->prepare("SELECT share_token_shrplot FROM shared_plot_shrplot WHERE id_staplot_shrplot = ? LIMIT 1");
$stmt->execute([$plot->id]);
$token = $stmt->fetchColumn();

if (!$token) {
  $token = bin2hex(random_bytes(32)); // 64-char hex token
  $db->prepare("INSERT INTO shared_plot_shrplot (id_staplot_shrplot, share_token_shrplot) VALUES (?, ?)")
     ->execute([$plot->id, $token]);
}

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$url    = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/view-plot.php?token=' . $token;

echo json_encode(['success' => true, 'url' => $url]);
