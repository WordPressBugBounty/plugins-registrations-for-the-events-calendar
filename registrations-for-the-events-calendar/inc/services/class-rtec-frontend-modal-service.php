<?php

class RTEC_Frontend_Modal_Service {
	public function init_hooks() {
		add_action( 'rtec_footer', array( $this, 'render_modal' ) );
	}

	public function render_modal() {
		$modal_content = array();
		$maybe_form_modal_content = $this->maybe_form_modal_content();
		if ( $maybe_form_modal_content ) {
			$modal_content['form-modal'] = $maybe_form_modal_content;
		}

		$action_modal_content_items = apply_filters( 'rtec_action_modal_content_items', $modal_content );

		if ( ! empty( $action_modal_content_items ) ) {
			?>
			<div class="rtec-modal-backdrop"></div>

			<?php
			foreach ( $action_modal_content_items as $key => $item ) :
				?>
				<div id="rtec-modal" class="rtec-modal rtec-<?php echo esc_attr( $key ); ?>">
					<button type="button" class="rtec-button-link rtec-<?php echo esc_attr( $key ); ?>-close rtec-action-modal-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg><span class="rtec-media-modal-icon"><span class="screen-reader-text">Close</span></span></button>
					<?php echo $item['html']; ?>
				</div>
			<?php
			endforeach;
		}
	}

	public function maybe_form_modal_content() {
		global $rtec_options;
		if ( ! isset( $rtec_options['display_type'] ) || $rtec_options['display_type'] !== 'popup_modal' ) {
			return false;
		}

		return array(
			'html' => '<div class="rtec-modal-content"></div>'
		);

	}
}
