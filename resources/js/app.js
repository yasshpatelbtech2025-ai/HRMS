import Alpine from 'alpinejs';
import { employeeForm, photoCapture } from './employee-form';
import { shiftRoster } from './shift-roster';
import { toasts } from './toasts';
import { globalSearch } from './global-search';
import './inline-validation';
import { confirmDialog } from './confirm-dialog';
import { bulkSelect } from './bulk-select';
import './loading';

window.Alpine = Alpine;

Alpine.data('employeeForm', employeeForm);
Alpine.data('photoCapture', photoCapture);
Alpine.data('shiftRoster', shiftRoster);
Alpine.data('toasts', toasts);
Alpine.data('globalSearch', globalSearch);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('bulkSelect', bulkSelect);

Alpine.start();
