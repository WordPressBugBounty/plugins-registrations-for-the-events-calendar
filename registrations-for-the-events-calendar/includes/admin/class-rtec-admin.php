<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Class RTEC_Admin
 *
 * Just your standard settings pages with a tab to view current registrations
 *
 * @since 1.0
 */
class RTEC_Admin {

	/**
	 * RTEC_Admin constructor.
	 *
	 * Create the basic admin pages
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_tribe_submenu' ) );
		add_action( 'admin_menu', array( $this, 'add_rtec_menu' ) );
		add_action( 'admin_init', array( $this, 'options_page_init' ) );

		add_action( 'wp_ajax_rtec_addon_install', array( $this, 'install_listener' ) );
		add_action( 'wp_ajax_rtec_addon_activate', array( $this, 'activate_listener' ) );

		add_filter( 'admin_footer_text', array( $this, 'rating_prompt' ), 2, 2 );
		add_filter( 'admin_footer_text', array( $this, 'empty_footer_text_on_onboarding' ), 9999, 1 );

	}

	/**
	 * Add the menu with new registration count alert
	 *
	 * @since 1.0
	 */
	public function add_tribe_submenu() {
		add_submenu_page(
			'edit.php?post_type=' . RTEC_TRIBE_EVENTS_POST_TYPE,
			esc_html__( 'Registrations', 'registrations-for-the-events-calendar' ),
			esc_html__( 'Registrations', 'registrations-for-the-events-calendar' ),
			'edit_posts',
			RTEC_PLUGIN_DIR . '_settings',
			array( $this, 'create_options_page' )
		);
	}

	public function add_rtec_menu() {
		$menu_title = __( 'Registrations', 'registrations-for-the-events-calendar' );

		$new_registrations_count = rtec_get_existing_new_reg_count();

		if ( $new_registrations_count > 0 ) {
			$menu_title .= ' <span class="update-plugins rtec-notice-admin-reg-count count-' . (int) $new_registrations_count . '"><span class="plugin-count">' . esc_html( $new_registrations_count ) . '</span></span>';
		}

		$capability = rtec_get_capability();

		add_menu_page(
			__( 'Registrations', 'registrations-for-the-events-calendar' ),
			__( $menu_title, 'registrations-for-the-events-calendar' ),
			$capability,
			RTEC_MENU_SLUG,
			array( $this, 'create_options_page' ),
			'dashicons-calendar',
			7
		);

		add_submenu_page(
			'registrations-for-the-events-calendar',
			esc_html__( 'Settings', 'registrations-for-the-events-calendar' ),
			esc_html__( 'Settings', 'registrations-for-the-events-calendar' ),
			'manage_options',
			'rtec-settings',
			array( $this, 'create_options_page' )
		);
	}

	/**
	 * Validates the $_GET field with tab information
	 *
	 * @param string $tab   current selected tab
	 *
	 * @return string       name of the tab to navigate to
	 * @since 1.0
	 */
	public static function get_active_tab( $tab = '' ) {
		switch ( $tab ) {
			// Registrations area.
			case 'overview':
				return 'overview';
			case 'latest':
				return 'latest';
			case 'single':
				return 'single';
			// Legacy: default registrations page tab.
			case 'registrations':
				return 'overview';
			case 'registrations-for-the-events-calendar':
				return 'overview';
			// Settings area.
			case 'general':
				return 'general';
			case 'form':
				return 'form';
			case 'email':
				return 'email';
			case 'text':
				return 'text';
			case 'advanced':
				return 'advanced';
			case 'support':
				return 'support';
			case 'settings':
				return 'general';
			default:
				return 'overview';
		}
	}

	public function create_options_page() {
		require_once rtec_plugin_path( 'admin-templates/main.php' );
	}

	public function blank() {
		// none needed
	}

	public function form_fields_description() {
		?>
			<p><?php 
			// Translators: %1$s is the opening link tag, %2$s is the closing link tag
			printf( __( 'Create unlimited forms and form fields by %1$supgrading to pro%2$s.', 'registrations-for-the-events-calendar' ), '<a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=form-settings&utm_medium=unlimited-forms&utm_content=UpgradingToPro" target="_blank">', '</a>' ); ?></p>
		<?php
	}

	public function options_page_init() {

		/**
		 * Form Settings
		 */

		register_setting(
			'rtec_options',
			'rtec_options',
			array( $this, 'validate_options' )
		);

		/* Form Settings Section */

		add_settings_section(
			'rtec_form_form_fields',
			'Form Fields',
			array( $this, 'form_fields_description' ),
			'rtec_form_form_fields'
		);

		$first_error       = __( 'Please enter your first name', 'registrations-for-the-events-calendar' );
		$last_error        = __( 'Please enter your last name', 'registrations-for-the-events-calendar' );
		$email_error       = __( 'Please enter a valid email address', 'registrations-for-the-events-calendar' );
		$phone_error       = __( 'Please enter a valid phone number', 'registrations-for-the-events-calendar' );
		$form_fields_array = array(
			array( 'first', __( 'First', 'registrations-for-the-events-calendar' ), $first_error, true, true, '', '{first}', true, true ),
			array( 'last', __( 'Last', 'registrations-for-the-events-calendar' ), $last_error, true, true, '', '{last}', true, true ),
			array( 'email', __( 'Email', 'registrations-for-the-events-calendar' ), $email_error, true, true, '', '{email}', true, false ),
			array( 'phone', __( 'Phone', 'registrations-for-the-events-calendar' ), $phone_error, false, false, '7, 10', '{phone}', false, false ),
		);

		$this->create_settings_field(
			array(
				'option'   => 'rtec_options',
				'name'     => 'form_fields',
				'title'    => 'Select Form Fields',
				'callback' => 'form_field_select',
				'class'    => '',
				'page'     => 'rtec_form_form_fields',
				'section'  => 'rtec_form_form_fields',
				'fields'   => $form_fields_array,
			)
		);

		/* Event Defaults (General tab) */
		add_settings_section(
			'rtec_event_defaults',
			__( 'Event Defaults', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_event_defaults'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'disable_by_default',
				'title'       => '<label for="rtec_disable_by_default">' . __( 'Disable Registration', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => '',
				'description' => __( 'New events will have registration disabled by default', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_event_defaults',
				'section'     => 'rtec_event_defaults',
				'default'     => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'limit_registrations',
				'title'       => '<label for="rtec_limit_registrations">' . __( 'Limit Registration', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => '',
				'description' => __( 'New events will limit the number of available registrations', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_event_defaults',
				'section'     => 'rtec_event_defaults',
				'default'     => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'default_max_registrations',
				'title'       => '<label for="rtec_default_max_registrations">' . __( 'Max Capacity', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => '',
				'description' => __( 'New events will have a registration limit set to this number', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'small-text',
				'page'        => 'rtec_event_defaults',
				'section'     => 'rtec_event_defaults',
				'type'        => 'number',
				'default'     => 30,
			)
		);

		$this->create_settings_field(
			array(
				'name'        => 'registration_deadline',
				'title'       => '<label for="rtec_registration_deadline">' . __( 'Deadline Offset', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'description' => __( 'This offset will apply for all events that have the deadline set as "Start time"', 'registrations-for-the-events-calendar' ),
				'callback'    => 'deadline_offset',
				'page'        => 'rtec_event_defaults',
				'section'     => 'rtec_event_defaults',
				'option'      => 'rtec_options',
				'class'       => 'short-text',
			)
		);

		/* General Registration Options (General tab) */
		add_settings_section(
			'rtec_form_general',
			__( 'General Registration Options', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_form_general'
		);

		if ( class_exists( 'Tribe__Editor__Blocks__Abstract' ) ) {
			global $rtec_options;
			if ( ! isset( $rtec_options['template_location'] ) ||
				isset( $rtec_options['template_location'] ) && ( $rtec_options['template_location'] === 'shortcode' || $rtec_options['template_location'] === 'tribe_events_single_event_before_the_content' || $rtec_options['template_location'] === 'tribe_events_single_event_after_the_content' ) ) {
				$locations = array(
					array( 'tribe_events_single_event_before_the_content', __( 'Before the event description', 'registrations-for-the-events-calendar' ) ),
					array( 'tribe_events_single_event_after_the_content', __( 'After the event description', 'registrations-for-the-events-calendar' ) ),
					array( 'shortcode', __( 'Shortcode or Gutenberg Block', 'registrations-for-the-events-calendar' ) ),
				);
			} else {
				$locations = array(
					array( 'tribe_events_single_event_before_the_content', __( 'Before the content (near top)', 'registrations-for-the-events-calendar' ) ),
					array( 'tribe_events_single_event_after_the_content', __( 'After the content (middle top)', 'registrations-for-the-events-calendar' ) ),
					array( 'tribe_events_single_event_before_the_meta', __( 'Before the meta (middle bottom)', 'registrations-for-the-events-calendar' ) ),
					array( 'tribe_events_single_event_after_the_meta', __( 'After the meta (near bottom)', 'registrations-for-the-events-calendar' ) ),
					array( 'shortcode', __( 'Shortcode or Gutenberg Block', 'registrations-for-the-events-calendar' ) ),
				);
			}
		} else {
			$locations = array(
				array( 'tribe_events_single_event_before_the_content', __( 'Before the event description', 'registrations-for-the-events-calendar' ) ),
				array( 'tribe_events_single_event_after_the_content', __( 'After the event description', 'registrations-for-the-events-calendar' ) ),
				array( 'tribe_events_single_event_before_the_meta', __( 'Before the meta (middle bottom)', 'registrations-for-the-events-calendar' ) ),
				array( 'tribe_events_single_event_after_the_meta', __( 'After the meta (near bottom)', 'registrations-for-the-events-calendar' ) ),
				array( 'shortcode', __( 'Shortcode or Gutenberg Block', 'registrations-for-the-events-calendar' ) ),
			);
		}

		$this->create_settings_field(
			array(
				'name'        => 'template_location',
				'title'       => '<label id="form-location" for="rtec_template_location">' . __( 'Form Location', 'registrations-for-the-events-calendar' ) . '</label>',
				'callback'    => 'location_select',
				'page'        => 'rtec_form_general',
				'section'     => 'rtec_form_general',
				'option'      => 'rtec_options',
				'class'       => 'default-text',
				'fields'      => $locations,
				'description' => __( 'Where on the event page should the registration form display?', 'registrations-for-the-events-calendar' ),
			)
		);

		$d_types = array(
			array( 'click_reveal', __( 'Reveal on button click', 'registrations-for-the-events-calendar' ) ),
			array( 'always_visible', __( 'Always visible', 'registrations-for-the-events-calendar' ) ),
			array( 'popup_modal', __( 'Pop-up modal window', 'registrations-for-the-events-calendar' ) ),
		);

		$this->create_settings_field(
			array(
				'name'        => 'display_type',
				'title'       => '<label for="rtec_display_type">' . __( 'Display Type', 'registrations-for-the-events-calendar' ) . '</label>',
				'callback'    => 'default_select',
				'page'        => 'rtec_form_general',
				'section'     => 'rtec_form_general',
				'option'      => 'rtec_options',
				'class'       => 'default-text',
				'fields'      => $d_types,
				'description' => __( 'How the form will be displayed on the page.', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'check_for_duplicates',
				'title'       => '<label for="rtec_check_for_duplicate">' . __( 'Check for Duplicate Emails', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Only allow one registration per event per submitted email', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_form_general',
				'section'     => 'rtec_form_general',
				'default'     => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'error_duplicate_message',
				'title'       => '<label>' . __( 'Duplicate Email Error Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'You have already registered for this event', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter an error message if the visitor has already registered', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'regular-text',
				'page'        => 'rtec_form_general',
				'section'     => 'rtec_form_general',
			)
		);

		$pformat_select = array(
			array( '1', '(###) ###-####' ),
			array( '2', '## #### ####' ),
			array( '3', '(##) #### ####' ),
			array( '5', '+1-###-###-#### ' ),
			array( '4', __( 'No Format', 'registrations-for-the-events-calendar' ) ),
		);
		$this->create_settings_field(
			array(
				'name'        => 'phone_format',
				'title'       => '<label for="phone_format">' . __( 'Phone Number Format', 'registrations-for-the-events-calendar' ) . '</label>',
				'callback'    => 'default_select',
				'page'        => 'rtec_form_general',
				'section'     => 'rtec_form_general',
				'option'      => 'rtec_options',
				'class'       => 'default-text',
				'fields'      => $pformat_select,
				'description' => __( 'Formatting for 10 digit phone numbers', 'registrations-for-the-events-calendar' ),
				'after'       => '<a href="https://roundupwp.com/faq/format-phone-numbers/" target="_blank">Custom Formatting Options</a>',
			)
		);

		/* Form Defaults (Form tab) */
		add_settings_section(
			'rtec_form_defaults',
			__( 'Form Defaults', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_form_defaults'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'show_registrants_data',
				'title'       => '<label for="rtec_show_registrants_data">' . __( 'Attendee List', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => '',
				'description' => __( 'New events will automatically enable this feature.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_form_defaults',
				'section'     => 'rtec_form_defaults',
				'default'     => false,
			)
		);

		$who_options = array(
			array( 'any', __( 'Any registration', 'registrations-for-the-events-calendar' ) ),
			array( 'users_and_confirmed', __( 'Reviewed', 'registrations-for-the-events-calendar' ) ),
		);
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'registrants_data_who',
				'title'       => '<label for="rtec_registrants_who_include">' . __( 'What Registrations Will Display', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Choosing "Reviewed" will only display registrations that existed the last time an admin viewed the registrations list—any newer registrations are hidden until an admin views the list again.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_radio',
				'values'      => $who_options,
				'class'       => 'rtec-show-registrant-options',
				'page'        => 'rtec_form_defaults',
				'section'     => 'rtec_form_defaults',
				'default'     => 'any',
				'new_line'    => true,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'attendee_list_title',
				'title'       => '<label for="rtec_attendee_list_title">' . __( 'Attendee List Title', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_text',
				'class'       => 'rtec-show-registrant-options',
				'input_class' => 'default-text',
				'page'        => 'rtec_form_defaults',
				'section'     => 'rtec_form_defaults',
				'type'        => 'text',
				'default'     => __( 'Currently Registered', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'attendance_count_message',
				'title'       => '<label>' . __( 'Attendance Count', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => '',
				'description' => '',
				'callback'    => 'attendance_count_message',
				'class'       => '',
				'page'        => 'rtec_form_defaults',
				'section'     => 'rtec_form_defaults',
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'num_registrations_messages',
				'title'       => '<label>' . __( 'Top of Form Messages', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => '',
				'description' => '',
				'callback'    => 'num_registrations_messages',
				'class'       => '',
				'page'        => 'rtec_form_defaults',
				'section'     => 'rtec_form_defaults',
			)
		);

		/* Custom Text Source (Text tab) */
		add_settings_section(
			'rtec_text_source',
			__( 'Custom Text Source', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_text_source'
		);

		$translation_options = array(
			0 => array( 'custom', __( 'Settings (recommended)', 'registrations-for-the-events-calendar' ) ),
			1 => array( 'translate', __( 'Translation Files', 'registrations-for-the-events-calendar' ) ),
		);
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'message_source',
				'title'       => '<label for="use_translations">' . __( 'Translation Source', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'values'      => $translation_options,
				'description' => __( 'If your site is multilingual or you wish to use translation files or a translation plugin for text, change this setting to "Translation Files".', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_radio',
				'class'       => 'default-text',
				'page'        => 'rtec_text_source',
				'section'     => 'rtec_text_source',
				'default'     => 'custom',
			)
		);

		/* Form Buttons and Alerts (Text tab) */
		add_settings_section(
			'rtec_form_custom_text',
			__( 'Form Buttons and Alerts', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_form_custom_text'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'register_text',
				'title'       => '<label for="rtec_register_text">' . __( '"Register" Button Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'The text displayed on the button that reveals the form', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'default-text',
				'page'        => 'rtec_form_custom_text',
				'section'     => 'rtec_form_custom_text',
				'type'        => 'text',
				'default'     => __( 'Register', 'registrations-for-the-events-calendar' ),
			)
		);

		// submit text
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'submit_text',
				'title'       => '<label for="rtec_submit_text">' . __( '"Submit" Button Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'The text displayed on the button that submits the form', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'default-text',
				'page'        => 'rtec_form_custom_text',
				'section'     => 'rtec_form_custom_text',
				'type'        => 'text',
				'default'     => __( 'Submit', 'registrations-for-the-events-calendar' ),
			)
		);

		// success message
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'success_message',
				'title'       => '<label>' . __( 'Website Success Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'Success! Please check your email inbox for a confirmation message.', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter the message you would like to display on your site after a successful form completion', 'registrations-for-the-events-calendar' ),
				'callback'    => 'message_text_area',
				'rows'        => '3',
				'class'       => '',
				'page'        => 'rtec_form_custom_text',
				'section'     => 'rtec_form_custom_text',
				'legend'      => false,
			)
		);


		/* Registration Management (Form tab) */
		add_settings_section(
			'rtec_form_registration_management',
			__( 'Registration Management', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_form_registration_management'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'visitors_can_edit_what_status',
				'title'       => '<label for="rtec_add_send_unregister_button">' . __( 'Add cancel link tool', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'This will add a button to the event page that will allow attendees to send a cancel link by entering the email address they registered with.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_form_registration_management',
				'section'     => 'rtec_form_registration_management',
				'default'     => true,
			)
		);

		/* "Already registered?" Tool (Text tab) */
		add_settings_section(
			'rtec_form_visitors_messages',
			__( '"Already registered?" Tool', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_form_visitors_messages'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'already_registered_question',
				'title'       => '<label>' . __( 'Already Registered Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'Already registered?', 'registrations-for-the-events-calendar' ),
				'description' => '',
				'callback'    => 'default_text',
				'class'       => 'rtec-visitor-can-edit',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'already_registered_directions',
				'title'       => '<label>' . __( 'Tool Directions', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'Use this tool to manage your registration.', 'registrations-for-the-events-calendar' ),
				'description' => '',
				'callback'    => 'message_text_area',
				'rows'        => '2',
				'class'       => 'rtec-visitor-can-edit',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'legend'      => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'enter_your_email_text',
				'title'       => '<label for="rtec_enter_your_email_text">' . __( 'Registered Email Label', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_text',
				'class'       => 'large-text',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => __( 'Enter your registered email address', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'send_unregister_link_text',
				'title'       => '<label for="rtec_send_unregister_link_text">' . __( '"Send cancel link" Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_text',
				'class'       => 'rtec-visitor-can-edit',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => __( 'Send cancel link', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'success_send_message',
				'title'       => '<label for="rtec_success_send_message">' . __( 'Successful Cancel Link Send Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_text',
				'class'       => 'large-text',
				'input_class' => 'regular-text',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => __( 'Check your email inbox for a cancel link.', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'email_error_message',
				'title'       => '<label for="rtec_email_error_message">' . __( 'Email Error Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_text',
				'class'       => 'large-text',
				'input_class' => 'regular-text',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => __( 'Please enter the email you registered with.', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_link_text',
				'title'       => '<label for="rtec_unregister_link_text">' . __( '"Cancel" Link Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Used for the cancel link in emails (template {cancel-link}). {unregister-link} is still supported for backwards compatibility.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'regular-text',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => __( 'Cancel my registration', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'cancel_confirm_message',
				'title'       => '<label for="rtec_cancel_confirm_message">' . __( 'Confirm Cancel Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'When cancelling using a link in the confirmation email, this text will appear above the button to confirm cancellation.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'rich_editor',
				'input_class' => 'regular-text',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => Rtec_Defaults::get( 'cancel_confirm_message' ),
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'media_buttons' => false,
					'wpautop'       => true,
				),
				'columns'     => '60',
				'preview'     => true,
				'legend'      => true,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'success_unregistration',
				'title'       => '<label>' . __( 'Website Cancel Success Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => Rtec_Defaults::get( 'success_unregistration' ),
				'description' => __( 'Enter the message you would like to display on your site after a visitor cancels their registration', 'registrations-for-the-events-calendar' ),
				'callback'    => 'message_text_area',
				'rows'        => '3',
				'class'       => '',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'legend'      => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'no_record_found_text',
				'title'       => '<label for="rtec_no_record_found_text">' . __( 'No Record Found Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Appears as a notice if no record is found when cancelling', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'default-text',
				'page'        => 'rtec_form_visitors_messages',
				'section'     => 'rtec_form_visitors_messages',
				'type'        => 'text',
				'default'     => Rtec_Defaults::get( 'no_record_found_text' ),
			)
		);

		/* Logged-in Users */
		add_settings_section(
			'rtec_form_users_options',
			'Logged-In Users',
			array( $this, 'blank' ),
			'rtec_form_users_options'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'only_logged_in',
				'title'       => '<label for="rtec_only_logged_in">' . __( 'Logged-in Users Only', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => '',
				'description' => __( 'New events will automatically enable this feature.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_form_users_options',
				'section'     => 'rtec_form_users_options',
				'default'     => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'please_log_in_message',
				'title'       => '<label>' . __( 'Website "Users Only" Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'Log in to register', 'registrations-for-the-events-calendar' ),
				'description' => __( 'This message will appear if the user is not logged-in and the event is only available to logged-in users', 'registrations-for-the-events-calendar' ),
				'callback'    => 'message_text_area',
				'rows'        => '3',
				'class'       => '',
				'page'        => 'rtec_form_users_options',
				'section'     => 'rtec_form_users_options',
				'legend'      => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'show_log_in_form',
				'title'       => '<label for="rtec_show_log_in_form">' . __( 'Show Log-in Form', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'A log-in form will appear in place of the form if a user is logged-out and registration is for logged-in users only.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_form_users_options',
				'section'     => 'rtec_form_users_options',
				'default'     => true,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'allow_users_reregister',
				'title'       => '<label for="rtec_allow_users_reregister">' . __( 'Users Can Register More Than Once', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Logged-in users can register for the same event more than once.', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_form_users_options',
				'section'     => 'rtec_form_users_options',
				'default'     => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'already_registered_message',
				'title'       => '<label>' . __( 'Already Registered Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'You are already registered for this event.', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter the message you would like to display if the visitor has already registered for the event', 'registrations-for-the-events-calendar' ),
				'callback'    => 'message_text_area',
				'rows'        => '3',
				'class'       => '',
				'page'        => 'rtec_form_users_options',
				'section'     => 'rtec_form_users_options',
				'legend'      => false,
			)
		);

		/* Form Styling */

		add_settings_section(
			'rtec_form_styles',
			'Styling',
			array( $this, 'blank' ),
			'rtec_form_styles'
		);

		// width
		$this->create_settings_field(
			array(
				'option'       => 'rtec_options',
				'name'         => 'width',
				'title'        => '<label for="rtec_form_width">' . __( 'Width of Form', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'      => '',
				'description'  => 'The width of the form',
				'callback'     => 'width_and_height_settings',
				'class'        => 'small-text',
				'default'      => '100',
				'page'         => 'rtec_form_styles',
				'section'      => 'rtec_form_styles',
				'type'         => 'text',
				'default_unit' => '%',
			)
		);

		// form background color
		$this->create_settings_field(
			array(
				'option'   => 'rtec_options',
				'name'     => 'form_bg_color',
				'title'    => '<label for="rtec_form_bg_color">' . __( 'Form Background Color', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'  => '',
				'callback' => 'default_color',
				'class'    => 'small-text',
				'page'     => 'rtec_form_styles',
				'section'  => 'rtec_form_styles',
			)
		);

		// button background color
		$this->create_settings_field(
			array(
				'option'   => 'rtec_options',
				'name'     => 'button_bg_color',
				'title'    => '<label for="rtec_button_bg_color">' . __( 'Button Background Color', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'  => '',
				'callback' => 'default_color',
				'class'    => 'small-text',
				'page'     => 'rtec_form_styles',
				'section'  => 'rtec_form_styles',
			)
		);

		// button text color
		$this->create_settings_field(
			array(
				'option'   => 'rtec_options',
				'name'     => 'button_text_color',
				'title'    => '<label for="rtec_button_text_color">' . __( 'Button Text Color', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'  => '',
				'callback' => 'default_color',
				'class'    => 'small-text',
				'page'     => 'rtec_form_styles',
				'section'  => 'rtec_form_styles',
			)
		);

		/* Advanced */

		add_settings_section(
			'rtec_advanced',
			__( 'Advanced', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_advanced'
		);

        if ( RTEC_WPML_Lite::wpml_is_active() ) {
	        $wpml_select = array(
		        array( 'enabled', __( 'Enabled', 'registrations-for-the-events-calendar' ) ),
		        array( 'disabled', __( 'Disabled', 'registrations-for-the-events-calendar' ) ),

	        );
	        $this->create_settings_field(
		        array(
			        'option'      => 'rtec_options',
			        'name'        => 'wpml_share_registrations',
			        'title'       => '<label for="rtec_wpml_share_registrations">' . __( 'Share Registrations Among Translations of an Event', 'registrations-for-the-events-calendar' ) . '</label>',
			        'example'     => '',
			        'description' => 'Registration records will be shared among all translations of an event.',
			        'callback'    => 'default_select',
			        'fields'      => $wpml_select,
			        'class'       => '',
			        'page'        => 'rtec_advanced',
			        'section'     => 'rtec_advanced',
			        'default'     => 'enabled',
		        )
	        );
        }

		// preserve database  preserve_db
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'preserve_registrations',
				'title'       => '<label for="rtec_preserve_registrations">' . __( 'Preserve registrations on uninstall', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Keep your registration records preserved in the database when you uninstall the plugin', 'registrations-for-the-events-calendar' ),
				'callback'    => 'preserve_checkbox',
				'class'       => 'default-text',
				'page'        => 'rtec_advanced',
				'section'     => 'rtec_advanced',
				'default'     => true,
			)
		);

		// preserve settings
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'preserve_settings',
				'title'       => '<label for="rtec_preserve_settings">' . __( 'Preserve settings on uninstall', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Keep your form and email settings preserved when you uninstall the plugin', 'registrations-for-the-events-calendar' ),
				'callback'    => 'preserve_checkbox',
				'class'       => 'default-text',
				'page'        => 'rtec_advanced',
				'section'     => 'rtec_advanced',
				'default'     => true,
			)
		);

		// Custom CSS
		$this->create_settings_field(
			array(
				'name'        => 'custom_css',
				'title'       => '<label for="rtec_custom_css">' . __( 'Custom CSS', 'registrations-for-the-events-calendar' ) . '</label>', // label for the input field
				'callback'    => 'custom_code', // name of the function that outputs the html
				'page'        => 'rtec_advanced', // matches the section name
				'section'     => 'rtec_advanced', // matches the section name
				'option'      => 'rtec_options', // matches the options name
				'class'       => 'default-text', // class for the wrapper and input field
				'description' => __( 'Enter your own custom CSS in the box below', 'registrations-for-the-events-calendar' ),
			)
		);

		// Custom JS
		$this->create_settings_field(
			array(
				'name'        => 'custom_js',
				'title'       => '<label for="rtec_custom_js">' . __( 'Custom JavaScript', 'registrations-for-the-events-calendar' ) . '</label>', // label for the input field
				'callback'    => 'custom_code', // name of the function that outputs the html
				'page'        => 'rtec_advanced', // matches the section name
				'section'     => 'rtec_advanced', // matches the section name
				'option'      => 'rtec_options', // matches the options name
				'class'       => 'default-text', // class for the wrapper and input field
				'description' => __( 'Enter your own custom Javascript/JQuery in the box below', 'registrations-for-the-events-calendar' ),
			)
		);

		/**
		 * Email Settings
		 */

		/* General Email Options */

		add_settings_section(
			'rtec_email_all',
			__( 'General Email Options', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_email_all'
		);

		// confirmation from address
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'confirmation_from_address',
				'title'       => '<label>' . __( 'Confirmation/Notification From Address', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => __( 'example', 'registrations-for-the-events-calendar' ) . ': registrations@yoursite.com',
				'description' => __( 'Enter an email address that you would like emails to be sent from', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'regular-text',
				'page'        => 'rtec_email_all',
				'section'     => 'rtec_email_all',
				'default'     => get_option( 'admin_email' ),
			)
		);

		/* Notification Email Settings Section */

		add_settings_section(
			'rtec_email_notification',
			'Notification Email',
			array( $this, 'blank' ),
			'rtec_email_notification'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'disable_notification',
				'title'       => '<label for="rtec_disable_notification">' . __( 'Disable Notification Email', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'default'     => false,
			)
		);

		// notification recipients
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'recipients',
				'title'       => '<label>' . __( 'Notification Recipient(s) Email', 'registrations-for-the-events-calendar' ) . '<span class="rtec-individual-available">&#42;</span></label>',
				'example'     => __( 'example', 'registrations-for-the-events-calendar' ) . ': one@yoursite.com, two@yoursite.com',
				'description' => 'Enter the email addresses you would like notification emails to go to separated by commas',
				'callback'    => 'default_text',
				'class'       => 'large-text',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'default'     => get_option( 'admin_email' ),
			)
		);

		// notify organizer
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'notify_organizer',
				'title'       => '<label for="rtec_notify_organizer">' . __( 'Always Notify Organizer', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => 'Organizers can be created on the "Edit" page for an event',
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'default'     => false,
			)
		);

		// notification from
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'notification_from',
				'title'       => '<label>' . __( 'Notification From', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => __( 'example', 'registrations-for-the-events-calendar' ) . ': ' . __( 'New Registration', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter the name you would like the notification email to come from', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'regular-text',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'default'     => 'WordPress',
			)
		);

		// notification subject
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'notification_subject',
				'title'       => '<label>' . __( 'Notification Subject', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => __( 'example', 'registrations-for-the-events-calendar' ) . ': ' . __( 'Registration Notification', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter a subject for the notification email', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'regular-text',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'default'     => __( 'Registration Notification', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'use_custom_notification',
				'title'       => '<label for="rtec_disable_notification">' . __( 'Use Custom Notification Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Click to reveal and use a custom message that you can configure', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'default'     => false,
			)
		);

		// notification message
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'notification_message',
				'title'       => '<label>' . __( 'Notification Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => '<p>The following submission was made for: {event-title} at {venue} on {event-date}</p><table><tbody><tr><td>First&#58;</td><td>{first}</td></tr><tr><td>Last&#58;</td><td>{last}</td></tr><tr><td>Email&#58;</td><td>{email}</td></tr><tr><td>Phone&#58;</td><td>{phone}</td></tr><tr><td>Other&#58;</td><td>{other}</td></tr></tbody></table><p>{admin-view-in-dashboard}</p>',
				'description' => '',
				'callback'    => 'rich_editor',
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'media_buttons' => false,
					'wpautop'       => true,
				),
				'class'       => 'rtec-notification-message-tr',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'columns'     => '60',
				'preview'     => true,
				'legend'      => true,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'admin_view_in_dashboard_text',
				'title'       => '<label for="rtec_admin_view_in_dashboard_text">' . __( '"View registration in dashboard" Link Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => __( 'Used for the admin dashboard link in notification emails (placeholder {admin-view-in-dashboard}).', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'regular-text',
				'page'        => 'rtec_email_notification',
				'section'     => 'rtec_email_notification',
				'type'        => 'text',
				'default'     => __( 'View registration in dashboard', 'registrations-for-the-events-calendar' ),
			)
		);

		/* Confirmation Email Settings Section */

		add_settings_section(
			'rtec_email_confirmation',
			__( 'Confirmation Email', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_email_confirmation'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'disable_confirmation',
				'title'       => '<label for="rtec_disable_confirmation">' . __( 'Disable Confirmation Email', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_email_confirmation',
				'section'     => 'rtec_email_confirmation',
				'default'     => false,
			)
		);

		// confirmation from name
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'confirmation_from',
				'title'       => '<label>' . __( 'Confirmation From', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => __( 'example: Your Site', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter the name you would like visitors to get the email from', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'regular-text',
				'page'        => 'rtec_email_confirmation',
				'section'     => 'rtec_email_confirmation',
				'default'     => get_bloginfo( 'name' ),
			)
		);

		// confirmation subject
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'confirmation_subject',
				'title'       => '<label>' . __( 'Confirmation Subject', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => __( 'example: Registration Confirmation', 'registrations-for-the-events-calendar' ),
				'description' => __( 'Enter a subject for the confirmation email', 'registrations-for-the-events-calendar' ),
				'callback'    => 'default_text',
				'class'       => 'regular-text',
				'page'        => 'rtec_email_confirmation',
				'section'     => 'rtec_email_confirmation',
				'default'     => '{event-title}',
			)
		);

		// confirmation message
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'confirmation_message',
				'title'       => '<label>' . __( 'Confirmation Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'Hey {first},<br /><br />You are registered for {event-title} at {venue} on {event-date}. We are looking forward to having you there. The event will be held at this location:<br /><br />{venue-address}<br />{venue-city}, {venue-state} {venue-zip}<br /><br />See you there!', 'registrations-for-the-events-calendar' ),
				'description' => '',
				'callback'    => 'rich_editor',
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'media_buttons' => false,
					'wpautop'       => true,
				),
				'class'       => '',
				'page'        => 'rtec_email_confirmation',
				'section'     => 'rtec_email_confirmation',
				'columns'     => '60',
				'preview'     => true,
				'legend'      => true,
			)
		);

		add_settings_section(
			'rtec_unregister_email',
			__( 'Cancel Registration', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_unregister_email'
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_subject',
				'title'       => '<label>' . __( 'Cancel Registration Email Subject', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'regular-text',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'description' => '',
				'default'     => __( '{event-title}', 'registrations-for-the-events-calendar' ),
			)
		);

		// Instructions to Confirm Email Address
		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_message',
				'title'       => '<label>' . __( 'Cancel Registration Email Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'To cancel your registration for {event-title} on {start-date}, click the button below.', 'registrations-for-the-events-calendar' ) . '{cancel-button}',
				'description' => '',
				'callback'    => 'rich_editor',
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'wpautop'       => true,
					'media_buttons' => false,
				),
				'class'       => '',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'columns'     => '60',
				'preview'     => true,
				'legend'      => true,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_button_text',
				'title'       => '<label>' . __( 'Cancel Button Text', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'regular-text',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'default'     => __( 'Cancel My Registration', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_notification_subject',
				'title'       => '<label>' . __( 'Cancellation Notification Subject', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'regular-text',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'description' => '',
				'default'     => __( 'Notification of Cancellation', 'registrations-for-the-events-calendar' ) . ' {event-title}',
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_notification_message',
				'title'       => '<label>' . __( 'Cancellation Notification', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => rtec_default_unregister_notification(),
				'description' => '',
				'callback'    => 'rich_editor',
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'wpautop'       => true,
					'media_buttons' => false,
				),
				'class'       => '',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'columns'     => '60',
				'preview'     => true,
				'legend'      => true,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'disable_unregister_confirmation',
				'title'       => '<label for="rtec_disable_unregister_confirmation">' . __( 'Disable Cancel Confirmation', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'description' => '',
				'callback'    => 'default_checkbox',
				'class'       => '',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'default'     => false,
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_confirmation_subject',
				'title'       => '<label>' . __( 'Cancellation Confirmation Subject', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'callback'    => 'default_text',
				'class'       => '',
				'input_class' => 'regular-text',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'description' => '',
				'default'     => __( 'Cancellation Confirmed', 'registrations-for-the-events-calendar' ),
			)
		);

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'unregister_confirmation_message',
				'title'       => '<label>' . __( 'Cancellation Confirmation', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'You are no longer registered for the event {event-title} on {start-date}.', 'registrations-for-the-events-calendar' ),
				'description' => '',
				'callback'    => 'rich_editor',
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'wpautop'       => true,
					'media_buttons' => false,
				),
				'class'       => '',
				'page'        => 'rtec_unregister_email',
				'section'     => 'rtec_unregister_email',
				'columns'     => '60',
				'preview'     => true,
				'legend'      => true,
			)
		);

		/* Email Advanced Settings Section */

		add_settings_section(
			'rtec_email_advanced',
			__( 'Advanced', 'registrations-for-the-events-calendar' ),
			array( $this, 'blank' ),
			'rtec_email_advanced'
		);

		$this->create_settings_field(
			array(
				'name'        => 'custom_date_format',
				'title'       => '<label for="rtec_custom_date_format">' . __( 'Custom Date Format', 'registrations-for-the-events-calendar' ) . '</label>',
				'callback'    => 'customize_custom_date_format',
				'page'        => 'rtec_email_advanced',
				'section'     => 'rtec_email_advanced',
				'option'      => 'rtec_options',
				'class'       => 'default-text',
				'description' => __( 'If you would like a custom date format in your messages, enter it here using the examples as a guide', 'registrations-for-the-events-calendar' ),
				'default'     => rtec_get_date_time_format(),
			)
		);

	}

	public function the_description( $description ) {
		// Descriptions are shown as hover tooltips next to the label (via create_settings_field); do not output inline.
	}

	public function default_text( $args ) {
		// get option 'text_string' value from the database
		$options       = get_option( $args['option'] );
		$default       = isset( $args['default'] ) ? $args['default'] : '';
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : $default;
		$type          = ( isset( $args['type'] ) ) ? 'type="' . $args['type'] . '"' : 'type="text"';
		?>
		<input id="rtec-<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>" name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" <?php echo $type; ?> value="<?php echo esc_attr( $option_string ); ?>"/>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	public function default_select( $args ) {
		$options  = get_option( $args['option'] );
		$selected = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
		?>
		<select name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>">
			<?php foreach ( $args['fields'] as $field ) : ?>
				<option value="<?php echo esc_attr( $field[0] ); ?>" id="rtec-<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"
											<?php
											if ( $selected == $field[0] ) {
												echo ' selected'; }
											?>
					><?php esc_html_e( $field[1], 'registrations-for-the-events-calendar' ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		if ( isset( $args['after'] ) ) {
			echo $args['after'];
		}
		?>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	public function location_select( $args ) {
		$options               = get_option( $args['option'] );
		$selected              = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
		$using_custom_template = isset( $options['using_custom_template'] ) ? $options['using_custom_template'] : false;
		?>
		<?php $this->the_description( $args['description'] ); ?>
		<select name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>">
			<?php foreach ( $args['fields'] as $field ) : ?>
				<option value="<?php echo esc_attr( $field[0] ); ?>" id="rtec-<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"
											<?php
											if ( $selected == $field[0] ) {
												echo ' selected'; }
											?>
					><?php esc_html_e( $field[1], 'registrations-for-the-events-calendar' ); ?></option>
			<?php endforeach; ?>
		</select>

		<p>
			<?php
			self::location_instructions( $selected );
			?>
		</p>

		<?php
		if ( isset( $args['after'] ) ) {
			echo $args['after'];
		}
		?>
		<p style="display: none;">
			<input type="checkbox" name="<?php echo $args['option']; ?>[using_custom_template]" id="rtec_using_custom_template" 
													<?php
													if ( $using_custom_template ) {
														echo ' checked'; }
													?>
				><label for="rtec_using_custom_template"><?php esc_html_e( 'I\'m using a custom single-event.php file in my theme.', 'registrations-for-the-events-calendar' ); ?></label>
			<a class="rtec-tooltip-link" href="JavaScript:void(0);"><?php esc_html_e( 'What is this?' ); ?></a>
			<span class="rtec-tooltip rtec-box"><?php echo wp_kses_post( __( 'This will force the plugin to use the "tribe_events_single_event_before_the_content" or the "tribe_events_single_event_after_the_content" hooks. Try this setting if the registration form isn\'t showing up on the single event page. Read <a href="https://roundupwp.com/faq/registration-form-wont-show-event-page/" target="blank">this FAQ</a> for more information.', 'registrations-for-the-events-calendar' ) ); ?></span>
		</p>
		<?php
	}

	public static function location_instructions( $selected, $show_all = true ) {

		$locations = array(
			array(
				'value'        => 'tribe_events_single_event_before_the_content',
				'instructions' => __( 'Registration form is automatically placed before the event description when an event is created.', 'registrations-for-the-events-calendar' ),
				// Translators: %1$s is the opening link tag, %2$s is the closing link tag
				'trouble'      => sprintf( __( 'Form not showing or at the bottom of the page? See %1$sthis page%2$s to resolve this issue.', 'registrations-for-the-events-calendar' ), '<a href="https://roundupwp.com/faq/registration-form-missing-footer/" target="blank" rel="noopener">', '</a>' ),
				'img'          => rtec_plugin_url( 'assets/images/form-above.png' ),
			),
			array(
				'value'        => 'tribe_events_single_event_after_the_content',
				'instructions' => __( 'Registration form is automatically placed after the event description when an event is created.', 'registrations-for-the-events-calendar' ),
				'trouble'      => sprintf( __( 'Form not showing or at the bottom of the page? See %1$sthis page%2$s to resolve this issue.', 'registrations-for-the-events-calendar' ), '<a href="https://roundupwp.com/faq/registration-form-missing-footer/" target="blank" rel="noopener">', '</a>' ),
				'img'          => rtec_plugin_url( 'assets/images/form-below.png' ),
			),
			array(
				'value'        => 'shortcode',
				'instructions' => __( 'Place the registration form using the shortcode [rtec-registration-form] in the event description or use the "Registration" Gutenberg Block if using the block editor.', 'registrations-for-the-events-calendar' ),
				'img'          => rtec_plugin_url( 'assets/images/form-shortcode.png' ),
			),
		);
		foreach ( $locations as $location ) :
			$should_show = $show_all ? true : $selected === $location['value'];
			if ( $should_show ) :
				?>
			<div class="rtec-form-location-example rtec-box rtec-clear rtec-form-location-<?php echo esc_attr( $location['value'] ); ?>" style="display: none;">
				<img src="<?php echo esc_url( $location['img'] ); ?>" alt="Form location example">
				<p>
					<?php echo esc_html( $location['instructions'] ); ?>
				<?php if ( isset( $location['trouble'] ) ) : ?>
					<br>
					<span>
					<?php echo $location['trouble']; ?>
					</span>
				<?php endif; ?>
				</p>
			</div>
				<?php
				endif;
endforeach;
	}

	public function default_checkbox( $args ) {
		$options        = get_option( $args['option'] );
		$option_checked = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $args['default'];
		?>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" type="checkbox" 
								<?php
								if ( $option_checked == true ) {
									echo 'checked';}
								?>
		/>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	public function default_radio( $args ) {
		$options        = get_option( $args['option'] );
		$option_checked = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $args['default'];
		?>
		<?php foreach ( $args['values'] as $value ) : ?>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" type="radio" value="<?php echo esc_attr( $value[0] ); ?>"
								<?php
								if ( $option_checked == $value[0] ) {
									echo 'checked';}
								?>
		/><label class="rtec-radio-label"><?php echo $value[1]; ?></label>
		<?php endforeach; ?>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	public function preserve_checkbox( $args ) {
		$options = get_option( $args['option'] );
		if ( isset( $options['preserve_db'] ) && $options['preserve_db'] == true ) {
			$option_checked = true;
		} else {
			$option_checked = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $args['default'];
		}
		?>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" type="checkbox" 
								<?php
								if ( $option_checked == true ) {
									echo 'checked';}
								?>
		/>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	public function default_color( $args ) {
		$options       = get_option( $args['option'] );
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : '';
		?>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" value="#<?php echo esc_attr( str_replace( '#', '', $option_string ) ); ?>" class="rtec-colorpicker" />
		<?php
	}

	public function width_and_height_settings( $args ) {
		$options       = get_option( $args['option'] );
		$default       = isset( $args['default'] ) ? $args['default'] : '';
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : $default;
		$selected      = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? $options[ $args['name'] . '_unit' ] : $args['default_unit'];
		?>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec-<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>" type="number" value="<?php echo esc_attr( $option_string ); ?>" />
		<select name="<?php echo $args['option'] . '[' . $args['name'] . '_unit]'; ?>" id="rtec-<?php echo $args['name'] . '_unit'; ?>">
			<option value="px" 
			<?php
			if ( $selected == 'px' ) {
				echo 'selected="selected"';}
			?>
				>px</option>
			<option value="%" 
			<?php
			if ( $selected == '%' ) {
				echo 'selected="selected"';}
			?>
				>%</option>
		</select>

		<?php
	}

	public function form_field_select( $args ) {
		$options = get_option( $args['option'] );
		foreach ( $args['fields'] as $field ) {
			$label        = isset( $field[1] ) ? $field[1] : '';
			$custom_label = isset( $options[ $field[0] . '_label' ] ) ? esc_attr( $options[ $field[0] . '_label' ] ) : $label;
			$show         = isset( $options[ $field[0] . '_show' ] ) ? esc_attr( $options[ $field[0] . '_show' ] ) : $field[3];
			$require      = isset( $options[ $field[0] . '_require' ] ) ? esc_attr( $options[ $field[0] . '_require' ] ) : $field[4];
			$error        = isset( $options[ $field[0] . '_error' ] ) ? esc_attr( $options[ $field[0] . '_error' ] ) : $field[2];
			$valid_count  = isset( $options[ $field[0] . '_valid_count' ] ) ? esc_attr( $options[ $field[0] . '_valid_count' ] ) : $field[5];
			?>
			<div class="rtec-field-options-wrapper rtec-field-wrapper-<?php echo $field[0]; ?>">
				<h4><?php esc_html_e( $label, 'registrations-for-the-events-calendar' ); ?></h4>
				<p>
					<label><?php esc_html_e( 'Label', 'registrations-for-the-events-calendar' ); ?>:</label><input type="text" name="<?php echo $args['option'] . '[' . $field[0] . '_label]'; ?>" value="<?php echo esc_html( $custom_label ); ?>" class="large-text">
				</p>
				<p class="rtec-checkbox-row">
					<input type="checkbox" class="rtec_include_checkbox" name="<?php echo $args['option'] . '[' . $field[0] . '_show]'; ?>" 
																							<?php
																							if ( $show == true ) {
																								echo 'checked'; }
																							?>
						>
					<label><?php esc_html_e( 'include', 'registrations-for-the-events-calendar' ); ?></label>

					<input type="checkbox" class="rtec_require_checkbox" name="<?php echo $args['option'] . '[' . $field[0] . '_require]'; ?>" 
																							<?php
																							if ( $require == true ) {
																								echo 'checked'; }
																							?>
						>
					<label><?php esc_html_e( 'require', 'registrations-for-the-events-calendar' ); ?></label><br>
				</p>
				<p class="rtec-e-message rtec-e-message-<?php echo $field[0]; ?>">
					<label><?php esc_html_e( 'Error Message:', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="text" name="<?php echo $args['option'] . '[' . $field[0] . '_error]'; ?>" value="<?php echo esc_html( $error ); ?>" class="large-text rtec-other-input">
				</p>
				<?php if ( $field[0] === 'phone' ) : ?>
				<p>
					<label><?php esc_html_e( 'Required length for validation:', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="text" name="<?php echo $args['option'] . '[' . $field[0] . '_valid_count]'; ?>" value="<?php echo esc_attr( $valid_count ); ?>" class="large-text rtec-valid-count-input">
					<a class="rtec-tooltip-link" href="JavaScript:void(0);"><?php echo RTEC_Icon::get( 'question-circle' ); ?></a>
					<span class="rtec-tooltip rtec-notice"><?php esc_html_e( 'Enter the length or lengths of the responses that are valid for this field separated by commas. For example, to accept North American phone numbers with and without area codes you would enter "7, 10". If area code is required, enter "10"' ); ?></span>
				</p>
				<?php endif; ?>
				<a href="javascript:void(0);" class="rtec-reveal-field-atts button-secondary">+ <?php esc_html_e( 'Show Notes', 'registrations-for-the-events-calendar' ); ?></a>
				<div class="rtec-field-atts">
					<ul>
					<?php if ( isset( $field[7] ) && $field[7] ) : ?>
					<li><?php esc_html_e( 'prefilled with user data for logged-in users', 'registrations-for-the-events-calendar' ); ?></li>
					<?php endif; ?>
					<?php if ( isset( $field[8] ) && $field[8] ) : ?>
						<li><?php esc_html_e( 'used in attendee list', 'registrations-for-the-events-calendar' ); ?></li>

					<?php endif; ?>
					<?php if ( isset( $field[6] ) ) : ?>
						<li><?php esc_html_e( 'Email template text', 'registrations-for-the-events-calendar' ); ?>: <?php echo $field[6]; ?></li>
					<?php endif; ?>
					</ul>
				</div>
			</div>
			<?php
		} // endforeach
		// the other field is treated specially
		$label   = isset( $options['other_label'] ) ? esc_attr( $options['other_label'] ) : __( 'Other', 'registrations-for-the-events-calendar' );
		$show    = isset( $options['other_show'] ) ? esc_attr( $options['other_show'] ) : false;
		$require = isset( $options['other_require'] ) ? $options['other_require'] : false;
		$error   = isset( $options['other_error'] ) ? $options['other_error'] : __( 'This is required', 'registrations-for-the-events-calendar' );
		?>
		<div class="rtec-field-options-wrapper">
			<h4><?php esc_html_e( 'Other', 'registrations-for-the-events-calendar' ); ?></h4>
			<div class="rtec-padded-group rtec-field-type">
				<label><?php esc_html_e( 'Type', 'registrations-for-the-events-calendar' ); ?>:</label>
				<div class="rtec-flex rtec-flex-gap-large">
					<span><?php esc_html_e( 'Text Field', 'registrations-for-the-events-calendar' ); ?></span>
					<div class="rtec-pro-action-button-wrap">
						<div class="rtec-pro-pill">Pro <?php echo RTEC_Icon::get( 'pro' ); ?>
						</div>
						<button type="button" class="button rtec-admin-secondary-button rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'form-fields', 'location' => 'form-settings' ) ) ); ?>"><?php esc_html_e( 'Change', 'registrations-for-the-events-calendar' ); ?></button>
					</div>
				</div>
			</div>
			<p>

				<label><?php esc_html_e( 'Label', 'registrations-for-the-events-calendar' ); ?>:</label>
				<input type="text" name="<?php echo $args['option'] . '[other_label]'; ?>" value="<?php echo esc_attr( $label ); ?>" class="large-text">
			</p>
			<p class="rtec-checkbox-row">
				<input type="checkbox" class="rtec_include_checkbox" name="<?php echo $args['option'] . '[other_show]'; ?>" 
																						<?php
																						if ( $show == true ) {
																							echo 'checked'; }
																						?>
					>
				<label><?php esc_html_e( 'include', 'registrations-for-the-events-calendar' ); ?></label>

				<input type="checkbox" class="rtec_require_checkbox" name="<?php echo $args['option'] . '[other_require]'; ?>" 
																						<?php
																						if ( $require == true ) {
																							echo 'checked'; }
																						?>
					>
				<label><?php esc_html_e( 'require', 'registrations-for-the-events-calendar' ); ?></label>
			</p>
			<p>
				<label><?php esc_html_e( 'Error Message:', 'registrations-for-the-events-calendar' ); ?></label>
				<input type="text" name="<?php echo $args['option'] . '[other_error]'; ?>" value="<?php echo esc_attr( $error ); ?>" class="large-text rtec-other-input">
			</p>
			<a href="javascript:void(0);" class="rtec-reveal-field-atts button-secondary">+ <?php esc_html_e( 'Show Notes', 'registrations-for-the-events-calendar' ); ?></a>
			<div class="rtec-field-atts">
				<ul>
					<li><?php esc_html_e( 'Email template text', 'registrations-for-the-events-calendar' ); ?>: {other}</li>
				</ul>
			</div>
		</div>

		<?php
			$custom_field_names  = isset( $options['custom_field_names'] ) ? explode( ',', $options['custom_field_names'] ) : array();
			$custom_field_string = isset( $options['custom_field_names'] ) ? $options['custom_field_names'] : '';
		?>
		<?php foreach ( $custom_field_names as $custom_field ) : ?>
			<?php if ( ! empty( $custom_field ) ) : ?>
				<?php
				$custom_field_id = str_replace( 'custom', '', $custom_field );
				$label           = isset( $options[ $custom_field . '_label' ] ) ? $options[ $custom_field . '_label' ] : 'Custom ' . $custom_field_id;
				$error           = isset( $options[ $custom_field . '_error' ] ) ? $options[ $custom_field . '_error' ] : __( 'This is required', 'registrations-for-the-events-calendar' );
				$show            = isset( $options[ $custom_field . '_show' ] ) ? $options[ $custom_field . '_show' ] : false;
				$require         = isset( $options[ $custom_field . '_require' ] ) ? $options[ $custom_field . '_require' ] : false;
				?>
		<div id="rtec-custom-field-<?php echo $custom_field_id; ?>" class="rtec-field-options-wrapper rtec-custom-field"  data-name="<?php echo $custom_field; ?>">
			<a href="JavaScript:void(0);" class="rtec-custom-field-remove"><?php echo RTEC_Icon::get( 'trash' ); ?></a>
			<h4><?php esc_html_e( 'Custom Field', 'registrations-for-the-events-calendar' ); ?> <?php echo $custom_field_id; ?></h4>
			<div class="rtec-padded-group rtec-field-type">
				<label><?php esc_html_e( 'Type', 'registrations-for-the-events-calendar' ); ?>:</label>
				<div class="rtec-flex rtec-flex-gap-large">
					<span><?php esc_html_e( 'Text Field', 'registrations-for-the-events-calendar' ); ?></span>
					<div class="rtec-pro-action-button-wrap">
						<div class="rtec-pro-pill">Pro <?php echo RTEC_Icon::get( 'pro' ); ?>
						</div>
						<button type="button" class="button rtec-admin-secondary-button rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'form-fields', 'location' => 'form-settings' ) ) ); ?>"><?php esc_html_e( 'Change', 'registrations-for-the-events-calendar' ); ?></button>
					</div>
				</div>
			</div>
			<p>

				<label><?php esc_html_e( 'Label', 'registrations-for-the-events-calendar' ); ?>:</label><input type="text" name="rtec_options[<?php echo $custom_field; ?>_label]" value="<?php echo esc_attr( $label ); ?>" class="large-text">
			</p>
			<p class="rtec-checkbox-row">
				<input type="checkbox" class="rtec_include_checkbox" name="rtec_options[<?php echo $custom_field; ?>_show]" 
																									<?php
																									if ( $show ) {
																										echo 'checked=checked'; }
																									?>
					>
				<label><?php esc_html_e( 'include', 'registrations-for-the-events-calendar' ); ?></label>

				<input type="checkbox" class="rtec_require_checkbox" name="rtec_options[<?php echo $custom_field; ?>_require]" 
																									<?php
																									if ( $require ) {
																										echo 'checked=checked'; }
																									?>
					>
				<label><?php esc_html_e( 'require', 'registrations-for-the-events-calendar' ); ?></label>
			</p>
			<p>
				<label><?php esc_html_e( 'Error Message', 'registrations-for-the-events-calendar' ); ?>:</label>
				<input type="text" name="rtec_options[<?php echo $custom_field; ?>_error]" value="<?php echo esc_attr( $error ); ?>" class="large-text rtec-other-input">
			</p>
				<?php if ( isset( $options[ $custom_field . '_label' ] ) ) : ?>
				<a href="javascript:void(0);" class="rtec-reveal-field-atts button-secondary">+ <?php esc_html_e( 'Show Notes', 'registrations-for-the-events-calendar' ); ?></a>
				<div class="rtec-field-atts">
					<ul>
						<li><?php esc_html_e( 'Email template text', 'registrations-for-the-events-calendar' ); ?>: {<?php echo esc_html( $options[ $custom_field . '_label' ] ); ?>}</li>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<?php endforeach; ?>
		<div class="rtec-green-bg"><a href="JavaScript:void(0);" class="rtec-add-field rtec-icon-text rtec-flex-center"><?php echo RTEC_Icon::get( 'plus' ); ?> <?php esc_html_e( 'Add Field', 'registrations-for-the-events-calendar' ); ?></a></div>
		<input type="hidden" id="rtec_custom_field_names" name="rtec_options[custom_field_names]" value="<?php echo esc_attr( $custom_field_string ); ?>"/>
		<?php
		// the other field is treated specially
		$label      = isset( $options['terms_conditions_label'] ) ? esc_attr( $options['terms_conditions_label'] ) : __( 'I accept the terms and conditions', 'registrations-for-the-events-calendar' );
		$require    = isset( $options['terms_conditions_require'] ) ? $options['terms_conditions_require'] : false;
		$error      = isset( $options['terms_conditions_error'] ) ? esc_attr( $options['terms_conditions_error'] ) : __( 'This is required', 'registrations-for-the-events-calendar' );
		$link       = isset( $options['terms_conditions_link'] ) ? esc_attr( $options['terms_conditions_link'] ) : '';
		$link_label = isset( $options['terms_conditions_link_label'] ) ? esc_attr( $options['terms_conditions_link_label'] ) : __( 'Terms and Conditions Page', 'registrations-for-the-events-calendar' );

		?>
		<div class="rtec-field-options-wrapper rtec-custom-field" style="margin-top: 0.5em;">
			<h4><?php esc_html_e( 'Terms and Conditions', 'registrations-for-the-events-calendar' ); ?> <span>(<?php esc_html_e( 'Checkbox field useful for GDPR compliance', 'registrations-for-the-events-calendar' ); ?>)</span></h4>
			<p>
				<label><?php esc_html_e( 'Label', 'registrations-for-the-events-calendar' ); ?>:</label><input type="text" name="<?php echo $args['option'] . '[terms_conditions_label]'; ?>" value="<?php echo esc_attr( $label ); ?>"  class="large-text"/>
			</p>
			<p class="rtec-checkbox-row">
				<input type="checkbox" class="rtec_require_checkbox" name="<?php echo $args['option'] . '[terms_conditions_require]'; ?>" 
																						<?php
																						if ( $require == true ) {
																							echo 'checked'; }
																						?>
					>
				<label><?php esc_html_e( 'require and include', 'registrations-for-the-events-calendar' ); ?></label>
			</p>
			<p>
				<label><?php esc_html_e( 'Terms and Conditions Page URL:', 'registrations-for-the-events-calendar' ); ?></label>
				<input type="text" name="<?php echo $args['option'] . '[terms_conditions_link]'; ?>" value="<?php echo esc_attr( $link ); ?>" class="large-text rtec-terms_conditions-input">
			</p>
			<p>
				<label><?php esc_html_e( 'Link Text:', 'registrations-for-the-events-calendar' ); ?></label>
				<input type="text" name="<?php echo $args['option'] . '[terms_conditions_link_label]'; ?>" value="<?php echo esc_attr( $link_label ); ?>" class="large-text rtec-terms_conditions-input">
			</p>
			<p>
				<label><?php esc_html_e( 'Error Message:', 'registrations-for-the-events-calendar' ); ?></label>
				<input type="text" name="<?php echo $args['option'] . '[terms_conditions_error]'; ?>" value="<?php echo esc_attr( $error ); ?>" class="large-text rtec-terms_conditions-input">
			</p>
		</div>
		<?php
		// the other field is treated specially
		$recaptcha_type = isset( $options['recaptcha_type'] ) ? $options['recaptcha_type'] : 'math';
		$api_key        = isset( $options['recaptcha_site_key'] ) ? $options['recaptcha_site_key'] : '';
		$secret_key     = isset( $options['recaptcha_secret_key'] ) ? $options['recaptcha_secret_key'] : '';

		$label   = isset( $options['recaptcha_label'] ) ? esc_attr( $options['recaptcha_label'] ) : __( 'What is', 'registrations-for-the-events-calendar' );
		$require = isset( $options['recaptcha_require'] ) ? $options['recaptcha_require'] : false;
		$error   = isset( $options['recaptcha_error'] ) ? esc_attr( $options['recaptcha_error'] ) : __( 'Please try again', 'registrations-for-the-events-calendar' );
		?>
		<div class="rtec-field-options-wrapper" style="margin-top: 0.5em;">
			<h4><?php esc_html_e( 'Recaptcha', 'registrations-for-the-events-calendar' ); ?></h4>
			<p class="rtec-checkbox-row">
				<input type="checkbox" class="rtec_require_checkbox" name="<?php echo $args['option'] . '[recaptcha_require]'; ?>" 
																						<?php
																						if ( $require == true ) {
																							echo 'checked'; }
																						?>
					>
				<label><?php esc_html_e( 'require and include', 'registrations-for-the-events-calendar' ); ?></label>
			</p>
			<div class="rtec-padded-group rtec-input-group" style="margin-top: 10px;">
				<div class="rtec-admin-row">
					<div class="rtec-admin-2-columns">
						<input id="rtec_recaptcha_type_math" class="rtec-recaptcha-type-radio" name="<?php echo $args['option'] . '[recaptcha_type]'; ?>" type="radio" value="math" 
																												<?php
																												if ( $recaptcha_type == 'math' ) {
																													echo 'checked';}
																												?>
						/>
						<label for="rtec_recaptcha_type_math"><?php esc_html_e( 'Math Question', 'registrations-for-the-events-calendar' ); ?></label>
					</div>
					<div class="rtec-admin-2-columns">
						<input id="rtec_recaptcha_type_google" class="rtec-recaptcha-type-radio" name="<?php echo $args['option'] . '[recaptcha_type]'; ?>" type="radio" value="google" 
																													<?php
																													if ( $recaptcha_type == 'google' ) {
																														echo 'checked';}
																													?>
						/>
						<label for="rtec_recaptcha_type_google"><?php esc_html_e( 'Google reCAPTCHA', 'registrations-for-the-events-calendar' ); ?></label><a href="https://roundupwp.com/faq/get-google-recaptcha-api-key/" target="blank" title="<?php esc_attr_e( "What's this?", 'registrations-for-the-events-calendar' ); ?>"><?php echo RTEC_Icon::get( 'question-circle' ); ?></a>
					</div>
				</div>

			</div>
			<div class="rtec-recaptcha-type rtec-recaptcha-type-math rtec-padded-group">
				<span class="description"><?php esc_html_e( 'Simple math question to avoid spam entries. Spam "honey pot" field is in the form by default', 'registrations-for-the-events-calendar' ); ?></span>
				<p>
					<label><?php esc_html_e( 'Label', 'registrations-for-the-events-calendar' ); ?>:</label><input type="text" name="<?php echo $args['option'] . '[recaptcha_label]'; ?>" value="<?php echo esc_attr( $label ); ?>" />
					<span> 2 + 5</span>
				</p>
				<p>
					<label><?php esc_html_e( 'Error Message:', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="text" name="<?php echo $args['option'] . '[recaptcha_error]'; ?>" value="<?php echo esc_attr( $error ); ?>" class="large-text rtec-recaptcha-input">
				</p>
			</div>
			<div class="rtec-recaptcha-type rtec-recaptcha-type-google rtec-padded-group">
				<span class="description"><?php esc_html_e( '"I\'m not a robot" checkbox connected to Google\'s Recaptcha.', 'registrations-for-the-events-calendar' ); ?> <a href="https://roundupwp.com/faq/get-google-recaptcha-api-key/" target="blank"><?php esc_html_e( 'Instructions', 'registrations-for-the-events-calendar' ); ?></a></span>
				<p>
					<label for="rtec-field-api-key-input"><?php esc_html_e( 'Google Recaptcha Site Key', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="text" class="large-text" name="<?php echo $args['option'] . '[recaptcha_site_key]'; ?>" id="rtec-field-api-key-input" value="<?php echo esc_attr( wp_unslash( $api_key ) ); ?>" />
				</p>
				<p>
					<label for="rtec-field-api-key-input"><?php esc_html_e( 'Google Recaptcha Secret Key', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="text" class="large-text" name="<?php echo $args['option'] . '[recaptcha_secret_key]'; ?>" id="rtec-field-secret-key-input" value="<?php echo esc_attr( wp_unslash( $secret_key ) ); ?>" />
				</p>
			</div>
		</div>
		<?php
	}

	public function custom_code( $args ) {
		$options       = get_option( $args['option'] );
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : '';
		?>
		<p><?php esc_html_e( $args['description'], 'registrations-for-the-events-calendar' ); ?></p>
		<textarea name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" style="width: 70%;" rows="7"><?php echo esc_textarea( wp_unslash( $option_string ) ); ?></textarea>
		<?php
	}

	public function deadline_offset( $args ) {
		$options       = get_option( $args['option'] );
		$default       = 0;
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
		$selected      = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? esc_attr( $options[ $args['name'] . '_unit' ] ) : '3600';
		?>
		<span><?php esc_html_e( 'Accept registrations up until', 'registrations-for-the-events-calendar' ); ?></span>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" type="number" value="<?php echo esc_attr( $option_string ); ?>"/>
		<select name="<?php echo $args['option'] . '[' . $args['name'] . '_unit]'; ?>">
			<option value="60" 
			<?php
			if ( $selected == '60' ) {
				echo 'selected="selected"';}
			?>
				><?php esc_attr_e( 'Minutes' ); ?></option>
			<option value="3600" 
			<?php
			if ( $selected == '3600' ) {
				echo 'selected="selected"';}
			?>
				><?php esc_attr_e( 'Hours' ); ?></option>
			<option value="86400" 
			<?php
			if ( $selected == '86400' ) {
				echo 'selected="selected"';}
			?>
				><?php esc_attr_e( 'Days' ); ?></option>
		</select>
		<span><?php esc_html_e( 'before event start time', 'registrations-for-the-events-calendar' ); ?></span>
		<?php
	}

	public function attendance_count_message( $args ) {
		$options        = get_option( $args['option'] );
		$option_checked = ( isset( $options[ 'include_' . $args['name'] ] ) ) ? $options[ 'include_' . $args['name'] ] : false;
		$locations      = isset( $options[ $args['name'] . '_location' ] ) ? $options[ $args['name'] . '_location' ] : array( 'above_button', 'above_description_list' );
		$template       = ( isset( $options[ $args['name'] . '_template' ] ) ) ? $options[ $args['name'] . '_template' ] : __( 'Attendance: {num} / {max}', 'registrations-for-the-events-calendar' );
		?>
		<input name="<?php echo $args['option'] . '[include_' . $args['name'] . ']'; ?>" id="rtec_include_attendance_count_message" type="checkbox" 
								<?php
								if ( $option_checked ) {
									echo 'checked';}
								?>
		/>
		<label for="rtec_include_attendance_count_message"><?php esc_html_e( 'include attendance count message', 'registrations-for-the-events-calendar' ); ?></label>
		<div class="rtec-message-group-wrap">
			<div class="rtec-availability-options-wrapper" id="rtec-message-type-wrapper">
				<h4><?php esc_html_e( 'Display', 'registrations-for-the-events-calendar' ); ?></h4>
				<div class="rtec-input-group">
					<span><?php esc_html_e( 'Locations', 'registrations-for-the-events-calendar' ); ?>:</span>
					<br>
					<input name="<?php echo $args['option'] . '[' . $args['name'] . '_location][]'; ?>" type="checkbox" value="above_button" id="rtec_above_button" 
											<?php
											if ( in_array( 'above_button', $locations, true ) ) {
												echo 'checked';}
											?>
					><label for="rtec_above_button"><?php esc_html_e( 'Above Register Button', 'registrations-for-the-events-calendar' ); ?></label><br>
					<input name="<?php echo $args['option'] . '[' . $args['name'] . '_location][]'; ?>" type="checkbox" value="above_description_list" id="rtec_above_description_list" 
											<?php
											if ( in_array( 'above_description_list', $locations, true ) ) {
												echo 'checked';}
											?>
					><label for="rtec_above_description_list"><?php esc_html_e( 'Above Description in "List" View', 'registrations-for-the-events-calendar' ); ?></label>
				</div>

				<div class="rtec-input-group">
					<label><?php esc_html_e( 'Template', 'registrations-for-the-events-calendar' ); ?>:</label>
					<br>
					<input name="<?php echo $args['option'] . '[' . $args['name'] . '_template]'; ?>" type="text" class="regular-text" value="<?php echo esc_attr( $template ); ?>">
					<br/><a class="rtec-tooltip-link" href="JavaScript:void(0);"><?php esc_html_e( 'Templates' ); ?></a>
					<span class="rtec-tooltip-table rtec-tooltip">
						<span class="rtec-col-1">{num}</span><span class="rtec-col-2"><?php esc_html_e( 'Number of attendees', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-col-1">{max}</span><span class="rtec-col-2"><?php esc_html_e( 'Maximum number of attendees', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-col-1">{remaining}</span><span class="rtec-col-2"><?php esc_html_e( 'Remaining registrations', 'registrations-for-the-events-calendar' ); ?></span>
					</span>
					<br><?php $this->the_description( $args['description'] ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function num_registrations_messages( $args ) {
		$options          = get_option( $args['option'] );
		$text_before_up   = ( isset( $options['attendance_text_before_up'] ) ) ? $options['attendance_text_before_up'] : __( 'Join', 'registrations-for-the-events-calendar' );
		$text_after_up    = ( isset( $options['attendance_text_after_up'] ) ) ? $options['attendance_text_after_up'] : __( 'others!', 'registrations-for-the-events-calendar' );
		$one_up           = ( isset( $options['attendance_text_one_up'] ) ) ? $options['attendance_text_one_up'] : __( 'Join one other person', 'registrations-for-the-events-calendar' );
		$text_before_down = ( isset( $options['attendance_text_before_down'] ) ) ? $options['attendance_text_before_down'] : __( 'Only', 'registrations-for-the-events-calendar' );
		$text_after_down  = ( isset( $options['attendance_text_after_down'] ) ) ? $options['attendance_text_after_down'] : __( 'spots left', 'registrations-for-the-events-calendar' );
		$one_down         = ( isset( $options['attendance_text_one_down'] ) ) ? $options['attendance_text_one_down'] : __( 'Only one spot left!', 'registrations-for-the-events-calendar' );
		$none_yet         = ( isset( $options['attendance_text_none_yet'] ) ) ? $options['attendance_text_none_yet'] : __( 'Be the first!', 'registrations-for-the-events-calendar' );
		$closed           = ( isset( $options['registrations_closed_message'] ) ) ? $options['registrations_closed_message'] : __( 'Registrations are closed for this event', 'registrations-for-the-events-calendar' );
		// Translators: %s is the date when registration opens
		$not_open           = ( isset( $options['registrations_open_on_message'] ) ) ? $options['registrations_open_on_message'] : __( 'Registration will open on %s', 'registrations-for-the-events-calendar' );
		$option_checked   = ( isset( $options['include_attendance_message'] ) ) ? $options['include_attendance_message'] : true;
		$option_selected  = ( isset( $options['attendance_message_type'] ) ) ? $options['attendance_message_type'] : 'up';
		?>
		<input name="<?php echo $args['option'] . '[include_attendance_message]'; ?>" id="rtec_include_attendance_message" type="checkbox" 
								<?php
								if ( $option_checked ) {
									echo 'checked';}
								?>
		/>
		<label for="rtec_include_attendance_message"><?php esc_html_e( 'include registrations availability message', 'registrations-for-the-events-calendar' ); ?></label>
		<div class="rtec-message-group-wrap">
			<div class="rtec-availability-options-wrapper" id="rtec-message-type-wrapper">
				<div class="rtec-checkbox-row">
					<h4><?php esc_html_e( 'Message Type', 'registrations-for-the-events-calendar' ); ?></h4>
					<div class="rtec-input-group">
						<div class="rtec-admin-row">
							<div class="rtec-admin-2-columns">
								<input class="rtec_attendance_message_type" id="rtec_guests_attending_type" name="<?php echo $args['option'] . '[attendance_message_type]'; ?>" type="radio" value="up" 
																															<?php
																															if ( $option_selected == 'up' ) {
																																echo 'checked';}
																															?>
								/>
								<label for="rtec_guests_attending_type"><?php esc_html_e( 'guests attending (count up)', 'registrations-for-the-events-calendar' ); ?></label>
							</div>
						<div class="rtec-admin-2-columns"><input class="rtec_attendance_message_type" id="rtec_spots_remaining_type" name="<?php echo $args['option'] . '[attendance_message_type]'; ?>" type="radio" value="down" 
																																						<?php
																																						if ( $option_selected == 'down' ) {
																																							echo 'checked';}
																																						?>
						/>
							<label for="rtec_spots_remaining_type"><?php echo wp_kses_post( __( 'spots remaining (count down, <strong>only for events with limits</strong>)', 'registrations-for-the-events-calendar' ) ); ?></label>
							</div>
						</div>

					</div>
				</div>
			</div>

			<div class="rtec-admin-row">
				<div class="rtec-availability-options-wrapper rtec-admin-2-columns" id="rtec-message-text-wrapper-up">

					<h4><?php esc_html_e( 'Guests Attending Message Text', 'registrations-for-the-events-calendar' ); ?></h4>
					<div class="rtec-input-group">
						<label for="rtec_text_before_up"><?php esc_html_e( 'Text Before: ', 'registrations-for-the-events-calendar' ); ?></label><input id="rtec_text_before_up" type="text" name="<?php echo $args['option'] . '[attendance_text_before_up]'; ?>" value="<?php echo esc_attr( $text_before_up ); ?>"/></br>
						<label for="rtec_text_after_up"><?php esc_html_e( 'Text After: ', 'registrations-for-the-events-calendar' ); ?></label><input id="rtec_text_after_up" type="text" name="<?php echo $args['option'] . '[attendance_text_after_up]'; ?>" value="<?php echo esc_attr( $text_after_up ); ?>"/>
						<p class="description">Example: "<strong>Join</strong> 20 <strong>others.</strong>"</p>
						<br>
						<label for="rtec_text_one_up"><?php esc_html_e( 'Message if exactly 1 registration: ', 'registrations-for-the-events-calendar' ); ?></label>
						<input id="rtec_text_one_up" type="text" class="large-text" name="<?php echo $args['option'] . '[attendance_text_one_up]'; ?>" value="<?php echo esc_attr( $one_up ); ?>"/>
					</div>
				</div>

				<div class="rtec-availability-options-wrapper rtec-admin-2-columns" id="rtec-message-text-wrapper-down">
					<h4><?php esc_html_e( 'Spots Remaining Message Text', 'registrations-for-the-events-calendar' ); ?></h4>
					<div class="rtec-input-group">
						<label for="rtec_text_before_down"><?php esc_html_e( 'Text Before: ', 'registrations-for-the-events-calendar' ); ?></label><input id="rtec_text_before_down" type="text" name="<?php echo $args['option'] . '[attendance_text_before_down]'; ?>" value="<?php echo esc_attr( $text_before_down ); ?>"/></br>
						<label for="rtec_text_after_down"><?php esc_html_e( 'Text After: ', 'registrations-for-the-events-calendar' ); ?></label><input id="rtec_text_after_down" type="text" name="<?php echo $args['option'] . '[attendance_text_after_down]'; ?>" value="<?php echo esc_attr( $text_after_down ); ?>"/>
						<p class="description">Example: "<strong>Only</strong> 5 <strong>spots left.</strong>"</p>
						<br>
						<label for="rtec_text_one_down"><?php esc_html_e( 'Message if exactly 1 spot left: ', 'registrations-for-the-events-calendar' ); ?></label>
						<input id="rtec_text_one_down" type="text" class="large-text" name="<?php echo $args['option'] . '[attendance_text_one_down]'; ?>" value="<?php echo esc_attr( $one_down ); ?>"/>
					</div>
				</div>
			</div>
			<div class="rtec-availability-options-wrapper" id="rtec-message-text-wrapper-other">

				<h4><?php esc_html_e( 'Other Messages', 'registrations-for-the-events-calendar' ); ?></h4>
				<div class="rtec-input-group">
					<label for="rtec_text_none_yet"><?php esc_html_e( 'Message if no registrations yet: ', 'registrations-for-the-events-calendar' ); ?></label>
					<input id="rtec_text_none_yet" type="text" class="large-text" name="<?php echo $args['option'] . '[attendance_text_none_yet]'; ?>" value="<?php echo esc_attr( $none_yet ); ?>"/>
					<br><br>
					<label for="rtec_registrations_open_message"><?php esc_html_e( 'Message if registrations are not yet open: ', 'registrations-for-the-events-calendar' ); ?></label>
					<input id="rtec_registrations_open_message" type="text" class="large-text" name="<?php echo $args['option'] . '[registrations_open_on_message]'; ?>" value="<?php echo esc_attr( $not_open ); ?>"/>
                    <br><br>
                    <label for="rtec_registrations_closed_message"><?php esc_html_e( 'Message if registrations are closed or filled: ', 'registrations-for-the-events-calendar' ); ?></label>
                    <input id="rtec_registrations_closed_message" type="text" class="large-text" name="<?php echo $args['option'] . '[registrations_closed_message]'; ?>" value="<?php echo esc_attr( $closed ); ?>"/>
                </div>
			</div>
		</div>
		<?php

		$this->create_settings_field(
			array(
				'option'      => 'rtec_options',
				'name'        => 'registrations_open_on_message',
				'title'       => '<label>' . __( 'Registration Not Open Message', 'registrations-for-the-events-calendar' ) . '</label>',
				'example'     => '',
				'default'     => __( 'Registration will open on %s', 'registrations-for-the-events-calendar' ),
				// Translators: %s is a placeholder for the date
				'description' => __( 'Message displayed if registration has not yet opened for the event. "%s" is a placeholder for the date', 'registrations-for-the-events-calendar' ),
				'callback'    => 'message_text_area',
				'rows'        => '3',
				'class'       => '',
				'page'        => 'rtec_event_custom_text',
				'section'     => 'rtec_event_custom_text',
				'legend'      => false,
			)
		);
	}



	public function rich_editor( $args ) {
		// get option 'text_string' value from the database
		$options       = get_option( $args['option'] );
		$default       = isset( $args['default'] ) ? $args['default'] : false;
		$option_string = isset( $options[ $args['name'] ] ) ? str_replace( '{nl}', '<br />', $options[ $args['name'] ] ) : $default;

		$include_reference = ! empty( $args['legend'] );
		$reference_type    = isset( $args['reference_type'] ) ? $args['reference_type'] : 'email';

		if ( $include_reference ) {
			echo '<div class="rtec-placeholderable-field">';
		}

		$settings                  = $args['settings'];
		$settings['textarea_name'] = $args['option'] . '[' . $args['name'] . ']';
		wp_editor( $option_string, $args['name'], $settings );

		if ( $include_reference ) {
			$filtered = apply_filters( 'rtec_rich_editor_reference', null, $reference_type, $args );
			if ( $filtered !== null ) {
				echo $filtered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filter may output HTML
			} else {
				$placeholders = new RTEC_Placeholders( array(), $reference_type, true );
				$data         = $placeholders->data();
				// Merge custom field placeholders from options into data for the reference table.
				if ( isset( $options['custom_field_names'] ) ) {
					$custom_field_names = is_array( $options['custom_field_names'] ) ? $options['custom_field_names'] : explode( ',', $options['custom_field_names'] );
					foreach ( $custom_field_names as $field ) {
						if ( isset( $options[ $field . '_label' ] ) && $options[ $field . '_label' ] !== '' ) {
							$label       = wp_unslash( $options[ $field . '_label' ] );
							$token       = '{' . $label . '}';
							$data[ 'custom_' . $field ] = array(
								'placeholder'  => $token,
								'description'  => sprintf( __( 'Value entered in the %s field', 'registrations-for-the-events-calendar' ), $label ),
								'category'     => 'registration',
								'value'        => '',
							);
						}
					}
				}
				$placeholders->placeholder_reference_table( $data, true );
			}
			echo '</div>';
		}

		?>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	public function message_text_area( $args ) {
		// get option 'text_string' value from the database
		$options       = get_option( $args['option'] );
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : $args['default'];
		$rows          = isset( $args['rows'] ) ? $args['rows'] : '10';
		$columns       = isset( $args['columns'] ) ? $args['columns'] : '70';
		$preview       = isset( $args['preview'] ) ? $args['preview'] : false;
		?>
		<textarea id="confirmation_message_textarea" class="<?php echo $args['class']; ?> confirmation_message_textarea" name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" cols="<?php echo $columns; ?>" rows="<?php echo $rows; ?>"><?php echo esc_textarea( $option_string ); ?></textarea>

		<?php if ( $args['legend'] ) : ?>
		<a class="rtec-tooltip-link" href="JavaScript:void(0);"><?php esc_html_e( 'Template Text (find and replace)', 'registrations-for-the-events-calendar' ); ?></a>
		<span class="rtec-tooltip-table rtec-tooltip rtec-availability-options-wrapper">
			<span class="rtec-col-1">{venue}</span><span class="rtec-col-2">Event venue/location</span>
			<span class="rtec-col-1">{venue-address}</span><span class="rtec-col-2">Venue street address</span>
			<span class="rtec-col-1">{venue-city}</span><span class="rtec-col-2">Venue city</span>
			<span class="rtec-col-1">{venue-state}</span><span class="rtec-col-2">Venue state/province</span>
			<span class="rtec-col-1">{venue-zip}</span><span class="rtec-col-2">Venue zip code</span>
			<span class="rtec-col-1">{venue-2}</span><span class="rtec-col-2"><?php esc_html_e( 'Additional event venue/location', 'registrations-for-the-events-calendar' ); ?><br>{venue-address-2}, {venue-city-2}, {venue-state-2}, {venue-zip-2}</span>
			<span class="rtec-col-1">{event-title}</span><span class="rtec-col-2">Title of event</span>
			<span class="rtec-col-1">{event-url}</span><span class="rtec-col-2"><?php esc_html_e( 'Plain text web address of event page', 'registrations-for-the-events-calendar' ); ?></span>
			<span class="rtec-col-1">{event-cost}</span><span class="rtec-col-2"><?php esc_html_e( 'Cost of the event', 'registrations-for-the-events-calendar' ); ?></span>
			<span class="rtec-col-1">{start-date}</span><span class="rtec-col-2"><?php esc_html_e( 'Event start date', 'registrations-for-the-events-calendar' ); ?></span>
			<span class="rtec-col-1">{start-time}</span><span class="rtec-col-2"><?php esc_html_e( 'Event start time', 'registrations-for-the-events-calendar' ); ?></span>
			<span class="rtec-col-1">{end-date}</span><span class="rtec-col-2"><?php esc_html_e( 'Event end date', 'registrations-for-the-events-calendar' ); ?></span>
			<span class="rtec-col-1">{end-time}</span><span class="rtec-col-2"><?php esc_html_e( 'Event end time', 'registrations-for-the-events-calendar' ); ?></span>
			<span class="rtec-col-1">{first}</span><span class="rtec-col-2">First name of registrant</span>
			<span class="rtec-col-1">{last}</span><span class="rtec-col-2">Last name of registrant</span>
			<span class="rtec-col-1">{email}</span><span class="rtec-col-2">Email of registrant</span>
			<span class="rtec-col-1">{phone}</span><span class="rtec-col-2">Phone number of registrant</span>
			<span class="rtec-col-1">{other}</span><span class="rtec-col-2">Information submitted in the "other" field</span>
			<span class="rtec-col-1">{ical-url}</span><span class="rtec-col-2">Plain text web address to download ical file for event</span>
			<?php
			// add custom
			if ( isset( $options['custom_field_names'] ) ) {

				if ( is_array( $options['custom_field_names'] ) ) {
					$custom_field_names = $options['custom_field_names'];
				} else {
					$custom_field_names = explode( ',', $options['custom_field_names'] );
				}
			} else {
				$custom_field_names = array();
			}

			foreach ( $custom_field_names as $field ) {
				if ( $options[ $field . '_show' ] ) {
					echo '<span class="rtec-col-1">' . '{' . $options[ $field . '_label' ] . '}' . '</span><span class="rtec-col-2">Custom field</span>';
				}
			}
			?>
		</span>
		<?php endif; ?>

		<br><?php $this->the_description( $args['description'] ); ?>
		<?php if ( $preview ) : ?>
		<td>
			<h4>Preview:</h4>
			<div class="rtec_js_preview">
				<pre></pre>
			</div>
		</td>
		<?php endif; ?>
		<?php
	}

	public function customize_custom_date_format( $args ) {
		$options       = get_option( $args['option'] );
		$default       = rtec_get_date_time_format();
		$option_string = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : $default;
		// echo rtec_get_date_time_format();
		?>
		<input name="<?php echo $args['option'] . '[' . $args['name'] . ']'; ?>" id="rtec_<?php echo $args['name']; ?>" type="text" value="<?php echo esc_attr( $option_string ); ?>" size="10" placeholder="Eg. F jS, Y" />
		<a href="https://www.roundupwp.com/products/registrations-for-the-events-calendar/docs/date-formatting-guide/" target="_blank"><?php esc_html_e( 'Examples', 'registrations-for-the-events-calendar' ); ?></a>
		<br><?php $this->the_description( $args['description'] ); ?>
		<?php
	}

	/**
	 * Makes creating settings easier
	 *
	 * @param array $args   extra arguments to create parts of the form fields
	 */
	public function create_settings_field( $args = array() ) {
		$title = $args['title'];
		if ( ! empty( $args['description'] ) ) {
			$title .= rtec_get_setting_tooltip_html( $args['description'] );
		}
		add_settings_field(
			$args['name'],
			$title,
			array( $this, $args['callback'] ),
			$args['page'],
			$args['section'],
			$args
		);
	}

	private function get_allowed_tags() {
		$allowed_tags = array(
			'a'          => array(
				'class' => array(),
				'href'  => array(),
				'rel'   => array(),
				'title' => array(),
			),
			'abbr'       => array(
				'title' => array(),
			),
			'b'          => array(),
			'blockquote' => array(
				'cite' => array(),
			),
			'br'         => array(),
			'cite'       => array(
				'title' => array(),
			),
			'code'       => array(),
			'del'        => array(
				'datetime' => array(),
				'title'    => array(),
			),
			'dd'         => array(),
			'div'        => array(
				'class' => array(),
				'title' => array(),
				'style' => array(),
			),
			'dl'         => array(),
			'dt'         => array(),
			'em'         => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'i'          => array(),
			'img'        => array(
				'alt'    => array(),
				'class'  => array(),
				'height' => array(),
				'src'    => array(),
				'width'  => array(),
			),
			'li'         => array(
				'class' => array(),
			),
			'ol'         => array(
				'class' => array(),
			),
			'p'          => array(
				'class' => array(),
			),
			'q'          => array(
				'cite'  => array(),
				'title' => array(),
			),
			'span'       => array(
				'class' => array(),
				'title' => array(),
				'style' => array(),
			),
			'strike'     => array(),
			'strong'     => array(),
			'ul'         => array(
				'class' => array(),
			),
			'table'      => array(
				'style'       => array(),
				'class'       => array(),
				'cellpadding' => array(),
				'cellspacing' => array(),
				'border'      => array(),
			),
			'tbody'      => array(
				'style' => array(),
				'class' => array(),
			),
			'td'         => array(
				'style' => array(),
				'class' => array(),
			),
			'th'         => array(
				'style' => array(),
				'class' => array(),
			),
			'tr'         => array(
				'style' => array(),
				'class' => array(),
			),
		);

		return $allowed_tags;
	}

	/**
	 * Validate and sanitize form entries
	 *
	 * This is used for settings not involved in email
	 *
	 * @param array $input raw input data from the user
	 * @return array valid and sanitized data
	 * @since 1.0
	 */
	public function validate_options( $input ) {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'registrations';

		$updated_options      = get_option( 'rtec_options', false );
		$checkbox_settings    = array();
		$unfiltered_html      = array();
		$allowed_tags         = $this->get_allowed_tags();
		$rich_editor_settings = array();
		$array_settings       = array();

		// Only reset checkboxes/settings that belong to the form being saved (by tab), so saving one tab does not wipe others.
		if ( isset( $input['template_location'] ) ) {
			// General tab: registration_availability.
			$checkbox_settings = array( 'disable_by_default', 'limit_registrations', 'check_for_duplicates' );
		} elseif ( isset( $input['custom_field_names'] ) || isset( $input['form_width'] ) ) {
			// Form tab: form fields, attendee data, users options, styles.
			$checkbox_settings    = array( 'first_show', 'first_require', 'last_show', 'last_require', 'email_show', 'email_require', 'phone_show', 'phone_require', 'other_show', 'other_require', 'terms_conditions_require', 'recaptcha_require', 'show_registrants_data', 'only_logged_in', 'show_log_in_form', 'include_attendance_count_message', 'include_attendance_message', 'using_custom_template', 'preserve_db', 'preserve_registrations', 'preserve_settings', 'allow_users_reregister' );
			$array_settings       = array( 'attendance_count_message_location' );
			$rich_editor_settings = array( 'please_log_in_message', 'attendance_count_message_template' );
		} elseif ( isset( $input['register_text'] ) || isset( $input['enter_your_email_text'] ) ) {
			// Text & Translation tab: custom text + visitors_options (includes attendance/availability message checkboxes).
			$checkbox_settings    = array( 'add_send_unregister_button', 'visitors_can_edit_what_status', 'include_attendance_count_message', 'include_attendance_message' );
			$rich_editor_settings = array( 'notification_message', 'success_message', 'success_unregistration', 'attendance_count_message_template' );
			$array_settings       = array( 'attendance_count_message_location' );
		} elseif ( isset( $input['confirmation_message'] ) ) {
			// Email tab.
			$rich_editor_settings = array( 'confirmation_message', 'notification_message', 'unregister_message', 'unregister_notification_message', 'unregister_confirmation_message' );
			$checkbox_settings    = array( 'disable_notification', 'disable_confirmation', 'use_custom_notification', 'notify_organizer', 'disable_unregister_confirmation' );
		} elseif ( isset( $input['preserve_registrations'] ) || isset( $input['recaptcha_require'] ) || isset( $input['custom_css'] ) || isset( $input['custom_js'] ) ) {
			// Advanced tab.
			$checkbox_settings = array( 'preserve_registrations', 'preserve_settings', 'recaptcha_require' );
			$unfiltered_html    = array( 'custom_js', 'custom_css' );
			if ( function_exists( 'RTEC_WPML_Lite' ) && RTEC_WPML_Lite::wpml_is_active() ) {
				$checkbox_settings[] = 'wpml_share_registrations';
			}
		}

		if ( isset( $input['custom_field_names'] ) ) {
			$custom_field_names = explode( ',', $input['custom_field_names'] );
		} else {
			$custom_field_names = array();
		}

		foreach ( $checkbox_settings as $checkbox_setting ) {
			$updated_options[ $checkbox_setting ] = false;
		}

		foreach ( $array_settings as $array_setting ) {
			$updated_options[ $array_setting ] = array();
		}

		if ( isset( $updated_options['visitors_can_edit_what_status'] ) && $updated_options['visitors_can_edit_what_status'] ) {
			$updated_options['add_registration_management_tool'] = true;
		} else {
			$updated_options['add_registration_management_tool'] = false;
		}

		foreach ( $input as $key => $val ) {
			if ( is_array( $val ) ) {
				$updated_options[ $key ] = array();
				foreach ( $val as $sub_val ) {
					$updated_options[ $key ][] = sanitize_text_field( $sub_val );
				}
			} elseif ( in_array( $key, $checkbox_settings ) ) {
				if ( $val == 'on' ) {
					$updated_options[ $key ] = true;
				}
			} elseif ( in_array( $key, $rich_editor_settings, true ) ) {
				$working_text            = wp_kses( str_replace( '{nl}', '<br />', $val ), $allowed_tags );
				$updated_options[ $key ] = $working_text;
			} elseif ( in_array( $key, $unfiltered_html ) ) {
                if ( current_user_can( 'unfiltered_html' ) ) {
                    $updated_options[ $key ] = $val;
                }
			} else {
				$updated_options[ $key ] = sanitize_text_field( $val );
			}
			if ( $tab === 'email' ) {
				$updated_options[ $key ] = $this->check_malicious_headers( $val );
			}
		}

		foreach ( $custom_field_names as $field ) {

			if ( isset( $input[ $field . '_require' ] ) ) {
				$updated_options[ $field . '_require' ] = true;
			} else {
				$updated_options[ $field . '_require' ] = false;
			}

			if ( isset( $input[ $field . '_show' ] ) ) {
				$updated_options[ $field . '_show' ] = true;
			} else {
				$updated_options[ $field . '_show' ] = false;
			}

			if ( isset( $input[ $field . '_label' ] ) ) {
				$updated_options[ $field . '_label' ] = sanitize_text_field( wp_unslash( $input[ $field . '_label' ] ) );
			}
		}

		return $updated_options;
	}

	/**
	 * Checks for malicious headers
	 *
	 * Since these settings are used as part of an email message, the data is
	 * checked for potential header injections
	 *
	 * @param string $value value of an option submitted from the plugin options page
	 * @return string sanitized data string or if validation fails, empty string
	 * @since 1.0
	 */
	public function check_malicious_headers( $value ) {
		$malicious = array( 'to:', 'cc:', 'bcc:', 'content-type:', 'mime-version:', 'multipart-mixed:', 'content-transfer-encoding:' );

		foreach ( $malicious as $m ) {
			if ( stripos( $value, $m ) !== false ) {
				add_settings_error( '', 'setting-error', 'Your entries contain dangerous input', 'error' );
				return '';
			}
		}

		$value = str_replace( array( '%0a', '%0d' ), ' ', $value );
		return trim( $value );
	}

	public static function get_plugin_data( $plugin ) {

		$installed_plugins = get_plugins();

		if ( $plugin === 'event-genius' ) {
			$event_genius_file = 'event-genius/event-genius.php';
			if ( ! function_exists( 'is_plugin_active' ) && is_admin() && defined( 'ABSPATH' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return array(
				'plugin'       => $event_genius_file,
				'is_installed' => isset( $installed_plugins[ $event_genius_file ] ),
				'is_active'    => function_exists( 'is_plugin_active' ) && is_plugin_active( $event_genius_file ),
				'slug'         => 'event-genius',
				'icon'         => '<img src="' . esc_url( rtec_plugin_url( 'assets/images/event-genius-icon.png' ) ) . '" alt="">',
				'download_url' => 'https://downloads.wordpress.org/plugin/event-genius.latest-stable.zip',
				'name'         => __( 'Event Genius', 'registrations-for-the-events-calendar' ),
				'description'  => esc_html__( 'Event management, registration, RSVP, and tickets — an alternative to The Events Calendar.', 'registrations-for-the-events-calendar' ),
				'compare_description' => esc_html__( 'An all-in-one event and registration system with built-in calendars, recurring events, event registration, and a modern design. Choose this if you want everything managed in one place.', 'registrations-for-the-events-calendar' ),
				'link'         => 'https://wordpress.org/plugins/event-genius/',
			);
		}

		$tec_data       = array(
			'plugin'           => 'the-events-calendar/the-events-calendar.php',
			'is_licensed'      => false,
			'is_bundled'       => false,
			'main_is_licensed' => false,
			'license_data'     => array(),
			'is_installed'     => isset( $installed_plugins['the-events-calendar/the-events-calendar.php'] ),
			'is_active'        => class_exists( 'Tribe__Events__Main' ),
			'slug'             => 'tribe-tec',
			'icon'             => '<img src="' . esc_url( rtec_plugin_url( 'assets/images/tec-icon.png' ) ) . '">',
			'download_url'     => 'https://downloads.wordpress.org/plugin/the-events-calendar' . RTEC_TEC_VER_STRING . '.zip',
			'name'             => __( 'The Events Calendar', 'registrations-for-the-events-calendar' ),
			'description'      => esc_html__( 'A fully featured, immensely popular calendar solution from Modern Tribe.', 'registrations-for-the-events-calendar' ),
			'compare_description' => esc_html__( "Use The Events Calendar to manage events and add registration forms with this plugin. Choose this if you're already using The Events Calendar or prefer to keep your event management separate.", 'registrations-for-the-events-calendar' ),
			'link'             => 'https://roundupwp.com/products',
		);

		return $tec_data;
	}

	/**
	 * Used for AJAX
	 *
	 * @since 2.20
	 */
	public function install_listener() {
		check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error();
		}

		$return = array(
			'messageHTML' => '<div class="rtec-danger-message">' . esc_html__( 'Could not install the plugin. Please try again or install from the Plugins page.', 'registrations-for-the-events-calendar' ) . '</div>',
		);

		if ( isset( $_POST['plugin'] ) ) {

			$type           = 'plugin';
			$plugin         = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
			$activate_after = ! isset( $_POST['activate_after'] ) || $_POST['activate_after'] === '1' || $_POST['activate_after'] === 'yes';
			if ( $activate_after && ! current_user_can( 'activate_plugins' ) ) {
				$return['messageHTML'] = '<div class="rtec-danger-message">' . esc_html__( 'You do not have permission to activate plugins.', 'registrations-for-the-events-calendar' ) . '</div>';
				wp_send_json_error( $return );
			}

			$return = $this->install( $plugin, '', $activate_after );

			if ( ! is_wp_error( $return ) && $return['success'] ) {
				$updated_add_on = self::get_plugin_data( $plugin );
				update_option( 'tribe_skip_welcome', true );
				if ( 'plugin' === $type ) {
					$return['messageHTML'] = '<div class="rtec-success-message">' . esc_html__( 'Plugin installed & activated. Refreshing page.', 'registrations-for-the-events-calendar' ) . '</div>';
					wp_send_json_success( $return );
				} else {
					$return['messageHTML'] = '<div class="rtec-success-message">' . esc_html__( 'Add-on installed & activated.', 'registrations-for-the-events-calendar' ) . '</div>';
					wp_send_json_success( $return );
				}
			} else {
				$return['messageHTML'] = '<div class="rtec-danger-message">' . rtec_sanitize_outputted_html( $return['msg'] ) . '</div>';
				wp_send_json_error( $return );
			}
		}

		wp_send_json_error( $return );
	}

	/**
	 * Used for AJAX
	 *
	 * @since 2.7.5
	 */
	public function activate_listener() {
		check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

		$error_message = esc_html__( 'Could not activate add-on. Please activate from the Plugins page.', 'registrations-for-the-events-calendar' );
		$return        = array( 'messageHTML' => '<div class="rtec-danger-message">' . $error_message . '</div>' );
		// Check for permissions.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( $return );
		}

		if ( isset( $_POST['plugin'] ) ) {
			$type = 'plugin';

			$plugin = sanitize_text_field( $_POST['plugin'] );

			$activate = $this->activate( $plugin );

			if ( ! is_wp_error( $activate ) ) {
				update_option( 'tribe_skip_welcome', true );
				$updated_add_on        = self::get_plugin_data( $plugin );
				$return['messageHTML'] = '<div class="rtec-success-message">' . esc_html__( 'Plugin installed & activated. Refreshing page.', 'registrations-for-the-events-calendar' ) . '</div>';
				wp_send_json_success( $return );
			}
		}

		wp_send_json_error( $return );
	}

	/**
	 * Install Add On (from roundupwp.com)
	 *
	 * @param string|array $add_on
	 * @param string       $license_key
	 * @param bool         $activate_after Whether to activate the plugin after install. Default true.
	 *
	 * @return array
	 *
	 * @since 2.7.5
	 */
	public function install( $add_on, $license_key, $activate_after = true ) {
		$return      = array(
			'success' => false,
			'msg'     => esc_html__( 'Could not install The Events Calendar. Please search for it on the plugins page.', 'registrations-for-the-events-calendar' ),
		);
		$plugin_data = self::get_plugin_data( $add_on );
		// Set the current screen to avoid undefined notices.
		set_current_screen( 'registrations-for-the-events-calendar' );

		// Onboarding: plugin already present but inactive — activate only (skip download/install).
		if ( ( 'event-genius' === $add_on || 'tribe-tec' === $add_on ) && $activate_after && ! empty( $plugin_data['is_installed'] ) && empty( $plugin_data['is_active'] ) ) {
			if ( ! function_exists( 'activate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$activated = activate_plugin( $plugin_data['plugin'] );
			if ( ! is_wp_error( $activated ) ) {
				if ( 'tribe-tec' === $add_on ) {
					update_option( 'tribe_skip_welcome', true );
				}
				$return['success'] = true;
				$return['msg']     = esc_html__( 'Plugin installed & activated. Refreshing page.', 'registrations-for-the-events-calendar' );
				return $return;
			}
			$return['msg'] = sprintf(
				/* translators: %s: plugin name */
				esc_html__( 'Could not activate %s. Please activate it from the Plugins page.', 'registrations-for-the-events-calendar' ),
				$plugin_data['name']
			);
			return $return;
		}

		// Prepare variables.
		$url = esc_url_raw(
			add_query_arg(
				array(
					'page' => 'registrations-for-the-events-calendar',
				),
				admin_url( 'admin.php' )
			)
		);

		$creds = request_filesystem_credentials( $url, '', false, false, null );

		// Check for file system permissions.
		if ( false === $creds ) {
			wp_send_json_error( $return );
		}

		if ( ! WP_Filesystem( $creds ) ) {
			wp_send_json_error( $return );
		}

		// Create the plugin upgrader with our custom skin.
		require_once rtec_plugin_path( 'includes/helpers/PluginSilentUpgrader.php' );
		require_once rtec_plugin_path( 'includes/helpers/PluginSilentUpgraderSkin.php' );
		require_once rtec_plugin_path( 'includes/admin/class-install-skin.php' );

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		$installer = new RTEC\Helpers\PluginSilentUpgrader( new Rtec_Install_Skin() );

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			return $return;
		}

		$file = $plugin_data['download_url'];

		if ( ! empty( $file ) ) {

			$installer->install( $file ); // phpcs:ignore
			// Check license key.
			// Flush the cache and return the newly installed plugin basename.
			wp_cache_flush();

			$plugin_basename = $installer->plugin_info();

			if ( $plugin_basename ) {
				if ( ! $activate_after ) {
					$return['msg']     = esc_html__( 'Plugin installed. You can activate it from the Plugins page or click Activate below.', 'registrations-for-the-events-calendar' );
					$return['success'] = true;
					return $return;
				}
				// Activate the plugin silently.
				$activated = activate_plugin( $plugin_basename );

				if ( ! is_wp_error( $activated ) ) {
					if ( $add_on === 'tribe-tec' ) {
						update_option( 'tribe_skip_welcome', true );
					}
					$return['msg']     = esc_html__( 'Plugin installed & activated. Refreshing page.', 'registrations-for-the-events-calendar' );
					$return['success'] = true;

					return $return;
				} else {
					$return['msg'] = esc_html__( 'The plugin was installed but needs to be activated from the Plugins page.', 'registrations-for-the-events-calendar' );

					return $return;
				}
			}
		}

		return $return;
	}

	/**
	 * Activate a plugin
	 *
	 * @param string|array $add_on
	 *
	 * @return bool
	 *
	 * @since 2.7.5
	 */
	public function activate( $add_on ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error( 'rtec_no_activate_cap', esc_html__( 'Could not activate add-on. Please activate from the Plugins page.', 'registrations-for-the-events-calendar' ) );
		}
		$single_add_on = self::get_plugin_data( $add_on );
		if ( $single_add_on ) {
			return activate_plugins( $single_add_on['plugin'] );
		}

		return false;
	}

	public static function get_nav_name() {
		$return = '';

		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'registrations';
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'registrations-for-the-events-calendar';

		if ( $page === 'registrations-for-the-events-calendar' || $page === RTEC_MENU_SLUG ) {
			if ( $tab === 'single' || $tab === 'single-payment' || $tab === 'message-history' ) {
				$return = __( 'Single Event', 'registrations-for-the-events-calendar' );
			} elseif ( $tab === 'latest' ) {
				$return = __( 'All Registrations', 'registrations-for-the-events-calendar' );
			} else {
				$return = __( 'Events', 'registrations-for-the-events-calendar' );
			}
		} elseif ( $page === 'rtec-settings' ) {
			if ( $tab === 'general' ) {
				$return = __( 'General', 'registrations-for-the-events-calendar' );
			} elseif ( $tab === 'form' || $tab === 'create' ) {
				$return = __( 'Form Settings', 'registrations-for-the-events-calendar' );
			} elseif ( $tab === 'email' || $tab === 'message-create' ) {
				$return = __( 'Email Settings', 'registrations-for-the-events-calendar' );
			} elseif ( $tab === 'text' ) {
				$return = __( 'Text & Translation', 'registrations-for-the-events-calendar' );
			} elseif ( $tab === 'advanced' ) {
				$return = __( 'Advanced', 'registrations-for-the-events-calendar' );
			} elseif ( $tab === 'support' ) {
				$return = __( 'Support', 'registrations-for-the-events-calendar' );
			} else {
				$return = __( 'Settings', 'registrations-for-the-events-calendar' );
			}
		} elseif ( $page === 'rtec-text' ) {
			$return = __( 'Translation & Text Settings', 'registrations-for-the-events-calendar' );
		} elseif ( $page === 'rtec-payments' ) {
			$return = __( 'Payments Settings', 'registrations-for-the-events-calendar' );
		} elseif ( $page === 'rtec-misc' ) {
			$return = __( 'Misc Settings', 'registrations-for-the-events-calendar' );
		} elseif ( $page === 'rtec-license' ) {
			$return = __( 'License & Add Ons', 'registrations-for-the-events-calendar' );
		} elseif ( $page === 'rtec-create' ) {
			$return = __( 'All Forms', 'registrations-for-the-events-calendar' );
		} elseif ( $page === 'rtec-message-create' ) {
			$return = __( 'Create Message', 'registrations-for-the-events-calendar' );
		} elseif ( $page === 'rtec-message-history' ) {
			$return = __( 'Message History', 'registrations-for-the-events-calendar' );
		}

		$return = apply_filters( 'rtec_nav_name', $return, $page, $tab );

		return $return;
	}

	/**
	 * Empty admin footer text during setup (wizard, Event Genius choice, checklist) so no plugin or core text appears.
	 *
	 * @param string $footer_text Current footer text.
	 * @return string Empty string when suppressed, otherwise unchanged.
	 */
	public function empty_footer_text_on_onboarding( $footer_text ) {
		if ( class_exists( 'RTEC_Onboarding' ) && RTEC_Onboarding::should_suppress_setup_footer_chrome() ) {
			return '';
		}
		return $footer_text;
	}

	public function rating_prompt( $footer_text ) {

		if ( empty( $_GET['page'] ) ) {
			return $footer_text;
		}
		if ( class_exists( 'RTEC_Onboarding' ) && RTEC_Onboarding::should_suppress_setup_footer_chrome() ) {
			return $footer_text;
		}
		if ( strpos( $_GET['page'], 'registrations-for-the-events-calendar' ) !== 0 && strpos( $_GET['page'], 'rtec-' ) !== 0 ) {
			return $footer_text;
		}
		$review_url = 'https://wordpress.org/support/plugin/registrations-for-the-events-calendar/reviews/?filter=5';
		// Translators: %1$s is the plugin name link, %2$s is the star rating link
		$footer_text = sprintf(
			__( 'Please rate %1$s %2$s to support our plugin', 'registrations-for-the-events-calendar' ),
			'<a href="' . $review_url . '" target="_blank" rel="noopener noreferrer" class="rtec-rating"><strong>Registrations for the Events Calendar</strong></a>',
			'<a href="' . $review_url . '" target="_blank" rel="noopener noreferrer" class="rtec-rating">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
		);

		return $footer_text;
	}
}

/**
 * Create the admin menus and pages
 *
 * @since 1.0
 */
function RTEC_ADMIN() {

	$admin = new RTEC_Admin();
}
RTEC_ADMIN();
