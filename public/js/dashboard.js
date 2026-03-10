// ─── Palette ───────────────────────────────────────────────────────────────────

const cardContainer = document.querySelector('.element-card-container');

// Event delegation — works for both the initial PHP-rendered cards and any
// cards injected dynamically when the user switches instrument category.
cardContainer.addEventListener('dragstart', (e) => {
  const card = e.target.closest('.element-card');
  if (!card) return;
  e.dataTransfer.setData('text/plain', JSON.stringify({
    src:   card.querySelector('img').src,
    label: card.querySelector('p').textContent
  }));
});

// Intercept category button clicks so switching categories fetches icons via
// AJAX instead of reloading the page (which would clear the canvas).
document.querySelector('.element-type').addEventListener('click', (e) => {
  const btn = e.target.closest('button[value]');
  if (!btn) return;
  e.preventDefault();
  switchPalette(btn.value);
});

/**
 * Fetches icons for the given category and repopulates the card container.
 * @param {string} category - e.g. 'guitars', 'percussion'
 */
async function switchPalette(category) {
  try {
    const res  = await fetch('/api/get_palette.php?category=' + encodeURIComponent(category));
    const data = await res.json();
    if (!data.success) return;

    cardContainer.innerHTML = data.icons.map(icon => `
      <div class="element-card" draggable="true">
        <img src="${icon.src}" alt="${icon.label} Icon." width="48" height="48">
        <p>${icon.label}</p>
      </div>
    `).join('');
  } catch {
    // Silently fail — the existing cards remain visible
  }
}

const canvas = document.querySelector('.stage-plot-canvas');

canvas.addEventListener('dragstart', (e) => {
  if (e.target.closest('.placed-element')) e.preventDefault();
});

canvas.addEventListener('dragover', (e) => {
  e.preventDefault(); // Allows dropping
});

canvas.addEventListener('drop', (e) => {
  e.preventDefault(); // Prevents default browser behavior (e.g., opening the image)
  const data = JSON.parse(e.dataTransfer.getData('text/plain'));

  // Calculate drop position relative to canvas
  const rect = canvas.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  placeElement(data, x, y);
});

/**
 * Places a new element on the canvas at the specified coordinates.
 * @param {Object} data - The element data: src, label, and optional rotation, flipped, size, zIndex.
 * @param {number} x - The x-coordinate for the element's position on the canvas.
 * @param {number} y - The y-coordinate for the element's position on the canvas.
 */
function placeElement(data, x, y) {
  const el = document.createElement('div');
  el.className = 'placed-element';
  el.style.position = 'absolute';
  el.style.left = x + 'px';
  el.style.top = y + 'px';
  el.dataset.rotation = data.rotation ?? '0';
  el.dataset.flipped = data.flipped ?? 'false';
  el.dataset.size = data.size ?? '48';
  if (data.zIndex) el.style.zIndex = data.zIndex;

  const size = data.size ?? 48;

  el.innerHTML = `
    <div class="element-toolbar">
      <button data-action="delete" title="Delete">✕</button>
      <button data-action="duplicate" title="Duplicate">⧉</button>
      <button data-action="enlarge" title="Enlarge">+</button>
      <button data-action="decrease" title="Decrease">−</button>
      <button data-action="rotate-right" title="Rotate Right">↻</button>
      <button data-action="rotate-left" title="Rotate Left">↺</button>
      <button data-action="flip-h" title="Flip Horizontal">↔</button>
      <button data-action="layer-up" title="Layer Up"><img src="/assets/icons/layer-up.svg" alt="Layer Up" width="16" height="16"></button>
      <button data-action="layer-down" title="Layer Down"><img src="/assets/icons/layer-down.svg" alt="Layer Down" width="16" height="16"></button>
    </div>
    <img src="${data.src}" alt="${data.label} Icon." width="${size}" height="${size}">
    <p>${data.label}</p>
  `;

  canvas.appendChild(el);
  applyTransform(el);
}

// ─── Selection ────────────────────────────────────────────────────────────────

let selectedEl = null;

/**
 * Selects a placed element and deselects any previously selected one.
 * @param {HTMLElement} el - The placed element to select.
 */
function selectElement(el) {
  if (selectedEl && selectedEl !== el) selectedEl.classList.remove('selected');
  selectedEl = el;
  el.classList.add('selected');
}

/**
 * Deselects all placed elements.
 */
function deselectAll() {
  if (selectedEl) {
    selectedEl.classList.remove('selected');
    selectedEl = null;
  }
}

// Deselect when clicking the canvas background (not on a placed element)
canvas.addEventListener('click', (e) => {
  const actionBtn = e.target.closest('[data-action]');
  if (actionBtn) {
    handleAction(actionBtn.dataset.action);
    return;
  }

  const el = e.target.closest('.placed-element');
  if (el) {
    selectElement(el);
  } else {
    deselectAll();
  }
});

// ─── Toolbar Actions ───────────────────────────────────────────────────────────

/**
 * Applies the current rotation and flip state to the element's image.
 * @param {HTMLElement} el - The placed element whose image transform should be updated.
 */
function applyTransform(el) {
  const img = el.querySelector(':scope > img');
  const rotation = parseInt(el.dataset.rotation || '0');
  const flipped = el.dataset.flipped === 'true';
  img.style.transform = `rotate(${rotation}deg) scaleX(${flipped ? -1 : 1})`;
}

/**
 * Handles a toolbar action on the currently selected element.
 * @param {string} action - The action identifier from the button's data-action attribute.
 */
function handleAction(action) {
  if (!selectedEl) return;

  switch (action) {
    case 'delete':
      selectedEl.remove();
      selectedEl = null;
      break;

    case 'duplicate': {
      const img = selectedEl.querySelector(':scope > img');
      const label = selectedEl.querySelector('p').textContent;
      placeElement({
        src: img.src,
        label,
        rotation: selectedEl.dataset.rotation,
        flipped: selectedEl.dataset.flipped,
        size: parseInt(selectedEl.dataset.size),
        zIndex: selectedEl.style.zIndex,
      }, parseInt(selectedEl.style.left) + 16, parseInt(selectedEl.style.top) + 16);
      break;
    }

    case 'enlarge': {
      const size = parseInt(selectedEl.dataset.size) + 8;
      selectedEl.dataset.size = size;
      const img = selectedEl.querySelector(':scope > img');
      img.width = size;
      img.height = size;
      break;
    }

    case 'decrease': {
      const size = Math.max(16, parseInt(selectedEl.dataset.size) - 8);
      selectedEl.dataset.size = size;
      const img = selectedEl.querySelector(':scope > img');
      img.width = size;
      img.height = size;
      break;
    }

    case 'rotate-right':
      selectedEl.dataset.rotation = (parseInt(selectedEl.dataset.rotation) + 45) % 360;
      applyTransform(selectedEl);
      break;

    case 'rotate-left':
      selectedEl.dataset.rotation = (parseInt(selectedEl.dataset.rotation) - 45 + 360) % 360;
      applyTransform(selectedEl);
      break;

    case 'flip-h':
      selectedEl.dataset.flipped = selectedEl.dataset.flipped === 'true' ? 'false' : 'true';
      applyTransform(selectedEl);
      break;

    case 'layer-up':
      selectedEl.style.zIndex = (parseInt(selectedEl.style.zIndex || '0') + 1).toString();
      break;

    case 'layer-down':
      selectedEl.style.zIndex = Math.max(0, parseInt(selectedEl.style.zIndex || '0') - 1).toString();
      break;
  }
}

// ─── Drag to Reposition ────────────────────────────────────────────────────────

let activeEl = null;
let startX, startY;
let hasDragged = false;

// Skip drag initiation when clicking toolbar buttons
canvas.addEventListener('mousedown', (e) => {
  if (e.target.closest('.element-toolbar')) return;
  const el = e.target.closest('.placed-element');
  if (el) mouseDownHandler(e, el);
});

/**
 * Handles the mousedown event on a placed element, enabling it to be dragged around the canvas.
 * @param {MouseEvent} e - The mousedown event object.
 * @param {HTMLElement} el - The placed element that was clicked on.
 */
function mouseDownHandler(e, el) {
  activeEl = el;
  startX = e.clientX;
  startY = e.clientY;
  hasDragged = false;

  canvas.addEventListener('mousemove', mouseMoveHandler);
  canvas.addEventListener('mouseup', mouseUpHandler);
}

/**
 * Handles the mousemove event while dragging a placed element.
 * @param {MouseEvent} e - The mousemove event object.
 */
function mouseMoveHandler(e) {
  hasDragged = true;

  const newX = startX - e.clientX;
  const newY = startY - e.clientY;

  startX = e.clientX;
  startY = e.clientY;

  activeEl.style.left = (activeEl.offsetLeft - newX) + 'px';
  activeEl.style.top = (activeEl.offsetTop - newY) + 'px';
}

/**
 * Handles the end of a drag, selects the element on a plain click (no movement), and cleans up listeners.
 * @param {MouseEvent} e - The mouseup event object.
 */
function mouseUpHandler(e) {
  canvas.removeEventListener('mousemove', mouseMoveHandler);
  canvas.removeEventListener('mouseup', mouseUpHandler);

  if (!hasDragged && activeEl) selectElement(activeEl);

  activeEl = null;
}

// ─── Save / Load ───────────────────────────────────────────────────────────────

// Tracks the database ID of the currently loaded plot (null = unsaved new plot).
let currentPlotId = null;

/**
 * Collects the current canvas state into a plain array ready to POST to the API.
 * Uses the src pathname (not the full absolute URL) so paths stay portable.
 * @returns {Array<Object>}
 */
function serializeCanvas() {
  const elements = [];
  canvas.querySelectorAll('.placed-element').forEach(el => {
    const img = el.querySelector(':scope > img');
    elements.push({
      src:      new URL(img.src).pathname,
      label:    el.querySelector('p').textContent,
      x:        parseFloat(el.style.left),
      y:        parseFloat(el.style.top),
      rotation: parseInt(el.dataset.rotation || '0'),
      flipped:  el.dataset.flipped === 'true',
      size:     parseInt(el.dataset.size || '48'),
      z_index:  Math.max(1, parseInt(el.style.zIndex || '1')),
    });
  });
  return elements;
}

/**
 * Saves the current plot (creates new or updates existing) via the API.
 * Stores the returned plot_id so subsequent saves perform an update.
 */
async function savePlot() {
  const title   = document.getElementById('plot-title').value.trim();
  const gigDate = document.getElementById('plot-gig-date').value.trim();
  const venue   = document.getElementById('plot-venue').value.trim();

  if (!title || !gigDate) {
    alert('Title and Gig Date are required to save.');
    return;
  }

  closeDropdown();

  try {
    const res  = await fetch('/api/save_plot.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        plot_id:  currentPlotId,
        title,
        gig_date: gigDate,
        venue:    venue || null,
        elements: serializeCanvas(),
      }),
    });

    const data = await res.json();

    if (data.success) {
      currentPlotId = data.plot_id;
      alert('Plot saved!');
    } else {
      alert('Save failed:\n' + (data.errors ? data.errors.join('\n') : data.error));
    }
  } catch {
    alert('Error: could not reach the server.');
  }
}

/**
 * Fetches the user's saved plots and opens the load modal.
 */
async function showLoadModal() {
  closeDropdown();

  try {
    const res  = await fetch('/api/load_plots.php');
    const data = await res.json();

    if (!data.success) {
      alert('Could not load plots.');
      return;
    }

    const list = document.getElementById('load-plot-list');
    list.innerHTML = '';

    if (data.plots.length === 0) {
      list.innerHTML = '<li class="load-plot-empty">No saved plots found.</li>';
    } else {
      data.plots.forEach(plot => {
        const li = document.createElement('li');
        li.className = 'load-plot-item';
        li.innerHTML = `
          <span class="load-plot-title">${plot.title}</span>
          <span class="load-plot-meta">${plot.gig_date}${plot.venue ? ' — ' + plot.venue : ''}</span>
        `;
        li.addEventListener('click', () => loadPlot(plot.id));
        list.appendChild(li);
      });
    }

    document.getElementById('load-plot-modal').removeAttribute('hidden');
  } catch {
    alert('Error: could not reach the server.');
  }
}

/**
 * Loads a saved plot from the API, clears the canvas, and restores all elements.
 * @param {number} plotId
 */
async function loadPlot(plotId) {
  closeLoadModal();

  try {
    const res  = await fetch('/api/load_plot.php?id=' + plotId);
    const data = await res.json();

    if (!data.success) {
      alert('Could not load plot.');
      return;
    }

    // Restore meta fields
    document.getElementById('plot-title').value    = data.title;
    document.getElementById('plot-gig-date').value = data.gig_date;
    document.getElementById('plot-venue').value    = data.venue ?? '';
    currentPlotId = data.plot_id;

    // Clear the canvas and redraw elements
    deselectAll();
    canvas.querySelectorAll('.placed-element').forEach(el => el.remove());

    data.elements.forEach(el => {
      placeElement({
        src:      el.src,
        label:    el.label,
        rotation: String(el.rotation),
        flipped:  el.flipped ? 'true' : 'false',
        size:     el.size,
        zIndex:   String(el.z_index),
      }, el.x, el.y);
    });
  } catch {
    alert('Error: could not reach the server.');
  }
}

/**
 * Closes the load plot modal.
 */
function closeLoadModal() {
  document.getElementById('load-plot-modal').setAttribute('hidden', '');
}

/**
 * Closes the plot actions dropdown by unchecking its toggle checkbox.
 */
function closeDropdown() {
  document.getElementById('plot-toolbar-toggle').checked = false;
}

// ─── Button wiring ─────────────────────────────────────────────────────────────

document.getElementById('save-plot-btn').addEventListener('click', savePlot);
document.getElementById('load-plot-btn').addEventListener('click', showLoadModal);
document.getElementById('load-modal-cancel').addEventListener('click', closeLoadModal);
document.getElementById('clear-stage-btn').addEventListener('click', () => {
  closeDropdown();
  deselectAll();
  canvas.querySelectorAll('.placed-element').forEach(el => el.remove());
});
