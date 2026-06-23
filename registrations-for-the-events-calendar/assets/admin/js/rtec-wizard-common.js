(function($, window) {
	'use strict';

	var SPINNER_HTML = '<div class="rtec-spinner-container"><div class="rtec-spinner-circle"></div></div>';

	window.RtecWizard = window.RtecWizard || {};

	window.RtecWizard.SPINNER_HTML = SPINNER_HTML;

	window.RtecWizard.startProcessing = function($btn) {
		if (!$btn || !$btn.length) {
			return;
		}
		$btn.attr('aria-busy', 'true');
		$btn.wrap('<div class="rtec-processing-wrap-flex rtec-is-processing"></div>');
		$btn.addClass('rtec-fade').prop('disabled', true);
		$btn.before(SPINNER_HTML);
	};

	window.RtecWizard.stopProcessing = function($btn) {
		if (!$btn || !$btn.length) {
			return;
		}
		setTimeout(function() {
			var $wrap = $btn.closest('.rtec-processing-wrap-flex');
			if ($wrap.length) {
				$wrap.find('.rtec-spinner-container').remove();
				$btn.unwrap('.rtec-processing-wrap-flex');
			}
			$btn.removeClass('rtec-fade').prop('disabled', false).attr('aria-busy', 'false');
		}, 100);
	};

	window.RtecWizard.setMessage = function($el, text, isError) {
		$el.removeClass('rtec-success rtec-error').addClass(isError ? 'rtec-error' : 'rtec-success').text(text).show();
	};

	window.RtecWizard.goToStep = function(pageUrl, step) {
		if (pageUrl) {
			window.location.href = pageUrl + '&step=' + step;
		}
	};

})(jQuery, window);
