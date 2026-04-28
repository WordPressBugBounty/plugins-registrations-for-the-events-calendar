/**
 * Settings checklist expand/collapse toggle.
 * Only runs when the checklist is present (e.g. not dismissed).
 */
(function() {
	'use strict';

	function init() {
		var el = document.getElementById('rtec-settings-checklist');
		var btn = document.getElementById('rtec-settings-checklist-toggle');
		var body = document.getElementById('rtec-settings-checklist-body');
		if (!el || !btn || !body) {
			return;
		}
		btn.addEventListener('click', function() {
			var collapsed = el.classList.toggle('rtec-settings-checklist-collapsed');
			btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
			body.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
