const cards = document.querySelectorAll('.element-card');

cards.forEach(card => {
  card.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', JSON.stringify({
      src: card.querySelector('img').src,
      label: card.querySelector('p').textContent
    }));
  });
});

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
      <button data-action="layer-up" title="Layer Up">↑</button>
      <button data-action="layer-down" title="Layer Down">↓</button>
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
  const img = el.querySelector('img');
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
      const img = selectedEl.querySelector('img');
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
      const img = selectedEl.querySelector('img');
      img.width = size;
      img.height = size;
      break;
    }

    case 'decrease': {
      const size = Math.max(16, parseInt(selectedEl.dataset.size) - 8);
      selectedEl.dataset.size = size;
      const img = selectedEl.querySelector('img');
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
