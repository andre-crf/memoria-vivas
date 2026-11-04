export default function registerPhotographyForm(Alpine) {
    Alpine.data('characterCounter', (initialCount, limit) => ({
        count: initialCount,
        limit,

        update(value) {
            this.count = [...value].length;
        },

        get formattedCount() {
            return `${this.count.toLocaleString('pt-BR')}/${this.limit.toLocaleString('pt-BR')}`;
        },
    }));

    Alpine.data('historicalDateFields', (initialPrecision) => ({
        precision: initialPrecision,
        fieldsByPrecision: {
            data_exata: ['dia', 'mes', 'ano'],
            mes_ano: ['mes', 'ano'],
            ano: ['ano'],
            decada: ['decada'],
            desconhecida: [],
        },

        isVisible(field) {
            return (this.fieldsByPrecision[this.precision] ?? []).includes(field);
        },

        init() {
            this.clearIncompatibleFields();
            this.$watch('precision', () => this.clearIncompatibleFields());
        },

        clearIncompatibleFields() {
            this.$root.querySelectorAll('input[data-date-field]').forEach((input) => {
                if (!this.isVisible(input.dataset.dateField)) input.value = '';
            });
        },
    }));

    // Combobox do seletor de catálogo (Livewire): o servidor devolve as opções; aqui fica só o teclado.
    Alpine.data('catalogCombobox', (multiple) => ({
        open: false,
        active: 0,

        // Consulta pelo $root, e não por $refs: o Livewire re-renderiza a lista e o campo a cada busca.
        options() {
            return [...(this.$root.querySelector('[role="listbox"]')?.querySelectorAll('[role="option"]') ?? [])];
        },

        get activeId() {
            return this.open ? (this.options()[this.active]?.id ?? null) : null;
        },

        show() {
            this.open = true;
            this.active = 0;
        },

        move(step) {
            const total = this.options().length;

            if (!this.open) this.show();
            if (total === 0) return;

            this.active = (this.active + step + total) % total;
            this.options()[this.active]?.scrollIntoView({ block: 'nearest' });
        },

        // Envia a busca pendente (com debounce) antes de escolher, para o Enter não pegar a lista antiga.
        async choose() {
            await this.$wire.$commit();
            this.options()[this.active]?.click();
        },

        // O item clicado some da lista quando o Livewire responde, levando junto o escopo dele:
        // por isso a raiz é guardada antes do await. Na seleção múltipla a lista continua aberta.
        async pick(id) {
            const root = this.$root;

            await this.$wire.select(id);
            this.active = 0;
            this.open = multiple;
            root.querySelector('[role="combobox"]')?.focus();
        },
    }));

    // Área de arrastar e soltar do arquivo original. O <input type="file"> continua sendo a fonte
    // do envio: o arquivo válido é sempre copiado para ele, e um inválido nunca fica nele.
    Alpine.data('fileDropzone', ({ extensions, maxBytes, formats, maxLabel }) => ({
        file: null,
        previewUrl: null,
        previewFailed: false,
        dragging: false,
        dragDepth: 0,
        error: '',

        input() {
            return this.$root.querySelector('input[type="file"]');
        },

        change(event) {
            const file = event.target.files?.[0] ?? null;

            if (file === null) {
                this.clear();
                return;
            }

            this.accept(file);
        },

        drop(event) {
            this.dragDepth = 0;
            this.dragging = false;

            const files = [...(event.dataTransfer?.files ?? [])];

            if (files.length === 0 || !this.accept(files[0])) return;
            if (files.length > 1) this.error = 'Envie um arquivo por vez: apenas o primeiro foi considerado.';
        },

        // Contador para os eventos de arrastar sobre os elementos filhos não piscarem o destaque.
        dragEnter() {
            this.dragDepth++;
            this.dragging = true;
        },

        dragLeave() {
            this.dragDepth = Math.max(0, this.dragDepth - 1);
            if (this.dragDepth === 0) this.dragging = false;
        },

        accept(file) {
            const problem = this.validate(file);

            if (problem !== '') {
                this.error = problem;
                this.syncInput();
                return false;
            }

            this.revokePreview();
            this.error = '';
            this.file = file;
            this.previewFailed = false;
            if (this.isImage) this.previewUrl = URL.createObjectURL(file);
            this.syncInput();
            // Quem usa o dropzone, como o modal de substituição, acompanha a seleção por este evento.
            this.$dispatch('file-change', { file });

            return true;
        },

        validate(file) {
            const extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';

            if (!extensions.includes(extension)) return `Formato não aceito. Envie ${formats}.`;
            if (file.size > maxBytes) return `O arquivo tem ${formatSize(file.size)}; o limite é ${maxLabel}.`;

            return '';
        },

        // Deixa o input com o arquivo aceito (ou vazio); um arquivo rejeitado não chega ao POST.
        syncInput() {
            const transfer = new DataTransfer();

            if (this.file !== null) transfer.items.add(this.file);
            this.input().files = transfer.files;
        },

        replace() {
            this.input().click();
        },

        remove() {
            this.clear();
            this.$nextTick(() => this.input().focus());
        },

        clear() {
            this.revokePreview();
            this.file = null;
            this.previewFailed = false;
            this.error = '';
            this.syncInput();
            this.$dispatch('file-change', { file: null });
        },

        revokePreview() {
            if (this.previewUrl !== null) {
                URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
            }
        },

        destroy() {
            this.revokePreview();
        },

        get isImage() {
            return this.file?.type.startsWith('image/') ?? false;
        },

        get isPdf() {
            return this.file?.type === 'application/pdf';
        },

        get metadata() {
            if (this.file === null) return '';

            const extension = this.file.name.split('.').pop().toUpperCase();

            return `${extension} · ${formatSize(this.file.size)}`;
        },
    }));

    Alpine.data('quickCatalogModal', () => ({
        open() {
            this.$refs.dialog.showModal();
            this.$nextTick(() => this.$refs.dialog.querySelector('input, select')?.focus());
        },
        close() {
            this.$refs.dialog.close();
        },
        restoreFocus() {
            this.$refs.trigger.focus();
        },
    }));
}

function formatSize(bytes) {
    const megabytes = bytes / 1024 / 1024;

    if (megabytes >= 1) {
        return `${megabytes.toLocaleString('pt-BR', { maximumFractionDigits: 1 })} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024)).toLocaleString('pt-BR')} KB`;
}
