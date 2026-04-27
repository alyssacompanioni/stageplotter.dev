(function () {
  const zone      = document.getElementById('drop-zone');
  const input     = document.getElementById('svg_file');
  const stagedEl  = document.getElementById('drop-zone-staged');
  const fileLabel = document.getElementById('drop-zone-filename');
  const clearBtn  = document.getElementById('clear-file-btn');
  const errorEl   = document.getElementById('drop-zone-error');
  const prompt    = zone.querySelector('.drop-zone-prompt');
  const uploadBtn = document.getElementById('upload-btn');
  const typeRadios = document.querySelectorAll('input[name="type"]');
  const subSelect  = document.getElementById('upload-subcategory');

  const subcategoryOptions = window.SUBCATEGORY_OPTIONS || {};

  typeRadios.forEach(radio => {
    radio.addEventListener('change', (e) => {
      const opts = subcategoryOptions[e.target.value] || {};
      subSelect.innerHTML = Object.entries(opts)
        .map(([val, label]) => `<option value="${val}">${label}</option>`)
        .join('');
    });
  });

  function setFile(file) {
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    prompt.hidden = true;
    fileLabel.textContent = file.name;
    stagedEl.hidden = false;
    uploadBtn.disabled = false;
  }

  function clearFile() {
    input.value = '';
    fileLabel.textContent = '';
    stagedEl.hidden = true;
    errorEl.hidden = true;
    prompt.hidden = false;
    uploadBtn.disabled = true;
  }

  clearBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    clearFile();
  });

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') input.click(); });
  input.addEventListener('change', () => setFile(input.files[0]));

  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drop-zone--active'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drop-zone--active'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drop-zone--active');
    if (!stagedEl.hidden) {
      errorEl.hidden = false;
      return;
    }
    setFile(e.dataTransfer.files[0]);
  });

  const errorCloseBtn = errorEl.querySelector('.msg-close-btn');
  if (errorCloseBtn) errorCloseBtn.addEventListener('click', () => { errorEl.hidden = true; });

  document.querySelectorAll('.flash-message .msg-close-btn').forEach(btn => {
    btn.addEventListener('click', () => { btn.closest('.flash-message').hidden = true; });
  });

  document.querySelectorAll('.library-table').forEach(table => {
    const tbody = table.querySelector('tbody');
    let allRows = Array.from(tbody.querySelectorAll('tr'));
    let sortColIdx = -1;
    let sortDir = 'asc';

    table.querySelectorAll('thead th[data-col]').forEach(th => {
      th.addEventListener('click', () => {
        const idx = th.cellIndex;
        if (sortColIdx === idx) {
          sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
          sortColIdx = idx;
          sortDir = 'asc';
        }
        updateSortIndicators(th);
        sortRows();
      });
    });

    function sortRows() {
      allRows.sort((a, b) => {
        const av = a.cells[sortColIdx].textContent.trim().toLowerCase();
        const bv = b.cells[sortColIdx].textContent.trim().toLowerCase();
        if (av < bv) return sortDir === 'asc' ? -1 : 1;
        if (av > bv) return sortDir === 'asc' ? 1 : -1;
        return 0;
      });
      allRows.forEach(row => tbody.appendChild(row));
    }

    function updateSortIndicators(activeTh) {
      table.querySelectorAll('thead th[data-col]').forEach(th => {
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
  });

  const searchInput = document.getElementById('library-search-input');
  const searchClear = document.getElementById('library-search-clear');

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      searchClear.hidden = searchInput.value === '';
      filterLibrary();
    });
  }

  if (searchClear) {
    searchClear.addEventListener('click', function() {
      searchInput.value = '';
      searchClear.hidden = true;
      filterLibrary();
      searchInput.focus();
    });
  }

  function filterLibrary() {
    const query = searchInput.value.trim().toLowerCase();

    document.querySelectorAll('.library-subcategory').forEach(function(subcategory) {
      let hasMatch = false;

      subcategory.querySelectorAll('.library-table tbody tr').forEach(function(row) {
        const matches = query === '' || row.textContent.toLowerCase().includes(query);
        row.style.display = matches ? '' : 'none';
        if (matches) hasMatch = true;
      });

      const emptyMsg = subcategory.querySelector('.library-empty');
      if (emptyMsg) hasMatch = query === '';

      subcategory.hidden = !hasMatch;
      if (query !== '' && hasMatch) subcategory.open = true;
    });

    document.querySelectorAll('.library-section').forEach(function(section) {
      const hasMatch = Array.from(section.querySelectorAll('.library-subcategory'))
        .some(sub => !sub.hidden);
      section.hidden = !hasMatch;
      if (query !== '' && hasMatch) section.open = true;
    });
  }
})();
