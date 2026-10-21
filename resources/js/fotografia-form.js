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

    Alpine.data('filePreview', () => ({
        file: null,
        previewUrl: null,
        previewFailed: false,

        selectFile(event) {
            this.revokePreview();
            this.file = event.target.files?.[0] ?? null;
            this.previewFailed = false;

            if (this.isImage) {
                this.previewUrl = URL.createObjectURL(this.file);
            }
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

        get metadata() {
            if (this.file === null) {
                return '';
            }

            const size = (this.file.size / 1024 / 1024).toLocaleString('pt-BR', {
                maximumFractionDigits: 2,
            });

            return `${this.file.type || 'Tipo não identificado'} · ${size} MB`;
        },

        get status() {
            if (this.previewFailed) return 'Este formato não pode ser visualizado pelo navegador. O arquivo continua selecionado.';
            return this.isImage ? 'Imagem pronta para envio.' : 'Arquivo pronto para envio.';
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
