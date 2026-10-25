/*
 * Typing help for Indian ID and contact fields, matched by field name (so it
 * works on every form without changing the markup): the right keyboard on
 * phones, capital letters where the format needs them, and Aadhaar shown in
 * groups of four. What is sent is what the server already accepts: Aadhaar and
 * account spaces are removed again just before the form is submitted.
 */
const MASKS = [
    { name: /(^|\[)aadhar_card_no\]?$/, inputmode: 'numeric', group: 4, max: 12 },
    { name: /(^|\[)account_number\]?$/, inputmode: 'numeric', digitsOnly: true },
    { name: /(^|\[)(uan_code|micr_code|pin_code)\]?$/, inputmode: 'numeric', digitsOnly: true },
    { name: /(^|\[)(phone|mobile|alternate_phone)\]?$/, inputmode: 'tel', autocomplete: 'tel' },
    { name: /(^|\[)(pan_card_no|pan|ifsc_code)\]?$/, upper: true, autocomplete: 'off' },
];

const ruleFor = (field) => field.name && MASKS.find((m) => m.name.test(field.name));

function prepare(field) {
    if (field.dataset.mask || field.closest('.sheet') || !(field instanceof HTMLInputElement)) return;
    const rule = ruleFor(field);
    if (!rule) return;
    field.dataset.mask = '1';
    if (rule.inputmode) field.inputMode = rule.inputmode;
    if (rule.autocomplete) field.autocomplete = rule.autocomplete;
    if (rule.upper) field.style.textTransform = 'uppercase';
    if (rule.group) format(field, rule);
}

function format(field, rule) {
    const digits = field.value.replace(/\D/g, '').slice(0, rule.max ?? 99);
    const grouped = digits.replace(new RegExp(`(\\d{${rule.group}})(?=\\d)`, 'g'), '$1 ');
    if (grouped !== field.value) {
        const fromEnd = field.value.length - (field.selectionStart ?? field.value.length);
        field.value = grouped;
        const at = Math.max(0, grouped.length - fromEnd);
        try { field.setSelectionRange(at, at); } catch (e) { /* not focused */ }
    }
}

document.addEventListener('focusin', (e) => prepare(e.target));
document.addEventListener('input', (e) => {
    const field = e.target;
    const rule = field.dataset?.mask && ruleFor(field);
    if (!rule) return;
    if (rule.group) format(field, rule);
    else if (rule.digitsOnly && /\D/.test(field.value)) field.value = field.value.replace(/\D/g, '');
});

// Before inline validation and the server see it: send Aadhaar digits only.
document.addEventListener('submit', (e) => {
    e.target.querySelectorAll?.('input[data-mask]').forEach((field) => {
        if (ruleFor(field)?.group) field.value = field.value.replace(/\s+/g, '');
    });
}, true);

const prepareAll = () => document.querySelectorAll('input[name]').forEach(prepare);
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', prepareAll) : prepareAll();
