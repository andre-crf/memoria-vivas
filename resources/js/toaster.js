const TOAST_DURATION = 5000;

export default function registerToaster(Alpine) {
    // Lista dos toasts que chegam pelo evento de navegador "toast" (ex.: $this->dispatch('toast', message: '…') no Livewire).
    Alpine.data('toaster', () => ({
        items: [],
        nextId: 0,

        push(message) {
            this.items.push({ id: this.nextId++, message });
        },
    }));

    // Um toast: some sozinho, mas pausa enquanto o mouse ou o foco estão nele.
    Alpine.data('toast', () => ({
        visible: true,
        timer: null,

        init() {
            this.resume();
        },

        pause() {
            clearTimeout(this.timer);
        },

        resume() {
            this.pause();
            this.timer = setTimeout(() => this.dismiss(), TOAST_DURATION);
        },

        dismiss() {
            this.pause();
            this.visible = false;
        },
    }));
}
