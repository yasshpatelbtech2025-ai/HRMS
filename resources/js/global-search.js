/*
 * Global search in the top bar: employees, companies and pages as you type
 * (SearchController). "/" or Ctrl/Cmd+K focuses it; arrow keys move through
 * the results and Enter opens one.
 */
export function globalSearch(endpoint) {
    return {
        query: '',
        open: false,
        loading: false,
        groups: [],
        active: 0,
        mobileOpen: false,
        timer: null,
        controller: null,

        get flat() {
            return this.groups.flatMap((g) => g.items);
        },

        init() {
            window.addEventListener('keydown', (event) => {
                const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
                if ((event.key === 'k' && (event.metaKey || event.ctrlKey)) || (event.key === '/' && !typing)) {
                    event.preventDefault();
                    this.focus();
                }
            });
        },

        focus() {
            this.mobileOpen = true;
            this.$nextTick(() => (window.innerWidth < 640 ? this.$refs.mobileInput : this.$refs.input)?.focus());
        },

        search() {
            clearTimeout(this.timer);
            if (this.query.trim().length < 2) {
                this.groups = [];
                this.loading = false;
                return;
            }
            this.loading = true;
            this.open = true;
            this.timer = setTimeout(async () => {
                this.controller?.abort();
                this.controller = new AbortController();
                try {
                    const response = await fetch(`${endpoint}?q=${encodeURIComponent(this.query.trim())}`, {
                        headers: { Accept: 'application/json' },
                        signal: this.controller.signal,
                    });
                    const data = await response.json();
                    this.groups = [
                        { label: 'Employees', items: data.employees ?? [] },
                        { label: 'Companies', items: data.companies ?? [] },
                        { label: 'Pages', items: data.pages ?? [] },
                    ].filter((g) => g.items.length);
                    this.active = 0;
                } catch (error) {
                    if (error.name !== 'AbortError') this.groups = [];
                } finally {
                    this.loading = false;
                }
            }, 200);
        },

        move(step) {
            if (!this.flat.length) return;
            this.active = (this.active + step + this.flat.length) % this.flat.length;
            this.$nextTick(() => document.querySelector(`[data-search-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
        },

        go() {
            const item = this.flat[this.active];
            if (item) window.location.href = item.url;
        },

        indexOf(item) {
            return this.flat.indexOf(item);
        },

        close() {
            this.open = false;
            this.mobileOpen = false;
        },
    };
}
