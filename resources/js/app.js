import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

import '../css/app.css';

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[data-datepicker]').forEach((input) => {
		flatpickr(input, {
			altInput: true,
			altFormat: 'd M Y',
			dateFormat: 'Y-m-d',
			minDate: 'today',
			disableMobile: true,
		});
	});
});
