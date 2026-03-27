(function() {
  const searchInput = document.getElementById('user-search');
  const tbody = document.querySelector('tbody');
  let allRows = Array.from(tbody.querySelectorAll('tr'));
  let sortColIdx = -1;
  let sortDir = 'asc';

  searchInput.addEventListener('input', filterRows);

  document.querySelectorAll('thead th[data-col]').forEach(function(th) {
    th.addEventListener('click', function() {
      const idx = th.cellIndex;
      if (sortColIdx === idx) {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        sortColIdx = idx;
        sortDir = 'asc';
      }
      updateSortIndicators(th);
      sortRows();
      filterRows();
    });
  });

  function sortRows() {
    allRows.sort(function(a, b) {
      const av = a.cells[sortColIdx].textContent.trim().toLowerCase();
      const bv = b.cells[sortColIdx].textContent.trim().toLowerCase();
      if (av < bv) return sortDir === 'asc' ? -1 : 1;
      if (av > bv) return sortDir === 'asc' ? 1 : -1;
      return 0;
    });
    allRows.forEach(function(row) { tbody.appendChild(row); });
  }

  function filterRows() {
    const query = searchInput.value.trim().toLowerCase();
    allRows.forEach(function(row) {
      const text = row.textContent.toLowerCase();
      row.style.display = query === '' || text.includes(query) ? '' : 'none';
    });
  }

  function updateSortIndicators(activeTh) {
    document.querySelectorAll('thead th[data-col]').forEach(function(th) {
      th.removeAttribute('data-sort');
      const icon = th.querySelector('.sort-icon');
      if (icon) icon.remove();
    });
    activeTh.setAttribute('data-sort', sortDir);
    const img = document.createElement('img');
    img.src = sortDir === 'asc' ? '/assets/icons/down-arrow.svg' : '/assets/icons/up-arrow.svg';
    img.alt = sortDir === 'asc' ? 'ascending' : 'descending';
    img.className = 'sort-icon';
    activeTh.appendChild(img);
  }
})();
