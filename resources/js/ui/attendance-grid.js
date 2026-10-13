// Attendance grid interaction model (design prototype).
// Built for 500 rows × 31 days: no per-cell Alpine bindings. One delegated listener,
// cells updated directly, summaries recomputed only for rows that changed.

export const STATUSES = {
    P: { label: 'Present', paid: 1 },
    A: { label: 'Absent', paid: 0 },
    H: { label: 'Half day', paid: 0.5 },
    L: { label: 'Leave', paid: 1 },
    W: { label: 'Weekly off', paid: 1 },
    F: { label: 'Holiday', paid: 1 },
    M: { label: 'Missing punch', paid: 0 },
};

export function attendanceGrid(root, { onChange = () => {} } = {}) {
    const table = root.querySelector('table');
    const body = table.tBodies[0];
    const first = Number(table.dataset.firstDayCol); // index of the first day cell in a row
    const days = Number(table.dataset.days);
    const rows = body.rows.length;
    const cell = (r, c) => body.rows[r]?.cells[first + c];

    let focus = { r: 0, c: 0 };
    let anchor = { r: 0, c: 0 };
    let dragging = false;
    const undo = [];
    const dirty = new Set();

    const rect = () => ({
        r1: Math.min(anchor.r, focus.r), r2: Math.max(anchor.r, focus.r),
        c1: Math.min(anchor.c, focus.c), c2: Math.max(anchor.c, focus.c),
    });
    const eachSelected = (fn) => {
        const { r1, r2, c1, c2 } = rect();
        for (let r = r1; r <= r2; r++) for (let c = c1; c <= c2; c++) fn(cell(r, c), r, c);
    };

    let painted = [];
    function paintSelection() {
        painted.forEach((td) => td.classList.remove('is-sel'));
        painted = [];
        const { r1, r2, c1, c2 } = rect();
        if (r1 === r2 && c1 === c2) return;
        eachSelected((td) => { td.classList.add('is-sel'); painted.push(td); });
    }

    function moveTo(r, c, extend = false) {
        r = Math.max(0, Math.min(rows - 1, r));
        c = Math.max(0, Math.min(days - 1, c));
        cell(focus.r, focus.c).tabIndex = -1;
        focus = { r, c };
        if (!extend) anchor = { r, c };
        const td = cell(r, c);
        td.tabIndex = 0;
        td.focus({ preventScroll: false });
        paintSelection();
        root.dispatchEvent(new CustomEvent('grid:focus', { detail: { row: body.rows[r].dataset.name, day: c + 1, count: selectionSize() } }));
    }
    const selectionSize = () => { const { r1, r2, c1, c2 } = rect(); return (r2 - r1 + 1) * (c2 - c1 + 1); };

    function set(status, { onlyBlank = false } = {}) {
        const batch = [];
        const touched = new Set();
        eachSelected((td, r) => {
            const before = td.dataset.s ?? '';
            if (onlyBlank && before) return;
            if (before === status) return;
            batch.push([td, before]);
            write(td, status);
            touched.add(r);
        });
        if (!batch.length) return 0;
        undo.push(batch);
        touched.forEach(summarise);
        onChange(dirty.size);
        return batch.length;
    }
    function write(td, status) {
        if (status) { td.dataset.s = status; td.textContent = status; } else { delete td.dataset.s; td.textContent = ''; }
        td.setAttribute('aria-label', `${td.dataset.day}: ${status ? STATUSES[status].label : 'Not marked'}`);
        dirty.add(td);
    }
    function summarise(r) {
        const tr = body.rows[r];
        const n = { P: 0, A: 0, L: 0, paid: 0 };
        for (let c = 0; c < days; c++) {
            const s = cell(r, c).dataset.s;
            if (!s) continue;
            if (s in n) n[s]++;
            n.paid += STATUSES[s].paid;
        }
        tr.querySelector('[data-sum=P]').textContent = n.P;
        tr.querySelector('[data-sum=A]').textContent = n.A;
        tr.querySelector('[data-sum=L]').textContent = n.L;
        tr.querySelector('[data-sum=paid]').textContent = n.paid;
    }
    function undoLast() {
        const batch = undo.pop();
        if (!batch) return;
        const touched = new Set();
        batch.forEach(([td, before]) => { write(td, before); touched.add(td.parentElement.sectionRowIndex); });
        touched.forEach(summarise);
        onChange(dirty.size);
    }

    // Keyboard ------------------------------------------------------------
    table.addEventListener('keydown', (e) => {
        if (!e.target.matches('td[data-day]')) return;
        const { r, c } = focus;
        const k = e.key;
        const ext = e.shiftKey;
        const moves = {
            ArrowUp: [r - 1, c], ArrowDown: [r + 1, c], ArrowLeft: [r, c - 1], ArrowRight: [r, c + 1],
            Home: [r, 0], End: [r, days - 1], PageUp: [r - 10, c], PageDown: [r + 10, c],
        };
        if (moves[k]) { e.preventDefault(); moveTo(...moves[k], ext); return; }
        if ((e.ctrlKey || e.metaKey) && k.toLowerCase() === 'z') { e.preventDefault(); undoLast(); return; }
        if ((e.ctrlKey || e.metaKey) && k.toLowerCase() === 'a') { e.preventDefault(); anchor = { r: 0, c: 0 }; moveTo(rows - 1, days - 1, true); return; }
        if (k === 'Escape') { anchor = { ...focus }; paintSelection(); return; }
        if (k === 'Backspace' || k === 'Delete') { e.preventDefault(); set(''); return; }
        const s = k.toUpperCase();
        if (STATUSES[s] && !e.ctrlKey && !e.metaKey && !e.altKey) {
            e.preventDefault();
            const single = selectionSize() === 1;
            set(s);
            // Typing in a single cell advances, so a row can be keyed in one go: P P P A P…
            if (single) moveTo(r, c + 1);
        }
    });

    // Mouse: click, shift-click, drag ---------------------------------------
    table.addEventListener('mousedown', (e) => {
        const td = e.target.closest('td[data-day]');
        if (!td) return;
        e.preventDefault();
        dragging = true;
        moveTo(td.parentElement.sectionRowIndex, Number(td.dataset.day) - 1, e.shiftKey);
    });
    table.addEventListener('mouseover', (e) => {
        if (!dragging) return;
        const td = e.target.closest('td[data-day]');
        if (td) moveTo(td.parentElement.sectionRowIndex, Number(td.dataset.day) - 1, true);
    });
    window.addEventListener('mouseup', () => { dragging = false; });

    // Whole column (day header) or whole row (employee name) --------------
    table.addEventListener('click', (e) => {
        const th = e.target.closest('[data-select-day]');
        if (th) {
            const c = Number(th.dataset.selectDay) - 1;
            anchor = { r: 0, c }; moveTo(rows - 1, c, true);
        }
        const name = e.target.closest('[data-select-row]');
        if (name) {
            const r = name.closest('tr').sectionRowIndex;
            anchor = { r, c: 0 }; moveTo(r, days - 1, true);
        }
    });

    cell(0, 0).tabIndex = 0;

    return {
        set,
        undo: undoLast,
        fillBlanks: (status = 'P') => set(status, { onlyBlank: true }),
        selectAll: () => { anchor = { r: 0, c: 0 }; moveTo(rows - 1, days - 1, true); },
        focus: () => moveTo(focus.r, focus.c, true),
        changes: () => dirty.size,
    };
}
