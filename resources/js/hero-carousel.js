export default function registerHeroCarousel(Alpine) {
    Alpine.data('heroCarousel', (count) => ({
        active: 0,

        goTo(index) {
            this.active = (index + count) % count;
        },

        previous() {
            this.goTo(this.active - 1);
        },

        next() {
            this.goTo(this.active + 1);
        },
    }));
}
