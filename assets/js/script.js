document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.querySelector('.mobile-menu');

  if (menuButton) {
    menuButton.addEventListener('click', () => {
      document.body.classList.toggle('nav-open');
    });
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

  const carousel = document.querySelector('.featured-carousel');

  if (!carousel) {
    return;
  }

  const track = carousel.querySelector('.carousel-track');
  const slides = Array.from(carousel.querySelectorAll('.carousel-slide'));
  const prevButton = carousel.querySelector('.carousel-control.prev');
  const nextButton = carousel.querySelector('.carousel-control.next');
  const dotsContainer = carousel.querySelector('.carousel-dots');

  let currentIndex = 0;
  let visibleSlides = getVisibleSlides();

  function getVisibleSlides() {
    if (window.matchMedia('(max-width: 680px)').matches) {
      return 1;
    }

    if (window.matchMedia('(max-width: 1050px)').matches) {
      return 2;
    }

    return 3;
  }

  function getMaxIndex() {
    return Math.max(0, slides.length - visibleSlides);
  }

  function getStepSize() {
    if (!slides[0]) {
      return 0;
    }

    const slideWidth = slides[0].getBoundingClientRect().width;
    const gap = Number.parseFloat(getComputedStyle(track).gap) || 0;

    return slideWidth + gap;
  }

  function createDots() {
    dotsContainer.innerHTML = '';

    const totalDots = getMaxIndex() + 1;

    for (let i = 0; i < totalDots; i += 1) {
      const button = document.createElement('button');

      button.type = 'button';
      button.className = 'carousel-dot';
      button.setAttribute('aria-label', `Ir para destaque ${i + 1}`);

      button.addEventListener('click', () => {
        currentIndex = i;
        updateCarousel();
      });

      dotsContainer.appendChild(button);
    }
  }

  function updateCarousel() {
    visibleSlides = getVisibleSlides();

    const maxIndex = getMaxIndex();

    if (currentIndex > maxIndex) {
      currentIndex = maxIndex;
    }

    if (currentIndex < 0) {
      currentIndex = 0;
    }

    track.style.transform = `translateX(-${currentIndex * getStepSize()}px)`;

    prevButton.disabled = currentIndex === 0;
    nextButton.disabled = currentIndex === maxIndex;

    const dots = Array.from(dotsContainer.querySelectorAll('.carousel-dot'));

    dots.forEach((dot, index) => {
      dot.classList.toggle('active', index === currentIndex);
      dot.setAttribute('aria-current', index === currentIndex ? 'true' : 'false');
    });
  }

  prevButton.addEventListener('click', () => {
    currentIndex -= 1;
    updateCarousel();
  });

  nextButton.addEventListener('click', () => {
    currentIndex += 1;
    updateCarousel();
  });

  window.addEventListener('resize', () => {
    const previousVisibleSlides = visibleSlides;

    visibleSlides = getVisibleSlides();

    if (previousVisibleSlides !== visibleSlides) {
      createDots();
    }

    updateCarousel();
  });

  createDots();
  updateCarousel();
});