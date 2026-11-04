export default function registerContributionForm(Alpine) {
    Alpine.data('contributionForm', () => ({
        files: [],
        fileError: '',
        submitting: false,

        selectFiles(event) {
            this.files.forEach((file) => URL.revokeObjectURL(file.url));
            this.files = [];
            this.fileError = '';

            const selected = Array.from(event.target.files);

            if (selected.length > 5) {
                this.fileError = 'Selecione no máximo cinco fotografias.';
            } else if (selected.some((file) => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024)) {
                this.fileError = 'Escolha imagens JPG, PNG ou WebP com até 10 MB cada.';
            }

            if (this.fileError) {
                event.target.value = '';
                return;
            }

            this.files = selected.map((file) => ({ name: file.name, url: URL.createObjectURL(file) }));
        },

        destroy() {
            this.files.forEach((file) => URL.revokeObjectURL(file.url));
        },
    }));
}
