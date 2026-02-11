(function () {
	'use strict';

	const root = document.querySelector('[data-arkn-cards]');
	if (!root) {
		return;
	}

	const closeAllHovers = () => {
		root.querySelectorAll('[data-arkn-hover].is-open').forEach((openItem) => {
			openItem.classList.remove('is-open');
		});
	};

	root.querySelectorAll('[data-arkn-hover-trigger]').forEach((trigger) => {
		trigger.addEventListener('click', (event) => {
			event.preventDefault();
			const item = trigger.closest('[data-arkn-hover]');
			if (!item) {
				return;
			}

			const isOpen = item.classList.contains('is-open');
			closeAllHovers();
			if (!isOpen) {
				item.classList.add('is-open');
			}
		});
	});

	document.addEventListener('click', (event) => {
		if (!root.contains(event.target)) {
			closeAllHovers();
		}
	});

	root.querySelectorAll('[data-arkn-card]').forEach((card) => {
		const tabTriggers = card.querySelectorAll('[data-arkn-tab-trigger]');
		const panels = card.querySelectorAll('[data-arkn-tab-panel]');

		tabTriggers.forEach((trigger) => {
			trigger.addEventListener('click', () => {
				const target = trigger.getAttribute('data-target');
				if (!target) {
					return;
				}

				tabTriggers.forEach((item) => {
					item.classList.remove('is-active');
					item.setAttribute('aria-selected', 'false');
				});
				trigger.classList.add('is-active');
				trigger.setAttribute('aria-selected', 'true');

				panels.forEach((panel) => {
					const isTarget = panel.getAttribute('data-arkn-tab-panel') === target;
					panel.classList.toggle('is-active', isTarget);
					panel.hidden = !isTarget;
				});
			});
		});

		card.querySelectorAll('[data-arkn-gallery]').forEach((gallery) => {
			const track = gallery.querySelector('[data-arkn-gallery-track]');
			const slides = gallery.querySelectorAll('.arkn-gallery__slide');
			const prevBtn = gallery.querySelector('[data-arkn-gallery-prev]');
			const nextBtn = gallery.querySelector('[data-arkn-gallery-next]');

			if (!track || slides.length <= 1 || !prevBtn || !nextBtn) {
				return;
			}

			let index = 0;
			const total = slides.length;

			const render = () => {
				track.style.transform = `translateX(${index * -100}%)`;
			};

			prevBtn.addEventListener('click', () => {
				index = (index - 1 + total) % total;
				render();
			});

			nextBtn.addEventListener('click', () => {
				index = (index + 1) % total;
				render();
			});
		});
	});
})();
