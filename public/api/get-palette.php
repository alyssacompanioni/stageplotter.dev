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

$valid_instrument_categories = ['guitars', 'drums', 'keys', 'strings', 'brass', 'winds', 'percussion', 'misc'];
$valid_equipment_categories  = ['audio', 'furniture', 'lighting', 'misc'];

$type     = $_GET['type'] ?? 'instruments';
$category = $_GET['category'] ?? '';

if ($type === 'equipment') {
  if (!in_array($category, $valid_equipment_categories, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category']);
    exit;
  }
  $asset_base = '/assets/equipment/';
} else {
  if (!in_array($category, $valid_instrument_categories, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category']);
    exit;
  }
  $asset_base = '/assets/instruments/';
}

$dir   = $_SERVER['DOCUMENT_ROOT'] . $asset_base . $category . '/';
$files = glob($dir . '*.{svg,png}', GLOB_BRACE) ?: [];
sort($files);

$icons = array_map(function (string $file) use ($category, $asset_base): array {
  $filename = basename($file);
  $label    = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
  return [
    'src'   => $asset_base . $category . '/' . $filename,
    'label' => $label,
  ];
}, $files);

echo json_encode(['success' => true, 'icons' => $icons]);
