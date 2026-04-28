<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class RTEC_Placeholder_Service {


	public function __construct() {
	}

	public function init_hooks() {
		add_filter( 'rtec_email_templating', array( $this, 'email_templating' ), 10, 2 );
	}

	public function email_templating( $search_replace, $sanitized_data ) {
		if ( empty( $sanitized_data['event_id'] ) ) {
			return $search_replace;
		}

		$event_id = (int) $sanitized_data['event_id'];
		$contextual_event_id = ! empty( $sanitized_data['contextual_event_id'] ) ? (int) $sanitized_data['contextual_event_id'] : $event_id;

		// Venue 2+ placeholders.
		$search_replace = $this->add_venue_placeholders( $search_replace, $event_id );

		// Calendar links (Event category): ical-link, gcal-link.
		$search_replace = $this->add_calendar_link_placeholders( $search_replace, $contextual_event_id );

		// Organizer placeholders: organizer-link, organizer-name/email/phone, organizer-1-*, organizer-2-*, etc.
		$search_replace = $this->add_organizer_placeholders( $search_replace, $event_id );

		return $search_replace;
	}

	/**
	 * Add venue-2, venue-3, ... placeholders when multiple venues exist.
	 *
	 * @param array $search_replace
	 * @param int   $event_id
	 * @return array
	 */
	protected function add_venue_placeholders( $search_replace, $event_id ) {
		$venues_repository = new \RTEC_Venue_Query( tribe_venues(), array( 'event' => $event_id ) );
		$venues_repository->init_query();
		$maybe_multiple_venues = $venues_repository->get_venues();
		if ( ! is_array( $maybe_multiple_venues ) ) {
			return $search_replace;
		}

		if ( empty( $maybe_multiple_venues[1] ) ) {
			return $search_replace;
		}

		$meta = ! empty( $event_id ) ? get_post_meta( $event_id ) : array();
		$venue_db_id = isset( $meta['_EventVenueID'][0] ) ? (int) $meta['_EventVenueID'][0] : 0;
		$working_index = 2;

		foreach ( $maybe_multiple_venues as $maybe_multiple_venue ) {
			$venue_id = $maybe_multiple_venue->ID;

			if ( $venue_db_id === $venue_id ) {
				continue;
			}

			$venue_meta = get_post_meta( $venue_id );
			if ( ! is_array( $venue_meta ) ) {
				continue;
			}

			$suffix = $working_index;
			++$working_index;
			$search_replace[ '{venue-' . $suffix . '}' ]         = get_the_title( $venue_id );
			$search_replace[ '{venue-address-' . $suffix . '}' ] = isset( $venue_meta['_VenueAddress'][0] ) ? $venue_meta['_VenueAddress'][0] : '';
			$search_replace[ '{venue-city-' . $suffix . '}' ]    = isset( $venue_meta['_VenueCity'][0] ) ? $venue_meta['_VenueCity'][0] : '';
			$search_replace[ '{venue-state-' . $suffix . '}' ]   = isset( $venue_meta['_VenueStateProvince'][0] ) ? $venue_meta['_VenueStateProvince'][0] : '';
			$search_replace[ '{venue-zip-' . $suffix . '}' ]     = isset( $venue_meta['_VenueZip'][0] ) ? $venue_meta['_VenueZip'][0] : '';
		}

		return $search_replace;
	}

	/**
	 * Add {ical-link} and {gcal-link} (Event category). {ical-url} is already set by Templater/Email.
	 *
	 * @param array $search_replace
	 * @param int   $event_id Event ID (or contextual event ID for series).
	 * @return array
	 */
	protected function add_calendar_link_placeholders( $search_replace, $event_id ) {
		$event_link = get_the_permalink( $event_id );
		$ical_url   = add_query_arg( array( 'ical' => 1 ), $event_link );

		$search_replace['{ical-link}'] = '<a class="tribe-events-ical tribe-events-button" href="' . esc_url( $ical_url ) . '" title="' . esc_attr__( 'Download .ics file', 'the-events-calendar' ) . '">+ ' . esc_html__( 'iCal Export', 'the-events-calendar' ) . '</a>';

		$gcal_link = '';
		if ( class_exists( 'Tribe__Events__Main' ) && function_exists( 'tribe_get_gcal_link' ) ) {
			$gcal_url = tribe_get_gcal_link( $event_id );
			if ( is_callable( array( \Tribe__Events__Main::instance(), 'esc_gcal_url' ) ) ) {
				$gcal_url = \Tribe__Events__Main::instance()->esc_gcal_url( $gcal_url );
			}
			$gcal_link = '<a class="tribe-events-gcal tribe-events-button" href="' . esc_url( $gcal_url ) . '" title="' . esc_attr__( 'Add to Google Calendar', 'the-events-calendar' ) . '">+ ' . esc_html__( 'Google Calendar', 'the-events-calendar' ) . '</a>';
		}
		$search_replace['{gcal-link}'] = $gcal_link;

		return $search_replace;
	}

	/**
	 * Add {organizer-link}, {organizer-name}, {organizer-email}, {organizer-phone}, and indexed organizer placeholders.
	 *
	 * @param array $search_replace
	 * @param int   $event_id
	 * @return array
	 */
	protected function add_organizer_placeholders( $search_replace, $event_id ) {
		// Organizer link(s): name(s) with link(s) to organizer page(s).
		if ( is_callable( 'tribe_get_organizer_ids' ) && is_callable( 'tribe_get_organizer_link' ) ) {
			$organizer_ids = tribe_get_organizer_ids( $event_id );
			$organizer_link_array = array();
			if ( is_array( $organizer_ids ) ) {
				foreach ( $organizer_ids as $organizer_id ) {
					$organizer_link_array[] = tribe_get_organizer_link( $organizer_id );
				}
			}
			$search_replace['{organizer-link}'] = implode( ', ', $organizer_link_array );
		} else {
			$search_replace['{organizer-link}'] = '';
		}

		if ( ! function_exists( 'tribe_get_organizer_ids' ) ) {
			$search_replace['{organizer-name}']  = '';
			$search_replace['{organizer-email}'] = '';
			$search_replace['{organizer-phone}'] = '';
			return $search_replace;
		}

		$organizer_ids = tribe_get_organizer_ids( $event_id );
		if ( empty( $organizer_ids ) || ! is_array( $organizer_ids ) ) {
			$search_replace['{organizer}']       = '';
			$search_replace['{organizer-email}'] = '';
			$search_replace['{organizer-phone}'] = '';
			return $search_replace;
		}

		foreach ( $organizer_ids as $index => $organizer_id ) {
			$organizer_id = (int) $organizer_id;

			$name  = '';
			$email = '';
			$phone = '';

			if ( function_exists( 'tribe_get_organizer' ) ) {
				$name = tribe_get_organizer( $organizer_id );
			} else {
				$name = get_the_title( $organizer_id );
			}
			if ( function_exists( 'tribe_get_organizer_email' ) ) {
				$email = tribe_get_organizer_email( $organizer_id, false );
			} else {
				$email = get_post_meta( $organizer_id, '_OrganizerEmail', true );
			}
			if ( function_exists( 'tribe_get_organizer_phone' ) ) {
				$phone = tribe_get_organizer_phone( $organizer_id );
			} else {
				$phone = get_post_meta( $organizer_id, '_OrganizerPhone', true );
			}

			$name  = $name ? sanitize_text_field( $name ) : '';
			$email = $email ? sanitize_email( $email ) : '';
			$phone = $phone ? sanitize_text_field( $phone ) : '';

			// Index at end like venue: {organizer} (first), {organizer-2}, {organizer-3}, etc.
			$suffix = $index + 1;
			if ( $index === 0 ) {
				$search_replace['{organizer}']       = $name;
				$search_replace['{organizer-email}'] = $email;
				$search_replace['{organizer-phone}'] = $phone;
			} elseif ( $suffix >= 2 ) {
				$search_replace[ '{organizer-' . $suffix . '}' ]  = $name;
				$search_replace[ '{organizer-email-' . $suffix . '}' ] = $email;
				$search_replace[ '{organizer-phone-' . $suffix . '}' ] = $phone;
			}
		}

		return $search_replace;
	}
}
