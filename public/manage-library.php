<?php

/**
 * manage-library.php
 * Lets admins and super_admins upload and browse the shared SVG
 * plot-element image library.
 *
 * Requires: admin role or higher.
 *
 * @author Alyssa Companioni
 */
require_once __DIR__ . '/../private/initialize.php';
$session->require_role('admin');

define('ASSETS_INSTRUMENTS_DIR', __DIR__ . '/assets/instruments/');
define('PLOT_ELEMENT_DIR', __DIR__ . '/assets/plot_elements/');

// ── Category definitions ──────────────────────────────────────────────────────
$instrument_categories = [
  'guitars'    => 'Guitars',
  'percussion' => 'Percussion',
  'keys'       => 'Keys',
  'strings'    => 'Strings',
  'winds'      => 'Winds',
];

$equipment_categories = [
  'amps' => 'Amps',
  'misc' => 'Misc',
];

// ── Handle SVG upload POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['svg_file'])) {
  if ($_FILES['svg_file']['error'] !== UPLOAD_ERR_OK) {
    $session->message('Upload error. Please try again.');
  } else {
    $tmp = $_FILES['svg_file']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['svg_file']['name'], PATHINFO_EXTENSION));

    if (mime_content_type($tmp) !== 'image/svg+xml' || $ext !== 'svg') {
      $session->message('Only SVG files are accepted.');
    } else {
      $base     = preg_replace('/[^a-z0-9_\-]/', '_', strtolower(pathinfo($_FILES['svg_file']['name'], PATHINFO_FILENAME)));
      $filename = $base . '.svg';
      $dest     = PLOT_ELEMENT_DIR . $filename;

      // Appends a Unix timestamp if the filename already exists to avoid overwriting
      if (file_exists($dest)) {
        $filename = $base . '_' . time() . '.svg';
        $dest     = PLOT_ELEMENT_DIR . $filename;
      }

      if (move_uploaded_file($tmp, $dest)) {
        $session->message(htmlspecialchars($filename) . ' uploaded successfully.');
      } else {
        $session->message('Upload failed. Please try again.');
      }
    }
  }

  header('Location: manage-library.php');
  exit;
}

// ── Handle delete POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
  $requested = $_POST['delete_file'];

  // Resolve to a real path and confirm it lives inside ASSETS_INSTRUMENTS_DIR
  $real_base = realpath(ASSETS_INSTRUMENTS_DIR);
  $real_file = realpath(ASSETS_INSTRUMENTS_DIR . $requested);

  if (
    $real_file !== false &&
    str_starts_with($real_file, $real_base . DIRECTORY_SEPARATOR) &&
    is_file($real_file)
  ) {
    if (unlink($real_file)) {
      $session->message(htmlspecialchars(basename($real_file)) . ' deleted.');
    } else {
      $session->message('Could not delete file. Please try again.');
    }
  } else {
    $session->message('Invalid file path.');
  }

  header('Location: manage-library.php');
  exit;
}

// ── Build image data grouped by section ──────────────────────────────────────
/**
 * Returns [ 'subcategory_slug' => [ ['slug'=>…, 'filename'=>…, 'src'=>…, 'label'=>…], … ], … ]
 */
function gather_images(array $categories): array {
  $result = [];
  foreach (array_keys($categories) as $slug) {
    $dir   = ASSETS_INSTRUMENTS_DIR . $slug . '/';
    $files = is_dir($dir) ? (glob($dir . '*.{svg,png}', GLOB_BRACE) ?: []) : [];
    sort($files);
    $result[$slug] = array_map(function (string $file) use ($slug): array {
      $filename = basename($file);
      return [
        'slug'     => $slug,
        'filename' => $filename,
        'rel_path' => $slug . '/' . $filename,
        'src'      => '/assets/instruments/' . $slug . '/' . rawurlencode($filename),
        'label'    => ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME))),
      ];
    }, $files);
  }
  return $result;
}

$instrument_images = gather_images($instrument_categories);
$equipment_images  = gather_images($equipment_categories);
$flash             = $session->message();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Image Library | Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
  <?php require_once 'includes/header.php'; ?>
  <div class="wrapper">
    <main>
      <h1>Plot Element Image Library</h1>

      <?php if ($flash !== '') { ?>
        <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
      <?php } ?>

      <form method="post" enctype="multipart/form-data">
        <label for="svg_file">Upload SVG:</label>
        <input type="file" id="svg_file" name="svg_file" accept=".svg" required>
        <button type="submit" class="btn">Upload</button>
      </form>

      <?php
      // ── Reusable helper: render one category section ──────────────────────
      function render_section(string $section_title, array $categories, array $images_by_slug): void { ?>
        <section class="library-section">
          <h2><?= htmlspecialchars($section_title) ?></h2>

          <?php foreach ($categories as $slug => $cat_label):
            $images = $images_by_slug[$slug] ?? [];
          ?>
            <h3><?= htmlspecialchars($cat_label) ?></h3>

            <?php if (empty($images)): ?>
              <p class="library-empty">No images in this category yet.</p>
            <?php else: ?>
              <table class="library-table">
                <thead>
                  <tr>
                    <th>Preview</th>
                    <th>Filename</th>
                    <th>Label</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($images as $img): ?>
                    <tr>
                      <td>
                        <img src="<?= htmlspecialchars($img['src']) ?>"
                             alt="<?= htmlspecialchars($img['label']) ?>"
                             class="library-thumb">
                      </td>
                      <td><?= htmlspecialchars($img['filename']) ?></td>
                      <td><?= htmlspecialchars($img['label']) ?></td>
                      <td>
                        <form method="post"
                              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($img['filename'])) ?>?');">
                          <input type="hidden" name="delete_file"
                                 value="<?= htmlspecialchars($img['rel_path']) ?>">
                          <button type="submit" class="btn btn-delete">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

          <?php endforeach; ?>
        </section>
      <?php } ?>

      <?php render_section('Instruments', $instrument_categories, $instrument_images); ?>
      <?php render_section('Equipment',   $equipment_categories,  $equipment_images);  ?>

    </main>
  </div>
</body>
