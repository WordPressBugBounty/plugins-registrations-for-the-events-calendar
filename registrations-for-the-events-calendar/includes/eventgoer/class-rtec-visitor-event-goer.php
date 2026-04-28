<?php

class RTEC_Visitor_Event_Goer extends RTEC_Base_Event_Goer {

	public function query_existing_registration_data() {
		return array();
	}

	public function can_register_for_event() {
		$event_meta = $this->event->get_meta();

		return apply_filters( 'rtec_user_can_register', $event_meta['who_can_register'] !== 'users', $this->user_id, $this->get_legacy_combined_data() );
	}

	public function can_edit_registration_status() {
		global $rtec_options;

		return isset( $rtec_options['visitors_can_edit_what_status'] ) ? (bool) $rtec_options['visitors_can_edit_what_status'] : true;
	}

	public function filter_field_value( $value, $field_attributes, $is_guest = false ) {
		return $value;
	}
}
