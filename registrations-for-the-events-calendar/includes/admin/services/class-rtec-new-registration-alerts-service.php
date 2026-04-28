<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Service for "new" registration alerts: count in menu/tab and "new" tag per row.
 * "New" = registrations created after the current admin last viewed the Registrations area
 * (All Registrations tab or a single event's registrations tab). Per-user, time-based; no per-registration "seen" flag.
 *
 * @since 1.0
 */
class RTEC_New_Registration_Alerts_Service {

	/**
	 * @var RTEC_New_Registration_Alerts_Service
	 */
	private static $instance;

	/**
	 * Get the service instance.
	 *
	 * @return RTEC_New_Registration_Alerts_Service
	 * @since 1.0
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks (AJAX handler for "dismiss notices", bubble on Tribe menu).
	 *
	 * @since 1.0
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_rtec_dismiss_new', array( $this, 'ajax_dismiss_new' ) );
		add_action( 'admin_menu', array( $this, 'add_bubble_to_tribe_menu' ), 999 );
	}

	/**
	 * Adds the new-registrations count bubble to the Events (Tribe) menu item when applicable.
	 *
	 * @since 1.0
	 */
	public function add_bubble_to_tribe_menu() {
		$count = $this->get_new_count();
		if ( $count <= 0 ) {
			return;
		}
		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $key => $value ) {
			if ( isset( $menu[ $key ][2] ) && $menu[ $key ][2] === RTEC_TRIBE_MENU_PAGE ) {
				$menu[ $key ][0] .= ' <span class="update-plugins rtec-notice-admin-reg-count count-' . (int) $count . '"><span class="plugin-count">' . (int) $count . '</span></span>';
				return;
			}
		}
	}

	/**
	 * AJAX handler: dismiss new notices by setting last-viewed to now for the current user.
	 *
	 * @since 1.0
	 */
	public function ajax_dismiss_new() {
		check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

		if ( ! current_user_can( rtec_get_capability() ) ) {
			wp_send_json_error();
		}

		$this->update_last_viewed();
		wp_send_json_success();
	}

	/**
	 * New registrations count = those created after the current user last viewed the Registrations area.
	 *
	 * @return int Count of registrations created after last view; 0 if never viewed or none.
	 * @since 1.0
	 */
	public function get_new_count() {
		$cache_key = '';
		$user_id   = get_current_user_id();
		if ( ! $user_id ) {
			return 0;
		}

		$last_viewed = get_user_meta( $user_id, RTEC_LAST_VIEWED_REGISTRATIONS_META_KEY, true );
		if ( empty( $last_viewed ) || ! is_numeric( $last_viewed ) ) {
			return 0;
		}

		$cache_key = 'rtec_new_registrations_' . $user_id . '_' . $last_viewed;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$db    = new RTEC_Db_Admin();
		$count = $db->count_registrations_after( (int) $last_viewed );
		set_transient( $cache_key, $count, 5 * MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Updates the current user's "last viewed registrations" timestamp to now.
	 * Call when the admin views the All Registrations page, a single event's registrations page,
	 * or when they click "dismiss notices" — so that the new-count and row "new" tags disappear.
	 *
	 * @since 1.0
	 */
	public function update_last_viewed() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$now = time();
		$previous = get_user_meta( $user_id, RTEC_LAST_VIEWED_REGISTRATIONS_META_KEY, true );
		update_user_meta( $user_id, RTEC_LAST_VIEWED_REGISTRATIONS_META_KEY, $now );
		// Invalidate cached count for the previous timestamp so the next read gets 0 immediately.
		if ( ! empty( $previous ) && is_numeric( $previous ) ) {
			delete_transient( 'rtec_new_registrations_' . $user_id . '_' . $previous );
		}
		// Invalidate frontend "Reviewed" cache so it picks up this view.
		delete_transient( 'rtec_max_admin_last_viewed_at' );
	}

	/**
	 * Whether a registration is "new" for the current user (created after their last view).
	 *
	 * @param array $registration Registration row with at least 'registration_date'.
	 * @return bool True if not new (seen), false if new.
	 * @since 1.0
	 */
	public function is_registration_new( $registration ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$last_viewed = get_user_meta( $user_id, RTEC_LAST_VIEWED_REGISTRATIONS_META_KEY, true );
		if ( empty( $last_viewed ) || ! is_numeric( $last_viewed ) ) {
			return false;
		}

		$reg_date = isset( $registration['registration_date'] ) ? strtotime( $registration['registration_date'] ) : 0;
		if ( ! $reg_date ) {
			return false;
		}

		return $reg_date > (int) $last_viewed;
	}
}
