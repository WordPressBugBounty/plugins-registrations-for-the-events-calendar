<?php
/**
 * Manage plugin settings and options
 *
 * @since 2.21
 */

class RTEC_Settings {

	/**
	 * Returns the raw setting value or the default value if none
	 * is set.
	 *
	 * @param string $key
	 *
	 * @return mixed|bool|array|string|null
	 *
	 * @since 2.21
	 */
	public static function get( $key ) {
		$rtec_options = get_option( 'rtec_options', array() );

		if ( ! is_array( $rtec_options ) ) {
			$rtec_options = array();
		}

		if ( isset( $rtec_options[ $key ] ) ) {
			return $rtec_options[ $key ];
		}

		return Rtec_Defaults::get( $key );
	}

}
