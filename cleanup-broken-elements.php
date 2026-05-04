<?php
/**
 * cleanup-broken-elements.php
 *
 * Finds plot_element_pele rows whose src_pele points to an SVG that no
 * longer exists on disk and optionally deletes them.
 *
 * Usage:
 *   php cleanup-broken-elements.php            # dry run — lists broken elements
 *   php cleanup-broken-elements.php --delete   # deletes broken elements
 *
 * Run from the project root. Works on DDEV and production (db_connection.php
 * detects the environment automatically).
 */

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}

$delete_mode = in_array('--delete', $argv ?? [], true);

require_once __DIR__ . '/private/db_connection.php';

define('PUBLIC_DIR', __DIR__ . '/public');

// ── Fetch all elements with their plot title for context ──────────────────────
$rows = $db->query(
    "SELECT e.id_pele, e.src_pele, e.name_pele, e.id_staplot_pele,
            COALESCE(s.title_staplot, '[deleted plot]') AS plot_title
     FROM plot_element_pele e
     LEFT JOIN stage_plot_staplot s ON s.id_staplot = e.id_staplot_pele
     ORDER BY e.id_staplot_pele, e.id_pele"
)->fetchAll();

// ── Identify broken elements (missing asset file) ─────────────────────────────
$broken = [];
foreach ($rows as $row) {
    $file_path = PUBLIC_DIR . $row['src_pele'];
    if (!file_exists($file_path)) {
        $broken[] = $row;
    }
}

$total   = count($rows);
$n_broken = count($broken);

echo "Scanned {$total} plot element" . ($total === 1 ? '' : 's') . ".\n";

if ($n_broken === 0) {
    echo "No broken elements found. Nothing to do.\n";
    exit(0);
}

echo "Found {$n_broken} broken element" . ($n_broken === 1 ? '' : 's') . ":\n\n";

// ── Print details ─────────────────────────────────────────────────────────────
$col_w = [8, 30, 30, 35];   // id, plot, element name, src
$fmt   = "  %-{$col_w[0]}s  %-{$col_w[1]}s  %-{$col_w[2]}s  %s\n";
printf($fmt, 'ID', 'Plot', 'Element name', 'Missing src');
printf($fmt, str_repeat('-', $col_w[0]), str_repeat('-', $col_w[1]),
             str_repeat('-', $col_w[2]), str_repeat('-', $col_w[3]));

foreach ($broken as $row) {
    printf(
        $fmt,
        $row['id_pele'],
        mb_strimwidth($row['plot_title'],   0, $col_w[1] - 1, '…'),
        mb_strimwidth($row['name_pele'],    0, $col_w[2] - 1, '…'),
        $row['src_pele']
    );
}

echo "\n";

// ── Dry run vs delete ─────────────────────────────────────────────────────────
if (!$delete_mode) {
    echo "Dry run — no changes made.\n";
    echo "Run with --delete to remove these elements from the database.\n";
    exit(0);
}

$ids        = array_column($broken, 'id_pele');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt       = $db->prepare("DELETE FROM plot_element_pele WHERE id_pele IN ({$placeholders})");
$stmt->execute($ids);
$deleted    = $stmt->rowCount();

echo "Deleted {$deleted} broken element" . ($deleted === 1 ? '' : 's') . ".\n";
