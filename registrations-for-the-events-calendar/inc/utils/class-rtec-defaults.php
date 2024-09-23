<?php
/**
 * Object to store defaults
 *
 * @since 2.21
 */

class Rtec_Defaults {

	/**
	 * All default values in key value pairs
	 *
	 * @return string[]
	 *
	 * @since 2.21
	 */
	public static function all() {
		$defaults = array(
			'already_canceled_message'            => __( 'Your registration was already canceled for the event {event-title} on {start-date}', 'registrations-for-the-events-calendar' ),
			'cancel_confirm_message'              => __( 'You are cancelling your registration for the event {event-title} on {start-date}', 'registrations-for-the-events-calendar' ),
			'no_record_found_text'                => __( 'No record found.', 'registrations-for-the-events-calendar' ),
			'send_notification_immediately'       => false,
			'success_unregistration'              => __( "Your registration has been canceled for {event-title} on {start-date}", 'registrations-for-the-events-calendar' ),
		);

		return $defaults;
	}

	/**
	 * Return a single default value by key.
	 *
	 * @param string $key
	 *
	 * @return string|null
	 *
	 * @since 2.21
	 */
	public static function get( $key ) {
		$defaults = self::all();

		if ( isset( $defaults[ $key ] ) ) {
			return $defaults[ $key ];
		}

		return null;
	}
}
