<?php
/**
 * After installing Event Genius from onboarding, optionally self-uninstall RTEC.
 *
 * @package RTEC
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Handles transient set on Event Genius install success and removes RTEC when safe.
 */
class RTEC_Evge_Install_Followup {

	const TRANSIENT = 'rtec_evge_install_pending_uninstall';

	/**
	 * Register hooks.
	 */
	public static function init_hooks() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_process' ), 0 );
	}

	/**
	 * Set after Event Genius is installed and activated from the onboarding AJAX flow.
	 */
	public static function flag_pending() {
		set_transient( self::TRANSIENT, 1, WEEK_IN_SECONDS );
	}

	/**
	 * If the transient is set, registrations table is empty, and user can delete plugins:
	 * turn off preserve-on-uninstall options, then deactivate and delete this plugin on shutdown.
	 */
	public static function maybe_process() {
		if ( ! get_transient( self::TRANSIENT ) ) {
			return;
		}

		if ( ! current_user_can( 'delete_plugins' ) ) {
			return;
		}

		$evge = RTEC_Admin::get_plugin_data( 'event-genius' );
		if ( empty( $evge['is_active'] ) ) {
			return;
		}

		if ( class_exists( 'Registrations_For_The_Events_Calendar_Pro', false ) ) {
			delete_transient( self::TRANSIENT );
			return;
		}

		if ( ! self::registrations_table_is_empty() ) {
			delete_transient( self::TRANSIENT );
			return;
		}

		$options = get_option( 'rtec_options', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options['preserve_registrations'] = false;
		$options['preserve_settings']      = false;
		$options['preserve_db']            = false;
		update_option( 'rtec_options', $options );

		add_action( 'shutdown', array( __CLASS__, 'deactivate_and_delete' ), 0 );
	}

	/**
	 * True when the registrations custom table has zero rows or does not exist.
	 *
	 * @return bool
	 */
	private static function registrations_table_is_empty() {
		global $wpdb;

		if ( ! defined( 'RTEC_TABLENAME' ) ) {
			return false;
		}

		$table = $wpdb->prefix . RTEC_TABLENAME;

		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			return true;
		}

		$table_sql = esc_sql( $table );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_sql}`" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return 0 === $count;
	}

	/**
	 * Deactivate and delete the plugin (runs uninstall.php via core).
	 */
	public static function deactivate_and_delete() {
		if ( ! get_transient( self::TRANSIENT ) ) {
			return;
		}

		if ( ! defined( 'RTEC_PLUGIN_BASENAME' ) || ! current_user_can( 'delete_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! function_exists( 'delete_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		deactivate_plugins( RTEC_PLUGIN_BASENAME, true );

		$result = delete_plugins( array( RTEC_PLUGIN_BASENAME ) );

		if ( ! is_wp_error( $result ) && $result ) {
			delete_transient( self::TRANSIENT );
		}
	}
}
