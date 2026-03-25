<?php require_once __DIR__ . '/../private/initialize.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
  <script src="/js/index.js" defer></script>
</head>

<body>
  <?php require_once __DIR__ . '/includes/header.php'; ?>
  <div class="wrapper">
    <main class="index-main">
      <h1>Welcome to StagePlotter!</h1>

      <section class="about">
        <h2>About StagePlotter</h2>
        <p>StagePlotter helps musicians and sound engineers create, save, and share professional stage plots for live performances. Drag and drop instruments and equipment onto your stage, label each input, then share a link or print a PDF — all in minutes. Whether you're a solo artist or a full band, StagePlotter takes the guesswork out of show day.</p>
        <p>Unfortunately, the drag-and-drop feature of this application makes it difficult to use on mobile devices or screens smaller than 600px wide. We recommend using a desktop or laptop computer to access the full functionality of StagePlotter.</p>
      </section>

      <section class="browse-stage-plots">
        <h2>Browse and Search Existing Stage Plots</h2>
        <input type="search" id="plot-search" placeholder="Search" autocomplete="off">

        <table>
          <thead>
            <tr>
              <th data-col="title">Title</th>
              <th data-col="gig_date">Gig Date</th>
              <th data-col="venue">Venue</th>
              <th data-col="created_by">Created By</th>
            </tr>
          </thead>
          <tbody id="plots-tbody">
            <tr>
              <td colspan="4">Loading...</td>
            </tr>
          </tbody>
        </table>

      </section>
    </main>
  </div>
  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
