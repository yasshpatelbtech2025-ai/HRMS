/*
 * Warn before leaving a page with unsaved changes. Applies to forms marked
 * data-unsaved-warning (the long add/edit forms): once a field is changed,
 * closing the tab or following a link asks first. Submitting the form, or
 * pressing Cancel on purpose (data-discard), does not.
 */
const dirty = new Set();

document.addEventListener('input', (e) => {
    const form = e.target.form ?? e.target.closest?.('form');
    if (form?.hasAttribute('data-unsaved-warning')) {
        dirty.add(form);
        form.dispatchEvent(new CustomEvent('unsaved-change', { bubbles: true }));
    }
});
document.addEventListener('change', (e) => {
    const form = e.target.form;
    if (form?.hasAttribute('data-unsaved-warning')) dirty.add(form);
});
document.addEventListener('submit', (e) => dirty.delete(e.target));
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-discard]')) dirty.clear();
});

window.addEventListener('beforeunload', (e) => {
    if ([...dirty].some((form) => form.isConnected)) {
        e.preventDefault();
        e.returnValue = '';
    }
});
