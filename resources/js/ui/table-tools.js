// List tools for any page table (components/list-toolbar): hide/show columns, row density
// and saved views, remembered per list in this browser. Works on the table that follows
// the toolbar in its card, by column position, so pages need no changes.
export default ({ id }) => ({
    hidden: Alpine.$persist([]).as(`table:${id}:hidden`),
    density: Alpine.$persist('comfortable').as(`table:${id}:density`),
    views: Alpine.$persist([]).as(`table:${id}:views`),
    newView: '',
    columns: [],
    table: null,

    init() {
        this.table = this.$root.closest('.rounded-lg')?.querySelector('table');
        if (!this.table) return;
        this.table.dataset.tt = id;
        const head = this.table.tHead?.rows[this.table.tHead.rows.length - 1];
        // Every named column except the selection checkbox, serial number and actions can be hidden.
        this.columns = [...(head?.cells ?? [])]
            .map((th, i) => ({ index: i + 1, label: th.innerText.replace(/\s+/g, ' ').trim() }))
            .filter((c) => c.label && !['Sl', 'Action', 'Actions'].includes(c.label));
        this.$watch('density', (d) => { this.table.dataset.density = d; });
        this.table.dataset.density = this.density;
    },

    get css() {
        return this.hidden.map((n) => `table[data-tt="${id}"] tr > :nth-child(${n}){display:none}`).join('');
    },
    toggle(index) {
        this.hidden = this.hidden.includes(index) ? this.hidden.filter((n) => n !== index) : [...this.hidden, index];
    },

    saveView() {
        const name = this.newView.trim();
        if (!name) return;
        this.views = [...this.views.filter((v) => v.name !== name), { name, url: location.pathname + location.search }];
        this.newView = '';
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: `View “${name}” saved.` } }));
    },
    removeView(i) { this.views = this.views.filter((_, n) => n !== i); },
});
