import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import { employeeForm, photoCapture } from './employee-form';
import { shiftRoster } from './shift-roster';
import { invoiceForm } from './invoice-form';
import { toasts } from './toasts';
import { globalSearch } from './global-search';
import './inline-validation';
import formSteps from './ui/form-steps';
import { confirmDialog } from './confirm-dialog';
import { bulkSelect } from './bulk-select';
import './loading';
import './ui/masks';
import './ui/unsaved-changes';
import menu, { placeBelow } from './ui/menu';
import tableTools from './ui/table-tools';

window.Alpine = Alpine;

Alpine.plugin([persist, focus, collapse]);

Alpine.data('employeeForm', employeeForm);
Alpine.data('photoCapture', photoCapture);
Alpine.data('shiftRoster', shiftRoster);
Alpine.data('invoiceForm', invoiceForm);
Alpine.data('toasts', toasts);
Alpine.data('globalSearch', globalSearch);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('bulkSelect', bulkSelect);
Alpine.data('menu', menu);
Alpine.data('tableTools', tableTools);
Alpine.data('formSteps', formSteps);
window.placeBelow = placeBelow;

Alpine.start();
