(function($) {
	'use strict';

	var pageUrl = typeof rtecOnboarding !== 'undefined' ? rtecOnboarding.page_url : '';
	var ajaxUrl = typeof rtecOnboarding !== 'undefined' ? rtecOnboarding.ajax_url : '';
	var nonce   = typeof rtecOnboarding !== 'undefined' ? rtecOnboarding.nonce : '';
	var strings = typeof rtecOnboarding !== 'undefined' ? rtecOnboarding.strings : {};

	var SPINNER_HTML = '<div class="rtec-spinner-container"><div class="rtec-spinner-circle"></div></div>';

	function startProcessing($btn) {
		if (!$btn || !$btn.length) return;
		$btn.attr('aria-busy', 'true');
		$btn.wrap('<div class="rtec-processing-wrap-flex rtec-is-processing"></div>');
		$btn.addClass('rtec-fade').prop('disabled', true);
		$btn.before(SPINNER_HTML);
	}

	function stopProcessing($btn) {
		if (!$btn || !$btn.length) return;
		setTimeout(function() {
			var $wrap = $btn.closest('.rtec-processing-wrap-flex');
			if ($wrap.length) {
				$wrap.find('.rtec-spinner-container').remove();
				$btn.unwrap('.rtec-processing-wrap-flex');
			}
			$btn.removeClass('rtec-fade').prop('disabled', false).attr('aria-busy', 'false');
		}, 100);
	}

	function setMessage($el, text, isError) {
		$el.removeClass('rtec-success rtec-error').addClass(isError ? 'rtec-error' : 'rtec-success').text(text).show();
	}

	function goToStep(step) {
		if (pageUrl) {
			window.location.href = pageUrl + '&step=' + step;
		}
	}

	// Step 1: Update install CTA text and uninstall notice when path selection changes.
	$(document).on('change', 'input[name="rtec_path"]', function() {
		var cta = $(this).data('cta');
		if (cta) {
			$('.rtec-onboarding-install-continue .rtec-button-text').text(cta);
		}
		$('.rtec-onboarding-step1-uninstall-notice').toggle( $(this).val() === 'event-genius' );
	});

	// Step 1: "Install and Continue" — install/activate selected plugin (TEC or Event Genius).
	$(document).on('click', '.rtec-onboarding-install-continue', function() {
		var path = $('input[name="rtec_path"]:checked').val();
		var $btn = $(this);
		var $msg = $('.rtec-onboarding-step1-message');
		var pluginSlug = (path === 'event-genius') ? 'event-genius' : 'tribe-tec';

		startProcessing($btn);
		setMessage($msg, strings.thanks_patience || strings.installing || 'Installing…', false);
		$.post(ajaxUrl, {
			action: 'rtec_addon_install',
			rtec_nonce: rtecOnboarding.rtec_nonce,
			plugin: pluginSlug,
			activate_after: '1'
		}).done(function(res) {
			if (res.success) {
				setMessage($msg, strings.success || 'Success! Continuing…', false);
				if (pluginSlug === 'event-genius' && rtecOnboarding.evge_dashboard_url) {
					setTimeout(function() { window.location.href = rtecOnboarding.evge_dashboard_url; }, 800);
				} else {
					setTimeout(function() { goToStep(2); }, 800);
				}
			} else {
				setMessage($msg, (res.data && res.data.messageHTML) ? $('<div>').html(res.data.messageHTML).text() : (strings.error || 'Error'), true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error || 'Something went wrong.', true);
			stopProcessing($btn);
		});
	});

	// Step 1 (when TEC already active): Continue with Registrations link.
	$(document).on('click', '.rtec-onboarding-btn-continue', function(e) {
		e.preventDefault();
		if ($(this).attr('href')) {
			window.location.href = $(this).attr('href');
		}
	});

	// Step 2: TEC Install (one of two buttons when not installed)
	$(document).on('click', '.rtec-onboarding-tec-install, .rtec-onboarding-tec-install-activate', function() {
		var $btn = $(this);
		var activateAfter = $btn.data('activate-after') ? '1' : '0';
		var $wrap = $('#rtec-admin-tec-welcome');
		var $msg = $wrap.find('.rtec-onboarding-ajax-message');
		startProcessing($btn);
		setMessage($msg, strings.thanks_patience || strings.installing || 'Installing…', false);
		$.post(ajaxUrl, {
			action: 'rtec_addon_install',
			rtec_nonce: rtecOnboarding.rtec_nonce,
			plugin: 'tribe-tec',
			activate_after: activateAfter
		}).done(function(res) {
			if (res.success) {
				if (activateAfter === '0') {
					setMessage($msg, (res.data && res.data.messageHTML) ? $('<div>').html(res.data.messageHTML).text() : strings.success, false);
					setTimeout(function() { window.location.reload(); }, 1200);
				} else {
					setMessage($msg, strings.success || 'Success! Continuing…', false);
					setTimeout(function() { goToStep(2); }, 800);
				}
			} else {
				setMessage($msg, (res.data && res.data.messageHTML) ? $('<div>').html(res.data.messageHTML).text() : (strings.error || 'Error'), true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error || 'Something went wrong.', true);
			stopProcessing($btn);
		});
	});

	// Step 2: TEC Activate (when installed but not active — button in box or in .rtec-onboarding-tec-activate-footer)
	$(document).on('click', '#rtec-admin-tec-welcome .rtec-addon-activate', function() {
		var $btn = $(this);
		var $wrap = $('#rtec-admin-tec-welcome');
		var $msg = $wrap.find('.rtec-onboarding-ajax-message');
		startProcessing($btn);
		setMessage($msg, strings.activating || 'Activating…', false);
		$.post(ajaxUrl, {
			action: 'rtec_addon_activate',
			rtec_nonce: rtecOnboarding.rtec_nonce,
			plugin: 'tribe-tec'
		}).done(function(res) {
			if (res.success) {
				setMessage($msg, strings.success || 'Success! Continuing…', false);
				setTimeout(function() { goToStep(2); }, 800);
			} else {
				setMessage($msg, (res.data && res.data.messageHTML) ? $(res.data.messageHTML).text() : (strings.error || 'Error'), true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error || 'Something went wrong.', true);
			stopProcessing($btn);
		});
	});

	// Step 3: Enable all or show 3B
	$(document).on('click', '.rtec-onboarding-enable-continue', function() {
		var mode = $('input[name="rtec_enable_mode"]:checked').val();
		var $footer = $(this).closest('.rtec-onboarding-enable-footer');
		var $msg = $footer.next('.rtec-onboarding-ajax-message');
		if (mode === 'selected') {
			$('#rtec-onboarding-step-3').hide();
			$('#rtec-onboarding-step-3b').show();
			return;
		}
		var $btn = $(this);
		startProcessing($btn);
		setMessage($msg, strings.installing || 'Enabling…', false);
		$.post(ajaxUrl, {
			action: 'rtec_onboarding_enable_all',
			nonce: nonce
		}).done(function(res) {
			if (res.success) {
				setMessage($msg, strings.success || 'Success!', false);
				setTimeout(function() { goToStep(4); }, 800);
			} else {
				setMessage($msg, (res.data && res.data.message) || strings.error, true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error, true);
			stopProcessing($btn);
		});
	});

	// Step 3B: Enable single event
	$(document).on('click', '.rtec-onboarding-enable-single', function() {
		var eventId = $('#rtec-onboarding-select-event').val();
		if (!eventId) return;
		var $btn = $(this);
		var $actions = $(this).closest('.rtec-onboarding-step-3b-actions');
		var $msg = $actions.next('.rtec-onboarding-ajax-message');
		startProcessing($btn);
		setMessage($msg, strings.activating || 'Enabling…', false);
		$.post(ajaxUrl, {
			action: 'rtec_onboarding_enable_event',
			nonce: nonce,
			event_id: eventId
		}).done(function(res) {
			if (res.success) {
				setMessage($msg, strings.success || 'Success!', false);
				setTimeout(function() { goToStep(4); }, 800);
			} else {
				setMessage($msg, (res.data && res.data.message) || strings.error, true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error, true);
			stopProcessing($btn);
		});
	});

	// Step 4: Create event
	$(document).on('click', '.rtec-onboarding-create-event', function() {
		var title = $('#rtec-onboarding-event-title').val();
		title = (title && title.trim()) ? title.trim() : 'My Test Event';
		var date     = $('#rtec-onboarding-event-start-date').val();
		var time     = $('#rtec-onboarding-event-start-time').val();
		var endDate  = $('#rtec-onboarding-event-end-date').val();
		var endTime  = $('#rtec-onboarding-event-end-time').val();
		if (!date) date = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
		if (!time) time = '09:00';
		if (!endDate) endDate = date;
		if (!endTime) endTime = '17:00';
		var timeFull   = time.length === 5 ? time + ':00' : time;
		var endTimeFull = endTime.length === 5 ? endTime + ':00' : endTime;
		var $btn = $(this);
		var $msg = $('.rtec-onboarding-create-event-footer').next('.rtec-onboarding-ajax-message');
		startProcessing($btn);
		setMessage($msg, strings.creating || 'Creating event…', false);
		$.post(ajaxUrl, {
			action: 'rtec_onboarding_create_event',
			nonce: nonce,
			title: title,
			date: date,
			time: timeFull,
			end_date: endDate,
			end_time: endTimeFull
		}).done(function(res) {
			if (res.success) {
				setMessage($msg, strings.success || 'Success!', false);
				setTimeout(function() { goToStep(4); }, 800);
			} else {
				setMessage($msg, (res.data && res.data.message) || strings.error, true);
				stopProcessing($btn);
			}
		}).fail(function() {
			setMessage($msg, strings.error, true);
			stopProcessing($btn);
		});
	});

})(jQuery);
