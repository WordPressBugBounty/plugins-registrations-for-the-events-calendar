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

		$event_id = $sanitized_data['event_id'];

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
}
