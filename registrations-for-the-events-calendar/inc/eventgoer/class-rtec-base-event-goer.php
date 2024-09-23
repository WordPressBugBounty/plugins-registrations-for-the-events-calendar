<?php

class RTEC_Base_Event_Goer {
	protected $user_id;

	protected $database;

	/**
	 * @var RTEC_Event
	 */
	protected $event;

	protected $existing_event_data;

	protected $user_data;

	protected $submission_status;


	protected $event_status;

	public function __construct( $user_id ) {
		$this->user_id             = $user_id;
		$this->database            = RTEC()->db_frontend;
		$this->existing_event_data = array();
		$this->user_data           = array();
		$this->submission_status   = 'new';
		$this->event_status        = false;
	}

	public function init( $event ) {
		$this->event = $event;

		$user_data = array();
		$user_meta = array();
		if ( $this->user_id > 0 ) {
			$user_meta = get_user_meta( $this->user_id, '', true );

			$user_obj              = get_userdata( $this->user_id );
			$user_data['first']    = isset( $user_meta['first_name'] ) ? $user_meta['first_name'][0] : '';
			$user_data['last']     = isset( $user_meta['last_name'] ) ? $user_meta['last_name'][0] : '';
			$user_data['email']    = isset( $user_obj->data->user_email ) ? $user_obj->data->user_email : '';
			$user_data['nickname'] = isset( $user_meta['nickname'] ) ? $user_meta['nickname'][0] : '';

		}

		$this->user_data = apply_filters( 'rtec_user_data', $user_data, $user_meta );

		$existing_event_data = $this->query_existing_registration_data();

		$user_submission_status = 'new';

		global $rtec_options;

		$can_register_more_than_once = isset( $rtec_options['allow_users_reregister'] ) ? $rtec_options['allow_users_reregister'] : false;
		if ( ! $can_register_more_than_once && ! empty( $existing_event_data ) ) {
			$user_submission_status = 'submission_made';
		}
		$this->submission_status = $user_submission_status;

		if ( $user_submission_status !== 'new' ) {
			$this->existing_event_data = $existing_event_data;
			$this->event_status        = ! empty( $existing_event_data );
		}

		return $existing_event_data;
	}

	/**
	 * @return Event
	 */
	public function event() {
		return $this->event;
	}

	public function query_existing_registration_data() {
		return array();
	}

	public function get_event_status() {
		return $this->event_status;
	}

	public function can_register_for_event() {
		return false;
	}

	public function has_made_submission_for_event() {
		return $this->submission_status === 'submission_made';
	}

	public function can_edit_registration_status() {
		return false;
	}

	public function get_event_data() {
		return $this->get_legacy_combined_data();
	}

	protected function get_legacy_combined_data() {
		return array_merge( $this->user_data, $this->existing_event_data );
	}

	public function can_skip_spam_detection() {
		return false;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function get_entry_id() {
		if ( empty( $this->existing_event_data ) ) {
			return false;
		}

		return ! empty( $this->existing_event_data['id'] ) ? absint( $this->existing_event_data['id'] ) : 0;
	}

	public function filter_field_value( $value, $field_attributes ) {
		$event_data = $this->get_event_data();
		$field_slug = str_replace( 'rtec_', '', $field_attributes['name'] );

		if ( ! empty( $event_data ) && ! empty( $event_data[ $field_slug ] ) ) {
			return $event_data[ $field_slug ];
		}

		$value = $this->filter_field_value_with_user_data( $value, $field_slug );

		return $value;
	}

	protected function filter_field_value_with_user_data( $value, $field_slug ) {
		global $rtec_options;
		$should_prefill              = isset( $rtec_options['prefill_form'] ) ? $rtec_options['prefill_form'] : true;
		$can_register_more_than_once = isset( $rtec_options['allow_users_reregister'] ) ? $rtec_options['allow_users_reregister'] : false;

		if ( ! $should_prefill || $can_register_more_than_once ) {
			return $value;
		}

		if ( ! empty( $this->user_data[ $field_slug ] ) ) {
			return $this->user_data[ $field_slug ];
		}

		return $value;
	}
}
