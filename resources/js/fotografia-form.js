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

    Alpine.data('relationshipPicker', (initialSelectedCount) => ({
        search: '',
        selectedCount: initialSelectedCount,
        showNoResults: false,

        init() {
            this.filterOptions();
            this.refreshSelection();
        },

        filterOptions() {
            const term = this.search.trim().toLocaleLowerCase('pt-BR');
            let visible = 0;

            this.$refs.options.querySelectorAll('[data-option]').forEach((option) => {
                const matches = option.dataset.searchValue.includes(term);

                option.hidden = !matches;

                if (matches) {
                    visible++;
                }
            });

            this.showNoResults = term !== '' && visible === 0;
        },

        refreshSelection() {
            this.selectedCount = this.$refs.options.querySelectorAll('input[type="checkbox"]:checked').length;
        },

        refresh() {
            this.filterOptions();
            this.refreshSelection();
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
