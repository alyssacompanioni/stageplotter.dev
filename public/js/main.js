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

canvas.addEventListener('dragover', (e) => {
  e.preventDefault(); // Allows dropping
});

canvas.addEventListener('drop', (e) => {
  e.preventDefault();
  const data = JSON.parse(e.dataTransfer.getData('text/plain'));

  //Calculate drop position relative to canvas
  const rect = canvas.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  
  placeElement(data, x, y);
});

function placeElement(data, x, y) {
  
}
