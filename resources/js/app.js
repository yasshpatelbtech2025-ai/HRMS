import Alpine from 'alpinejs';
import { employeeForm, photoCapture } from './employee-form';

window.Alpine = Alpine;

Alpine.data('employeeForm', employeeForm);
Alpine.data('photoCapture', photoCapture);

Alpine.start();
