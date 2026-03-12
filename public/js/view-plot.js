// view-plot.js
// Renders a read-only stage plot from the PLOT_ELEMENTS array embedded by plot.php.

(function () {
  const canvas = document.getElementById('shared-canvas');
  if (!canvas || !Array.isArray(window.PLOT_ELEMENTS)) return;

  window.PLOT_ELEMENTS.forEach(el => {
    const div = document.createElement('div');
    div.className    = 'placed-element';
    div.style.left   = el.x + 'px';
    div.style.top    = el.y + 'px';
    div.style.zIndex = el.z_index;

    const img    = document.createElement('img');
    img.src      = el.src;
    img.alt      = el.label + ' Icon.';
    img.width    = el.size;
    img.height   = el.size;
    img.style.transform = `rotate(${el.rotation}deg) scaleX(${el.flipped ? -1 : 1})`;

    const label      = document.createElement('p');
    label.textContent = el.label;

    div.appendChild(img);
    div.appendChild(label);
    canvas.appendChild(div);
  });
})();
