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

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $_GET['rtec_debug'] ) && $_GET['rtec_debug'] === '1' && isset( $_GET['action'] ) && $_GET['action'] === 'unregister' ) {
			$modal_content['rtec-debug-note'] = array(
				'html' => '<div class="rtec-modal-content"><div class="rtec-modal-inner-pad"><p>RTEC debug: ' . count( $action_modal_content_items ) . ' modal(s) will be shown. Check the debug box at the bottom of the page for details.</p></div></div>',
			);
			$action_modal_content_items = array_merge( $action_modal_content_items, $modal_content );
		}

		if ( ! empty( $action_modal_content_items ) ) {
			?>
			<div class="rtec-modal-backdrop"></div>

			<?php
			foreach ( $action_modal_content_items as $key => $item ) :
				?>
				<div id="rtec-modal" class="rtec-modal rtec-<?php echo esc_attr( $key ); ?>">
					<button type="button" class="rtec-button-link rtec-<?php echo esc_attr( $key ); ?>-close rtec-action-modal-close"><?php echo RTEC_Icon::get( 'close' ); ?><span class="rtec-media-modal-icon"><span class="screen-reader-text">Close</span></span></button>
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
