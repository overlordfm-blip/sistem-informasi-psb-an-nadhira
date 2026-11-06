document.addEventListener('DOMContentLoaded', function () {
	const sidebar = document.getElementById('admin-sidebar');
	const toggle = document.querySelector('[data-admin-menu-toggle]');
	const closeButton = document.querySelector('[data-admin-menu-close]');
	const backdrop = document.querySelector('[data-admin-menu-backdrop]');
	const mobile = window.matchMedia('(max-width: 1023px)');

	if (!sidebar || !toggle || !closeButton || !backdrop) {
		return;
	}

	function setMenuOpen(open) {
		const shouldOpen = mobile.matches && open;
		sidebar.classList.toggle('-translate-x-full', mobile.matches && !shouldOpen);
		sidebar.inert = mobile.matches && !shouldOpen;
		backdrop.classList.toggle('hidden', !shouldOpen);
		toggle.setAttribute('aria-expanded', String(shouldOpen));
		document.body.classList.toggle('overflow-hidden', shouldOpen);
	}

	toggle.addEventListener('click', function () {
		setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true');
		if (toggle.getAttribute('aria-expanded') === 'true') {
			closeButton.focus();
		}
	});
	closeButton.addEventListener('click', function () {
		setMenuOpen(false);
		toggle.focus();
	});
	backdrop.addEventListener('click', function () {
		setMenuOpen(false);
		toggle.focus();
	});
	sidebar.querySelectorAll('nav a').forEach(function (link) {
		link.addEventListener('click', function () {
			if (mobile.matches) {
				setMenuOpen(false);
				toggle.focus();
			}
		});
	});
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			setMenuOpen(false);
			toggle.focus();
		}
	});
	mobile.addEventListener('change', function () {
		setMenuOpen(false);
	});

	setMenuOpen(false);
});
