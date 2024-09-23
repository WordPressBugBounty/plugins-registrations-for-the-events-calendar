<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class RTEC_WPML_Service {


	public function __construct() {
	}

	public function init_hooks() {
		add_filter( 'rtec_event_meta', array( $this, 'maybe_change_event_meta' ), 10, 1 );
	}

	public function maybe_change_event_meta( $event_meta ) {
		if ( ! RTEC_WPML_Lite::wpml_is_active() ) {
			return $event_meta;
		}
		if ( ! RTEC_WPML_Lite::shared_count_enabled() ) {
			return $event_meta;
		}

		$original_post_id = $event_meta['post_id'];
		$meta = get_post_meta( $event_meta['post_id'] );

		if ( ! empty( $meta['_RTECmaxRegistrations'] ) ) {
			return $event_meta;
		}

		$translation_objects = RTEC_WPML_Lite::get_all_translation_ids( $event_meta['post_id'] );

		foreach ( $translation_objects as $translation_object ) {
			if ( $translation_object->original === '1' && (int)$translation_object->element_id !== $event_meta['post_id'] ) {
				$event_meta = rtec_get_event_meta( $translation_object->element_id );

				$event_meta['post_id'] = $original_post_id;
			}
		}

		return $event_meta;
	}
}
