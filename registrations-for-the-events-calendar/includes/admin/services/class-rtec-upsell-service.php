<?php
/**
 * Serves upsell modal content via AJAX and provides the data contract for the upsell template.
 *
 * @package Registrations_For_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RTEC_Upsell_Service
 */
class RTEC_Upsell_Service {

	const AJAX_ACTION = 'rtec_get_upsell_modal';
	const BASE_UTM_URL = 'https://roundupwp.com/pricing/';

	/**
	 * Registers AJAX and hooks.
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_get_upsell_modal' ) );
	}

	/**
	 * AJAX handler: validates request, builds upsell data, renders template, returns JSON.
	 */
	public function ajax_get_upsell_modal() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'rtec_modal' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'registrations-for-the-events-calendar' ) ) );
		}

		$type     = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$location = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';

		if ( empty( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing type.', 'registrations-for-the-events-calendar' ) ) );
		}

		$upsell_data   = $this->get_upsell_data( $type, $location );
		$upsell_link   = $this->build_upsell_link( $type, $location );
		$discount_link = add_query_arg(
			array(
				'discount'     => 'bfcm',
				'utm_campaign' => 'rtec-free',
				'utm_source'   => 'pro-features-modal',
				'utm_medium'   => $type . ( $location ? '-' . $location : '' ),
				'utm_content'  => 'Get50%Off',
			),
			self::BASE_UTM_URL
		);

		ob_start();
		$this->render_upsell_modal( $upsell_data, $upsell_link, $discount_link );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Builds the primary CTA URL with UTM parameters.
	 *
	 * @param string $type     Upsell type (e.g. payments, form-fields).
	 * @param string $location Location identifier (e.g. settings-nav, single-event).
	 * @return string
	 */
	public function build_upsell_link( $type, $location ) {
		$params = array(
			'utm_campaign' => 'rtec-free',
			'utm_source'   => 'pro-features-modal',
			'utm_medium'   => $type . ( $location ? '-' . $location : '' ),
			'utm_content'  => 'UpgradeToPro',
		);
		return add_query_arg( $params, self::BASE_UTM_URL );
	}

	/**
	 * Returns upsell data for the given type and location (data contract for template).
	 *
	 * @param string $type     Upsell type.
	 * @param string $location Optional location.
	 * @return array{title: string, description: string, image?: string, image_2x?: string, features?: string[], additional_features?: string[]}
	 */
	public function get_upsell_data( $type, $location ) {
		$defaults = array(
			'title'               => __( 'Upgrade to Pro', 'registrations-for-the-events-calendar' ),
			'description'         => '',
			'image'               => '',
			'image_2x'            => '',
			'features'            => array(),
			'additional_features'  => $this->get_shared_additional_features(),
		);

		$by_type = $this->get_upsell_data_by_type( $type );
		return array_merge( $defaults, $by_type );
	}

	/**
	 * Shared "And much more!" feature list used across all upsell modals.
	 *
	 * @return string[]
	 */
	private function get_shared_additional_features() {
		return array(
			__( 'Unlimited Forms', 'registrations-for-the-events-calendar' ),
			__( 'Unlimited Email Templates', 'registrations-for-the-events-calendar' ),
			__( 'Collect Online Payments', 'registrations-for-the-events-calendar' ),
			__( 'Multiple Registration Types', 'registrations-for-the-events-calendar' ),
			__( 'Waiting Lists', 'registrations-for-the-events-calendar' ),
			__( 'Membership Features', 'registrations-for-the-events-calendar' ),
			__( 'All Form Field Types', 'registrations-for-the-events-calendar' ),
			__( 'Downloadable Reports', 'registrations-for-the-events-calendar' ),
			__( 'Multiple Guest Registration', 'registrations-for-the-events-calendar' ),
			__( 'Event Transfers & Copying', 'registrations-for-the-events-calendar' ),
			__( 'Manual Emailing', 'registrations-for-the-events-calendar' ),
			__( 'Check-In Attendees', 'registrations-for-the-events-calendar' ),
		);
	}

	/**
	 * Per-type upsell data (title, description, image, features).
	 *
	 * @param string $type Upsell type.
	 * @return array
	 */
	private function get_upsell_data_by_type( $type ) {
		$img_base = rtec_plugin_url( 'assets/images/admin/pro-features/' );
		$types    = array(
			'confirm-selected'         => array(
				'title'       => __( 'Manual Confirmation of Registrations', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Review and confirm registrations from the dashboard.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'confirm-selected.png',
				'features'    => array(
					__( 'Review registrations before confirming.', 'registrations-for-the-events-calendar' ),
					__( 'Update registration data and payment information manually.', 'registrations-for-the-events-calendar' ),
					__( 'Send confirmation and follow up emails as needed.', 'registrations-for-the-events-calendar' ),
				),
			),
			'process-waiting-selected' => array(
				'title'       => __( 'Waiting Lists', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Collect signups when full and promote or transfer when spots open.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'process-waiting-selected.png',
				'features'    => array(
					__( 'Collect waiting list signups when events are full.', 'registrations-for-the-events-calendar' ),
					__( 'Automatically promote when a spot opens.', 'registrations-for-the-events-calendar' ),
					__( 'Transfer waiting list guests to a new event.', 'registrations-for-the-events-calendar' ),
				),
			),
			'email-selected'          => array(
				'title'       => __( 'Manual Emailing', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Email registrants directly from the dashboard.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'email-selected.png',
				'features'    => array(
					__( 'Email registrants directly from the dashboard.', 'registrations-for-the-events-calendar' ),
					__( 'Send updates or reminders when needed.', 'registrations-for-the-events-calendar' ),
					__( 'Craft one-off emails or use templates.', 'registrations-for-the-events-calendar' ),
				),
			),
			'transfer-selected'        => array(
				'title'       => __( 'Event Transfers & Copying', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Transfer or copy registrations between events from the dashboard.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'transfer-selected.png',
				'features'    => array(
					__( 'Transfer registrations to another event.', 'registrations-for-the-events-calendar' ),
					__( 'Copy registrations to duplicate registrations.', 'registrations-for-the-events-calendar' ),
					__( 'Handle waiting list transfers easily.', 'registrations-for-the-events-calendar' ),
				),
			),
			'payments'                 => array(
				'title'       => __( 'Complete Payment Management', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Manage offline and online payments with complete control from the dashboard.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'payments.png',
				'features'    => array(
					__( 'Use PayPal, WooCommerce, Stripe and more.', 'registrations-for-the-events-calendar' ),
					__( 'Set variable prices by registration type.', 'registrations-for-the-events-calendar' ),
					__( 'Additional costs for form field responses.', 'registrations-for-the-events-calendar' ),
					__( 'Discounts and membership pricing.', 'registrations-for-the-events-calendar' ),
				),
			),
			'form-fields'              => array(
				'title'       => __( 'More Field Types', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Add dropdowns, checkboxes, date pickers, file uploads, and more.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'form-fields.png',
				'features'    => array(
					__( 'Number, dropdown, radio, and checkbox fields.', 'registrations-for-the-events-calendar' ),
					__( 'Date picker and file upload fields.', 'registrations-for-the-events-calendar' ),
					__( 'More settings and features for each field type.', 'registrations-for-the-events-calendar' ),
				),
			),
			'message-history'          => array(
				'title'       => __( 'Message History', 'registrations-for-the-events-calendar' ),
				'description' => __( 'See which emails each registrant received and resend when needed.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'message-history.png',
				'features'    => array(
					__( 'See which emails each registrant received.', 'registrations-for-the-events-calendar' ),
					__( 'Resend confirmation or reminder emails.', 'registrations-for-the-events-calendar' ),
					__( 'Keep communication consistent and auditable.', 'registrations-for-the-events-calendar' ),
				),
			),
			'scheduled-messages'       => array(
				'title'       => __( 'Scheduled Messages', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Schedule and automate reminders, follow-ups, and announcements for your events.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'scheduled-messages.png',
				'features'    => array(
					__( 'Schedule messages before and after your events.', 'registrations-for-the-events-calendar' ),
					__( 'Set defaults for all events.', 'registrations-for-the-events-calendar' ),
					__( 'Automate reminders so you do not have to send them manually.', 'registrations-for-the-events-calendar' ),
				),
			),
			'reports'                  => array(
				'title'       => __( 'Reports & Analytics', 'registrations-for-the-events-calendar' ),
				'description' => __( 'See registration performance and export reports across your events.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'reports.png',
				'features'    => array(
					__( 'View registration totals by event and date range.', 'registrations-for-the-events-calendar' ),
					__( 'Add filters and columns to fit your needs.', 'registrations-for-the-events-calendar' ),
					__( 'Export clean CSV reports to use in your own tools.', 'registrations-for-the-events-calendar' ),
				),
			),
			'attendance'               => array(
				'title'       => __( 'Attendance & Check-In', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Track who actually attends your events with an attendee check-in view.', 'registrations-for-the-events-calendar' ),
				'image'       => $img_base . 'attendance.png',
				'features'    => array(
					__( 'Mark attendees as checked in from any device.', 'registrations-for-the-events-calendar' ),
					__( 'See real-time attendance totals during your event.', 'registrations-for-the-events-calendar' ),
					__( 'Export attendance logs for reporting and compliance.', 'registrations-for-the-events-calendar' ),
				),
			),
		);

		return isset( $types[ $type ] ) ? $types[ $type ] : array();
	}

	/**
	 * Renders the upsell modal template (expects $upsell_data, $upsell_link, and $discount_link in scope).
	 *
	 * @param array  $upsell_data   Data contract array.
	 * @param string $upsell_link   Primary CTA URL.
	 * @param string $discount_link Discount CTA URL.
	 */
	public function render_upsell_modal( $upsell_data, $upsell_link, $discount_link ) {
		$logo_url = rtec_plugin_url( 'assets/images/RU-Logo.png' );
		include rtec_plugin_path( 'admin-templates/partials/modals/upsell.php' );
	}
}
