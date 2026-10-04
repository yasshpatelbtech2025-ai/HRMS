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
        add({ type = 'success', message = '', undo = null }) {
            if (!message) return;
            const id = this.next++;
            this.items.push({ id, type, message, undo, shown: true });
            // A toast with Undo stays longer, so there is time to use it.
            setTimeout(() => this.remove(id), type === 'error' ? 8000 : undo ? 10000 : 4500);
        },
        undo(toast) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = toast.undo;
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        },
        remove(id) {
            const toast = this.items.find((t) => t.id === id);
            if (toast) toast.shown = false;
            setTimeout(() => (this.items = this.items.filter((t) => t.id !== id)), 200);
        },
    };
}
