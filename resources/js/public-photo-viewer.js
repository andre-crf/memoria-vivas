export default function registerPublicPhotoViewer(Alpine) {
    Alpine.data('publicPhotoViewer', () => ({
        available: true,
        zoomed: false,
        previousOverflow: '',
        opener: null,

        init() {
            // Se a imagem falhou antes do Alpine iniciar, o x-on:error não chega a disparar.
            this.$root.querySelectorAll('img[data-photo]').forEach((image) => {
                if (image.complete && image.naturalWidth === 0) this.available = false;
            });
        },

        open(zoomed = false) {
            if (!this.available || this.$refs.viewer.open) return;
            this.opener = document.activeElement;
            this.zoomed = zoomed;
            this.previousOverflow = document.documentElement.style.overflow;
            document.documentElement.style.overflow = 'hidden';
            this.$refs.viewer.showModal();
            this.$nextTick(() => this.$refs.close.focus());
        },

        close() {
            this.$refs.viewer.close();
        },

        restore() {
            document.documentElement.style.overflow = this.previousOverflow;
            this.zoomed = false;
            (this.opener ?? this.$refs.trigger).focus();
        },

        destroy() {
            if (this.$refs.viewer.open) document.documentElement.style.overflow = this.previousOverflow;
        },
    }));
}
