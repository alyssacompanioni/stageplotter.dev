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
 * @param {Object} data - The data for the element, including src and label.
 * @param {number} x - The x-coordinate for the element's position on the canvas.
 * @param {number} y - The y-coordinate for the element's position on the canvas.
 */
function placeElement(data, x, y) {
  const el = document.createElement('div');
  el.className = 'placed-element';
  el.style.position = 'absolute';
  el.style.left = x + 'px';
  el.style.top = y + 'px';

  el.innerHTML = `<img src="${data.src}" alt="${data.label} Icon." width="48" height="48"><p>${data.label}</p>`;

  canvas.appendChild(el);
}

let activeEl = null;

// Listen for a mousedown event on the canvas and check if the event was on a placed element. If so, enable dragging it around the canvas
canvas.addEventListener('mousedown', (e) => {
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
  
  canvas.addEventListener('mousemove', mouseMoveHandler);
  canvas.addEventListener('mouseup', mouseUpHandler);
}

/**
 * Handles the mousemove event while dragging a placed element.
 * @param {MouseEvent} e - The mousemove event object.
 */
function mouseMoveHandler(e) {
  newX = startX - e.clientX;
  newY = startY - e.clientY;

  startX = e.clientX;
  startY = e.clientY;

  activeEl.style.left = (activeEl.offsetLeft - newX) + 'px';
  activeEl.style.top = (activeEl.offsetTop - newY) + 'px';

  console.log({newX, newY});
}

/**
 * Handles the end of the mousemove event by repositioning a placed element at the final coordinates and removing the mousemove event listener.
 * @param {MouseEvent} e - The mouseup event object.
 */
function mouseUpHandler(e) {
  canvas.removeEventListener('mousemove', mouseMoveHandler);
}

