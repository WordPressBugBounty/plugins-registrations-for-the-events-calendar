<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Production migration wizard UI state (RTEC Steps 1–2).
 */
class RTEC_Migration_Wizard_State {

	const OPTION_NAME = 'rtec_migration_wizard_state';

	const KEY_USER_INTEREST = 'user_interest';

	const KEY_CURRENT_STEP = 'current_step';

	const KEY_PREFLIGHT_SNAPSHOT = 'preflight_snapshot';

	const KEY_EG_INSTALLED_VIA_WIZARD = 'eg_installed_via_wizard';

	/**
	 * @return array<string, mixed>
	 */
	public static function get_default_state() {
		return array(
			self::KEY_USER_INTEREST         => false,
			self::KEY_CURRENT_STEP          => 1,
			self::KEY_PREFLIGHT_SNAPSHOT    => array(),
			self::KEY_EG_INSTALLED_VIA_WIZARD => false,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_state() {
		$state = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $state ) ) {
			$state = array();
		}
		return array_merge( self::get_default_state(), $state );
	}

	/**
	 * @param string $key   self::KEY_* constant.
	 * @param mixed  $value Value.
	 */
	public static function set_state_key( $key, $value ) {
		$state         = self::get_state();
		$state[ $key ] = $value;
		update_option( self::OPTION_NAME, $state, false );
	}

	/**
	 * Whether the user has started the migration wizard (notice, support link, or submenu).
	 *
	 * @return bool
	 */
	public static function has_started() {
		$state = self::get_state();
		return ! empty( $state[ self::KEY_USER_INTEREST ] )
			|| ! empty( $state[ self::KEY_PREFLIGHT_SNAPSHOT ] );
	}

	/**
	 * Clear wizard progress so the site is treated as not having started migration.
	 */
	public static function reset_interest() {
		delete_option( self::OPTION_NAME );
	}
}
