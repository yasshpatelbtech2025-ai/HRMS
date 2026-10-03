/*
 * Toast notifications (bottom-right on phones, top-right otherwise).
 * Session flashes are shown on page load; anything can add one with
 *   window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success' | 'error' | 'info', message } }))
 */
export function toasts(initial = []) {
    return {
        items: [],
        next: 1,
        init() {
            initial.forEach((toast) => this.add(toast));
            window.addEventListener('toast', (event) => this.add(event.detail));
        },
        add({ type = 'success', message = '' }) {
            if (!message) return;
            const id = this.next++;
            this.items.push({ id, type, message, shown: true });
            setTimeout(() => this.remove(id), type === 'error' ? 8000 : 4500);
        },
        remove(id) {
            const toast = this.items.find((t) => t.id === id);
            if (toast) toast.shown = false;
            setTimeout(() => (this.items = this.items.filter((t) => t.id !== id)), 200);
        },
    };
}
