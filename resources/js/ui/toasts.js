// Global toast store. Errors stay until dismissed; others auto-hide (undo toasts get longer).
let seq = 0;

export default {
    items: [],
    load(list) { list.forEach((t) => this.push(t)); },
    push({ type = 'success', message, undo = null }) {
        const toast = { id: ++seq, type, message, undo, visible: true, timer: null, remaining: undo ? 10000 : 5000 };
        this.items.push(toast);
        if (type !== 'error') this.resume(toast);
        return toast;
    },
    pause(toast) { clearTimeout(toast.timer); },
    resume(toast) {
        if (toast.type === 'error') return;
        toast.timer = setTimeout(() => this.dismiss(toast), toast.remaining);
    },
    dismiss(toast) {
        toast.visible = false;
        setTimeout(() => { this.items = this.items.filter((t) => t.id !== toast.id); }, 200);
    },
};
