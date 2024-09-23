jQuery(document).ready(function($){
	$('.rtec-modal-opener').on('click',function(event) {
		event.preventDefault();
		var content = typeof $(this).attr('data-content') !== 'undefined' ? $(this).attr('data-content') : false;
		if ( content ) {
			rtecOpenModal( content );
		}
	});

	$('.rtec-modal-backdrop, .rtec-modal-close').on('click',function() {
		rtecCloseModal();
	})

	function rtecOpenModal( content ) {
		$('.rtec-modal-item').hide();
		console.log(content)
		$('#rtec-modal-item-' + content).show();

		$('body').addClass( 'rtec-modal-is-open');
	}

	function rtecCloseModal() {
		$('body').removeClass( 'rtec-modal-is-open');
	}
});