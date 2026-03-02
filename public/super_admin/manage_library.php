<?php

/**
 * manage_library.php
 * Lets super_admins upload and browse the shared SVG plot-element image library.
 *
 * Requires: super_admin role.
 *
 * @author Alyssa Companioni
 */
require_once __DIR__ . '/../../private/initialize.php';
$session->require_role('super_admin');

define('PLOT_ELEMENT_DIR', __DIR__ . '/../assets/plot_elements/');

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

  header('Location: manage_library.php');
  exit;
}

// ── Fetch existing SVGs ───────────────────────────────────────────────────────
$svgs  = glob(PLOT_ELEMENT_DIR . '*.svg') ?: [];
$flash = $session->message();
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
  <?php require_once '../includes/header.php'; ?>
  <main>
    <h1>Plot Element Image Library</h1>

    <?php if ($flash !== '') { ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php } ?>

    <form method="post" enctype="multipart/form-data">
      <label for="svg_file">Upload SVG:</label>
      <input type="file" id="svg_file" name="svg_file" accept=".svg" required>
      <button type="submit">Upload</button>
    </form>

    <?php if (empty($svgs)) { ?>
      <p>No images uploaded yet.</p>
    <?php } else { ?>
      <ul class="svg-library">
        <?php foreach ($svgs as $svg_path) {
          $svg_name = basename($svg_path);
          $svg_url  = '/assets/plot_elements/' . $svg_name;
        ?>
          <li>
            <img src="<?= htmlspecialchars($svg_url) ?>" alt="<?= htmlspecialchars($svg_name) ?>">
            <span><?= htmlspecialchars($svg_name) ?></span>
          </li>
        <?php } ?>
      </ul>
    <?php } ?>

  </main>
</body>
