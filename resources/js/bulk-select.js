/*
 * Row selection for bulk actions on a list: row checkboxes carry
 * data-bulk-id and x-model="selected"; the header checkbox selects or clears
 * every row on the page; <x-bulk-bar> acts on the selection.
 */
export function bulkSelect() {
    return {
        selected: [],
        pageIds() {
            return [...this.$root.querySelectorAll('[data-bulk-id]')].map((el) => el.value);
        },
        allOnPage() {
            const ids = this.pageIds();
            return ids.length > 0 && ids.every((id) => this.selected.includes(id));
        },
        someOnPage() {
            return this.pageIds().some((id) => this.selected.includes(id)) && !this.allOnPage();
        },
        toggleAll(checked) {
            const ids = this.pageIds();
            this.selected = checked ? [...new Set([...this.selected, ...ids])] : this.selected.filter((id) => !ids.includes(id));
        },
        // A group's checkbox (e.g. one compliance task per employee).
        groupState(ids) {
            const chosen = ids.filter((id) => this.selected.includes(id)).length;
            return chosen === 0 ? 'none' : chosen === ids.length ? 'all' : 'some';
        },
        toggleIds(ids, checked) {
            this.selected = checked ? [...new Set([...this.selected, ...ids])] : this.selected.filter((id) => !ids.includes(id));
        },
        clear() {
            this.selected = [];
        },
        withIds(url) {
            const params = new URLSearchParams();
            this.selected.forEach((id) => params.append('ids[]', id));
            return `${url}${url.includes('?') ? '&' : '?'}${params}`;
        },
    };
}
