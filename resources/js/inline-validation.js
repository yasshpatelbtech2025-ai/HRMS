/*
 * Inline validation: a field is checked when you leave it (and again as
 * you correct it), and an invalid form shows every error under its field
 * instead of the browser's bubbles. The rules are the fields' own
 * (required, type, min/max, length) plus the formats the server checks,
 * matched by field name. The server still validates everything.
 */

// [field name pattern, input pattern, message] — the same formats as the server (e.g. EmployeeRequest).
const FORMATS = [
    [/(^|\[)aadhar_card_no\]?$/, '\\s*(?:\\d\\s*){12}', 'Aadhaar number must be 12 digits.'],
    [/(^|\[)(pan_card_no|pan)\]?$/, '\\s*[A-Za-z]{5}[0-9]{4}[A-Za-z]\\s*', 'PAN must look like ABCDE1234F.'],
    [/(^|\[)ifsc_code\]?$/, '\\s*[A-Za-z]{4}0[A-Za-z0-9]{6}\\s*', 'IFSC must look like SBIN0001234.'],
    [/(^|\[)uan_code\]?$/, '\\s*\\d{12}\\s*', 'UAN must be 12 digits.'],
    [/(^|\[)micr_code\]?$/, '\\s*\\d{9}\\s*', 'MICR code must be 9 digits.'],
    [/(^|\[)account_number\]?$/, '\\s*(?:\\d\\s*){6,20}', 'Account number must be 6 to 20 digits.'],
    [/(^|\[)pin_code\]?$/, '\\s*\\d{6}\\s*', 'PIN code must be 6 digits.'],
    [/(^|\[)(phone|mobile|alternate_phone)\]?$/, '\\s*\\+?[0-9][0-9 \\-]{8,16}\\s*', 'Enter a valid phone number (10 digits).'],
];

const FIELDS = 'input:not([type=hidden]):not([type=submit]):not([type=button]):not([type=checkbox]):not([type=radio]), select, textarea';

function applyFormat(field) {
    if (field.dataset.formatApplied || !field.name || field.type === 'file') return;
    field.dataset.formatApplied = '1';
    const rule = FORMATS.find(([name]) => name.test(field.name));
    if (rule && !field.pattern) {
        field.pattern = rule[1];
        field.dataset.formatMessage = rule[2];
    }
}

function message(field) {
    const v = field.validity;
    if (v.valueMissing) return 'This field is required.';
    if (v.patternMismatch && field.dataset.formatMessage) return field.dataset.formatMessage;
    if (v.typeMismatch && field.type === 'email') return 'Enter a valid email address.';
    if (v.rangeUnderflow) return `Must be at least ${field.min}.`;
    if (v.rangeOverflow) return `Must be at most ${field.max}.`;
    if (v.tooLong) return `Must be at most ${field.maxLength} characters.`;
    if (v.tooShort) return `Must be at least ${field.minLength} characters.`;
    return field.validationMessage;
}

// Where the message goes: after the field (after a wrapping relative box, e.g. an input with an icon).
function anchor(field) {
    const parent = field.parentElement;
    return parent?.classList.contains('relative') && parent.children.length <= 3 ? parent : field;
}

function show(field, text) {
    const box = anchor(field);
    let el = box.nextElementSibling?.matches('[data-field-error]') ? box.nextElementSibling : null;
    if (!el) {
        el = document.createElement('p');
        el.dataset.fieldError = '';
        el.className = 'mt-1 text-sm text-red-600';
        el.setAttribute('role', 'alert');
        box.after(el);
    }
    el.textContent = text;
    field.setAttribute('aria-invalid', 'true');
    field.classList.add('!border-red-500', 'focus:!ring-red-500');
}

function clear(field) {
    const box = anchor(field);
    if (box.nextElementSibling?.matches('[data-field-error]')) box.nextElementSibling.remove();
    field.removeAttribute('aria-invalid');
    field.classList.remove('!border-red-500', 'focus:!ring-red-500');
    // The server's message for this field is out of date once it is edited.
    field.closest('div')?.querySelectorAll(':scope > ul.text-red-600').forEach((ul) => ul.remove());
}

function check(field) {
    applyFormat(field);
    if (field.disabled || field.readOnly) return true;
    if (field.checkValidity()) {
        clear(field);
        return true;
    }
    show(field, message(field));
    return false;
}

/** Check every visible field inside `root` (one form step); focus the first problem. */
export function validateWithin(root) {
    const fields = [...root.querySelectorAll(FIELDS)].filter((f) => f.form && !f.form.hasAttribute('data-no-validate'));
    fields.forEach(applyFormat);
    const invalid = fields.filter((f) => visible(f) && !check(f));
    if (invalid.length) {
        invalid[0].focus({ preventScroll: true });
        invalid[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
    return invalid.length === 0;
}

const visible = (el) => !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
const inScope = (field) => field.form && !field.form.hasAttribute('data-no-validate') && field.matches(FIELDS);

document.addEventListener('focusout', (event) => {
    if (inScope(event.target) && (event.target.value !== '' || event.target.dataset.touched)) check(event.target);
});

document.addEventListener('input', (event) => {
    const field = event.target;
    if (!inScope(field)) return;
    field.dataset.touched = '1';
    // Once an error shows, update it as the user types.
    if (field.getAttribute('aria-invalid') === 'true' || anchor(field).nextElementSibling?.matches('ul.text-red-600')) check(field);
});

// Runs before the confirm dialog and the loading spinner (both listen later).
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (form.hasAttribute('data-no-validate')) return;
        const fields = [...form.querySelectorAll(FIELDS)];
        fields.forEach(applyFormat);
        const invalid = fields.filter((f) => visible(f) && !check(f));
        if (invalid.length) {
            event.preventDefault();
            event.stopImmediatePropagation();
            invalid[0].focus({ preventScroll: true });
            invalid[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    },
    true,
);

// Our messages replace the browser's bubbles (fields in hidden steps are left to the server).
const disableBubbles = () => document.querySelectorAll('form:not([data-no-validate])').forEach((form) => (form.noValidate = true));
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', disableBubbles) : disableBubbles();
