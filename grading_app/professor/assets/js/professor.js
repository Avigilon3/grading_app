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
    initNotificationDropdowns();
		initGradeItemActions();
		initGradingSheetExport();
		initStudentSearchFilters();
		// If server exposes counts, try to fetch them; otherwise pages can call updateCounts
		// tryFetchCounts();

		// Expose small API for inline scripts to update counts dynamically
		window.admin = window.admin || {};
		window.admin.updateCounts = updateCounts;
		window.admin.highlightNav = highlightNav;
	}

	function initGradeItemActions() {
		var body = document.body;
		if (!body) return;
		if ((body.getAttribute('data-sheet-editable') || '') !== '1') return;

		var addButtons = safeQueryAll('[data-add-grade-item]');
		var editButtons = safeQueryAll('[data-edit-grade-item]');
		var deleteButtons = safeQueryAll('[data-delete-grade-item]');
		if (!addButtons.length && !editButtons.length && !deleteButtons.length) return;

		var modal = safeQuery('[data-grade-item-modal]');
		var form = modal ? safeQuery('[data-grade-item-form]', modal) : null;
		var titleInput = form ? form.querySelector('#grade-item-name') : null;
		var pointsInput = form ? form.querySelector('#grade-item-points') : null;
		var componentLabel = modal ? safeQuery('[data-grade-item-modal-component]', modal) : null;
		var submitBtn = form ? safeQuery('[data-grade-item-submit]', form) : null;
		var defaultSubmitText = submitBtn ? submitBtn.textContent : '';
		var activeItemId = null;

		function closeModal() {
			if (!modal) return;
			modal.setAttribute('hidden', 'hidden');
			modal.classList.remove('open');
			if (form) {
				form.reset();
			}
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.textContent = defaultSubmitText || 'Save Changes';
			}
			if (componentLabel) {
				componentLabel.textContent = '';
			}
			activeItemId = null;
		}

		function openModal(button) {
			if (!modal || !form || !titleInput || !pointsInput) return;
			activeItemId = button.getAttribute('data-grade-item-id');
			titleInput.value = button.getAttribute('data-title') || '';
			pointsInput.value = button.getAttribute('data-total-points') || '';
			if (componentLabel) {
				var comp = button.getAttribute('data-component-name') || '';
				componentLabel.textContent = comp ? ('Component: ' + comp) : '';
			}
			modal.removeAttribute('hidden');
			modal.classList.add('open');
			setTimeout(function () {
				titleInput.focus();
				titleInput.select();
			}, 50);
		}

		addButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var componentId = btn.getAttribute('data-component-id');
				var componentName = btn.getAttribute('data-component-name') || 'this component';
				if (!componentId) return;

				var originalLabel = btn.textContent;
				btn.disabled = true;
				btn.textContent = '...';

				sendGradeItemRequest({
					action: 'create',
					component_id: componentId
				}).then(function () {
					window.location.reload();
				}).catch(function (err) {
					alert('Unable to add grade item for ' + componentName + ': ' + (err.message || 'Please try again.'));
					btn.disabled = false;
					btn.textContent = originalLabel;
				});
			});
		});

		editButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				openModal(btn);
			});
		});

		deleteButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var title = btn.getAttribute('data-title') || 'this item';
				var componentName = btn.getAttribute('data-component-name') || '';
				var message = 'Delete "' + title + '"';
				if (componentName) {
					message += ' under ' + componentName;
				}
				message += '? This will remove all recorded scores for this activity.';
				if (!confirm(message)) {
					return;
				}

				var gradeItemId = btn.getAttribute('data-grade-item-id');
				if (!gradeItemId) return;

				sendGradeItemRequest({
					action: 'delete',
					grade_item_id: gradeItemId
				}).then(function () {
					window.location.reload();
				}).catch(function (err) {
					alert(err.message || 'Unable to delete grade item at this time.');
				});
			});
		});

		if (form && titleInput && pointsInput) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				if (!activeItemId) return;

				var title = titleInput.value.trim();
				var totalPoints = pointsInput.value.trim();
				if (!title) {
					alert('Please provide a name for the grade item.');
					return;
				}
				if (!totalPoints) {
					alert('Please enter the total points for this grade item.');
					return;
				}

				if (submitBtn) {
					submitBtn.disabled = true;
					submitBtn.textContent = 'Saving...';
				}

				sendGradeItemRequest({
					action: 'update',
					grade_item_id: activeItemId,
					title: title,
					total_points: totalPoints
				}).then(function () {
					window.location.reload();
				}).catch(function (err) {
					alert(err.message || 'Unable to update grade item.');
					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.textContent = defaultSubmitText || 'Save Changes';
					}
				});
			});
		}

		if (modal) {
			modal.addEventListener('click', function (event) {
				if (event.target === modal) {
					closeModal();
				}
			});
			var closeButtons = safeQueryAll('[data-grade-item-close]', modal).concat(
				safeQueryAll('[data-grade-item-cancel]', modal)
			);
			closeButtons.forEach(function (btn) {
				btn.addEventListener('click', function () {
					closeModal();
				});
			});
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
					closeModal();
				}
			});
		}
	}

	function sendGradeItemRequest(payload) {
		var formData = new FormData();
		Object.keys(payload || {}).forEach(function (key) {
			if (payload[key] !== undefined && payload[key] !== null) {
				formData.append(key, payload[key]);
			}
		});

		return fetch('../includes/grade_item_actions.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: formData
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Request failed. Please try again.');
				}
				return response.json();
			})
			.then(function (data) {
				if (!data || data.success !== true) {
					throw new Error((data && data.message) || 'Action could not be completed.');
				}
				return data;
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

	function initGradingSheetExport() {
		var buttons = safeQueryAll('[data-export-grading-sheet]');
		if (!buttons.length) return;

		var table = safeQuery('.grading-sheet-table');
		if (!table) return;

		function handleExport() {
			var csv = buildGradingSheetCsv(table);
			if (!csv) {
				alert('There is no grading data to export yet.');
				return;
			}
			var fileName = buildExportFilename();
			downloadCsv(csv, fileName);
		}

		buttons.forEach(function (btn) {
			btn.addEventListener('click', handleExport);
		});
	}

	function buildGradingSheetCsv(table) {
		var rows = Array.from(table.querySelectorAll('tr'));
		if (!rows.length) return '';

		var csvRows = rows.map(function (row) {
			var cells = Array.from(row.querySelectorAll('th,td'));
			var values = cells.map(function (cell) {
				var input = cell.querySelector('input');
				var text = '';
				if (input) {
					text = input.value || '';
				} else {
					text = extractCellText(cell);
				}
				return wrapCsvValue(text);
			});
			return values.join(',');
		});

		return csvRows.join('\r\n');
	}

	function extractCellText(cell) {
		var clone = cell.cloneNode(true);
		Array.from(clone.querySelectorAll('button, [data-export-ignore]')).forEach(function (el) {
			if (el && el.parentNode) {
				el.parentNode.removeChild(el);
			}
		});
		var text = (clone.textContent || '')
			.replace(/\s+/g, ' ')
			.replace(/\u2014/g, '-')
			.trim();
		return text;
	}

	function wrapCsvValue(value) {
		var normalized = (value == null ? '' : String(value))
			.replace(/\r?\n|\r/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();
		var escaped = normalized.replace(/"/g, '""');
		return '"' + escaped + '"';
	}

	function buildExportFilename() {
		var section = '';
		var body = document.body;
		if (body) {
			section = body.getAttribute('data-section-name') || '';
		}
		if (!section) {
			var heading = safeQuery('.content-header h1');
			if (heading) {
				section = heading.textContent || '';
			}
		}
		section = section.replace(/\s*[–—-]\s*Grading Sheet/i, '').trim();
		var safeSection = section
			.toLowerCase()
			.replace(/[^a-z0-9]+/gi, '_')
			.replace(/^_+|_+$/g, '');
		if (!safeSection) {
			safeSection = 'grading_sheet';
		}
		var now = new Date();
		var stamp = [
			now.getFullYear(),
			String(now.getMonth() + 1).padStart(2, '0'),
			String(now.getDate()).padStart(2, '0'),
			String(now.getHours()).padStart(2, '0'),
			String(now.getMinutes()).padStart(2, '0')
		].join('');
		return safeSection + '_' + stamp + '.csv';
	}

	function downloadCsv(content, filename) {
		var blob = new Blob(['\uFEFF' + content], { type: 'text/csv;charset=utf-8;' });
		var link = document.createElement('a');
		var url = URL.createObjectURL(blob);
		link.href = url;
		link.download = filename;
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		setTimeout(function () {
			URL.revokeObjectURL(url);
		}, 0);
	}

	function initStudentSearchFilters() {
		var inputs = safeQueryAll('[data-student-search-input]');
		if (!inputs.length) return;

		inputs.forEach(function (input) {
			var key = input.getAttribute('data-student-search-input');
			if (!key) return;
			var table = safeQuery('[data-students-table="' + key + '"]');
			if (!table) return;

			var rows = Array.from(table.querySelectorAll('tbody tr')).filter(function (row) {
				return !row.classList.contains('empty-row') && !row.classList.contains('no-results-row');
			});
			var noResultsRow = table.querySelector('.no-results-row');

			function applyFilter() {
				if (!rows.length) {
					if (noResultsRow) {
						noResultsRow.hidden = true;
					}
					return;
				}

				var query = (input.value || '').trim().toLowerCase();
				if (!query) {
					rows.forEach(function (row) {
						row.hidden = false;
					});
					if (noResultsRow) {
						noResultsRow.hidden = true;
					}
					return;
				}

				var visible = 0;
				rows.forEach(function (row) {
					var haystack = (row.textContent || '').toLowerCase();
					var isMatch = haystack.indexOf(query) !== -1;
					row.hidden = !isMatch;
					if (isMatch) {
						visible += 1;
					}
				});

				if (noResultsRow) {
					noResultsRow.hidden = visible !== 0;
				}
			}

			input.addEventListener('input', applyFilter);
			applyFilter();
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
    

})();
