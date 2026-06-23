<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Onboarding wizard and setup flow for RTEC.
 * Runs once on first activation; can be relaunched manually.
 *
 * @since 3.0
 */
class RTEC_Onboarding {

	const PAGE_SLUG = 'rtec-onboarding';

	/** Single option storing all onboarding and checklist state. */
	const OPTION_NAME = 'rtec_onboarding_state';

	/** Keys in the state array. */
	const KEY_WIZARD_COMPLETED    = 'wizard_completed';
	const KEY_CHECKLIST_DISMISSED = 'checklist_dismissed';
	const KEY_FORM_FIELDS_DONE    = 'form_fields_done';
	const KEY_EMAIL_DONE         = 'email_done';
	const KEY_EMAIL_DELIVERY_DONE = 'email_delivery_done';
	/** True after user saw step 1 while TEC was inactive (install/activate). Used to skip redundant step 1 after they activate TEC. */
	const KEY_TEC_ACTIVATION_PROMPTED = 'tec_activation_prompted';
	/** User dismissed the Event Genius vs RTEC interstitial (main admin or onboarding step 1). */
	const KEY_EVGE_CONTEXT_DISMISSED = 'evge_context_dismissed';

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Default values for the onboarding state array.
	 *
	 * @return array<string, bool>
	 */
	public static function get_default_state() {
		return array(
			self::KEY_WIZARD_COMPLETED    => false,
			self::KEY_CHECKLIST_DISMISSED => false,
			self::KEY_FORM_FIELDS_DONE    => false,
			self::KEY_EMAIL_DONE          => false,
			self::KEY_EMAIL_DELIVERY_DONE     => false,
			self::KEY_TEC_ACTIVATION_PROMPTED => false,
			self::KEY_EVGE_CONTEXT_DISMISSED  => false,
		);
	}

	/**
	 * Get onboarding/checklist state.
	 *
	 * @return array<string, bool>
	 */
	public static function get_state() {
		$state = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $state ) ) {
			$state = array();
		}
		return array_merge( self::get_default_state(), $state );
	}

	/**
	 * Whether to show the Event Genius context screen instead of the TEC install/activate prompt.
	 * True when Event Genius is active, The Events Calendar is not, and the user has not dismissed the message.
	 *
	 * @return bool
	 */
	public static function is_evge_context_interstitial_active() {
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			return false;
		}
		$evge = RTEC_Admin::get_plugin_data( 'event-genius' );
		if ( empty( $evge['is_active'] ) ) {
			return false;
		}
		$state = self::get_state();
		return empty( $state[ self::KEY_EVGE_CONTEXT_DISMISSED ] );
	}

	/**
	 * Whether to suppress the WP admin footer string and the RTEC rating footer swap.
	 * Applies on the onboarding wizard, the Event Genius vs RTEC screen, and while the setup checklist is active.
	 *
	 * @return bool
	 */
	public static function should_suppress_setup_footer_chrome() {
		if ( ! is_admin() ) {
			return false;
		}
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page === self::PAGE_SLUG ) {
			return true;
		}
		$is_rtec_admin = ( strpos( $page, 'registrations-for-the-events-calendar' ) === 0 || strpos( $page, 'rtec-' ) === 0 );
		if ( ! $is_rtec_admin || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$state = self::get_state();
		if ( empty( $state[ self::KEY_WIZARD_COMPLETED ] ) ) {
			return true;
		}
		if ( ! empty( $state['checklist_initiated'] ) && empty( $state[ self::KEY_CHECKLIST_DISMISSED ] ) ) {
			return true;
		}
		if ( self::is_evge_context_interstitial_active() ) {
			return true;
		}
		return false;
	}

	/**
	 * Nonce URL: dismiss EVGE interstitial and return to the current admin screen.
	 *
	 * @param string $return_url Full admin URL to return to (without EVGE action args).
	 * @return string
	 */
	public static function get_evge_use_rtec_url( $return_url ) {
		return wp_nonce_url( add_query_arg( 'rtec_evge_use_rtec', '1', $return_url ), 'rtec_evge_use_rtec' );
	}

	/**
	 * Nonce URL: continue with Event Genius (redirects to EVGE dashboard on click).
	 *
	 * @return string
	 */
	public static function get_evge_continue_url() {
		return wp_nonce_url( add_query_arg( 'rtec_evge_continue', '1', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ), 'rtec_evge_continue' );
	}

	/**
	 * Update a single key in the onboarding state.
	 *
	 * @param string $key   Key (use self::KEY_* constants).
	 * @param bool   $value Value.
	 */
	public static function set_state_key( $key, $value ) {
		$state = self::get_state();
		$state[ $key ] = (bool) $value;
		update_option( self::OPTION_NAME, $state );
	}

	/**
	 * Get onboarding checklist items (7 items). Used on settings and registrations pages.
	 * Each item: id, label, url, done (bool), description (optional), cta_label (optional), external (optional).
	 *
	 * @since 3.0
	 * @return array
	 */
	public static function get_checklist() {
		global $wpdb, $rtec_options;
		$rtec_options = isset( $rtec_options ) ? $rtec_options : get_option( 'rtec_options', array() );
		$table        = $wpdb->prefix . RTEC_TABLENAME;

		// 1) Registrations enabled: disable_by_default is false/unset (default = enabled for all) OR at least one future published TEC event has registration enabled.
		$registrations_enabled = empty( $rtec_options['disable_by_default'] );
		if ( ! $registrations_enabled && function_exists( 'rtec_get_events' ) ) {
			$args   = array( 'posts_per_page' => 50, 'start_date' => gmdate( 'Y-m-d H:i:s' ) );
			$events = rtec_get_events( $args, true );
			foreach ( $events as $event ) {
				$id = isset( $event->ID ) ? $event->ID : ( isset( $event->id ) ? $event->id : 0 );
				if ( $id ) {
					$meta = get_post_meta( $id, '_RTECregistrationsDisabled', true );
					if ( (string) $meta !== '1' ) {
						$registrations_enabled = true;
						break;
					}
				}
			}
		}

		// 2) At least one published future TEC event exists.
		$has_future_event = false;
		$next_event_url   = '';
		$next_event_id    = 0;
		if ( function_exists( 'rtec_get_events' ) ) {
			$args   = array( 'posts_per_page' => 1, 'start_date' => gmdate( 'Y-m-d H:i:s' ) );
			$events = rtec_get_events( $args, true );
			if ( ! empty( $events ) ) {
				$has_future_event = true;
				$ev               = $events[0];
				$next_event_id    = isset( $ev->ID ) ? (int) $ev->ID : ( isset( $ev->id ) ? (int) $ev->id : 0 );
				$next_event_url   = $next_event_id ? ( get_permalink( $next_event_id ) . '#rtec' ) : '';
			}
		}
		$create_event_url = admin_url( 'post-new.php?post_type=tribe_events' );

		// 3) First registration: count > 0; dashboard link to single event or overview.
		$has_registrations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) > 0;
		$dashboard_url     = admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=overview' );
		if ( $has_registrations ) {
			$row = $wpdb->get_row( "SELECT event_id FROM $table ORDER BY id DESC LIMIT 1" );
			if ( $row && ! empty( $row->event_id ) ) {
				$dashboard_url = admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=single&id=' . (int) $row->event_id );
			}
		}
		$test_registration_url = $next_event_url ? $next_event_url : $create_event_url;

		// 4 & 5) Visit-based completion.
		$onboarding_state  = self::get_state();
		$form_fields_done  = ! empty( $onboarding_state[ self::KEY_FORM_FIELDS_DONE ] );
		$email_review_done = ! empty( $onboarding_state[ self::KEY_EMAIL_DONE ] );

		// 6) Verify email delivery: SMTP detected OR user clicked CTA.
		$smtp_detected       = function_exists( 'rtec_checklist_smtp_detected' ) ? rtec_checklist_smtp_detected() : false;
		$from_matches        = function_exists( 'rtec_checklist_from_domain_matches_site' ) ? rtec_checklist_from_domain_matches_site() : false;
		$delivery_click_done = ! empty( $onboarding_state[ self::KEY_EMAIL_DELIVERY_DONE ] );
		$email_delivery_done = $smtp_detected || $delivery_click_done;
		if ( $smtp_detected && $from_matches ) {
			$email_delivery_description = __( 'Your email setup looks good.', 'registrations-for-the-events-calendar' );
		} else {
			$email_delivery_description = __( 'Email delivery can sometimes fail on shared hosting. We recommend SMTP for improved reliability.', 'registrations-for-the-events-calendar' );
		}

		$form_url           = admin_url( 'admin.php?page=rtec-settings&tab=form&rtec_checklist_visit=form' ) . '#rtec-form-fields';
		$email_url          = admin_url( 'admin.php?page=rtec-settings&tab=email&rtec_checklist_visit=email' ) . '#rtec-email-confirmation';
		$delivery_trigger_url = admin_url( 'admin.php?page=rtec-settings&rtec_checklist_delivery=1' );

		// 7) Finish first event: event created by wizard has _rtec_finish_setup_pending; complete when user updates that event (meta removed).
		$wizard_event_id = 0;
		$wizard_events   = get_posts(
			array(
				'post_type'      => 'tribe_events',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_rtec_finish_setup_pending',
				'meta_value'     => '1',
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $wizard_events ) ) {
			$wizard_event_id = (int) $wizard_events[0];
		}
		$finish_first_event_done = ( $wizard_event_id === 0 );
		$finish_first_event_url  = $wizard_event_id ? get_edit_post_link( $wizard_event_id, 'raw' ) : '';

		$items = array(
			array(
				'id'   => 'registrations_enabled',
				'label' => __( 'Registrations enabled', 'registrations-for-the-events-calendar' ),
				'url'   => admin_url( 'admin.php?page=rtec-settings&tab=form' ) . '#rtec-form-fields',
				'done'  => $registrations_enabled,
			),
			array(
				'id'        => 'upcoming_event',
				'label'     => __( 'Upcoming event created', 'registrations-for-the-events-calendar' ),
				'url'       => $create_event_url,
				'done'      => $has_future_event,
				'cta_label' => __( 'Create event', 'registrations-for-the-events-calendar' ),
			),
			array(
				'id'          => 'first_registration',
				'label'       => $has_registrations ? __( 'First registration received', 'registrations-for-the-events-calendar' ) : __( 'Receive your first registration', 'registrations-for-the-events-calendar' ),
				'label_done'  => __( 'First registration received', 'registrations-for-the-events-calendar' ),
				'description' => $has_registrations ? '' : __( 'Submit a test registration or share your event link to see everything in action.', 'registrations-for-the-events-calendar' ),
				'url'         => $has_registrations ? $dashboard_url : $test_registration_url,
				'done'        => $has_registrations,
				'cta_label'   => $has_registrations ? __( 'View in dashboard', 'registrations-for-the-events-calendar' ) : __( 'View event & test registration', 'registrations-for-the-events-calendar' ),
			),
			array(
				'id'          => 'finish_first_event',
				'label'       => __( 'Finish setting up your first event', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Edit the event you created in the wizard to add details, venue, or description.', 'registrations-for-the-events-calendar' ),
				'url'         => $finish_first_event_url,
				'done'        => $finish_first_event_done,
				'cta_label'   => __( 'Edit event', 'registrations-for-the-events-calendar' ),
			),
			array(
				'id'          => 'customize_form',
				'label'       => __( 'Customize form fields', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Add or remove fields to match what you need to collect.', 'registrations-for-the-events-calendar' ),
				'url'         => $form_url,
				'done'        => $form_fields_done,
				'cta_label'   => __( 'Edit form fields', 'registrations-for-the-events-calendar' ),
			),
			array(
				'id'          => 'review_email',
				'label'       => __( 'Review confirmation email', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Edit the message attendees receive after registering.', 'registrations-for-the-events-calendar' ),
				'url'         => $email_url,
				'done'        => $email_review_done,
				'cta_label'   => __( 'Edit email', 'registrations-for-the-events-calendar' ),
			),
			array(
				'id'          => 'verify_email_delivery',
				'label'       => __( 'Verify email delivery', 'registrations-for-the-events-calendar' ),
				'description' => $email_delivery_description,
				'url'         => $delivery_trigger_url,
				'done'        => $email_delivery_done,
				'cta_label'   => __( 'Improve email deliverability', 'registrations-for-the-events-calendar' ),
				'external'    => true,
			),
		);

		return $items;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 5 );
		add_action( 'admin_init', array( $this, 'maybe_evge_context_actions' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_reset_onboarding' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_exit_onboarding' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_redirect_away' ) );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_checklist' ) );
		add_action( 'admin_init', array( $this, 'maybe_reset_checklist' ) );
		add_action( 'admin_init', array( $this, 'maybe_checklist_visit_completion' ), 5 );
		add_action( 'save_post_tribe_events', array( $this, 'clear_finish_setup_flag_on_event_update' ), 20, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_rtec_onboarding_create_event', array( $this, 'ajax_create_event' ) );
		add_action( 'wp_ajax_rtec_onboarding_enable_all', array( $this, 'ajax_enable_all_future' ) );
		add_action( 'wp_ajax_rtec_onboarding_enable_event', array( $this, 'ajax_enable_event' ) );
		add_action( 'wp_ajax_rtec_onboarding_dismiss_checklist', array( $this, 'ajax_dismiss_checklist' ) );
		add_action( 'wp_ajax_rtec_onboarding_future_events', array( $this, 'ajax_future_events' ) );
	}

	/**
	 * Handle "Continue with Event Genius" and "Continue with RTEC and The Events Calendar" (dismiss interstitial).
	 */
	public function maybe_evge_context_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['rtec_evge_use_rtec'] ) && $_GET['rtec_evge_use_rtec'] === '1' ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rtec_evge_use_rtec' ) ) {
				return;
			}
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			if ( ! function_exists( 'deactivate_plugins' ) && is_admin() && defined( 'ABSPATH' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( function_exists( 'deactivate_plugins' ) ) {
				deactivate_plugins( array( 'event-genius/event-genius.php' ) );
			}
			self::set_state_key( self::KEY_EVGE_CONTEXT_DISMISSED, true );
			$redirect = wp_get_referer();
			$redirect = $redirect ? wp_validate_redirect( $redirect, admin_url( 'admin.php?page=' . RTEC_MENU_SLUG ) ) : admin_url( 'admin.php?page=' . RTEC_MENU_SLUG );
			wp_safe_redirect( remove_query_arg( array( 'rtec_evge_use_rtec', '_wpnonce' ), $redirect ) );
			exit;
		}
		if ( isset( $_GET['rtec_evge_continue'] ) && $_GET['rtec_evge_continue'] === '1' ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rtec_evge_continue' ) ) {
				return;
			}
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			if ( ! function_exists( 'deactivate_plugins' ) && is_admin() && defined( 'ABSPATH' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( function_exists( 'deactivate_plugins' ) ) {
				$plugins_to_deactivate = array(
					'registrations-for-the-events-calendar/registrations-for-the-events-calendar.php',
					'the-events-calendar/the-events-calendar.php',
					'events-calendar-pro/events-calendar-pro.php',
				);
				deactivate_plugins( $plugins_to_deactivate );
			}
			wp_safe_redirect( admin_url( 'admin.php?page=evge-dashboard' ) );
			exit;
		}
	}

	public function register_page() {
		add_submenu_page(
			'rtec-onboarding',
			__( 'Setup', 'registrations-for-the-events-calendar' ),
			__( 'Setup', 'registrations-for-the-events-calendar' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * If user clicks "Reset onboarding" on Support tab, clear the completed flag and redirect to wizard.
	 */
	public function maybe_reset_onboarding() {
		if ( ! isset( $_GET['rtec_reset_onboarding'] ) || $_GET['rtec_reset_onboarding'] !== '1' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rtec_reset_onboarding' ) ) {
			return;
		}
		delete_option( self::OPTION_NAME );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * If user clicks "Exit Setup", mark onboarding complete and redirect to main Registrations page.
	 */
	public function maybe_exit_onboarding() {
		if ( ! isset( $_GET['rtec_exit_onboarding'] ) || $_GET['rtec_exit_onboarding'] !== '1' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rtec_exit_onboarding' ) ) {
			return;
		}
		self::set_state_key( self::KEY_WIZARD_COMPLETED, true );
		wp_safe_redirect( admin_url( 'admin.php?page=' . RTEC_MENU_SLUG ) );
		exit;
	}

	/**
	 * If user dismisses the settings checklist, save and redirect.
	 */
	public function maybe_dismiss_checklist() {
		if ( ! isset( $_GET['rtec_dismiss_checklist'] ) || $_GET['rtec_dismiss_checklist'] !== '1' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rtec_dismiss_checklist' ) ) {
			return;
		}
		self::set_state_key( self::KEY_CHECKLIST_DISMISSED, true );
		update_user_meta( get_current_user_id(), 'rtec_whats_new_3_0_dashboard_notice_dismissed', true );
		wp_safe_redirect( remove_query_arg( array( 'rtec_dismiss_checklist', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * If user clicks "Reset checklist" on Support tab, clear checklist state so it shows again.
	 */
	public function maybe_reset_checklist() {
		if ( ! isset( $_GET['rtec_reset_checklist'] ) || $_GET['rtec_reset_checklist'] !== '1' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rtec_reset_checklist' ) ) {
			return;
		}
		$state = self::get_state();
		$state[ self::KEY_CHECKLIST_DISMISSED ]  = false;
		$state[ self::KEY_FORM_FIELDS_DONE ]     = false;
		$state[ self::KEY_EMAIL_DONE ]           = false;
		$state[ self::KEY_EMAIL_DELIVERY_DONE ]  = false;
		update_option( self::OPTION_NAME, $state );
		wp_safe_redirect( add_query_arg( 'rtec_checklist_reset', '1', remove_query_arg( array( 'rtec_reset_checklist', '_wpnonce' ) ) ) );
		exit;
	}

	/**
	 * Mark checklist items complete when user visits form/email tab or clicks email deliverability CTA.
	 */
	public function maybe_checklist_visit_completion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		// Visit form tab from checklist link (redirect with anchor so user lands on form fields section).
		if ( $page === 'rtec-settings' && $tab === 'form' && isset( $_GET['rtec_checklist_visit'] ) && $_GET['rtec_checklist_visit'] === 'form' ) {
			self::set_state_key( self::KEY_FORM_FIELDS_DONE, true );
			wp_safe_redirect( admin_url( 'admin.php?page=rtec-settings&tab=form' ) . '#rtec-form-fields' );
			exit;
		}
		// Visit email tab from checklist link (redirect with anchor so user lands on confirmation email section).
		if ( $page === 'rtec-settings' && $tab === 'email' && isset( $_GET['rtec_checklist_visit'] ) && $_GET['rtec_checklist_visit'] === 'email' ) {
			self::set_state_key( self::KEY_EMAIL_DONE, true );
			wp_safe_redirect( admin_url( 'admin.php?page=rtec-settings&tab=email' ) . '#rtec-email-confirmation' );
			exit;
		}
		// Click "Improve email deliverability": mark done and redirect to deliverability doc (URL from code only, no user input).
		if ( $page === 'rtec-settings' && isset( $_GET['rtec_checklist_delivery'] ) && $_GET['rtec_checklist_delivery'] === '1' ) {
			self::set_state_key( self::KEY_EMAIL_DELIVERY_DONE, true );
			$deliverability_url = 'https://roundupwp.com/faq/send-email-sendinblue-wp-mail-smtp/?utm_campaign=rtec-free&utm_source=checklist&utm_medium=deliverability';
			wp_redirect( $deliverability_url, 302, 'WordPress' );
			exit;
		}
	}

	/**
	 * When an event created by the wizard is updated in the editor, remove the flag so the "Finish setting up your first event" checklist item is marked complete.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public function clear_finish_setup_flag_on_event_update( $post_id, $post, $update ) {
		if ( ! $update || ! $post_id ) {
			return;
		}
		if ( get_post_meta( $post_id, '_rtec_finish_setup_pending', true ) === '1' ) {
			delete_post_meta( $post_id, '_rtec_finish_setup_pending' );
		}
	}

	/**
	 * Redirects for onboarding page. Runs in admin_init so headers are not yet sent.
	 */
	public function maybe_redirect_away() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$relaunch = isset( $_GET['relaunch'] ) && $_GET['relaunch'] === '1';
		$state = self::get_state();
		if ( ! empty( $state[ self::KEY_WIZARD_COMPLETED ] ) && ! $relaunch ) {
			wp_safe_redirect( admin_url( 'admin.php?page=registrations-for-the-events-calendar' ) );
			exit;
		}

		// Relaunching the wizard: always allow step 1 welcome when TEC is already active.
		if ( $relaunch ) {
			self::set_state_key( self::KEY_TEC_ACTIVATION_PROMPTED, false );
			$state = self::get_state();
		}

		// Step routing. 4 steps: 1 = welcome/install/activate, 2 = enable registrations, 3 = create event (if needed), 4 = success.
		$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
		$tec_active = class_exists( 'Tribe__Events__Main' );

		// Skip step 1 only if they were prompted to install/activate TEC on step 1, then TEC became active (wizard button or Plugins screen).
		if ( $step === 1 && $tec_active && ! empty( $state[ self::KEY_TEC_ACTIVATION_PROMPTED ] ) ) {
			self::set_state_key( self::KEY_TEC_ACTIVATION_PROMPTED, false );
			wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'step' => 2 ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( $step === 2 && ! $tec_active ) {
			wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'step' => 1 ) ) );
			exit;
		}
		if ( $step === 2 && $tec_active ) {
			$future_events = $this->get_future_events();
			if ( empty( $future_events ) ) {
				wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'step' => 3 ) ) );
				exit;
			}
		}
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}
		// Use same base styles as welcome screen (centered, addon boxes).
		wp_enqueue_style( 'rtec_admin_styles', rtec_plugin_url( 'assets/admin/css/rtec-admin-styles.css' ), array(), RTEC_VERSION );
		wp_enqueue_style(
			'rtec-wizard-common',
			rtec_plugin_url( 'assets/admin/css/rtec-wizard-common.css' ),
			array( 'rtec_admin_styles' ),
			RTEC_VERSION
		);
		wp_enqueue_style(
			'rtec-onboarding',
			rtec_plugin_url( 'assets/admin/css/rtec-onboarding.css' ),
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
			'rtec-onboarding',
			rtec_plugin_url( 'assets/admin/js/rtec-onboarding.js' ),
			array( 'jquery', 'rtec-wizard-common' ),
			RTEC_VERSION,
			true
		);
		wp_localize_script( 'rtec-onboarding', 'rtecOnboarding', array(
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'rtec_onboarding' ),
			'rtec_nonce'         => wp_create_nonce( 'rtec_nonce' ),
			'page_url'           => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			'evge_dashboard_url' => admin_url( 'admin.php?page=evge-dashboard' ),
			'tec_active'         => class_exists( 'Tribe__Events__Main' ),
			'tec_installed'      => $this->is_tec_installed(),
			'strings'     => array(
				'installing' => __( 'Installing…', 'registrations-for-the-events-calendar' ),
				'activating' => __( 'Activating…', 'registrations-for-the-events-calendar' ),
				'success'    => __( 'Success! Continuing…', 'registrations-for-the-events-calendar' ),
				'error'      => __( 'Something went wrong. Please try again or install from the Plugins page.', 'registrations-for-the-events-calendar' ),
				'creating'   => __( 'Creating event…', 'registrations-for-the-events-calendar' ),
				'thanks_patience' => __( 'This may take a minute or two. Thanks for your patience.', 'registrations-for-the-events-calendar' ),
				'event_genius_coming_soon' => __( 'Event Genius install is coming soon. For now you can install it from the Plugins page.', 'registrations-for-the-events-calendar' ),
			),
		) );
	}

	/**
	 * Whether TEC is installed (may be inactive).
	 *
	 * @return bool
	 */
	private function is_tec_installed() {
		if ( ! function_exists( 'get_plugins' ) && is_admin() && defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();
		return isset( $plugins['the-events-calendar/the-events-calendar.php'] );
	}

	public function render_page() {
		$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
		$tec_active = class_exists( 'Tribe__Events__Main' );
		$future_events = $this->get_future_events();

		$onboarding_base = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=1' );
		$evge_continue_url   = self::get_evge_continue_url();
		$evge_use_rtec_url   = self::get_evge_use_rtec_url( $onboarding_base );
		$rtec_onboarding_evge_context_screen = ( $step === 1 && self::is_evge_context_interstitial_active() );
		$hide_onboarding_progress            = $rtec_onboarding_evge_context_screen;

		// Success step: mark onboarding complete.
		if ( $step === 4 ) {
			self::set_state_key( self::KEY_WIZARD_COMPLETED, true );
			self::set_state_key( self::KEY_TEC_ACTIVATION_PROMPTED, false );
		}

		// Step 1 while TEC is inactive: user is on install/activate path; after TEC activates, skip redundant step 1.
		// Not when step 1 is only the Event Genius interstitial (no TEC install UI yet).
		if ( $step === 1 && ! $tec_active && ! $rtec_onboarding_evge_context_screen ) {
			self::set_state_key( self::KEY_TEC_ACTIVATION_PROMPTED, true );
		}

		$total_steps = 4;
		$progress = min( $step, $total_steps );
		include rtec_plugin_path( 'admin-templates/onboarding.php' );
	}

	/**
	 * Whether at least one tribe_events post exists.
	 *
	 * @return bool
	 */
	private function has_any_event() {
		$query = new WP_Query(
			array(
				'post_type'      => 'tribe_events',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return $query->have_posts();
	}

	/**
	 * Get future events (TEC post type) for dropdown, sorted by date.
	 *
	 * @return array List of events with id, title, start_date.
	 */
	public function get_future_events() {
		if ( ! function_exists( 'rtec_get_events' ) ) {
			return array();
		}
		$args = array(
			'posts_per_page' => 100,
			'start_date'     => gmdate( 'Y-m-d H:i:s' ),
			'orderby'        => 'event_date',
			'order'          => 'ASC',
		);
		$events = rtec_get_events( $args, true );
		$out = array();
		foreach ( $events as $event ) {
			$id = isset( $event->ID ) ? $event->ID : ( isset( $event->id ) ? $event->id : 0 );
			if ( ! $id ) {
				continue;
			}
			$title = get_the_title( $id );
			$start = function_exists( 'tribe_get_start_date' ) ? tribe_get_start_date( $id, false, 'Y-m-d H:i' ) : '';
			$out[] = array(
				'id'         => $id,
				'title'      => $title ? $title : __( '(No title)', 'registrations-for-the-events-calendar' ),
				'start_date' => $start,
			);
		}
		return $out;
	}

	public function ajax_future_events() {
		check_ajax_referer( 'rtec_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		wp_send_json_success( array( 'events' => $this->get_future_events() ) );
	}

	public function ajax_create_event() {
		check_ajax_referer( 'rtec_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'registrations-for-the-events-calendar' ) ) );
		}
		$event_id = $this->create_first_event();
		if ( is_wp_error( $event_id ) ) {
			wp_send_json_error( array( 'message' => $event_id->get_error_message() ) );
		}
		$this->enable_registration_for_event( $event_id );
		$link = get_permalink( $event_id );
		$url  = $link ? $link : get_edit_post_link( $event_id, 'raw' );
		set_transient( 'rtec_onboarding_success_event_id', $event_id, 300 );
		set_transient( 'rtec_onboarding_success_event_url', $url, 300 );
		wp_send_json_success( array(
			'event_id' => $event_id,
			'url'      => $url,
		) );
	}

	/**
	 * Create a single event (Community Event style) with pre-filled defaults.
	 * Uses The Events Calendar API when available so all meta and custom tables
	 * are populated for front-end display and queries.
	 *
	 * @return int|WP_Error Post ID or error.
	 */
	private function create_first_event() {
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : __( 'My Test Event', 'registrations-for-the-events-calendar' );
		$date     = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : gmdate( 'Y-m-d', strtotime( '+7 days' ) );
		$time     = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '09:00:00';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : $date;
		$end_time = isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : '17:00:00';

		$time     = strlen( $time ) === 5 ? $time . ':00' : $time;
		$end_time = strlen( $end_time ) === 5 ? $end_time . ':00' : $end_time;

		// Use TEC API when available so event has full meta and custom tables (avoids 404 / missing from queries).
		if ( class_exists( 'Tribe__Events__API' ) ) {
			$args = array(
				'post_title'     => $title,
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'EventStartDate' => $date,
				'EventEndDate'   => $end_date,
				'EventStartTime' => $time,
				'EventEndTime'   => $end_time,
				'EventAllDay'    => 'no',
			);
			$post_id = Tribe__Events__API::createEvent( $args );
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			$this->sync_event_custom_tables( $post_id );
			update_post_meta( $post_id, '_rtec_finish_setup_pending', '1' );
			return $post_id;
		}

		// Fallback: manual create when TEC API is not available.
		$post_data = array(
			'post_title'   => $title,
			'post_type'   => 'tribe_events',
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		);
		$post_id = wp_insert_post( $post_data, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$start = $date . ' ' . $time;
		$end   = $end_date . ' ' . $end_time;
		$timezone = get_option( 'timezone_string', 'UTC' );
		update_post_meta( $post_id, '_EventStartDate', $start );
		update_post_meta( $post_id, '_EventEndDate', $end );
		if ( class_exists( 'Tribe__Events__Timezones' ) && class_exists( 'Tribe__Date_Utils' ) ) {
			$utc_start = Tribe__Events__Timezones::to_utc( $start, $timezone, Tribe__Date_Utils::DBDATETIMEFORMAT );
			$utc_end   = Tribe__Events__Timezones::to_utc( $end, $timezone, Tribe__Date_Utils::DBDATETIMEFORMAT );
			update_post_meta( $post_id, '_EventStartDateUTC', $utc_start );
			update_post_meta( $post_id, '_EventEndDateUTC', $utc_end );
		} else {
			update_post_meta( $post_id, '_EventStartDateUTC', $start );
			update_post_meta( $post_id, '_EventEndDateUTC', $end );
		}
		update_post_meta( $post_id, '_EventTimezone', $timezone );
		update_post_meta( $post_id, '_EventTimezoneAbbr', '' );
		$start_ts = strtotime( $start );
		$end_ts   = strtotime( $end );
		if ( $end_ts > $start_ts ) {
			update_post_meta( $post_id, '_EventDuration', $end_ts - $start_ts );
		}
		update_post_meta( $post_id, '_EventAllDay', 'no' );
		$event = get_post( $post_id );
		do_action( 'tribe_events_update_meta', $post_id, array(), $event );
		$this->sync_event_custom_tables( $post_id );
		update_post_meta( $post_id, '_rtec_finish_setup_pending', '1' );
		return $post_id;
	}

	/**
	 * Ensures an event is present in TEC Custom Tables V1 (tec_events / tec_occurrences)
	 * so it appears in queries and single-event views instead of 404.
	 *
	 * @param int $post_id Event post ID.
	 */
	private function sync_event_custom_tables( $post_id ) {
		if ( ! function_exists( 'tribe' ) ) {
			return;
		}
		if ( ! class_exists( 'TEC\Events\Custom_Tables\V1\Updates\Events' ) ) {
			return;
		}
		try {
			$events = tribe( \TEC\Events\Custom_Tables\V1\Updates\Events::class );
			if ( method_exists( $events, 'update' ) ) {
				$events->update( $post_id );
			}
		} catch ( \Exception $e ) {
			// No-op if container or class not available.
		}
	}

	public function ajax_enable_all_future() {
		check_ajax_referer( 'rtec_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}
		$rtec_options = get_option( 'rtec_options', array() );
		$rtec_options['disable_by_default'] = false;
		update_option( 'rtec_options', $rtec_options );

		$future = $this->get_future_events();
		$first_url = '';
		foreach ( $future as $ev ) {
			$this->enable_registration_for_event( $ev['id'] );
			if ( ! $first_url ) {
				$first_url = get_permalink( $ev['id'] );
			}
		}
		if ( $first_url ) {
			set_transient( 'rtec_onboarding_success_event_url', $first_url, 300 );
		}
		wp_send_json_success();
	}

	public function ajax_enable_event() {
		check_ajax_referer( 'rtec_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid event.', 'registrations-for-the-events-calendar' ) ) );
		}
		// "Only selected events" path: ensure new events have registrations off by default.
		$rtec_options = get_option( 'rtec_options', array() );
		$rtec_options['disable_by_default'] = true;
		$options_updated = update_option( 'rtec_options', $rtec_options );

		$this->enable_registration_for_event( $event_id );
		$url = get_permalink( $event_id );
		$url = $url ? $url : get_edit_post_link( $event_id, 'raw' );
		set_transient( 'rtec_onboarding_success_event_id', $event_id, 300 );
		set_transient( 'rtec_onboarding_success_event_url', $url, 300 );
		wp_send_json_success( array( 'url' => $url ) );
	}

	private function enable_registration_for_event( $post_id ) {
		update_post_meta( $post_id, '_RTECregistrationsDisabled', '0' );
	}

	public function ajax_dismiss_checklist() {
		check_ajax_referer( 'rtec_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		self::set_state_key( self::KEY_CHECKLIST_DISMISSED, true );
		update_user_meta( get_current_user_id(), 'rtec_whats_new_3_0_dashboard_notice_dismissed', true );
		wp_send_json_success();
	}

	/**
	 * Mark onboarding complete (call from success step).
	 */
	public static function complete_onboarding() {
		self::set_state_key( self::KEY_WIZARD_COMPLETED, true );
	}
}
