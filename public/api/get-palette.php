<?php

/**
 * get-palette.php
 * Returns the icon src paths and labels for a given instrument category.
 *
 * Method:  GET
 * Params:  ?category=guitars
 * Auth:    member or higher (session)
 * Returns: JSON { success: true, icons: [ { src, label } ] }
 */

require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('member');

header('Content-Type: application/json');

$valid_categories = ['guitars', 'drums', 'keys', 'strings', 'brass', 'winds', 'percussion', 'misc'];
$category         = $_GET['category'] ?? '';

if (!in_array($category, $valid_categories, true)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid category']);
  exit;
}

$dir   = $_SERVER['DOCUMENT_ROOT'] . '/assets/instruments/' . $category . '/';
$files = glob($dir . '*.{svg,png}', GLOB_BRACE) ?: [];
sort($files);

$icons = array_map(function (string $file) use ($category): array {
  $filename = basename($file);
  $label    = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
  return [
    'src'   => '/assets/instruments/' . $category . '/' . $filename,
    'label' => $label,
  ];
}, $files);

echo json_encode(['success' => true, 'icons' => $icons]);
