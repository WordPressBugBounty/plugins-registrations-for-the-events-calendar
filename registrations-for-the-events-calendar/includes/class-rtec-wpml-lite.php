<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'RTEC_WPML_Lite' ) ) {
	class RTEC_WPML_Lite {

		public static function shared_count_enabled() {
			global $rtec_options;

			return isset( $rtec_options['wpml_share_registrations'] ) && $rtec_options['wpml_share_registrations'] === 'enabled';
		}

		public static function get_all_translation_ids( $event_id ) {
			global $sitepress;
			$el_type = 'post_tribe_events';
			$trid = $sitepress->get_element_trid( $event_id, $el_type );

			return $sitepress->get_element_translations( $trid, $el_type, false, false, false, false, true );
        }

		public static function wpml_is_active() {
			if ( empty( $GLOBALS ) ) {
				return false;
			}
			if ( ! class_exists( 'SitePress' ) ) {
				return false;
			}
			if ( ! array_key_exists('sitepress', $GLOBALS ) ) {
				return false;
			}

			global $sitepress;

			if ( empty( $sitepress ) ) {
				return false;
			}

			return defined( 'ICL_LANGUAGE_CODE' ) && method_exists( $sitepress, 'get_element_trid' );
		}

	}
}
