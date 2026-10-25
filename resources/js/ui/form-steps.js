import { validateWithin } from '../inline-validation';

// Long forms as steps (like the first version): the form's sections ([data-step-section])
// become steps with a progress list; "Show all sections" lists them on one page instead.
// Next checks the current step first; going back or jumping to an earlier step never does.
export default ({ stepped = true, remember = true } = {}) => ({
    stepped: remember ? Alpine.$persist(stepped).as('form:stepped') : stepped,
    steps: [],
    step: 0,
    visited: [0],
    sections: [],

    init() {
        this.sections = [...this.$root.querySelectorAll('[data-step-section]')];
        this.steps = this.sections.map((s) => ({ title: s.dataset.stepTitle, error: s.dataset.hasError === '1', required: s.hasAttribute('data-step-required') }));
        const firstError = this.steps.findIndex((s) => s.error);
        if (firstError > -1) this.step = firstError;
        this.visited = [...Array(this.step + 1).keys()];
        this.apply();
        this.$watch('stepped', () => this.apply());
        // The error summary's links jump straight to the step that holds the field.
        this.sections.forEach((s, i) => s.addEventListener('form-step-show', () => {
            if (this.stepped && i !== this.step) { this.step = i; if (!this.visited.includes(i)) this.visited.push(i); this.apply(); }
        }));
    },
    apply() {
        this.sections.forEach((s, i) => {
            s.style.display = !this.stepped || i === this.step ? '' : 'none';
            if (this.stepped && i === this.step) s.dispatchEvent(new CustomEvent('form-step-open'));
        });
    },
    state(i) {
        if (this.steps[i]?.error) return 'error';
        if (this.stepped && i === this.step) return 'current';
        return this.visited.includes(i) && i < this.step ? 'done' : 'todo';
    },
    go(i) {
        if (!this.stepped) {
            this.sections[i].dispatchEvent(new CustomEvent('form-step-open'));
            this.sections[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (i > this.step && !this.check()) return;
        this.step = i;
        if (!this.visited.includes(i)) this.visited.push(i);
        this.apply();
        this.$nextTick(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.sections[i].querySelector('input:not([type=hidden]), select, textarea')?.focus({ preventScroll: true });
        });
    },
    check() {
        const ok = validateWithin(this.sections[this.step]);
        this.steps[this.step].error = !ok;
        return ok;
    },
    next() { if (this.step < this.steps.length - 1) this.go(this.step + 1); },
    back() { if (this.step > 0) this.go(this.step - 1); },
    get last() { return this.step === this.steps.length - 1; },
    get progress() { return this.steps.length ? Math.round(((this.step + 1) / this.steps.length) * 100) : 0; },
});
