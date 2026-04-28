/**
 * Content modal: open on trigger click, load content via AJAX, close on backdrop/button.
 * Width: Add New (registration_id=0) uses small; manage-registration with id uses maximum; or data-rtec-modal-settings.
 */
(function($) {
	'use strict';

	var $backdrop = $('#rtec-content-modal-backdrop');
	var $modal = $('#rtec-content-modal');
	var $dialog = $modal.find('.rtec-content-modal-dialog');
	var $content = $('#rtec-content-modal-content');

	var WIDTH_CLASSES = [
		'rtec-narrow-max-width-modal',
		'rtec-small-max-width-modal',
		'rtec-medium-max-width-modal',
		'rtec-maximum-max-width-modal'
	];

	function getModalSettings($trigger) {
		var raw = $trigger && $trigger.length ? $trigger.attr('data-rtec-modal-settings') : '';
		var settings = {};
		if (raw) {
			try {
				settings = JSON.parse(raw);
			} catch (err) {}
		}
		return settings;
	}

	function applyModalWidth(contentType, settings, triggerData) {
		var width = (settings && settings.width) ? settings.width : '';
		if (!width && contentType === 'manage-registration' && triggerData) {
			var regId = triggerData.registration_id;
			if (regId === '0' || regId === 0) {
				width = 'small';
			} else {
				width = 'maximum';
			}
		}
		if (!width && contentType === 'manage-registration') {
			width = 'maximum';
		}
		if (!width) {
			width = 'medium';
		}
		var classMap = {
			narrow: 'rtec-narrow-max-width-modal',
			small: 'rtec-small-max-width-modal',
			medium: 'rtec-medium-max-width-modal',
			max: 'rtec-maximum-max-width-modal',
			maximum: 'rtec-maximum-max-width-modal'
		};
		var addClass = classMap[width] || classMap.medium;
		if ($dialog.length) {
			WIDTH_CLASSES.forEach(function(cls) {
				$dialog.removeClass(cls);
			});
			$dialog.addClass(addClass);
		}
	}

	function getTriggerData($trigger) {
		var data = {};
		if ($trigger.attr('data-rtec-registration-id') !== undefined) {
			data.registration_id = $trigger.attr('data-rtec-registration-id');
		}
		if ($trigger.attr('data-rtec-event-id') !== undefined) {
			data.event_id = $trigger.attr('data-rtec-event-id');
		}
		return data;
	}

	function setLoading() {
		$content.html('<div class="rtec-content-modal-placeholder"><span class="spinner is-active" style="float:none;display:block;margin:20px auto;"></span></div>');
	}

	function setError(message) {
		var msg = message || (typeof rtecAdminScript !== 'undefined' && rtecAdminScript.strings && rtecAdminScript.strings.error ? rtecAdminScript.strings.error : 'Something went wrong.');
		$content.html('<div class="rtec-content-modal-body"><p class="rtec-content-modal-error">' + (msg.replace ? msg.replace(/</g, '&lt;') : msg) + '</p></div>');
	}

	function setContent(html) {
		$content.html(html);
		$modal.trigger('rtec_modal_content_loaded', [$content]);
	}

	function openModal(contentType, settings, triggerData) {
		applyModalWidth(contentType, settings || {}, triggerData);
		$backdrop.addClass('rtec-content-modal-visible').attr('aria-hidden', 'false');
		$modal.addClass('rtec-content-modal-visible').attr('aria-hidden', 'false');
		$('body').addClass('rtec-content-modal-is-open');
	}

	function closeModal() {
		$backdrop.removeClass('rtec-content-modal-visible').attr('aria-hidden', 'true');
		$modal.removeClass('rtec-content-modal-visible').attr('aria-hidden', 'true');
		$('body').removeClass('rtec-content-modal-is-open');
		$modal.trigger('rtec_content_modal_closed');
	}

	function loadContent(contentType, triggerData) {
		var payload = {
			action: 'rtec_modal_content',
			rtec_nonce: typeof rtecAdminScript !== 'undefined' ? rtecAdminScript.rtec_nonce : '',
			content_type: contentType
		};
		$.extend(payload, triggerData);

		$.post(typeof rtecAdminScript !== 'undefined' ? rtecAdminScript.ajax_url : '', payload)
			.done(function(response) {
				if (response && response.success && response.data && response.data.html !== undefined) {
					setContent(response.data.html);
				} else {
					setError(response && response.data && response.data.message ? response.data.message : null);
				}
			})
			.fail(function() {
				setError();
			});
	}

	$(document).on('click', '[data-rtec-modal-content]', function(e) {
		var $trigger = $(e.currentTarget);
		var contentType = $trigger.attr('data-rtec-modal-content');
		if (!contentType) return;
		e.preventDefault();

		var triggerData = getTriggerData($trigger);
		var settings = getModalSettings($trigger);
		setLoading();
		openModal(contentType, settings, triggerData);
		loadContent(contentType, triggerData);
	});

	$modal.on('click', '.rtec-content-modal-close', function(e) {
		e.preventDefault();
		closeModal();
	});

	$backdrop.on('click', function(e) {
		if (e.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $modal.hasClass('rtec-content-modal-visible')) {
			closeModal();
		}
	});
})(jQuery);
