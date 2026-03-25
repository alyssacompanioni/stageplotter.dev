(function () {
  const zone       = document.getElementById('drop-zone');
  const input      = document.getElementById('svg_file');
  const fileLabel  = document.getElementById('drop-zone-filename');
  const prompt     = zone.querySelector('.drop-zone-prompt');
  const uploadBtn  = document.getElementById('upload-btn');
  const typeSelect = document.getElementById('upload-type');
  const subSelect  = document.getElementById('upload-subcategory');

  const subcategoryOptions = window.SUBCATEGORY_OPTIONS || {};

  typeSelect.addEventListener('change', () => {
    const opts = subcategoryOptions[typeSelect.value] || {};
    subSelect.innerHTML = Object.entries(opts)
      .map(([val, label]) => `<option value="${val}">${label}</option>`)
      .join('');
  });

  function setFile(file) {
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    prompt.hidden = true;
    fileLabel.textContent = file.name;
    fileLabel.hidden = false;
    uploadBtn.disabled = false;
  }

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') input.click(); });
  input.addEventListener('change', () => setFile(input.files[0]));

  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drop-zone--active'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drop-zone--active'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drop-zone--active');
    setFile(e.dataTransfer.files[0]);
  });
})();
