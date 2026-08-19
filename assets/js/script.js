document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.querySelector('.mobile-menu');
  if (menuButton) {
    menuButton.addEventListener('click', () => document.body.classList.toggle('nav-open'));
  }

  const chips = document.querySelectorAll('.chip[data-filter]');
  const cards = document.querySelectorAll('.collection-card[data-category]');

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const filter = chip.dataset.filter;
      chips.forEach((item) => item.classList.remove('active'));
      chip.classList.add('active');

      cards.forEach((card) => {
        const categories = card.dataset.category.split(' ');
        const shouldShow = filter === 'all' || categories.includes(filter);
        card.style.display = shouldShow ? '' : 'none';
      });
    });
  });
});
