export default function registerDrawer(Alpine) {
    // Painel lateral (<dialog> modal: foco preso, Esc fecha), usado pelos filtros do catálogo e pelo menu público.
    // Trava a rolagem da página enquanto aberto e devolve o foco a quem abriu.
    Alpine.data('drawer', () => ({
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
