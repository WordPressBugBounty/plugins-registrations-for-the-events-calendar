jQuery(document).ready(function($){
	var upsellContentClass = 'rtec-modal-content-is-upsell';

	$('.rtec-modal-opener').on('click',function(event) {
		event.preventDefault();
		var $trigger = $(this);
		var content = typeof $trigger.attr('data-content') !== 'undefined' ? $trigger.attr('data-content') : false;
		var ajaxPayload = $trigger.attr('data-rtec-ajax');
		if ( content === 'ajax' && ajaxPayload ) {
			try {
				var payload = typeof ajaxPayload === 'string' ? JSON.parse( ajaxPayload ) : ajaxPayload;
				rtecOpenModalAjax( payload );
			} catch ( e ) {
				// Invalid payload; do nothing
			}
		}
	});

	$('.rtec-modal-backdrop, .rtec-modal-close').on('click',function() {
		rtecCloseModal();
	});

	function rtecOpenModalAjax( payload ) {
		var $slot = $('.rtec-modal-ajax-slot');
		var $innerPad = $('.rtec-modal .rtec-modal-content .rtec-modal-inner-pad');
		var target = $slot.length ? $slot : $innerPad;
		var $modalContent = $('.rtec-modal-content');
		target.empty().show();
		$modalContent.addClass( upsellContentClass );
		$('body').addClass( 'rtec-modal-is-open' );
		$.post(
			typeof rtecAdminModalScript !== 'undefined' ? rtecAdminModalScript.ajaxUrl : '',
			{
				action: payload.action || 'rtec_get_upsell_modal',
				nonce: typeof rtecAdminModalScript !== 'undefined' ? rtecAdminModalScript.nonce : '',
				type: payload.type || '',
				location: payload.location || ''
			},
			function( response ) {
				if ( response && response.success && response.data && response.data.html ) {
					target.html( response.data.html );
				} else {
					target.html( '<p class="rtec-modal-alert">' + ( response && response.data && response.data.message ? response.data.message : 'Unable to load content.' ) + '</p>' );
				}
			},
			'json'
		).fail( function() {
			target.html( '<p class="rtec-modal-alert">Unable to load content.</p>' );
		});
	}

	function rtecCloseModal() {
		$('body').removeClass( 'rtec-modal-is-open' );
		$('.rtec-modal-content').removeClass( upsellContentClass );
		$('.rtec-modal-ajax-slot').empty().hide();
	}
});