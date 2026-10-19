export default function registerPublicPhotoViewer(Alpine) {
    Alpine.data('publicPhotoViewer', () => ({
        available: true,
        zoomed: false,
        previousOverflow: '',

        open() {
            if (!this.available || this.$refs.viewer.open) return;
            this.zoomed = false;
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
            this.$refs.trigger.focus();
        },

        destroy() {
            if (this.$refs.viewer.open) document.documentElement.style.overflow = this.previousOverflow;
        },
    }));
}
