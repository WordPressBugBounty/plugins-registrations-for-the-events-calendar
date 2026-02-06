<?php

class RTEC_Notice_Service {


	public function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'rtec_admin_notices', array( $this, 'maybe_dashboard_notices' ) );

		add_action( 'admin_init', array( $this, 'prevent_redirect_to_guided_setup' ), 1 );
	}

	public function prevent_redirect_to_guided_setup() {
		// Exit early if we're on the plugins page
		global $pagenow;
		if ( isset( $pagenow ) && 'plugins.php' === $pagenow ) {
			return;
		}
		
		// Also check using get_current_screen() if available (more reliable)
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && 'plugins' === $screen->id ) {
				return;
			}
		}
		
		// Check if tribe() function exists
		if ( ! function_exists( 'tribe' ) ) {
			return;
		}

		// Check if the Controller class exists (using string to avoid fatal error if class doesn't exist)
		$controller_class = 'TEC\Events\Admin\Onboarding\Controller';
		if ( ! class_exists( $controller_class ) ) {
			return;
		}
		
		// Get the controller instance - tribe() will return null if not found
		$controller = tribe( $controller_class );
		
		// Only proceed if we have a valid controller
		if ( ! $controller ) {
			return;
		}
		
		// Check if the method exists on the controller object
		if ( ! method_exists( $controller, 'redirect_tec_pages_to_guided_setup' ) ) {
			return;
		}
		
		// Remove the redirect action hook
		remove_action( 'tec_admin_headers_about_to_be_sent', [ $controller, 'redirect_tec_pages_to_guided_setup' ] );
	}

	public function maybe_dashboard_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( $this->should_show_notice( 'welcome' ) ) {
			$this->dismiss_tec_onboarding_wizard();
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
			<div id="rtec-welcome-notice-banner" class="rtec-admin-notice-banner rtec-box-shadow rtec-standard-notice notice notice-info is-dismissible" style="margin-top: 20px">
				<div class="rtec-img-wrap">
					<img src="<?php echo esc_url( RTEC_PLUGIN_URL . 'img/RTEC-Logo-300.png' ); ?>" alt="Registrations for the Events Calendar">
				</div>
				<div class="rtec-msg-wrap">
					<h3><?php esc_html_e( 'Welcome! Let\'s Get Started', 'registrations-for-the-events-calendar' ); ?></h3>
					<p><?php esc_html_e( 'Registrations are automatically collected for all of your existing events. Make changes to how registrations are collected on the form settings page.', 'registrations-for-the-events-calendar' ); ?></p>
					<div class="rtec-button-wrap">
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tribe_events' ) ); ?>" class="button button-primary rtec-cta"><?php esc_html_e( 'Create an event', 'registrations-for-the-events-calendar' ); ?></a>
						<a class="button button-primary rtec-cta" href="<?php echo esc_url( admin_url( 'admin.php?page=registrations-for-the-events-calendar&tab=form' ) ); ?>"><?php esc_html_e( 'Registration form settings', 'registrations-for-the-events-calendar' ); ?></a>
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

	public function dismiss_tec_onboarding_wizard() {
		   // Disable The Events Calendar onboarding wizard and welcome screen
	    if ( function_exists( 'tribe_update_option' ) ) {
		    tribe_update_option( 'tec_events_onboarding_page_dismissed', true );
		    tribe_update_option( 'tec_onboarding_wizard_visited_guided_setup', true );
		    
		    // Mark wizard as finished to prevent it from showing (checked in should_show_wizard())
		    $wizard_data = get_option( 'tec_onboarding_wizard_data', [] );
		    if ( ! is_array( $wizard_data ) ) {
			    $wizard_data = [];
		    }
		    $wizard_data['finished'] = true;
					    $wizard_data['begun'] = true;

		    
		    // Use Data class if available, otherwise update option directly
		    if ( class_exists( 'TEC\Events\Admin\Onboarding\Data' ) && function_exists( 'tribe' ) ) {
			    try {
				    $data = tribe( 'TEC\Events\Admin\Onboarding\Data' );
				    if ( method_exists( $data, 'update_wizard_settings' ) ) {
					    $data->update_wizard_settings( $wizard_data );
				    } else {
					    update_option( 'tec_onboarding_wizard_data', $wizard_data );
				    }
			    } catch ( Exception $e ) {
				    update_option( 'tec_onboarding_wizard_data', $wizard_data );
			    }
		    } else {
			    update_option( 'tec_onboarding_wizard_data', $wizard_data );
		    }
	    } else {
		    update_option( 'tec_events_onboarding_page_dismissed', true );
		    update_option( 'tec_onboarding_wizard_visited_guided_setup', true );
		    
		    // Mark wizard as finished
		    $wizard_data = get_option( 'tec_onboarding_wizard_data', [] );
		    if ( ! is_array( $wizard_data ) ) {
			    $wizard_data = [];
		    }
		    $wizard_data['finished'] = true;
		    $wizard_data['begun'] = true;
		    update_option( 'tec_onboarding_wizard_data', $wizard_data );
	    }
	    
	    // Disable The Events Calendar welcome screen redirect
	    delete_transient( '_tribe_events_activation_redirect' );
	    if ( function_exists( 'tribe_update_option' ) ) {
		    tribe_update_option( 'tribe_skip_welcome', true );
	    } else {
		    update_option( 'tribe_skip_welcome', true );
	    }
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
