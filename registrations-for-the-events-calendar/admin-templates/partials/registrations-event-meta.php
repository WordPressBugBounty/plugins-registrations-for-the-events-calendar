<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$event_id    = $event_obj->event_meta['post_id'];
$date_format = 'F jS, ' . rtec_get_time_format();
$event_post  = $event_id ? get_post( $event_id ) : null;

// Attendance: icon + count text (no MVT in free)
$attendance_icon  = '<span class="rtec-icon" aria-hidden="true">' . RTEC_Icon::get( 'registration' ) . '</span>';
$attendance_text  = $event_obj->get_registration_text( array(), $event_obj->event_meta['num_registered'] );
$attendance_html  = '<span class="rtec-event-meta-attendance rtec-event-meta-item rtec-flex-align-center">' . $attendance_icon . ' ' . esc_html( $attendance_text ) . '</span>';

// Location + venue (and organizers if TEC provides them)
$location_icon      = '<span class="rtec-event-meta-location rtec-icon" aria-hidden="true">' . RTEC_Icon::get( 'location' ) . '</span>';
$event_details_array = array( '<span class="rtec-flex-align-center">' . $location_icon . ' ' . esc_html( $event_obj->event_meta['venue_title'] ) . '</span>' );
if ( $event_post && function_exists( 'tribe_get_organizer_ids' ) && function_exists( 'tribe_get_organizer' ) ) {
	$organizer_ids = tribe_get_organizer_ids( $event_id );
	if ( ! empty( $organizer_ids ) && is_array( $organizer_ids ) ) {
		foreach ( $organizer_ids as $organizer_id ) {
			$event_details_array[] = esc_html( tribe_get_organizer( $organizer_id ) );
		}
	}
}

// Schedule: use TEC schedule details if available, else plain date range.
$schedule_details = '';
if ( $event_post && function_exists( 'tribe_events_event_schedule_details' ) ) {
	$schedule_details = tribe_events_event_schedule_details( $event_id, '<p class="rtec-event-date">', '</p>' );
}
if ( $schedule_details === '' ) {
	if ( ! $event_post ) {
		$schedule_details = '<p class="rtec-event-date">' . esc_html__( 'Event no longer exists.', 'registrations-for-the-events-calendar' ) . '</p>';
	} else {
		$schedule_details  = '<p class="rtec-event-date">';
		$schedule_details .= date_i18n( $date_format, strtotime( $event_obj->event_meta['start_date'] ) );
		$schedule_details .= ' ' . __( 'to', 'registrations-for-the-events-calendar' ) . ' ';
		$schedule_details .= '<span class="rtec-end-time">' . date_i18n( $date_format, strtotime( $event_obj->event_meta['end_date'] ) ) . '</span>';
		$schedule_details .= '</p>';
	}
}
?>

<div class="rtec-outline">
	<?php if ( $event_obj->view_type !== 'single' ) : ?>
		<a href="<?php $this->the_detailed_view_href( $event_id, '' ); ?>"><h3><?php echo esc_html( $event_obj->event_meta['title'] ); ?></h3></a>
	<?php else : ?>
		<h3><?php echo esc_html( $event_obj->event_meta['title'] ); ?></h3>
	<?php endif; ?>
	<?php echo $schedule_details; ?>
</div>

<div class="rtec-venue-highlight">
	<div class="rtec-event-meta-details-row rtec-event-meta-item-wrap rtec-flex-align-center">
		<?php echo $attendance_html; ?>
		<span class="rtec-event-meta-item rtec-flex-align-center"><?php echo implode( ' | ', $event_details_array ); ?></span>
	</div>
</div>

<?php if ( $event_obj->view_type !== 'single' && $event_post ) : ?>
	<div class="rtec-event-actions rtec-clear">
		<a href="<?php echo esc_url( get_the_permalink( $event_id ) ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text" target="_blank" rel="noopener noreferrer"><?php echo RTEC_Icon::get( 'eye' ); ?> <?php esc_html_e( 'View Event', 'registrations-for-the-events-calendar' ); ?></a>
		<?php if ( current_user_can( 'edit_posts' ) ) : ?>
			<a href="<?php echo esc_url( get_edit_post_link( $event_id ) . '#rtec-event-details' ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text" target="_blank" rel="noopener noreferrer"><?php echo RTEC_Icon::get( 'edit' ); ?> <?php esc_html_e( 'Event Options', 'registrations-for-the-events-calendar' ); ?></a>
		<?php endif; ?>
		<a href="<?php $this->the_detailed_view_href( $event_id, '' ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text"><?php echo RTEC_Icon::get( 'plus' ); ?> <?php esc_html_e( 'Manage Registrations', 'registrations-for-the-events-calendar' ); ?></a>
	</div>
<?php endif; ?>
