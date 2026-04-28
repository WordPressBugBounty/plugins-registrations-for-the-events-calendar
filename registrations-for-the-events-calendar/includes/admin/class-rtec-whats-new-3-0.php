<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * RTEC 3.0 "What's New" admin screen and post-update redirect.
 *
 * This uses a small version check plus the existing rtec_statuses
 * option so it can be extended for future major releases.
 *
 * @since 3.0
 */
class RTEC_Whats_New_3_0 {

	const PAGE_SLUG          = 'rtec-whats-new-3-0';
	const VERSION_TRIGGER    = '3.0.0';
	const OPTION_VERSION_KEY = 'rtec_plugin_version';
	const TRANSIENT_REDIRECT = 'rtec_update_3_0_redirect';
	const STATUS_KEY_SHOWN   = 'whats_new_3_0_shown';

	/**
	 * Register hooks for the welcome screen and redirect.
	 */
	public static function init_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_set_redirect_flag' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_to_page' ), 20 );
	}

	/**
	 * Whether this is the Pro version.
	 *
	 * Pro should define RTEC_IS_PRO as true; Lite will not.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return defined( 'RTEC_IS_PRO' ) && RTEC_IS_PRO;
	}

	/**
	 * Get the rtec_statuses array, always as an array.
	 *
	 * @return array
	 */
	protected static function get_statuses() {
		$statuses = get_option( 'rtec_statuses', array() );
		if ( ! is_array( $statuses ) ) {
			$statuses = array();
		}

		return $statuses;
	}

	/**
	 * Whether the 3.0 "What's New" screen has been recorded as shown.
	 *
	 * @return bool
	 */
	protected static function has_been_shown() {
		$statuses = self::get_statuses();

		return ! empty( $statuses[ self::STATUS_KEY_SHOWN ] );
	}

	/**
	 * Mark the 3.0 "What's New" screen as shown so it does not reappear.
	 */
	protected static function mark_shown() {
		$statuses                                      = self::get_statuses();
		$statuses[ self::STATUS_KEY_SHOWN ]            = true;
		$statuses[ self::STATUS_KEY_SHOWN . '_time' ]  = time();
		update_option( 'rtec_statuses', $statuses, false );
	}

	/**
	 * Set a short-lived transient after updating to 3.0 so we can redirect once.
	 *
	 * This uses an option to remember the last seen plugin version and a clearly
	 * named VERSION_TRIGGER for 3.0.0 so future major releases can follow
	 * the same pattern with new constants.
	 */
	public static function maybe_set_redirect_flag() {
		if ( ! is_admin() ) {
			return;
		}

		// Only proceed for users who can manage settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Avoid setting the redirect during AJAX, cron, CLI, or network admin.
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
			|| ( defined( 'WP_CLI' ) && WP_CLI )
			|| is_network_admin()
		) {
			return;
		}

		// Do not schedule the 3.0 "What's New" redirect on first-time installs.
		// New users should see the onboarding wizard and checklist instead.
		$db_ver = get_option( 'rtec_db_version', 0 );
		if ( (int) $db_ver === 0 ) {
			return;
		}

		// Do not change anything if the welcome screen has already been shown.
		if ( self::has_been_shown() ) {
			return;
		}

		$current_version = defined( 'RTEC_VERSION' ) ? RTEC_VERSION : '';
		if ( '' === $current_version ) {
			return;
		}

		$stored_version = get_option( self::OPTION_VERSION_KEY, '' );

		// If we have already processed this version (or newer), stop here.
		if ( '' !== $stored_version && version_compare( $stored_version, $current_version, '>=' ) ) {
			return;
		}

		// Update the stored version first so this logic is idempotent.
		update_option( self::OPTION_VERSION_KEY, $current_version, false );

		// Only trigger the redirect when moving onto 3.0.0 or higher.
		if ( version_compare( $current_version, self::VERSION_TRIGGER, '>=' ) ) {
			// Short lifetime: if not used on the next admin load, it will naturally expire.
			set_transient( self::TRANSIENT_REDIRECT, 'yes', MINUTE_IN_SECONDS * 30 );
		}
	}

	/**
	 * Perform a safe post-update redirect into the 3.0 welcome screen.
	 *
	 * Mirrors core patterns used for activation redirects while also ensuring
	 * we do not redirect during bulk updates, cron, AJAX, CLI, or network admin.
	 */
	public static function maybe_redirect_to_page() {
		// Only redirect if a redirect flag exists.
		if ( ! get_transient( self::TRANSIENT_REDIRECT ) ) {
			return;
		}

		// Never redirect on bulk updates, AJAX, cron, CLI, or in network admin.
		if ( is_network_admin()
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
			|| ( defined( 'WP_CLI' ) && WP_CLI )
		) {
			delete_transient( self::TRANSIENT_REDIRECT );
			return;
		}

		// Only redirect in admin for users with manage_options.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			delete_transient( self::TRANSIENT_REDIRECT );
			return;
		}

		// Do not redirect away from plugin update screens or bulk update flows.
		global $pagenow;
		if ( 'update-core.php' === $pagenow || 'update.php' === $pagenow ) {
			delete_transient( self::TRANSIENT_REDIRECT );
			return;
		}

		// Plugins list bulk actions such as "Update" or "Activate".
		if ( 'plugins.php' === $pagenow ) {
			$action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action2 = isset( $_GET['action2'] ) ? sanitize_text_field( wp_unslash( $_GET['action2'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( isset( $_GET['activate-multi'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				|| 'update-selected' === $action
				|| 'update-selected' === $action2
			) {
				delete_transient( self::TRANSIENT_REDIRECT );
				return;
			}
		}

		// If we are already on the welcome page, just clear the transient.
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG === $current_page ) {
			delete_transient( self::TRANSIENT_REDIRECT );
			return;
		}

		delete_transient( self::TRANSIENT_REDIRECT );

		// Final guard: never redirect if headers have already been sent.
		if ( headers_sent() ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Register the 3.0 "What's New" admin page.
	 *
	 * Uses the existing admin page system (under the Registrations menu).
	 */
	public static function register_page() {
		add_submenu_page(
			RTEC_MENU_SLUG,
			__( "What's New in RTEC 3.0", 'registrations-for-the-events-calendar' ),
			__( "What's New", 'registrations-for-the-events-calendar' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the 3.0 "What's New" page and mark it as shown.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::mark_shown();

		$is_pro               = self::is_pro();
		$primary_cta_action   = self::get_primary_event_cta_action();
		$primary_cta_label    = isset( $primary_cta_action['label'] ) ? $primary_cta_action['label'] : __( 'Create an event', 'registrations-for-the-events-calendar' );
		$primary_cta_url      = isset( $primary_cta_action['url'] ) ? $primary_cta_action['url'] : admin_url( 'post-new.php?post_type=tribe_events' );
		$primary_cta_external = ! empty( $primary_cta_action['external'] );

		include rtec_plugin_path( 'admin-templates/whats-new-3-0.php' );
	}

	/**
	 * Determine the primary "What's New" action:
	 * - View the next upcoming event with registrations enabled, or
	 * - Create an event if none are available.
	 *
	 * @return array{label:string,url:string,external:bool}
	 */
	protected static function get_primary_event_cta_action() {
		$event_url = self::get_upcoming_event_with_registrations_url();
		if ( ! empty( $event_url ) ) {
			return array(
				'label'    => __( 'View upcoming event', 'registrations-for-the-events-calendar' ),
				'url'      => $event_url,
				'external' => true,
			);
		}

		return array(
			'label'    => __( 'Create an event', 'registrations-for-the-events-calendar' ),
			'url'      => admin_url( 'post-new.php?post_type=tribe_events' ),
			'external' => false,
		);
	}

	/**
	 * Return URL for next upcoming event where RTEC registrations are enabled.
	 *
	 * @return string
	 */
	protected static function get_upcoming_event_with_registrations_url() {
		if ( ! function_exists( 'rtec_get_events' ) ) {
			return '';
		}

		$events = rtec_get_events(
			array(
				'posts_per_page' => 20,
				'start_date'     => gmdate( 'Y-m-d H:i:s' ),
				'orderby'        => 'event_date',
				'order'          => 'ASC',
			),
			true
		);

		if ( empty( $events ) || ! is_array( $events ) ) {
			return '';
		}

		foreach ( $events as $event ) {
			$event_id = isset( $event->ID ) ? (int) $event->ID : ( isset( $event->id ) ? (int) $event->id : 0 );
			if ( ! $event_id ) {
				continue;
			}

			$registrations_disabled = get_post_meta( $event_id, '_RTECregistrationsDisabled', true );
			if ( (string) $registrations_disabled === '1' ) {
				continue;
			}

			$event_url = get_permalink( $event_id );
			if ( ! empty( $event_url ) ) {
				return $event_url . '#rtec';
			}
		}

		return '';
	}
}

