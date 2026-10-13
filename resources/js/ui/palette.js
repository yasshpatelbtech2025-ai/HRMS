// Command palette (Ctrl/Cmd+K): jump to pages, find employees and companies.
export default ({ pages, searchUrl }) => ({
    open: false,
    query: '',
    active: 0,
    loading: false,
    remote: { employees: [], companies: [] },
    abort: null,
    timer: null,

    get results() {
        const q = this.query.trim().toLowerCase();
        const pageHits = pages
            .filter((p) => !q || `${p.label} ${p.section ?? ''} ${p.keywords}`.toLowerCase().includes(q))
            .slice(0, q ? 6 : 8)
            .map((p) => ({ ...p, group: 'Pages', icon: 'page' }));
        const people = this.remote.employees.map((e) => ({ ...e, group: 'Employees', icon: 'person' }));
        const orgs = this.remote.companies.map((c) => ({ ...c, group: 'Companies', icon: 'org' }));
        return [...people, ...orgs, ...pageHits];
    },
    get groups() {
        const out = [];
        this.results.forEach((r, i) => {
            let g = out.find((x) => x.name === r.group);
            if (!g) out.push((g = { name: r.group, items: [] }));
            g.items.push({ ...r, index: i });
        });
        return out;
    },

    show() {
        this.open = true;
        this.query = '';
        this.active = 0;
        this.remote = { employees: [], companies: [] };
        this.$nextTick(() => this.$refs.input.focus());
    },
    hide() { this.open = false; },

    onInput() {
        this.active = 0;
        clearTimeout(this.timer);
        const q = this.query.trim();
        if (q.length < 2) {
            this.loading = false;
            this.remote = { employees: [], companies: [] };
            return;
        }
        this.loading = true;
        this.timer = setTimeout(() => this.search(q), 180);
    },
    async search(q) {
        this.abort?.abort();
        this.abort = new AbortController();
        try {
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json' },
                signal: this.abort.signal,
            });
            if (res.ok) this.remote = await res.json();
        } catch (e) {
            if (e.name !== 'AbortError') this.remote = { employees: [], companies: [] };
        } finally {
            if (q === this.query.trim()) this.loading = false;
        }
    },

    move(step) {
        const n = this.results.length;
        if (!n) return;
        this.active = (this.active + step + n) % n;
        this.$nextTick(() => this.$refs.list.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
    },
    go(item = this.results[this.active]) {
        if (item) window.location.href = item.url;
    },
});
