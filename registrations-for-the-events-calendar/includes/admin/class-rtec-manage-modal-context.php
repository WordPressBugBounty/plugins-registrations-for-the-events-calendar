<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Builds context for the manage-registration modal (free version).
 */
class RTEC_Manage_Modal_Context {

	/**
	 * Get context array for rendering manage-registration or event-actions modal content.
	 *
	 * @param int $event_id        Event post ID.
	 * @param int $registration_id Registration row id (0 for "add new").
	 * @return array
	 */
	public static function get_context( $event_id, $registration_id = 0 ) {
		$event_id        = (int) $event_id;
		$registration_id = (int) $registration_id;

		$rtec  = RTEC();
		$form  = $rtec->form->instance();
		$form->build_form( $event_id );
		$event_meta  = $form->get_event_meta();
		$fields_atts = $form->get_field_attributes();

		$admin_registrations = new RTEC_Admin_Registrations();
		$admin_registrations->build_admin_registrations( 'single', array( 'id' => $event_id ) );

		$event_obj = new RTEC_Admin_Event();
		$event_obj->build_admin_event( $event_id, 'single', '', $form );

		$entry_data = array();
		if ( $registration_id > 0 ) {
			$entry_data = self::get_entry_row( $event_id, $event_meta, $registration_id, $fields_atts );
		}

		return array(
			'event_meta'          => $event_meta,
			'form'                => $form,
			'fields_atts'         => $fields_atts,
			'admin_registrations' => $admin_registrations,
			'event_obj'           => $event_obj,
			'event_id'            => $event_id,
			'entry_data'          => $entry_data,
		);
	}

	/**
	 * Load one registration row by event_id and registration id; flatten for form use.
	 *
	 * @param int   $event_id        Event post ID.
	 * @param array $event_meta      Event meta from form.
	 * @param int   $registration_id Registration row id.
	 * @param array $fields_atts     Form field attributes.
	 * @return array Flattened entry data or empty array.
	 */
	public static function get_entry_row( $event_id, $event_meta, $registration_id, $fields_atts ) {
		$rtec = RTEC();
		$db   = $rtec->db_frontend->instance();

		$columns = array( 'registration_date', 'status', 'event_id', 'id', 'user_id' );
		foreach ( array_keys( $fields_atts ) as $field ) {
			$columns[] = $field;
		}
		if ( ! in_array( 'custom', $columns, true ) ) {
			$columns[] = 'custom';
		}

		$args = array(
			'fields'   => $columns,
			'where'    => array(
				array( 'event_id', $event_id, '=', 'int' ),
				array( 'id', $registration_id, '=', 'int' ),
			),
			'order_by' => 'registration_date',
		);

		$results = $db->retrieve_entries( $args, false, 1 );
		if ( empty( $results ) || ! isset( $results[0] ) ) {
			return array();
		}

		return self::flatten_entry_row( $results[0], $fields_atts );
	}

	/**
	 * Get other registrations for the same person (same email or same user_id), excluding current entry/event.
	 * Used for "Related Registrations" in the manage modal (free version).
	 *
	 * @param array $entry_data    Current registration (must have id, event_id, email; optional user_id).
	 * @param int   $current_event_id Event ID to exclude from results (show other events only).
	 * @param int   $limit         Max number of related rows. Default 20.
	 * @return array List of rows with id, event_id, registration_date (and first_name, last_name, email from DB).
	 */
	public static function get_related_registrations( $entry_data, $current_event_id, $limit = 20 ) {
		if ( empty( $entry_data ) || empty( $entry_data['email'] ) ) {
			return array();
		}
		$current_id = isset( $entry_data['id'] ) ? (int) $entry_data['id'] : 0;
		$email      = sanitize_email( $entry_data['email'] );
		$user_id    = isset( $entry_data['user_id'] ) ? (int) $entry_data['user_id'] : 0;
		$rtec       = RTEC();
		$db         = $rtec->db_frontend->instance();

		$where = array();
		if ( $user_id > 0 ) {
			$where[] = array( 'user_id', $user_id, '=', 'int' );
		} else {
			$where[] = array( 'email', $email, '=', 'string' );
		}

		$args = array(
			'fields'   => array( 'id', 'event_id', 'registration_date', 'first_name', 'last_name', 'email' ),
			'where'    => $where,
			'order_by' => 'registration_date',
		);
		$results = $db->retrieve_entries( $args, false, $limit * 3, 'DESC' );
		if ( ! is_array( $results ) ) {
			return array();
		}
		$filtered = array();
		foreach ( $results as $row ) {
			$row_id     = isset( $row['id'] ) ? (int) $row['id'] : 0;
			$row_event  = isset( $row['event_id'] ) ? (int) $row['event_id'] : 0;
			if ( $row_id === $current_id && $row_event === (int) $current_event_id ) {
				continue;
			}
			if ( $row_event === (int) $current_event_id ) {
				continue;
			}
			$filtered[] = $row;
			if ( count( $filtered ) >= $limit ) {
				break;
			}
		}
		return $filtered;
	}

	/**
	 * Count total registrations for a user (by user_id). Used for User Information block.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int
	 */
	public static function get_registration_count_for_user( $user_id ) {
		if ( (int) $user_id <= 0 ) {
			return 0;
		}
		$rtec = RTEC();
		$db   = $rtec->db_frontend->instance();
		$args = array(
			'fields'   => array( 'id' ),
			'where'    => array( array( 'user_id', $user_id, '=', 'int' ) ),
			'order_by' => 'id',
		);
		$results = $db->retrieve_entries( $args, false, 9999 );
		return is_array( $results ) ? count( $results ) : 0;
	}

	/**
	 * Flatten one registration row: merge custom into top-level for form display.
	 * Maps DB column names to form field names (first_name -> first, last_name -> last)
	 * so edit fields prefill correctly.
	 *
	 * @param array $row        One row from retrieve_entries.
	 * @param array $fields_atts Form field attributes.
	 * @return array
	 */
	public static function flatten_entry_row( $row, $fields_atts ) {
		// Map DB columns to form field keys so form inputs find values by name.
		if ( array_key_exists( 'first_name', $row ) ) {
			$row['first'] = $row['first_name'];
		}
		if ( array_key_exists( 'last_name', $row ) ) {
			$row['last'] = $row['last_name'];
		}

		$custom = isset( $row['custom'] ) ? maybe_unserialize( $row['custom'] ) : array();
		if ( ! is_array( $custom ) ) {
			$custom = array();
		}
		unset( $row['custom'] );
		foreach ( $custom as $key => $val ) {
			if ( is_array( $val ) && isset( $val['value'] ) ) {
				$row[ $key ] = $val['value'];
			} else {
				$row[ $key ] = $val;
			}
		}
		return $row;
	}
}
