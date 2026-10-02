import Alpine from 'alpinejs';
import { employeeForm, photoCapture } from './employee-form';
import { shiftRoster } from './shift-roster';

window.Alpine = Alpine;

Alpine.data('employeeForm', employeeForm);
Alpine.data('photoCapture', photoCapture);
Alpine.data('shiftRoster', shiftRoster);

Alpine.start();
