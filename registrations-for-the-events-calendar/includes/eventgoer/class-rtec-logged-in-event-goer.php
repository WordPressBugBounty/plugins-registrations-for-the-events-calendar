<?php

class RTEC_Logged_In_Event_Goer extends RTEC_Base_Event_Goer {

	public function query_existing_registration_data() {
		$args = array(
			'fields'   => array(
				'id',
				'first',
				'last',
				'email',
				'venue',
				'phone',
				'other',
				'event_id',
				'custom',
				'action_key',
				'guests',
			),
			'where'    => array(
				array( 'event_id', $this->event->get_post_id(), '=', 'int' ),
				array( 'user_id', $this->user_id, '=', 'int' ),
			),
			'order_by' => 'registration_date',
		);

		$registrations = $this->database->retrieve_entries( $args, false, 1 );

		if ( empty( $registrations ) ) {
			return array();
		}

		$registration     = $registrations[0];

		if ( isset( $registration['entry_data_cache'] ) ) {
			$registration = array_merge( $registration, rtec_convert_entry_data_cache_for_user( $registration['entry_data_cache'] ) );
		} elseif ( isset( $registration['custom'] ) ) {
			$registration          = array_merge( $registration, rtec_convert_entry_data_cache_for_user( $registration['custom'] ) );
			$registration['first'] = isset( $registration['first_name'] ) ? $registration['first_name'] : '';
			$registration['last']  = isset( $registration['last_name'] ) ? $registration['last_name'] : '';
		}

		return $registration;
	}

	public function can_register_for_event() {
		return apply_filters( 'rtec_user_can_register', true, $this->user_id, $this->get_legacy_combined_data() );
	}

	public function can_edit_registration_status() {
		global $rtec_options;
		$can_edit = isset( $rtec_options['visitors_can_edit_what_status'] ) ? (bool)$rtec_options['visitors_can_edit_what_status'] : true;

		return $can_edit;
	}

	public function can_skip_spam_detection() {
		return true;
	}



}
