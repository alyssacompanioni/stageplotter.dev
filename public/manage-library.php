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
define('ASSETS_EQUIPMENT_DIR',   __DIR__ . '/assets/equipment/');

// ── Category definitions ──────────────────────────────────────────────────────
$instrument_categories = [
  'guitars'    => 'Guitars',
  'drums'      => 'Drums',
  'keys'       => 'Keys',
  'strings'    => 'Strings',
  'brass'      => 'Brass',
  'winds'      => 'Woodwinds',
  'percussion' => 'Percussion',
  'misc'       => 'Misc',
];

$equipment_categories = [
  'audio'     => 'Audio',
  'furniture' => 'Furniture',
  'lighting'  => 'Lighting',
  'misc'      => 'Misc',
];

// ── Handle SVG upload POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['svg_file'])) {
  $type        = $_POST['type']        ?? '';
  $subcategory = $_POST['subcategory'] ?? '';

  // Validate type and subcategory
  if ($type === 'instruments' && array_key_exists($subcategory, $instrument_categories)) {
    $dest_dir  = ASSETS_INSTRUMENTS_DIR . $subcategory . '/';
  } elseif ($type === 'equipment' && array_key_exists($subcategory, $equipment_categories)) {
    $dest_dir  = ASSETS_EQUIPMENT_DIR . $subcategory . '/';
  } else {
    $session->message('Invalid category selection.');
    header('Location: manage-library.php');
    exit;
  }

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
      $dest     = $dest_dir . $filename;

      // Appends a Unix timestamp if the filename already exists to avoid overwriting
      if (file_exists($dest)) {
        $filename = $base . '_' . time() . '.svg';
        $dest     = $dest_dir . $filename;
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
  $type      = $_POST['delete_type'] ?? 'instruments';
  $base_dir  = $type === 'equipment' ? ASSETS_EQUIPMENT_DIR : ASSETS_INSTRUMENTS_DIR;
  $requested = $_POST['delete_file'];

  // Resolve to a real path and confirm it lives inside the expected base dir
  $real_base = realpath($base_dir);
  $real_file = realpath($base_dir . $requested);

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
function gather_images(array $categories, string $base_dir, string $url_prefix): array
{
  $result = [];
  foreach (array_keys($categories) as $slug) {
    $dir   = $base_dir . $slug . '/';
    $files = is_dir($dir) ? (glob($dir . '*.{svg,png}', GLOB_BRACE) ?: []) : [];
    sort($files);
    $result[$slug] = array_map(function (string $file) use ($slug, $url_prefix): array {
      $filename = basename($file);
      return [
        'slug'     => $slug,
        'filename' => $filename,
        'rel_path' => $slug . '/' . $filename,
        'src'      => $url_prefix . $slug . '/' . rawurlencode($filename),
        'label'    => ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME))),
      ];
    }, $files);
  }
  return $result;
}

$instrument_images = gather_images($instrument_categories, ASSETS_INSTRUMENTS_DIR, '/assets/instruments/');
$equipment_images  = gather_images($equipment_categories,  ASSETS_EQUIPMENT_DIR,   '/assets/equipment/');
$flash             = $session->message();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Image Library | Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
  <script src="/js/manage-library.js" defer></script>
</head>

<body>
  <?php require_once 'includes/header.php'; ?>
  <div class="wrapper">
    <main>
      <h1>Plot Element Image Manager</h1>

      <?php if ($flash !== '') { ?>
        <div class="flash-message">
          <span><?= htmlspecialchars($flash) ?></span>
          <button type="button" class="msg-close-btn" aria-label="Dismiss">&times;</button>
        </div>
      <?php } ?>

      <div class="drop-zone-error" id="drop-zone-error" hidden>
        <span>You can only upload one image to the library at a time.</span>
        <button type="button" class="msg-close-btn" aria-label="Dismiss">&times;</button>
      </div>

      <div class="upload-image-section">
        <h2>Upload a New Image</h2>

        <form method="post" enctype="multipart/form-data" id="upload-form">
          <div class="drop-zone" id="drop-zone" role="button" tabindex="0" aria-label="Drop SVG file here or click to browse">
            <p class="drop-zone-prompt">Drag &amp; drop an SVG here, or <span class="drop-zone-link">browse</span></p>
            <div class="drop-zone-staged" id="drop-zone-staged" hidden>
              <span id="drop-zone-filename"></span>
              <button type="button" id="clear-file-btn" aria-label="Remove staged file"><img src="/assets/icons/trash.svg" alt="" width="16" height="16"></button>
            </div>
            <input type="file" id="svg_file" name="svg_file" accept=".svg" required>
          </div>

          <div class="upload-category-row">
            <div class="upload-type-group">
              <span class="upload-type-label">Type:</span>
              <label class="upload-type-option">
                <input type="radio" name="type" value="instruments" checked> Instruments
              </label>
              <label class="upload-type-option">
                <input type="radio" name="type" value="equipment"> Equipment
              </label>
            </div>

            <div class="upload-subcategory-group">
              <label for="upload-subcategory">Category:</label>
              <select id="upload-subcategory" name="subcategory" required>
                <?php foreach ($instrument_categories as $slug => $label): ?>
                  <option value="<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button type="submit" class="btn" id="upload-btn" disabled>Upload</button>
        </form>
      </div>

      <div class="library-search">
        <h2>Search Plot Elements</h2>
        <input type="text" id="library-search-input" placeholder="Search images...">
      </div>

      <script>
        window.SUBCATEGORY_OPTIONS = {
          instruments: <?= json_encode($instrument_categories) ?>,
          equipment: <?= json_encode($equipment_categories) ?>,
        };
      </script>

      <?php
      // ── Reusable helper: render one category section ──────────────────────
      function render_section(string $section_title, string $delete_type, array $categories, array $images_by_slug): void
      { ?>
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
                    <th></th>
                    <th data-col="1">Filename</th>
                    <th data-col="2">Label</th>
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
                          <input type="hidden" name="delete_type"
                            value="<?= htmlspecialchars($delete_type) ?>">
                          <button type="submit" class="btn btn-delete" aria-label="Delete <?= htmlspecialchars($img['filename']) ?>">
                            <img src="/assets/icons/trash.svg" alt="" width="16" height="16">
                          </button>
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

      <?php render_section('Instruments', 'instruments', $instrument_categories, $instrument_images); ?>
      <?php render_section('Equipment',   'equipment',   $equipment_categories,  $equipment_images);  ?>

    </main>
  </div>
</body>
