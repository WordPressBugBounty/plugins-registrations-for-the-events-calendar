<?php

class RTEC_Modal_Service {

	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'rtec_after_admin_wrap', array( $this, 'add_pro_feature_modal' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	public function add_pro_feature_modal() {
		include_once rtec_plugin_path( 'admin-templates/partials/modal.php' );
	}

	public function scripts_and_styles() {

		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore
			return;
		}

		if ( strpos( $_GET['page'], RTEC_MENU_SLUG ) === false // phpcs:ignore
			&& strpos( $_GET['page'], 'rtec' ) === false ) { // phpcs:ignore
			return;
		}

wp_enqueue_style( 'rtec_admin_modal_styles', rtec_plugin_url( 'assets/admin/css/rtec-admin-modal.css' ), array(), RTEC_VERSION );
			wp_enqueue_script( 'rtec_admin_modal_scripts', rtec_plugin_url( 'assets/admin/js/rtec-admin-modal.js' ), array( 'jquery' ), RTEC_VERSION, true );
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
