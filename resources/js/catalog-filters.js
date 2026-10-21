export default function registerCatalogFilters(Alpine) {
    // Drawer de filtros do catálogo: <dialog> modal (foco preso, Esc fecha), com a rolagem da página travada.
    Alpine.data('catalogFilters', () => ({
        previousOverflow: '',
        opener: null,

        open() {
            if (this.$refs.drawer.open) return;
            this.opener = document.activeElement;
            this.previousOverflow = document.documentElement.style.overflow;
            document.documentElement.style.overflow = 'hidden';
            this.$refs.drawer.showModal();
        },

        close() {
            this.$refs.drawer.close();
        },

        restore() {
            document.documentElement.style.overflow = this.previousOverflow;
            this.opener?.focus();
        },

        destroy() {
            if (this.$refs.drawer.open) document.documentElement.style.overflow = this.previousOverflow;
        },
    }));
}
