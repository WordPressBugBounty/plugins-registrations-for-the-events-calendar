<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
$dismiss_new_needed = class_exists( 'RTEC_New_Registration_Alerts_Service' ) && RTEC_New_Registration_Alerts_Service::instance()->get_new_count() > 0;
$settings           = $this->settings;
$event_search       = isset( $settings['event_search'] ) ? $settings['event_search'] : '';
$admin_base_url     = admin_url( 'admin.php' );
?>
<div class="rtec-toolbar rtec-registrations-filter-bar wp-filter">
	<div class="rtec-filter-bar">
		<div class="rtec-flex-align-center rtec-registrations-filters">
		<form id="rtec-toolbar-form" action="<?php echo esc_url( $admin_base_url ); ?>" method="get" class="rtec-toolbar-filters-form" style="margin-bottom: 0;">
			<input type="hidden" name="page" value="<?php echo esc_attr( RTEC_MENU_SLUG ); ?>">
			<input type="hidden" name="tab" value="overview">
			<input type="hidden" name="v" value="<?php echo esc_attr( $settings['v'] ); ?>">
			<input type="hidden" name="event_search" id="rtec-toolbar-form-event-search" value="<?php echo esc_attr( $event_search ); ?>">
			<div class="view-switch rtec-grid-view-switch">
				<a href="<?php $this->the_toolbar_href( 'v', 'list' ); ?>" class="view-list
														<?php
														if ( $settings['v'] === 'list' ) {
															echo ' current';}
														?>
				">
					<span class="screen-reader-text"><?php esc_html_e( 'List View', 'registrations-for-the-events-calendar' ); ?></span>
				</a>
				<a href="<?php $this->the_toolbar_href( 'v', 'grid' ); ?>" class="view-grid
														<?php
														if ( $settings['v'] === 'grid' ) {
															echo ' current';}
														?>
				">
					<span class="screen-reader-text"><?php esc_html_e( 'Grid View', 'registrations-for-the-events-calendar' ); ?></span>
				</a>
			</div>
			<label for="rtec-registrations-date" class="screen-reader-text"><?php esc_html_e( 'Filter by start date', 'registrations-for-the-events-calendar' ); ?></label>
			<select id="rtec-registrations-date" name="qtype" class="registrations-filters">
				<option value="upcoming" 
				<?php
				if ( $settings['qtype'] === 'upcoming' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'View Upcoming', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="cur" 
				<?php
				if ( $settings['qtype'] === 'cur' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'View Current', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="past" 
				<?php
				if ( $settings['qtype'] === 'past' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'View Past', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="hid" 
				<?php
				if ( $settings['qtype'] === 'hid' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'View Hidden from Listing', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="start" 
				<?php
				if ( $settings['qtype'] === 'start' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'Select Start Date', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="all" 
				<?php
				if ( $settings['qtype'] === 'all' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'View All', 'registrations-for-the-events-calendar' ); ?></option>
			</select>
			<label for="rtec-date-picker" class="screen-reader-text"><?php esc_html_e( 'Filter by event start date', 'registrations-for-the-events-calendar' ); ?></label>
			<input type="text" id="rtec-date-picker" name="start" value="<?php echo date( 'm/d/Y', strtotime( $settings['start'] ) ); ?>" class="rtec-date-picker" style="vertical-align: middle;
																					<?php
																					if ( $settings['qtype'] !== 'start' ) {
																						echo 'display: none;';}
																					?>
			"/>
			<label for="rtec-registrations-reg" class="screen-reader-text"><?php esc_html_e( 'Filter by registrations', 'registrations-for-the-events-calendar' ); ?></label>
			<select id="rtec-registrations-reg" name="with" class="registrations-filters">
				<option value="with" 
				<?php
				if ( $settings['with'] === 'with' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'With registrations enabled', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="either" 
				<?php
				if ( $settings['with'] === 'either' ) {
					echo 'selected';}
				?>
				><?php esc_html_e( 'With/without registrations', 'registrations-for-the-events-calendar' ); ?></option>
			</select>
			<button id="rtec-filter-go" type="button" class="button rtec-toolbar-button" data-rtec-view-settings="<?php echo esc_attr( wp_json_encode( $settings ) ); ?>"><?php esc_html_e( 'Go', 'registrations-for-the-events-calendar' ); ?></button>
		</form>
		</div>
		<div class="rtec-flex-align-center rtec-search-box">
			<form method="get" class="rtec-search-form" id="rtec-event-search-form" action="<?php echo esc_url( $admin_base_url ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( RTEC_MENU_SLUG ); ?>">
				<input type="hidden" name="tab" value="overview">
				<input type="hidden" name="v" value="<?php echo esc_attr( $settings['v'] ); ?>">
				<input type="hidden" name="qtype" value="<?php echo esc_attr( $settings['qtype'] ); ?>">
				<input type="hidden" name="with" value="<?php echo esc_attr( $settings['with'] ); ?>">
				<input type="hidden" name="start" value="<?php echo esc_attr( $settings['start'] ); ?>">
				<p class="search-box">
					<span class="rtec-toolbar-icon"><?php echo RTEC_Icon::get( 'search' ); ?></span>
					<label class="screen-reader-text" for="rtec-event-search-input"><?php esc_attr_e( 'Search events', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="search" id="rtec-event-search-input" name="event_search" value="<?php echo esc_attr( $event_search ); ?>" placeholder="<?php esc_attr_e( 'Search events', 'registrations-for-the-events-calendar' ); ?>">
				</p>
			</form>
		</div>
	</div>
	<?php if ( $dismiss_new_needed ) : ?>
		<a id="rtec-new-dismiss" href="JavaScript:void(0);" class="rtec-email-creator-send rtec-inline-flex rtec-icon-text"><span class="rtec-icon"><?php echo RTEC_Icon::get( 'tag' ); ?></span> <?php esc_html_e( 'dismiss notices', 'registrations-for-the-events-calendar' ); ?></a>
	<?php endif; ?>
</div>