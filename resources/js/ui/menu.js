// Dropdown menu with keyboard support: ↓ opens, ↑/↓ move, Esc closes and returns focus.
// The panel is position: fixed and placed from the trigger, so it is never clipped by a
// scrolling table and always paints above the page. It flips upward near the bottom edge.
export function placeBelow(trigger, panel, align = 'start') {
    const t = trigger.getBoundingClientRect();
    const w = panel.offsetWidth;
    const h = panel.offsetHeight;
    let top = t.bottom + 6;
    if (top + h > window.innerHeight - 8 && t.top - h - 6 > 8) top = t.top - h - 6;
    let left = align === 'end' ? t.right - w : t.left;
    left = Math.max(8, Math.min(left, window.innerWidth - w - 8));
    return `top:${Math.round(top)}px;left:${Math.round(left)}px`;
}

export default ({ align = 'end' } = {}) => ({
    open: false,
    pos: '',

    init() {
        const dismiss = (e) => { if (this.open && !this.$refs.panel.contains(e.target)) this.close(); };
        window.addEventListener('scroll', dismiss, true);
        window.addEventListener('resize', dismiss);
    },
    show() {
        this.open = true;
        this.$nextTick(() => { this.pos = placeBelow(this.$refs.trigger, this.$refs.panel, align); });
    },
    toggle() { this.open ? this.close() : this.show(); },
    close(refocus = false) {
        if (!this.open) return;
        this.open = false;
        if (refocus) this.$refs.trigger.querySelector('button, a')?.focus();
    },
    items() { return [...this.$refs.panel.querySelectorAll('[role=menuitem], input, button:not([disabled])')]; },
    openAndFocus() {
        this.show();
        this.$nextTick(() => this.items()[0]?.focus());
    },
    focusNext(step) {
        const items = this.items();
        const i = items.indexOf(document.activeElement);
        items[(i + step + items.length) % items.length]?.focus();
    },
});
