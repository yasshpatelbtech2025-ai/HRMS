// Per-browser table preferences (hidden columns, density, saved views) and bulk selection.
export default ({ id, noun }) => ({
    noun,
    hidden: Alpine.$persist([]).as(`table:${id}:hidden`),
    density: Alpine.$persist('comfortable').as(`table:${id}:density`),
    views: Alpine.$persist([]).as(`table:${id}:views`),
    newView: '',
    selected: [],

    init() {
        this.$watch('selected', () => this.syncAll());
    },

    get hiddenCss() {
        return this.hidden.map((c) => `[data-table="${id}"] [data-col="${c}"]{display:none}`).join('');
    },
    toggleColumn(key) {
        this.hidden = this.hidden.includes(key) ? this.hidden.filter((k) => k !== key) : [...this.hidden, key];
    },

    saveView() {
        const name = this.newView.trim();
        if (!name) return;
        const url = window.location.pathname + window.location.search;
        this.views = [...this.views.filter((v) => v.name !== name), { name, url }];
        this.newView = '';
        Alpine.store('toasts').push({ message: `View “${name}” saved.` });
    },
    removeView(i) { this.views = this.views.filter((_, n) => n !== i); },

    boxes() { return [...this.$root.querySelectorAll('tbody input[type=checkbox]')]; },
    toggleAll(on) { this.selected = on ? this.boxes().map((b) => b.value) : []; },
    clear() { this.selected = []; },
    syncAll() {
        const all = this.$refs.all;
        if (!all) return;
        const total = this.boxes().length;
        all.checked = total > 0 && this.selected.length === total;
        all.indeterminate = this.selected.length > 0 && this.selected.length < total;
    },

    // CSV of the selected rows, using the columns currently visible.
    exportCsv() {
        const visible = (cell) => cell.dataset.col && getComputedStyle(cell).display !== 'none';
        const quote = (s) => `"${s.replace(/\s+/g, ' ').trim().replace(/"/g, '""')}"`;
        const head = [...this.$root.querySelectorAll('thead th')].filter(visible).map((th) => quote(th.textContent));
        const rows = this.boxes().filter((b) => this.selected.includes(b.value)).map((b) =>
            [...b.closest('tr').querySelectorAll('td')].filter(visible).map((td) => quote(td.textContent)).join(','));
        const blob = new Blob([[head.join(','), ...rows].join('\n')], { type: 'text/csv;charset=utf-8' });
        const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: `${id}-${new Date().toISOString().slice(0, 10)}.csv` });
        a.click();
        URL.revokeObjectURL(a.href);
    },
});
