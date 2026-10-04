/*
 * Confirm before destructive or irreversible actions: any form with
 * data-confirm="message" opens a styled dialog instead of submitting.
 * Delete forms get a red "Delete" button; data-confirm-button sets a label.
 */
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (!form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const method = (form.querySelector('input[name="_method"]')?.value ?? form.method).toUpperCase();
        window.dispatchEvent(
            new CustomEvent('confirm-action', {
                detail: {
                    form,
                    submitter: event.submitter,
                    message: form.dataset.confirm,
                    button: form.dataset.confirmButton ?? (method === 'DELETE' ? 'Delete' : 'Confirm'),
                    danger: method === 'DELETE' || form.hasAttribute('data-confirm-danger'),
                },
            }),
        );
    },
    true,
);

export function confirmDialog() {
    return {
        open: false,
        message: '',
        button: 'Confirm',
        danger: false,
        pending: null,
        init() {
            window.addEventListener('confirm-action', (event) => {
                Object.assign(this, { message: event.detail.message, button: event.detail.button, danger: event.detail.danger, pending: event.detail, open: true });
                this.$nextTick(() => this.$refs.confirm?.focus());
            });
        },
        cancel() {
            this.open = false;
            this.pending = null;
        },
        confirm() {
            const { form, submitter } = this.pending ?? {};
            this.open = false;
            if (!form) return;
            form.dataset.confirmed = '1';
            submitter ? form.requestSubmit(submitter) : form.requestSubmit();
            delete form.dataset.confirmed;
        },
    };
}
