import { validateWithin } from './validate';

// Long forms split into sections. In "steps" mode one section shows at a time with
// Back/Next; "all" mode shows every section with a jump list. The choice is remembered.
export default ({ steps, start = 0, stepped = true, errors = [] }) => ({
    steps,
    step: start,
    stepped: stepped ? Alpine.$persist(true).as('form:stepped') : false,
    visited: new Set([0, start]),
    errors,

    section(i) { return this.$root.querySelector(`[data-step="${i}"]`); },
    isVisible(i) { return !this.stepped || this.step === i; },
    state(i) {
        if (this.errors.includes(i)) return 'error';
        if (i === this.step && this.stepped) return 'current';
        return this.visited.has(i) && i < this.step ? 'done' : 'todo';
    },
    go(i) {
        if (!this.stepped) {
            this.section(i)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        // Moving forward checks the current section first; going back is always allowed.
        if (i > this.step && !this.check()) return;
        this.step = i;
        this.visited.add(i);
        this.$nextTick(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.section(i)?.querySelector('input:not([type=hidden]), select, textarea')?.focus({ preventScroll: true });
        });
    },
    check() {
        const ok = validateWithin(this.section(this.step));
        this.errors = ok ? this.errors.filter((e) => e !== this.step) : [...new Set([...this.errors, this.step])];
        return ok;
    },
    next() { if (this.step < this.steps.length - 1) this.go(this.step + 1); },
    back() { if (this.step > 0) this.go(this.step - 1); },
    submit(e) {
        // Check everything before sending; jump to the first section with a problem.
        const bad = this.steps.findIndex((_, i) => !validateWithin(this.section(i)));
        if (bad !== -1) {
            e.preventDefault();
            delete e.target.dataset.submitting;
            this.errors = [...new Set([...this.errors, bad])];
            if (this.stepped) { this.step = bad; this.$nextTick(() => validateWithin(this.section(bad))); }
        }
    },
});
