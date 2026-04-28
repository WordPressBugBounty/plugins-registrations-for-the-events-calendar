<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Serves AJAX content for the admin content modal (manage-registration, etc.).
 */
class RTEC_Modal_Content_Service {

	/**
	 * Register AJAX and hooks.
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_rtec_modal_content', array( $this, 'handle_modal_content' ) );
	}

	/**
	 * AJAX handler: return HTML for the requested content type.
	 */
	public function handle_modal_content() {
		check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

		$content_type = isset( $_POST['content_type'] ) ? sanitize_key( wp_unslash( $_POST['content_type'] ) ) : '';
		if ( $content_type === '' ) {
			wp_send_json_error( array( 'message' => __( 'Missing content type.', 'registrations-for-the-events-calendar' ) ) );
		}

		$handlers = $this->get_content_handlers();
		if ( ! isset( $handlers[ $content_type ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown content type.', 'registrations-for-the-events-calendar' ) ) );
		}

		$data = $this->get_request_data();
		$html = call_user_func( $handlers[ $content_type ], $data );
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Content type handlers.
	 *
	 * @return array
	 */
	private function get_content_handlers() {
		$handlers = array(
			'manage-registration' => array( $this, 'render_manage_registration_content' ),
		);
		return apply_filters( 'rtec_modal_content_handlers', $handlers );
	}

	/**
	 * Get sanitized request data from POST (trigger data).
	 *
	 * @return array
	 */
	private function get_request_data() {
		$data = array(
			'content_type' => isset( $_POST['content_type'] ) ? sanitize_key( wp_unslash( $_POST['content_type'] ) ) : '',
		);
		$keys = array( 'registration_id', 'event_id', 'panel', 'entry_id', 'field_id' );
		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$data[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}
		return $data;
	}

	/**
	 * Render manage-registration tabbed content (Submissions + Contact).
	 *
	 * @param array $data Request data with event_id, registration_id.
	 * @return string HTML.
	 */
	private function render_manage_registration_content( $data ) {
		$event_id        = isset( $data['event_id'] ) ? (int) $data['event_id'] : 0;
		$registration_id = isset( $data['registration_id'] ) ? (int) $data['registration_id'] : 0;

		if ( $event_id <= 0 ) {
			return '<div class="rtec-content-modal-body"><p class="rtec-content-modal-error">' . esc_html__( 'Invalid event.', 'registrations-for-the-events-calendar' ) . '</p></div>';
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			$post = get_post( $event_id );
			if ( ! $post || (int) $post->post_author !== (int) get_current_user_id() ) {
				return '<div class="rtec-content-modal-body"><p class="rtec-content-modal-error">' . esc_html__( 'You do not have permission to manage this registration.', 'registrations-for-the-events-calendar' ) . '</p></div>';
			}
		}

		$context = RTEC_Manage_Modal_Context::get_context( $event_id, $registration_id );
		$event_meta          = $context['event_meta'];
		$event_obj           = $context['event_obj'];
		$admin_registrations = $context['admin_registrations'];
		$form                = $context['form'];
		$fields_atts         = $context['fields_atts'];
		$event_id            = $context['event_id'];
		$entry_data          = $context['entry_data'];

		ob_start();
		include rtec_plugin_path( 'admin-templates/registration-manage-modal/tabbed-content.php' );
		return ob_get_clean();
	}
}
