<?php
/**
 * Find and replace placeholder templates for email messages and
 * other strings.
 *
 * @since 2.21
 */


class RTEC_Templater {

	/**
	 * Passes in text with placeholders that are converted into the appropriate
	 * text for a group of connected registrations and then a single registration.
	 *
	 * @param string $text
	 * @param array $sanitized_data
	 * @param bool $needs_templating
	 * @param array $custom_template_pairs
	 *
	 * @return string
	 *
	 * @since 2.21
	 */
	public function search_replace( $text, $sanitized_data, $needs_templating, $custom_template_pairs ) {
		$working_text = $text;

		return $this->individual_search_replace( $working_text, $sanitized_data, $needs_templating, $custom_template_pairs );
	}

	/**
	 * Passes in text with placeholders that are converted into the appropriate
	 * text for a single registration record
	 *
	 * @param string $text
	 * @param array $sanitized_data

	 *
	 * @return string
	 *
	 * @since 2.21
	 */
	public function individual_search_replace( $text, $sanitized_data ) {
		global $rtec_options;

		$working_text = $text;

		$date_str   = isset( $sanitized_data['date'] ) ? date_i18n( rtec_get_date_time_format(), strtotime( $sanitized_data['date'] ) ) : '';
		$start_date = '';
		$start_time = '';
		$end_date   = '';
		$end_time   = '';
		$event_link = '';
		$event_cost = function_exists( 'tribe_get_cost' ) ? tribe_get_cost( $sanitized_data['event_id'] ) : '';
		$first      = isset( $sanitized_data['first'] ) ? $sanitized_data['first'] : '';
		$last       = isset( $sanitized_data['last'] ) ? $sanitized_data['last'] : '';
		$email      = isset( $sanitized_data['email'] ) ? $sanitized_data['email'] : '';
		$phone      = isset( $sanitized_data['phone'] ) ? rtec_format_phone_number( $sanitized_data['phone'] ) : '';
		$other      = isset( $sanitized_data['other'] ) ? $sanitized_data['other'] : '';
		$mvt_label  = isset( $sanitized_data['mvt_label'] ) ? $sanitized_data['mvt_label'] : '';
		$ical_link  = '';
		if ( isset( $sanitized_data['event_id'] ) && ! empty( $sanitized_data['event_id'] ) ) {
			$event_link = get_the_permalink( $sanitized_data['event_id'] );
			$ical_link  = add_query_arg( 'ical', 1, $event_link );
		}
		if ( is_callable( 'tribe_get_start_date' ) && is_callable( 'tribe_get_end_date' ) ) {
			$time_format = rtec_get_time_format();

			$start_date = tribe_get_start_date( $sanitized_data['event_id'], false );
			$start_time = tribe_get_start_date( $sanitized_data['event_id'], false, $time_format );
			$end_date   = tribe_get_end_date( $sanitized_data['event_id'], false );
			$end_time   = tribe_get_end_date( $sanitized_data['event_id'], false, $time_format );
		}

		if ( ! empty( $sanitized_data ) ) {
			$search_replace = array(
				'{venue}'         => $sanitized_data['venue_title'],
				'{venue-address}' => $sanitized_data['venue_address'],
				'{venue-city}'    => $sanitized_data['venue_city'],
				'{venue-state}'   => $sanitized_data['venue_state'],
				'{venue-zip}'     => $sanitized_data['venue_zip'],
				'{event-title}'   => $sanitized_data['title'],
				'{event-date}'    => $date_str,
				'{start-date}'    => $start_date,
				'{start-time}'    => $start_time,
				'{end-date}'      => $end_date,
				'{end-time}'      => $end_time,
				'{event-url}'     => $event_link,
				'{event-cost}'    => $event_cost,
				'{first}'         => $first,
				'{last}'          => $last,
				'{email}'         => $email,
				'{phone}'         => $phone,
				'{other}'         => $other,
				'{venue-or-tier}' => $mvt_label,
				'{ical-url}'      => $ical_link,
			);

			$sanitized_data['event_id'] = isset( $sanitized_data['event_id'] ) ? $sanitized_data['event_id'] : $sanitized_data['post_id'];
			$search_replace             = apply_filters( 'rtec_email_templating', $search_replace, $sanitized_data );

			if ( $this->get_content_type() === 'plain' ) {
				$search_replace['{nl}'] = "\n";
			} else {
				$search_replace['{nl}'] = '<br />';
			}

			// add custom
			if ( is_array( $this->custom_template_pairs ) && ! empty( $this->custom_template_pairs ) ) {

				foreach ( $this->custom_template_pairs  as $field => $atts ) {

					if ( $atts['label'] !== '' ) {

						if ( isset( $sanitized_data['custom'][ $field ] ) ) {
							$search_replace[ '{' . str_replace( '&#42;', '', stripslashes( $atts['label'] ) ) . '}' ] = $sanitized_data['custom'][ $field ]['value'];
						} elseif ( isset( $sanitized_data['custom'][ $atts['label'] ] ) ) {
							$search_replace[ '{' . str_replace( '&#42;', '', stripslashes( $atts['label'] ) ) . '}' ] = $sanitized_data['custom'][ $atts['label'] ];
						} elseif ( isset( $sanitized_data[ $field ] ) ) {
							$search_replace[ '{' . str_replace( '&#42;', '', stripslashes( $atts['label'] ) ) . '}' ] = $sanitized_data[ $field ];
						} else {
							$search_replace[ '{' . str_replace( '&#42;', '', stripslashes( $atts['label'] ) ) . '}' ] = '';
						}
					}
				}
			}

			foreach ( $search_replace as $search => $replace ) {
				$working_text = str_replace( $search, $replace, $working_text );
			}
			$require_confirmation = empty( $_POST['action'] ) || $_POST['action'] !== 'rtec_send_unregister_link';

			if ( strpos( $working_text, '{unregister-link}' ) !== false ) {
				if ( isset( $sanitized_data['action_key'] ) && $sanitized_data['action_key'] != '' ) {
					$unregister_link_text = isset( $rtec_options['unregister_link_text'] ) ? $rtec_options['unregister_link_text'] : __( 'Unregister from this event', 'registrations-for-the-events-calendar' );
					$unregister_link_text = rtec_get_text( $unregister_link_text, __( 'Unregister from this event', 'registrations-for-the-events-calendar' ) );
					$u_link               = rtec_generate_unregister_link( $sanitized_data['event_id'], $sanitized_data['action_key'], $sanitized_data['email'], $unregister_link_text, $require_confirmation );
				} else {
					$u_link = '';
				}

				$working_text = str_replace( '{unregister-link}', $u_link, $working_text );
			}

			if ( strpos( $working_text, '{unregister-button}' ) !== false ) {
				$working_text = str_replace( '{unregister-button}', rtec_generate_unregister_button( $sanitized_data['event_id'], $sanitized_data['action_key'], $sanitized_data['email'] ), $working_text, $require_confirmation );
			}
		}

		return $working_text;
	}

	public function event_details_search_and_replace( $working_text, $event_id ) {
		if ( is_callable( 'tribe_get_start_date' ) && is_callable( 'tribe_get_end_date' ) ) {
			$time_format = rtec_get_time_format();

			$start_date = tribe_get_start_date( $event_id, false );
			$start_time = tribe_get_start_date( $event_id, false, $time_format );
			$end_date   = tribe_get_end_date( $event_id, false );
			$end_time   = tribe_get_end_date( $event_id, false, $time_format );
			$date_str = isset( $event_id ) ? date_i18n( rtec_get_date_time_format(), strtotime( tribe_get_start_date( $event_id, false, 'Y-m-d H:i:s' ) ) ) : '';
		}
		$search_replace = array(
			'{event-title}'             => get_the_title( $event_id ),
			'{event-date}'              => $date_str,
			'{start-date}'              => $start_date,
			'{start-time}'              => $start_time,
			'{end-date}'                => $end_date,
			'{end-time}'                => $end_time,
			'{event-url}'               => get_the_permalink( $event_id ),
		);

		foreach ( $search_replace as $search => $replace ) {
			$working_text = str_replace( $search, $replace, $working_text );
			if ( $search !== esc_html( $search ) ) {
				$working_text = str_replace( esc_html( $search ), $replace, $working_text );
			}
		}

		return $working_text;
	}
}

