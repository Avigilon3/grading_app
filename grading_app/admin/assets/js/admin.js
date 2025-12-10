// Admin UI helpers: nav highlighting, badge updates, logout confirmation
(function () {
	'use strict';

	function safeQuery(selector, root = document) {
		try { return root.querySelector(selector); } catch (e) { return null; }
	}

	function safeQueryAll(selector, root = document) {
		try { return Array.from(root.querySelectorAll(selector)); } catch (e) { return []; }
	}

	function highlightNav() {
		var navLinks = safeQueryAll('.nav-item');
		if (!navLinks.length) return;

		var current = window.location.pathname + window.location.search;

		navLinks.forEach(function (a) {
			try {
				var href = a.getAttribute('href') || '';
				// Resolve relative URLs against the current location
				var url = new URL(href, window.location.href);
				// simple match: last segment or full pathname
				if (current.endsWith(url.pathname) || current === url.pathname + url.search) {
					a.classList.add('active');
					a.style.background = '#618A61';
					a.style.color = '#fff';
				} else {
					a.classList.remove('active');
					a.style.background = '';
				}
			} catch (err) {
				// ignore invalid hrefs
			}
		});
	}

	function updateBadge(id, value) {
		var el = safeQuery('#' + id);
		if (!el) return;
		el.textContent = String(value == null ? el.textContent : value);
	}

	function updateCounts(counts) {
		if (!counts || typeof counts !== 'object') return;
		if ('edit_requests' in counts) updateBadge('edit-req-count', counts.edit_requests);
		if ('submissions' in counts) updateBadge('submissions-count', counts.submissions);
	}

	function tryFetchCounts() {
		// Attempt to fetch counts from a sensible relative endpoint if present.
		// This is wrapped in try/catch so it fails silently when no API exists.
		var endpoint = './api/counts.php';
		try {
			fetch(endpoint, { credentials: 'same-origin' })
				.then(function (r) { if (!r.ok) throw new Error('no'); return r.json(); })
				.then(function (data) { updateCounts(data); })
				.catch(function () { /* ignore */ });
		} catch (e) { /* ignore */ }
	}

	function wireLogoutConfirm() {
		var logout = Array.from(document.querySelectorAll('a[href$="logout.php"]'))[0];
		if (!logout) return;
		logout.addEventListener('click', function (e) {
			if (!confirm('Are you sure you want to log out?')) {
				e.preventDefault();
			}
		});
	}

  function initSidebarDropdowns() {
    var dropdowns = safeQueryAll('[data-dropdown]');
    if (!dropdowns.length) return;

    var STORAGE_KEY = 'adminSidebarDropdownState';

    function loadDropdownState() {
      try {
        var raw = window.localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : {};
      } catch (err) {
        return {};
      }
    }

    function saveDropdownState(state) {
      try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
      } catch (err) {
        // ignore storage failures
      }
    }

    var dropdownState = loadDropdownState();

    dropdowns.forEach(function (dropdown, index) {
      var toggle = safeQuery('.dropdown-toggle', dropdown);
      var submenu = safeQuery('.submenu', dropdown);
      if (!toggle || !submenu) return;

      var stateKey = dropdown.getAttribute('data-dropdown-key') || String(index);

      function applyState(isOpen) {
        dropdown.classList.toggle('open', isOpen);
        submenu.style.display = isOpen ? 'block' : 'none';
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      }

      var hasActiveChild = !!safeQuery('.nav-item.active', submenu);
      var shouldOpen = dropdownState[stateKey] === 'open' || hasActiveChild;
      applyState(shouldOpen);

      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        var isOpen = !dropdown.classList.contains('open');
        applyState(isOpen);
        dropdownState[stateKey] = isOpen ? 'open' : 'closed';
        saveDropdownState(dropdownState);
      });
    });
  }

	function initNotificationDropdowns() {
		var dropdown = safeQuery('[data-notifications-dropdown]');
		if (!dropdown) return;

		var trigger = safeQuery('[data-notif-trigger]', dropdown);
		if (!trigger) return;

		function closeDropdown(event) {
			if (event && dropdown.contains(event.target)) return;
			dropdown.classList.remove('open');
			trigger.setAttribute('aria-expanded', 'false');
			document.removeEventListener('click', closeDropdown);
		}

		trigger.addEventListener('click', function (event) {
			event.stopPropagation();
			var isOpen = dropdown.classList.toggle('open');
			trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

			document.removeEventListener('click', closeDropdown);
			if (isOpen) {
				setTimeout(function () {
					document.addEventListener('click', closeDropdown);
				}, 0);
			}
		});
	}


	function init() {
		highlightNav();
		wireLogoutConfirm();
		initUserDropdowns();
    initSidebarDropdowns();
		initNotificationDropdowns();
		// If server exposes counts, try to fetch them; otherwise pages can call updateCounts
		// tryFetchCounts();

		// Expose small API for inline scripts to update counts dynamically
		window.admin = window.admin || {};
		window.admin.updateCounts = updateCounts;
		window.admin.highlightNav = highlightNav;
	}

	function initUserDropdowns() {
		var dropdowns = safeQueryAll('[data-user-dropdown]');
		if (!dropdowns.length) return;

		dropdowns.forEach(function (dropdown) {
			var trigger = safeQuery('[data-user-trigger]', dropdown);
			if (!trigger) return;

			function closeDropdown(event) {
				if (event && dropdown.contains(event.target)) return;
				dropdown.classList.remove('open');
				trigger.setAttribute('aria-expanded', 'false');
				document.removeEventListener('click', closeDropdown);
			}

			trigger.addEventListener('click', function (event) {
				event.stopPropagation();
				var isOpen = dropdown.classList.toggle('open');
				trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

				document.removeEventListener('click', closeDropdown);
				if (isOpen) {
					setTimeout(function () {
						document.addEventListener('click', closeDropdown);
					}, 0);
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
    

})();
