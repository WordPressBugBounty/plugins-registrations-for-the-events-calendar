<?php

class RTEC_Modal_Service {

	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'rtec_after_admin_wrap', array( $this, 'add_pro_feature_modal' ) );
		add_action( 'rtec_admin_modal_content', array( $this, 'pro_feature_modal_content' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	public function add_pro_feature_modal() {
		include_once RTEC_PLUGIN_DIR . 'inc/admin/templates/partials/modal.php';
	}

	public function pro_feature_modal_content() {
		$features = $this->pro_features();

		foreach ( $features as $feature ) {
			include RTEC_PLUGIN_DIR . 'inc/admin/templates/partials/modals/pro-features.php';

		}
	}
	public function pro_features() {
		$pro_features = array(
			array(
				'slug'        => 'confirm-selected',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/confirm-selected.png',
				'heading'     => __( 'Upgrade to Pro for Manual Confirmation', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Create a system for reviewing registrations before confirming them. Easily update the status of the registration and send the confirmation email from the WordPress dashboard.', 'registrations-for-the-events-calendar' ),
			),
			array(
				'slug'        => 'process-waiting-selected',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/process-waiting-selected.png',
				'heading'     => __( 'Upgrade to Pro for Waiting Lists', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Once capacity is reached, collect registrants for a waiting list. Automatically promote when someone cancels or transfer them to a new event.', 'registrations-for-the-events-calendar' ),
			),
			array(
				'slug'        => 'email-selected',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/email-selected.png',
				'heading'     => __( 'Upgrade to Pro for Manual Emailing', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Keep attendees informed of changes using emails sent using tools in your WordPress dashboard.', 'registrations-for-the-events-calendar' ),
			),
			array(
				'slug'        => 'transfer-selected',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/transfer-selected.png',
				'heading'     => __( 'Upgrade to Pro for Event Transfers and Copying', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Transfer or copy registrations from one event to a different event right from the WordPress dashboard.', 'registrations-for-the-events-calendar' ),
			),
			array(
				'slug'        => 'payments',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/payments.png',
				'heading'     => __( 'Upgrade to Pro for Event Payment Management', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Collect online PayPal payments for your events. Create variable costs for different registration types. Optional off-line payment management as well as Stripe and WooCommerce integrations.', 'registrations-for-the-events-calendar' ),
			),
			array(
				'slug'        => 'form-fields',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/form-fields.png',
				'heading'     => __( 'Upgrade to Pro for More Field Types', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Choose from number, dropdown select fields, multi-selection checkboxes, radio fields, date selections, file upload, paragraph text and more.', 'registrations-for-the-events-calendar' ),
			),
			array(
				'slug'        => 'message-history',
				'img'         => RTEC_PLUGIN_URL . 'img/admin/pro-features/message-history.png',
				'heading'     => __( 'Upgrade to Pro for Message History', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Track what email messages each registrant has received. Manually email updates or missing messages right from the WordPress dashboard.', 'registrations-for-the-events-calendar' ),
			),
		);

		return $pro_features;
	}


	public function scripts_and_styles() {

		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore
			return;
		}

		if ( strpos( $_GET['page'], RTEC_MENU_SLUG ) === false // phpcs:ignore
			&& strpos( $_GET['page'], 'rtec' ) === false ) { // phpcs:ignore
			return;
		}

		wp_enqueue_style( 'rtec_admin_modal_styles', trailingslashit( RTEC_PLUGIN_URL ) . 'css/rtec-admin-modal.css', array(), RTEC_VERSION );
		wp_enqueue_script( 'rtec_admin_modal_scripts', trailingslashit( RTEC_PLUGIN_URL ) . 'js/rtec-admin-modal.js', array( 'jquery' ), RTEC_VERSION, true );
		wp_localize_script(
			'rtec_admin_modal_scripts',
			'rtecAdminModalScript',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rtec_modal' ),
			)
		);
	}
}
