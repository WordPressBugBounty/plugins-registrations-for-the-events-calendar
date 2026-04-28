<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Class RTEC_Validator
 */
class RTEC_Validator {

	/**
	 * @param $subject
	 * @param $min
	 * @param $max
	 *
	 * @return bool
	 */
	static public function length( $subject, $min, $max )
	{
		$working_max = $max;
		$working_min = $min;

		if ( $working_max === 'no-max' || $working_max > 1000 ) {
			$working_max = 1000;
		}

		if ( $working_min < 0 ) {
			$working_min = 0;
		}

		if ( strlen( $subject ) >= (int)$working_min && strlen( $subject ) <= (int)$working_max ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $subject
	 * @param $minval
	 * @param $maxval
	 *
	 * @return bool
	 */
	static public function numval( $subject, $minval, $maxval )
	{
		$working_max = $maxval;
		$working_min = $minval;

		if ( $working_max === 'no-max' || $working_max > 9999999 ) {
			$working_max = 9999999;
		}

		if ( $working_min === 'no-min' || $working_min < -9999999 ) {
			$working_min = -9999999;
		}

		if ( $subject >= (int)$working_min && $subject <= (int)$working_max ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $subject
	 *
	 * @return bool|string
	 */
	static public function email( $subject )
	{
		return is_email( $subject );
	}

	/**
	 * @param $subject
	 * @param $acceptable_counts
	 * @param string $count_what
	 *
	 * @return bool
	 */
	static public function count( $subject, $acceptable_counts, $count_what = 'numbers' )
	{
		$working_counts = $acceptable_counts;
		if ( $count_what === 'numbers') {
			$stripped_subject = preg_replace( '/\D/', '', $subject );

		} elseif ( $count_what === 'letters' ) {
			$stripped_subject = str_replace( "/^\p{L}+$/ui", '', $subject );
        } else {
			$stripped_subject = $subject;
		}

		if ( ! is_array( $working_counts ) ) {
			$working_counts = explode( ',', $working_counts );
		}

		foreach( $working_counts as $acceptable_count ) {

			if ( strlen( $stripped_subject ) === (int)$acceptable_count ) {
				return true;
			}

		}

		if ( $count_what === 'letters' ) return true;

		return false;
	}

	/**
	 * @param $first_val
	 * @param $second_val
	 * @param string $strictness
	 *
	 * @return bool
	 */
	static public function num_equality( $first_val, $second_val, $strictness = 'strict' )
	{
		if ( $strictness === 'strict' ) {
			return ( (int)$first_val === (int)$second_val );
		} else {
			return (int)$second_val > 0;
		}
	}

	/**
	 * Validates Google reCAPTCHA response token
	 *
	 * @param string $recaptcha_response The reCAPTCHA response token
	 * @param string $secret_key The reCAPTCHA secret key
	 * @return bool True if validation succeeds, false otherwise
	 */
	static public function google_recaptcha( $recaptcha_response, $secret_key )
	{
		// Validate input parameters
		if ( empty( $recaptcha_response ) || empty( $secret_key ) ) {
			return false;
		}

		// Get user's IP address for better verification
		$user_ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$user_ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		// Prepare request body
		$body = array(
			'secret'   => $secret_key,
			'response' => $recaptcha_response,
		);

		// Include IP address if available (recommended by Google)
		if ( ! empty( $user_ip ) ) {
			$body['remoteip'] = $user_ip;
		}

		// Make API request with timeout
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body'    => $body,
				'timeout' => 10,
			)
		);

		// Check for WP_Error (network failures, etc.)
		if ( is_wp_error( $response ) ) {
			// Log error for debugging (optional)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'RTEC reCAPTCHA verification failed: ' . $response->get_error_message() );
			}
			return false;
		}

		// Check response code
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'RTEC reCAPTCHA verification failed: HTTP ' . $response_code );
			}
			return false;
		}

		// Get response body
		$response_body = wp_remote_retrieve_body( $response );
		if ( empty( $response_body ) ) {
			return false;
		}

		// Decode JSON response
		$response_data = json_decode( $response_body, true );

		// Validate JSON was decoded successfully
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'RTEC reCAPTCHA verification failed: Invalid JSON response' );
			}
			return false;
		}

		// Check if verification was successful
		if ( isset( $response_data['success'] ) && $response_data['success'] === true ) {
			return true;
		}

		// Log error codes for debugging (optional)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $response_data['error-codes'] ) ) {
			error_log( 'RTEC reCAPTCHA verification failed: ' . implode( ', ', $response_data['error-codes'] ) );
		}

		return false;
	}

}