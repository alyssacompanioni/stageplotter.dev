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
})();
