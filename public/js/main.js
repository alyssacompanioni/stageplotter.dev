const cards = document.querySelectorAll('.element-card');

cards.forEach(card => {
  card.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', JSON.stringify({
      src: card.querySelector('img').src,
      label: card.querySelector('p').textContent
    }));
  });
});

