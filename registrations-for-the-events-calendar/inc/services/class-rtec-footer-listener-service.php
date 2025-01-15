<?php

class RTEC_Footer_Listener_Service {

	private $entry;

	public function init_hooks() {
		add_action( 'rtec_footer_listeners', array( $this, 'action_listener' ) );
		add_action( 'wp_ajax_rtec_confirm_unregistration', array( $this, 'confirm_unregistration' ) );
		add_action( 'wp_ajax_nopriv_rtec_confirm_unregistration', array( $this, 'confirm_unregistration' ) );
        add_action( 'wp_ajax_rtec_unregister_by_event_id_for_logged_in_user', array( $this, 'unregister_by_event_id_for_logged_in_user' ) );
	}

	public function action_listener() {
		if ( empty( $_GET['action'] ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		$action = sanitize_key( $_GET['action'] );

		$verification_data = array(
			'email'  => isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '',
			'token'  => isset( $_GET['token'] ) ? sanitize_key( $_GET['token'] ) : '',
			'action' => $action,
		);

		$rtec = RTEC();

		$entry_exists = $rtec->db_frontend->maybe_verify_token( $verification_data );

		if ( $verification_data['action'] === 'unregister' && $entry_exists && $verification_data['token'] !== '' ) {

			$event_id = get_the_ID();
			$form     = $rtec->form->instance();

			$form->build_form( $event_id );

			if ( $form->registration_deadline_has_passed() ) {
				return;
			}

			$args = array(
				'fields'   => array(
					'id',
					'event_id',
					'registration_date',
					'action_key',
				),
				'where'    => array(
					array( 'action_key', $verification_data['token'], '=', 'string' ),
				),
				'order_by' => 'registration_date',
			);

			$entries = RTEC()->db_frontend->retrieve_entries( $args, false, 1, 'DESC' );

			if ( empty( $entries ) ) {
				return;
			}

			$event_id = isset( $entries[0] ) ? $entries[0]['event_id'] : 0;
			$entry    = $entries[0];

			global $rtec_options;

			$require_confirmation = isset( $rtec_options['require_unregister_confirmation'] ) ? $rtec_options['require_unregister_confirmation'] : false;
			if ( ! empty( $_GET['confirm_required'] ) ) {
				$require_confirmation = true;
			}

			if ( ! $require_confirmation ) {
				$this->maybe_unregister( $verification_data, $entry );
				add_filter( 'rtec_action_modal_content_items', array( $this, 'add_cancel_complete_modal' ) );

			} else {
				$this->entry = $entry;

				add_filter( 'rtec_action_modal_content_items', array( $this, 'add_unregister_confirm_modal' ) );
			}
		} elseif ( $verification_data['action'] === 'unregister' && ! $entry_exists ) {
			add_filter( 'rtec_action_modal_content_items', array( $this, 'no_record_found' ) );
        }
	}

	public function maybe_unregister( $verification_data, $entry ) {
		if ( ( strtotime( $entry['registration_date'] ) + 30 ) < time() ) {
			global $rtec_options;

			$disable_notification = isset( $rtec_options['disable_notification'] ) ? $rtec_options['disable_notification'] : false;

			if ( ! $disable_notification ) {
				rtec_send_unregistration_notification( array( $entry['id'] ) );
			}

			if ( empty( $rtec_options['disable_unregister_confirmation'] ) ) {
				rtec_send_unregistration_confirmation( array( $entry['id'] ) );
			}

			$record_was_deleted = RTEC()->db_frontend->remove_record_by_action_key( $verification_data['token'] );
			if ( $record_was_deleted ) {
				RTEC()->db_frontend->update_num_registered_meta_for_event( $entry['event_id'] );

			}
		}
	}

	public function add_unregister_confirm_modal( $modal_content ) {
		global $rtec_options;
		$templater           = new RTEC_Templater();
		$confirm_button_text = isset( $rtec_options['confirm_unregister_message'] ) ? $rtec_options['confirm_unregister_message'] : __( 'Confirm Cancellation', 'registrations-for-the-events-calendar' );
		$confirm_button_text = rtec_get_text( $confirm_button_text, __( 'Confirm Cancellation', 'registrations-for-the-events-calendar' ) );
		ob_start();
		?>
		<div class="rtec-modal-content">
			<div class="rtec-modal-inner-pad">
				<div id="rtec-confirm-unregister" style="position: relative;">
					<p><?php echo wp_kses_post( $templater->event_details_search_and_replace( RTEC_Settings::get( 'cancel_confirm_message' ), $this->entry['event_id'] ) ); ?></p>
					<form id="rtec-confirm-unregister-form" action="<?php echo esc_url( get_the_permalink( $this->entry['event_id'] ) ); ?>" method="post">
						<button type="submit"><?php echo esc_html( $confirm_button_text ); ?></button>
						<input type="hidden" value="<?php echo esc_attr( $this->entry['action_key'] ); ?>" name="rtec_unregister_confirm">
						<input type="hidden" value="<?php echo esc_attr( $this->entry['event_id'] ); ?>" name="event_id">
					</form>
				</div>
			</div>
		</div>
		<?php

		$html = ob_get_contents();
		ob_get_clean();
		$modal_content['cancel-confirm'] = array(
			'html' => $html,
		);

		return $modal_content;
	}

	public function no_record_found( $modal_content ) {
		$none_found_text = RTEC_Settings::get( 'no_record_found_text' );
		ob_start();
		?>
        <div class="rtec-modal-content">
            <div class="rtec-modal-inner-pad">
                <div id="rtec-confirm-unregister" style="position: relative;">
                    <p><?php echo esc_html( $none_found_text ); ?></p>
                </div>
            </div>
        </div>
		<?php

		$html = ob_get_contents();
		ob_get_clean();
		$modal_content['cancel-confirm'] = array(
			'html' => $html,
		);

		return $modal_content;
	}

	public function add_cancel_complete_modal( $modal_content ) {
		$templater = new RTEC_Templater();
		ob_start();
		?>
		<div class="rtec-modal-content">
			<div class="rtec-modal-inner-pad">
				<p class="rtec-attendance tribe-events-notices"><?php echo wp_kses_post( $templater->event_details_search_and_replace( RTEC_Settings::get( 'success_unregistration' ), get_the_ID() ) ); ?></p>
			</div>
		</div>
		<?php

		$html = ob_get_contents();
		ob_get_clean();
		$modal_content['cancel-complete'] = array(
			'html' => $html,
		);

		return $modal_content;
	}

	public function confirm_unregistration() {
		global $rtec_options;
		$action_key = isset( $_POST['rtec_unregister_confirm'] ) ? sanitize_key( $_POST['rtec_unregister_confirm'] ) : '';

		if ( $action_key !== '' ) {

			$args = array(
				'fields'   => array(
					'id',
					'event_id',
					'registration_date',
				),
				'where'    => array(
					array( 'action_key', $action_key, '=', 'string' ),
				),
				'order_by' => 'registration_date',
			);

			$rtec = RTEC();

			$entries = $rtec->db_frontend->retrieve_entries( $args, false, 1, 'DESC' );

			if ( isset( $entries[0] ) ) {
				$disable_notification = isset( $rtec_options['disable_notification'] ) ? $rtec_options['disable_notification'] : false;

				if ( ! $disable_notification ) {
					rtec_send_unregistration_notification( array( $entries[0]['id'] ) );
				}

				if ( empty( $rtec_options['disable_unregister_confirmation'] ) ) {
					rtec_send_unregistration_confirmation( array( $entries[0]['id'] ) );
				}

				$record_was_deleted = RTEC()->db_frontend->remove_record_by_action_key( $action_key );
				if ( $record_was_deleted ) {
					RTEC()->db_frontend->update_num_registered_meta_for_event( $entries[0]['event_id'] );
					$templater = new RTEC_Templater();

					echo '<div class="rtec-attendance tribe-events-notices rtec-unregister-message" style="display: none;">' . wp_kses_post( $templater->event_details_search_and_replace( RTEC_Settings::get( 'success_unregistration' ), $entries[0]['event_id'] ) ) . '</div>';

				} else {
					echo '<div class="rtec-attendance tribe-events-notices rtec-unregister-message rtec-scrollto" style="display: none;">' . esc_html( RTEC_Settings::get( 'no_record_found_text' ) ) . '</div>';
				}
				die();
			}
		}

		echo '<div class="rtec-attendance tribe-events-notices rtec-unregister-message rtec-scrollto" style="display: none;">' . esc_html__( 'Something went wrong.', 'registrations-for-the-events-calendar' ) . '</div>';

		die();
	}

	public function unregister_by_event_id_for_logged_in_user() {
		$event_id = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;

        $return = array(
                'success' => false,
                'html' => ''
        );
        if ( empty( $event_id ) ) {
	        $return['html'] = 'No Event ID';
            wp_send_json_error( $return );
        }

        $logged_in_event_goer = new RTEC_Logged_In_Event_Goer( get_current_user_id() );
        $logged_in_event_goer->init( new RTEC_Event( $event_id ) );

        if ( empty( $logged_in_event_goer->get_entry_id() ) ) {
	        $return['html'] = 'No Record Found';
	        wp_send_json_error( $return );
        }

        $event_data = $logged_in_event_goer->get_event_data();

		global $rtec_options;

		$disable_notification = isset( $rtec_options['disable_notification'] ) ? $rtec_options['disable_notification'] : false;

		if ( ! $disable_notification ) {
			rtec_send_unregistration_notification( array( $event_data['id'] ) );
		}

		if ( empty( $rtec_options['disable_unregister_confirmation'] ) ) {
			rtec_send_unregistration_confirmation( array( $event_data['id'] ) );
		}

		$record_was_deleted = RTEC()->db_frontend->remove_record_by_action_key( $event_data['action_key'] );

		if ( $record_was_deleted ) {
			$templater = new RTEC_Templater();

			$return['success'] = true;
			$return['html'] = '<p class="rtec-attendance tribe-events-notices rtec-scrollto">' . wp_kses_post( $templater->event_details_search_and_replace( RTEC_Settings::get( 'success_unregistration' ), $event_id ) ) . '</p>';
			wp_send_json_success( $return );

			do_action( 'rtec_after_unregistration', array( $event_data['id'] ), $event_id, 'footer_listener_unregister_by_event_id_for_logged_in_user' );
		} else {
			$return['html'] = '<p class="rtec-attendance tribe-events-notices rtec-scrollto">' . esc_html__( 'No record found.', 'registrations-for-the-events-calendar' ) . '</p>';
		}

		wp_send_json_error( $return );
    }

}
