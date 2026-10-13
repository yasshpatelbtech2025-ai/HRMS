// Searchable select. `source` is [{ value, label }]; see components/ui/combobox.blade.php.
export default ({ value = '', emptyLabel = null } = {}) => ({
    value,
    source: [],
    query: '',
    open: false,
    active: 0,

    get options() {
        const list = this.source.map((o) => ({ value: String(o.value ?? o.id), label: o.label ?? o.name }));
        return emptyLabel !== null ? [{ value: '', label: emptyLabel }, ...list] : list;
    },
    get filtered() {
        const q = this.query.trim().toLowerCase();
        return q ? this.options.filter((o) => o.label.toLowerCase().includes(q)) : this.options;
    },
    get selectedLabel() {
        const hit = this.options.find((o) => o.value === String(this.value));
        return hit && hit.value !== '' ? hit.label : '';
    },

    init() {
        // Drop a value that no longer exists in a dependent list (e.g. department after company change).
        this.$watch('source', () => {
            if (this.value !== '' && !this.options.some((o) => o.value === String(this.value))) this.value = '';
        });
    },
    openList() {
        if (this.open) return;
        this.query = '';
        this.open = true;
        this.active = Math.max(0, this.filtered.findIndex((o) => o.value === String(this.value)));
        this.$nextTick(() => this.$refs.input.select());
    },
    close() { this.open = false; this.query = ''; },
    move(step) {
        if (!this.open) return this.openList();
        const n = this.filtered.length;
        if (!n) return;
        this.active = (this.active + step + n) % n;
        this.$nextTick(() => this.$refs.list.children[this.active + 1]?.scrollIntoView({ block: 'nearest' }));
    },
    choose(option) {
        if (!option) return;
        this.value = option.value;
        this.close();
        // After the hidden input has the new value, so listeners (validation) see it.
        this.$nextTick(() => this.$refs.input.dispatchEvent(new Event('change', { bubbles: true })));
    },
});
