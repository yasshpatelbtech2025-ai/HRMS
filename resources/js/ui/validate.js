// Inline validation that mirrors the server rules, with plain-language messages.
// Fields opt in with data-rules="required|digits:10|pan" and data-label="Mobile number".
// The server still validates everything; this only gives earlier feedback.

const digitsOnly = (v) => v.replace(/[\s-]+/g, '');

const checks = {
    required: (v) => v.trim() !== '' || 'Please enter {label}.',
    digits: (v, n) => !v || new RegExp(`^\\d{${n}}$`).test(digitsOnly(v)) || `{Label} should be exactly ${n} digits.`,
    'digits-between': (v, range) => {
        const [min, max] = range.split(',');
        return !v || new RegExp(`^\\d{${min},${max}}$`).test(digitsOnly(v)) || `{Label} should be ${min} to ${max} digits.`;
    },
    email: (v) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'This doesn’t look like an email address (example: name@gmail.com).',
    pan: (v) => !v || /^[A-Z]{5}[0-9]{4}[A-Z]$/.test(v.trim().toUpperCase()) || 'PAN should look like ABCDE1234F: 5 letters, 4 numbers, then 1 letter.',
    ifsc: (v) => !v || /^[A-Z]{4}0[A-Z0-9]{6}$/.test(v.trim().toUpperCase()) || 'IFSC should look like SBIN0001234: 4 letters, a zero, then 6 letters or numbers.',
    account: (v) => !v || /^\d{6,34}$/.test(digitsOnly(v)) || 'Account number should be 6 to 34 digits, numbers only.',
    past: (v) => !v || new Date(v) < new Date(new Date().toDateString()) || '{Label} must be a date in the past.',
    'required-if': (v, cond, form) => {
        const [other, expected] = cond.split(',');
        const current = form.querySelector(`[name="${other}"]:checked, select[name="${other}"], input[name="${other}"]:not([type=radio])`)?.value;
        return current !== expected || v.trim() !== '' || 'Please enter {label}.';
    },
    'not-before': (v, other, form) => {
        const field = form.querySelector(`[name="${other}"]`);
        return !v || !field?.value || v >= field.value || `{Label} can’t be before the ${field.dataset.label?.toLowerCase() ?? other}.`;
    },
};

export function validateField(el) {
    if (!el.dataset.rules || el.disabled) return clear(el), true;
    const label = el.dataset.label ?? el.name;
    // Comboboxes validate the hidden input that actually gets submitted.
    // (by name: form.elements[x] would also match the visible input's id)
    const value = el.dataset.valueFrom ? (el.form?.querySelector(`[name="${el.dataset.valueFrom}"]`)?.value ?? '') : (el.value ?? '');

    for (const rule of el.dataset.rules.split('|')) {
        const [name, arg] = rule.split(':');
        const result = checks[name]?.(value, arg, el.form);
        if (typeof result === 'string') {
            show(el, result.replace('{label}', label.toLowerCase()).replace('{Label}', label));
            return false;
        }
    }
    clear(el);
    return true;
}

function errorEl(el) {
    return el.form?.querySelector(`[data-error-for="${el.id}"]`);
}
function show(el, message) {
    el.setAttribute('aria-invalid', 'true');
    el.setAttribute('aria-describedby', `${el.id}-error`);
    const box = errorEl(el);
    if (box) { box.querySelector('span').textContent = message; box.classList.remove('hidden'); }
}
function clear(el) {
    el.removeAttribute('aria-invalid');
    const box = errorEl(el);
    if (box) box.classList.add('hidden');
}

/** Validate on blur, and re-check while typing once a field has been flagged. */
export function installInlineValidation() {
    document.addEventListener('focusout', (e) => {
        if (e.target.dataset?.rules && e.target.value !== '') validateField(e.target);
        else if (e.target.dataset?.rules?.includes('required') && e.target.dataset.touched) validateField(e.target);
    });
    document.addEventListener('change', (e) => {
        if (e.target.dataset?.rules && e.target.getAttribute('aria-invalid') === 'true') validateField(e.target);
    });
    document.addEventListener('input', (e) => {
        if (!e.target.dataset?.rules) return;
        e.target.dataset.touched = '1';
        if (e.target.getAttribute('aria-invalid') === 'true') validateField(e.target);
    });
}

/** Validate every rule-bearing field inside `root`; focus the first failure. */
export function validateWithin(root) {
    const fields = [...root.querySelectorAll('[data-rules]')];
    const failed = fields.filter((f) => !validateField(f));
    failed[0]?.focus();
    return failed.length === 0;
}
