const MAX_SCALE = 6;
const KEY_STEP = 1.25;
const PAN_STEP = 60;

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

export default function registerPublicPhotoViewer(Alpine) {
    Alpine.data('publicPhotoViewer', () => {
        // Ponteiros ativos (mouse/toque) e o gesto de pinça; não precisam ser reativos.
        const pointers = new Map();
        let pinch = null;

        return {
            available: true,
            previousOverflow: '',
            opener: null,
            scale: 1,
            x: 0,
            y: 0,
            dragging: false,

            init() {
                // Se a imagem falhou antes do Alpine iniciar, o x-on:error não chega a disparar.
                this.$root.querySelectorAll('img[data-photo]').forEach((image) => {
                    if (image.complete && image.naturalWidth === 0) this.available = false;
                });
            },

            get zoomed() {
                return this.scale > 1.001;
            },

            get transform() {
                return `translate3d(${this.x}px, ${this.y}px, 0) scale(${this.scale})`;
            },

            open() {
                if (!this.available || this.$refs.viewer.open) return;
                this.opener = document.activeElement;
                this.reset();
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
                this.reset();
                (this.opener ?? this.$refs.trigger).focus();
            },

            destroy() {
                if (this.$refs.viewer.open) document.documentElement.style.overflow = this.previousOverflow;
            },

            reset() {
                pointers.clear();
                pinch = null;
                this.dragging = false;
                this.scale = 1;
                this.x = 0;
                this.y = 0;
            },

            // Escala em que a imagem fica no tamanho natural (1 = ajustada à tela).
            naturalScale() {
                const image = this.$refs.zoomImage;

                return image.offsetWidth > 0 ? Math.max(1, image.naturalWidth / image.offsetWidth) : 1;
            },

            maxScale() {
                return Math.max(MAX_SCALE, this.naturalScale());
            },

            // Amplia mantendo fixo o ponto sob (clientX, clientY); sem ponto, usa o centro.
            zoomTo(scale, clientX = null, clientY = null) {
                const box = this.$refs.zoomViewport.getBoundingClientRect();
                const pointX = clientX === null ? 0 : clientX - (box.left + box.width / 2);
                const pointY = clientY === null ? 0 : clientY - (box.top + box.height / 2);
                const next = clamp(scale, 1, this.maxScale());
                const ratio = next / this.scale;

                this.x = pointX - (pointX - this.x) * ratio;
                this.y = pointY - (pointY - this.y) * ratio;
                this.scale = next;
                this.keepInView();
            },

            // A imagem ampliada nunca sai da área visível; ajustada à tela, fica centralizada.
            keepInView() {
                const image = this.$refs.zoomImage;
                const viewport = this.$refs.zoomViewport;
                const maxX = Math.max(0, (image.offsetWidth * this.scale - viewport.clientWidth) / 2);
                const maxY = Math.max(0, (image.offsetHeight * this.scale - viewport.clientHeight) / 2);

                this.x = clamp(this.x, -maxX, maxX);
                this.y = clamp(this.y, -maxY, maxY);
            },

            wheel(event) {
                const speed = event.deltaMode === 1 ? 0.05 : 0.0015;

                this.zoomTo(this.scale * Math.exp(-event.deltaY * speed), event.clientX, event.clientY);
            },

            toggleZoom(event) {
                if (this.zoomed) this.zoomTo(1);
                else this.zoomTo(2.5, event.clientX, event.clientY);
            },

            pointerDown(event) {
                pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                // Mantém o arraste mesmo se o ponteiro sair da área; alguns ponteiros recusam a captura.
                try {
                    this.$refs.zoomViewport.setPointerCapture(event.pointerId);
                } catch {
                    // Segue sem captura.
                }
                this.dragging = true;

                if (pointers.size === 2) {
                    const [a, b] = [...pointers.values()];
                    pinch = {
                        distance: Math.hypot(a.x - b.x, a.y - b.y),
                        scale: this.scale,
                        middle: { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 },
                    };
                }
            },

            pointerMove(event) {
                const previous = pointers.get(event.pointerId);
                if (!previous) return;

                pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

                if (pointers.size === 2 && pinch) {
                    const [a, b] = [...pointers.values()];
                    const middle = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };

                    this.x += middle.x - pinch.middle.x;
                    this.y += middle.y - pinch.middle.y;
                    pinch.middle = middle;
                    this.zoomTo(pinch.scale * (Math.hypot(a.x - b.x, a.y - b.y) / pinch.distance), middle.x, middle.y);

                    return;
                }

                if (this.zoomed) {
                    this.x += event.clientX - previous.x;
                    this.y += event.clientY - previous.y;
                    this.keepInView();
                }
            },

            pointerUp(event) {
                pointers.delete(event.pointerId);
                if (pointers.size < 2) pinch = null;
                if (pointers.size === 0) this.dragging = false;
            },

            // Alternativa ao mouse: + e − ampliam, 0 ajusta à tela, setas movem a foto ampliada.
            keydown(event) {
                const pan = { ArrowLeft: [PAN_STEP, 0], ArrowRight: [-PAN_STEP, 0], ArrowUp: [0, PAN_STEP], ArrowDown: [0, -PAN_STEP] }[event.key];

                if (event.key === '+' || event.key === '=') this.zoomTo(this.scale * KEY_STEP);
                else if (event.key === '-' || event.key === '_') this.zoomTo(this.scale / KEY_STEP);
                else if (event.key === '0') this.zoomTo(1);
                else if (pan && this.zoomed) {
                    this.x += pan[0];
                    this.y += pan[1];
                    this.keepInView();
                } else return;

                event.preventDefault();
            },
        };
    });
}
