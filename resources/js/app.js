import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/600.css';
import '@fontsource/ibm-plex-sans/700.css';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import { employeeForm, photoCapture } from './employee-form';
import { shiftRoster } from './shift-roster';
import { toasts } from './toasts';
import { globalSearch } from './global-search';
import './inline-validation';
import { confirmDialog } from './confirm-dialog';
import { bulkSelect } from './bulk-select';
import './loading';
import menu, { placeBelow } from './ui/menu';
import tableTools from './ui/table-tools';

window.Alpine = Alpine;

Alpine.plugin([persist, focus, collapse]);

Alpine.data('employeeForm', employeeForm);
Alpine.data('photoCapture', photoCapture);
Alpine.data('shiftRoster', shiftRoster);
Alpine.data('toasts', toasts);
Alpine.data('globalSearch', globalSearch);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('bulkSelect', bulkSelect);
Alpine.data('menu', menu);
Alpine.data('tableTools', tableTools);
window.placeBelow = placeBelow;

Alpine.start();
