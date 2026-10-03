/*
 * Loading feedback for a server-rendered app: a thin progress bar at the top
 * while the next page loads, and a spinner on a form's submit button while
 * it is being sent (so it cannot be clicked twice). Downloads and links that
 * open elsewhere are left alone.
 */

const bar = () => document.getElementById('page-progress');

function start() {
    bar()?.classList.remove('hidden');
    // A download or a cancelled navigation never unloads the page: give up after a while.
    clearTimeout(start.timer);
    start.timer = setTimeout(stop, 12000);
}

function stop() {
    bar()?.classList.add('hidden');
    document.querySelectorAll('[data-loading]').forEach((button) => {
        button.removeAttribute('data-loading');
        button.disabled = false;
        button.querySelector('.spinner[data-added]')?.remove();
    });
}

const isDownload = (url) => /\/export|[?&]output=(pdf|csv|xlsx)|\/(file|image|signed-copy|attachment)\b|[?&]format=/.test(url);

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const href = link.getAttribute('href');
    if (link.target === '_blank' || link.hasAttribute('download') || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || isDownload(url.href)) return;
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;
    start();
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.target === '_blank' || form.hasAttribute('data-no-loading')) return;
    if (isDownload(form.action || '')) return;
    start();
    const button = event.submitter ?? form.querySelector('button[type="submit"], button:not([type])');
    if (button && !button.hasAttribute('data-loading')) {
        button.setAttribute('data-loading', '');
        const spinner = document.createElement('span');
        spinner.className = 'spinner me-2 align-[-2px]';
        spinner.setAttribute('data-added', '');
        spinner.setAttribute('aria-hidden', 'true');
        button.prepend(spinner);
        // Disable after the submit has been dispatched, so the button's own name/value is still sent.
        setTimeout(() => (button.disabled = true), 0);
    }
});

// Back/forward cache: the page comes back as it was left.
window.addEventListener('pageshow', stop);
