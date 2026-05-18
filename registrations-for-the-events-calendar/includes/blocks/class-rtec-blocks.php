<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Registration form block and Attendee List block.
 *
 * @since 2.14 (blocks), 3.0 (category, REST, new UI, Attendee List block)
 */
class RTEC_Blocks {

	/**
	 * Indicates if current integration is allowed to load.
	 *
	 * @since 2.14
	 *
	 * @return bool
	 */
	public function allow_load() {
		return function_exists( 'register_block_type' );
	}

	/**
	 * Loads an integration.
	 *
	 * @since 2.14
	 */
	public function load() {
		$this->hooks();
	}

	const BLOCK_CATEGORY_SLUG = 'rtec';

	/**
	 * Integration hooks.
	 *
	 * @since 2.14
	 */
	protected function hooks() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
		add_filter( 'rtec_event_meta', array( $this, 'block_editor_event_meta_changes' ), 99, 1 );
	}

	/**
	 * Register custom block category "Event Registration".
	 *
	 * @since 3.0
	 *
	 * @param array                   $block_categories     Array of block categories.
	 * @param \WP_Block_Editor_Context $block_editor_context Block editor context.
	 * @return array
	 */
	public function register_block_category( $block_categories, $block_editor_context ) {
		$block_categories[] = array(
			'slug'  => self::BLOCK_CATEGORY_SLUG,
			'title' => __( 'Event Registration', 'registrations-for-the-events-calendar' ),
			'icon'  => null,
		);
		return $block_categories;
	}

	/**
	 * Register REST route for event search in block editor.
	 *
	 * @since 3.0
	 */
	public function register_rest_routes() {
		register_rest_route(
			'rtec/v1',
			'/search-events',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'search'           => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'current_event_id' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'absint',
					),
					'per_page'         => array(
						'type'              => 'integer',
						'default'           => 25,
						'sanitize_callback' => 'absint',
					),
				),
				'callback'            => array( $this, 'rest_search_events' ),
			)
		);
	}

	/**
	 * REST callback: search/fetch events for block editor.
	 *
	 * @since 3.0
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_search_events( $request ) {
		$search           = $request->get_param( 'search' );
		$current_event_id = (int) $request->get_param( 'current_event_id' );
		$per_page         = (int) $request->get_param( 'per_page' );
		$per_page         = min( 50, max( 1, $per_page ) );

		$post_type = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		global $rtec_options;

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_key'       => '_EventStartDate',
		);

		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_EventStartDate',
				'value'   => gmdate( 'Y-m-d H:i', time() + rtec_get_utc_offset() ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
		if ( isset( $rtec_options['disable_by_default'] ) && $rtec_options['disable_by_default'] === true ) {
			$meta_query[] = array(
				'key'     => '_RTECregistrationsDisabled',
				'value'   => '0',
				'compare' => '=',
			);
		} else {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_RTECregistrationsDisabled',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_RTECregistrationsDisabled',
					'value'   => '1',
					'compare' => '!=',
				),
			);
		}
		$args['meta_query'] = $meta_query;

		if ( $search !== '' ) {
			$args['s'] = $search;
		}

		$posts  = get_posts( $args );
		$events  = array();
		$seen_ids = array();

		if ( $current_event_id > 0 ) {
			$current = get_post( $current_event_id );
			if ( $current && $current->post_type === $post_type && $current->post_status === 'publish' ) {
				$events[] = array(
					'id'    => $current_event_id,
					'title' => $current->post_title . ' (' . ( function_exists( 'tribe_get_start_date' ) ? tribe_get_start_date( $current_event_id, false ) : '' ) . ')',
				);
				$seen_ids[ $current_event_id ] = true;
			}
		}

		foreach ( $posts as $post ) {
			if ( isset( $seen_ids[ $post->ID ] ) ) {
				continue;
			}
			$seen_ids[ $post->ID ] = true;
			$events[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title . ' (' . tribe_get_start_date( $post->ID, false ) . ')',
			);
		}

		return rest_ensure_response( array( 'events' => $events ) );
	}

	/**
	 * Register block types (Registration form and Attendee List).
	 *
	 * @since 2.14
	 */
	public function register_block() {
		if ( ! class_exists( 'Tribe__Main' ) ) {
			return;
		}

		rtec_register_frontend_styles();

		wp_register_style(
			'rtec-blocks-styles',
			rtec_frontend_asset_url( 'assets/frontend/css/rtec-blocks.css' ),
			array( 'rtec_styles', 'wp-edit-blocks' ),
			RTEC_VERSION
		);

		$block_styles = array( 'rtec_common', 'rtec_styles', 'rtec-blocks-styles' );

		$attributes = array(
			'shortcodeSettings' => array( 'type' => 'string' ),
			'eventID'           => array(
				'type'    => 'string',
				'default' => 'auto',
			),
			'isTribeEvent'      => array( 'type' => 'boolean' ),
			'executed'          => array( 'type' => 'boolean' ),
			'showheader'        => array( 'type' => 'boolean', 'default' => false ),
			'showtools'         => array( 'type' => 'boolean', 'default' => false ),
			'attendeelist'      => array( 'type' => 'boolean', 'default' => false ),
			'hidden'            => array( 'type' => 'boolean', 'default' => false ),
		);

		register_block_type(
			'rtec/rtec-form-block',
			array(
				'api_version'     => 3,
				'attributes'      => $attributes,
				'render_callback' => array( $this, 'get_form_html' ),
				'category'        => self::BLOCK_CATEGORY_SLUG,
				'editor_style'    => $block_styles,
				'style'           => array( 'rtec_common', 'rtec_styles' ),
			)
		);

		register_block_type(
			'rtec/rtec-attendee-list-block',
			array(
				'api_version'     => 3,
				'category'        => self::BLOCK_CATEGORY_SLUG,
				'attributes'      => array(
					'eventID'    => array(
						'type'    => 'string',
						'default' => 'auto',
					),
					'showheader' => array( 'type' => 'boolean', 'default' => false ),
				),
				'render_callback' => array( $this, 'render_attendee_list_block' ),
				'editor_style'    => $block_styles,
				'style'           => array( 'rtec_common', 'rtec_styles' ),
			)
		);
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @since 2.14
	 */
	public function enqueue_block_editor_assets() {
		if ( ! function_exists( 'tribe_get_start_date' ) ) {
			return;
		}
		rtec_scripts_and_styles();
		wp_enqueue_script(
			'rtec-form-block',
			rtec_frontend_asset_url( 'assets/frontend/js/rtec-blocks.js' ),
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-api-fetch', 'wp-block-editor', 'wp-components' ),
			RTEC_VERSION,
			true
		);

		$post_type = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		global $rtec_options;
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => '_EventEndDate',
				'value'   => gmdate( 'Y-m-d H:i', time() + rtec_get_utc_offset() ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
		if ( isset( $rtec_options['disable_by_default'] ) && $rtec_options['disable_by_default'] === true ) {
			$args['meta_query'][] = array(
				'key'     => '_RTECregistrationsDisabled',
				'value'   => '0',
				'compare' => '=',
			);
		} else {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array( 'key' => '_RTECregistrationsDisabled', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_RTECregistrationsDisabled', 'value' => '1', 'compare' => '!=' ),
			);
		}
		$upcoming_posts = get_posts( $args );
		$upcoming_event_array = array();
		if ( ! empty( $upcoming_posts ) ) {
			foreach ( $upcoming_posts as $post ) {
				$upcoming_event_array[] = array(
					'id'    => $post->ID,
					'title' => $post->post_title . ' (' . tribe_get_start_date( $post->ID, false ) . ')',
				);
			}
		}

		$i18n = array(
			'registration'             => esc_html__( 'Registration', 'registrations-for-the-events-calendar' ),
			'addSettings'              => esc_html__( 'Add Settings', 'registrations-for-the-events-calendar' ),
			'shortcodeSettings'        => esc_html__( 'Shortcode Settings', 'registrations-for-the-events-calendar' ),
			'example'                  => esc_html__( 'Example', 'registrations-for-the-events-calendar' ),
			'preview'                  => esc_html__( 'Apply Changes', 'registrations-for-the-events-calendar' ),
			'whichevent'               => esc_html__( 'Choose an event', 'registrations-for-the-events-calendar' ),
			'showAttendeeTools'        => esc_html__( 'Show tools', 'registrations-for-the-events-calendar' ),
			'showAttendeeListAboveForm' => esc_html__( 'Show attendee list', 'registrations-for-the-events-calendar' ),
			'showFormInitially'        => esc_html__( 'Show form initially', 'registrations-for-the-events-calendar' ),
			'attendeeList'             => esc_html__( 'Attendee List', 'registrations-for-the-events-calendar' ),
			'attendeeListDesc'         => esc_html__( 'Display the list of attendees for an event.', 'registrations-for-the-events-calendar' ),
			'showEventHeader'          => esc_html__( 'Show event header', 'registrations-for-the-events-calendar' ),
			'eventAuto'                => esc_html__( 'Auto', 'registrations-for-the-events-calendar' ),
			'eventAutoHelp'            => esc_html__( 'Choose Auto to show the current event on single event pages; otherwise it uses the next upcoming event.', 'registrations-for-the-events-calendar' ),
			'searchEventsPlaceholder'  => esc_html__( 'Search events…', 'registrations-for-the-events-calendar' ),
		);

		wp_localize_script(
			'rtec-form-block',
			'rtec_block_editor',
			array(
				'wpnonce'          => wp_create_nonce( 'rtec-blocks' ),
				'canShowFeed'      => true,
				'upcoming'         => $upcoming_event_array,
				'shortcodeSettings' => '',
				'i18n'             => $i18n,
				'searchEventsUrl'  => rest_url( 'rtec/v1/search-events' ),
				'restNonce'        => wp_create_nonce( 'wp_rest' ),
				'blockCategory'    => self::BLOCK_CATEGORY_SLUG,
			)
		);
	}

	/**
	 * Get next upcoming event ID that allows registration.
	 *
	 * @since 3.0
	 *
	 * @return int
	 */
	protected function get_next_upcoming_event_id() {
		$post_type = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		global $rtec_options;
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_key'       => '_EventStartDate',
		);
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => '_EventStartDate',
				'value'   => gmdate( 'Y-m-d H:i', time() + rtec_get_utc_offset() ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
		if ( isset( $rtec_options['disable_by_default'] ) && $rtec_options['disable_by_default'] === true ) {
			$args['meta_query'][] = array(
				'key'     => '_RTECregistrationsDisabled',
				'value'   => '0',
				'compare' => '=',
			);
		} else {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array( 'key' => '_RTECregistrationsDisabled', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_RTECregistrationsDisabled', 'value' => '1', 'compare' => '!=' ),
			);
		}
		$posts = get_posts( $args );
		return ! empty( $posts ) ? (int) $posts[0]->ID : 0;
	}

	/**
	 * Get form HTML to display in the Registration block.
	 *
	 * @since 2.14
	 *
	 * @param array $attr Block attributes.
	 * @return string
	 */
	public function get_form_html( $attr ) {
		$return             = '';
		$shortcode_settings  = isset( $attr['shortcodeSettings'] ) ? $attr['shortcodeSettings'] : '';
		$is_tribe_event      = isset( $attr['isTribeEvent'] ) ? $attr['isTribeEvent'] : false;
		$event_id            = ! empty( $attr['eventID'] ) ? $attr['eventID'] : false;

		// "auto": on single event pages, use current event; otherwise use next upcoming event.
		if ( $event_id === 'auto' ) {
			$post_type = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
			if ( is_singular( $post_type ) ) {
				$event_id = (int) get_the_ID();
			} else {
				$event_id = $this->get_next_upcoming_event_id();
			}
		}
		if ( empty( $event_id ) ) {
			global $post;
			$event_id = isset( $post->ID ) ? $post->ID : false;
		}

		$shortcode_settings_trimmed = trim( (string) $shortcode_settings );
		if ( $shortcode_settings_trimmed === '' ) {
			$parts = array();
			if ( ! empty( $attr['showheader'] ) ) {
				$parts[] = 'showheader="true"';
			}
			if ( ! empty( $attr['showtools'] ) ) {
				$parts[] = 'showtools="true"';
			}
			if ( ! empty( $attr['attendeelist'] ) ) {
				$parts[] = 'attendeelist="true"';
			}
			if ( ! empty( $attr['hidden'] ) ) {
				$parts[] = 'hidden="true"';
			}
			$shortcode_settings = implode( ' ', $parts );
		} else {
			$shortcode_settings = str_replace( array( '[rtec-registration-form', ']' ), '', $shortcode_settings );
		}

		if ( $is_tribe_event ) {
			$shortcode_settings = 'tribe_flag=true ' . $shortcode_settings;
		}
		if ( $event_id ) {
			$shortcode_settings = 'event=' . $event_id . ' ' . $shortcode_settings;
		}
		$return .= do_shortcode( '[rtec-registration-form ' . $shortcode_settings . ']' );
		return $return;
	}

	/**
	 * Resolve event ID for blocks: attribute, "auto" (current event on single event pages, otherwise next upcoming), or current post when singular.
	 *
	 * @since 3.0
	 *
	 * @param array $attr    Block attributes.
	 * @param int   $fallback Fallback ID.
	 * @return int
	 */
	protected function resolve_event_id_for_block( $attr, $fallback = 0 ) {
		$event_id_raw = isset( $attr['eventID'] ) ? $attr['eventID'] : '';
		// "auto": on single event pages, use current event; otherwise use next upcoming event.
		if ( $event_id_raw === 'auto' ) {
			$post_type = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
			if ( is_singular( $post_type ) ) {
				return (int) get_the_ID();
			}
			return $this->get_next_upcoming_event_id();
		}
		$event_id = ! empty( $event_id_raw ) ? (int) $event_id_raw : 0;
		if ( $event_id > 0 ) {
			return $event_id;
		}
		$post_type = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		if ( is_singular( $post_type ) ) {
			return (int) get_the_ID();
		}
		return $fallback;
	}

	/**
	 * Render callback for Attendee List block.
	 *
	 * @since 3.0
	 *
	 * @param array $attr Block attributes.
	 * @return string
	 */
	public function render_attendee_list_block( $attr ) {
		$event_id = $this->resolve_event_id_for_block( $attr );
		if ( $event_id <= 0 ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<div class="rtec-yellow-message"><span>' . esc_html__( 'Please select an event in the block settings to show the attendee list.', 'registrations-for-the-events-calendar' ) . '</span></div>';
			}
			return '';
		}
		$showheader = ! empty( $attr['showheader'] );
		$shortcode  = '[rtec-attendee-list event=' . $event_id . ' showheader="' . ( $showheader ? 'true' : 'false' ) . '"]';
		return do_shortcode( $shortcode );
	}

	/**
	 * Adjust event meta in block editor for preview.
	 *
	 * @since 2.14
	 *
	 * @param array $event_meta Event meta.
	 * @return array
	 */
	public function block_editor_event_meta_changes( $event_meta ) {
		if ( ! self::is_gb_editor() ) {
			return $event_meta;
		}
		$event_meta['registration_deadline']  = time() + 2000;
		$event_meta['registrations_disabled'] = false;
		return $event_meta;
	}

	/**
	 * Check if request is from block editor.
	 *
	 * @since 2.14
	 *
	 * @return bool
	 */
	public static function is_gb_editor() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
