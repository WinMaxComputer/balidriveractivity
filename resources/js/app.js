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

	const destinationInput = document.getElementById('destination');
	const destinationSuggestions = document.getElementById('popular-destinations');

	if (destinationInput && destinationSuggestions) {
		const suggestionButtons = Array.from(destinationSuggestions.querySelectorAll('button'));
		const suggestionGroups = Array.from(destinationSuggestions.querySelectorAll('[data-suggestion-group]'));

		const closeSuggestions = () => {
			destinationSuggestions.hidden = true;
			destinationInput.setAttribute('aria-expanded', 'false');
		};

		const updateSuggestions = () => {
			const query = destinationInput.value.trim().toLowerCase();
			let visibleSuggestions = 0;

			suggestionGroups.forEach((group) => {
				const buttons = group.querySelectorAll('button');
				let visibleGroupSuggestions = 0;

				buttons.forEach((button) => {
					const matches = button.textContent.trim().toLowerCase().includes(query);
					button.hidden = !matches;
					visibleGroupSuggestions += matches ? 1 : 0;
				});

				group.hidden = visibleGroupSuggestions === 0;
				visibleSuggestions += visibleGroupSuggestions;
			});

			destinationSuggestions.hidden = visibleSuggestions === 0;
			destinationInput.setAttribute('aria-expanded', String(visibleSuggestions > 0));
		};

		destinationInput.addEventListener('focus', updateSuggestions);
		destinationInput.addEventListener('input', updateSuggestions);

		suggestionButtons.forEach((button) => {
			button.addEventListener('click', () => {
				destinationInput.value = button.textContent.trim();
				closeSuggestions();
			});
		});

		destinationInput.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				closeSuggestions();
			}
		});

		document.addEventListener('click', (event) => {
			if (!destinationInput.parentElement.contains(event.target)) {
				closeSuggestions();
			}
		});
	}
});
