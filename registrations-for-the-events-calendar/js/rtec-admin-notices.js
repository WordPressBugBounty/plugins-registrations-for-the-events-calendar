jQuery( document ).ready(function ($) {

	function rtecRegistrationAjax(submitData,successFunc) {
		$.ajax(
			{
				url: rtecAdminNoticeScript.ajax_url,
				type: 'post',
				data: submitData,
				success: successFunc
			}
		);
	}

	$( 'body' ).on(
		'click',
		'.rtec-admin-notice-banner .notice-dismiss, .rtec-admin-notice-banner .notice-dismiss',
		function () {
		}
	);

	$( '#rtec-banner-dismiss' ).on(
		'click',
		function (event) {
			event.preventDefault();
			if (typeof $( '#rtec-banner-dismiss' ).attr( 'data-disabled' ) === 'undefined') {
				rtecDismissBanner( 'always' );
			}
		}
	);



	function rtecDismissBanner(time) {
		$( '#rtec-banner-dismiss' ).css( 'opacity', '.5' ).attr( 'data-disabled', '1' ).after( '<div class="spinner" style="visibility: visible; position: relative;float: left;"></div>' );

		var submitData = {
			action: 'rtec_dismiss_banner',
			time: time,
			rtec_nonce : rtecAdminNoticeScript.rtec_nonce
		},
		successFunc    = function (data) {
			if (data.success === true) {
				$( '.rtec-admin-notice-banner' ).fadeOut();
			} else {
				$( '#rtec-banner-dismiss' ).after( '<div>Error: Please refresh the page and try again.</div>' );
			}
		}
		rtecRegistrationAjax( submitData, successFunc );
	}

	$( '#rtec-smtp-notice' ).on(
		'click',
		function () {
			var submitData = {
				action: 'rtec_dismiss_dashboard_notice',
				type: $( 'this' ).attr( 'id' ),
				rtec_nonce : rtecAdminNoticeScript.rtec_nonce
			},
			successFunc    = function (data) {
			}
			rtecRegistrationAjax( submitData, successFunc );
		}
	);

});
