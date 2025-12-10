// Admin UI helpers: nav highlighting, badge updates, logout confirmation
(function () {
	'use strict';

	function safeQuery(selector, root = document) {
		try { return root.querySelector(selector); } catch (e) { return null; }
	}

	function safeQueryAll(selector, root = document) {
		try { return Array.from(root.querySelectorAll(selector)); } catch (e) { return []; }
	}

	function escapeHtml(value) {
		if (value === null || value === undefined) return '';
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
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

  function displayTodayDate() {
    const today = new Date(); 
    const formattedDate = today.toLocaleDateString('en-US',{
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    const topDateElement =document.getElementById('reportDateTop');
    const bottomDateElement =document.getElementById('generationDate');

    if (topDateElement) {
      topDateElement.textContent = formattedDate;
    }
    if (bottomDateElement) {
      bottomDateElement.textContent = formattedDate;
    }
  }
	function init() {
		highlightNav();
		wireLogoutConfirm();
		initUserDropdowns();
		initNotificationDropdowns();
		initGradeSheetViewer();
    displayTodayDate();
		// If server exposes counts, try to fetch them; otherwise pages can call updateCounts
		// tryFetchCounts();

		// Expose small API for inline scripts to update counts dynamically
		window.admin = window.admin || {};
		window.admin.updateCounts = updateCounts;
		window.admin.highlightNav = highlightNav;
	}

	function initGradeSheetViewer() {
		var modal = safeQuery('[data-grade-modal]');
		if (!modal) return;

		var body = safeQuery('[data-grade-modal-body]', modal);
		var titleEl = safeQuery('[data-grade-modal-title]', modal);
		var professorEl = safeQuery('[data-grade-modal-professor]', modal);
		var closeEls = safeQueryAll('[data-grade-modal-close]', modal);
		var activeFetch = null;

		function setBody(html) {
			if (body) {
				body.innerHTML = html;
			}
		}

		function openModal() {
			modal.hidden = false;
			document.body.style.overflow = 'hidden';
		}

		function closeModal() {
			modal.hidden = true;
			document.body.style.overflow = '';
			if (activeFetch && typeof activeFetch.abort === 'function') {
				activeFetch.abort();
			}
			activeFetch = null;
		}

		closeEls.forEach(function (btn) {
			btn.addEventListener('click', function () {
				closeModal();
			});
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && modal && !modal.hidden) {
				closeModal();
			}
		});

		function renderComponentTable(component) {
			var items = component.items || [];
			var rows = '';
			if (!items.length) {
				rows = '<tr><td colspan="3">No grade items yet.</td></tr>';
			} else {
				rows = items.map(function (item) {
					var score = item.score === null || item.score === undefined ? '—' : escapeHtml(formatNumber(item.score));
					var totalPoints = escapeHtml(formatNumber(item.total_points));
					return '<tr>' +
						'<td>' + escapeHtml(item.title || 'Grade Item') + '</td>' +
						'<td>' + score + '</td>' +
						'<td>' + totalPoints + '</td>' +
					'</tr>';
				}).join('');
			}

			var weightLabel = formatNumber(component.weight) + '%';
			var earned = component.earned_points != null ? formatNumber(component.earned_points) : '0';
			var total = component.total_points != null ? formatNumber(component.total_points) : '0';
			var finalPercent = component.final_percent_display != null
				? formatNumber(component.final_percent_display) + '%'
				: '—';

			return '' +
			'<div class="grade-component-card">' +
				'<div class="grade-component-card__header">' +
					'<span>' + escapeHtml(component.name || 'Component') + ' (' + weightLabel + ')</span>' +
					'<span>' + finalPercent + '</span>' +
				'</div>' +
				'<div class="grade-component-card__body">' +
					'<table class="grade-breakdown-table">' +
						'<thead>' +
							'<tr><th>Grade Item</th><th>Your Score</th><th>Total Points</th></tr>' +
						'</thead>' +
						'<tbody>' + rows + '</tbody>' +
					'</table>' +
					'<div class="grade-component-summary">' +
						'<span>Earned: ' + earned + ' / ' + total + '</span>' +
						'<span>Weighted Contribution: ' + finalPercent + '</span>' +
					'</div>' +
				'</div>' +
			'</div>';
		}

		function formatNumber(value) {
			if (value === null || value === undefined || value === '') {
				return '0';
			}
			var num = Number(value);
			if (Number.isNaN(num)) {
				return '0';
			}
			return num % 1 === 0 ? num.toString() : num.toFixed(2);
		}

		function renderBreakdown(data) {
			if (!data || !data.components || !data.components.length) {
				return '<p class="text-muted">No grading sheet for this subject has been submitted yet.</p>';
			}

			var componentsHtml = data.components.map(renderComponentTable).join('');

			var finalValue = data.final_grade_display != null
				? formatNumber(data.final_grade_display) + '%'
				: (data.final_grade != null ? formatNumber(data.final_grade) + '%' : 'N/A');

			var equivalent = data.equivalent ? escapeHtml(data.equivalent) : 'N/A';

			var info = '';
			if (Array.isArray(data.weights_breakdown) && data.weights_breakdown.length) {
				info = '<p>Current weights: ' + data.weights_breakdown.map(escapeHtml).join(', ') + '</p>';
			}

			return componentsHtml +
				'<div class="grade-modal-info">' +
					info +
					'<p><strong>Final Grade:</strong> ' + finalValue + '</p>' +
					'<p><strong>Equivalent:</strong> ' + equivalent + '</p>' +
				'</div>';
		}

		document.addEventListener('click', function (event) {
			var trigger = event.target.closest('[data-grade-view]');
			if (!trigger) return;
			event.preventDefault();

			var sectionId = parseInt(trigger.getAttribute('data-section-id'), 10) || 0;
			var sectionSubjectId = parseInt(trigger.getAttribute('data-section-subject-id'), 10) || 0;
			if (!sectionId) {
				return;
			}

			if (titleEl) {
				titleEl.textContent = 'Grading Sheet - ' + (trigger.getAttribute('data-subject') || '');
			}
			if (professorEl) {
				var professor = trigger.getAttribute('data-professor') || 'Professor (TBA)';
				professorEl.textContent = 'Professor: ' + professor;
			}

			openModal();
			setBody('<p class="loading">Loading grading sheet...</p>');

			var url = './subject_grading_sheet.php?section_id=' + encodeURIComponent(sectionId);
			if (sectionSubjectId) {
				url += '&section_subject_id=' + encodeURIComponent(sectionSubjectId);
			}

			if (activeFetch && typeof activeFetch.abort === 'function') {
				activeFetch.abort();
			}

			var controller = window.AbortController ? new AbortController() : null;
			activeFetch = controller || null;

			fetch(url, {
				credentials: 'same-origin',
				signal: controller ? controller.signal : undefined
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Unable to fetch grading sheet.');
					}
					return response.json();
				})
				.then(function (payload) {
					activeFetch = null;
					if (!payload.ok) {
						setBody('<p class="text-muted">' + escapeHtml(payload.message || 'No grading sheet for this subject has been submitted yet.') + '</p>');
						return;
					}
					setBody(renderBreakdown(payload.data));
				})
				.catch(function () {
					activeFetch = null;
					setBody('<p class="text-muted">No grading sheet for this subject has been submitted yet.</p>');
				});
		});
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
