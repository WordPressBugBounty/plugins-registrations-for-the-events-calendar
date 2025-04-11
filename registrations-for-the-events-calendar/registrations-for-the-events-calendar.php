<?php
/*
Plugin Name: Registrations for The Events Calendar
Description: Collect and manage event registrations with a customizable form and email template. This plugin requires The Events Calendar by Modern Tribe to work.
Version: 2.13.5
Author: Roundup WP
Author URI: roundupwp.com
License: GPLv2 or later
Text Domain: registrations-for-the-events-calendar
*/

/*
Copyright 2025 by Roundup WP LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
/**
 * @package RTEC
 * @author Roundup WP
 * @version 1.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Plugin version.
if ( ! defined( 'RTEC_VERSION' ) ) {
	define( 'RTEC_VERSION', '2.13.5' );
}
// Plugin Folder Path.
if ( ! defined( 'RTEC_PLUGIN_DIR' ) ) {
	define( 'RTEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
// Plugin Folder Path.
if ( ! defined( 'RTEC_PLUGIN_URL' ) ) {
	define( 'RTEC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'RTEC_TEC_VER_STRING' ) ) {
	define( 'RTEC_TEC_VER_STRING', '.6.10.1' );
}

// Check for The Events Calendar to be active
function rtec_TEC_check() {
}
add_action( 'plugins_loaded', 'rtec_TEC_check' );

if ( ! class_exists( 'Registrations_For_The_Events_Calendar' ) ) :

	/**
	 * Main Registrations_For_The_Events_Calendar Class.
	 *
	 * Design pattern inspired by Pippin Williamson's Easy Digital Downloads
	 *
	 * @since 1.0
	 */
	final class Registrations_For_The_Events_Calendar {
		/** Singleton *************************************************************/
		/**
		 * @var Registrations_For_The_Events_Calendar
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * @var Registrations_For_The_Events_Calendar
		 * @since 1.0
		 */
		public $form;

		/**
		 * @var Registrations_For_The_Events_Calendar
		 * @since 1.0
		 */
		public $submission;

		/**
		 * @var Registrations_For_The_Events_Calendar
		 * @since 1.0
		 */
		public $db_frontend;

		/**
		 * Main Registrations_For_The_Events_Calendar Instance.
		 *
		 * Only on instance of the form and functions at a time
		 *
		 * @since 1.0
		 * @return object|Registrations_For_The_Events_Calendar
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Registrations_For_The_Events_Calendar ) ) {
				self::$instance = new Registrations_For_The_Events_Calendar();
				self::$instance->constants();
				self::$instance->includes();
				self::$instance->form        = new RTEC_Form();
				self::$instance->db_frontend = new RTEC_Db();
				if ( isset( $_POST['rtec_email_submission'] ) && '1' === $_POST['rtec_email_submission'] ) {
					$sanitized_post = array();
					foreach ( $_POST as $post_key => $raw_post_value ) {
						$sanitized_post[ $post_key ] = sanitize_text_field( $raw_post_value );
					}
					self::$instance->submission  = new RTEC_Submission( $sanitized_post );
					self::$instance->db_frontend = new RTEC_Db();
				}
			}
			return self::$instance;
		}

		/**
		 * Throw error on object clone.
		 *
		 * @since 1.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'registrations-for-the-events-calendar' ), '1.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'registrations-for-the-events-calendar' ), '1.0' );
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function constants() {
			// Plugin Base Name
			if ( ! defined( 'RTEC_PLUGIN_BASENAME' ) ) {
				define( 'RTEC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			}
			// Plugin Title.
			if ( ! defined( 'RTEC_TITLE' ) ) {
				define( 'RTEC_TITLE', 'Registrations for the Events Calendar' );
			}
			// Db version.
			if ( ! defined( 'RTEC_DBVERSION' ) ) {
				define( 'RTEC_DBVERSION', '1.6' );
			}
			// Table Name.
			if ( ! defined( 'RTEC_TABLENAME' ) ) {
				define( 'RTEC_TABLENAME', 'rtec_registrations' );
			}
			// Tribe Events Post Type
			if ( ! defined( 'RTEC_TRIBE_EVENTS_POST_TYPE' ) ) {
				define( 'RTEC_TRIBE_EVENTS_POST_TYPE', 'tribe_events' );
			}
			// Tribe Menu Page.
			if ( ! defined( 'RTEC_TRIBE_MENU_PAGE' ) ) {
				define( 'RTEC_TRIBE_MENU_PAGE', 'edit.php?post_type=tribe_events' );
			}
			if ( ! defined( 'RTEC_ADMIN_URL' ) ) {
				define( 'RTEC_ADMIN_URL', 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar' );
			}
			if ( ! defined( 'RTEC_MENU_SLUG' ) ) {
				define( 'RTEC_MENU_SLUG', 'registrations-for-the-events-calendar' );
			}
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function includes() {
			global $rtec_options;
			$rtec_options = get_option( 'rtec_options', array() );
			require_once RTEC_PLUGIN_DIR . 'inc/class-rtec-db.php';
			require_once RTEC_PLUGIN_DIR . 'inc/helper-functions.php';
			require_once RTEC_PLUGIN_DIR . 'inc/form/class-rtec-form.php';
			require_once RTEC_PLUGIN_DIR . 'inc/form/form-functions.php';
			require_once RTEC_PLUGIN_DIR . 'inc/class-rtec-submission.php';
			require_once RTEC_PLUGIN_DIR . 'inc/utils/class-rtec-templater.php';
			require_once RTEC_PLUGIN_DIR . 'inc/utils/class-rtec-settings.php';
			require_once RTEC_PLUGIN_DIR . 'inc/utils/class-rtec-defaults.php';

			require_once trailingslashit( RTEC_PLUGIN_DIR ) . 'inc/class-rtec-venue-query.php';
			require_once trailingslashit( RTEC_PLUGIN_DIR ) . 'inc/blocks/class-rtec-blocks.php';
			require_once trailingslashit( RTEC_PLUGIN_DIR ) . 'inc/class-rtec-wpml-lite.php';

			$rtec_blocks = new RTEC_Blocks();

			if ( $rtec_blocks->allow_load() ) {
				$rtec_blocks->load();
			}

			require_once RTEC_PLUGIN_DIR . 'inc/class-rtec-migration.php';
			$rtec_migration = new RTEC_Migration();
			$rtec_migration->init();
			if ( is_admin() ) {
				require_once RTEC_PLUGIN_DIR . 'inc/admin/class-rtec-db-admin.php';
				require_once RTEC_PLUGIN_DIR . 'inc/admin/admin-functions.php';
				require_once RTEC_PLUGIN_DIR . 'inc/admin/class-rtec-admin.php';
				require_once RTEC_PLUGIN_DIR . 'inc/admin/class-rtec-admin-registrations.php';
				require_once RTEC_PLUGIN_DIR . 'inc/admin/class-rtec-admin-event.php';
				require_once RTEC_PLUGIN_DIR . 'inc/admin/services/class-rtec-modal-service.php';
				$modal_service = new RTEC_Modal_Service();
				$modal_service->init_hooks();
				require_once RTEC_PLUGIN_DIR . 'inc/admin/services/class-rtec-notice-service.php';
				$notice_service = new RTEC_Notice_Service();
				$notice_service->init_hooks();
			}

			/*
			 * Services
			 */
			require_once trailingslashit( RTEC_PLUGIN_DIR ) . 'inc/services/class-rtec-placeholder-service.php';
			$placeholder_service = new RTEC_Placeholder_Service();
			$placeholder_service->init_hooks();

			require_once RTEC_PLUGIN_DIR . 'inc/services/class-rtec-frontend-modal-service.php';
			$modal_service = new RTEC_Frontend_Modal_Service();
			$modal_service->init_hooks();

			require_once RTEC_PLUGIN_DIR . 'inc/services/class-rtec-footer-listener-service.php';
			$footer_service = new RTEC_Footer_Listener_Service();
			$footer_service->init_hooks();

			require_once RTEC_PLUGIN_DIR . 'inc/services/class-rtec-wpml-service.php';
			$wpml_service = new RTEC_WPML_Service();
			$wpml_service->init_hooks();

			require_once RTEC_PLUGIN_DIR . 'inc/class-rtec-event.php';
			require_once RTEC_PLUGIN_DIR . 'inc/eventgoer/class-rtec-base-event-goer.php';
			require_once RTEC_PLUGIN_DIR . 'inc/eventgoer/class-rtec-logged-in-event-goer.php';
			require_once RTEC_PLUGIN_DIR . 'inc/eventgoer/class-rtec-visitor-event-goer.php';

		}

		public static function activation_scripts( $network_wide ) {
			if ( is_multisite() && $network_wide && function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
				// Get all blogs in the network and activate plugin on each one
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					self::install();
					restore_current_blog();
				}
			} else {
				self::install();
			}
			
			// Set transient for redirect
			set_transient( 'rtec_activation_redirect', true, 30 );
		}

		/**
		 * Add default settings and create the table in db
		 *
		 * @access public
		 * @since 1.0
		 * @return void
		 */
		public static function install() {
			$rtec_options = get_option( 'rtec_options', false );
			require_once plugin_dir_path( __FILE__ ) . 'inc/class-rtec-db.php';
			require_once plugin_dir_path( __FILE__ ) . 'inc/admin/class-rtec-db-admin.php';

			$db = new RTEC_Db_Admin();
			$db->create_table();

			if ( ! $rtec_options ) {
				$defaults = array(
					'first_show'        => true,
					'first_require'     => true,
					'last_show'         => true,
					'last_require'      => true,
					'email_show'        => true,
					'email_require'     => true,
					'phone_show'        => false,
					'phone_require'     => false,
					'phone_valid_count' => '7, 10',
					'recaptcha_require' => false,
					'other_show'        => false,
					'other_require'     => false,
					'message_source'    => 'custom',
				);
				// get form options from the db
				update_option( 'rtec_options', $defaults );
				// add cues to find the plugin for three days
				set_transient( 'rtec_new_messages', 'yes', 60 * 60 * 24 * 3 );
				set_transient( 'rtec_smtp_message', 'yes', 60 * 60 * 24 * 3 );

			}

			update_user_meta( get_current_user_id(), 'tribe-dismiss-notice-time-event-tickets-install', '1823773858' );
			update_user_meta( get_current_user_id(), 'tribe-dismiss-notice', 'event-tickets-install' );
			update_option( 'tec_events_onboarding_page_dismissed', true );
		}
	}
endif; // End if class_exists check.
register_activation_hook( __FILE__, array( 'Registrations_For_The_Events_Calendar', 'activation_scripts' ) );

function rtec_text_domain() {
	load_plugin_textdomain( 'registrations-for-the-events-calendar', false, basename( __DIR__ ) . '/lang' );
}
add_action( 'init', 'rtec_text_domain' );

function rtec_include_class() {
	if ( function_exists( 'tribe_events' )
		&& class_exists( 'TEC\Events\Custom_Tables\V1\WP_Query\Custom_Tables_Query' ) ) {
		require_once RTEC_PLUGIN_DIR . 'inc/class-rtec-events-query.php';
	}
}
add_action( 'init', 'rtec_include_class' );

function rtec_on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	if ( is_plugin_active_for_network( 'registrations-for-the-events-calendar/registrations-for-the-events-calendar.php' ) ) {
		switch_to_blog( $blog_id );

		Registrations_For_The_Events_Calendar::install();

		restore_current_blog();
	}
}
add_action( 'wpmu_new_blog', 'rtec_on_create_blog', 10, 6 );

function rtec_on_delete_blog( $tables ) {

	global $wpdb;
	$tables[] = $wpdb->prefix . 'rtec_registrations';

	return $tables;
}
add_filter( 'wpmu_drop_tables', 'rtec_on_delete_blog' );

function rtec_activation_redirect() {
	// Check if we should redirect
	if ( get_transient( 'rtec_activation_redirect' ) ) {
		// Delete the transient
		delete_transient( 'rtec_activation_redirect' );
		
		// Only redirect if we're on the main plugin page
		if ( ! isset( $_GET['activate-multi'] ) && ! is_network_admin() ) {
			// Safe redirect to the main plugin page
			wp_safe_redirect( admin_url( 'admin.php?page=registrations-for-the-events-calendar' ) );
			exit;
		}
	}
}
add_action( 'admin_init', 'rtec_activation_redirect' );

/**
 * The main function for Registrations_For_The_Events_Calendar
 *
 * The main function responsible for returning the one true Registrations_For_The_Events_Calendar
 * Instance to functions everywhere.
 *
 * @since 1.0
 * @return object|Registrations_For_The_Events_Calendar The one true Registrations_For_The_Events_Calendar Instance.
 */
function RTEC() {
	return Registrations_For_The_Events_Calendar::instance();
}
// Get rtec Running.
RTEC();
