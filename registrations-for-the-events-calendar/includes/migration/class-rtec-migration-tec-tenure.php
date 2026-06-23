<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Estimates whether TEC was in use before the Event Genius migration rollout.
 *
 * TEC does not persist a first-install timestamp. We infer tenure from existing
 * TEC/RTEC activity dates, then fall back to TEC's version history helpers.
 */
class RTEC_Migration_TEC_Tenure {

	/** @var string Sites with activity before this date are treated as legacy TEC installs. */
	const LEGACY_CUTOFF_DATE = '2026-03-01';

	/**
	 * First TEC release in March 2026 (see TEC changelog).
	 * Used as a version proxy when activity dates are unavailable.
	 */
	const LEGACY_VERSION_CUTOFF = '6.15.17.1';

	/**
	 * Whether this site likely used TEC before March 2026.
	 *
	 * @return bool
	 */
	public static function was_installed_before_march_2026() {
		$cutoff = self::get_cutoff_timestamp();

		$legacy_from_activity = self::has_activity_before( $cutoff );
		if ( null !== $legacy_from_activity ) {
			return $legacy_from_activity;
		}

		return self::was_first_tec_version_before_cutoff();
	}

	/**
	 * @return int
	 */
	private static function get_cutoff_timestamp() {
		return (int) strtotime( self::LEGACY_CUTOFF_DATE . ' 00:00:00' );
	}

	/**
	 * @param int $cutoff Unix timestamp for the legacy cutoff.
	 * @return bool|null True when pre-cutoff activity exists, false when only newer activity exists, null when unknown.
	 */
	private static function has_activity_before( $cutoff ) {
		$signals = array();

		$oldest_post = self::get_oldest_tec_post_timestamp();
		if ( $oldest_post > 0 ) {
			$signals[] = $oldest_post < $cutoff;
		}

		$oldest_registration = self::get_oldest_registration_timestamp();
		if ( $oldest_registration > 0 ) {
			$signals[] = $oldest_registration < $cutoff;
		}

		if ( empty( $signals ) ) {
			return null;
		}

		return in_array( true, $signals, true );
	}

	/**
	 * @return bool
	 */
	private static function was_first_tec_version_before_cutoff() {
		if ( function_exists( 'tribe_installed_after' ) && tribe_installed_after( 'Tribe__Events__Main', self::LEGACY_VERSION_CUTOFF ) ) {
			return false;
		}

		if ( function_exists( 'tribe_installed_before' ) && tribe_installed_before( 'Tribe__Events__Main', self::LEGACY_VERSION_CUTOFF ) ) {
			return true;
		}

		$first_version = self::get_first_recorded_tec_version();
		if ( empty( $first_version ) ) {
			// TEC treats missing version history as a long-running install.
			return true;
		}

		return version_compare( $first_version, self::LEGACY_VERSION_CUTOFF, '<' );
	}

	/**
	 * @return string
	 */
	private static function get_first_recorded_tec_version() {
		$versions = self::get_previous_ecp_versions();
		if ( empty( $versions ) ) {
			return '';
		}

		foreach ( $versions as $version ) {
			if ( ! empty( $version ) && '0' !== (string) $version ) {
				return (string) $version;
			}
		}

		return '';
	}

	/**
	 * @return array<int, string>
	 */
	private static function get_previous_ecp_versions() {
		if ( class_exists( 'Tribe__Settings_Manager' ) ) {
			$versions = Tribe__Settings_Manager::get_option( 'previous_ecp_versions', array() );
			return is_array( $versions ) ? $versions : array();
		}

		$options = get_option( 'tribe_events_calendar_options', array() );
		if ( empty( $options['previous_ecp_versions'] ) || ! is_array( $options['previous_ecp_versions'] ) ) {
			return array();
		}

		return $options['previous_ecp_versions'];
	}

	/**
	 * @return int
	 */
	private static function get_oldest_tec_post_timestamp() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$oldest = $wpdb->get_var(
			"SELECT MIN(post_date) FROM {$wpdb->posts}
			WHERE post_type IN ('tribe_events', 'tribe_venue', 'tribe_organizer')
			AND post_status != 'auto-draft'"
		);

		if ( empty( $oldest ) ) {
			return 0;
		}

		return (int) strtotime( $oldest );
	}

	/**
	 * @return int
	 */
	private static function get_oldest_registration_timestamp() {
		global $wpdb;

		if ( ! defined( 'RTEC_TABLENAME' ) ) {
			return 0;
		}

		$table = $wpdb->prefix . RTEC_TABLENAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$oldest = $wpdb->get_var( "SELECT MIN(registration_date) FROM {$table}" );

		if ( empty( $oldest ) ) {
			return 0;
		}

		return (int) strtotime( $oldest );
	}
}
