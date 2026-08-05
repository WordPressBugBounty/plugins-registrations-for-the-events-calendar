<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Eligibility checks for the production migration wizard.
 */
class RTEC_Migration_Eligibility {

	/**
	 * @deprecated Counts no longer block eligibility. Soft warnings live in Event Genius preflight.
	 */
	const MAX_EVENTS = 10000;

	/**
	 * @deprecated Counts no longer block eligibility. Soft warnings live in Event Genius preflight.
	 */
	const MAX_REGISTRATIONS = 50000;

	const STATUS_KEY_NO_MIGRATION_PROMPT = 'no_migration_prompt';

	const SUPPORT_URL = 'https://wpeventgenius.com/support/';

	/**
	 * Plugins that hard-block the RTEC handoff wizard.
	 *
	 * TEC Pro and free Event Tickets are supported by Event Genius V2 and are not listed.
	 * Event Tickets Plus is advisory only (see is_event_tickets_plus_active()).
	 *
	 * @var string[]
	 */
	private static $blocking_plugins = array(
		// Filter Bar.
		'the-events-calendar-filterbar/the-events-calendar-filter-view.php',
		// Community Events.
		'the-events-calendar-community-events/tribe-community-events.php',
		'events-community/events-community.php',
		// Virtual Events.
		'events-virtual/events-virtual.php',
		// Eventbrite Tickets.
		'the-events-calendar-eventbrite-tickets/tribe-eventbrite.php',
		'tribe-eventbrite/tribe-eventbrite.php',
		// Community Tickets.
		'events-community-tickets/events-community-tickets.php',
		'the-events-calendar-community-events-tickets/events-community-tickets.php',
		// Event Schedule Manager.
		'event-schedule-manager/event-schedule-manager.php',
	);

	/**
	 * @return bool
	 */
	public static function is_eligible() {
		return empty( self::get_reasons() );
	}

	/**
	 * Whether expensive disqualifying checks have already ruled out the migration notice.
	 *
	 * @return bool
	 */
	public static function is_migration_prompt_suppressed() {
		return false; // TODO: Remove this once the migration prompt is working again.
		$statuses = get_option( 'rtec_statuses', array() );
		if ( ! is_array( $statuses ) ) {
			return false;
		}

		return ! empty( $statuses[ self::STATUS_KEY_NO_MIGRATION_PROMPT ] );
	}

	/**
	 * Remember that this site should not see the migration notice (skips future expensive checks).
	 */
	public static function suppress_migration_prompt() {
		return; // TODO: Remove this once the migration prompt is working again.
		$statuses = get_option( 'rtec_statuses', array() );
		if ( ! is_array( $statuses ) ) {
			$statuses = array();
		}

		$statuses[ self::STATUS_KEY_NO_MIGRATION_PROMPT ] = true;
		update_option( 'rtec_statuses', $statuses, false );
	}

	/**
	 * Whether the site restricts registration to logged-in users (unsupported by EG migration).
	 *
	 * @return bool
	 */
	public static function has_logged_in_users_only_registration() {
		$options = get_option( 'rtec_options', array() );
		if ( isset( $options['only_logged_in'] ) && true === $options['only_logged_in'] ) {
			return true;
		}

		return self::has_events_restricted_to_logged_in_users();
	}

	/**
	 * Event Tickets Plus is not auto-imported; wizard may continue with a support warning.
	 *
	 * @return bool
	 */
	public static function is_event_tickets_plus_active() {
		return self::is_plugin_active( 'event-tickets-plus/event-tickets-plus.php' )
			|| class_exists( 'Tribe__Tickets_Plus__Main' );
	}

	/**
	 * Advisory reasons that do not block eligibility (shown in the wizard UI).
	 *
	 * @return string[]
	 */
	public static function get_advisory_reasons() {
		$reasons = array();

		if ( self::is_event_tickets_plus_active() ) {
			$reasons[] = 'event_tickets_plus_detected';
		}

		return $reasons;
	}

	/**
	 * Friendly scan counts for wizard UI.
	 *
	 * @return array<string, int>
	 */
	public static function get_counts() {
		global $wpdb;

		$table = $wpdb->prefix . RTEC_TABLENAME;
		$registrations = 0;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$registrations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		return array(
			'events'        => self::count_posts( 'tribe_events' ),
			'registrations' => $registrations,
			'venues'        => self::count_posts( 'tribe_venue' ),
			'organizers'    => self::count_posts( 'tribe_organizer' ),
			'categories'    => self::count_terms( 'tribe_events_cat' ),
			'tags'          => self::count_event_tags(),
		);
	}

	/**
	 * Machine-readable ineligibility reasons (empty when eligible).
	 *
	 * @return string[]
	 */
	public static function get_reasons() {
		$reasons = array();

		if ( is_multisite() ) {
			$reasons[] = 'multisite';
			return $reasons;
		}

		if ( ! self::is_tec_free_active() ) {
			$reasons[] = 'tec_missing';
		}

		if ( ! self::is_rtec_free_active() ) {
			$reasons[] = 'rtec_missing';
		}

		foreach ( self::$blocking_plugins as $plugin_file ) {
			if ( self::is_plugin_active( $plugin_file ) ) {
				$reasons[] = 'blocking_plugin:' . $plugin_file;
			}
		}

		return $reasons;
	}

	/**
	 * @return bool
	 */
	private static function is_tec_free_active() {
		return self::is_plugin_active( 'the-events-calendar/the-events-calendar.php' )
			|| class_exists( 'Tribe__Events__Main' );
	}

	/**
	 * @return bool
	 */
	private static function is_rtec_free_active() {
		return self::is_plugin_active( 'registrations-for-the-events-calendar/registrations-for-the-events-calendar.php' )
			|| defined( 'RTEC_VERSION' );
	}

	/**
	 * @param string $plugin_file Plugin basename.
	 * @return bool
	 */
	private static function is_plugin_active( $plugin_file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $plugin_file );
	}

	/**
	 * @param string $post_type Post type slug.
	 * @return int
	 */
	private static function count_posts( $post_type ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'auto-draft'",
				$post_type
			)
		);
	}

	/**
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	private static function count_terms( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}
		$count = wp_count_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		return is_wp_error( $count ) ? 0 : (int) $count;
	}

	/**
	 * @return bool
	 */
	private static function has_events_restricted_to_logged_in_users() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				AND p.post_type = %s
				AND p.post_status != 'auto-draft'
				LIMIT 1",
				'_RTECwhoCanRegister',
				'users',
				'tribe_events'
			)
		);

		return ! empty( $found );
	}

	/**
	 * Count post tags used by tribe_events posts.
	 *
	 * @return int
	 */
	private static function count_event_tags() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT tt.term_id)
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE p.post_type = %s AND p.post_status != 'auto-draft' AND tt.taxonomy = 'post_tag'",
				'tribe_events'
			)
		);
	}
}
