// Backs components/ui/confirm-dialog.blade.php.
export default () => ({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Delete',
    action: '',
    method: 'DELETE',
    hiddenFields: [],
    returnFocus: null,

    ask({ title, message = '', confirmLabel = 'Delete', action, method = 'DELETE', fields = {} }) {
        Object.assign(this, { title, message, confirmLabel, action, method });
        this.hiddenFields = Object.entries(fields).flatMap(([name, value]) =>
            (Array.isArray(value) ? value : [value]).map((v) => ({ name, value: v })));
        this.returnFocus = document.activeElement;
        this.open = true;
        // Focus "Cancel" first is the safer default; Enter on the red button is one Tab away.
        this.$nextTick(() => this.$refs.form.querySelector('button[type=button]')?.focus());
    },
    cancel() {
        this.open = false;
        this.returnFocus?.focus?.();
    },
});
