export default function registerConfirmationDialog(Alpine) {
    Alpine.data('confirmationDialog', () => ({
        form: null,
        submitter: null,
        trigger: null,
        title: '',
        message: '',
        confirmLabel: '',
        variant: 'danger',
        submitting: false,

        intercept(event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage) {
                return;
            }

            if (this.submitting && this.form === form) {
                return;
            }

            event.preventDefault();

            if (this.form !== null) {
                return;
            }

            this.form = form;
            this.submitter = event.submitter;
            this.trigger = event.submitter ?? document.activeElement;
            this.title = form.dataset.confirmTitle || 'Confirmar ação';
            this.message = form.dataset.confirmMessage;
            this.confirmLabel = form.dataset.confirmLabel || 'Confirmar';
            this.variant = form.dataset.confirmVariant || 'danger';

            this.$refs.dialog.showModal();
            this.$nextTick(() => this.$refs.cancel.focus());
        },

        approve() {
            if (this.form === null || this.submitting) {
                return;
            }

            this.submitting = true;
            this.form.setAttribute('aria-busy', 'true');
            this.form.requestSubmit(this.submitter ?? undefined);
            this.form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
                control.disabled = true;
            });
            this.$refs.dialog.close('confirmed');
        },

        cancel() {
            if (!this.submitting) {
                this.$refs.dialog.close('cancelled');
            }
        },

        restoreFocus() {
            if (this.submitting) {
                return;
            }

            const trigger = this.trigger;

            this.form = null;
            this.submitter = null;
            this.trigger = null;
            this.title = '';
            this.message = '';
            this.confirmLabel = '';
            this.variant = 'danger';

            trigger?.focus();
        },
    }));
}
