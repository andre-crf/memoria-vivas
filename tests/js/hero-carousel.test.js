import assert from 'node:assert/strict';
import test from 'node:test';
import registerHeroCarousel from '../../resources/js/hero-carousel.js';

test('hero carousel wraps in both directions and selects a slide', () => {
    let factory;
    registerHeroCarousel({ data: (name, callback) => {
        assert.equal(name, 'heroCarousel');
        factory = callback;
    } });

    const carousel = factory(3);
    assert.equal(carousel.active, 0);

    carousel.previous();
    assert.equal(carousel.active, 2);

    carousel.next();
    assert.equal(carousel.active, 0);

    carousel.goTo(1);
    assert.equal(carousel.active, 1);
});
