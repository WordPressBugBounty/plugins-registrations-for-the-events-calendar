(function($) {
	'use strict';

	var config  = typeof rtecMigrationWizard !== 'undefined' ? rtecMigrationWizard : {};
	var ajaxUrl = config.ajax_url || '';
	var nonce   = config.nonce || '';
	var strings = config.strings || {};
	var wizard  = window.RtecWizard || {};

	function startProcessing($btn) {
		if (wizard.startProcessing) {
			wizard.startProcessing($btn);
		}
	}

	function stopProcessing($btn) {
		if (wizard.stopProcessing) {
			wizard.stopProcessing($btn);
		}
	}

	function setMessage($el, text, isError) {
		if (wizard.setMessage) {
			wizard.setMessage($el, text, isError);
		}
	}

	$(document).on('click', '#rtec-migration-wizard-install-eg', function() {
		var $btn = $(this);
		var $msg = $('#rtec-migration-wizard-status');

		startProcessing($btn);
		var pendingMessage = config.eg_active
			? (strings.continuing || 'Continuing…')
			: (strings.thanks_patience || strings.installing || 'Installing…');
		setMessage($msg, pendingMessage, false);

		$.post(ajaxUrl, {
			action: 'rtec_migration_wizard_install_eg',
			nonce: nonce
		}).done(function(res) {
			if (res.success && res.data && res.data.redirect) {
				setMessage($msg, strings.success || 'Success! Continuing…', false);
				setTimeout(function() {
					window.location.href = res.data.redirect;
				}, 800);
			} else {
				var err = (res.data && res.data.message) ? res.data.message : (strings.error || 'Error');
				setMessage($msg, err, true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error || 'Something went wrong.', true);
			stopProcessing($btn);
		});
	});

})(jQuery);
