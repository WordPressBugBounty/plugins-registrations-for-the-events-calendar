<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * RTEC 3.0 "What's New" admin screen.
 *
 * Uses the rtec_statuses option when the screen is viewed so other UI
 * (for example dashboard notices) can stay in sync.
 *
 * @since 3.0
 */
class RTEC_Whats_New_3_0 {

	const PAGE_SLUG        = 'rtec-whats-new-3-0';
	const STATUS_KEY_SHOWN = 'whats_new_3_0_shown';

	/**
	 * Register hooks for the welcome screen.
	 */
	public static function init_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
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
	 * Mark the 3.0 "What's New" screen as shown so it does not reappear.
	 */
	protected static function mark_shown() {
		$statuses                                      = self::get_statuses();
		$statuses[ self::STATUS_KEY_SHOWN ]            = true;
		$statuses[ self::STATUS_KEY_SHOWN . '_time' ]  = time();
		update_option( 'rtec_statuses', $statuses, false );
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

