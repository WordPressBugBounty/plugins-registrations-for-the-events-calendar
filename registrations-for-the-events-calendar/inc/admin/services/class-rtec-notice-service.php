<?php

class RTEC_Notice_Service {

	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'rtec_admin_notices', array( $this, 'maybe_dashboard_notices' ) );
	}

	public function maybe_dashboard_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( $this->should_show_notice( 'welcome' ) ) {
			$this->welcome_dashboard_notice();
		} elseif ( $this->should_show_notice( 'bfcm' ) ) {
			$this->bfcm_dashboard_notice();
		}
	}

	public function should_show_notice( $notice_slug ) {
		$tec_data = RTEC_Admin::get_plugin_data( 'tribe-tec' );

		if ( ! $tec_data['is_active'] ) {
			return false;
		}
		global $rtec_options;

		if ( $notice_slug === 'welcome' ) {
			if ( isset( $rtec_options['default_max_registrations'] ) ) {
				return false;
			}

			if ( empty( $_GET['page'] ) || $_GET['page'] !== RTEC_MENU_SLUG ) { // phpcs:ignore
				return false;
			}

			return true;
		} elseif ( $notice_slug === 'bfcm' ) {
			if ( isset( $_GET['rtec_dismiss'] ) ) { // phpcs:ignore
				return false;
			}

			if ( ! isset( $rtec_options['default_max_registrations'] ) ) {
				return false;
			}

			$bfcm_dismiss_user_meta = get_user_meta( get_current_user_id(), 'rtec_dismiss_bfcm', true );

			if ( 'always' === $bfcm_dismiss_user_meta ) {
				return false;
			}

			if ( gmdate( 'Y', rtec_time() ) === (string) $bfcm_dismiss_user_meta ) {
				return false;
			}

			if ( ! rtec_is_bfcm_time_range() ) {
				return false;
			}

			return true;
		}

		return false;
	}

	public function help_dashboard_notice() {
		global $rtec_options;
		if ( isset( $rtec_options['default_max_registrations'] ) ) :
			$dismissed = get_transient( 'registrations_help_notice_dismiss' );
			if ( empty( $dismissed ) ) :
				?>
				<div id="rtec-help-notice" class="rtec-admin-notice-banner rtec-box-shadow rtec-standard-notice notice notice-info is-dismissible">
					<div class="rtec-img-wrap">
						<img src="<?php echo esc_url( RTEC_PLUGIN_URL . 'img/admin/icons/forum.svg' ); ?>" alt="forum icon">
					</div>
					<div class="rtec-msg-wrap">
						<h3><?php esc_html_e( 'Need Support?', 'registrations-for-the-events-calendar' ); ?></h3>
						<p><?php esc_html_e( 'Our team is happy to offer support to help you get the most out of the plugin! Please post in the WordPress.org forum if you have questions about anything.', 'registrations-for-the-events-calendar' ); ?></p>
						<div class="rtec-button-wrap">
							<a class="button button-primary rtec-cta" href="https://wordpress.org/support/plugin/registrations-for-the-events-calendar/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Go to the WordPress.org forum', 'registrations-for-the-events-calendar' ); ?></a>
							<a class="button rtec-secondary rtec-dismiss" href="#"><?php esc_html_e( 'Ask me later', 'registrations-for-the-events-calendar' ); ?></a>
						</div>
					</div>
				</div>
				<?php
			endif;
		endif;
	}

	/**
	 * Add notice if no settings saved
	 */
	function welcome_dashboard_notice() {
		?>
			<div id="rtec-welcome-notice-banner" class="rtec-admin-notice-banner rtec-box-shadow rtec-standard-notice notice notice-info is-dismissible">
				<div class="rtec-img-wrap">
					<img src="<?php echo esc_url( RTEC_PLUGIN_URL . 'img/RTEC-Logo-300.png' ); ?>" alt="Registrations for the Events Calendar">
				</div>
				<div class="rtec-msg-wrap">
					<h3><?php esc_html_e( 'Welcome! Let\'s Get Started', 'registrations-for-the-events-calendar' ); ?></h3>
					<p><?php esc_html_e( 'Registrations are automatically collected for all of your existing events. Make changes to how registrations are collected on the form settings page.', 'registrations-for-the-events-calendar' ); ?></p>
					<div class="rtec-button-wrap">
						<a class="button button-primary rtec-cta" href="<?php echo esc_url( admin_url( 'admin.php?page=registrations-for-the-events-calendar&tab=form' ) ); ?>"><?php esc_html_e( 'Go to form settings page', 'registrations-for-the-events-calendar' ); ?></a>
						<a class="button rtec-secondary" href="https://roundupwp.com/products/registrations-for-the-events-calendar/setup/?utm_campaign=rtec-free&utm_source=dashboard-notice&utm_medium=welcome&utm_content=SetupDirections" target="_blank" rel="noopener"><?php esc_html_e( 'Setup directions', 'registrations-for-the-events-calendar' ); ?></a>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Banner notice that might appear at the top of admin pages
	 *
	 * @since 2.7.7
	 */
	function bfcm_dashboard_notice() {
		?>
		<div id="rtec-announcement-banner" class="rtec-admin-notice-banner rtec-box-shadow rtec-standard-notice notice notice-info is-dismissible">
			<div class="rtec-img-wrap">
				<img src="<?php echo esc_url( RTEC_PLUGIN_URL . 'img/RU-Logo.png' ); ?>" alt="Registrations for the Events Calendar">
			</div>
			<div class="rtec-msg-wrap">
				<h3><?php esc_html_e( 'Happy Holidays! Save Up to 60% off Pro', 'registrations-for-the-events-calendar' ); ?></h3>
				<div><?php esc_html_e( 'For Black Friday and Cyber Monday, our users can purchase our Pro plugin and save up to 60%.', 'registrations-for-the-events-calendar' ); ?></div>
				<div class="rtec-button-wrap">
					<a class="button button-primary rtec-cta" href="<?php echo esc_url( add_query_arg( array( 'discount' => 'bfcm' ), 'https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=dashboard-notice&utm_medium=blackfriday&utm_content=ClaimDiscount' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Claim Discount', 'registrations-for-the-events-calendar' ); ?></a>
					<a id="rtec-banner-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'rtec_dismiss' => 'bfcm' ), admin_url( 'admin.php?page=registrations-for-the-events-calendar' ) ), 'rtec-dismiss', 'rtec_nonce' ) ); ?>" data-time="<?php echo esc_attr( gmdate( 'Y', rtec_time() ) ); ?>"><?php esc_html_e( 'No thanks', 'registrations-for-the-events-calendar' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	public function scripts_and_styles() {

		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore
			return;
		}

		if ( strpos( $_GET['page'], RTEC_MENU_SLUG ) === false // phpcs:ignore
		     && strpos( $_GET['page'], 'rtec' ) === false ) { // phpcs:ignore
			return;
		}

		wp_enqueue_script( 'rtec_admin_notice_scripts', trailingslashit( RTEC_PLUGIN_URL ) . 'js/rtec-admin-notices.js', array( 'jquery' ), RTEC_VERSION, true );
		wp_localize_script(
			'rtec_admin_notice_styles',
			'rtecAdminNoticeScript',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rtec_notice' ),
			)
		);
	}
}
