<?php require_once __DIR__ . '/../private/initialize.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stage Plotter</title>
  <link rel="stylesheet" href="/css/styles.css">
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

        <table class="plots-table">
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

        <script>
          (function() {
            const PAGE_SIZE = 20;
            let allPlots = [];
            let sortCol = 'title';
            let sortDir = 'asc';

            // ── Fetch ────────────────────────────────────────────────
            fetch('/api/get_public_plots.php')
              .then(r => r.json())
              .then(data => {
                allPlots = data.plots || [];
                updateSortIndicators();
                render();
              })
              .catch(() => {
                document.getElementById('plots-tbody').innerHTML =
                  '<tr><td colspan="4">Failed to load plots.</td></tr>';
              });

            // ── Search ───────────────────────────────────────────────
            document.getElementById('plot-search').addEventListener('input', render);

            // ── Sort headers ─────────────────────────────────────────
            document.querySelectorAll('.plots-table thead th').forEach(th => {
              th.addEventListener('click', () => {
                const col = th.dataset.col;
                if (sortCol === col) {
                  sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                  sortCol = col;
                  sortDir = 'asc';
                }
                updateSortIndicators();
                render();
              });
            });

            function updateSortIndicators() {
              document.querySelectorAll('.plots-table thead th').forEach(th => {
                th.removeAttribute('data-sort');
                const existing = th.querySelector('.sort-icon');
                if (existing) existing.remove();
                if (th.dataset.col === sortCol) {
                  th.setAttribute('data-sort', sortDir);
                  const img = document.createElement('img');
                  img.src = sortDir === 'asc' ? '/assets/icons/down-arrow.svg' : '/assets/icons/up-arrow.svg';
                  img.alt = sortDir === 'asc' ? 'ascending' : 'descending';
                  img.className = 'sort-icon';
                  th.appendChild(img);
                }
              });
            }

            // ── Render ───────────────────────────────────────────────
            function render() {
              const query = document.getElementById('plot-search').value.trim().toLowerCase();
              let results = allPlots;

              if (query) {
                results = results.filter(p =>
                  (p.title || '').toLowerCase().includes(query) ||
                  (p.gig_date || '').toLowerCase().includes(query) ||
                  (p.venue || '').toLowerCase().includes(query) ||
                  (p.created_by || '').toLowerCase().includes(query)
                );
              }

              if (sortCol) {
                results = [...results].sort((a, b) => {
                  const av = (a[sortCol] || '').toLowerCase();
                  const bv = (b[sortCol] || '').toLowerCase();
                  if (av < bv) return sortDir === 'asc' ? -1 : 1;
                  if (av > bv) return sortDir === 'asc' ? 1 : -1;
                  return 0;
                });
              }

              const slice = results.slice(0, PAGE_SIZE);
              const tbody = document.getElementById('plots-tbody');

              if (slice.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4">No plots found.</td></tr>';
                return;
              }

              tbody.innerHTML = slice.map(p => {
                const title = p.token ?
                  `<a href="/plot.php?token=${encodeURIComponent(p.token)}">${escHtml(p.title)}</a>` :
                  escHtml(p.title);
                return `<tr>
                <td>${title}</td>
                <td>${escHtml(p.gig_date)}</td>
                <td>${escHtml(p.venue)}</td>
                <td>${escHtml(p.created_by)}</td>
              </tr>`;
              }).join('');
            }

            function escHtml(str) {
              return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
          })();
        </script>
      </section>
    </main>
  </div>
  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
