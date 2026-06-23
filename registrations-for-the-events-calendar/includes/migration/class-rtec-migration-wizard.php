<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Production migration wizard (RTEC Steps 1–2).
 */
class RTEC_Migration_Wizard {

	const PAGE_SLUG = 'rtec-migration-wizard';

	const TOTAL_STEPS = 4;

	const DISMISS_USER_META = 'rtec_migration_wizard_dashboard_notice_dismissed';

	const DISMISS_NONCE_ACTION = 'rtec-dismiss-migration-wizard-notice';

	const RESET_INTEREST_NONCE_ACTION = 'rtec_reset_migration_interest';

	/** Query arg EG reads when redirecting from RTEC Step 2. */
	const EG_HANDOFF_QUERY_ARG = 'started_from_rtec';

	/** @var self|null */
	private static $instance;

	/**
	 * EG migration wizard URL with handoff query arg.
	 *
	 * @param int $step EG wizard step (default 3).
	 * @return string
	 */
	public static function get_eg_handoff_url( $step = 3 ) {
		return add_query_arg(
			array(
				'page'                    => 'evge-migration-wizard',
				'step'                    => (int) $step,
				self::EG_HANDOFF_QUERY_ARG => '1',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 11 );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_dashboard_notice' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_reset_migration_interest' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_redirect_away' ) );
		add_action( 'rtec_admin_notices', array( $this, 'maybe_render_dashboard_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_rtec_migration_wizard_install_eg', array( $this, 'ajax_install_event_genius' ) );
	}

	/**
	 * Whether the current user can access Event Genius migration UI and actions.
	 *
	 * @return bool
	 */
	public static function current_user_can_access() {
		return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
	}

	/**
	 * Whether the wizard admin page should be registered (Support tab and dashboard notice links).
	 *
	 * @return bool
	 */
	public function should_register_page() {
		if ( ! self::current_user_can_access() ) {
			return false;
		}
		return RTEC_Migration_Eligibility::is_eligible();
	}

	/**
	 * Whether the wizard appears in the RTEC submenu.
	 *
	 * @return bool
	 */
	public function should_register_menu() {
		if ( ! $this->should_register_page() ) {
			return false;
		}
		return RTEC_Migration_Wizard_State::has_started();
	}

	/**
	 * @return string
	 */
	public function get_submenu_label() {
		$eg_data = RTEC_Admin::get_plugin_data( 'event-genius' );
		if ( ! empty( $eg_data['is_active'] ) ) {
			return __( 'Event Genius Migration', 'registrations-for-the-events-calendar' );
		}
		return __( 'Move to Event Genius', 'registrations-for-the-events-calendar' );
	}

	public function register_page() {
		if ( ! $this->should_register_page() ) {
			return;
		}

		// Hidden pages must use a null parent. remove_submenu_page() breaks direct URL access.
		$parent_slug = $this->should_register_menu() ? RTEC_MENU_SLUG : null;

		add_submenu_page(
			$parent_slug,
			__( 'Event Genius Migration', 'registrations-for-the-events-calendar' ),
			$this->get_submenu_label(),
			'install_plugins',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function maybe_redirect_away() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) {
			return;
		}
		if ( ! self::current_user_can_access() ) {
			return;
		}
		$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
		$step = min( 2, max( 1, $step ) );
		if ( $step === 2 && ! $this->is_step1_complete() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=1' ) );
			exit;
		}
	}

	/**
	 * @return bool
	 */
	private function is_step1_complete() {
		$state = RTEC_Migration_Wizard_State::get_state();
		return ! empty( $state[ RTEC_Migration_Wizard_State::KEY_PREFLIGHT_SNAPSHOT ] );
	}

	/**
	 * @return bool
	 */
	private function is_dashboard_notice_dismissed() {
		return (bool) get_user_meta( get_current_user_id(), self::DISMISS_USER_META, true );
	}

	public function maybe_reset_migration_interest() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['rtec_reset_migration_interest'] ) || '1' !== (string) wp_unslash( $_GET['rtec_reset_migration_interest'] ) ) {
			return;
		}
		if ( ! self::current_user_can_access() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::RESET_INTEREST_NONCE_ACTION ) ) {
			return;
		}
		if ( ! RTEC_Migration_Wizard_State::has_started() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=rtec-settings&tab=support' ) );
			exit;
		}

		RTEC_Migration_Wizard_State::reset_interest();

		wp_safe_redirect(
			add_query_arg(
				'rtec_migration_interest_reset',
				'1',
				remove_query_arg(
					array( 'rtec_reset_migration_interest', '_wpnonce' ),
					admin_url( 'admin.php?page=rtec-settings&tab=support' )
				)
			)
		);
		exit;
	}

	public function maybe_dismiss_dashboard_notice() {
		if ( ! self::current_user_can_access() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['rtec_dismiss_migration_notice'] ) || '1' !== (string) wp_unslash( $_GET['rtec_dismiss_migration_notice'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::DISMISS_NONCE_ACTION ) ) {
			return;
		}
		update_user_meta( get_current_user_id(), self::DISMISS_USER_META, true );

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : RTEC_MENU_SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$redirect_args = array( 'page' => $page );
		if ( '' !== $tab ) {
			$redirect_args['tab'] = $tab;
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function maybe_render_dashboard_notice() {
		if ( ! self::current_user_can_access() ) {
			return;
		}
		if ( $this->is_dashboard_notice_dismissed() ) {
			return;
		}
		if ( RTEC_Migration_Eligibility::is_migration_prompt_suppressed() ) {
			return;
		}
		if ( ! RTEC_Migration_Eligibility::is_eligible() ) {
			RTEC_Migration_Eligibility::suppress_migration_prompt();
			return;
		}
		if ( ! RTEC_Migration_TEC_Tenure::was_installed_before_march_2026() ) {
			RTEC_Migration_Eligibility::suppress_migration_prompt();
			return;
		}
		if ( RTEC_Migration_Eligibility::has_logged_in_users_only_registration() ) {
			RTEC_Migration_Eligibility::suppress_migration_prompt();
			return;
		}
		$this->render_dashboard_notice();
	}

	public function render_dashboard_notice() {
		$wizard_url  = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=1' );
		$learn_more  = 'https://wpeventgenius.com/migrate-to-event-genius/?utm_campaign=rtec-free&utm_source=migration-notice&utm_medium=learn-more';
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : RTEC_MENU_SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$dismiss_url  = wp_nonce_url(
			add_query_arg(
				array(
					'rtec_dismiss_migration_notice' => '1',
					'page'                            => $current_page,
					'tab'                             => $tab,
				),
				admin_url( 'admin.php' )
			),
			self::DISMISS_NONCE_ACTION
		);
		?>
		<div class="rtec-notice rtec-complex-notice rtec-box-shadow rtec-migration-dashboard-notice">
			<div class="rtec-img-wrap">
				<img
					src="<?php echo esc_url( rtec_plugin_url( 'assets/images/event-genius-icon.png' ) ); ?>"
					alt="<?php esc_attr_e( 'Event Genius', 'registrations-for-the-events-calendar' ); ?>"
				/>
			</div>
			<div class="rtec-msg-wrap">
				<h3 class="rtec-migration-dashboard-notice-title">
					<?php esc_html_e( 'Try Event Genius, an all-in-one event management solution', 'registrations-for-the-events-calendar' ); ?>
				</h3>
				<p><?php esc_html_e( 'Event Genius combines events, calendars, registrations, venues, organizers, and attendee management into a single plugin. No separate event calendar plugin is required.', 'registrations-for-the-events-calendar' ); ?></p>
				<p><?php esc_html_e( 'Built by the same developer as Registrations for The Events Calendar, Event Genius can automatically import your existing events, registrations, venues, and organizers.', 'registrations-for-the-events-calendar' ); ?></p>
				<p><?php esc_html_e( 'Your current calendar remains active during migration, allowing you to review imported events before making any changes.', 'registrations-for-the-events-calendar' ); ?></p>
				<p class="rtec-migration-dashboard-notice-ctas">
					<a class="button button-primary rtec-cta" href="<?php echo esc_url( $wizard_url ); ?>"><?php esc_html_e( 'Move to Event Genius', 'registrations-for-the-events-calendar' ); ?></a>
					<a class="button rtec-secondary" href="<?php echo esc_url( $learn_more ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'registrations-for-the-events-calendar' ); ?></a>
					<a class="button rtec-secondary rtec-dismiss" href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'registrations-for-the-events-calendar' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	public function render_page() {
		if ( ! self::current_user_can_access() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'registrations-for-the-events-calendar' ) );
		}
		if ( ! RTEC_Migration_Eligibility::is_eligible() ) {
			echo '<div class="wrap"><p>';
			esc_html_e( 'The migration wizard is not available for this site.', 'registrations-for-the-events-calendar' );
			echo '</p></div>';
			return;
		}

		RTEC_Migration_Wizard_State::set_state_key( RTEC_Migration_Wizard_State::KEY_USER_INTEREST, true );

		$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step = min( 2, max( 1, $step ) );

		$counts = RTEC_Migration_Eligibility::get_counts();
		if ( 1 === $step ) {
			RTEC_Migration_Wizard_State::set_state_key( RTEC_Migration_Wizard_State::KEY_CURRENT_STEP, 1 );
			RTEC_Migration_Wizard_State::set_state_key( RTEC_Migration_Wizard_State::KEY_PREFLIGHT_SNAPSHOT, $counts );
		}

		$eg_data     = RTEC_Admin::get_plugin_data( 'event-genius' );
		$logo_url    = rtec_plugin_url( 'assets/images/RU-Logo-150.png' );
		$page_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$total_steps = self::TOTAL_STEPS;
		$progress    = $step;
		$scan_counts = $counts;

		include rtec_plugin_path( 'admin-templates/migration-wizard.php' );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( (string) $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( 'rtec_admin_styles', rtec_plugin_url( 'assets/admin/css/rtec-admin-styles.css' ), array(), RTEC_VERSION );
		wp_enqueue_style(
			'rtec-wizard-common',
			rtec_plugin_url( 'assets/admin/css/rtec-wizard-common.css' ),
			array( 'rtec_admin_styles' ),
			RTEC_VERSION
		);
		wp_enqueue_style(
			'rtec-migration-wizard',
			rtec_plugin_url( 'assets/admin/css/rtec-migration-wizard.css' ),
			array( 'rtec-wizard-common' ),
			RTEC_VERSION
		);
		wp_enqueue_script(
			'rtec-wizard-common',
			rtec_plugin_url( 'assets/admin/js/rtec-wizard-common.js' ),
			array( 'jquery' ),
			RTEC_VERSION,
			true
		);
		wp_enqueue_script(
			'rtec-migration-wizard',
			rtec_plugin_url( 'assets/admin/js/rtec-migration-wizard.js' ),
			array( 'jquery', 'rtec-wizard-common' ),
			RTEC_VERSION,
			true
		);

		$eg_data = RTEC_Admin::get_plugin_data( 'event-genius' );
		wp_localize_script(
			'rtec-migration-wizard',
			'rtecMigrationWizard',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'rtec_migration_wizard' ),
				'rtec_nonce'        => wp_create_nonce( 'rtec_nonce' ),
				'page_url'          => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'eg_wizard_url'     => self::get_eg_handoff_url( 3 ),
				'eg_installed'      => ! empty( $eg_data['is_installed'] ),
				'eg_active'         => ! empty( $eg_data['is_active'] ),
				'strings'           => array(
					'installing'       => __( 'Installing…', 'registrations-for-the-events-calendar' ),
					'activating'       => __( 'Activating…', 'registrations-for-the-events-calendar' ),
					'continuing'       => __( 'Continuing to Event Genius…', 'registrations-for-the-events-calendar' ),
					'success'          => __( 'Success! Continuing to Event Genius…', 'registrations-for-the-events-calendar' ),
					'error'            => __( 'Something went wrong. Please try again or install from the Plugins page.', 'registrations-for-the-events-calendar' ),
					'thanks_patience'  => __( 'This may take a minute or two. Thanks for your patience.', 'registrations-for-the-events-calendar' ),
				),
			)
		);
	}

	/**
	 * Install/activate Event Genius and hand off to EG wizard Step 3.
	 */
	public function ajax_install_event_genius() {
		check_ajax_referer( 'rtec_migration_wizard', 'nonce' );
		if ( ! self::current_user_can_access() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'registrations-for-the-events-calendar' ) ) );
		}
		if ( ! RTEC_Migration_Eligibility::is_eligible() ) {
			wp_send_json_error( array( 'message' => __( 'This site is not eligible for migration.', 'registrations-for-the-events-calendar' ) ) );
		}

		$eg_data = RTEC_Admin::get_plugin_data( 'event-genius' );

		if ( ! empty( $eg_data['is_active'] ) ) {
			$this->write_handoff_state();
			wp_send_json_success( array(
				'redirect' => self::get_eg_handoff_url( 3 ),
			) );
		}

		$admin  = new RTEC_Admin();
		$result = $admin->install( 'event-genius', '', true );

		if ( empty( $result['success'] ) ) {
			$message = ! empty( $result['msg'] ) ? $result['msg'] : __( 'Install failed.', 'registrations-for-the-events-calendar' );
			wp_send_json_error( array( 'message' => $message ) );
		}

		$this->write_handoff_state();
		wp_send_json_success( array(
			'redirect' => self::get_eg_handoff_url( 3 ),
		) );
	}

	/**
	 * Write cross-plugin handoff state and RTEC wizard state.
	 */
	private function write_handoff_state() {
		$counts = RTEC_Migration_Eligibility::get_counts();

		RTEC_Migration_Wizard_State::set_state_key( RTEC_Migration_Wizard_State::KEY_CURRENT_STEP, 2 );
		RTEC_Migration_Wizard_State::set_state_key( RTEC_Migration_Wizard_State::KEY_EG_INSTALLED_VIA_WIZARD, true );
		RTEC_Migration_Wizard_State::set_state_key( RTEC_Migration_Wizard_State::KEY_PREFLIGHT_SNAPSHOT, $counts );

		update_option(
			'evge_migration_wizard_state',
			array(
				'started_from_rtec' => true,
				'current_step'      => 3,
				'wizard_completed'  => false,
				'completed_at'      => '',
				'calendar_slug'     => '',
				'summary_counts'    => array(),
			),
			false
		);
	}
}
