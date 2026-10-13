// Site-wide form behaviour:
//  - the clicked submit button shows a spinner and the form can't be double-submitted
//  - forms marked data-guard warn before leaving with unsaved changes
export function installFormHelpers() {
    let dirty = false;

    document.addEventListener('input', (e) => {
        if (e.target.form?.hasAttribute('data-guard')) dirty = true;
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented) return; // a form-level handler (e.g. validation) stopped it
        if (form.dataset.submitting) return e.preventDefault();
        form.dataset.submitting = '1';
        dirty = false;
        (e.submitter ?? form.querySelector('[type=submit]'))?.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('beforeunload', (e) => {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // Restore buttons if the page comes back from bfcache.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-submitting]').forEach((f) => delete f.dataset.submitting);
        document.querySelectorAll('[aria-busy=true]').forEach((b) => b.removeAttribute('aria-busy'));
    });
}
