<?php
/**
 * Placeholder reference table (UI only). Builds placeholder data and outputs
 * the reference table HTML (tabs, search, categories, insert buttons).
 * Does not perform runtime replacement; that stays in Templater / rtec_email_templating.
 *
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class RTEC_Placeholders {

	/**
	 * @var array
	 */
	protected $sanitized_data;

	/**
	 * @var string
	 */
	protected $context;

	/**
	 * @var bool
	 */
	protected $show_admin_placeholders;

	/**
	 * @param array  $sanitized_data         Optional. Data for filter context.
	 * @param string $context                Optional. e.g. 'email'. Passed to rtec_placeholders.
	 * @param bool   $show_admin_placeholders Optional. Whether to show admin-only placeholders.
	 */
	public function __construct( $sanitized_data = array(), $context = 'email', $show_admin_placeholders = false ) {
		$this->sanitized_data         = $sanitized_data;
		$this->context                = $context;
		$this->show_admin_placeholders = $show_admin_placeholders;
	}

	/**
	 * Category definitions for tabs. Filtered by rtec_placeholders_categories.
	 *
	 * @return array[] Array of [ 'name' => string, 'label' => string ].
	 */
	public function data_categories() {
		$categories = array(
			array( 'name' => 'event', 'label' => __( 'Event', 'registrations-for-the-events-calendar' ) ),
			array( 'name' => 'venue', 'label' => __( 'Venue', 'registrations-for-the-events-calendar' ) ),
			array( 'name' => 'organizer', 'label' => __( 'Organizer', 'registrations-for-the-events-calendar' ) ),
			array( 'name' => 'registration', 'label' => __( 'Registration', 'registrations-for-the-events-calendar' ) ),
			array( 'name' => 'actions', 'label' => __( 'Actions', 'registrations-for-the-events-calendar' ) ),
		);
		return apply_filters( 'rtec_placeholders_categories', $categories );
	}

	/**
	 * Full placeholder data (after rtec_placeholders filter). Each item has placeholder, description, category, value.
	 *
	 * @return array Associative array of placeholder key => definition.
	 */
	public function data() {
		$data = $this->build_placeholder_definitions();
		return apply_filters( 'rtec_placeholders', $data, $this->sanitized_data, $this->context );
	}

	/**
	 * Build default placeholder definitions for the reference table. No new placeholders; matches existing tokens.
	 * {nl} and similar hidden tokens are omitted (runtime-only).
	 *
	 * @return array
	 */
	protected function build_placeholder_definitions() {
		$definitions = array(
			// Event
			'event_title'   => array(
				'placeholder'  => '{event-title}',
				'description'  => __( 'Title of event.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'event_url'      => array(
				'placeholder'  => '{event-url}',
				'description'  => __( 'Plain text web address of event page.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'event_cost'     => array(
				'placeholder'  => '{event-cost}',
				'description'  => __( 'Cost of the event.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'start_date'     => array(
				'placeholder'  => '{start-date}',
				'description'  => __( 'Event start date.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'start_time'     => array(
				'placeholder'  => '{start-time}',
				'description'  => __( 'Event start time.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'end_date'       => array(
				'placeholder'  => '{end-date}',
				'description'  => __( 'Event end date.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'end_time'       => array(
				'placeholder'  => '{end-time}',
				'description'  => __( 'Event end time.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'ical_url'       => array(
				'placeholder'  => '{ical-url}',
				'description'  => __( 'Plain URL to download the event .ics file.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'ical_link'      => array(
				'placeholder'  => '{ical-link}',
				'description'  => __( 'Clickable HTML link for iCal download.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			'gcal_link'      => array(
				'placeholder'  => '{gcal-link}',
				'description'  => __( 'Clickable HTML link for Google Calendar.', 'registrations-for-the-events-calendar' ),
				'category'     => 'event',
				'value'        => '',
			),
			// Venue
			'venue'          => array(
				'placeholder'  => '{venue}',
				'description'  => __( 'Event venue/location.', 'registrations-for-the-events-calendar' ),
				'category'     => 'venue',
				'value'        => '',
			),
			'venue_address'   => array(
				'placeholder'  => '{venue-address}',
				'description'  => __( 'Venue street address.', 'registrations-for-the-events-calendar' ),
				'category'     => 'venue',
				'value'        => '',
			),
			'venue_city'      => array(
				'placeholder'  => '{venue-city}',
				'description'  => __( 'Venue city.', 'registrations-for-the-events-calendar' ),
				'category'     => 'venue',
				'value'        => '',
			),
			'venue_state'     => array(
				'placeholder'  => '{venue-state}',
				'description'  => __( 'Venue state/province.', 'registrations-for-the-events-calendar' ),
				'category'     => 'venue',
				'value'        => '',
			),
			'venue_zip'       => array(
				'placeholder'  => '{venue-zip}',
				'description'  => __( 'Venue zip code.', 'registrations-for-the-events-calendar' ),
				'category'     => 'venue',
				'value'        => '',
			),
			'venue_2'         => array(
				'placeholder'  => '{venue-2}',
				'description'  => __( 'Additional event venue/location. Related: {venue-address-2}, {venue-city-2}, {venue-state-2}, {venue-zip-2} (use venue-3, venue-4 prefix for more).', 'registrations-for-the-events-calendar' ),
				'category'     => 'venue',
				'value'        => '',
			),
			// Organizer
			'organizer_link' => array(
				'placeholder'  => '{organizer-link}',
				'description'  => __( 'Organizer name(s) with link(s) to organizer page(s).', 'registrations-for-the-events-calendar' ),
				'category'     => 'organizer',
				'value'        => '',
			),
			'organizer'       => array(
				'placeholder'  => '{organizer}',
				'description'  => __( 'Primary organizer\'s name.', 'registrations-for-the-events-calendar' ),
				'category'     => 'organizer',
				'value'        => '',
			),
			'organizer_email' => array(
				'placeholder'  => '{organizer-email}',
				'description'  => __( 'Primary organizer\'s email.', 'registrations-for-the-events-calendar' ),
				'category'     => 'organizer',
				'value'        => '',
			),
			'organizer_phone' => array(
				'placeholder'  => '{organizer-phone}',
				'description'  => __( 'Primary organizer\'s phone.', 'registrations-for-the-events-calendar' ),
				'category'     => 'organizer',
				'value'        => '',
			),
			'organizer_2'     => array(
				'placeholder'  => '{organizer-2}',
				'description'  => __( 'Second organizer\'s name. Related: {organizer-email-2}, {organizer-phone-2} (use organizer-3, organizer-email-3, organizer-phone-3 for third, etc.).', 'registrations-for-the-events-calendar' ),
				'category'     => 'organizer',
				'value'        => '',
			),
			// Registration
			'first'          => array(
				'placeholder'  => '{first}',
				'description'  => __( 'First name of registrant.', 'registrations-for-the-events-calendar' ),
				'category'     => 'registration',
				'value'        => '',
			),
			'last'           => array(
				'placeholder'  => '{last}',
				'description'  => __( 'Last name of registrant.', 'registrations-for-the-events-calendar' ),
				'category'     => 'registration',
				'value'        => '',
			),
			'email'          => array(
				'placeholder'  => '{email}',
				'description'  => __( 'Email of registrant.', 'registrations-for-the-events-calendar' ),
				'category'     => 'registration',
				'value'        => '',
			),
			'phone'          => array(
				'placeholder'  => '{phone}',
				'description'  => __( 'Phone number of registrant.', 'registrations-for-the-events-calendar' ),
				'category'     => 'registration',
				'value'        => '',
			),
			'other'          => array(
				'placeholder'  => '{other}',
				'description'  => __( 'Value entered in the "other" field.', 'registrations-for-the-events-calendar' ),
				'category'     => 'registration',
				'value'        => '',
			),
			// Actions
			'cancel_link' => array(
				'placeholder'  => '{cancel-link}',
				'description'  => __( 'Link for user to cancel their registration.', 'registrations-for-the-events-calendar' ),
				'category'     => 'actions',
				'value'        => '',
			),
			// Hidden from reference table; backwards support at runtime (same as {cancel-link}).
			'unregister_link' => array(
				'placeholder'  => '{unregister-link}',
				'description'  => __( 'Link for user to cancel their registration.', 'registrations-for-the-events-calendar' ),
				'category'     => 'actions',
				'value'        => '',
				'deprecated'   => true,
			),
			'cancel_button' => array(
				'placeholder'  => '{cancel-button}',
				'description'  => __( 'Button for user to cancel their registration.', 'registrations-for-the-events-calendar' ),
				'category'     => 'actions',
				'value'        => '',
			),
			// Hidden from reference table; backwards support at runtime (same as {cancel-button}).
			'unregister_button' => array(
				'placeholder'  => '{unregister-button}',
				'description'  => __( 'Button for user to cancel their registration.', 'registrations-for-the-events-calendar' ),
				'category'     => 'actions',
				'value'        => '',
				'deprecated'   => true,
			),
			'admin_view_in_dashboard' => array(
				'placeholder'  => '{admin-view-in-dashboard}',
				'description'  => __( 'Link to view this registration in the dashboard (notification email only).', 'registrations-for-the-events-calendar' ),
				'category'     => 'actions',
				'value'        => '',
			),
		);
		return $definitions;
	}

	/**
	 * Output the reference table HTML (tabs, search, categories, insert buttons).
	 *
	 * @param array|null $data       Optional. Placeholder data; defaults to $this->data().
	 * @param bool|null  $show_admin Optional. Override instance value.
	 * @param array|null $categories Optional. Category list; defaults to $this->data_categories().
	 * @return void Outputs HTML.
	 */
	public function placeholder_reference_table( $data = null, $show_admin = null, $categories = null ) {
		if ( $data === null ) {
			$data = $this->data();
		}
		if ( $show_admin === null ) {
			$show_admin = $this->show_admin_placeholders;
		}
		if ( $categories === null ) {
			$categories = $this->data_categories();
		}

		$category_names = wp_list_pluck( $categories, 'name' );
		$grouped        = array();
		foreach ( $category_names as $name ) {
			$grouped[ $name ] = array();
		}
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) || empty( $item['placeholder'] ) || empty( $item['category'] ) ) {
				continue;
			}
			// Hide deprecated placeholders from the reference table (still supported at runtime).
			if ( ! empty( $item['deprecated'] ) ) {
				continue;
			}
			$cat = $item['category'];
			if ( isset( $grouped[ $cat ] ) ) {
				$grouped[ $cat ][] = $item;
			}
		}

		$first = true;
		?>
		<div class="rtec-placeholder-reference">
			<div class="rtec-placeholder-header">
				<div class="rtec-placeholder-tabs">
					<?php foreach ( $categories as $cat ) : ?>
						<button type="button" class="rtec-placeholder-tab<?php echo $first ? ' rtec-active' : ''; ?>" data-category="<?php echo esc_attr( $cat['name'] ); ?>"><?php echo esc_html( $cat['label'] ); ?></button>
						<?php $first = false; ?>
					<?php endforeach; ?>
				</div>
				<div class="rtec-placeholder-search">
					<input type="text" class="rtec-placeholder-search-input" placeholder="<?php esc_attr_e( 'Search placeholders', 'registrations-for-the-events-calendar' ); ?>">
				</div>
			</div>
			<div class="rtec-placeholder-content">
				<?php
				$first_cat = true;
				foreach ( $categories as $cat ) :
					$items = isset( $grouped[ $cat['name'] ] ) ? $grouped[ $cat['name'] ] : array();
					?>
					<div class="rtec-placeholder-category<?php echo $first_cat ? ' rtec-active' : ''; ?>" data-category="<?php echo esc_attr( $cat['name'] ); ?>">
						<?php
						$index = 0;
						foreach ( $items as $item ) :
							$token = isset( $item['placeholder'] ) ? $item['placeholder'] : '';
							$desc  = isset( $item['description'] ) ? $item['description'] : '';
							$hidden_class = ( $index >= 5 ) ? ' rtec-placeholder-hidden' : '';
							$index++;
							?>
							<div class="rtec-placeholder-item<?php echo esc_attr( $hidden_class ); ?>">
								<div class="rtec-placeholder-info">
									<code class="rtec-placeholder-code"><?php echo esc_html( $token ); ?></code>
									<span class="rtec-placeholder-description"><?php echo esc_html( $desc ); ?></span>
								</div>
								<button type="button" class="rtec-placeholder-insert" data-placeholder="<?php echo esc_attr( $token ); ?>">+ <?php esc_html_e( 'Insert', 'registrations-for-the-events-calendar' ); ?></button>
							</div>
						<?php endforeach; ?>
						<?php if ( count( $items ) > 5 ) : ?>
							<button type="button" class="rtec-show-more-placeholders"><?php esc_html_e( 'Show more', 'registrations-for-the-events-calendar' ); ?></button>
						<?php endif; ?>
					</div>
					<?php
					$first_cat = false;
				endforeach;
				?>
			</div>
		</div>
		<?php
	}
}
