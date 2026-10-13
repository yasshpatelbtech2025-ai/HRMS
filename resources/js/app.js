import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/600.css';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import mask from '@alpinejs/mask';

import menu, { placeBelow } from './ui/menu';
import combobox from './ui/combobox';
import toasts from './ui/toasts';
import confirmDialog from './ui/confirm';
import dataTable from './ui/data-table';
import palette from './ui/palette';
import steppedForm from './ui/stepped-form';
import { attendanceGrid, STATUSES } from './ui/attendance-grid';
import { installFormHelpers } from './ui/forms';
import { installInlineValidation } from './ui/validate';

Alpine.plugin([persist, focus, collapse, mask]);

Alpine.store('toasts', toasts);
Alpine.data('menu', menu);
Alpine.data('combobox', combobox);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('dataTable', dataTable);
Alpine.data('palette', palette);
Alpine.data('steppedForm', steppedForm);
Alpine.data('attendancePrototype', () => ({
    grid: null,
    statuses: STATUSES,
    changes: 0,
    where: '',
    init() {
        this.grid = attendanceGrid(this.$root, { onChange: (n) => { this.changes = n; } });
        this.$root.addEventListener('grid:focus', (e) => {
            const { row, day, count } = e.detail;
            this.where = count > 1 ? `${count} cells selected` : `${row} · day ${day}`;
        });
    },
}));

installFormHelpers();
installInlineValidation();

window.Alpine = Alpine;
window.placeBelow = placeBelow;
Alpine.start();
